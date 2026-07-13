<?php
if (!defined('ABSPATH')) exit;

/**
 * NV PW: Product Upsells
 *
 * Replaces WL: Product Upsell from ShopLentor.
 * Renders products that have been manually set as "upsells" on the product.
 * These are configured in: Product → Linked Products → Upsells.
 */
class NV_PW_Upsells extends \Elementor\Widget_Base {
    public function get_name(): string    { return 'nv-upsells'; }
    public function get_title(): string   { return 'NV: Product Upsells'; }
    public function get_icon(): string    { return 'eicon-product-upsell'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array   { return ['upsell', 'upgrade', 'products', 'linked', 'woocommerce']; }

    protected function register_controls(): void {

        /* ── Content ── */
        $this->start_controls_section('section_content', [
            'label' => 'Upsell Settings',
        ]);
        $this->add_control('section_title', [
            'label'   => 'Section Title',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Du kanske också gillar',
        ]);
        $this->add_control('posts_per_page', [
            'label'   => 'Max Products',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 3,
            'min'     => 1,
            'max'     => 12,
        ]);
        $this->add_responsive_control('columns', [
            'label'          => 'Columns',
            'type'           => \Elementor\Controls_Manager::NUMBER,
            'default'        => 3,
            'tablet_default' => 2,
            'mobile_default' => 1,
            'min'            => 1,
            'max'            => 6,
        ]);
        $this->add_control('show_cta_button', [
            'label'   => 'Show "View Product" Button',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        $this->add_control('cta_text', [
            'label'     => 'Button Text',
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'Se produkt',
            'condition' => ['show_cta_button' => 'yes'],
        ]);
        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        /* ── Style ── */
        $this->start_controls_section('section_style', [
            'label' => 'Style',
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control('heading_color', [
            'label'     => 'Heading Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#111318',
            'selectors' => ['{{WRAPPER}} .nv-pw-upsells__heading' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('btn_bg_color', [
            'label'     => 'Button Color',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#0f766e',
            'selectors' => ['{{WRAPPER}} .nv-pw-upsells__btn' => 'background: {{VALUE}};'],
            'condition' => ['show_cta_button' => 'yes'],
        ]);
        $this->add_responsive_control('card_gap', [
            'label'     => 'Gap Between Cards',
            'type'      => \Elementor\Controls_Manager::SLIDER,
            'default'   => ['size' => 20, 'unit' => 'px'],
            'range'     => ['px' => ['min' => 0, 'max' => 60]],
            'selectors' => ['{{WRAPPER}} .nv-pw-upsells__grid' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $product = NV_PW_Editor_Helper::get_preview_product();

        if (!$product instanceof WC_Product) {
            NV_PW_Editor_Helper::render_placeholder('NV: Product Upsells', '⬆️');
            return;
        }

        $GLOBALS['product'] = $product;

        $settings    = $this->get_settings_for_display();
        $limit       = intval($settings['posts_per_page'] ?? 3);
        $columns     = intval($settings['columns'] ?? 3);
        $title_str   = esc_html($settings['section_title'] ?? 'Du kanske också gillar');
        $show_btn    = !empty($settings['show_cta_button']);
        $btn_text    = esc_html($settings['cta_text'] ?? 'Se produkt');

        $upsell_ids = $product->get_upsell_ids();

        if (empty($upsell_ids)) {
            if (NV_PW_Editor_Helper::is_editor()) {
                echo '<p style="color:#999;font-family:Manrope,sans-serif;font-size:13px;">No upsell products set. Add them in Product → Linked Products → Upsells.</p>';
            }
            return;
        }

        // Limit and fetch
        $upsell_ids = array_slice($upsell_ids, 0, $limit);
        $query = new WP_Query([
            'post_type'      => 'product',
            'post__in'       => $upsell_ids,
            'posts_per_page' => $limit,
            'post_status'    => 'publish',
            'orderby'        => 'post__in',
        ]);

        if (!$query->have_posts()) {
            return;
        }

        ?>
        <div class="nv-pw-upsells">
            <?php if ($title_str) : ?>
                <h3 class="nv-pw-upsells__heading<?php echo NV_PW_Headline::mod($settings); ?>"><?php echo $title_str; ?></h3>
            <?php endif; ?>
            <div class="nv-pw-upsells__grid" style="--nv-ups-cols:<?php echo $columns; ?>;">
                <?php while ($query->have_posts()) : $query->the_post();
                    $ups = wc_get_product(get_the_ID());
                    if (!$ups instanceof WC_Product) continue;
                    $img_id  = $ups->get_image_id();
                    $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'woocommerce_thumbnail') : wc_placeholder_img_src('woocommerce_thumbnail');
                    $on_sale = $ups->is_on_sale();
                    ?>
                    <div class="nv-pw-upsells__card">
                        <a href="<?php echo esc_url(get_permalink($ups->get_id())); ?>" class="nv-pw-upsells__img-link">
                            <img src="<?php echo esc_url($img_url); ?>"
                                 alt="<?php echo esc_attr($ups->get_name()); ?>"
                                 loading="lazy"
                                 class="nv-pw-upsells__img">
                            <?php if ($on_sale) : ?>
                                <span class="nv-pw-upsells__badge">Rea</span>
                            <?php endif; ?>
                        </a>
                        <div class="nv-pw-upsells__info">
                            <a href="<?php echo esc_url(get_permalink($ups->get_id())); ?>" class="nv-pw-upsells__name">
                                <?php echo esc_html($ups->get_name()); ?>
                            </a>
                            <?php if ($ups->get_short_description()) : ?>
                                <p class="nv-pw-upsells__excerpt">
                                    <?php echo wp_kses_post(wp_trim_words($ups->get_short_description(), 14)); ?>
                                </p>
                            <?php endif; ?>
                            <div class="nv-pw-upsells__price">
                                <?php echo wp_kses_post($ups->get_price_html()); ?>
                            </div>
                            <?php if ($show_btn) : ?>
                                <a href="<?php echo esc_url(get_permalink($ups->get_id())); ?>" class="nv-pw-upsells__btn">
                                    <?php echo $btn_text; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
        <?php
    }
}
