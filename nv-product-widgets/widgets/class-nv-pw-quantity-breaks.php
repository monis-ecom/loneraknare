<?php
if (!defined('ABSPATH')) exit;

/**
 * NV: Quantity Breaks — CourtX-style volume/bundle discount selector.
 * Pick one quantity tier (radio) → the selected tier reveals per-unit
 * variation (size) dropdowns → one Add-to-Cart adds qty×variation + optional
 * free gift, and applies an optional WooCommerce coupon for the real discount.
 */
class NV_PW_Quantity_Breaks extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-quantity-breaks'; }
    public function get_title(): string { return 'NV: Quantity Breaks'; }
    public function get_icon(): string { return 'eicon-price-list'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['quantity', 'breaks', 'volume', 'bundle', 'discount', 'tiers', 'bulk', 'köp fler', 'mängdrabatt', 'gift']; }
    public function get_script_depends(): array { return ['nv-quantity-breaks']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_general', ['label' => __('General', 'nv-product-widgets')]);
        $this->add_control('heading', [
            'label' => __('Heading', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Erbjudande', 'nv-product-widgets'),
        ]);
        $this->add_control('product_id', [
            'label' => __('Product ID (0 = current product)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 0,
            'description' => __('The variable/simple product these quantities apply to.', 'nv-product-widgets'),
        ]);
        $this->add_control('size_label', [
            'label' => __('Variation group label', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Storlek', 'nv-product-widgets'),
        ]);
        $this->add_control('variation_placeholder', [
            'label' => __('Variation dropdown placeholder', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('välj storlek', 'nv-product-widgets'),
        ]);
        $this->add_control('button_text', [
            'label' => __('Add-to-cart button text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Lägg i varukorg', 'nv-product-widgets'),
        ]);
        $this->add_control('guarantee_text', [
            'label' => __('Guarantee line (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('60 dagars öppet köp — nöjd eller pengarna tillbaka', 'nv-product-widgets'),
        ]);
        $this->add_control('discount_mode', [
            'label' => __('Discount method', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'coupon',
            'options' => [
                'coupon' => __('WooCommerce coupon per tier', 'nv-product-widgets'),
                'auto'   => __('Automatic — no code (per-tier %)', 'nv-product-widgets'),
                'nvcc'   => __('Exact tier price via NV Commerce Core', 'nv-product-widgets'),
            ],
            'description' => __('“Exact tier price via NV Commerce Core” charges precisely the tier’s displayed price and stops NV Commerce Core’s bulk discount from also applying to these items — use this if you run NV Commerce Core. The number is read from each tier’s “Price (display)” field.', 'nv-product-widgets'),
        ]);
        $this->add_control('after_add', [
            'label' => __('After add to cart', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'side_cart',
            'options' => [
                'side_cart' => __('Open side cart (no redirect)', 'nv-product-widgets'),
                'stay'      => __('Stay on page (refresh cart only)', 'nv-product-widgets'),
                'redirect_cart' => __('Go to cart page', 'nv-product-widgets'),
            ],
            'description' => __('“Open side cart” refreshes the header cart and fires the standard add-to-cart event most themes use to slide their cart open.', 'nv-product-widgets'),
        ]);
        $this->add_control('cart_selector', [
            'label' => __('Side cart trigger selector (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
            'condition' => ['after_add' => 'side_cart'],
            'description' => __('Only if your side cart does not open automatically: the CSS selector of your cart drawer button (e.g. .my-cart-toggle).', 'nv-product-widgets'),
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_tiers', ['label' => __('Tiers', 'nv-product-widgets')]);
        $r = new \Elementor\Repeater();
        $r->add_control('qty', ['label' => __('Quantity', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 1, 'max' => 12, 'default' => 1]);
        $r->add_control('label', ['label' => __('Tier label', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('1 par', 'nv-product-widgets')]);
        $r->add_control('sublabel', ['label' => __('Sub-label', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Du sparar 20%', 'nv-product-widgets')]);
        $r->add_control('price', ['label' => __('Price (display)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '299 kr']);
        $r->add_control('original_price', ['label' => __('Original price (struck-through)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $r->add_control('badge', ['label' => __('Badge (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $r->add_control('highlighted', ['label' => __('Selected by default', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => '']);
        $r->add_control('coupon', ['label' => __('WooCommerce coupon code (real discount)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '', 'description' => __('Used when Discount method = Coupon. Create a matching coupon in WooCommerce → Marketing → Coupons.', 'nv-product-widgets'), 'condition' => ['discount_mode' => 'coupon']]);
        $r->add_control('discount_percent', ['label' => __('Discount % (automatic mode)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 0, 'min' => 0, 'max' => 90, 'description' => __('Used when Discount method = Automatic. Applied to this tier’s items at checkout — no code.', 'nv-product-widgets'), 'condition' => ['discount_mode' => 'auto']]);
        $r->add_control('gift_enabled', ['label' => __('Free gift on this tier', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => '']);
        $r->add_control('gift_label', ['label' => __('Gift label', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('+ GRATIS gåva', 'nv-product-widgets'), 'condition' => ['gift_enabled' => 'yes']]);
        $r->add_control('gift_value', ['label' => __('Gift value (struck-through)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '', 'condition' => ['gift_enabled' => 'yes']]);
        $r->add_control('gift_product_id', [
            'label' => __('Free gift product', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT2,
            'options' => $this->product_options(),
            'default' => '',
            'label_block' => true,
            'description' => __('Search and pick the product to add as a free gift. Leave empty for display-only.', 'nv-product-widgets'),
            'condition' => ['gift_enabled' => 'yes'],
        ]);
        $r->add_control('gift_auto', ['label' => __('Auto-fill label & value from the gift product', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => '', 'description' => __('When on, the bar shows the gift product\'s name and price instead of the manual fields above.', 'nv-product-widgets'), 'condition' => ['gift_enabled' => 'yes']]);
        $r->add_control('gift_custom_name', ['label' => __('Custom gift name (shorter title)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '', 'description' => __('Overrides the gift product\'s name in the bar — handy for long titles.', 'nv-product-widgets'), 'condition' => ['gift_enabled' => 'yes', 'gift_auto' => 'yes']]);
        $this->add_control('tiers', [
            'label' => __('Tiers', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ label }}}',
            'default' => [
                ['qty' => 1, 'label' => __('1 par', 'nv-product-widgets'), 'sublabel' => __('Du sparar 20%', 'nv-product-widgets'), 'price' => '299 kr', 'original_price' => '369 kr', 'highlighted' => ''],
                ['qty' => 2, 'label' => __('2 par', 'nv-product-widgets'), 'sublabel' => __('Du sparar 30%', 'nv-product-widgets'), 'price' => '499 kr', 'original_price' => '739 kr', 'badge' => __('Populärast', 'nv-product-widgets'), 'highlighted' => 'yes', 'gift_enabled' => 'yes', 'gift_label' => __('+ GRATIS guide 🎁', 'nv-product-widgets'), 'gift_value' => '100 kr'],
                ['qty' => 3, 'label' => __('3 par', 'nv-product-widgets'), 'sublabel' => __('Du sparar 45%', 'nv-product-widgets'), 'price' => '599 kr', 'original_price' => '1109 kr', 'highlighted' => ''],
            ],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('accent', [
            'label' => __('Accent (selected border / radio)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#3B37C4',
            'selectors' => ['{{WRAPPER}} .nv-pw-qb' => '--nv-qb-accent: {{VALUE}};'],
        ]);
        $this->add_control('selected_bg', [
            'label' => __('Selected card background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#E9E9FB',
            'selectors' => ['{{WRAPPER}} .nv-pw-qb' => '--nv-qb-sel-bg: {{VALUE}};'],
        ]);
        $this->add_control('badge_bg', [
            'label' => __('Badge background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#2A2550',
            'selectors' => ['{{WRAPPER}} .nv-pw-qb' => '--nv-qb-badge-bg: {{VALUE}};'],
        ]);
        $this->add_control('badge_position', [
            'label' => __('Badge position', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'top-right',
            'options' => [
                'top-right'  => __('Top right', 'nv-product-widgets'),
                'top-left'   => __('Top left', 'nv-product-widgets'),
                'top-center' => __('Top center', 'nv-product-widgets'),
                'inline'     => __('Inline pill (above the row)', 'nv-product-widgets'),
            ],
            'description' => __('Move the badge off the price. “Top left”, “Top center” or “Inline” keep it clear of the price on the right.', 'nv-product-widgets'),
        ]);
        $this->add_control('gift_bg', [
            'label' => __('Gift bar background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#2A2550',
            'selectors' => ['{{WRAPPER}} .nv-pw-qb' => '--nv-qb-gift-bg: {{VALUE}};'],
        ]);
        $this->add_control('button_bg', [
            'label' => __('Button background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#6C63E0',
            'selectors' => ['{{WRAPPER}} .nv-pw-qb__cta' => 'background: {{VALUE}};'],
        ]);
        $this->add_control('button_color', [
            'label' => __('Button text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF',
            'selectors' => ['{{WRAPPER}} .nv-pw-qb__cta' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('fs_heading_ctrl', ['label' => __('Font sizes', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $font_targets = [
            'fs_heading'   => [__('Heading', 'nv-product-widgets'), '.nv-pw-qb__heading'],
            'fs_label'     => [__('Tier label', 'nv-product-widgets'), '.nv-pw-qb__label'],
            'fs_sub'       => [__('Sub-label', 'nv-product-widgets'), '.nv-pw-qb__sub'],
            'fs_price'     => [__('Price', 'nv-product-widgets'), '.nv-pw-qb__price'],
            'fs_orig'      => [__('Original price', 'nv-product-widgets'), '.nv-pw-qb__orig'],
            'fs_badge'     => [__('Badge', 'nv-product-widgets'), '.nv-pw-qb__badge'],
            'fs_varlabel'  => [__('Variation group label', 'nv-product-widgets'), '.nv-pw-qb__variations-label'],
            'fs_variation' => [__('Variation dropdowns', 'nv-product-widgets'), '.nv-pw-qb__vselect'],
            'fs_gift'      => [__('Gift label', 'nv-product-widgets'), '.nv-pw-qb__gift-label'],
            'fs_giftval'   => [__('Gift value', 'nv-product-widgets'), '.nv-pw-qb__gift-value'],
            'fs_button'    => [__('Button', 'nv-product-widgets'), '.nv-pw-qb__cta'],
            'fs_guarantee' => [__('Guarantee line', 'nv-product-widgets'), '.nv-pw-qb__guarantee'],
        ];
        foreach ($font_targets as $key => $meta) {
            $this->add_responsive_control($key, [
                'label' => sprintf(__('%s size', 'nv-product-widgets'), $meta[0]),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => ['px' => ['min' => 8, 'max' => 48, 'step' => 1]],
                'selectors' => ['{{WRAPPER}} ' . $meta[1] => 'font-size: {{SIZE}}{{UNIT}};'],
            ]);
        }
        $this->end_controls_section();
    }

    /** Published products as id => "Title (#id)" for the gift picker (editor only). */
    private function product_options(): array {
        $opts = ['' => __('— No gift product —', 'nv-product-widgets')];
        if (!function_exists('wc_get_products')) return $opts;
        // Only run the query where the control UI is shown (admin / editor); the
        // saved value is read directly in render(), so the frontend needs no list.
        if (!is_admin() && !NV_PW_Editor_Helper::is_editor()) {
            return $opts;
        }
        $q = new \WP_Query([
            'post_type'        => 'product',
            'post_status'      => 'publish',
            'posts_per_page'   => 500,
            'orderby'          => 'title',
            'order'            => 'ASC',
            'fields'           => 'ids',
            'no_found_rows'    => true,
            'suppress_filters' => true,
        ]);
        foreach ($q->posts as $pid) {
            $opts[(string) $pid] = get_the_title($pid) . ' (#' . $pid . ')';
        }
        return $opts;
    }

    /** Build the list of purchasable variations for the size dropdowns. */
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

    protected function render(): void {
        if (!function_exists('wc_get_product')) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Quantity Breaks — WooCommerce required', '🧮');
            return;
        }
        $s = $this->get_settings_for_display();
        $rows = is_array($s['tiers'] ?? null) ? $s['tiers'] : [];
        $tiers = [];
        foreach ($rows as $t) {
            if (!is_array($t)) continue;
            $tiers[] = $t;
        }
        if (empty($tiers)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Quantity Breaks — add tiers', '🧮');
            return;
        }

        $pid = (int) ($s['product_id'] ?? 0);
        $product = null;
        if ($pid > 0) {
            $product = wc_get_product($pid);
        } else {
            $product = NV_PW_Editor_Helper::get_preview_product();
        }
        if (!($product instanceof \WC_Product)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Quantity Breaks — no product', '🧮');
            return;
        }
        $product_id = $product->get_id();
        $is_variable = $product->is_type('variable');
        $variations = $this->get_variations($product);

        $heading = trim((string) ($s['heading'] ?? ''));
        $size_label = trim((string) ($s['size_label'] ?? ''));
        $placeholder = trim((string) ($s['variation_placeholder'] ?? 'välj')) ?: 'välj';
        $button_text = trim((string) ($s['button_text'] ?? '')) ?: __('Lägg i varukorg', 'nv-product-widgets');
        $guarantee = trim((string) ($s['guarantee_text'] ?? ''));
        $discount_mode = ($s['discount_mode'] ?? 'coupon') === 'auto' ? 'auto' : 'coupon';
        $badge_pos = in_array(($s['badge_position'] ?? 'top-right'), ['top-right', 'top-left', 'top-center', 'inline'], true) ? (string) $s['badge_position'] : 'top-right';
        $after_add = in_array(($s['after_add'] ?? 'side_cart'), ['side_cart', 'stay', 'redirect_cart'], true) ? (string) $s['after_add'] : 'side_cart';
        $cart_selector = trim((string) ($s['cart_selector'] ?? ''));

        $default_index = 0;
        foreach ($tiers as $i => $t) {
            if (($t['highlighted'] ?? '') === 'yes') { $default_index = $i; break; }
        }
        ?>
        <div class="nv-pw-qb nv-pw-qb--badge-<?php echo esc_attr($badge_pos); ?>" data-nv-qb data-product-id="<?php echo esc_attr((string) $product_id); ?>" data-variable="<?php echo $is_variable ? '1' : '0'; ?>" data-discount-mode="<?php echo esc_attr($discount_mode); ?>" data-after-add="<?php echo esc_attr($after_add); ?>"<?php if ($cart_selector !== '') echo ' data-cart-selector="' . esc_attr($cart_selector) . '"'; ?>>
            <?php if ($heading !== '') : ?><div class="nv-pw-qb__heading"><?php echo esc_html($heading); ?></div><?php endif; ?>

            <div class="nv-pw-qb__tiers">
                <?php foreach ($tiers as $i => $t) :
                    $qty = max(1, (int) ($t['qty'] ?? 1));
                    $label = trim((string) ($t['label'] ?? ''));
                    $sublabel = trim((string) ($t['sublabel'] ?? ''));
                    $price = trim((string) ($t['price'] ?? ''));
                    $orig = trim((string) ($t['original_price'] ?? ''));
                    $badge = trim((string) ($t['badge'] ?? ''));
                    $coupon = trim((string) ($t['coupon'] ?? ''));
                    $discount_pct = max(0, min(90, (float) ($t['discount_percent'] ?? 0)));
                    $gift_on = ($t['gift_enabled'] ?? '') === 'yes';
                    $gift_label = trim((string) ($t['gift_label'] ?? ''));
                    $gift_value = trim((string) ($t['gift_value'] ?? ''));
                    $gift_pid = (int) ($t['gift_product_id'] ?? 0);
                    // Auto-fill the gift label/value from the actual gift product.
                    if ($gift_on && $gift_pid > 0 && ($t['gift_auto'] ?? '') === 'yes') {
                        $gift_product = wc_get_product($gift_pid);
                        if ($gift_product instanceof \WC_Product) {
                            $gname = trim((string) ($t['gift_custom_name'] ?? ''));
                            if ($gname === '') $gname = $gift_product->get_name();
                            $gift_label = '🎁 ' . sprintf(__('GRATIS: %s', 'nv-product-widgets'), $gname);
                            $reg = $gift_product->get_regular_price();
                            if ($reg !== '' && $reg !== null) {
                                $gift_value = trim(html_entity_decode(wp_strip_all_tags(wc_price((float) $reg))));
                            }
                        }
                    }
                    $selected = ($i === $default_index);
                    // Parse the tier's displayed price into a numeric total for the
                    // exact-price (NV Commerce Core) mode. Handles "1 109 kr", "499 kr",
                    // "1 109,50 kr" → 1109 / 499 / 1109.50.
                    $cart_total = (float) preg_replace('/[^0-9.]/', '', str_replace([' ', "\xC2\xA0", ','], ['', '', '.'], $price));
                    ?>
                    <div class="nv-pw-qb__tier<?php echo $selected ? ' is-selected' : ''; ?>"
                         data-nv-qb-tier="<?php echo (int) $i; ?>"
                         data-qty="<?php echo (int) $qty; ?>"
                         data-coupon="<?php echo esc_attr($coupon); ?>"
                         data-discount="<?php echo esc_attr((string) $discount_pct); ?>"
                         data-cart-total="<?php echo esc_attr((string) $cart_total); ?>"
                         data-label="<?php echo esc_attr($label); ?>"
                         data-gift-id="<?php echo esc_attr((string) ($gift_on ? $gift_pid : 0)); ?>">
                        <?php if ($badge !== '') : ?><span class="nv-pw-qb__badge"><?php echo esc_html($badge); ?></span><?php endif; ?>
                        <div class="nv-pw-qb__row">
                            <span class="nv-pw-qb__radio" aria-hidden="true"></span>
                            <span class="nv-pw-qb__info">
                                <span class="nv-pw-qb__label"><?php echo esc_html($label); ?></span>
                                <?php if ($sublabel !== '') : ?><span class="nv-pw-qb__sub"><?php echo esc_html($sublabel); ?></span><?php endif; ?>
                            </span>
                            <span class="nv-pw-qb__prices">
                                <?php if ($price !== '') : ?><span class="nv-pw-qb__price"><?php echo esc_html($price); ?></span><?php endif; ?>
                                <?php if ($orig !== '') : ?><span class="nv-pw-qb__orig"><?php echo esc_html($orig); ?></span><?php endif; ?>
                            </span>
                        </div>

                        <?php if ($is_variable && !empty($variations)) : ?>
                            <div class="nv-pw-qb__variations" data-nv-qb-variations>
                                <?php if ($size_label !== '') : ?><span class="nv-pw-qb__variations-label"><?php echo esc_html($size_label); ?></span><?php endif; ?>
                                <?php for ($u = 0; $u < $qty; $u++) : ?>
                                    <label class="nv-pw-qb__vrow">
                                        <?php if ($qty > 1) : ?><span class="nv-pw-qb__vnum">#<?php echo (int) ($u + 1); ?></span><?php endif; ?>
                                        <select class="nv-pw-qb__vselect" data-nv-qb-unit="<?php echo (int) $u; ?>">
                                            <option value=""><?php echo esc_html($placeholder); ?></option>
                                            <?php foreach ($variations as $vr) : ?>
                                                <option value="<?php echo esc_attr((string) $vr['id']); ?>"><?php echo esc_html($vr['label']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($gift_on && $gift_label !== '') : ?>
                            <div class="nv-pw-qb__gift">
                                <span class="nv-pw-qb__gift-label"><?php echo esc_html($gift_label); ?></span>
                                <?php if ($gift_value !== '') : ?><span class="nv-pw-qb__gift-value"><?php echo esc_html($gift_value); ?></span><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="nv-pw-qb__cta" data-nv-qb-add><?php echo esc_html($button_text); ?></button>
            <p class="nv-pw-qb__msg" data-nv-qb-msg role="status"></p>
            <?php if ($guarantee !== '') : ?><p class="nv-pw-qb__guarantee"><?php echo esc_html($guarantee); ?></p><?php endif; ?>
        </div>
        <?php
    }
}
