<?php
if (!defined('ABSPATH')) exit;

/**
 * NV: Icon Columns — a 2–6 column icon + heading + text grid. The flexible
 * multicolumn "why buy" row every landing page uses; the horizontal counterpart
 * to the vertical Benefits List.
 */
class NV_PW_Icon_Columns extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-icon-columns'; }
    public function get_title(): string { return 'NV: Icon Columns'; }
    public function get_icon(): string { return 'eicon-icon-box'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['icon', 'columns', 'features', 'multicolumn', 'usp', 'benefits', 'why buy']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('heading', ['label' => __('Heading (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '', 'label_block' => true]);
        $this->add_control('subheading', ['label' => __('Subheading (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => '']);
        $r = new \Elementor\Repeater();
        $r->add_control('icon', ['label' => __('Icon', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::ICONS, 'default' => ['value' => 'fas fa-star', 'library' => 'fa-solid']]);
        $r->add_control('title', ['label' => __('Title', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Feature', 'nv-product-widgets')]);
        $r->add_control('text', ['label' => __('Text', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('Short supporting sentence about this benefit.', 'nv-product-widgets')]);
        $r->add_control('link', ['label' => __('Link (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::URL, 'default' => ['url' => '']]);
        $this->add_control('items', [
            'label' => __('Columns', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ title }}}',
            'default' => [
                ['icon' => ['value' => 'fas fa-truck', 'library' => 'fa-solid'], 'title' => __('Fri frakt', 'nv-product-widgets'), 'text' => __('Fri, spårbar frakt i hela Norden.', 'nv-product-widgets')],
                ['icon' => ['value' => 'fas fa-shield-halved', 'library' => 'fa-solid'], 'title' => __('60 dagars öppet köp', 'nv-product-widgets'), 'text' => __('Nöjd eller pengarna tillbaka.', 'nv-product-widgets')],
                ['icon' => ['value' => 'fas fa-medal', 'library' => 'fa-solid'], 'title' => __('9 000+ nöjda kunder', 'nv-product-widgets'), 'text' => __('Betyg 4,8 av 5 i snitt.', 'nv-product-widgets')],
            ],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('columns', ['label' => __('Columns', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 3, 'tablet_default' => 2, 'mobile_default' => 1, 'min' => 1, 'max' => 6, 'selectors' => ['{{WRAPPER}} .nv-pw-ic' => '--nv-ic-cols: {{VALUE}};']]);
        $this->add_control('layout', ['label' => __('Icon layout', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'stacked', 'options' => ['stacked' => __('Icon above text', 'nv-product-widgets'), 'inline' => __('Icon beside text', 'nv-product-widgets')]]);
        $this->add_control('align', ['label' => __('Alignment', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::CHOOSE, 'options' => ['left' => ['title' => __('Left', 'nv-product-widgets'), 'icon' => 'eicon-text-align-left'], 'center' => ['title' => __('Center', 'nv-product-widgets'), 'icon' => 'eicon-text-align-center']], 'default' => 'center', 'selectors' => ['{{WRAPPER}} .nv-pw-ic' => '--nv-ic-align: {{VALUE}};']]);
        $this->add_control('boxed', ['label' => __('Boxed cards', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => '']);
        $this->add_control('icon_size', ['label' => __('Icon size', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 16, 'max' => 72]], 'default' => ['size' => 32, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-ic__icon' => 'font-size: {{SIZE}}px;', '{{WRAPPER}} .nv-pw-ic__icon svg' => 'width: {{SIZE}}px;height: {{SIZE}}px;']]);
        $this->add_control('icon_color', ['label' => __('Icon color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '', 'description' => __('Leave blank to use your global brand accent (Settings → NV Brand).', 'nv-product-widgets'), 'selectors' => ['{{WRAPPER}} .nv-pw-ic' => '--nv-ic-icon: {{VALUE}};']]);
        $this->add_control('icon_bg', ['label' => __('Icon background (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '', 'selectors' => ['{{WRAPPER}} .nv-pw-ic' => '--nv-ic-icon-bg: {{VALUE}};']]);
        $this->add_control('title_color', ['label' => __('Title color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-ic' => '--nv-ic-title: {{VALUE}};']]);
        $this->add_control('text_color', ['label' => __('Text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-ic' => '--nv-ic-text: {{VALUE}};']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $rows = is_array($s['items'] ?? null) ? $s['items'] : [];
        $items = [];
        foreach ($rows as $it) {
            if (is_array($it) && (trim((string) ($it['title'] ?? '')) !== '' || !empty($it['icon']['value']))) $items[] = $it;
        }
        if (empty($items)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Icon Columns — add columns', '⭐');
            return;
        }

        $layout = ($s['layout'] ?? 'stacked') === 'inline' ? 'inline' : 'stacked';
        $boxed = (($s['boxed'] ?? '') === 'yes');
        $heading = trim((string) ($s['heading'] ?? ''));
        $sub = trim((string) ($s['subheading'] ?? ''));
        $has_bg = trim((string) ($s['icon_bg'] ?? '')) !== '';
        ?>
        <div class="nv-pw-ic nv-pw-ic--<?php echo esc_attr($layout); ?><?php echo $boxed ? ' nv-pw-ic--boxed' : ''; ?><?php echo $has_bg ? ' nv-pw-ic--icon-bg' : ''; ?>">
            <?php if ($heading !== '' || $sub !== '') : ?>
                <div class="nv-pw-ic__head">
                    <?php if ($heading !== '') : ?><h2 class="nv-pw-ic__heading"><?php echo esc_html($heading); ?></h2><?php endif; ?>
                    <?php if ($sub !== '') : ?><p class="nv-pw-ic__sub"><?php echo esc_html($sub); ?></p><?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="nv-pw-ic__grid">
                <?php foreach ($items as $it) :
                    $title = trim((string) ($it['title'] ?? ''));
                    $text = trim((string) ($it['text'] ?? ''));
                    $link = isset($it['link']['url']) ? (string) $it['link']['url'] : '';
                    $target = !empty($it['link']['is_external']) ? ' target="_blank" rel="noopener"' : '';
                    $tag = $link !== '' ? 'a' : 'div';
                    ?>
                    <<?php echo $tag; ?> class="nv-pw-ic__col"<?php echo $link !== '' ? ' href="' . esc_url($link) . '"' . $target : ''; ?>>
                        <?php if (!empty($it['icon']['value'])) : ?>
                            <span class="nv-pw-ic__icon"><?php \Elementor\Icons_Manager::render_icon($it['icon'], ['aria-hidden' => 'true']); ?></span>
                        <?php endif; ?>
                        <span class="nv-pw-ic__body">
                            <?php if ($title !== '') : ?><span class="nv-pw-ic__title"><?php echo esc_html($title); ?></span><?php endif; ?>
                            <?php if ($text !== '') : ?><span class="nv-pw-ic__text"><?php echo esc_html($text); ?></span><?php endif; ?>
                        </span>
                    </<?php echo $tag; ?>>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
