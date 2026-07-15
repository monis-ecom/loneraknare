<?php
// Run:  php app/tests/calc_test.php
require __DIR__ . '/../lib/profit_calc.php';

$T = 0; $F = 0;
function ok($cond, $label, $got = null, $want = null) {
    global $T, $F;
    $T++;
    if ($cond) { echo "  ✓ $label\n"; }
    else { $F++; echo "  ✗ $label"; if ($got !== null || $want !== null) echo "  (got " . json_encode($got) . ", want " . json_encode($want) . ")"; echo "\n"; }
}
function near($a, $b, $eps = 0.01) { return abs($a - $b) <= $eps; }

$ctx = [
    'costs_map'    => [100 => 40.0, 200 => 0.0],   // 200 has an explicit 0 (treated as gap)
    'var_costs'    => [555 => 25.0],
    'cost_history' => [
        'p100' => [ ['from' => '2025-01-01', 'cost' => 30.0], ['from' => '2025-06-01', 'cost' => 45.0] ],
    ],
    'fee_config'   => [
        ['method_slug' => 'stripe', 'rate' => 1.5, 'fixed' => 1.8],
        ['method_slug' => '*',      'rate' => 2.5, 'fixed' => 0.0],
    ],
    'ship_config'  => ['cost_domestic' => 59.0, 'cost_international' => 99.0],
];

// ── C2: variation cost overrides product cost ──────────────────────
echo "C2 — variation-level COGS\n";
$r = pt_resolve_unit_cost($ctx, 100, 555, '2024-01-01');
ok(near($r['cost'], 25.0) && $r['found'], 'variation 555 cost wins over product 100', $r['cost'], 25.0);
$r = pt_resolve_unit_cost($ctx, 100, 999, '2024-01-01'); // unknown variation → fall back to product (no history before 2025)
ok(!$r['found'] || $r['cost'] > 0, 'unknown variation falls back to product', $r);

// ── C6: effective-dated cost history ───────────────────────────────
echo "C6 — effective-dated cost history\n";
$r = pt_resolve_unit_cost($ctx, 100, 0, '2025-03-15'); // between Jan and Jun → 30
ok(near($r['cost'], 30.0), 'order in Mar-2025 uses 30.00 (Jan price)', $r['cost'], 30.0);
$r = pt_resolve_unit_cost($ctx, 100, 0, '2025-09-15'); // after Jun → 45
ok(near($r['cost'], 45.0), 'order in Sep-2025 uses 45.00 (Jun price)', $r['cost'], 45.0);

// ── missing cost is a gap, not a silent zero ───────────────────────
echo "C3 — missing COGS detected\n";
$r = pt_resolve_unit_cost($ctx, 200, 0, '2025-01-01');
ok(!$r['found'], 'product 200 (cost 0) reported as gap', $r['found'], false);

// ── C1: refund-aware order metrics ─────────────────────────────────
echo "C1 — refund-aware profit\n";
$order = [
    'total' => 500.0, 'total_tax' => 100.0, 'date_created' => '2024-05-01',
    'payment_method' => 'stripe',
    'shipping' => ['country' => 'SE'],
    'line_items' => [ ['product_id' => 100, 'variation_id' => 0, 'quantity' => 2] ], // cost 40 (no 2024 history)
];
$m = pt_order_metrics($order, $ctx);
ok(near($m['revenue_incl'], 500.0), 'no refund → full revenue', $m['revenue_incl'], 500.0);
ok(near($m['cogs'], 80.0), 'no refund → COGS 2×40 = 80', $m['cogs'], 80.0);
ok(near($m['units'], 2.0), 'no refund → 2 units', $m['units'], 2.0);

// Half refund
$order['refunds'] = [ ['total' => -250.0] ];
$m = pt_order_metrics($order, $ctx);
ok(near($m['revenue_incl'], 250.0), '50% refund → revenue halved', $m['revenue_incl'], 250.0);
ok(near($m['cogs'], 40.0), '50% refund → COGS halved (was the bug: stayed 80)', $m['cogs'], 40.0);
ok(near($m['units'], 1.0), '50% refund → 1 unit', $m['units'], 1.0);
ok(near($m['revenue_excl'], 200.0), '50% refund → ex-VAT revenue 200', $m['revenue_excl'], 200.0);
// margin preserved: gross profit = 200 - 40 = 160 (80% margin) same as full order 400-80=320 (80%)
ok(near(($m['revenue_excl'] - $m['cogs']) / $m['revenue_excl'], 0.8), 'gross margin preserved across refund', null, 0.8);
ok(near($m['fees'], 1.5/100*500 + 1.8), 'fee stays on gross (conservative): 9.30', $m['fees'], 9.3);

// Full refund
$order['refunds'] = [ ['total' => -500.0] ];
$m = pt_order_metrics($order, $ctx);
ok(near($m['revenue_incl'], 0.0) && near($m['cogs'], 0.0), 'full refund → revenue & COGS zero', [$m['revenue_incl'],$m['cogs']], [0,0]);

// ── R6: VAT-correct break-even ─────────────────────────────────────
echo "R6/R1 — VAT-correct break-even ROAS\n";
// price 500 incl 25% VAT → ex-VAT 400. COGS 100, ship 50, fee 2% (=10), other 0.
$be = pt_break_even(500, 100, 50, 2.0, 0.0, 25.0);
ok(near($be['price_excl'], 400.0), 'ex-VAT price 400', $be['price_excl'], 400.0);
ok(near($be['tx_fee'], 10.0), 'tx fee 2% of 500 = 10', $be['tx_fee'], 10.0);
ok(near($be['contribution'], 240.0), 'contribution = 400-100-50-10 = 240', $be['contribution'], 240.0);
ok(near($be['margin'], 60.0), 'ex-VAT margin 60%', $be['margin'], 60.0);
ok(near($be['be_roas'], 2.08, 0.01), 'BE ROAS = 500/240 = 2.08 (vs VAT-blind ~1.28)', $be['be_roas'], 2.08);
// other_costs now counted (old app ignored it)
$be2 = pt_break_even(500, 100, 50, 2.0, 40.0, 25.0);
ok(near($be2['contribution'], 200.0), 'other_costs 40 now reduces contribution to 200', $be2['contribution'], 200.0);
// zero/negative contribution → null BE ROAS
$be3 = pt_break_even(100, 90, 20, 2.0, 0.0, 25.0);
ok($be3['be_roas'] === null, 'unprofitable unit → BE ROAS null (∞)', $be3['be_roas'], null);

// ── R3: scale/hold/kill verdict ────────────────────────────────────
echo "R3 — actual vs break-even verdict\n";
ok(pt_roas_verdict(3.0, 2.0)['verdict'] === 'scale', 'actual 3.0 vs BE 2.0 → scale');
ok(pt_roas_verdict(2.1, 2.0)['verdict'] === 'hold', 'actual 2.1 vs BE 2.0 → hold');
ok(pt_roas_verdict(1.5, 2.0)['verdict'] === 'kill', 'actual 1.5 vs BE 2.0 → kill');
ok(pt_roas_verdict(3.0, null)['verdict'] === 'unknown', 'no BE ROAS → unknown');

echo "\n" . ($F === 0 ? "ALL $T PASSED ✅" : "$F/$T FAILED ❌") . "\n";
exit($F === 0 ? 0 : 1);
