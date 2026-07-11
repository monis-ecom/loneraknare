<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Product_Price extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-product-price'; }
    public function get_title(): string { return 'NV: Product Price'; }
    public function get_icon(): string { return 'eicon-product-price'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['product', 'price', 'woocommerce']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_price', [
            'label' => 'Price Settings',
        ]);
        $this->add_control('sale_price_color', [
            'label' => 'Sale Price Color',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#111318',
            'selectors' => ['{{WRAPPER}} .nv-pw-price ins, {{WRAPPER}} .nv-pw-price > .amount, {{WRAPPER}} .nv-pw-price > span > .amount' => 'color: {{VALUE}} !important;'],
        ]);
        $this->add_control('regular_price_color', [
            'label' => 'Regular/Strikethrough Color',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#6a747f',
            'selectors' => ['{{WRAPPER}} .nv-pw-price del, {{WRAPPER}} .nv-pw-price del .amount' => 'color: {{VALUE}} !important;'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'price_typography',
            'selector' => '{{WRAPPER}} .nv-pw-price, {{WRAPPER}} .nv-pw-price .amount, {{WRAPPER}} .nv-pw-price ins, {{WRAPPER}} .nv-pw-price del',
        ]);
        $this->add_responsive_control('price_align', [
            'label' => 'Alignment',
            'type' => \Elementor\Controls_Manager::CHOOSE,
            'options' => [
                'left'   => ['title' => 'Left', 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => 'Center', 'icon' => 'eicon-text-align-center'],
                'right'  => ['title' => 'Right', 'icon' => 'eicon-text-align-right'],
            ],
            'selectors' => ['{{WRAPPER}} .nv-pw-price' => 'text-align: {{VALUE}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $product = NV_PW_Editor_Helper::get_preview_product();

        if (!$product instanceof WC_Product) {
            NV_PW_Editor_Helper::render_placeholder('NV: Product Price', '💰');
            return;
        }

        // Set global so WC price functions work correctly
        $GLOBALS['product'] = $product;

        echo '<div class="nv-pw-price">' . $product->get_price_html() . '</div>';
    }
}
