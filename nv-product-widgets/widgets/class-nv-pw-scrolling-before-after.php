<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Scrolling_Before_After extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-scrolling-before-after'; }
    public function get_title(): string { return 'NV: Scrolling Before/After'; }
    public function get_icon(): string { return 'eicon-slider-push'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['before', 'after', 'scrolling', 'marquee', 'transformation', 'fore', 'efter', 'results']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('heading', [
            'label' => __('Heading (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
            'placeholder' => __('Verkliga resultat', 'nv-product-widgets'),
        ]);
        $this->add_control('before_label', [
            'label' => __('Before label', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Före', 'nv-product-widgets'),
        ]);
        $this->add_control('after_label', [
            'label' => __('After label', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Efter', 'nv-product-widgets'),
        ]);
        $this->add_control('show_labels', [
            'label' => __('Show labels', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $r = new \Elementor\Repeater();
        $r->add_control('before_image', [
            'label' => __('Before image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => ['url' => \Elementor\Utils::get_placeholder_image_src()],
        ]);
        $r->add_control('after_image', [
            'label' => __('After image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => ['url' => \Elementor\Utils::get_placeholder_image_src()],
        ]);
        $this->add_control('items', [
            'label' => __('Before/After pairs', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'default' => [
                ['before_image' => ['url' => \Elementor\Utils::get_placeholder_image_src()], 'after_image' => ['url' => \Elementor\Utils::get_placeholder_image_src()]],
                ['before_image' => ['url' => \Elementor\Utils::get_placeholder_image_src()], 'after_image' => ['url' => \Elementor\Utils::get_placeholder_image_src()]],
                ['before_image' => ['url' => \Elementor\Utils::get_placeholder_image_src()], 'after_image' => ['url' => \Elementor\Utils::get_placeholder_image_src()]],
            ],
        ]);
        $this->add_control('direction', [
            'label' => __('Direction', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'left',
            'options' => ['left' => __('Left', 'nv-product-widgets'), 'right' => __('Right', 'nv-product-widgets')],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('card_h', [
            'label' => __('Card height', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 120, 'max' => 420]],
            'default' => ['size' => 220, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-sba__card' => 'height: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_responsive_control('card_w', [
            'label' => __('Card width', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 160, 'max' => 520]],
            'default' => ['size' => 300, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-sba__card' => 'width: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_control('gap', [
            'label' => __('Gap', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 0, 'max' => 60]],
            'default' => ['size' => 18, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-sba__track' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_control('radius', [
            'label' => __('Radius', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 0, 'max' => 40]],
            'default' => ['size' => 16, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-sba__card' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_control('speed', [
            'label' => __('Speed (seconds)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 8, 'max' => 80]],
            'default' => ['size' => 36, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-sba__track' => '--nv-sba-dur: {{SIZE}}s;'],
        ]);
        $this->add_control('accent', [
            'label' => __('Label background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => 'rgba(17,17,22,0.66)',
            'selectors' => ['{{WRAPPER}} .nv-pw-sba' => '--nv-sba-label-bg: {{VALUE}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $pairs = [];
        foreach (($s['items'] ?? []) as $row) {
            if (!is_array($row)) continue;
            $b = (string) ($row['before_image']['url'] ?? '');
            $a = (string) ($row['after_image']['url'] ?? '');
            if ($b === '' || $a === '') continue;
            $pairs[] = ['before' => $b, 'after' => $a];
        }
        if (empty($pairs)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Scrolling Before/After', '🔀');
            return;
        }
        $heading = trim((string) ($s['heading'] ?? ''));
        $b_label = trim((string) ($s['before_label'] ?? ''));
        $a_label = trim((string) ($s['after_label'] ?? ''));
        $show_labels = (($s['show_labels'] ?? 'yes') === 'yes');
        $dir = ($s['direction'] ?? 'left') === 'right' ? 'right' : 'left';
        $loop = array_merge($pairs, $pairs);
        ?>
        <div class="nv-pw-sba nv-pw-sba--<?php echo esc_attr($dir); ?>">
            <?php if ($heading !== '') : ?><h3 class="nv-pw-sba__heading"><?php echo esc_html($heading); ?></h3><?php endif; ?>
            <div class="nv-pw-sba__mask">
                <div class="nv-pw-sba__track">
                    <?php foreach ($loop as $p) : ?>
                        <div class="nv-pw-sba__card">
                            <div class="nv-pw-sba__half nv-pw-sba__half--before">
                                <img src="<?php echo esc_url($p['before']); ?>" alt="" loading="lazy" aria-hidden="true" draggable="false">
                                <?php if ($show_labels && $b_label !== '') : ?><span class="nv-pw-sba__label"><?php echo esc_html($b_label); ?></span><?php endif; ?>
                            </div>
                            <div class="nv-pw-sba__half nv-pw-sba__half--after">
                                <img src="<?php echo esc_url($p['after']); ?>" alt="" loading="lazy" aria-hidden="true" draggable="false">
                                <?php if ($show_labels && $a_label !== '') : ?><span class="nv-pw-sba__label"><?php echo esc_html($a_label); ?></span><?php endif; ?>
                            </div>
                            <span class="nv-pw-sba__divider" aria-hidden="true"></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
