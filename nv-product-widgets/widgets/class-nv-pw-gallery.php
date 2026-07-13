<?php
if (!defined('ABSPATH')) exit;

/**
 * NV: Gallery / Lookbook — a general-purpose image grid (grid or masonry) with an
 * optional lightbox, per-image captions and optional links. Distinct from the
 * product gallery: for lifestyle / lookbook rows on any page.
 */
class NV_PW_Gallery extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-gallery'; }
    public function get_title(): string { return 'NV: Gallery / Lookbook'; }
    public function get_icon(): string { return 'eicon-gallery-grid'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['gallery', 'lookbook', 'grid', 'masonry', 'lightbox', 'images', 'bilder']; }
    public function get_script_depends(): array { return ['nv-gallery']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('heading', ['label' => __('Heading (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '', 'label_block' => true]);
        $this->add_control('subheading', ['label' => __('Subheading (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => '']);
        $r = new \Elementor\Repeater();
        $r->add_control('image', ['label' => __('Image', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::MEDIA, 'default' => ['url' => \Elementor\Utils::get_placeholder_image_src()]]);
        $r->add_control('caption', ['label' => __('Caption (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $r->add_control('link', ['label' => __('Link (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::URL, 'default' => ['url' => ''], 'description' => __('If set, the image links out instead of opening the lightbox.', 'nv-product-widgets')]);
        $this->add_control('images', [
            'label' => __('Images', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ caption || "Image" }}}',
            'default' => array_fill(0, 6, ['image' => ['url' => \Elementor\Utils::get_placeholder_image_src()]]),
        ]);
        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        $this->start_controls_section('section_layout', ['label' => __('Layout', 'nv-product-widgets')]);
        $this->add_control('layout', ['label' => __('Layout', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'grid', 'options' => ['grid' => __('Grid (equal tiles)', 'nv-product-widgets'), 'masonry' => __('Masonry (natural heights)', 'nv-product-widgets')]]);
        $this->add_responsive_control('columns', ['label' => __('Columns', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 3, 'tablet_default' => 2, 'mobile_default' => 1, 'min' => 1, 'max' => 6, 'selectors' => ['{{WRAPPER}} .nv-pw-gal' => '--nv-gal-cols: {{VALUE}};']]);
        $this->add_control('ratio', ['label' => __('Tile ratio (grid only)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '1/1', 'options' => ['1/1' => __('Square', 'nv-product-widgets'), '4/5' => __('Portrait 4:5', 'nv-product-widgets'), '3/4' => __('Portrait 3:4', 'nv-product-widgets'), '16/9' => __('Landscape 16:9', 'nv-product-widgets')], 'condition' => ['layout' => 'grid'], 'selectors' => ['{{WRAPPER}} .nv-pw-gal--grid .nv-pw-gal__media' => 'aspect-ratio: {{VALUE}};']]);
        $this->add_responsive_control('gap', ['label' => __('Gap', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 40]], 'default' => ['size' => 12, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-gal' => '--nv-gal-gap: {{SIZE}}px;']]);
        $this->add_control('lightbox', ['label' => __('Enable lightbox', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('align', ['label' => __('Heading alignment', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::CHOOSE, 'options' => ['left' => ['title' => __('Left', 'nv-product-widgets'), 'icon' => 'eicon-text-align-left'], 'center' => ['title' => __('Center', 'nv-product-widgets'), 'icon' => 'eicon-text-align-center']], 'default' => 'center', 'selectors' => ['{{WRAPPER}} .nv-pw-gal__head' => 'text-align: {{VALUE}};']]);
        $this->add_control('radius', ['label' => __('Corner radius', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 32]], 'default' => ['size' => 12, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-gal' => '--nv-gal-radius: {{SIZE}}px;']]);
        $this->add_control('heading_color', ['label' => __('Heading color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-gal' => '--nv-gal-heading: {{VALUE}};']]);
        $this->add_control('caption_color', ['label' => __('Caption color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-gal' => '--nv-gal-caption: {{VALUE}};']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $rows = is_array($s['images'] ?? null) ? $s['images'] : [];
        $images = [];
        foreach ($rows as $it) {
            if (is_array($it) && !empty($it['image']['url'])) $images[] = $it;
        }
        if (empty($images)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Gallery — add images', '🖼️');
            return;
        }

        $layout = ($s['layout'] ?? 'grid') === 'masonry' ? 'masonry' : 'grid';
        $lightbox = (($s['lightbox'] ?? 'yes') === 'yes');
        $heading = trim((string) ($s['heading'] ?? ''));
        $sub = trim((string) ($s['subheading'] ?? ''));
        ?>
        <div class="nv-pw-gal nv-pw-gal--<?php echo esc_attr($layout); ?><?php echo $lightbox ? ' nv-pw-gal--lightbox' : ''; ?>"<?php echo $lightbox ? ' data-nv-gallery' : ''; ?>>
            <?php if ($heading !== '' || $sub !== '') : ?>
                <div class="nv-pw-gal__head">
                    <?php if ($heading !== '') : ?><h2 class="nv-pw-gal__heading<?php echo NV_PW_Headline::mod($s); ?>"><?php echo NV_PW_Headline::html($heading); ?></h2><?php endif; ?>
                    <?php if ($sub !== '') : ?><p class="nv-pw-gal__sub"><?php echo esc_html($sub); ?></p><?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="nv-pw-gal__grid">
                <?php foreach ($images as $i => $it) :
                    $url = (string) $it['image']['url'];
                    $caption = trim((string) ($it['caption'] ?? ''));
                    $link = isset($it['link']['url']) ? (string) $it['link']['url'] : '';
                    $target = !empty($it['link']['is_external']) ? ' target="_blank" rel="noopener"' : '';
                    $tag = $link !== '' ? 'a' : (($lightbox) ? 'button' : 'span');
                    $attr = $link !== '' ? ' href="' . esc_url($link) . '"' . $target : ($lightbox ? ' type="button" data-nv-gallery-item="' . (int) $i . '"' : '');
                    ?>
                    <figure class="nv-pw-gal__fig">
                        <<?php echo $tag; ?> class="nv-pw-gal__media"<?php echo $attr; // phpcs:ignore ?><?php echo ($lightbox && $link === '') ? ' aria-label="' . esc_attr($caption !== '' ? $caption : __('View image', 'nv-product-widgets')) . '"' : ''; ?>>
                            <img class="nv-pw-gal__img" src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr($caption); ?>" loading="lazy" data-nv-gallery-src="<?php echo esc_url($url); ?>" data-nv-gallery-cap="<?php echo esc_attr($caption); ?>">
                            <?php if ($lightbox && $link === '') : ?><span class="nv-pw-gal__zoom" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3M11 8v6M8 11h6"/></svg></span><?php endif; ?>
                        </<?php echo $tag; ?>>
                        <?php if ($caption !== '') : ?><figcaption class="nv-pw-gal__caption"><?php echo esc_html($caption); ?></figcaption><?php endif; ?>
                    </figure>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
