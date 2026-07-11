<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Guarantee extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-guarantee'; }
    public function get_title(): string { return 'NV: Guarantee Block'; }
    public function get_icon(): string { return 'eicon-lock-user'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['guarantee', 'garanti', 'risk reversal', 'money back', 'trust', 'trygghet']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('Content', 'nv-product-widgets'),
        ]);

        $this->add_control('icon_type', [
            'label' => __('Main icon type', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'icon',
            'options' => [
                'icon' => __('Icon', 'nv-product-widgets'),
                'image' => __('Image (.png / .gif / .jpeg / .avif)', 'nv-product-widgets'),
            ],
        ]);
        $this->add_control('icon', [
            'label' => __('Icon', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::ICONS,
            'default' => ['value' => 'fas fa-shield-alt', 'library' => 'fa-solid'],
            'condition' => ['icon_type' => 'icon'],
        ]);
        $this->add_control('main_image', [
            'label' => __('Icon image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'media_types' => ['image'],
            'condition' => ['icon_type' => 'image'],
        ]);
        $this->add_control('heading', [
            'label' => __('Heading', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('30 dagars nöjd-kund-garanti', 'nv-product-widgets'),
        ]);
        $this->add_control('text', [
            'label' => __('Text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Inte helt nöjd? Skicka tillbaka produkten inom 30 dagar så får du pengarna tillbaka – inga frågor.', 'nv-product-widgets'),
        ]);
        $this->add_control('layout', [
            'label' => __('Layout', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'boxed',
            'options' => [
                'boxed' => __('Boxed (icon left)', 'nv-product-widgets'),
                'center' => __('Centered', 'nv-product-widgets'),
            ],
        ]);

        $repeater = new \Elementor\Repeater();
        $repeater->add_control('badge_marker_type', [
            'label' => __('Icon type', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'icon',
            'options' => [
                'icon' => __('Icon', 'nv-product-widgets'),
                'image' => __('Image (.png / .gif / .jpeg / .avif)', 'nv-product-widgets'),
            ],
        ]);
        $repeater->add_control('badge_icon', [
            'label' => __('Icon', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::ICONS,
            'default' => ['value' => 'fas fa-check', 'library' => 'fa-solid'],
            'condition' => ['badge_marker_type' => 'icon'],
        ]);
        $repeater->add_control('badge_image', [
            'label' => __('Icon image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'media_types' => ['image'],
            'condition' => ['badge_marker_type' => 'image'],
        ]);
        $repeater->add_control('label', [
            'label' => __('Label', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Fri frakt', 'nv-product-widgets'),
        ]);
        $this->add_control('badges', [
            'label' => __('Trust badges', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => '{{{ label }}}',
            'default' => [
                ['badge_icon' => ['value' => 'fas fa-truck', 'library' => 'fa-solid'], 'label' => __('Fri frakt', 'nv-product-widgets')],
                ['badge_icon' => ['value' => 'fas fa-lock', 'library' => 'fa-solid'], 'label' => __('Säker betalning', 'nv-product-widgets')],
                ['badge_icon' => ['value' => 'fas fa-undo', 'library' => 'fa-solid'], 'label' => __('Enkel retur', 'nv-product-widgets')],
            ],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_style', [
            'label' => __('Style', 'nv-product-widgets'),
            'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control('bg', [
            'label' => __('Background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#F6F7FB',
            'selectors' => ['{{WRAPPER}} .nv-pw-guarantee' => '--nv-gr-bg: {{VALUE}};'],
        ]);
        $this->add_control('accent', [
            'label' => __('Accent color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#312E81',
            'selectors' => ['{{WRAPPER}} .nv-pw-guarantee' => '--nv-gr-accent: {{VALUE}};'],
        ]);
        $this->add_control('heading_color', [
            'label' => __('Heading color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#14161D',
            'selectors' => ['{{WRAPPER}} .nv-pw-guarantee' => '--nv-gr-heading: {{VALUE}};'],
        ]);
        $this->add_control('text_color', [
            'label' => __('Text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#4B5563',
            'selectors' => ['{{WRAPPER}} .nv-pw-guarantee' => '--nv-gr-text: {{VALUE}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $heading = trim((string) ($s['heading'] ?? ''));
        $text = trim((string) ($s['text'] ?? ''));
        if ($heading === '' && $text === '') {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Guarantee Block', '🛡️');
            }
            return;
        }
        $layout = ($s['layout'] ?? 'boxed') === 'center' ? 'center' : 'boxed';
        $badges = is_array($s['badges'] ?? null) ? $s['badges'] : [];
        ?>
        <div class="nv-pw-guarantee nv-pw-guarantee--<?php echo esc_attr($layout); ?>">
            <div class="nv-pw-guarantee__main">
                <?php
                $main_img = (($s['icon_type'] ?? 'icon') === 'image' && !empty($s['main_image']['url'])) ? (string) $s['main_image']['url'] : '';
                ?>
                <span class="nv-pw-guarantee__icon<?php echo $main_img !== '' ? ' nv-pw-guarantee__icon--image' : ''; ?>" aria-hidden="true">
                    <?php
                    if ($main_img !== '') {
                        echo '<img src="' . esc_url($main_img) . '" alt="' . esc_attr((string) ($s['main_image']['alt'] ?? '')) . '" loading="lazy">';
                    } elseif (($s['icon_type'] ?? 'icon') !== 'image' && !empty($s['icon']['value'])) {
                        \Elementor\Icons_Manager::render_icon($s['icon'], ['aria-hidden' => 'true']);
                    }
                    ?>
                </span>
                <div class="nv-pw-guarantee__body">
                    <?php if ($heading !== '') : ?><h3 class="nv-pw-guarantee__heading"><?php echo esc_html($heading); ?></h3><?php endif; ?>
                    <?php if ($text !== '') : ?><p class="nv-pw-guarantee__text"><?php echo esc_html($text); ?></p><?php endif; ?>
                </div>
            </div>
            <?php if (!empty($badges)) : ?>
                <ul class="nv-pw-guarantee__badges">
                    <?php foreach ($badges as $b) :
                        if (!is_array($b)) continue;
                        $label = trim((string) ($b['label'] ?? ''));
                        if ($label === '') continue;
                        ?>
                        <?php
                        $badge_img = (($b['badge_marker_type'] ?? 'icon') === 'image' && !empty($b['badge_image']['url'])) ? (string) $b['badge_image']['url'] : '';
                        ?>
                        <li class="nv-pw-guarantee__badge">
                            <span class="nv-pw-guarantee__badge-icon<?php echo $badge_img !== '' ? ' nv-pw-guarantee__badge-icon--image' : ''; ?>" aria-hidden="true">
                                <?php
                                if ($badge_img !== '') {
                                    echo '<img src="' . esc_url($badge_img) . '" alt="' . esc_attr((string) ($b['badge_image']['alt'] ?? '')) . '" loading="lazy">';
                                } elseif (($b['badge_marker_type'] ?? 'icon') !== 'image' && !empty($b['badge_icon']['value'])) {
                                    \Elementor\Icons_Manager::render_icon($b['badge_icon'], ['aria-hidden' => 'true']);
                                }
                                ?>
                            </span>
                            <span class="nv-pw-guarantee__badge-label"><?php echo esc_html($label); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
    }
}
