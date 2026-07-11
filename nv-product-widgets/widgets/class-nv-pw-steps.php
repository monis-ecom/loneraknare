<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Steps extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-steps'; }
    public function get_title(): string { return 'NV: Steps / How it works'; }
    public function get_icon(): string { return 'eicon-number-field'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['steps', 'how it works', 'process', 'sa har fungerar', 'guide']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);

        $this->add_control('heading', [
            'label' => __('Heading', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Så här fungerar det', 'nv-product-widgets'),
        ]);
        $this->add_control('columns', [
            'label' => __('Columns', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '3',
            'options' => ['2' => '2', '3' => '3', '4' => '4'],
        ]);
        $this->add_control('layout', [
            'label' => __('Layout style', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'default',
            'options' => [
                'default' => __('Default', 'nv-product-widgets'),
                'connected' => __('Connected (horizontal line)', 'nv-product-widgets'),
            ],
        ]);
        $this->add_control('show_numbers', [
            'label' => __('Show numbers', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $repeater = new \Elementor\Repeater();
        $repeater->add_control('marker_type', [
            'label' => __('Marker', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'icon',
            'options' => [
                'icon' => __('Icon / number', 'nv-product-widgets'),
                'image' => __('Image (.png / .gif / .jpeg / .avif)', 'nv-product-widgets'),
            ],
        ]);
        $repeater->add_control('icon', [
            'label' => __('Icon (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::ICONS,
            'condition' => ['marker_type' => 'icon'],
        ]);
        $repeater->add_control('image', [
            'label' => __('Image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'media_types' => ['image'],
            'condition' => ['marker_type' => 'image'],
        ]);
        $repeater->add_control('title', [
            'label' => __('Title', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Beställ enkelt', 'nv-product-widgets'),
        ]);
        $repeater->add_control('text', [
            'label' => __('Text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Lägg din order på under en minut – tryggt och säkert.', 'nv-product-widgets'),
        ]);
        $this->add_control('items', [
            'label' => __('Steps', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => '{{{ title }}}',
            'default' => [
                ['title' => __('Beställ enkelt', 'nv-product-widgets'), 'text' => __('Lägg din order på under en minut – tryggt och säkert.', 'nv-product-widgets')],
                ['title' => __('Snabb leverans', 'nv-product-widgets'), 'text' => __('Vi skickar inom 24 timmar direkt hem till dörren.', 'nv-product-widgets')],
                ['title' => __('Börja njut', 'nv-product-widgets'), 'text' => __('Packa upp och upplev skillnaden från dag ett.', 'nv-product-widgets')],
            ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('accent', [
            'label' => __('Accent color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#312E81',
            'selectors' => ['{{WRAPPER}} .nv-pw-steps' => '--nv-steps-accent: {{VALUE}};'],
        ]);
        $this->add_control('heading_color', [
            'label' => __('Heading color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#14161D',
            'selectors' => ['{{WRAPPER}} .nv-pw-steps' => '--nv-steps-heading: {{VALUE}};'],
        ]);
        $this->add_control('text_color', [
            'label' => __('Text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#4B5563',
            'selectors' => ['{{WRAPPER}} .nv-pw-steps' => '--nv-steps-text: {{VALUE}};'],
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
            $text = trim((string) ($r['text'] ?? ''));
            if ($title === '' && $text === '') continue;
            $items[] = $r;
        }
        if (empty($items)) {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Steps / How it works', '🪜');
            }
            return;
        }
        $heading = trim((string) ($s['heading'] ?? ''));
        $cols = in_array(($s['columns'] ?? '3'), ['2', '3', '4'], true) ? (string) $s['columns'] : '3';
        $show_numbers = (($s['show_numbers'] ?? 'yes') === 'yes');
        $layout = ($s['layout'] ?? 'default') === 'connected' ? 'connected' : 'default';
        $layout_class = $layout === 'connected' ? ' nv-pw-steps--connected' : '';
        ?>
        <div class="nv-pw-steps<?php echo $layout_class; ?>" style="--nv-steps-cols: <?php echo esc_attr($cols); ?>;">
            <?php if ($heading !== '') : ?><h3 class="nv-pw-steps__heading"><?php echo esc_html($heading); ?></h3><?php endif; ?>
            <ol class="nv-pw-steps__grid">
                <?php $n = 0; foreach ($items as $it) : $n++;
                    $title = trim((string) ($it['title'] ?? ''));
                    $text = trim((string) ($it['text'] ?? ''));
                    $marker_type = (string) ($it['marker_type'] ?? 'icon');
                    $marker_img = ($marker_type === 'image' && !empty($it['image']['url'])) ? (string) $it['image']['url'] : '';
                    $marker_img_alt = trim((string) ($it['image']['alt'] ?? ''));
                    $has_icon = ($marker_type === 'icon') && !empty($it['icon']['value']);
                    ?>
                    <li class="nv-pw-steps__item">
                        <span class="nv-pw-steps__marker<?php echo $marker_img !== '' ? ' nv-pw-steps__marker--image' : ''; ?>">
                            <?php if ($marker_img !== '') : ?>
                                <img src="<?php echo esc_url($marker_img); ?>" alt="<?php echo esc_attr($marker_img_alt); ?>" loading="lazy">
                            <?php elseif ($has_icon) : ?>
                                <?php \Elementor\Icons_Manager::render_icon($it['icon'], ['aria-hidden' => 'true']); ?>
                            <?php elseif ($show_numbers) : ?>
                                <span class="nv-pw-steps__num"><?php echo (int) $n; ?></span>
                            <?php else : ?>
                                <span class="nv-pw-steps__dot"></span>
                            <?php endif; ?>
                        </span>
                        <?php if ($title !== '') : ?><strong class="nv-pw-steps__title"><?php echo esc_html($title); ?></strong><?php endif; ?>
                        <?php if ($text !== '') : ?><p class="nv-pw-steps__text"><?php echo esc_html($text); ?></p><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php
    }
}
