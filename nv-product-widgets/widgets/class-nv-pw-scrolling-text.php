<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Scrolling_Text extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-scrolling-text'; }
    public function get_title(): string { return 'NV: Scrolling Text'; }
    public function get_icon(): string { return 'eicon-animation-text'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['scrolling', 'marquee', 'text', 'ticker', 'banderoll']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $r = new \Elementor\Repeater();
        $r->add_control('text', ['label' => __('Phrase', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('FRI FRAKT ÖVER 499 KR', 'nv-product-widgets')]);
        $this->add_control('items', [
            'label' => __('Phrases', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ text }}}',
            'default' => [['text' => __('FRI FRAKT ÖVER 499 KR', 'nv-product-widgets')], ['text' => __('30 DAGARS ÖPPET KÖP', 'nv-product-widgets')], ['text' => __('SNABB LEVERANS', 'nv-product-widgets')]],
        ]);
        $this->add_control('separator', ['label' => __('Separator', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'dot', 'options' => ['dot' => '•', 'star' => '★', 'slash' => '/', 'dash' => '—', 'none' => __('None', 'nv-product-widgets')]]);
        $this->add_control('direction', ['label' => __('Direction', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'left', 'options' => ['left' => __('Left', 'nv-product-widgets'), 'right' => __('Right', 'nv-product-widgets')]]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('bg', ['label' => __('Background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#312E81', 'selectors' => ['{{WRAPPER}} .nv-pw-st' => '--nv-st-bg: {{VALUE}};']]);
        $this->add_control('color', ['label' => __('Text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => ['{{WRAPPER}} .nv-pw-st' => '--nv-st-color: {{VALUE}};']]);
        $this->add_responsive_control('fs', ['label' => __('Font size', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 12, 'max' => 64]], 'default' => ['size' => 26, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-st__track' => 'font-size: {{SIZE}}{{UNIT}};']]);
        $this->add_control('speed', ['label' => __('Speed (seconds)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 8, 'max' => 80]], 'default' => ['size' => 26, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-st__track' => '--nv-st-dur: {{SIZE}}s;']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $phrases = [];
        foreach (($s['items'] ?? []) as $r) {
            if (is_array($r) && trim((string) ($r['text'] ?? '')) !== '') $phrases[] = trim((string) $r['text']);
        }
        if (empty($phrases)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Scrolling Text', '🔤');
            return;
        }
        $map = ['dot' => '•', 'star' => '★', 'slash' => '/', 'dash' => '—', 'none' => ''];
        $sep = $map[$s['separator'] ?? 'dot'] ?? '•';
        $dir = ($s['direction'] ?? 'left') === 'right' ? 'right' : 'left';
        $seq = array_merge($phrases, $phrases, $phrases);
        ?>
        <div class="nv-pw-st nv-pw-st--<?php echo esc_attr($dir); ?>">
            <div class="nv-pw-st__track">
                <?php foreach ($seq as $p) : ?>
                    <span class="nv-pw-st__item"><?php echo esc_html($p); ?></span>
                    <?php if ($sep !== '') : ?><span class="nv-pw-st__sep" aria-hidden="true"><?php echo esc_html($sep); ?></span><?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
