<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Feature extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-feature'; }
    public function get_title(): string { return 'NV: Feature / Image + Text'; }
    public function get_icon(): string { return 'eicon-image-box'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['feature', 'image text', 'media', 'split', 'usp']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);

        $this->add_control('layout', [
            'label' => __('Layout', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'split',
            'options' => [
                'split' => __('Split (image + text)', 'nv-product-widgets'),
                'icon-list' => __('Image + icon feature list', 'nv-product-widgets'),
                'centered' => __('Centered icon grid (no image)', 'nv-product-widgets'),
            ],
        ]);

        NV_PW_Media::add_controls($this, ['layout' => ['split', 'icon-list']]);

        $this->add_control('image', [
            'label' => __('Image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => ['url' => \Elementor\Utils::get_placeholder_image_src()],
            'condition' => ['nv_media_type' => 'image', 'layout' => ['split', 'icon-list']],
        ]);
        $this->add_control('image_side', [
            'label' => __('Image position', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'left',
            'options' => ['left' => __('Left', 'nv-product-widgets'), 'right' => __('Right', 'nv-product-widgets')],
        ]);
        $this->add_control('eyebrow', [
            'label' => __('Overline (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('FUNKTION', 'nv-product-widgets'),
        ]);
        $this->add_control('headline', [
            'label' => __('Headline', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Byggd för vardagen.', 'nv-product-widgets'),
            'label_block' => true,
        ]);
        $this->add_control('text', [
            'label' => __('Text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Slitstarka material och genomtänkt design som håller år efter år – utan att tumma på komforten.', 'nv-product-widgets'),
        ]);

        $bullets = new \Elementor\Repeater();
        $bullets->add_control('marker_type', [
            'label' => __('Marker', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'icon',
            'options' => [
                'icon' => __('Icon / checkmark', 'nv-product-widgets'),
                'image' => __('Image (.png / .gif / .jpeg / .avif)', 'nv-product-widgets'),
            ],
        ]);
        $bullets->add_control('icon', [
            'label' => __('Icon (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::ICONS,
            'condition' => ['marker_type' => 'icon'],
        ]);
        $bullets->add_control('image', [
            'label' => __('Image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'media_types' => ['image'],
            'condition' => ['marker_type' => 'image'],
        ]);
        $bullets->add_control('text', [
            'label' => __('Title', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Hållbart material', 'nv-product-widgets'),
        ]);
        $bullets->add_control('desc', [
            'label' => __('Description (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
        ]);
        $this->add_control('bullets', [
            'label' => __('Bullets (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $bullets->get_controls(),
            'title_field' => '{{{ text }}}',
            'default' => [
                ['text' => __('Hållbart material', 'nv-product-widgets')],
                ['text' => __('Lätt att rengöra', 'nv-product-widgets')],
            ],
        ]);
        $this->add_control('cta_text', [
            'label' => __('Button text (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);
        $this->add_control('cta_link', [
            'label' => __('Button link', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::URL,
            'default' => ['url' => '#'],
            'condition' => ['cta_text!' => ''],
        ]);

        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('accent', [
            'label' => __('Accent color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#312E81',
            'selectors' => ['{{WRAPPER}} .nv-pw-feat' => '--nv-feat-accent: {{VALUE}};'],
        ]);
        $this->add_control('heading_color', [
            'label' => __('Heading color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#14161D',
            'selectors' => ['{{WRAPPER}} .nv-pw-feat' => '--nv-feat-heading: {{VALUE}};'],
        ]);
        $this->add_control('text_color', [
            'label' => __('Text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#4B5563',
            'selectors' => ['{{WRAPPER}} .nv-pw-feat' => '--nv-feat-text: {{VALUE}};'],
        ]);
        $this->add_control('radius', [
            'label' => __('Image radius', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 0, 'max' => 40]],
            'default' => ['size' => 16, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-feat__media img, {{WRAPPER}} .nv-pw-feat__media video' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $headline = trim((string) ($s['headline'] ?? ''));
        $text = trim((string) ($s['text'] ?? ''));
        $is_video = NV_PW_Media::is_video($s);
        $img = isset($s['image']['url']) ? (string) $s['image']['url'] : '';
        $has_media = $is_video || $img !== '';
        if ($headline === '' && $text === '' && !$has_media) {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Feature / Image + Text', '🖼️');
            }
            return;
        }
        $side = ($s['image_side'] ?? 'left') === 'right' ? 'right' : 'left';
        $layout = in_array(($s['layout'] ?? 'split'), ['split', 'icon-list', 'centered'], true) ? (string) $s['layout'] : 'split';
        $eyebrow = trim((string) ($s['eyebrow'] ?? ''));
        $brows = [];
        foreach (($s['bullets'] ?? []) as $b) {
            if (is_array($b) && trim((string) ($b['text'] ?? '')) !== '') $brows[] = $b;
        }
        $cta_text = trim((string) ($s['cta_text'] ?? ''));
        $cta_url = isset($s['cta_link']['url']) ? (string) $s['cta_link']['url'] : '';
        $cta_target = !empty($s['cta_link']['is_external']) ? ' target="_blank" rel="noopener"' : '';
        ?>
        <div class="nv-pw-feat nv-pw-feat--L-<?php echo esc_attr($layout); ?> nv-pw-feat--img-<?php echo esc_attr($side); ?>">
            <?php if ($has_media && $layout !== 'centered') : ?>
                <div class="nv-pw-feat__media"><?php if ($is_video) { NV_PW_Media::render_video($s); } else { ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($headline); ?>" loading="lazy"><?php } ?></div>
            <?php endif; ?>
            <div class="nv-pw-feat__body">
                <?php if ($eyebrow !== '') : ?><span class="nv-pw-feat__eyebrow"><?php echo esc_html($eyebrow); ?></span><?php endif; ?>
                <?php if ($headline !== '') : ?><h3 class="nv-pw-feat__headline<?php echo NV_PW_Headline::mod($s); ?>"><?php echo NV_PW_Headline::html($headline); ?></h3><?php endif; ?>
                <?php if ($text !== '') : ?><p class="nv-pw-feat__text"><?php echo esc_html($text); ?></p><?php endif; ?>
                <?php if (!empty($brows)) : ?>
                    <ul class="nv-pw-feat__bullets">
                        <?php foreach ($brows as $b) :
                            $bt = trim((string) ($b['text'] ?? ''));
                            if ($bt === '') continue;
                            $bdesc = trim((string) ($b['desc'] ?? ''));
                            $bmarker = (string) ($b['marker_type'] ?? 'icon');
                            $bimg = ($bmarker === 'image' && !empty($b['image']['url'])) ? (string) $b['image']['url'] : '';
                            $bimg_alt = trim((string) ($b['image']['alt'] ?? ''));
                            $bicon = ($bmarker === 'icon') && !empty($b['icon']['value']);
                            ?>
                            <li class="nv-pw-feat__bullet">
                                <span class="nv-pw-feat__bicon<?php echo $bimg !== '' ? ' nv-pw-feat__bicon--image' : ''; ?>" aria-hidden="true">
                                    <?php if ($bimg !== '') { ?><img src="<?php echo esc_url($bimg); ?>" alt="<?php echo esc_attr($bimg_alt); ?>" loading="lazy"><?php } elseif ($bicon) { \Elementor\Icons_Manager::render_icon($b['icon'], ['aria-hidden' => 'true']); } else { ?><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><?php } ?>
                                </span>
                                <span class="nv-pw-feat__btext">
                                    <strong class="nv-pw-feat__btitle"><?php echo esc_html($bt); ?></strong>
                                    <?php if ($bdesc !== '') : ?><span class="nv-pw-feat__bdesc"><?php echo esc_html($bdesc); ?></span><?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($cta_text !== '') : ?>
                    <a class="nv-pw-feat__btn" href="<?php echo esc_url($cta_url !== '' ? $cta_url : '#'); ?>"<?php echo $cta_target; ?>><?php echo esc_html($cta_text); ?></a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
