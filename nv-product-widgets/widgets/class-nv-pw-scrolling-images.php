<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Scrolling_Images extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-scrolling-images'; }
    public function get_title(): string { return 'NV: Scrolling Images'; }
    public function get_icon(): string { return 'eicon-slider-push'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['scrolling', 'marquee', 'images', 'gallery', 'carousel']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('heading', ['label' => __('Heading (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);

        $r = new \Elementor\Repeater();
        $r->add_control('image', ['label' => __('Image', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::MEDIA, 'default' => ['url' => \Elementor\Utils::get_placeholder_image_src()]]);
        $this->add_control('items', [
            'label' => __('Images', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'default' => [['image' => ['url' => \Elementor\Utils::get_placeholder_image_src()]], ['image' => ['url' => \Elementor\Utils::get_placeholder_image_src()]], ['image' => ['url' => \Elementor\Utils::get_placeholder_image_src()]], ['image' => ['url' => \Elementor\Utils::get_placeholder_image_src()]]],
        ]);
        $this->add_control('direction', ['label' => __('Direction', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'left', 'options' => ['left' => __('Left', 'nv-product-widgets'), 'right' => __('Right', 'nv-product-widgets')]]);
        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('img_h', ['label' => __('Image height', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 60, 'max' => 400]], 'default' => ['size' => 180, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-si__item' => 'height: {{SIZE}}{{UNIT}};']]);
        $this->add_control('gap', ['label' => __('Gap', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 60]], 'default' => ['size' => 16, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-si__track' => 'gap: {{SIZE}}{{UNIT}};']]);
        $this->add_control('radius', ['label' => __('Radius', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 40]], 'default' => ['size' => 14, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-si__item img' => 'border-radius: {{SIZE}}{{UNIT}};']]);
        $this->add_control('speed', ['label' => __('Speed (seconds)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 8, 'max' => 80]], 'default' => ['size' => 32, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-si__track' => '--nv-si-dur: {{SIZE}}s;']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $imgs = [];
        foreach (($s['items'] ?? []) as $r) {
            if (is_array($r) && !empty($r['image']['url'])) $imgs[] = (string) $r['image']['url'];
        }
        if (empty($imgs)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Scrolling Images', '🖼️');
            return;
        }
        $heading = trim((string) ($s['heading'] ?? ''));
        $dir = ($s['direction'] ?? 'left') === 'right' ? 'right' : 'left';
        $loop = array_merge($imgs, $imgs);
        ?>
        <div class="nv-pw-si nv-pw-si--<?php echo esc_attr($dir); ?>">
            <?php if ($heading !== '') : ?><h3 class="nv-pw-si__heading<?php echo NV_PW_Headline::mod($s); ?>"><?php echo esc_html($heading); ?></h3><?php endif; ?>
            <div class="nv-pw-si__mask">
                <div class="nv-pw-si__track">
                    <?php foreach ($loop as $u) : ?>
                        <div class="nv-pw-si__item"><img src="<?php echo esc_url($u); ?>" alt="" loading="lazy" aria-hidden="true"></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
