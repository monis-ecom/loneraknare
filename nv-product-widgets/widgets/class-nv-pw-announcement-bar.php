<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Announcement_Bar extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-announcement-bar'; }
    public function get_title(): string { return 'NV: Announcement Bar'; }
    public function get_icon(): string { return 'eicon-tags'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['announcement', 'bar', 'notice', 'promo', 'topbar', 'usp']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('mode', [
            'label' => __('Mode', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'static',
            'options' => ['static' => __('Static (centered)', 'nv-product-widgets'), 'scroll' => __('Scrolling', 'nv-product-widgets'), 'spread' => __('Spread (3 items)', 'nv-product-widgets')],
        ]);
        $r = new \Elementor\Repeater();
        $r->add_control('icon', ['label' => __('Icon (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::ICONS]);
        $r->add_control('text', ['label' => __('Text', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Fri frakt över 499 kr', 'nv-product-widgets')]);
        $this->add_control('items', [
            'label' => __('Messages', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ text }}}',
            'default' => [
                ['icon' => ['value' => 'fas fa-truck', 'library' => 'fa-solid'], 'text' => __('Fri frakt över 499 kr', 'nv-product-widgets')],
                ['icon' => ['value' => 'fas fa-undo', 'library' => 'fa-solid'], 'text' => __('30 dagars öppet köp', 'nv-product-widgets')],
                ['icon' => ['value' => 'fas fa-lock', 'library' => 'fa-solid'], 'text' => __('Säker betalning', 'nv-product-widgets')],
            ],
        ]);
        $this->add_control('link_text', ['label' => __('Link text (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '', 'condition' => ['mode' => 'static']]);
        $this->add_control('link', ['label' => __('Link', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::URL, 'default' => ['url' => '#'], 'condition' => ['link_text!' => '']]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('bg', ['label' => __('Background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#14161D', 'selectors' => ['{{WRAPPER}} .nv-pw-ab' => '--nv-ab-bg: {{VALUE}};']]);
        $this->add_control('color', ['label' => __('Text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => ['{{WRAPPER}} .nv-pw-ab' => '--nv-ab-color: {{VALUE}};']]);
        $this->add_control('speed', ['label' => __('Scroll speed (s)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 8, 'max' => 80]], 'default' => ['size' => 28, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-ab__scroll' => '--nv-ab-dur: {{SIZE}}s;'], 'condition' => ['mode' => 'scroll']]);
        $this->end_controls_section();
    }

    private function item_html(array $it): string {
        $text = trim((string) ($it['text'] ?? ''));
        if ($text === '') return '';
        ob_start(); ?>
        <span class="nv-pw-ab__item">
            <?php if (!empty($it['icon']['value'])) : ?><span class="nv-pw-ab__icon" aria-hidden="true"><?php \Elementor\Icons_Manager::render_icon($it['icon'], ['aria-hidden' => 'true']); ?></span><?php endif; ?>
            <span><?php echo esc_html($text); ?></span>
        </span>
        <?php return (string) ob_get_clean();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $items = [];
        foreach (($s['items'] ?? []) as $r) {
            if (is_array($r) && trim((string) ($r['text'] ?? '')) !== '') $items[] = $r;
        }
        if (empty($items)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Announcement Bar', '📣');
            return;
        }
        $mode = in_array(($s['mode'] ?? 'static'), ['static', 'scroll', 'spread'], true) ? (string) $s['mode'] : 'static';
        $link_text = trim((string) ($s['link_text'] ?? ''));
        $link_url = isset($s['link']['url']) ? (string) $s['link']['url'] : '';
        $link_target = !empty($s['link']['is_external']) ? ' target="_blank" rel="noopener"' : '';
        ?>
        <div class="nv-pw-ab nv-pw-ab--<?php echo esc_attr($mode); ?>">
            <?php if ($mode === 'scroll') : ?>
                <div class="nv-pw-ab__scroll-mask">
                    <div class="nv-pw-ab__scroll">
                        <?php for ($k = 0; $k < 2; $k++) : foreach ($items as $it) : echo $this->item_html($it); echo '<span class="nv-pw-ab__dot" aria-hidden="true">•</span>'; endforeach; endfor; ?>
                    </div>
                </div>
            <?php elseif ($mode === 'spread') : ?>
                <div class="nv-pw-ab__spread">
                    <?php foreach ($items as $it) { echo $this->item_html($it); } ?>
                </div>
            <?php else : ?>
                <div class="nv-pw-ab__static">
                    <?php echo $this->item_html($items[0]); ?>
                    <?php if ($link_text !== '') : ?><a class="nv-pw-ab__link" href="<?php echo esc_url($link_url !== '' ? $link_url : '#'); ?>"<?php echo $link_target; ?>><?php echo esc_html($link_text); ?> →</a><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
