<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Hotspots extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-hotspots'; }
    public function get_title(): string { return 'NV: Hotspots'; }
    public function get_icon(): string { return 'eicon-image-hotspot'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['hotspot', 'hotspots', 'pins', 'markers', 'interactive', 'image', 'features', 'annotate', 'punkter']; }
    public function get_script_depends(): array { return ['nv-hotspots']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);

        $this->add_control('heading', [
            'label' => __('Heading (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);
        $this->add_control('image', [
            'label' => __('Image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => ['url' => \Elementor\Utils::get_placeholder_image_src()],
        ]);
        $this->add_control('trigger', [
            'label' => __('Open on', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'click',
            'options' => [
                'click' => __('Click / tap', 'nv-product-widgets'),
                'hover' => __('Hover', 'nv-product-widgets'),
            ],
        ]);

        $r = new \Elementor\Repeater();
        $r->add_control('title', [
            'label' => __('Title', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Funktion', 'nv-product-widgets'),
        ]);
        $r->add_control('text', [
            'label' => __('Description', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Beskriv den här funktionen kort.', 'nv-product-widgets'),
        ]);
        $r->add_control('x', [
            'label' => __('Horizontal position (%)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['%' => ['min' => 0, 'max' => 100]],
            'default' => ['size' => 50, 'unit' => '%'],
        ]);
        $r->add_control('y', [
            'label' => __('Vertical position (%)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['%' => ['min' => 0, 'max' => 100]],
            'default' => ['size' => 50, 'unit' => '%'],
        ]);
        $this->add_control('points', [
            'label' => __('Hotspots', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ title }}}',
            'default' => [
                ['title' => __('Funktion 1', 'nv-product-widgets'), 'text' => __('Beskriv den här funktionen kort.', 'nv-product-widgets'), 'x' => ['size' => 30, 'unit' => '%'], 'y' => ['size' => 32, 'unit' => '%']],
                ['title' => __('Funktion 2', 'nv-product-widgets'), 'text' => __('Beskriv den här funktionen kort.', 'nv-product-widgets'), 'x' => ['size' => 64, 'unit' => '%'], 'y' => ['size' => 46, 'unit' => '%']],
                ['title' => __('Funktion 3', 'nv-product-widgets'), 'text' => __('Beskriv den här funktionen kort.', 'nv-product-widgets'), 'x' => ['size' => 46, 'unit' => '%'], 'y' => ['size' => 72, 'unit' => '%']],
            ],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('max_width', [
            'label' => __('Max width', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 240, 'max' => 1200]],
            'default' => ['size' => 760, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-hs' => 'max-width: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_control('radius', [
            'label' => __('Image radius', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 0, 'max' => 48]],
            'default' => ['size' => 16, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-hs__figure' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_control('pin_color', [
            'label' => __('Pin color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#111318',
            'selectors' => ['{{WRAPPER}} .nv-pw-hs' => '--nv-hs-pin-bg: {{VALUE}};'],
        ]);
        $this->add_control('pin_icon_color', [
            'label' => __('Pin icon color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#FFFFFF',
            'selectors' => ['{{WRAPPER}} .nv-pw-hs' => '--nv-hs-pin-color: {{VALUE}};'],
        ]);
        $this->add_control('pin_size', [
            'label' => __('Pin size', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 22, 'max' => 60]],
            'default' => ['size' => 38, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-hs' => '--nv-hs-pin-size: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_control('pin_opacity', [
            'label' => __('Pin transparency', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['%' => ['min' => 20, 'max' => 100]],
            'default' => ['size' => 100, 'unit' => '%'],
            'selectors' => ['{{WRAPPER}} .nv-pw-hs__pin' => 'opacity: calc({{SIZE}} / 100);'],
        ]);
        $this->add_control('pin_shape', [
            'label' => __('Pin shape', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'circle',
            'options' => [
                'circle'  => __('Circle', 'nv-product-widgets'),
                'rounded' => __('Rounded square', 'nv-product-widgets'),
                'square'  => __('Square', 'nv-product-widgets'),
            ],
        ]);
        $this->add_control('tip_bg', [
            'label' => __('Tooltip background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#FFFFFF',
            'selectors' => ['{{WRAPPER}} .nv-pw-hs' => '--nv-hs-tip-bg: {{VALUE}};'],
        ]);
        $this->add_control('tip_color', [
            'label' => __('Tooltip text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#14161D',
            'selectors' => ['{{WRAPPER}} .nv-pw-hs' => '--nv-hs-tip-color: {{VALUE}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $img = (string) ($s['image']['url'] ?? '');
        if ($img === '') {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Hotspots', '📍');
            return;
        }
        $points = [];
        foreach (($s['points'] ?? []) as $row) {
            if (!is_array($row)) continue;
            $title = trim((string) ($row['title'] ?? ''));
            $text = trim((string) ($row['text'] ?? ''));
            if ($title === '' && $text === '') continue;
            $x = isset($row['x']['size']) ? max(0, min(100, (float) $row['x']['size'])) : 50;
            $y = isset($row['y']['size']) ? max(0, min(100, (float) $row['y']['size'])) : 50;
            $points[] = ['title' => $title, 'text' => $text, 'x' => $x, 'y' => $y];
        }
        if (empty($points)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Hotspots (add at least one point)', '📍');
            return;
        }
        $heading = trim((string) ($s['heading'] ?? ''));
        $trigger = ($s['trigger'] ?? 'click') === 'hover' ? 'hover' : 'click';
        $shape = in_array(($s['pin_shape'] ?? 'circle'), ['circle', 'rounded', 'square'], true) ? (string) $s['pin_shape'] : 'circle';
        $img_alt = (string) ($s['image']['alt'] ?? '');
        $uid = 'nvhs-' . $this->get_id();
        ?>
        <div class="nv-pw-hs nv-pw-hs--<?php echo esc_attr($trigger); ?> nv-pw-hs--pin-<?php echo esc_attr($shape); ?>">
            <?php if ($heading !== '') : ?><h3 class="nv-pw-hs__heading"><?php echo esc_html($heading); ?></h3><?php endif; ?>
            <div class="nv-pw-hs__figure" data-nv-hotspots data-trigger="<?php echo esc_attr($trigger); ?>">
                <img class="nv-pw-hs__img" src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($img_alt); ?>" loading="lazy" draggable="false">
                <?php foreach ($points as $i => $p) :
                    $pid = $uid . '-p' . $i; ?>
                    <div class="nv-pw-hs__spot" style="left: <?php echo esc_attr((string) $p['x']); ?>%; top: <?php echo esc_attr((string) $p['y']); ?>%;">
                        <button type="button" class="nv-pw-hs__pin" aria-expanded="false" aria-controls="<?php echo esc_attr($pid); ?>" aria-label="<?php echo esc_attr($p['title'] !== '' ? $p['title'] : __('Hotspot', 'nv-product-widgets')); ?>">
                            <span class="nv-pw-hs__plus" aria-hidden="true"></span>
                        </button>
                        <div class="nv-pw-hs__tip" id="<?php echo esc_attr($pid); ?>" role="tooltip">
                            <?php if ($p['title'] !== '') : ?><strong class="nv-pw-hs__tip-title"><?php echo esc_html($p['title']); ?></strong><?php endif; ?>
                            <?php if ($p['text'] !== '') : ?><span class="nv-pw-hs__tip-text"><?php echo esc_html($p['text']); ?></span><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
