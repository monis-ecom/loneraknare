<?php
if (!defined('ABSPATH')) exit;

/**
 * NV: Reviews — displays real WooCommerce product reviews with a rating
 * summary (average + star-breakdown bars), verified-buyer badges, and
 * optional review photos (from a configurable comment-meta key).
 */
class NV_PW_Reviews extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-reviews'; }
    public function get_title(): string { return 'NV: Reviews'; }
    public function get_icon(): string { return 'eicon-review'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['reviews', 'recensioner', 'ratings', 'testimonials', 'stars', 'social proof', 'verified']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('product_id', [
            'label' => __('Product ID (0 = current product)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 0,
        ]);
        $this->add_control('heading', [
            'label' => __('Heading', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Vad kunderna säger', 'nv-product-widgets'),
        ]);
        $this->add_control('show_summary', [
            'label' => __('Show rating summary', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        $this->add_control('count', [
            'label' => __('Reviews to show', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 6, 'min' => 1, 'max' => 50,
        ]);
        $this->add_control('columns', [
            'label' => __('Columns', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '2',
            'options' => ['1' => '1', '2' => '2', '3' => '3'],
        ]);
        $this->add_control('orderby', [
            'label' => __('Sort by', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'date',
            'options' => [
                'date' => __('Newest', 'nv-product-widgets'),
                'rating_high' => __('Highest rated', 'nv-product-widgets'),
            ],
        ]);
        $this->add_control('min_rating', [
            'label' => __('Minimum rating to show', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '0',
            'options' => ['0' => __('All', 'nv-product-widgets'), '3' => __('3★ and up', 'nv-product-widgets'), '4' => __('4★ and up', 'nv-product-widgets'), '5' => __('5★ only', 'nv-product-widgets')],
        ]);
        $this->add_control('show_verified', [
            'label' => __('Show "verified buyer" badge', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        $this->add_control('image_meta_key', [
            'label' => __('Review photo meta key (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
            'description' => __('If your review importer stores photo URLs in a comment meta key, enter it here to show review photos.', 'nv-product-widgets'),
        ]);
        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('star_color', [
            'label' => __('Star color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#F5A623',
            'selectors' => ['{{WRAPPER}} .nv-pw-rv' => '--nv-rv-star: {{VALUE}};'],
        ]);
        $this->add_control('accent', [
            'label' => __('Accent (bars / verified)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#1D9E75',
            'selectors' => ['{{WRAPPER}} .nv-pw-rv' => '--nv-rv-accent: {{VALUE}};'],
        ]);
        $this->add_control('card_bg', [
            'label' => __('Card background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF',
            'selectors' => ['{{WRAPPER}} .nv-pw-rv' => '--nv-rv-card-bg: {{VALUE}};'],
        ]);
        $this->add_control('text_color', [
            'label' => __('Text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#1F2430',
            'selectors' => ['{{WRAPPER}} .nv-pw-rv' => '--nv-rv-text: {{VALUE}};'],
        ]);
        $this->end_controls_section();
    }

    private function stars(float $rating): string {
        $rating = max(0, min(5, $rating));
        $pct = ($rating / 5) * 100;
        return '<span class="nv-pw-rv__stars" role="img" aria-label="' . esc_attr(sprintf(__('%s of 5', 'nv-product-widgets'), number_format_i18n($rating, 1))) . '">'
            . '<span class="nv-pw-rv__stars-base">★★★★★</span>'
            . '<span class="nv-pw-rv__stars-fill" style="width:' . esc_attr((string) $pct) . '%">★★★★★</span></span>';
    }

    private function review_images(int $comment_id, string $key): array {
        if ($key === '') return [];
        $raw = get_comment_meta($comment_id, $key, true);
        $urls = [];
        if (is_array($raw)) {
            foreach ($raw as $v) { $v = is_array($v) ? ($v['url'] ?? '') : $v; if ($v) $urls[] = (string) $v; }
        } elseif (is_string($raw) && $raw !== '') {
            foreach (preg_split('/[,\s]+/', $raw) as $v) { if ($v) $urls[] = $v; }
        }
        return $urls;
    }

    protected function render(): void {
        if (!function_exists('wc_get_product')) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Reviews — WooCommerce required', '⭐');
            return;
        }
        $s = $this->get_settings_for_display();
        $pid = (int) ($s['product_id'] ?? 0);
        $product = $pid > 0 ? wc_get_product($pid) : NV_PW_Editor_Helper::get_preview_product();
        if (!($product instanceof \WC_Product)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Reviews — no product', '⭐');
            return;
        }
        $product_id = $product->get_id();

        $avg = (float) $product->get_average_rating();
        $total = (int) $product->get_review_count();
        $counts = (array) $product->get_rating_counts(); // [5=>n, 4=>n, ...]

        $heading = trim((string) ($s['heading'] ?? ''));
        $show_summary = (($s['show_summary'] ?? 'yes') === 'yes');
        $count = max(1, (int) ($s['count'] ?? 6));
        $cols = in_array(($s['columns'] ?? '2'), ['1', '2', '3'], true) ? (string) $s['columns'] : '2';
        $min_rating = (int) ($s['min_rating'] ?? 0);
        $show_verified = (($s['show_verified'] ?? 'yes') === 'yes');
        $img_key = trim((string) ($s['image_meta_key'] ?? ''));

        $args = [
            'post_id' => $product_id,
            'status'  => 'approve',
            'type'    => 'review',
            'number'  => $count,
            'orderby' => 'comment_date_gmt',
            'order'   => 'DESC',
        ];
        if (($s['orderby'] ?? 'date') === 'rating_high') {
            $args['meta_key'] = 'rating';
            $args['orderby']  = 'meta_value_num';
        }
        if ($min_rating > 0) {
            $args['meta_query'] = [['key' => 'rating', 'value' => $min_rating, 'compare' => '>=', 'type' => 'NUMERIC']];
        }
        $comments = get_comments($args);

        if (empty($comments) && $total === 0) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Reviews — no reviews yet', '⭐');
            return;
        }
        ?>
        <div class="nv-pw-rv" style="--nv-rv-cols: <?php echo esc_attr($cols); ?>;">
            <?php if ($heading !== '') : ?><h3 class="nv-pw-rv__heading<?php echo NV_PW_Headline::mod($s); ?>"><?php echo NV_PW_Headline::html($heading); ?></h3><?php endif; ?>

            <?php if ($show_summary && $total > 0) : ?>
                <div class="nv-pw-rv__summary">
                    <div class="nv-pw-rv__avg">
                        <span class="nv-pw-rv__avg-num"><?php echo esc_html(number_format_i18n($avg, 1)); ?></span>
                        <?php echo $this->stars($avg); // phpcs:ignore ?>
                        <span class="nv-pw-rv__avg-count"><?php echo esc_html(sprintf(_n('%s recension', '%s recensioner', $total, 'nv-product-widgets'), number_format_i18n($total))); ?></span>
                    </div>
                    <div class="nv-pw-rv__bars">
                        <?php for ($star = 5; $star >= 1; $star--) :
                            $n = (int) ($counts[$star] ?? 0);
                            $pct = $total > 0 ? round(($n / $total) * 100) : 0;
                            ?>
                            <div class="nv-pw-rv__bar-row">
                                <span class="nv-pw-rv__bar-label"><?php echo (int) $star; ?>★</span>
                                <span class="nv-pw-rv__bar"><span class="nv-pw-rv__bar-fill" style="width:<?php echo esc_attr((string) $pct); ?>%"></span></span>
                                <span class="nv-pw-rv__bar-n"><?php echo (int) $n; ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($comments)) : ?>
                <div class="nv-pw-rv__grid">
                    <?php foreach ($comments as $c) :
                        $rating = (float) get_comment_meta($c->comment_ID, 'rating', true);
                        $verified = $show_verified && function_exists('wc_review_is_from_verified_owner') && wc_review_is_from_verified_owner($c->comment_ID);
                        $imgs = $this->review_images((int) $c->comment_ID, $img_key);
                        ?>
                        <figure class="nv-pw-rv__card">
                            <?php if ($rating > 0) echo $this->stars($rating); // phpcs:ignore ?>
                            <blockquote class="nv-pw-rv__text"><?php echo esc_html(get_comment_text($c)); ?></blockquote>
                            <?php if (!empty($imgs)) : ?>
                                <div class="nv-pw-rv__photos">
                                    <?php foreach (array_slice($imgs, 0, 4) as $u) : ?>
                                        <img class="nv-pw-rv__photo" src="<?php echo esc_url($u); ?>" alt="" loading="lazy">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <figcaption class="nv-pw-rv__foot">
                                <span class="nv-pw-rv__author"><?php echo esc_html($c->comment_author ?: __('Verifierad kund', 'nv-product-widgets')); ?></span>
                                <?php if ($verified) : ?>
                                    <span class="nv-pw-rv__verified">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                        <?php esc_html_e('Verifierad köpare', 'nv-product-widgets'); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="nv-pw-rv__date"><?php echo esc_html(get_comment_date('', $c)); ?></span>
                            </figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
