<?php
if (!defined('ABSPATH')) exit;

/**
 * NV: Free Shipping Bar — "You're X away from free shipping" with a live
 * progress meter that reads the real cart subtotal and updates on cart changes.
 */
class NV_PW_Free_Shipping_Bar extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-free-shipping-bar'; }
    public function get_title(): string { return 'NV: Free Shipping Bar'; }
    public function get_icon(): string { return 'eicon-product-meta'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['free shipping', 'fri frakt', 'progress', 'cart', 'aov', 'threshold', 'spend more']; }
    public function get_script_depends(): array { return ['nv-free-shipping-bar']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('threshold', [
            'label' => __('Free shipping threshold', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 499, 'min' => 1,
            'description' => __('Cart subtotal needed to unlock free shipping.', 'nv-product-widgets'),
        ]);
        $this->add_control('remaining_text', [
            'label' => __('Remaining text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Du är {remaining} från fri frakt!', 'nv-product-widgets'),
            'description' => __('Use {remaining} for the amount left.', 'nv-product-widgets'),
        ]);
        $this->add_control('unlocked_text', [
            'label' => __('Unlocked text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('🎉 Grattis! Du har fri frakt.', 'nv-product-widgets'),
        ]);
        $this->add_control('show_bar', [
            'label' => __('Show progress bar', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('track_color', [
            'label' => __('Bar track', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#E9EBF0',
            'selectors' => ['{{WRAPPER}} .nv-pw-fsb' => '--nv-fsb-track: {{VALUE}};'],
        ]);
        $this->add_control('fill_color', [
            'label' => __('Bar fill', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#1D9E75',
            'selectors' => ['{{WRAPPER}} .nv-pw-fsb' => '--nv-fsb-fill: {{VALUE}};'],
        ]);
        $this->add_control('text_color', [
            'label' => __('Text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#14161D',
            'selectors' => ['{{WRAPPER}} .nv-pw-fsb' => '--nv-fsb-text: {{VALUE}};'],
        ]);
        $this->add_control('bg', [
            'label' => __('Background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#F6F7FB',
            'selectors' => ['{{WRAPPER}} .nv-pw-fsb' => '--nv-fsb-bg: {{VALUE}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        if (!function_exists('WC')) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Free Shipping Bar — WooCommerce required', '🚚');
            return;
        }
        $s = $this->get_settings_for_display();
        $threshold = max(1, (float) ($s['threshold'] ?? 499));
        $remaining_tpl = (string) ($s['remaining_text'] ?? '');
        $unlocked_text = (string) ($s['unlocked_text'] ?? '');
        $show_bar = (($s['show_bar'] ?? 'yes') === 'yes');

        $current = 0.0;
        if (WC()->cart) {
            $current = (float) WC()->cart->get_subtotal();
        }
        $remaining = max(0, $threshold - $current);
        $unlocked = $current >= $threshold;
        $pct = $threshold > 0 ? min(100, ($current / $threshold) * 100) : 0;

        $symbol = get_woocommerce_currency_symbol();
        $decimals = wc_get_price_decimals();
        $remaining_display = html_entity_decode(wp_strip_all_tags(wc_price($remaining)));
        $msg = $unlocked ? $unlocked_text : str_replace('{remaining}', $remaining_display, $remaining_tpl);
        ?>
        <div class="nv-pw-fsb<?php echo $unlocked ? ' is-unlocked' : ''; ?>"
             data-nv-fsb
             data-threshold="<?php echo esc_attr((string) $threshold); ?>"
             data-current="<?php echo esc_attr((string) $current); ?>"
             data-symbol="<?php echo esc_attr($symbol); ?>"
             data-decimals="<?php echo esc_attr((string) $decimals); ?>"
             data-remaining-tpl="<?php echo esc_attr($remaining_tpl); ?>"
             data-unlocked="<?php echo esc_attr($unlocked_text); ?>">
            <p class="nv-pw-fsb__msg" data-nv-fsb-msg><?php echo esc_html($msg); ?></p>
            <?php if ($show_bar) : ?>
                <span class="nv-pw-fsb__track">
                    <span class="nv-pw-fsb__fill" data-nv-fsb-fill style="width: <?php echo esc_attr((string) $pct); ?>%;"></span>
                </span>
            <?php endif; ?>
        </div>
        <?php
    }
}
