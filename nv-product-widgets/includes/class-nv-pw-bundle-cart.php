<?php
if (!defined('ABSPATH')) exit;

/**
 * Server-side AJAX handler for the NV Bundle Builder.
 * Adds selected products to the WooCommerce cart and applies an optional tier coupon.
 */
final class NV_PW_Bundle_Cart {
    public static function init(): void {
        add_action('wp_ajax_nv_pw_add_bundle', [__CLASS__, 'handle']);
        add_action('wp_ajax_nopriv_nv_pw_add_bundle', [__CLASS__, 'handle']);
        add_action('wp_ajax_nv_pw_add_qty_breaks', [__CLASS__, 'handle_qty_breaks']);
        add_action('wp_ajax_nopriv_nv_pw_add_qty_breaks', [__CLASS__, 'handle_qty_breaks']);
        // Keep free-gift lines at zero on every cart/checkout recalculation.
        // High priority so we run AFTER other plugins (e.g. NV Commerce Core) that
        // may re-price the line on the same hook.
        add_action('woocommerce_before_calculate_totals', [__CLASS__, 'zero_gift_price'], 1000);
        // Apply coupon-free per-tier quantity-break discounts as a cart reduction.
        add_action('woocommerce_cart_calculate_fees', [__CLASS__, 'apply_qb_discount'], 20);
        // Safety net: whatever price another plugin ends up charging for a gift line,
        // subtract it back as a negative fee so the gift is always genuinely free.
        add_action('woocommerce_cart_calculate_fees', [__CLASS__, 'make_gift_free'], 30);
    }

    /**
     * Guarantee free gifts cost nothing even if another plugin overrides the price:
     * sum each gift line's currently-active price and refund it as a negative fee.
     * Self-correcting — if zero_gift_price already made the line 0, this adds nothing.
     */
    public static function make_gift_free($cart): void {
        if (is_admin() && !defined('DOING_AJAX')) return;
        if (!$cart || !is_object($cart) || !method_exists($cart, 'get_cart')) return;
        $refund = 0.0;
        foreach ($cart->get_cart() as $item) {
            if (empty($item['nv_pw_gift']) || empty($item['data']) || !is_object($item['data'])) continue;
            $refund += (float) $item['data']->get_price() * max(1, (int) $item['quantity']);
        }
        if ($refund > 0.009) {
            $cart->add_fee(__('Gratis gåva', 'nv-product-widgets'), -1 * round($refund, 2), false);
        }
    }

    /**
     * Coupon-free quantity-break discount: sums each flagged line's discount and
     * adds it back as a single negative "Mängdrabatt" fee. Runs on every recalc.
     */
    public static function apply_qb_discount($cart): void {
        if (is_admin() && !defined('DOING_AJAX')) return;
        if (!$cart || !is_object($cart) || !method_exists($cart, 'get_cart')) return;
        $discount = 0.0;
        foreach ($cart->get_cart() as $item) {
            if (empty($item['nv_pw_qb_discount']) || empty($item['data']) || !is_object($item['data'])) continue;
            $pct = max(0.0, min(90.0, (float) $item['nv_pw_qb_discount']));
            if ($pct <= 0) continue;
            $line = (float) $item['data']->get_price() * (int) $item['quantity'];
            $discount += $line * ($pct / 100);
        }
        if ($discount > 0.009) {
            $cart->add_fee(__('Mängdrabatt', 'nv-product-widgets'), -1 * round($discount, 2), false);
        }
    }

    /** Force any cart line flagged as a free gift to price 0. */
    public static function zero_gift_price($cart): void {
        if (is_admin() && !defined('DOING_AJAX')) return;
        if (!$cart || !is_object($cart) || !method_exists($cart, 'get_cart')) return;
        foreach ($cart->get_cart() as $item) {
            if (!empty($item['nv_pw_gift']) && isset($item['data']) && is_object($item['data'])) {
                $item['data']->set_price(0);
            }
        }
    }

