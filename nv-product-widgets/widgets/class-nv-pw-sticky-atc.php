<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Sticky_ATC extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-sticky-atc'; }
    public function get_title(): string { return 'NV: Sticky Add To Cart'; }
    public function get_icon(): string { return 'eicon-product-add-to-cart'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['sticky', 'add to cart', 'buy bar', 'floating', 'atc', 'köp', 'varukorg']; }
    public function get_script_depends(): array { return ['nv-sticky-atc']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('position', [
            'label' => __('Position', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'bottom',
            'options' => [
                'bottom' => __('Bottom', 'nv-product-widgets'),
                'top' => __('Top', 'nv-product-widgets'),
            ],
        ]);
        $this->add_control('show_after', [
            'label' => __('Appear after scrolling (px)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 100, 'max' => 2000]],
            'default' => ['size' => 600, 'unit' => 'px'],
        ]);
        $this->add_control('show_thumbnail', [
            'label' => __('Show thumbnail', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        $this->add_control('show_price', [
            'label' => __('Show price', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        $this->add_control('button_text', [
            'label' => __('Button text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Lägg i varukorg', 'nv-product-widgets'),
        ]);
        $this->add_control('variant_mode', [
            'label' => __('Variable products', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'inbar',
            'options' => [
                'inbar'  => __('Show variant dropdown in the bar', 'nv-product-widgets'),
                'scroll' => __('Scroll to the main variation selector', 'nv-product-widgets'),
            ],
            'description' => __('Only affects variable products. Simple products always add directly.', 'nv-product-widgets'),
        ]);
        $this->add_control('variant_placeholder', [
            'label' => __('Variant dropdown placeholder', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('välj storlek', 'nv-product-widgets'),
            'condition' => ['variant_mode' => 'inbar'],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('bar_bg', [
            'label' => __('Bar background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#FFFFFF',
            'selectors' => ['{{WRAPPER}} .nv-pw-satc' => '--nv-satc-bg: {{VALUE}};'],
        ]);
        $this->add_control('title_color', [
            'label' => __('Title color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#14161D',
            'selectors' => ['{{WRAPPER}} .nv-pw-satc' => '--nv-satc-title: {{VALUE}};'],
        ]);
        $this->add_control('price_color', [
            'label' => __('Price color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#312E81',
            'selectors' => ['{{WRAPPER}} .nv-pw-satc' => '--nv-satc-price: {{VALUE}};'],
        ]);
        $this->add_control('button_bg', [
            'label' => __('Button background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#312E81',
            'selectors' => ['{{WRAPPER}} .nv-pw-satc' => '--nv-satc-btn-bg: {{VALUE}};'],
        ]);
        $this->add_control('button_color', [
            'label' => __('Button text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#FFFFFF',
            'selectors' => ['{{WRAPPER}} .nv-pw-satc' => '--nv-satc-btn-color: {{VALUE}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $product = NV_PW_Editor_Helper::get_preview_product();
        if (!$product instanceof WC_Product) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Sticky Add To Cart', '🛒');
            return;
        }
        $s = $this->get_settings_for_display();
        $position = ($s['position'] ?? 'bottom') === 'top' ? 'top' : 'bottom';
        $show_after = isset($s['show_after']['size']) ? max(0, (int) $s['show_after']['size']) : 600;
        $show_thumb = (($s['show_thumbnail'] ?? 'yes') === 'yes');
        $show_price = (($s['show_price'] ?? 'yes') === 'yes');
        $button_text = trim((string) ($s['button_text'] ?? '')) ?: __('Lägg i varukorg', 'nv-product-widgets');

        $is_variable = $product->is_type('variable');
        $variant_mode = ($s['variant_mode'] ?? 'inbar') === 'scroll' ? 'scroll' : 'inbar';
        $placeholder = trim((string) ($s['variant_placeholder'] ?? 'välj')) ?: 'välj';
        $inbar = $is_variable && $variant_mode === 'inbar';
        $variations = $inbar ? $this->get_variations($product) : [];
        $inbar = $inbar && !empty($variations);

        $can_direct = ($product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock());
        $add_url = $can_direct ? $product->add_to_cart_url() : '';
        $thumb = $show_thumb ? $product->get_image('woocommerce_gallery_thumbnail') : '';
        $product_id = $product->get_id();
        ?>
        <div class="nv-pw-satc nv-pw-satc--<?php echo esc_attr($position); ?><?php echo $inbar ? ' nv-pw-satc--inbar' : ''; ?>" data-nv-satc data-show-after="<?php echo esc_attr((string) $show_after); ?>" data-variable="<?php echo $is_variable ? '1' : '0'; ?>" data-product-id="<?php echo esc_attr((string) $product_id); ?>" hidden>
            <div class="nv-pw-satc__inner">
                <?php if ($show_thumb && $thumb) : ?>
                    <div class="nv-pw-satc__thumb"><?php echo $thumb; ?></div>
                <?php endif; ?>
                <div class="nv-pw-satc__info">
                    <span class="nv-pw-satc__title"><?php echo esc_html($product->get_name()); ?></span>
                    <?php if ($show_price) : ?>
                        <span class="nv-pw-satc__price"><?php echo wp_kses_post($product->get_price_html()); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($inbar) : ?>
                    <select class="nv-pw-satc__select" data-nv-satc-variant aria-label="<?php echo esc_attr($placeholder); ?>">
                        <option value=""><?php echo esc_html($placeholder); ?></option>
                        <?php foreach ($variations as $vr) : ?>
                            <option value="<?php echo esc_attr((string) $vr['id']); ?>"><?php echo esc_html($vr['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <?php if ($add_url !== '') : ?>
                    <a class="nv-pw-satc__btn" href="<?php echo esc_url($add_url); ?>" data-nv-satc-add rel="nofollow"><?php echo esc_html($button_text); ?></a>
                <?php elseif ($inbar) : ?>
                    <button type="button" class="nv-pw-satc__btn" data-nv-satc-inbar><?php echo esc_html($button_text); ?></button>
                <?php else : ?>
                    <button type="button" class="nv-pw-satc__btn" data-nv-satc-scroll><?php echo esc_html($button_text); ?></button>
                <?php endif; ?>
            </div>
            <p class="nv-pw-satc__msg" data-nv-satc-msg role="status"></p>
        </div>
        <?php
    }

    /** Purchasable variations for the in-bar dropdown (id + short label). */
    private function get_variations(\WC_Product $product): array {
        $out = [];
        if (!$product->is_type('variable')) return $out;
        foreach ($product->get_available_variations() as $v) {
            $vid = (int) ($v['variation_id'] ?? 0);
            if ($vid <= 0 || empty($v['is_purchasable']) || empty($v['is_in_stock'])) continue;
            $parts = [];
            foreach ((array) ($v['attributes'] ?? []) as $val) {
                $val = trim((string) $val);
                if ($val !== '') $parts[] = ucfirst($val);
            }
            $out[] = ['id' => $vid, 'label' => $parts ? implode(' / ', $parts) : ('#' . $vid)];
        }
        return $out;
    }
}
