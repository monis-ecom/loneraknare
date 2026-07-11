<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Trust_Badges extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-trust-badges'; }
    public function get_title(): string { return 'NV: Trust Badges'; }
    public function get_icon(): string { return 'eicon-shield-o'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['trust', 'badges', 'security', 'shipping']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('Content', 'nv-product-widgets'),
        ]);

        $this->add_control('heading', [
            'label' => __('Heading (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Tryggt köp', 'nv-product-widgets'),
        ]);
        $this->add_control('layout', [
            'label' => __('Layout', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'inline',
            'options' => [
                'inline' => __('Inline pills', 'nv-product-widgets'),
                'stacked' => __('Stacked columns (icon over label)', 'nv-product-widgets'),
            ],
        ]);
        $this->add_control('icon_bg', [
            'label' => __('Icon circle background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#EDEBFB',
            'selectors' => ['{{WRAPPER}} .nv-pw-trust-badges' => '--nv-tb-icon-bg: {{VALUE}};'],
            'condition' => ['layout' => 'stacked'],
        ]);
        $this->add_control('icon_color', [
            'label' => __('Icon color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#6C63E0',
            'selectors' => ['{{WRAPPER}} .nv-pw-trust-badges' => '--nv-tb-icon-color: {{VALUE}};'],
        ]);

        $repeater = new \Elementor\Repeater();
        $repeater->add_control('marker_type', [
            'label' => __('Icon type', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'emoji',
            'options' => [
                'emoji' => __('Emoji / Text', 'nv-product-widgets'),
                'image' => __('Image (.png / .gif / .jpeg / .avif)', 'nv-product-widgets'),
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
        $repeater->add_control('label', [
            'label' => __('Label', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Säker betalning', 'nv-product-widgets'),
        ]);

        $this->add_control('items', [
            'label' => __('Badges', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => '{{{ label }}}',
            'default' => [
                ['icon' => '🛡️', 'label' => __('Säker betalning', 'nv-product-widgets')],
                ['icon' => '🚚', 'label' => __('Snabb leverans', 'nv-product-widgets')],
                ['icon' => '↩', 'label' => __('30 dagars öppet köp', 'nv-product-widgets')],
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render(): void {
        if (!NV_PW_Module_Bridge::is_module_enabled('trust_badges')) {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Trust Badges (Disabled in Product Widgets settings)', '🛡️');
            }
            return;
        }

        $settings = $this->get_settings_for_display();
        $heading = trim((string) ($settings['heading'] ?? ''));
        $layout = ($settings['layout'] ?? 'inline') === 'stacked' ? 'stacked' : 'inline';

        $items = [];
        foreach (($settings['items'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $items[] = [
                'marker_type' => (string) ($row['marker_type'] ?? 'emoji'),
                'icon' => trim((string) ($row['icon'] ?? '✓')),
                'image' => (string) ($row['image']['url'] ?? ''),
                'image_alt' => trim((string) ($row['image']['alt'] ?? '')),
                'lib_icon' => (is_array($row['lib_icon'] ?? null) && !empty($row['lib_icon']['value'])) ? $row['lib_icon'] : null,
                'label' => $label,
            ];
        }

        if (empty($items)) {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Trust Badges', '🛡️');
            }
            return;
        }
        ?>
        <div class="nv-pw-trust-badges nv-pw-trust-badges--<?php echo esc_attr($layout); ?>">
            <?php if ($heading !== '') : ?>
                <h3 class="nv-pw-trust-badges__heading"><?php echo esc_html($heading); ?></h3>
            <?php endif; ?>
            <div class="nv-pw-trust-badges__list">
                <?php foreach ($items as $item) :
                    $mt = $item['marker_type'];
                    if ($mt === 'image' && $item['image'] !== '') {
                        $marker_class = ' nv-pw-trust-badges__icon--image';
                    } elseif ($mt === 'icon' && $item['lib_icon'] !== null) {
                        $marker_class = ' nv-pw-trust-badges__icon--lib';
                    } else {
                        $marker_class = '';
                    }
                    ?>
                    <span class="nv-pw-trust-badges__item">
                        <span class="nv-pw-trust-badges__icon<?php echo $marker_class; ?>" aria-hidden="true"><?php
                            if ($mt === 'image' && $item['image'] !== '') {
                                echo '<img src="' . esc_url($item['image']) . '" alt="' . esc_attr($item['image_alt']) . '" loading="lazy">';
                            } elseif ($mt === 'icon' && $item['lib_icon'] !== null) {
                                \Elementor\Icons_Manager::render_icon($item['lib_icon'], ['aria-hidden' => 'true']);
                            } else {
                                echo esc_html($item['icon'] !== '' ? $item['icon'] : '✓');
                            }
                        ?></span>
                        <span class="nv-pw-trust-badges__label"><?php echo esc_html($item['label']); ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
