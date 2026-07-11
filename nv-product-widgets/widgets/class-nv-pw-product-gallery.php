<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Product_Gallery extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-product-gallery'; }
    public function get_title(): string { return 'NV: Product Gallery'; }
    public function get_icon(): string { return 'eicon-product-images'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['product', 'gallery', 'image', 'woocommerce']; }
    public function get_script_depends(): array { return ['nv-product-gallery']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_gallery', [
            'label' => 'Gallery Settings',
        ]);
        $this->add_control('show_thumbnails', [
            'label' => 'Show Thumbnails (Desktop Only)',
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
            'description' => 'Thumbnails are only shown on desktop. Mobile always uses dots.',
        ]);
        $this->add_control('thumbnail_position', [
            'label' => 'Thumbnail Position',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'bottom',
            'options' => ['bottom' => 'Bottom', 'left' => 'Left'],
            'condition' => ['show_thumbnails' => 'yes'],
        ]);
        $this->add_control('image_border_radius', [
            'label' => 'Border Radius',
            'type' => \Elementor\Controls_Manager::SLIDER,
            'default' => ['size' => 14, 'unit' => 'px'],
            'range' => ['px' => ['min' => 0, 'max' => 30]],
            'selectors' => ['.nv-pw-gallery__main img' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $product = NV_PW_Editor_Helper::get_preview_product();

        if (!$product instanceof WC_Product) {
            NV_PW_Editor_Helper::render_placeholder('NV: Product Gallery', '🖼️');
            return;
        }

        // Set global $product so WooCommerce functions work
        $GLOBALS['product'] = $product;

        $gallery_ids = $product->get_gallery_image_ids();
        $main_image_id = $product->get_image_id();
        $all_ids = [];
        if ($main_image_id) {
            $all_ids[] = $main_image_id;
        }
        $all_ids = array_merge($all_ids, $gallery_ids);
        if (empty($all_ids)) {
            echo wc_placeholder_img();
            return;
        }

        $settings = $this->get_settings_for_display();
        $has_multiple_images = count($all_ids) > 1;
        $show_thumbs = $settings['show_thumbnails'] === 'yes';
        $thumb_pos = $settings['thumbnail_position'] ?? 'bottom';
        $wrapper_class = 'nv-pw-gallery';
        if ($show_thumbs && $has_multiple_images) {
            $wrapper_class .= ' nv-pw-gallery--has-thumbs';
        }
        if ($thumb_pos === 'left' && $show_thumbs) {
            $wrapper_class .= ' nv-pw-gallery--thumbs-left';
        }
        ?>
        <div class="<?php echo esc_attr($wrapper_class); ?>" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
            <div class="nv-pw-gallery__main" role="region" aria-label="Product images">
                <div class="nv-pw-gallery__track">
                    <?php foreach ($all_ids as $index => $img_id) :
                        $full = wp_get_attachment_image_url($img_id, 'woocommerce_single');
                        $alt = get_post_meta($img_id, '_wp_attachment_image_alt', true) ?: $product->get_name();
                        ?>
                        <div class="nv-pw-gallery__slide" data-index="<?php echo esc_attr($index); ?>">
                            <img src="<?php echo esc_url($full); ?>" alt="<?php echo esc_attr($alt); ?>" loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>" />
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($has_multiple_images) : ?>
                    <div class="nv-pw-gallery__dots">
                        <?php foreach ($all_ids as $i => $id) : ?>
                            <button class="nv-pw-gallery__dot<?php echo $i === 0 ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr($i); ?>" aria-label="Go to image <?php echo $i + 1; ?>" type="button"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($show_thumbs && $has_multiple_images) : ?>
                <div class="nv-pw-gallery__thumbs">
                    <?php foreach ($all_ids as $i => $img_id) :
                        $thumb = wp_get_attachment_image_url($img_id, 'thumbnail');
                        ?>
                        <button class="nv-pw-gallery__thumb<?php echo $i === 0 ? ' is-active' : ''; ?>" data-index="<?php echo esc_attr($i); ?>" type="button">
                            <img src="<?php echo esc_url($thumb); ?>" alt="" loading="lazy" />
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
