<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Benefits_List extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-benefits-list'; }
    public function get_title(): string { return 'NV: Benefits List'; }
    public function get_icon(): string { return 'eicon-check-circle'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['benefits', 'features', 'list', 'selling points']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('Content', 'nv-product-widgets'),
        ]);

        $this->add_control('heading', [
            'label' => __('Heading', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Därför väljer kunder oss', 'nv-product-widgets'),
        ]);

        $repeater = new \Elementor\Repeater();
        $repeater->add_control('marker_type', [
            'label' => __('Marker type', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'emoji',
            'options' => [
                'emoji' => __('Emoji / Text', 'nv-product-widgets'),
                'image' => __('Image (.png / .gif / .jpeg)', 'nv-product-widgets'),
                'icon' => __('Icon library', 'nv-product-widgets'),
            ],
        ]);
        $repeater->add_control('icon', [
            'label' => __('Icon text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '✓',
            'condition' => ['marker_type' => 'emoji'],
        ]);
        $repeater->add_control('image', [
            'label' => __('Image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'media_types' => ['image'],
            'condition' => ['marker_type' => 'image'],
        ]);
        $repeater->add_control('lib_icon', [
            'label' => __('Icon', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::ICONS,
            'condition' => ['marker_type' => 'icon'],
        ]);
        $repeater->add_control('title', [
            'label' => __('Title', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Snabb leverans', 'nv-product-widgets'),
        ]);
        $repeater->add_control('text', [
            'label' => __('Description', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Skickas inom 24 timmar från vårt lager.', 'nv-product-widgets'),
        ]);

        $this->add_control('items', [
            'label' => __('Benefits', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => '{{{ title }}}',
            'default' => [
                [
                    'icon' => '✓',
                    'title' => __('Snabb leverans', 'nv-product-widgets'),
                    'text' => __('Skickas inom 24 timmar från vårt lager.', 'nv-product-widgets'),
                ],
                [
                    'icon' => '✓',
                    'title' => __('Trygg betalning', 'nv-product-widgets'),
                    'text' => __('Betala säkert med Klarna, kort eller Swish.', 'nv-product-widgets'),
                ],
                [
                    'icon' => '✓',
                    'title' => __('Nöjd-kund-garanti', 'nv-product-widgets'),
                    'text' => __('30 dagars öppet köp på alla beställningar.', 'nv-product-widgets'),
                ],
            ],
        ]);

        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);

        $this->add_control('heading_style', ['label' => __('Heading', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::HEADING]);
        $this->add_control('heading_color', [
            'label' => __('Heading color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nv-pw-benefits__heading' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'heading_typography',
            'selector' => '{{WRAPPER}} .nv-pw-benefits__heading',
        ]);

        $this->add_control('title_style', ['label' => __('Benefit title', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('item_title_color', [
            'label' => __('Title color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nv-pw-benefits__title' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'item_title_typography',
            'selector' => '{{WRAPPER}} .nv-pw-benefits__title',
        ]);

        $this->add_control('text_style', ['label' => __('Benefit text', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('item_text_color', [
            'label' => __('Text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nv-pw-benefits__text' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'item_text_typography',
            'selector' => '{{WRAPPER}} .nv-pw-benefits__text',
        ]);

        $this->add_control('marker_style', ['label' => __('Emoji marker', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('icon_bg', [
            'label' => __('Emoji marker background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nv-pw-benefits__icon:not(.nv-pw-benefits__icon--image):not(.nv-pw-benefits__icon--lib)' => 'background: {{VALUE}};'],
        ]);
        $this->add_control('icon_color', [
            'label' => __('Emoji marker text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nv-pw-benefits__icon:not(.nv-pw-benefits__icon--image)' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('card_style', ['label' => __('Card', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('card_bg', [
            'label' => __('Card background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nv-pw-benefits__item' => 'background: {{VALUE}};'],
        ]);
        $this->add_control('card_border', [
            'label' => __('Card border color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nv-pw-benefits__item' => 'border-color: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    protected function render(): void {
        if (!NV_PW_Module_Bridge::is_module_enabled('reasons_to_buy')) {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Benefits List (Disabled in Product Widgets settings)', '✅');
            }
            return;
        }

        $settings = $this->get_settings_for_display();
        $heading = trim((string) ($settings['heading'] ?? ''));

        $items = [];
        foreach (($settings['items'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $text = trim((string) ($row['text'] ?? ''));
            if ($title === '' && $text === '') {
                continue;
            }

            $items[] = [
                'marker_type' => (string) ($row['marker_type'] ?? 'emoji'),
                'icon' => trim((string) ($row['icon'] ?? '✓')),
                'image' => (string) ($row['image']['url'] ?? ''),
                'image_alt' => trim((string) ($row['image']['alt'] ?? '')),
                'lib_icon' => (is_array($row['lib_icon'] ?? null) && !empty($row['lib_icon']['value'])) ? $row['lib_icon'] : null,
                'title' => $title,
                'text' => $text,
            ];
        }

        if (empty($items)) {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Benefits List', '✅');
            }
            return;
        }

        ?>
        <div class="nv-pw-benefits">
            <?php if ($heading !== '') : ?>
                <h3 class="nv-pw-benefits__heading<?php echo NV_PW_Headline::mod($settings); ?>"><?php echo esc_html($heading); ?></h3>
            <?php endif; ?>
            <ul class="nv-pw-benefits__list">
                <?php foreach ($items as $item) :
                    $mt = $item['marker_type'];
                    if ($mt === 'image' && $item['image'] !== '') {
                        $marker_class = ' nv-pw-benefits__icon--image';
                    } elseif ($mt === 'icon' && $item['lib_icon'] !== null) {
                        $marker_class = ' nv-pw-benefits__icon--lib';
                    } else {
                        $marker_class = '';
                    }
                    ?>
                    <li class="nv-pw-benefits__item">
                        <span class="nv-pw-benefits__icon<?php echo $marker_class; ?>" aria-hidden="true"><?php
                            if ($mt === 'image' && $item['image'] !== '') {
                                echo '<img src="' . esc_url($item['image']) . '" alt="' . esc_attr($item['image_alt']) . '" loading="lazy">';
                            } elseif ($mt === 'icon' && $item['lib_icon'] !== null) {
                                \Elementor\Icons_Manager::render_icon($item['lib_icon'], ['aria-hidden' => 'true']);
                            } else {
                                echo esc_html($item['icon'] !== '' ? $item['icon'] : '✓');
                            }
                        ?></span>
                        <div class="nv-pw-benefits__content">
                            <?php if ($item['title'] !== '') : ?>
                                <strong class="nv-pw-benefits__title"><?php echo esc_html($item['title']); ?></strong>
                            <?php endif; ?>
                            <?php if ($item['text'] !== '') : ?>
                                <p class="nv-pw-benefits__text"><?php echo esc_html($item['text']); ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
}
