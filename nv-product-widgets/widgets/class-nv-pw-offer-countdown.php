<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Offer_Countdown extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-offer-countdown'; }
    public function get_title(): string { return 'NV: Offer Countdown'; }
    public function get_icon(): string { return 'eicon-countdown'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['countdown', 'timer', 'offer', 'nvcb']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('Countdown Settings', 'nv-product-widgets'),
        ]);

        $this->add_control('label_text', [
            'label' => __('Label text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Erbjudandet gäller:', 'nv-product-widgets'),
        ]);

        $this->add_control('countdown_minutes', [
            'label' => __('Duration (minutes)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 20,
            'min' => 1,
            'max' => 360,
        ]);

        $this->add_control('countdown_key', [
            'label' => __('Session key (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'placeholder' => __('example: buybox', 'nv-product-widgets'),
            'description' => __('Use unique keys if you want different timers on the same page.', 'nv-product-widgets'),
        ]);

        $this->add_control('helper_note', [
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw' => __('This widget is powered by NV Conversion Booster script. Keep that plugin active.', 'nv-product-widgets'),
            'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
        ]);

        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();

        $label = trim((string) ($settings['label_text'] ?? ''));
        if ($label === '') {
            $label = 'Erbjudandet gäller:';
        }

        $minutes = max(1, (int) ($settings['countdown_minutes'] ?? 20));
        $raw_key = sanitize_key((string) ($settings['countdown_key'] ?? ''));
        ?>
        <div class="nvcb-countdown-bar"
             role="timer"
             aria-live="polite"
             data-nvcb-countdown-minutes="<?php echo esc_attr((string) $minutes); ?>"
             data-nvcb-countdown-label="<?php echo esc_attr($label); ?>"
             <?php if ($raw_key !== '') : ?>data-nvcb-countdown-key="<?php echo esc_attr($raw_key); ?>"<?php endif; ?>>
            <span class="nvcb-countdown-bar__label"><?php echo esc_html($label); ?></span>
            <span class="nvcb-countdown-timer" aria-label="Nedräkningstimer">00:00</span>
        </div>
        <?php
    }
}
