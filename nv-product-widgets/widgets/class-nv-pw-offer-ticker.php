<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Offer_Ticker extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-offer-ticker'; }
    public function get_title(): string { return 'NV: Offer Ticker'; }
    public function get_icon(): string { return 'eicon-menu-bar'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['ticker', 'offer', 'marquee', 'campaign', 'announcement']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('Ticker Content', 'nv-product-widgets'),
        ]);

        $this->add_control('title', [
            'label' => __('Prefix label', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Just nu', 'nv-product-widgets'),
        ]);

        $this->add_control('items', [
            'label' => __('Ticker items', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => "Fri frakt över 699 kr\n30 dagars öppet köp\nTrygg betalning med Klarna",
            'description' => __('One item per line.', 'nv-product-widgets'),
        ]);

        $this->add_control('speed_seconds', [
            'label' => __('Loop duration (seconds)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 24,
            'min' => 8,
            'max' => 120,
        ]);

        $this->add_control('pause_on_hover', [
            'label' => __('Pause on hover', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => __('Yes', 'nv-product-widgets'),
            'label_off' => __('No', 'nv-product-widgets'),
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_style', [
            'label' => __('Style', 'nv-product-widgets'),
            'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('background_color', [
            'label' => __('Background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#F8F7F3',
            'selectors' => ['{{WRAPPER}} .nv-pw-offer-ticker' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('text_color', [
            'label' => __('Text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#111318',
            'selectors' => ['{{WRAPPER}} .nv-pw-offer-ticker' => 'color: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $title = trim((string) ($settings['title'] ?? ''));
        $speed = max(8, (int) ($settings['speed_seconds'] ?? 24));
        $pause_class = ($settings['pause_on_hover'] ?? 'yes') === 'yes' ? ' nv-pw-offer-ticker--pause-hover' : '';

        $items_raw = (string) ($settings['items'] ?? '');
        $items = [];
        foreach (preg_split('/\r\n|\r|\n/', $items_raw) as $line) {
            $line = trim((string) $line);
            if ($line !== '') {
                $items[] = $line;
            }
        }

        if (empty($items)) {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Offer Ticker', '📣');
            }
            return;
        }

        $track_items = array_merge($items, $items);
        ?>
        <div class="nv-pw-offer-ticker<?php echo esc_attr($pause_class); ?>" style="--nv-pw-ticker-duration: <?php echo esc_attr((string) $speed); ?>s;">
            <?php if ($title !== '') : ?>
                <span class="nv-pw-offer-ticker__title"><?php echo esc_html($title); ?></span>
            <?php endif; ?>
            <div class="nv-pw-offer-ticker__viewport" aria-label="<?php esc_attr_e('Campaign ticker', 'nv-product-widgets'); ?>">
                <div class="nv-pw-offer-ticker__track">
                    <?php foreach ($track_items as $index => $item) : ?>
                        <span class="nv-pw-offer-ticker__item"><?php echo esc_html($item); ?></span>
                        <?php if ($index < count($track_items) - 1) : ?>
                            <span class="nv-pw-offer-ticker__dot" aria-hidden="true">•</span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
