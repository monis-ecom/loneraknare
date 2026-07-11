<?php
if (!defined('ABSPATH')) exit;

/**
 * NV PW: Product Description (Full / Tabs)
 *
 * Replaces WL: Product Description from ShopLentor.
 * Two display modes:
 *   - "content"  → shows just the full description (no WC tabs chrome)
 *   - "tabs"     → shows WooCommerce native product tabs (Description, Reviews, etc.)
 */
class NV_PW_Product_Description extends \Elementor\Widget_Base {
    public function get_name(): string    { return 'nv-product-description'; }
    public function get_title(): string   { return 'NV: Product Description'; }
    public function get_icon(): string    { return 'eicon-product-tabs'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array   { return ['product', 'description', 'tabs', 'content', 'woocommerce']; }

    protected function register_controls(): void {

        /* ── Content ── */
        $this->start_controls_section('section_content', [
            'label' => 'Description Settings',
        ]);
        $this->add_control('display_mode', [
            'label'   => 'Display Mode',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'content',
            'options' => [
                'content' => 'Description Only',
                'tabs'    => 'WooCommerce Tabs (Description + Reviews)',
            ],
        ]);
        $this->add_control('description_title', [
            'label'     => 'Section Title',
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'Produktbeskrivning',
            'condition' => ['display_mode' => 'content'],
        ]);
        $this->add_control('hide_title', [
            'label'     => 'Hide Title',
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => '',
            'condition' => ['display_mode' => 'content'],
        ]);
        $this->end_controls_section();

        /* ── Style ── */
        $this->start_controls_section('section_style', [
            'label' => 'Typography',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control('title_color', [
            'label'     => 'Title Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#111318',
            'selectors' => ['{{WRAPPER}} .nv-pw-desc__title' => 'color: {{VALUE}};'],
            'condition' => ['display_mode' => 'content'],
        ]);
        $this->add_control('text_color', [
            'label'     => 'Body Text Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#5F6874',
            'selectors' => ['{{WRAPPER}} .nv-pw-desc__content' => 'color: {{VALUE}};'],
            'condition' => ['display_mode' => 'content'],
        ]);
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'      => 'body_typography',
                'label'     => 'Body Typography',
                'selector'  => '{{WRAPPER}} .nv-pw-desc__content',
                'condition' => ['display_mode' => 'content'],
            ]
        );
        $this->end_controls_section();
    }

    protected function render(): void {
        $product = NV_PW_Editor_Helper::get_preview_product();

        if (!$product instanceof WC_Product) {
            NV_PW_Editor_Helper::render_placeholder('NV: Product Description', '📄');
            return;
        }

        $GLOBALS['product'] = $product;
        $GLOBALS['post']    = get_post($product->get_id());

        $settings = $this->get_settings_for_display();
        $mode     = $settings['display_mode'] ?? 'content';

        if ($mode === 'tabs') {
            echo '<div class="nv-pw-desc nv-pw-desc--tabs woocommerce">';
            // WooCommerce outputs its tabs via this template
            woocommerce_output_product_data_tabs();
            echo '</div>';
            return;
        }

        /* ── Description-only mode ── */
        $desc = $product->get_description();

        if (empty(trim(wp_strip_all_tags($desc)))) {
            if (NV_PW_Editor_Helper::is_editor()) {
                echo '<p style="color:#999;font-family:Manrope,sans-serif;font-size:13px;">No product description set.</p>';
            }
            return;
        }

        echo '<div class="nv-pw-desc">';

        $title    = $settings['description_title'] ?? '';
        $show_ttl = empty($settings['hide_title']);
        if ($title && $show_ttl) {
            echo '<h3 class="nv-pw-desc__title">' . esc_html($title) . '</h3>';
        }

        echo '<div class="nv-pw-desc__content">';
        echo wp_kses_post(wpautop($desc));
        echo '</div></div>';
    }
}