    /**
     * Quantity-break add-to-cart: adds qty of the product (grouped per chosen
     * variation for variable products), an optional free gift, and an optional coupon.
     */
    public static function handle_qty_breaks(): void {
        if (!check_ajax_referer('nv_pw_bundle', 'nonce', false)) {
            wp_send_json_error(['message' => __('Säkerhetskontroll misslyckades. Ladda om sidan.', 'nv-product-widgets')], 400);
        }
        if (!function_exists('WC') || !WC()->cart) {
            wp_send_json_error(['message' => __('Varukorgen är inte tillgänglig.', 'nv-product-widgets')], 400);
        }

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $qty        = isset($_POST['qty']) ? max(1, absint($_POST['qty'])) : 1;
        $product    = $product_id ? wc_get_product($product_id) : null;
        if (!($product instanceof \WC_Product) || !$product->is_purchasable()) {
            wp_send_json_error(['message' => __('Produkten kunde inte läggas till.', 'nv-product-widgets')], 400);
        }

        $units_raw = isset($_POST['units']) ? wp_unslash($_POST['units']) : '';
        $units = json_decode((string) $units_raw, true);
        $units = is_array($units) ? $units : [];

        $discount_pct = isset($_POST['discount_pct']) ? max(0.0, min(90.0, (float) $_POST['discount_pct'])) : 0.0;
        $item_data = $discount_pct > 0 ? ['nv_pw_qb_discount' => $discount_pct] : [];

        $added = 0;
        if ($product->is_type('variable')) {
            if (count($units) < $qty) {
                wp_send_json_error(['message' => __('Välj alla varianter innan du fortsätter.', 'nv-product-widgets')], 400);
            }
            // Group identical variations so the cart shows one line per variation.
            $grouped = [];
            foreach ($units as $vid) {
                $vid = absint($vid);
                if ($vid <= 0) {
                    wp_send_json_error(['message' => __('Välj alla varianter innan du fortsätter.', 'nv-product-widgets')], 400);
                }
                $grouped[$vid] = ($grouped[$vid] ?? 0) + 1;
            }
            foreach ($grouped as $vid => $count) {
                $variation = wc_get_product($vid);
                if (!($variation instanceof \WC_Product_Variation) || $variation->get_parent_id() !== $product_id) continue;
                if (!$variation->is_purchasable() || !$variation->is_in_stock()) continue;
                $attrs = $variation->get_variation_attributes();
                if (WC()->cart->add_to_cart($product_id, $count, $vid, $attrs, $item_data)) $added += $count;
            }
        } else {
            if (!$product->is_in_stock()) {
                wp_send_json_error(['message' => __('Produkten är slut i lager.', 'nv-product-widgets')], 400);
            }
            if (WC()->cart->add_to_cart($product_id, $qty, 0, [], $item_data)) $added += $qty;
        }

        if ($added === 0) {
            wp_send_json_error(['message' => __('Det gick inte att lägga produkten i varukorgen.', 'nv-product-widgets')], 400);
        }

        // Free gift (added as a zero-priced line via the flag above).
        $gift_id = isset($_POST['gift_id']) ? absint($_POST['gift_id']) : 0;
        if ($gift_id > 0) {
            $gift = wc_get_product($gift_id);
            if ($gift instanceof \WC_Product && $gift->exists()) {
                WC()->cart->add_to_cart($gift_id, 1, 0, [], ['nv_pw_gift' => 1]);
            }
        }

        $coupon = isset($_POST['coupon']) ? sanitize_text_field(wp_unslash($_POST['coupon'])) : '';
        if ($coupon !== '' && function_exists('wc_get_coupon_id_by_code')) {
            $coupon_lc = strtolower($coupon);
            if (wc_get_coupon_id_by_code($coupon_lc) && !WC()->cart->has_discount($coupon_lc)) {
                WC()->cart->apply_coupon($coupon_lc);
            }
        }

        WC()->cart->calculate_totals();

        // Return mini-cart fragments + hash so the widget can refresh the header
        // cart and slide open the theme's side cart instead of redirecting.
        WC()->cart->maybe_set_cart_cookies();
        $data = [
            'added'      => $added,
            'redirect'   => wc_get_cart_url(),
            'fragments'  => apply_filters('woocommerce_add_to_cart_fragments', []),
            'cart_hash'  => WC()->cart->get_cart_hash(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
        ];
        wp_send_json_success($data);
    }

    public static function handle(): void {
        if (!check_ajax_referer('nv_pw_bundle', 'nonce', false)) {
            wp_send_json_error(['message' => __('Säkerhetskontroll misslyckades. Ladda om sidan.', 'nv-product-widgets')], 400);
        }
        if (!function_exists('WC') || !WC()->cart) {
            wp_send_json_error(['message' => __('Varukorgen är inte tillgänglig.', 'nv-product-widgets')], 400);
        }

        $raw = isset($_POST['items']) ? wp_unslash($_POST['items']) : '';
        $items = json_decode((string) $raw, true);
        if (!is_array($items) || empty($items)) {
            wp_send_json_error(['message' => __('Inga produkter valda.', 'nv-product-widgets')], 400);
        }

        $added = 0;
        foreach ($items as $row) {
            $pid = isset($row['id']) ? absint($row['id']) : 0;
            $qty = isset($row['qty']) ? max(1, absint($row['qty'])) : 1;
            if ($pid <= 0) continue;
            $product = wc_get_product($pid);
            if (!($product instanceof \WC_Product) || !$product->is_purchasable() || !$product->is_in_stock()) continue;
            if (WC()->cart->add_to_cart($pid, $qty)) {
                $added++;
            }
        }

        if ($added === 0) {
            wp_send_json_error(['message' => __('Det gick inte att lägga produkterna i varukorgen.', 'nv-product-widgets')], 400);
        }

        $coupon = isset($_POST['coupon']) ? sanitize_text_field(wp_unslash($_POST['coupon'])) : '';
        if ($coupon !== '' && function_exists('wc_get_coupon_id_by_code')) {
            $coupon_lc = strtolower($coupon);
            if (wc_get_coupon_id_by_code($coupon_lc) && !WC()->cart->has_discount($coupon_lc)) {
                WC()->cart->apply_coupon($coupon_lc);
            }
        }

        WC()->cart->calculate_totals();
        wp_send_json_success(['redirect' => wc_get_cart_url(), 'added' => $added]);
    }
}
