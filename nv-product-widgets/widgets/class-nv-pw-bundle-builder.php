<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Bundle_Builder extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-bundle-builder'; }
    public function get_title(): string { return 'NV: Bundle Builder'; }
    public function get_icon(): string { return 'eicon-product-add-to-cart'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['bundle', 'build your own', 'mix', 'kit', 'paket', 'add to cart']; }
    public function get_script_depends(): array { return ['nv-bundle-builder']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_head', ['label' => __('Heading', 'nv-product-widgets')]);
        $this->add_control('eyebrow', ['label' => __('Overline', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('BYGG DITT EGET PAKET', 'nv-product-widgets')]);
        $this->add_control('heading', ['label' => __('Heading', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Välj dina favoriter och spara mer', 'nv-product-widgets'), 'label_block' => true]);
        $this->end_controls_section();

        $this->start_controls_section('section_tiers', ['label' => __('Tiers', 'nv-product-widgets')]);
        $t = new \Elementor\Repeater();
        $t->add_control('label', ['label' => __('Label', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('3-pack', 'nv-product-widgets')]);
        $t->add_control('qty', ['label' => __('Items to pick', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 1, 'max' => 12, 'default' => 3]);
        $t->add_control('discount', ['label' => __('Discount %', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'max' => 90, 'default' => 15]);
        $t->add_control('coupon', ['label' => __('WooCommerce coupon code (for real discount)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'description' => __('Create a matching coupon in WooCommerce → Marketing → Coupons.', 'nv-product-widgets')]);
        $this->add_control('tiers', [
            'label' => __('Tiers', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $t->get_controls(),
            'title_field' => '{{{ label }}}',
            'default' => [
                ['label' => __('2-pack', 'nv-product-widgets'), 'qty' => 2, 'discount' => 10, 'coupon' => ''],
                ['label' => __('3-pack', 'nv-product-widgets'), 'qty' => 3, 'discount' => 15, 'coupon' => ''],
                ['label' => __('4-pack', 'nv-product-widgets'), 'qty' => 4, 'discount' => 25, 'coupon' => ''],
            ],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_products', ['label' => __('Products', 'nv-product-widgets')]);
        $p = new \Elementor\Repeater();
        $p->add_control('product_id', ['label' => __('WooCommerce product ID', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'description' => __('Required — bundle adds these to the cart.', 'nv-product-widgets')]);
        $this->add_control('products', [
            'label' => __('Selectable products', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $p->get_controls(),
            'title_field' => __('Product #{{{ product_id }}}', 'nv-product-widgets'),
            'default' => [],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('accent', ['label' => __('Accent color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#312E81', 'selectors' => ['{{WRAPPER}} .nv-pw-bb' => '--nv-bb-accent: {{VALUE}};']]);
        $this->add_control('cta_label', ['label' => __('Button label', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Lägg paket i varukorg', 'nv-product-widgets')]);
        $this->add_control('cols', ['label' => __('Product columns', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '3', 'options' => ['2' => '2', '3' => '3', '4' => '4']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        if (!function_exists('wc_get_product')) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Bundle Builder — WooCommerce required', '🧩');
            return;
        }
        $s = $this->get_settings_for_display();

        $products = [];
        foreach (($s['products'] ?? []) as $row) {
            if (!is_array($row)) continue;
            $pid = (int) ($row['product_id'] ?? 0);
            if ($pid <= 0) continue;
            $p = wc_get_product($pid);
            if (!($p instanceof \WC_Product) || !$p->is_purchasable()) continue;
            $img = $p->get_image_id();
            $src = $img ? wp_get_attachment_image_url($img, 'medium') : wc_placeholder_img_src('medium');
            $products[] = ['id' => $pid, 'name' => $p->get_name(), 'price' => (float) wc_get_price_to_display($p), 'price_html' => $p->get_price_html(), 'img' => (string) $src];
        }

        $tiers = [];
        foreach (($s['tiers'] ?? []) as $row) {
            if (!is_array($row)) continue;
            $qty = max(1, (int) ($row['qty'] ?? 1));
            $tiers[] = ['label' => trim((string) ($row['label'] ?? '')), 'qty' => $qty, 'discount' => max(0, min(90, (float) ($row['discount'] ?? 0))), 'coupon' => trim((string) ($row['coupon'] ?? ''))];
        }

        if (empty($products) || empty($tiers)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Bundle Builder — add tiers and product IDs', '🧩');
            return;
        }

        $eyebrow = trim((string) ($s['eyebrow'] ?? ''));
        $heading = trim((string) ($s['heading'] ?? ''));
        $cta = trim((string) ($s['cta_label'] ?? 'Lägg paket i varukorg')) ?: 'Lägg paket i varukorg';
        $cols = in_array(($s['cols'] ?? '3'), ['2', '3', '4'], true) ? (string) $s['cols'] : '3';
        $symbol = get_woocommerce_currency_symbol();
        $decimals = wc_get_price_decimals();
        ?>
        <div class="nv-pw-bb" data-nv-bundle data-symbol="<?php echo esc_attr($symbol); ?>" data-decimals="<?php echo (int) $decimals; ?>" style="--nv-bb-cols: <?php echo esc_attr($cols); ?>;">
            <?php if ($eyebrow !== '') : ?><span class="nv-pw-bb__eyebrow"><?php echo esc_html($eyebrow); ?></span><?php endif; ?>
            <?php if ($heading !== '') : ?><h3 class="nv-pw-bb__heading"><?php echo esc_html($heading); ?></h3><?php endif; ?>

            <div class="nv-pw-bb__tiers" role="tablist">
                <?php foreach ($tiers as $i => $t) : ?>
                    <button type="button" class="nv-pw-bb__tier<?php echo $i === 0 ? ' is-active' : ''; ?>" data-nv-tier="<?php echo (int) $i; ?>" data-qty="<?php echo (int) $t['qty']; ?>" data-discount="<?php echo esc_attr($t['discount']); ?>" data-coupon="<?php echo esc_attr($t['coupon']); ?>">
                        <span class="nv-pw-bb__tier-label"><?php echo esc_html($t['label']); ?></span>
                        <?php if ($t['discount'] > 0) : ?><span class="nv-pw-bb__tier-save">−<?php echo esc_html(rtrim(rtrim(number_format($t['discount'], 1), '0'), '.')); ?>%</span><?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <p class="nv-pw-bb__hint" data-nv-hint></p>

            <div class="nv-pw-bb__grid">
                <?php foreach ($products as $p) : ?>
                    <div class="nv-pw-bb__product" data-id="<?php echo (int) $p['id']; ?>" data-price="<?php echo esc_attr($p['price']); ?>">
                        <div class="nv-pw-bb__img"><img src="<?php echo esc_url($p['img']); ?>" alt="<?php echo esc_attr($p['name']); ?>" loading="lazy"></div>
                        <div class="nv-pw-bb__pinfo">
                            <span class="nv-pw-bb__pname"><?php echo esc_html($p['name']); ?></span>
                            <span class="nv-pw-bb__pprice"><?php echo wp_kses_post($p['price_html']); ?></span>
                        </div>
                        <div class="nv-pw-bb__qty">
                            <button type="button" class="nv-pw-bb__minus" data-nv-minus aria-label="<?php echo esc_attr__('Ta bort', 'nv-product-widgets'); ?>">−</button>
                            <span class="nv-pw-bb__count" data-nv-count>0</span>
                            <button type="button" class="nv-pw-bb__plus" data-nv-plus aria-label="<?php echo esc_attr__('Lägg till', 'nv-product-widgets'); ?>">+</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="nv-pw-bb__summary">
                <div class="nv-pw-bb__totals">
                    <div class="nv-pw-bb__row"><span><?php echo esc_html__('Delsumma', 'nv-product-widgets'); ?></span><span data-nv-subtotal>—</span></div>
                    <div class="nv-pw-bb__row nv-pw-bb__row--save"><span><?php echo esc_html__('Du sparar', 'nv-product-widgets'); ?></span><span data-nv-savings>—</span></div>
                    <div class="nv-pw-bb__row nv-pw-bb__row--total"><span><?php echo esc_html__('Totalt', 'nv-product-widgets'); ?></span><span data-nv-total>—</span></div>
                </div>
                <button type="button" class="nv-pw-bb__cta" data-nv-add disabled><?php echo esc_html($cta); ?></button>
                <p class="nv-pw-bb__msg" data-nv-msg role="status"></p>
            </div>
        </div>
        <?php
    }
}
