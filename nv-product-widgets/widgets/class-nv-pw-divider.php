<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Divider extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-divider'; }
    public function get_title(): string { return 'NV: Section Divider'; }
    public function get_icon(): string { return 'eicon-divider'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['divider', 'separator', 'spacer', 'shape', 'wave', 'avdelare']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('type', [
            'label' => __('Style', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'line',
            'options' => [
                'line' => __('Line', 'nv-product-widgets'),
                'dashed' => __('Dashed', 'nv-product-widgets'),
                'dots' => __('Dots', 'nv-product-widgets'),
                'icon' => __('Center icon', 'nv-product-widgets'),
                'wave' => __('Wave shape', 'nv-product-widgets'),
                'tilt' => __('Tilt shape', 'nv-product-widgets'),
                'zigzag' => __('Zigzag shape', 'nv-product-widgets'),
            ],
        ]);
        $this->add_control('icon', ['label' => __('Icon', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::ICONS, 'default' => ['value' => 'fas fa-star', 'library' => 'fa-solid'], 'condition' => ['type' => 'icon']]);
        $this->add_control('color', ['label' => __('Color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#E5E7EB', 'selectors' => ['{{WRAPPER}} .nv-pw-div' => '--nv-div-color: {{VALUE}};']]);
        $this->add_responsive_control('space', ['label' => __('Vertical space', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 120]], 'default' => ['size' => 28, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-div' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};']]);
        $this->add_control('shape_h', ['label' => __('Shape height', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 20, 'max' => 160]], 'default' => ['size' => 60, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-div__shape svg' => 'height: {{SIZE}}{{UNIT}};'], 'condition' => ['type' => ['wave', 'tilt', 'zigzag']]]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $type = in_array(($s['type'] ?? 'line'), ['line', 'dashed', 'dots', 'icon', 'wave', 'tilt', 'zigzag'], true) ? (string) $s['type'] : 'line';
        ?>
        <div class="nv-pw-div nv-pw-div--<?php echo esc_attr($type); ?>">
            <?php if (in_array($type, ['line', 'dashed', 'dots'], true)) : ?>
                <span class="nv-pw-div__rule"></span>
            <?php elseif ($type === 'icon') : ?>
                <span class="nv-pw-div__rule"></span>
                <span class="nv-pw-div__icon"><?php if (!empty($s['icon']['value'])) \Elementor\Icons_Manager::render_icon($s['icon'], ['aria-hidden' => 'true']); ?></span>
                <span class="nv-pw-div__rule"></span>
            <?php else : ?>
                <span class="nv-pw-div__shape" aria-hidden="true">
                    <?php if ($type === 'wave') : ?>
                        <svg viewBox="0 0 1200 60" preserveAspectRatio="none"><path d="M0,30 C150,60 350,0 600,30 C850,60 1050,0 1200,30 L1200,60 L0,60 Z" fill="currentColor"/></svg>
                    <?php elseif ($type === 'tilt') : ?>
                        <svg viewBox="0 0 1200 60" preserveAspectRatio="none"><path d="M0,60 L1200,0 L1200,60 Z" fill="currentColor"/></svg>
                    <?php else : ?>
                        <svg viewBox="0 0 1200 60" preserveAspectRatio="none"><path d="M0,60 L100,10 L200,60 L300,10 L400,60 L500,10 L600,60 L700,10 L800,60 L900,10 L1000,60 L1100,10 L1200,60 Z" fill="currentColor"/></svg>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
        <?php
    }
}
