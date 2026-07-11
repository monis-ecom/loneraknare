<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Tabs extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-tabs'; }
    public function get_title(): string { return 'NV: Tabs'; }
    public function get_icon(): string { return 'eicon-tabs'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['tabs', 'flikar', 'accordion', 'details', 'specs']; }
    public function get_script_depends(): array { return ['nv-tabs']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);

        $this->add_control('heading', [
            'label' => __('Heading (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);
        $this->add_control('nav_style', [
            'label' => __('Tab style', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'underline',
            'options' => [
                'underline' => __('Underline', 'nv-product-widgets'),
                'pills' => __('Pills', 'nv-product-widgets'),
            ],
        ]);

        $repeater = new \Elementor\Repeater();
        $repeater->add_control('title', [
            'label' => __('Tab title', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Beskrivning', 'nv-product-widgets'),
        ]);
        $repeater->add_control('content', [
            'label' => __('Tab content', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::WYSIWYG,
            'default' => __('Lägg till din text här. Du kan formatera med fet stil, listor och länkar.', 'nv-product-widgets'),
        ]);
        $this->add_control('items', [
            'label' => __('Tabs', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => '{{{ title }}}',
            'default' => [
                ['title' => __('Beskrivning', 'nv-product-widgets'), 'content' => __('En genomtänkt produkt byggd för att hålla. Här beskriver du de viktigaste fördelarna.', 'nv-product-widgets')],
                ['title' => __('Specifikationer', 'nv-product-widgets'), 'content' => __('<ul><li>Material: Premium</li><li>Vikt: 320 g</li><li>Garanti: 2 år</li></ul>', 'nv-product-widgets')],
                ['title' => __('Frakt & retur', 'nv-product-widgets'), 'content' => __('Fri frakt över 499 kr. 30 dagars öppet köp på alla beställningar.', 'nv-product-widgets')],
            ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('accent', [
            'label' => __('Active color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#312E81',
            'selectors' => ['{{WRAPPER}} .nv-pw-tabs' => '--nv-tabs-accent: {{VALUE}};'],
        ]);
        $this->add_control('text_color', [
            'label' => __('Text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#374151',
            'selectors' => ['{{WRAPPER}} .nv-pw-tabs' => '--nv-tabs-text: {{VALUE}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $rows = is_array($s['items'] ?? null) ? $s['items'] : [];
        $items = [];
        foreach ($rows as $r) {
            if (!is_array($r)) continue;
            $title = trim((string) ($r['title'] ?? ''));
            if ($title === '') continue;
            $items[] = $r;
        }
        if (empty($items)) {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Tabs', '📑');
            }
            return;
        }
        $heading = trim((string) ($s['heading'] ?? ''));
        $nav_style = ($s['nav_style'] ?? 'underline') === 'pills' ? 'pills' : 'underline';
        $nav_class = $nav_style === 'pills' ? ' nv-pw-tabs--pills' : '';
        $uid = 'nvtabs-' . $this->get_id();
        ?>
        <div class="nv-pw-tabs<?php echo $nav_class; ?>" data-nv-tabs>
            <?php if ($heading !== '') : ?><h3 class="nv-pw-tabs__heading"><?php echo esc_html($heading); ?></h3><?php endif; ?>
            <div class="nv-pw-tabs__nav" role="tablist">
                <?php foreach ($items as $idx => $it) :
                    $active = $idx === 0 ? ' is-active' : '';
                    ?>
                    <button type="button" class="nv-pw-tabs__tab<?php echo $active; ?>" role="tab" aria-selected="<?php echo $idx === 0 ? 'true' : 'false'; ?>" id="<?php echo esc_attr($uid . '-t' . $idx); ?>" aria-controls="<?php echo esc_attr($uid . '-p' . $idx); ?>" data-nv-tab="<?php echo (int) $idx; ?>">
                        <?php echo esc_html((string) $it['title']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="nv-pw-tabs__panels">
                <?php foreach ($items as $idx => $it) :
                    $active = $idx === 0 ? ' is-active' : '';
                    ?>
                    <div class="nv-pw-tabs__panel<?php echo $active; ?>" role="tabpanel" id="<?php echo esc_attr($uid . '-p' . $idx); ?>" aria-labelledby="<?php echo esc_attr($uid . '-t' . $idx); ?>" data-nv-panel="<?php echo (int) $idx; ?>"<?php echo $idx === 0 ? '' : ' hidden'; ?>>
                        <?php echo wp_kses_post((string) ($it['content'] ?? '')); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
