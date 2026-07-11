<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Shoppable_Video extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-shoppable-video'; }
    public function get_title(): string { return 'NV: Shoppable Video'; }
    public function get_icon(): string { return 'eicon-play-o'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['shoppable', 'video', 'product', 'tags', 'ugc', 'add to cart']; }
    public function get_script_depends(): array { return ['nv-video', 'nv-shoppable']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_video', ['label' => __('Video', 'nv-product-widgets')]);
        $this->add_control('source', [
            'label' => __('Source', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'file',
            'options' => ['file' => __('Uploaded file (MP4)', 'nv-product-widgets'), 'youtube' => __('YouTube', 'nv-product-widgets'), 'vimeo' => __('Vimeo', 'nv-product-widgets')],
        ]);
        $this->add_control('file', ['label' => __('Video file (MP4)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::MEDIA, 'media_types' => ['video'], 'condition' => ['source' => 'file']]);
        $this->add_control('fallback_url', ['label' => __('Fallback embed URL (used if no file)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'condition' => ['source' => 'file'], 'label_block' => true]);
        $this->add_control('url', ['label' => __('YouTube / Vimeo URL or ID', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'condition' => ['source!' => 'file'], 'label_block' => true]);
        $this->add_control('poster', ['label' => __('Poster image', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::MEDIA]);
        $this->add_control('aspect', ['label' => __('Video shape', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '9-16', 'options' => ['9-16' => __('Vertical 9:16', 'nv-product-widgets'), '1-1' => __('Square 1:1', 'nv-product-widgets'), '16-9' => __('Wide 16:9', 'nv-product-widgets')]]);
        $this->add_control('heading', ['label' => __('Heading (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Shoppa looken', 'nv-product-widgets')]);
        $this->add_control('hotspots', ['label' => __('Show hotspots on video', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->end_controls_section();

        $this->start_controls_section('section_products', ['label' => __('Products', 'nv-product-widgets')]);
        $r = new \Elementor\Repeater();
        $r->add_control('product_id', ['label' => __('WooCommerce product ID (recommended)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'description' => __('Leave empty to use the manual fields below.', 'nv-product-widgets')]);
        $r->add_control('image', ['label' => __('Image (manual)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::MEDIA]);
        $r->add_control('title', ['label' => __('Title (manual)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Produktnamn', 'nv-product-widgets')]);
        $r->add_control('price', ['label' => __('Price (manual)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('299 kr', 'nv-product-widgets')]);
        $r->add_control('link', ['label' => __('Link (manual)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::URL, 'default' => ['url' => '']]);
        $r->add_control('hx', ['label' => __('Hotspot X (%)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'max' => 100, 'default' => 50]);
        $r->add_control('hy', ['label' => __('Hotspot Y (%)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'max' => 100, 'default' => 50]);
        $this->add_control('items', [
            'label' => __('Tagged products', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ title }}}',
            'default' => [['title' => __('Produkt 1', 'nv-product-widgets'), 'price' => '299 kr', 'hx' => 30, 'hy' => 40], ['title' => __('Produkt 2', 'nv-product-widgets'), 'price' => '199 kr', 'hx' => 65, 'hy' => 60]],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('accent', ['label' => __('Accent color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#312E81', 'selectors' => ['{{WRAPPER}} .nv-pw-sv' => '--nv-sv-accent: {{VALUE}};']]);
        $this->add_control('atc_label', ['label' => __('Add-to-cart label', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Lägg i varukorg', 'nv-product-widgets')]);
        $this->end_controls_section();
    }

    private function resolve(array $r): ?array {
        $pid = (int) ($r['product_id'] ?? 0);
        $title = trim((string) ($r['title'] ?? ''));
        $price = trim((string) ($r['price'] ?? ''));
        $image = isset($r['image']['url']) ? (string) $r['image']['url'] : '';
        $link = isset($r['link']['url']) ? (string) $r['link']['url'] : '';
        $atc_id = 0; $is_simple = false;

        if ($pid > 0 && function_exists('wc_get_product')) {
            $p = wc_get_product($pid);
            if ($p instanceof \WC_Product) {
                if ($title === '') $title = $p->get_name();
                if ($price === '') $price = wp_strip_all_tags($p->get_price_html());
                if ($image === '') {
                    $img = $p->get_image_id();
                    if ($img) { $src = wp_get_attachment_image_url($img, 'medium'); if ($src) $image = $src; }
                }
                if ($link === '') $link = $p->get_permalink();
                if ($p->is_type('simple') && $p->is_purchasable() && $p->is_in_stock()) { $atc_id = $pid; $is_simple = true; }
            }
        }
        if ($title === '' && $image === '') return null;
        return ['title' => $title, 'price' => $price, 'image' => $image, 'link' => $link, 'atc_id' => $atc_id, 'is_simple' => $is_simple,
            'hx' => max(0, min(100, (int) ($r['hx'] ?? 50))), 'hy' => max(0, min(100, (int) ($r['hy'] ?? 50)))];
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $type = in_array(($s['source'] ?? 'file'), ['file', 'youtube', 'vimeo'], true) ? (string) $s['source'] : 'file';
        $poster = isset($s['poster']['url']) ? (string) $s['poster']['url'] : '';
        $embed = ''; $file = '';
        if ($type === 'file') {
            $file = isset($s['file']['url']) ? (string) $s['file']['url'] : '';
            if ($file === '') { $fb = NV_PW_Video_Slider::auto_embed((string) ($s['fallback_url'] ?? '')); if ($fb !== '') { $embed = $fb; $type = 'embed'; } }
        } else {
            $embed = NV_PW_Video_Slider::build_embed($type, (string) ($s['url'] ?? ''));
            if ($poster === '' && $type === 'youtube') $poster = NV_PW_Video_Slider::youtube_thumb((string) ($s['url'] ?? ''));
        }

        $products = [];
        foreach (($s['items'] ?? []) as $r) {
            if (!is_array($r)) continue;
            $p = $this->resolve($r);
            if ($p) $products[] = $p;
        }

        if (($embed === '' && $file === '') || empty($products)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Shoppable Video — add a video and products', '🛍️');
            return;
        }

        $heading = trim((string) ($s['heading'] ?? ''));
        $aspect = in_array(($s['aspect'] ?? '9-16'), ['9-16', '1-1', '16-9'], true) ? (string) $s['aspect'] : '9-16';
        $show_hot = (($s['hotspots'] ?? 'yes') === 'yes');
        $atc_label = trim((string) ($s['atc_label'] ?? 'Lägg i varukorg')) ?: 'Lägg i varukorg';
        ?>
        <div class="nv-pw-sv" data-nv-video data-nv-shoppable>
            <?php if ($heading !== '') : ?><h3 class="nv-pw-sv__heading"><?php echo esc_html($heading); ?></h3><?php endif; ?>
            <div class="nv-pw-sv__layout">
                <div class="nv-pw-sv__videowrap">
                    <div class="nv-pw-video nv-pw-ar nv-pw-ar--<?php echo esc_attr($aspect); ?>" data-type="<?php echo esc_attr($type); ?>" <?php if ($embed !== '') : ?>data-embed="<?php echo esc_url($embed); ?>"<?php endif; ?> <?php if ($file !== '') : ?>data-file="<?php echo esc_url($file); ?>"<?php endif; ?>>
                        <?php if ($poster !== '') : ?><img class="nv-pw-video__poster" src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($heading); ?>" loading="lazy"><?php else : ?><span class="nv-pw-video__poster nv-pw-video__poster--blank" aria-hidden="true"></span><?php endif; ?>
                        <button type="button" class="nv-pw-video__play" aria-label="<?php echo esc_attr__('Spela upp video', 'nv-product-widgets'); ?>"><svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg></button>
                        <?php if ($show_hot) : foreach ($products as $i => $p) : ?>
                            <button type="button" class="nv-pw-sv__hotspot" style="left:<?php echo (int) $p['hx']; ?>%;top:<?php echo (int) $p['hy']; ?>%;" data-nv-hotspot="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr($p['title']); ?>"><span></span></button>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
                <div class="nv-pw-sv__rail" data-nv-rail>
                    <?php foreach ($products as $i => $p) :
                        $atc_url = $p['atc_id'] > 0 ? esc_url('?add-to-cart=' . $p['atc_id']) : '';
                        ?>
                        <div class="nv-pw-sv__card" data-nv-card="<?php echo (int) $i; ?>">
                            <?php if ($p['image'] !== '') : ?><div class="nv-pw-sv__img"><img src="<?php echo esc_url($p['image']); ?>" alt="<?php echo esc_attr($p['title']); ?>" loading="lazy"></div><?php endif; ?>
                            <div class="nv-pw-sv__info">
                                <?php if ($p['link'] !== '') : ?><a class="nv-pw-sv__title" href="<?php echo esc_url($p['link']); ?>"><?php echo esc_html($p['title']); ?></a><?php else : ?><span class="nv-pw-sv__title"><?php echo esc_html($p['title']); ?></span><?php endif; ?>
                                <?php if ($p['price'] !== '') : ?><span class="nv-pw-sv__price"><?php echo esc_html($p['price']); ?></span><?php endif; ?>
                                <?php if ($p['is_simple'] && $atc_url !== '') : ?>
                                    <a href="<?php echo $atc_url; // already escaped ?>" data-product_id="<?php echo (int) $p['atc_id']; ?>" data-quantity="1" rel="nofollow" class="nv-pw-sv__atc add_to_cart_button ajax_add_to_cart"><?php echo esc_html($atc_label); ?></a>
                                <?php elseif ($p['link'] !== '') : ?>
                                    <a href="<?php echo esc_url($p['link']); ?>" class="nv-pw-sv__atc"><?php echo esc_html($atc_label); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
