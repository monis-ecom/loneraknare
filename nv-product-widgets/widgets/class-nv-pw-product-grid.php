<?php
if (!defined('ABSPATH')) exit;

/**
 * NV: Product Grid — a curated grid of hand-picked products (or a category /
 * newest / featured / best-selling set) with image, price, rating, sale badge
 * and an add-to-cart button. The curated counterpart to the auto-driven
 * Related Products / Upsells widgets.
 */
class NV_PW_Product_Grid extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-product-grid'; }
    public function get_title(): string { return 'NV: Product Grid'; }
    public function get_icon(): string { return 'eicon-products-grid'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['products', 'grid', 'curated', 'collection', 'shop', 'featured', 'produkter']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('heading', ['label' => __('Heading (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Populära produkter', 'nv-product-widgets'), 'label_block' => true]);
        $this->add_control('subheading', ['label' => __('Subheading (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => '']);
        $this->add_control('source', ['label' => __('Products from', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'pick', 'options' => [
            'pick' => __('Hand-picked', 'nv-product-widgets'),
            'category' => __('Category', 'nv-product-widgets'),
            'recent' => __('Newest', 'nv-product-widgets'),
            'featured' => __('Featured', 'nv-product-widgets'),
            'best_selling' => __('Best selling', 'nv-product-widgets'),
        ]]);
        $this->add_control('product_ids', ['label' => __('Products', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT2, 'multiple' => true, 'label_block' => true, 'options' => $this->product_options(), 'condition' => ['source' => 'pick']]);
        $this->add_control('category_id', ['label' => __('Category', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT2, 'label_block' => true, 'options' => $this->category_options(), 'condition' => ['source' => 'category']]);
        $this->add_control('count', ['label' => __('Max products', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 8, 'min' => 1, 'max' => 24, 'condition' => ['source!' => 'pick']]);
        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        $this->start_controls_section('section_display', ['label' => __('Display', 'nv-product-widgets')]);
        $this->add_responsive_control('columns', ['label' => __('Columns', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 4, 'tablet_default' => 2, 'mobile_default' => 2, 'min' => 1, 'max' => 6, 'selectors' => ['{{WRAPPER}} .nv-pw-pg' => '--nv-pg-cols: {{VALUE}};']]);
        $this->add_control('show_rating', ['label' => __('Show rating', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_price', ['label' => __('Show price', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('show_badge', ['label' => __('Show sale badge', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('button', ['label' => __('Button', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'add', 'options' => ['add' => __('Add to cart', 'nv-product-widgets'), 'view' => __('View product', 'nv-product-widgets'), 'none' => __('No button', 'nv-product-widgets')]]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('align', ['label' => __('Heading alignment', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::CHOOSE, 'options' => ['left' => ['title' => __('Left', 'nv-product-widgets'), 'icon' => 'eicon-text-align-left'], 'center' => ['title' => __('Center', 'nv-product-widgets'), 'icon' => 'eicon-text-align-center']], 'default' => 'center', 'selectors' => ['{{WRAPPER}} .nv-pw-pg__head' => 'text-align: {{VALUE}};']]);
        $this->add_control('card_bg', ['label' => __('Card background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => ['{{WRAPPER}} .nv-pw-pg' => '--nv-pg-card: {{VALUE}};']]);
        $this->add_control('accent', ['label' => __('Button / accent', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#14161D', 'selectors' => ['{{WRAPPER}} .nv-pw-pg' => '--nv-pg-accent: {{VALUE}};']]);
        $this->add_control('btn_color', ['label' => __('Button text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => ['{{WRAPPER}} .nv-pw-pg' => '--nv-pg-btn-color: {{VALUE}};']]);
        $this->add_control('radius', ['label' => __('Card radius', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 28]], 'default' => ['size' => 14, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-pg' => '--nv-pg-radius: {{SIZE}}px;']]);
        $this->end_controls_section();
    }

    /** Published products as id => "Title (#id)" for the picker (editor only). */
    private function product_options(): array {
        $opts = [];
        if (!function_exists('wc_get_products')) return $opts;
        if (!is_admin() && !NV_PW_Editor_Helper::is_editor()) return $opts;
        $q = new \WP_Query(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 500, 'orderby' => 'title', 'order' => 'ASC', 'fields' => 'ids', 'no_found_rows' => true, 'suppress_filters' => true]);
        foreach ($q->posts as $pid) {
            $opts[(string) $pid] = get_the_title($pid) . ' (#' . $pid . ')';
        }
        return $opts;
    }

    /** Product categories as id => "Name" for the picker (editor only). */
    private function category_options(): array {
        $opts = [];
        if (!is_admin() && !NV_PW_Editor_Helper::is_editor()) return $opts;
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        if (is_array($terms)) {
            foreach ($terms as $t) {
                if (is_object($t)) $opts[(string) $t->term_id] = $t->name;
            }
        }
        return $opts;
    }

    private function query_products(array $s): array {
        $source = (string) ($s['source'] ?? 'pick');
        $count = max(1, (int) ($s['count'] ?? 8));
        if ($source === 'pick') {
            $ids = array_filter(array_map('absint', (array) ($s['product_ids'] ?? [])));
            if (empty($ids)) return [];
            $args = ['include' => $ids, 'orderby' => 'post__in', 'limit' => count($ids)];
        } elseif ($source === 'category') {
            $cat = absint($s['category_id'] ?? 0);
            if ($cat <= 0) return [];
            $term = get_term($cat, 'product_cat');
            $args = ['category' => ($term && !is_wp_error($term)) ? [$term->slug] : [], 'limit' => $count, 'orderby' => 'date', 'order' => 'DESC'];
        } elseif ($source === 'featured') {
            $args = ['featured' => true, 'limit' => $count, 'orderby' => 'date', 'order' => 'DESC'];
        } elseif ($source === 'best_selling') {
            $args = ['limit' => $count, 'orderby' => 'popularity'];
        } else {
            $args = ['limit' => $count, 'orderby' => 'date', 'order' => 'DESC'];
        }
        $args['status'] = 'publish';
        $args['visibility'] = 'visible';
        $products = wc_get_products($args);
        return is_array($products) ? $products : [];
    }

    private function stars_html(float $rating): string {
        $rating = max(0, min(5, $rating));
        $pct = ($rating / 5) * 100;
        return '<span class="nv-pw-pg__stars" role="img" aria-label="' . esc_attr(sprintf(__('%s of 5', 'nv-product-widgets'), $rating)) . '"><span class="nv-pw-pg__stars-base">★★★★★</span><span class="nv-pw-pg__stars-fill" style="width:' . esc_attr((string) $pct) . '%">★★★★★</span></span>';
    }

    protected function render(): void {
        if (!function_exists('wc_get_products')) return;
        $s = $this->get_settings_for_display();
        $products = $this->query_products($s);
        if (empty($products)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Product Grid — pick products or a source', '🛍️');
            return;
        }

        $heading = trim((string) ($s['heading'] ?? ''));
        $sub = trim((string) ($s['subheading'] ?? ''));
        $show_rating = (($s['show_rating'] ?? 'yes') === 'yes');
        $show_price = (($s['show_price'] ?? 'yes') === 'yes');
        $show_badge = (($s['show_badge'] ?? 'yes') === 'yes');
        $button = in_array(($s['button'] ?? 'add'), ['add', 'view', 'none'], true) ? (string) $s['button'] : 'add';
        ?>
        <div class="nv-pw-pg">
            <?php if ($heading !== '' || $sub !== '') : ?>
                <div class="nv-pw-pg__head">
                    <?php if ($heading !== '') : ?><h2 class="nv-pw-pg__heading<?php echo NV_PW_Headline::mod($s); ?>"><?php echo esc_html($heading); ?></h2><?php endif; ?>
                    <?php if ($sub !== '') : ?><p class="nv-pw-pg__sub"><?php echo esc_html($sub); ?></p><?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="nv-pw-pg__grid">
                <?php foreach ($products as $product) :
                    if (!($product instanceof \WC_Product)) continue;
                    $pid = $product->get_id();
                    $link = get_permalink($pid);
                    $img_id = $product->get_image_id();
                    $img = $img_id ? wp_get_attachment_image_url($img_id, 'woocommerce_thumbnail') : wc_placeholder_img_src('woocommerce_thumbnail');
                    $rating = (float) $product->get_average_rating();
                    $count = (int) $product->get_rating_count();
                    ?>
                    <div class="nv-pw-pg__card">
                        <a class="nv-pw-pg__media" href="<?php echo esc_url($link); ?>">
                            <?php if ($show_badge && $product->is_on_sale()) : ?><span class="nv-pw-pg__badge"><?php esc_html_e('REA', 'nv-product-widgets'); ?></span><?php endif; ?>
                            <img class="nv-pw-pg__img" src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" loading="lazy">
                        </a>
                        <div class="nv-pw-pg__info">
                            <a class="nv-pw-pg__name" href="<?php echo esc_url($link); ?>"><?php echo esc_html($product->get_name()); ?></a>
                            <?php if ($show_rating && $count > 0) : ?>
                                <div class="nv-pw-pg__rating"><?php echo $this->stars_html($rating); // phpcs:ignore ?><span class="nv-pw-pg__rating-count">(<?php echo (int) $count; ?>)</span></div>
                            <?php endif; ?>
                            <?php if ($show_price) : ?><div class="nv-pw-pg__price"><?php echo wp_kses_post($product->get_price_html()); ?></div><?php endif; ?>
                            <?php if ($button === 'add' && $product->is_purchasable() && $product->is_in_stock() && !$product->is_type('variable')) : ?>
                                <a class="nv-pw-pg__btn ajax_add_to_cart add_to_cart_button" href="<?php echo esc_url($product->add_to_cart_url()); ?>" data-quantity="1" data-product_id="<?php echo esc_attr((string) $pid); ?>" rel="nofollow"><?php echo esc_html($product->add_to_cart_text()); ?></a>
                            <?php elseif ($button !== 'none') : ?>
                                <a class="nv-pw-pg__btn nv-pw-pg__btn--view" href="<?php echo esc_url($link); ?>"><?php echo $button === 'add' ? esc_html($product->add_to_cart_text()) : esc_html__('Se produkt', 'nv-product-widgets'); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
