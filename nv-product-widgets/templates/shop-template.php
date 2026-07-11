<?php
/**
 * NV Product Widgets — Custom Shop Template Wrapper
 *
 * Loaded for the main WooCommerce shop archive when an NV Shop template is
 * selected as the default in the Product Templates dashboard.
 */
if (!defined('ABSPATH')) exit;

if (!function_exists('nv_pw_render_default_shop_loop')) {
    function nv_pw_render_default_shop_loop(): void {
        if (!function_exists('woocommerce_product_loop')) {
            return;
        }

        do_action('woocommerce_before_main_content');

        if (woocommerce_product_loop()) {
            do_action('woocommerce_before_shop_loop');

            woocommerce_product_loop_start();

            if (function_exists('wc_get_loop_prop') && wc_get_loop_prop('total')) {
                while (have_posts()) {
                    the_post();
                    do_action('woocommerce_shop_loop');
                    wc_get_template_part('content', 'product');
                }
            }

            woocommerce_product_loop_end();
            do_action('woocommerce_after_shop_loop');
        } else {
            do_action('woocommerce_no_products_found');
        }

        do_action('woocommerce_after_main_content');
    }
}

$nv_template_id = (int) get_query_var('nv_pw_shop_template_id', 0);

get_header('shop');

if ($nv_template_id > 0 && class_exists('\Elementor\Plugin')) {
    $builder_content = (string) \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($nv_template_id, true);

    if ($builder_content !== '') {
        echo '<div class="nv-pw-shop-template-wrap">';
        echo $builder_content;
        echo '</div>';
    } else {
        nv_pw_render_default_shop_loop();
    }
} else {
    nv_pw_render_default_shop_loop();
}

get_footer('shop');
