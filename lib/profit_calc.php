<?php
// ═══════════════════════════════════════════════════════════════════
// profit_calc.php — PURE finance math (no DB, no HTTP).
// Shared by api.php and the test harness so the numbers are verifiable.
//
// Covers roadmap items:
//   C1 — refund-aware profit (reverse COGS + units proportionally to refunds)
//   C2 — variation-level COGS (variation cost overrides product cost)
//   C6 — effective-dated cost history (use the cost that applied on the order date)
//   R1/R6 — VAT-correct break-even ROAS + scenario ladder
// ═══════════════════════════════════════════════════════════════════

if (!defined('PT_DEFAULT_VAT_RATE')) define('PT_DEFAULT_VAT_RATE', 25.0); // SE standard

/**
 * Resolve the unit cost for a line item.
 *
 * Precedence:
 *   1. variation cost (exact variation_id)         — C2
 *   2. product cost (product_id)
 * Within each, if an effective-dated history exists, the entry whose
 * effective_from is the latest date <= $order_date wins.  — C6
 *
 * @param array  $ctx  { costs_map, var_costs, cost_history }
 *                     costs_map    = [product_id => unit_cost]
 *                     var_costs    = [variation_id => unit_cost]
 *                     cost_history = [ "p{id}"|"v{id}" => [ ['from'=>'Y-m-d','cost'=>float], ... ] ]
 * @return array ['cost' => float, 'found' => bool]
 */
function pt_resolve_unit_cost(array $ctx, $product_id, $variation_id = 0, $order_date = null) {
    $history = $ctx['cost_history'] ?? [];

    // 1. Variation first
    if ($variation_id) {
        $h = pt_history_cost($history, 'v' . $variation_id, $order_date);
        if ($h !== null) return ['cost' => $h, 'found' => true];
        $vc = $ctx['var_costs'][$variation_id] ?? null;
        if ($vc !== null && $vc > 0) return ['cost' => (float)$vc, 'found' => true];
    }

    // 2. Product
    if ($product_id) {
        $h = pt_history_cost($history, 'p' . $product_id, $order_date);
        if ($h !== null) return ['cost' => $h, 'found' => true];
        $pc = $ctx['costs_map'][$product_id] ?? null;
        if ($pc !== null && $pc > 0) return ['cost' => (float)$pc, 'found' => true];
    }

    return ['cost' => 0.0, 'found' => false];
}

/** Pick the cost effective on $order_date from a sorted-or-unsorted history list. */
function pt_history_cost(array $history, $key, $order_date) {
    if (empty($history[$key]) || !$order_date) return null;
    $d = substr((string)$order_date, 0, 10);
    $best = null; $bestFrom = '';
    foreach ($history[$key] as $entry) {
        $from = substr((string)($entry['from'] ?? ''), 0, 10);
        if ($from === '' || $from > $d) continue;      // not yet effective
        if ($from >= $bestFrom) { $bestFrom = $from; $best = (float)($entry['cost'] ?? 0); }
    }
    return $best;
}

/**
 * Per-order metrics, refund-aware (C1).
 *
 * Refunds proportionally reduce revenue, VAT, COGS and units so gross margin
 * stays truthful. Payment fees and carrier shipping stay on the gross order
 * (those costs are really incurred even when a sale is later refunded) — a
 * deliberately conservative choice, documented so it can be revisited.
 *
 * @param array $ctx { costs_map, var_costs, cost_history, fee_config, ship_config }
 * @return array metrics for one order
 */
function pt_order_metrics(array $o, array $ctx) {
    $order_total = (float)($o['total'] ?? 0);        // incl VAT
    $tax         = (float)($o['total_tax'] ?? 0);
    $order_date  = $o['date_created'] ?? null;

    // Refund fraction (WC refund totals are negative or positive; use abs)
    $refunded = 0.0;
    foreach (($o['refunds'] ?? []) as $r) $refunded += abs((float)($r['total'] ?? 0));
    $keep = $order_total > 0 ? max(0.0, 1.0 - min(1.0, $refunded / $order_total)) : 1.0;

    $revenue_incl = $order_total * $keep;
    $tax_kept     = $tax * $keep;
    $revenue_excl = $revenue_incl - $tax_kept;

    // COGS + units (refund-adjusted)
    $cogs = 0.0; $units = 0.0; $has_gap = false;
    foreach (($o['line_items'] ?? []) as $li) {
        $qty = (int)($li['quantity'] ?? 0);
        $pid = (int)($li['product_id'] ?? 0);
        $vid = (int)($li['variation_id'] ?? 0);
        $units += $qty * $keep;
        $res = pt_resolve_unit_cost($ctx, $pid, $vid, $order_date);
        if ($res['found']) {
            $cogs += $res['cost'] * $qty * $keep;
        } elseif ($pid) {
            $has_gap = true;
        }
    }

    // Payment fee — match by method, fall back to '*'. On gross order (conservative).
    $pm = strtolower(trim($o['payment_method'] ?? ''));
    $fee = pt_fee_for($ctx['fee_config'] ?? [], $pm, $order_total);

    // Carrier shipping — domestic vs international (conservative: on gross)
    $country = strtoupper(trim($o['shipping']['country'] ?? $o['billing']['country'] ?? 'SE'));
    $ship_cfg = $ctx['ship_config'] ?? ['cost_domestic' => 0, 'cost_international' => 0];
    $ship_cost = ($country === 'SE')
        ? (float)$ship_cfg['cost_domestic']
        : (float)$ship_cfg['cost_international'];

    return [
        'revenue_incl' => $revenue_incl,
        'revenue_excl' => $revenue_excl,
        'tax'          => $tax_kept,
        'refunded'     => min($order_total, $refunded),
        'cogs'         => $cogs,
        'units'        => $units,
        'fees'         => $fee,
        'ship_cost'    => $ship_cost,
        'has_cogs_gap' => $has_gap,
        'keep'         => $keep,
    ];
}

