<?php
/**
 * NV Product Widgets — Elementor Editor Helper
 *
 * Provides a preview product for widgets when editing in the Elementor editor.
 * When editing a Single Product template, get_the_ID() returns the template post ID,
 * not a real product. This helper fetches a real WC_Product for preview purposes.
 */
if (!defined('ABSPATH')) exit;

final class NV_PW_Editor_Helper {

    private static $preview_product = null;
    private static $resolved = false;

    /**
     * Get a WC_Product for preview in the Elementor editor.
     *
     * First checks if the global $product is already set (frontend / preview mode).
     * If not, tries to get a product from:
     *   1. The Elementor document settings (preview post ID)
     *   2. The most recent published WooCommerce product
     *
     * @return WC_Product|null
     */
    public static function get_preview_product() {
        global $product;

        // If we already have a valid product, return it
        if ($product instanceof WC_Product) {
            return $product;
        }

        // Try get_the_ID() first — works on frontend
        $post_id = get_the_ID();
        if ($post_id) {
            $maybe = wc_get_product($post_id);
            if ($maybe instanceof WC_Product) {
                return $maybe;
            }
        }

        // Already resolved via fallback? Return cached result
        if (self::$resolved) {
            return self::$preview_product;
        }

        self::$resolved = true;

        // In Elementor editor — resolve which product to preview.
        if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $document = \Elementor\Plugin::$instance->documents->get_current();

            // 1) The product this template is actually assigned to (source of truth
            //    in this system — a template is built for its assigned product).
            $template_id = ($document && method_exists($document, 'get_main_id')) ? (int) $document->get_main_id() : 0;
            if ($template_id > 0) {
                $assigned = get_posts([
                    'post_type'      => 'product',
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'post_status'    => 'publish',
                    'meta_key'       => '_nv_product_template',
                    'meta_value'     => (string) $template_id,
                    'no_found_rows'  => true,
                ]);
                if (!empty($assigned)) {
                    $maybe = wc_get_product((int) $assigned[0]);
                    if ($maybe instanceof WC_Product) {
                        self::$preview_product = $maybe;
                        return self::$preview_product;
                    }
                }
            }

            // 2) Elementor's explicit preview product (set via Preview Settings).
            if ($document) {
                $preview_id = $document->get_settings('preview_id');
                if ($preview_id) {
                    $maybe = wc_get_product($preview_id);
                    if ($maybe instanceof WC_Product) {
                        self::$preview_product = $maybe;
                        return self::$preview_product;
                    }
                }
            }

            // 3) Fallback: grab the most recent published product
            $recent = wc_get_products([
                'status' => 'publish',
                'limit'  => 1,
                'orderby' => 'date',
                'order'   => 'DESC',
                'type'    => ['simple', 'variable'],
            ]);

            if (!empty($recent) && $recent[0] instanceof WC_Product) {
                self::$preview_product = $recent[0];
                return self::$preview_product;
            }
        }

        return null;
    }

    /**
     * Check if we're currently in the Elementor editor (edit mode or preview mode).
     *
     * @return bool
     */
    public static function is_editor() {
        if (!class_exists('\Elementor\Plugin')) {
            return false;
        }
        $elementor = \Elementor\Plugin::$instance;
        return $elementor->editor->is_edit_mode() || $elementor->preview->is_preview_mode();
    }

    /**
     * Render a styled placeholder for the Elementor editor when no product can be found.
     *
     * @param string $widget_name Display name of the widget
     * @param string $icon        Optional dashicon or emoji
     */
    public static function render_placeholder(string $widget_name, string $icon = '📦'): void {
        printf(
            '<div class="nv-pw-editor-placeholder">
                <div class="nv-pw-editor-placeholder__icon">%s</div>
                <p class="nv-pw-editor-placeholder__title">%s</p>
                <p class="nv-pw-editor-placeholder__desc">%s</p>
            </div>',
            esc_html($icon),
            esc_html($widget_name),
            esc_html__('Product data will appear on the live page. To preview in the editor, set a Preview Product in Elementor → Template Settings.', 'nv-product-widgets')
        );
    }
}
