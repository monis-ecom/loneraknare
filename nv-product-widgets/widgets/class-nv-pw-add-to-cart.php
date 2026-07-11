<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Add_To_Cart extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-add-to-cart'; }
    public function get_title(): string { return 'NV: Add To Cart'; }
    public function get_icon(): string { return 'eicon-product-add-to-cart'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['product', 'cart', 'add', 'buy', 'woocommerce']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_atc', [
            'label' => 'Button Settings',
        ]);
        $this->add_control('button_bg_color', [
            'label' => 'Button Background',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#0f766e',
            'selectors' => ['{{WRAPPER}} .single_add_to_cart_button' => 'background-color: {{VALUE}} !important;'],
        ]);
        $this->add_control('button_text_color', [
            'label' => 'Button Text',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#FFFFFF',
            'selectors' => ['{{WRAPPER}} .single_add_to_cart_button' => 'color: {{VALUE}} !important;'],
        ]);
        $this->add_control('button_border_radius', [
            'label' => 'Border Radius',
            'type' => \Elementor\Controls_Manager::SLIDER,
            'default' => ['size' => 14, 'unit' => 'px'],
            'range' => ['px' => ['min' => 0, 'max' => 30]],
            'selectors' => ['{{WRAPPER}} .single_add_to_cart_button' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $product = NV_PW_Editor_Helper::get_preview_product();

        if (!$product instanceof WC_Product) {
            NV_PW_Editor_Helper::render_placeholder('NV: Add To Cart', '🛒');
            return;
        }

        // Set globals so WooCommerce template functions work
        $GLOBALS['product'] = $product;
        $GLOBALS['post'] = get_post($product->get_id());

        // In the Elementor editor iframe, render a styled static preview
        // instead of the full form (which can cause JS conflicts)
        if (NV_PW_Editor_Helper::is_editor() && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            echo '<div class="nv-pw-add-to-cart">';
            echo '<div style="padding:12px 0;">';
            if ($product->is_type('variable')) {
                echo '<p style="font-family:Manrope,sans-serif;font-size:14px;color:#5F6874;margin:0 0 12px;">Variant selector appears on the live page.</p>';
            }
            printf(
                '<button type="button" class="single_add_to_cart_button button alt" disabled style="opacity:0.8;cursor:default;">%s — %s</button>',
                esc_html__('Lägg i varukorg', 'nv-product-widgets'),
                esc_html($product->get_name())
            );
            echo '</div></div>';
            return;
        }

        echo '<div class="nv-pw-add-to-cart">';
        woocommerce_template_single_add_to_cart();
        echo '</div>';
    }
}
