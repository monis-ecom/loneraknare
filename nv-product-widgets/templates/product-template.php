<?php
/**
 * NV Product Widgets — Custom Product Template Wrapper
 *
 * This file is loaded instead of the standard theme template when a product
 * has an NV Custom Template assigned. It uses Elementor's frontend renderer
 * to output the template content.
 *
 * The template ID is passed via: get_query_var('nv_pw_template_id')
 */
if (!defined('ABSPATH')) exit;

if (!function_exists('nv_pw_builder_has_purchase_path')) {
    /**
     * Detect if rendered builder content already exposes a purchase path.
     */
    function nv_pw_builder_has_purchase_path(string $builder_content): bool {
        if ($builder_content === '') {
            return false;
        }

        $markers = [
            'single_add_to_cart_button',
            'variations_form cart',
            'nvcc-bundle__submit',
            'nvcc-bundle',
            'nv-pw-bundle__btn',
            'nv-pw-bundle',
            'elementor-widget-nv-add-to-cart',
            'elementor-widget-nv-bundle-selector',
            'elementor-widget-nv-quantity-breaks',
            'data-nv-qb-add',
            'nv-pw-qb__cta',
        ];

        foreach ($markers as $marker) {
            if (strpos($builder_content, $marker) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('nv_pw_render_fallback_bundle_markup')) {
    /**
     * Build fallback bundle markup by rendering NV Commerce Core shortcode directly.
     */
    function nv_pw_render_fallback_bundle_markup($product): string {
        if (
            !$product instanceof WC_Product ||
            !$product->is_type('variable') ||
            !function_exists('do_shortcode')
        ) {
            return '';
        }

        $product_id = (int) $product->get_id();
        if ($product_id <= 0) {
            return '';
        }

        $bundle_markup = (string) do_shortcode('[nvcc_variation_bundle product_id="' . $product_id . '"]');
        if ($bundle_markup === '') {
            return '';
        }

        if (
            strpos($bundle_markup, 'nvcc-bundle') === false &&
            strpos($bundle_markup, 'nvcc-bundle__submit') === false
        ) {
            return '';
        }

        return '<div class="elementor-element elementor-widget elementor-widget-nv-bundle-selector nv-pw-auto-fallback-bundle" data-nv-pw-fallback-bundle="1"><div class="elementor-widget-container">' . $bundle_markup . '</div></div>';
    }
}

if (!function_exists('nv_pw_render_fallback_add_to_cart_markup')) {
    /**
     * Build fallback add-to-cart markup using the native WooCommerce template.
     */
    function nv_pw_render_fallback_add_to_cart_markup($product): string {
        if (
            !$product instanceof WC_Product ||
            !$product->is_purchasable() ||
            !$product->is_in_stock() ||
            !function_exists('woocommerce_template_single_add_to_cart')
        ) {
            return '';
        }

        $GLOBALS['product'] = $product;
        $GLOBALS['post'] = get_post($product->get_id());

        ob_start();
        ?>
        <div class="elementor-element elementor-widget elementor-widget-nv-add-to-cart nv-pw-auto-fallback-atc" data-nv-pw-fallback-atc="1">
            <div class="elementor-widget-container">
                <div class="nv-pw-add-to-cart">
                    <?php woocommerce_template_single_add_to_cart(); ?>
                </div>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}

// Set up the product global so NV widgets can find it
global $product;
if (!$product instanceof WC_Product) {
    $product = wc_get_product(get_the_ID());
}

// Get the Elementor template ID
$nv_template_id = (int) get_query_var('nv_pw_template_id', 0);

// Build HTML
get_header();

if ($nv_template_id && class_exists('\Elementor\Plugin')) {
    $elementor_frontend = \Elementor\Plugin::$instance->frontend;

    // Tell Elementor we're rendering a specific document ID
    echo '<div class="nv-pw-product-template-wrap">';

    // Wrap in WooCommerce product context
    while (have_posts()) :
        the_post();
        // WooCommerce requires $post and $product globals to be set
        global $post;
        $product = wc_get_product(get_the_ID());

        // Output the Elementor template content.
        // Some Elementor blocks (e.g. heading/text widgets) do not execute shortcodes.
        // Resolve NV Commerce bundle shortcodes here to prevent raw token leakage.
        $builder_content = (string) $elementor_frontend->get_builder_content_for_display($nv_template_id, true);
        if (
            $builder_content !== '' &&
            function_exists('do_shortcode') &&
            (
                strpos($builder_content, '[nvcc_variation_bundle') !== false ||
                strpos($builder_content, '[nvcc_bundle_widget') !== false
            )
        ) {
            $resolved_content = preg_replace_callback(
                '/\[(nvcc_variation_bundle|nvcc_bundle_widget)(?:\s[^\]]*)?\]/i',
                static function (array $matches): string {
                    return do_shortcode($matches[0]);
                },
                $builder_content
            );

            if (is_string($resolved_content)) {
                $builder_content = $resolved_content;
            }
        }

        $fallback_bundle_markup = '';
        $fallback_atc_markup = '';
        if (!nv_pw_builder_has_purchase_path($builder_content)) {
            $fallback_bundle_markup = nv_pw_render_fallback_bundle_markup($product);
            $fallback_atc_markup = nv_pw_render_fallback_add_to_cart_markup($product);
        }

        $fallback_markup = $fallback_bundle_markup . $fallback_atc_markup;
        if ($fallback_markup !== '') {
            if (strpos($builder_content, 'id="buy-box"') !== false || strpos($builder_content, "id='buy-box'") !== false) {
                $with_injected_fallback = preg_replace_callback(
                    '/<[^>]*\bid=(["\'])buy-box\1[^>]*>/i',
                    static function (array $matches) use ($fallback_markup): string {
                        return $matches[0] . $fallback_markup;
                    },
                    $builder_content,
                    1
                );

                if (is_string($with_injected_fallback)) {
                    $builder_content = $with_injected_fallback;
                } else {
                    $builder_content .= $fallback_markup;
                }
            } else {
                $builder_content .= $fallback_markup;
            }
        }

        echo $builder_content;
    endwhile;

    echo '</div>';
} else {
    // Fallback: render default WooCommerce product template
    while (have_posts()) :
        the_post();
        wc_get_template_part('content', 'single-product');
    endwhile;
}

get_footer();
