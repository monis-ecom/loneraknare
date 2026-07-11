<?php
if (!defined('ABSPATH')) exit;

/**
 * NV PW: Related Products
 *
 * Replaces WL: Related Product from ShopLentor.
 * Renders a grid of related products based on WooCommerce's own
 * related products logic (shared categories / tags).
 */
class NV_PW_Related_Products extends \Elementor\Widget_Base {
    public function get_name(): string    { return 'nv-related-products'; }
    public function get_title(): string   { return 'NV: Related Products'; }
    public function get_icon(): string    { return 'eicon-product-related'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array   { return ['related', 'products', 'similar', 'upsell', 'woocommerce']; }

    protected function register_controls(): void {

        /* ── Content ── */
        $this->start_controls_section('section_content', [
            'label' => 'Related Products',
        ]);
        $this->add_control('section_title', [
            'label'   => 'Section Title',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Liknande produkter',
        ]);
        $this->add_control('posts_per_page', [
            'label'   => 'Number of Products',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 4,
            'min'     => 1,
            'max'     => 12,
        ]);
        $this->add_responsive_control('columns', [
            'label'          => 'Columns',
            'type'           => \Elementor\Controls_Manager::NUMBER,
            'default'        => 4,
            'tablet_default' => 2,
            'mobile_default' => 2,
            'min'            => 1,
            'max'            => 6,
        ]);
        $this->add_control('orderby', [
            'label'   => 'Order By',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'rand',
            'options' => [
                'rand'  => 'Random',
                'date'  => 'Latest',
                'price' => 'Price',
            ],
        ]);
        $this->end_controls_section();

        /* ── Style ── */
        $this->start_controls_section('section_style', [
            'label' => 'Style',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control('title_color', [
            'label'     => 'Title Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#111318',
            'selectors' => ['{{WRAPPER}} .nv-pw-related__heading' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('card_bg', [
            'label'     => 'Card Background',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#FFFFFF',
            'selectors' => ['{{WRAPPER}} .nv-pw-related__card' => 'background: {{VALUE}};'],
        ]);
        $this->add_responsive_control('column_gap', [
            'label'     => 'Column Gap',
            'type'      => \Elementor\Controls_Manager::SLIDER,
            'default'   => ['size' => 20, 'unit' => 'px'],
            'range'     => ['px' => ['min' => 0, 'max' => 60]],
            'selectors' => ['{{WRAPPER}} .nv-pw-related__grid' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $product = NV_PW_Editor_Helper::get_preview_product();

        if (!$product instanceof WC_Product) {
            NV_PW_Editor_Helper::render_placeholder('NV: Related Products', '🔗');
            return;
        }

        $GLOBALS['product'] = $product;

        $settings  = $this->get_settings_for_display();
        $limit     = intval($settings['posts_per_page'] ?? 4);
        $columns   = intval($settings['columns'] ?? 4);
        $orderby   = sanitize_key($settings['orderby'] ?? 'rand');
        $title_str = esc_html($settings['section_title'] ?? 'Liknande produkter');

        /* ── Get related product IDs via WooCommerce ── */
        $related_ids = wc_get_related_products($product->get_id(), $limit);

        if (empty($related_ids)) {
            if (NV_PW_Editor_Helper::is_editor()) {
                echo '<p style="color:#999;font-family:Manrope,sans-serif;font-size:13px;">No related products found. Products sharing categories or tags will appear here.</p>';
            }
            return;
        }

        // Respect orderby setting
        $args = [
            'post_type'      => 'product',
            'post__in'       => $related_ids,
            'posts_per_page' => $limit,
            'orderby'        => $orderby === 'rand' ? 'rand' : 'meta_value_num',
            'meta_key'       => $orderby === 'price' ? '_price' : '',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ];
        if ($orderby === 'date') {
            $args['orderby']  = 'date';
            $args['order']    = 'DESC';
            $args['meta_key'] = '';
        }

        $query = new WP_Query($args);

        if (!$query->have_posts()) {
            return;
        }

        ?>
        <div class="nv-pw-related">
            <?php if ($title_str) : ?>
                <h3 class="nv-pw-related__heading"><?php echo $title_str; ?></h3>
            <?php endif; ?>
            <div class="nv-pw-related__grid" style="--nv-rel-cols:<?php echo $columns; ?>;">
                <?php while ($query->have_posts()) : $query->the_post();
                    $rel = wc_get_product(get_the_ID());
                    if (!$rel instanceof WC_Product) continue;
                    $img_id  = $rel->get_image_id();
                    $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'woocommerce_thumbnail') : wc_placeholder_img_src('woocommerce_thumbnail');
                    ?>
                    <div class="nv-pw-related__card">
                        <a href="<?php echo esc_url(get_permalink($rel->get_id())); ?>" class="nv-pw-related__img-link">
                            <img src="<?php echo esc_url($img_url); ?>"
                                 alt="<?php echo esc_attr($rel->get_name()); ?>"
                                 loading="lazy"
                                 class="nv-pw-related__img">
                        </a>
                        <div class="nv-pw-related__info">
                            <a href="<?php echo esc_url(get_permalink($rel->get_id())); ?>" class="nv-pw-related__name">
                                <?php echo esc_html($rel->get_name()); ?>
                            </a>
                            <div class="nv-pw-related__price">
                                <?php echo wp_kses_post($rel->get_price_html()); ?>
                            </div>
                            <a href="<?php echo esc_url(get_permalink($rel->get_id())); ?>" class="nv-pw-related__btn">
                                <?php esc_html_e('Se produkt', 'nv-product-widgets'); ?>
                            </a>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
        <?php
    }
}
