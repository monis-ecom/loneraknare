<?php
if (!defined('ABSPATH')) exit;

/**
 * NV PW: Product Short Description
 *
 * Replaces WL: Product Short Description from ShopLentor.
 * Outputs the WooCommerce product excerpt / short description.
 */
class NV_PW_Short_Description extends \Elementor\Widget_Base {
    public function get_name(): string    { return 'nv-short-description'; }
    public function get_title(): string   { return 'NV: Short Description'; }
    public function get_icon(): string    { return 'eicon-product-description'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array   { return ['product', 'description', 'excerpt', 'short', 'woocommerce']; }

    protected function register_controls(): void {

        /* ── Content tab ── */
        $this->start_controls_section('section_content', [
            'label' => 'Short Description',
        ]);
        $this->add_control('show_divider_above', [
            'label'   => 'Divider Above',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => '',
        ]);
        $this->add_control('show_divider_below', [
            'label'   => 'Divider Below',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => '',
        ]);
        $this->end_controls_section();

        /* ── Style tab ── */
        $this->start_controls_section('section_style', [
            'label' => 'Typography',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control('text_color', [
            'label'     => 'Text Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#5F6874',
            'selectors' => ['{{WRAPPER}} .nv-pw-short-desc' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'typography',
                'selector' => '{{WRAPPER}} .nv-pw-short-desc',
            ]
        );
        $this->add_responsive_control('text_align', [
            'label'     => 'Text Alignment',
            'type'      => \Elementor\Controls_Manager::CHOOSE,
            'options'   => [
                'left'   => ['title' => 'Left',   'icon' => 'eicon-text-align-left'],
                'center' => ['title' => 'Center', 'icon' => 'eicon-text-align-center'],
                'right'  => ['title' => 'Right',  'icon' => 'eicon-text-align-right'],
            ],
            'selectors' => ['{{WRAPPER}} .nv-pw-short-desc' => 'text-align: {{VALUE}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $product = NV_PW_Editor_Helper::get_preview_product();

        if (!$product instanceof WC_Product) {
            NV_PW_Editor_Helper::render_placeholder('NV: Short Description', '📝');
            return;
        }

        $GLOBALS['product'] = $product;
        $GLOBALS['post']    = get_post($product->get_id());

        $settings = $this->get_settings_for_display();
        $desc     = $product->get_short_description();

        if (empty(trim(wp_strip_all_tags($desc)))) {
            if (NV_PW_Editor_Helper::is_editor()) {
                echo '<p class="nv-pw-short-desc" style="color:#999;font-style:italic;">No short description set for this product.</p>';
            }
            return;
        }

        if (!empty($settings['show_divider_above'])) {
            echo '<hr class="nv-pw-divider">';
        }
        echo '<div class="nv-pw-short-desc woocommerce-product-details__short-description">';
        echo wp_kses_post(wpautop($desc));
        echo '</div>';
        if (!empty($settings['show_divider_below'])) {
            echo '<hr class="nv-pw-divider">';
        }
    }
}
