<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Rating_Summary extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-rating-summary'; }
    public function get_title(): string { return 'NV: Rating Summary'; }
    public function get_icon(): string { return 'eicon-rating'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['rating', 'stars', 'reviews', 'betyg', 'stjärnor', 'social proof']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('source', [
            'label' => __('Rating source', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'manual',
            'options' => [
                'manual' => __('Manual', 'nv-product-widgets'),
                'woocommerce' => __('WooCommerce product rating', 'nv-product-widgets'),
            ],
        ]);
        $this->add_control('rating', [
            'label' => __('Rating (0–5)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'min' => 0, 'max' => 5, 'step' => 0.1,
            'default' => 4.9,
            'condition' => ['source' => 'manual'],
        ]);
        $this->add_control('label', [
            'label' => __('Label text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('4,9/5 · baserat på 9 000+ spelare', 'nv-product-widgets'),
        ]);
        $this->add_control('show_label', [
            'label' => __('Show label', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('star_color', [
            'label' => __('Star color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#F5A623',
            'selectors' => ['{{WRAPPER}} .nv-pw-rs' => '--nv-rs-star: {{VALUE}};'],
        ]);
        $this->add_control('star_empty', [
            'label' => __('Empty star color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => 'rgba(17,17,22,0.16)',
            'selectors' => ['{{WRAPPER}} .nv-pw-rs' => '--nv-rs-empty: {{VALUE}};'],
        ]);
        $this->add_responsive_control('star_size', [
            'label' => __('Star size', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 12, 'max' => 40]],
            'default' => ['size' => 20, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-rs__stars' => 'font-size: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_control('label_color', [
            'label' => __('Label color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#14161D',
            'selectors' => ['{{WRAPPER}} .nv-pw-rs__label' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'label_typography',
            'selector' => '{{WRAPPER}} .nv-pw-rs__label',
        ]);
        $this->add_responsive_control('align', [
            'label' => __('Alignment', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::CHOOSE,
            'options' => [
                'flex-start' => ['title' => __('Left', 'nv-product-widgets'), 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Center', 'nv-product-widgets'), 'icon' => 'eicon-text-align-center'],
                'flex-end' => ['title' => __('Right', 'nv-product-widgets'), 'icon' => 'eicon-text-align-right'],
            ],
            'default' => 'flex-start',
            'selectors' => ['{{WRAPPER}} .nv-pw-rs' => 'justify-content: {{VALUE}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $source = ($s['source'] ?? 'manual') === 'woocommerce' ? 'woocommerce' : 'manual';
        $rating = isset($s['rating']) ? (float) $s['rating'] : 4.9;
        $label = trim((string) ($s['label'] ?? ''));
        $show_label = (($s['show_label'] ?? 'yes') === 'yes');

        if ($source === 'woocommerce') {
            $product = NV_PW_Editor_Helper::get_preview_product();
            if ($product instanceof WC_Product) {
                $rating = (float) $product->get_average_rating();
                $count = (int) $product->get_rating_count();
                if ($label === '') {
                    $label = sprintf('%s/5 · %d %s', number_format_i18n($rating, 1), $count, _n('recension', 'recensioner', $count, 'nv-product-widgets'));
                }
            }
        }

        $rating = max(0, min(5, $rating));
        $pct = ($rating / 5) * 100;
        ?>
        <div class="nv-pw-rs" style="--nv-rs-fill: <?php echo esc_attr((string) $pct); ?>%;">
            <span class="nv-pw-rs__stars" role="img" aria-label="<?php echo esc_attr(sprintf(__('%s of 5 stars', 'nv-product-widgets'), number_format_i18n($rating, 1))); ?>">
                <span class="nv-pw-rs__stars-base">★★★★★</span>
                <span class="nv-pw-rs__stars-fill" aria-hidden="true">★★★★★</span>
            </span>
            <?php if ($show_label && $label !== '') : ?>
                <span class="nv-pw-rs__label"><?php echo esc_html($label); ?></span>
            <?php endif; ?>
        </div>
        <?php
    }
}
