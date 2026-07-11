<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Product_Title extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-product-title'; }
    public function get_title(): string { return 'NV: Product Title'; }
    public function get_icon(): string { return 'eicon-product-title'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['product', 'title', 'heading', 'woocommerce']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_title', [
            'label' => 'Title Settings',
        ]);
        $this->add_control('html_tag', [
            'label' => 'HTML Tag',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'h1',
            'options' => ['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'div' => 'div'],
        ]);
        $this->add_control('title_color', [
            'label' => 'Color',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#111318',
            'selectors' => ['{{WRAPPER}} .nv-pw-product-title' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'title_typography',
            'selector' => '{{WRAPPER}} .nv-pw-product-title',
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $product = NV_PW_Editor_Helper::get_preview_product();

        if (!$product instanceof WC_Product) {
            NV_PW_Editor_Helper::render_placeholder('NV: Product Title', '📝');
            return;
        }

        $tag = $this->get_settings_for_display('html_tag') ?: 'h1';
        $allowed = ['h1','h2','h3','h4','h5','h6','div','span','p'];
        if (!in_array($tag, $allowed, true)) $tag = 'h1';

        printf('<%1$s class="nv-pw-product-title">%2$s</%1$s>', $tag, esc_html($product->get_name()));
    }
}
