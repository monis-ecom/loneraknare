<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Content_Slider extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-content-slider'; }
    public function get_title(): string { return 'NV: Content Slider'; }
    public function get_icon(): string { return 'eicon-slider-album'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['slider', 'carousel', 'gallery', 'cards', 'slideshow']; }
    public function get_script_depends(): array { return ['nv-slider']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('heading', ['label' => __('Heading (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('layout', [
            'label' => __('Layout', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'cards',
            'options' => ['images' => __('Images only', 'nv-product-widgets'), 'cards' => __('Image + text cards', 'nv-product-widgets'), 'full' => __('Full-width (text overlay)', 'nv-product-widgets')],
        ]);
        $this->add_control('per_view', [
            'label' => __('Cards per view (desktop)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '3',
            'options' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4'],
            'condition' => ['layout!' => 'full'],
        ]);
        $this->add_control('arrows', ['label' => __('Arrows', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('dots', ['label' => __('Dots', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('autoplay', ['label' => __('Autoplay', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => '']);

        $r = new \Elementor\Repeater();
        $r->add_control('image', ['label' => __('Image', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::MEDIA, 'default' => ['url' => \Elementor\Utils::get_placeholder_image_src()]]);
        $r->add_control('heading', ['label' => __('Heading', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Rubrik', 'nv-product-widgets')]);
        $r->add_control('text', ['label' => __('Text', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('Kort beskrivande text för denna slide.', 'nv-product-widgets')]);
        $r->add_control('cta_text', ['label' => __('Button text', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $r->add_control('cta_link', ['label' => __('Button link', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::URL, 'default' => ['url' => '#'], 'condition' => ['cta_text!' => '']]);
        $this->add_control('items', [
            'label' => __('Slides', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ heading }}}',
            'default' => [
                ['heading' => __('Slide ett', 'nv-product-widgets'), 'text' => __('Kort beskrivande text.', 'nv-product-widgets')],
                ['heading' => __('Slide två', 'nv-product-widgets'), 'text' => __('Kort beskrivande text.', 'nv-product-widgets')],
                ['heading' => __('Slide tre', 'nv-product-widgets'), 'text' => __('Kort beskrivande text.', 'nv-product-widgets')],
            ],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('accent', ['label' => __('Accent color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#312E81', 'selectors' => ['{{WRAPPER}} .nv-pw-cs' => '--nv-cs-accent: {{VALUE}};']]);
        $this->add_control('radius', ['label' => __('Radius', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 32]], 'default' => ['size' => 14, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-cs__slide, {{WRAPPER}} .nv-pw-cs__slide img' => 'border-radius: {{SIZE}}{{UNIT}};']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $rows = is_array($s['items'] ?? null) ? $s['items'] : [];
        $slides = [];
        foreach ($rows as $r) {
            if (!is_array($r)) continue;
            $img = isset($r['image']['url']) ? (string) $r['image']['url'] : '';
            $h = trim((string) ($r['heading'] ?? ''));
            if ($img === '' && $h === '') continue;
            $slides[] = $r;
        }
        if (empty($slides)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Content Slider', '🎠');
            return;
        }
        $layout = in_array(($s['layout'] ?? 'cards'), ['images', 'cards', 'full'], true) ? (string) $s['layout'] : 'cards';
        $pv = in_array(($s['per_view'] ?? '3'), ['1', '2', '3', '4'], true) ? (string) $s['per_view'] : '3';
        if ($layout === 'full') $pv = '1';
        $heading = trim((string) ($s['heading'] ?? ''));
        $arrows = (($s['arrows'] ?? 'yes') === 'yes');
        $dots = (($s['dots'] ?? 'yes') === 'yes');
        $autoplay = (($s['autoplay'] ?? '') === 'yes');
        ?>
        <div class="nv-pw-cs nv-pw-cs--<?php echo esc_attr($layout); ?>" data-nv-slider data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>" style="--nv-cs-pv: <?php echo esc_attr($pv); ?>;">
            <div class="nv-pw-cs__head">
                <?php if ($heading !== '') : ?><h3 class="nv-pw-cs__heading"><?php echo esc_html($heading); ?></h3><?php endif; ?>
                <?php if ($arrows) : ?>
                    <div class="nv-pw-cs__arrows">
                        <button type="button" class="nv-pw-cs__arrow" data-nv-prev aria-label="<?php echo esc_attr__('Föregående', 'nv-product-widgets'); ?>"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>
                        <button type="button" class="nv-pw-cs__arrow" data-nv-next aria-label="<?php echo esc_attr__('Nästa', 'nv-product-widgets'); ?>"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
                    </div>
                <?php endif; ?>
            </div>
            <div class="nv-pw-cs__track" data-nv-track>
                <?php foreach ($slides as $sl) :
                    $img = isset($sl['image']['url']) ? (string) $sl['image']['url'] : '';
                    $h = trim((string) ($sl['heading'] ?? ''));
                    $t = trim((string) ($sl['text'] ?? ''));
                    $ct = trim((string) ($sl['cta_text'] ?? ''));
                    $cu = isset($sl['cta_link']['url']) ? (string) $sl['cta_link']['url'] : '';
                    ?>
                    <div class="nv-pw-cs__slide">
                        <?php if ($img !== '') : ?><div class="nv-pw-cs__img"><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($h); ?>" loading="lazy"></div><?php endif; ?>
                        <?php if ($layout !== 'images' && ($h !== '' || $t !== '' || $ct !== '')) : ?>
                            <div class="nv-pw-cs__body">
                                <?php if ($h !== '') : ?><strong class="nv-pw-cs__title"><?php echo esc_html($h); ?></strong><?php endif; ?>
                                <?php if ($t !== '') : ?><p class="nv-pw-cs__text"><?php echo esc_html($t); ?></p><?php endif; ?>
                                <?php if ($ct !== '') : ?><a class="nv-pw-cs__btn" href="<?php echo esc_url($cu !== '' ? $cu : '#'); ?>"><?php echo esc_html($ct); ?></a><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($dots) : ?><div class="nv-pw-cs__dots" data-nv-dots></div><?php endif; ?>
        </div>
        <?php
    }
}