/** Transaction fee for a payment method: rate% of $base + fixed, with '*' fallback. */
function pt_fee_for(array $fee_config, $method_slug, $base) {
    foreach ($fee_config as $fee) {
        if (($fee['method_slug'] ?? null) === $method_slug) {
            return ((float)$fee['rate'] / 100) * $base + (float)$fee['fixed'];
        }
    }
    foreach ($fee_config as $fee) {
        if (($fee['method_slug'] ?? null) === '*') {
            return ((float)$fee['rate'] / 100) * $base + (float)$fee['fixed'];
        }
    }
    return 0.0;
}

/**
 * VAT-correct break-even ROAS for a single product (R1/R6).
 *
 * $price is the VAT-INCLUSIVE selling price (as stored in WooCommerce and the
 * old ROAS calculator). Contribution is computed EX-VAT so it matches Profit
 * Tracker's actuals. Because Meta reports ROAS on VAT-inclusive revenue, the
 * break-even ROAS to compare against Meta uses the inclusive price in the
 * numerator: be_roas = price_incl / contribution.
 *
 * @return array full breakdown incl. a ROAS scenario ladder
 */
function pt_break_even($price, $cogs, $shipping, $fee_pct, $other = 0.0, $vat_rate = null) {
    $vat_rate = $vat_rate === null ? PT_DEFAULT_VAT_RATE : (float)$vat_rate;
    $price = (float)$price;
    $price_excl = $vat_rate > 0 ? $price / (1 + $vat_rate / 100) : $price;
    $tx = $price * ((float)$fee_pct / 100);                       // fee on gross charged amount
    $contribution = $price_excl - (float)$cogs - (float)$shipping - $tx - (float)$other;

    $margin = $price_excl > 0 ? ($contribution / $price_excl * 100) : 0;
    $be_roas = $contribution > 0 ? ($price / $contribution) : null;      // compare-to-Meta (incl VAT)

    $ladder = [];
    foreach ([1.0, 1.5, 2.0, 2.5, 3.0, 4.0, 5.0] as $r) {
        $ad = $price / $r;                    // spend per sale at this ROAS (incl-VAT revenue basis)
        $profit = $contribution - $ad;
        $ladder[] = [
            'roas'    => $r,
            'ad_cost' => round($ad, 2),
            'profit'  => round($profit, 2),
            'margin'  => $price_excl > 0 ? round($profit / $price_excl * 100, 1) : 0,
            'ok'      => $profit > 0,
        ];
    }

    return [
        'price_incl'   => round($price, 2),
        'price_excl'   => round($price_excl, 2),
        'vat_rate'     => $vat_rate,
        'cogs'         => round((float)$cogs, 2),
        'shipping'     => round((float)$shipping, 2),
        'fee_pct'      => (float)$fee_pct,
        'tx_fee'       => round($tx, 2),
        'other'        => round((float)$other, 2),
        'contribution' => round($contribution, 2),
        'margin'       => round($margin, 1),
        'be_roas'      => $be_roas === null ? null : round($be_roas, 2),
        'ladder'       => $ladder,
    ];
}

/**
 * Verdict for actual-vs-break-even ROAS (R3).
 * @return array ['verdict' => 'scale'|'hold'|'kill'|'unknown', 'ratio' => float|null]
 */
function pt_roas_verdict($actual_roas, $be_roas) {
    if (!$be_roas || $be_roas <= 0 || $actual_roas === null) {
        return ['verdict' => 'unknown', 'ratio' => null];
    }
    $ratio = $actual_roas / $be_roas;
    if ($ratio >= 1.25) $v = 'scale';       // comfortably above break-even
    elseif ($ratio >= 1.0) $v = 'hold';     // above break-even but thin
    else $v = 'kill';                       // losing money on ads
    return ['verdict' => $v, 'ratio' => round($ratio, 2)];
}
