<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Before_After extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-before-after'; }
    public function get_title(): string { return 'NV: Before/After Slider'; }
    public function get_icon(): string { return 'eicon-image-before-after'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['before', 'after', 'comparison', 'slider', 'transformation', 'fore', 'efter']; }
    public function get_script_depends(): array { return ['nv-before-after']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('Content', 'nv-product-widgets'),
        ]);

        $this->add_control('heading', [
            'label' => __('Heading (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
            'placeholder' => __('Se skillnaden själv', 'nv-product-widgets'),
        ]);
        $this->add_control('subheading', [
            'label' => __('Sub-heading (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => '',
            'placeholder' => __('Dra i reglaget för att jämföra.', 'nv-product-widgets'),
        ]);
        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->add_control('before_image', [
            'label' => __('Before image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => ['url' => \Elementor\Utils::get_placeholder_image_src()],
        ]);
        $this->add_control('after_image', [
            'label' => __('After image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => ['url' => \Elementor\Utils::get_placeholder_image_src()],
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
        $this->add_control('orientation', [
            'label' => __('Orientation', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'horizontal',
            'options' => [
                'horizontal' => __('Horizontal', 'nv-product-widgets'),
                'vertical' => __('Vertical', 'nv-product-widgets'),
            ],
        ]);
        $this->add_control('start', [
            'label' => __('Start position (%)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 0, 'max' => 100]],
            'default' => ['size' => 50, 'unit' => 'px'],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_style', [
            'label' => __('Style', 'nv-product-widgets'),
            'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        $this->add_responsive_control('max_width', [
            'label' => __('Max width', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 240, 'max' => 1200]],
            'default' => ['size' => 720, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-ba' => 'max-width: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_control('radius', [
            'label' => __('Border radius', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 0, 'max' => 48]],
            'default' => ['size' => 16, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-ba' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_control('accent', [
            'label' => __('Handle color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#FFFFFF',
            'selectors' => ['{{WRAPPER}} .nv-pw-ba' => '--nv-ba-accent: {{VALUE}};'],
        ]);
        $this->add_control('label_bg', [
            'label' => __('Label background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => 'rgba(17,17,22,0.62)',
            'selectors' => ['{{WRAPPER}} .nv-pw-ba' => '--nv-ba-label-bg: {{VALUE}};'],
        ]);
        $this->add_control('label_color', [
            'label' => __('Label text color (both)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#FFFFFF',
            'selectors' => ['{{WRAPPER}} .nv-pw-ba' => '--nv-ba-label-color: {{VALUE}};'],
        ]);
        $this->add_control('before_label_color', [
            'label' => __('Before label — text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nv-pw-ba__label--before' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('before_label_bg', [
            'label' => __('Before label — background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nv-pw-ba__label--before' => 'background: {{VALUE}};'],
        ]);
        $this->add_control('after_label_color', [
            'label' => __('After label — text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nv-pw-ba__label--after' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('after_label_bg', [
            'label' => __('After label — background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nv-pw-ba__label--after' => 'background: {{VALUE}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $before = isset($s['before_image']['url']) ? (string) $s['before_image']['url'] : '';
        $after  = isset($s['after_image']['url']) ? (string) $s['after_image']['url'] : '';

        if ($before === '' || $after === '') {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Before/After Slider', '🖼️');
            }
            return;
        }

        $heading = trim((string) ($s['heading'] ?? ''));
        $b_label = trim((string) ($s['before_label'] ?? ''));
        $a_label = trim((string) ($s['after_label'] ?? ''));
        $show_labels = (($s['show_labels'] ?? 'yes') === 'yes');
        $orientation = ($s['orientation'] ?? 'horizontal') === 'vertical' ? 'vertical' : 'horizontal';
        $start = isset($s['start']['size']) ? max(0, min(100, (float) $s['start']['size'])) : 50;
        $b_alt = isset($s['before_image']['alt']) ? (string) $s['before_image']['alt'] : ($b_label !== '' ? $b_label : 'before');
        $a_alt = isset($s['after_image']['alt']) ? (string) $s['after_image']['alt'] : ($a_label !== '' ? $a_label : 'after');
        ?>
        <div class="nv-pw-ba-wrap">
            <?php if ($heading !== '') : ?>
                <h3 class="nv-pw-ba-heading<?php echo NV_PW_Headline::mod($s); ?>"><?php echo NV_PW_Headline::html($heading); ?></h3>
            <?php endif; ?>
            <?php $ba_sub = trim((string) ($s['subheading'] ?? '')); ?>
            <?php if ($ba_sub !== '') : ?>
                <p class="nv-pw-ba-sub"><?php echo esc_html($ba_sub); ?></p>
            <?php endif; ?>
            <div class="nv-pw-ba" data-nv-ba data-orientation="<?php echo esc_attr($orientation); ?>" style="--nv-ba-pos: <?php echo esc_attr($start); ?>%;">
                <div class="nv-pw-ba__layer nv-pw-ba__after">
                    <img src="<?php echo esc_url($after); ?>" alt="<?php echo esc_attr($a_alt); ?>" loading="lazy" draggable="false">
                    <?php if ($show_labels && $a_label !== '') : ?>
                        <span class="nv-pw-ba__label nv-pw-ba__label--after"><?php echo esc_html($a_label); ?></span>
                    <?php endif; ?>
                </div>
                <div class="nv-pw-ba__layer nv-pw-ba__before">
                    <img src="<?php echo esc_url($before); ?>" alt="<?php echo esc_attr($b_alt); ?>" loading="lazy" draggable="false">
                    <?php if ($show_labels && $b_label !== '') : ?>
                        <span class="nv-pw-ba__label nv-pw-ba__label--before"><?php echo esc_html($b_label); ?></span>
                    <?php endif; ?>
                </div>
                <div class="nv-pw-ba__handle" role="slider" aria-label="<?php echo esc_attr__('Drag to compare', 'nv-product-widgets'); ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr((int) $start); ?>" tabindex="0">
                    <span class="nv-pw-ba__line"></span>
                    <span class="nv-pw-ba__knob" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 3 12 9 18"/><polyline points="15 6 21 12 15 18"/></svg>
                    </span>
                </div>
            </div>
        </div>
        <?php
    }
}
