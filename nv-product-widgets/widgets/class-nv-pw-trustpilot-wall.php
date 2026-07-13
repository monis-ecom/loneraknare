<?php
if (!defined('ABSPATH')) exit;

/**
 * NV: Trustpilot Wall — a Trustpilot-style social-proof section: an aggregate
 * rating header (label + score + green star blocks + review count) above a
 * continuously scrolling wall of review cards (green star blocks, bold headline,
 * review text, author + date). Themeable (light / dark / cream / blue / yellow)
 * with full colour control. Models section.store "Testimonials #16".
 */
class NV_PW_Trustpilot_Wall extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-trustpilot-wall'; }
    public function get_title(): string { return 'NV: Trustpilot Wall'; }
    public function get_icon(): string { return 'eicon-review'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['trustpilot', 'reviews', 'testimonials', 'wall', 'marquee', 'scrolling', 'stars', 'social proof', 'recensioner']; }

    protected function register_controls(): void {
        /* ── Header ── */
        $this->start_controls_section('section_head', ['label' => __('Header', 'nv-product-widgets')]);
        $this->add_control('headline', ['label' => __('Headline', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Our customers tell it better than we do!', 'nv-product-widgets'), 'label_block' => true]);
        $this->add_control('show_rating', ['label' => __('Show aggregate rating', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('rating_label', ['label' => __('Rating label', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Excellent', 'nv-product-widgets'), 'condition' => ['show_rating' => 'yes']]);
        $this->add_control('rating_value', ['label' => __('Rating (0–5)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'max' => 5, 'step' => 0.1, 'default' => 4.7, 'condition' => ['show_rating' => 'yes']]);
        $this->add_control('reviews_prefix', ['label' => __('Count prefix', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('based on', 'nv-product-widgets'), 'condition' => ['show_rating' => 'yes']]);
        $this->add_control('reviews_count', ['label' => __('Reviews count', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('1 067', 'nv-product-widgets'), 'condition' => ['show_rating' => 'yes']]);
        $this->add_control('reviews_suffix', ['label' => __('Count suffix', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('reviews', 'nv-product-widgets'), 'condition' => ['show_rating' => 'yes']]);
        $this->add_control('cta_text', ['label' => __('Button text (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('View All', 'nv-product-widgets')]);
        $this->add_control('cta_link', ['label' => __('Button link', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::URL, 'default' => ['url' => ''], 'condition' => ['cta_text!' => '']]);
        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        /* ── Reviews ── */
        $this->start_controls_section('section_reviews', ['label' => __('Reviews', 'nv-product-widgets')]);
        $r = new \Elementor\Repeater();
        $r->add_control('stars', ['label' => __('Stars', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '5', 'options' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5']]);
        $r->add_control('title', ['label' => __('Headline', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('I love it!', 'nv-product-widgets')]);
        $r->add_control('text', ['label' => __('Review text', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('I was initially hesitant to switch, but I am so glad I did. The quality is top-notch and it is incredibly efficient.', 'nv-product-widgets')]);
        $r->add_control('name', ['label' => __('Author name', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Cathrine', 'nv-product-widgets')]);
        $r->add_control('date', ['label' => __('Date / location', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('29 July', 'nv-product-widgets')]);
        $r->add_control('avatar', ['label' => __('Author image (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::MEDIA, 'media_types' => ['image']]);
        $this->add_control('items', [
            'label' => __('Reviews', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ title }}}',
            'default' => [
                ['stars' => '5', 'title' => __('Very effective', 'nv-product-widgets'), 'text' => __('Very effective and very convenient — no more cluttered bathroom counter. Cleans gently but thoroughly.', 'nv-product-widgets'), 'name' => __('Miguel', 'nv-product-widgets'), 'date' => __('27 August', 'nv-product-widgets')],
                ['stars' => '5', 'title' => __('I have very sensitive gums', 'nv-product-widgets'), 'text' => __('I have very sensitive gums that bleed with every brushing. I was skeptical about switching. The result? Thrilled!', 'nv-product-widgets'), 'name' => __('Barbara', 'nv-product-widgets'), 'date' => __('30 September', 'nv-product-widgets')],
                ['stars' => '5', 'title' => __('I adopted it!', 'nv-product-widgets'), 'text' => __('I have used another brand for years, but I am thrilled to have switched: softer yet more effective, quieter, and cleaner. Do not hesitate!', 'nv-product-widgets'), 'name' => __('Cathrine', 'nv-product-widgets'), 'date' => __('29 July', 'nv-product-widgets')],
                ['stars' => '5', 'title' => __('Very good product', 'nv-product-widgets'), 'text' => __('Very good product. I was skeptical at first because of the price, but it is indeed superb. Nice design, easy to use, very clever.', 'nv-product-widgets'), 'name' => __('Franck', 'nv-product-widgets'), 'date' => __('30 June', 'nv-product-widgets')],
                ['stars' => '5', 'title' => __('I love it! 😍', 'nv-product-widgets'), 'text' => __('At first I thought I would return it, but after a few days I got used to it and I am really glad I persisted. Best out there.', 'nv-product-widgets'), 'name' => __('Gerard', 'nv-product-widgets'), 'date' => __('30 June', 'nv-product-widgets')],
            ],
        ]);
        $this->end_controls_section();

        /* ── Layout ── */
        $this->start_controls_section('section_layout', ['label' => __('Layout', 'nv-product-widgets')]);
        $this->add_control('auto_scroll', ['label' => __('Continuous auto-scroll', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('direction', ['label' => __('Scroll direction', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'left', 'options' => ['left' => __('Left', 'nv-product-widgets'), 'right' => __('Right', 'nv-product-widgets')], 'condition' => ['auto_scroll' => 'yes']]);
        $this->add_control('speed', ['label' => __('Scroll duration (seconds)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 20, 'max' => 140]], 'default' => ['size' => 55, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-tp__track' => '--nv-tp-dur: {{SIZE}}s;'], 'condition' => ['auto_scroll' => 'yes']]);
        $this->add_control('columns', ['label' => __('Columns (when auto-scroll off)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '4', 'options' => ['2' => '2', '3' => '3', '4' => '4'], 'condition' => ['auto_scroll' => '']]);
        $this->add_control('card_width', ['label' => __('Card width', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 240, 'max' => 440]], 'default' => ['size' => 320, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-tp' => '--nv-tp-card-w: {{SIZE}}px;']]);
        $this->end_controls_section();

        /* ── Style ── */
        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('theme', ['label' => __('Theme', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'light', 'options' => [
            'light' => __('Light (white)', 'nv-product-widgets'),
            'dark' => __('Dark', 'nv-product-widgets'),
            'cream' => __('Cream / lilac', 'nv-product-widgets'),
            'blue' => __('Blue cards', 'nv-product-widgets'),
            'yellow' => __('Yellow retro', 'nv-product-widgets'),
        ]]);
        $this->add_control('green', ['label' => __('Trustpilot star color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-tp' => '--nv-tp-green: {{VALUE}};']]);
        $this->add_control('bg', ['label' => __('Section background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-tp' => '--nv-tp-bg: {{VALUE}};']]);
        $this->add_control('card_bg', ['label' => __('Card background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-tp' => '--nv-tp-card: {{VALUE}};']]);
        $this->add_control('heading_color', ['label' => __('Headline color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-tp' => '--nv-tp-heading: {{VALUE}};']]);
        $this->add_control('title_color', ['label' => __('Card headline color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-tp' => '--nv-tp-title: {{VALUE}};']]);
        $this->add_control('text_color', ['label' => __('Review text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-tp' => '--nv-tp-text: {{VALUE}};']]);
        $this->add_control('cta_bg', ['label' => __('Button background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-tp' => '--nv-tp-cta-bg: {{VALUE}};']]);
        $this->add_control('cta_color', ['label' => __('Button text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-tp' => '--nv-tp-cta-color: {{VALUE}};']]);
        $this->end_controls_section();
    }

    /** Green Trustpilot-style star blocks with fractional fill. */
    private function blocks_html(float $rating, string $size): string {
        $rating = max(0, min(5, $rating));
        $pct = ($rating / 5) * 100;
        $star = '<svg viewBox="0 0 24 24" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
        $tiles = '';
        for ($i = 0; $i < 5; $i++) $tiles .= '<span class="nv-pw-tp__tile">' . $star . '</span>';
        $out  = '<span class="nv-pw-tp__blocks nv-pw-tp__blocks--' . esc_attr($size) . '" style="--nv-tp-fill:' . esc_attr((string) $pct) . '%">';
        $out .= '<span class="nv-pw-tp__blocks-base" aria-hidden="true">' . $tiles . '</span>';
        $out .= '<span class="nv-pw-tp__blocks-fill" aria-hidden="true">' . $tiles . '</span>';
        return $out . '</span>';
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $rows = is_array($s['items'] ?? null) ? $s['items'] : [];
        $items = [];
        foreach ($rows as $it) {
            if (is_array($it) && (trim((string) ($it['text'] ?? '')) !== '' || trim((string) ($it['title'] ?? '')) !== '')) $items[] = $it;
        }
        if (empty($items)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Trustpilot Wall', '🟢');
            return;
        }

        $theme = in_array(($s['theme'] ?? 'light'), ['light', 'dark', 'cream', 'blue', 'yellow'], true) ? (string) $s['theme'] : 'light';
        $auto = (($s['auto_scroll'] ?? 'yes') === 'yes');
        $direction = ($s['direction'] ?? 'left') === 'right' ? 'right' : 'left';
        $cols = in_array(($s['columns'] ?? '4'), ['2', '3', '4'], true) ? (string) $s['columns'] : '4';

        $headline = trim((string) ($s['headline'] ?? ''));
        $show_rating = (($s['show_rating'] ?? 'yes') === 'yes');
        $rating_label = trim((string) ($s['rating_label'] ?? ''));
        $rating_value = (float) ($s['rating_value'] ?? 4.7);
        $reviews_prefix = trim((string) ($s['reviews_prefix'] ?? ''));
        $reviews_count = trim((string) ($s['reviews_count'] ?? ''));
        $reviews_suffix = trim((string) ($s['reviews_suffix'] ?? ''));

        $cta_text = trim((string) ($s['cta_text'] ?? ''));
        $cta_url = isset($s['cta_link']['url']) ? (string) $s['cta_link']['url'] : '';
        $cta_target = !empty($s['cta_link']['is_external']) ? ' target="_blank" rel="noopener"' : '';

        // Trim the fractional score for display (4.7 not 4.700000).
        $rating_disp = rtrim(rtrim(number_format($rating_value, 1), '0'), '.');

        $render_items = $auto ? array_merge($items, $items) : $items;
        ?>
        <section class="nv-pw-tp nv-pw-tp--<?php echo esc_attr($theme); ?>" style="--nv-tp-cols: <?php echo esc_attr($cols); ?>;">
            <?php if ($headline !== '' || $show_rating) : ?>
                <div class="nv-pw-tp__head">
                    <?php if ($headline !== '') : ?><h2 class="nv-pw-tp__headline<?php echo NV_PW_Headline::mod($s); ?>"><?php echo NV_PW_Headline::html($headline); ?></h2><?php endif; ?>
                    <?php if ($show_rating) : ?>
                        <div class="nv-pw-tp__rating">
                            <?php if ($rating_label !== '') : ?><span class="nv-pw-tp__rating-label"><?php echo esc_html($rating_label); ?></span><?php endif; ?>
                            <span class="nv-pw-tp__rating-score"><?php echo esc_html($rating_disp); ?> / 5</span>
                            <?php echo $this->blocks_html($rating_value, 'lg'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                        <?php if ($reviews_count !== '' || $reviews_prefix !== '' || $reviews_suffix !== '') : ?>
                            <p class="nv-pw-tp__count"><?php
                                if ($reviews_prefix !== '') echo esc_html($reviews_prefix) . ' ';
                                if ($reviews_count !== '') echo '<strong>' . esc_html($reviews_count) . '</strong> ';
                                if ($reviews_suffix !== '') echo esc_html($reviews_suffix);
                            ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="nv-pw-tp__wall<?php echo $auto ? ' nv-pw-tp__wall--marquee nv-pw-tp__wall--' . esc_attr($direction) : ' nv-pw-tp__wall--grid'; ?>">
                <div class="nv-pw-tp__track">
                    <?php foreach ($render_items as $it) :
                        $stars = (int) ($it['stars'] ?? 5);
                        $title = trim((string) ($it['title'] ?? ''));
                        $text = trim((string) ($it['text'] ?? ''));
                        $name = trim((string) ($it['name'] ?? ''));
                        $date = trim((string) ($it['date'] ?? ''));
                        $avatar = isset($it['avatar']['url']) ? (string) $it['avatar']['url'] : '';
                        ?>
                        <figure class="nv-pw-tp__card">
                            <?php echo $this->blocks_html((float) $stars, 'sm'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php if ($title !== '') : ?><strong class="nv-pw-tp__title"><?php echo esc_html($title); ?></strong><?php endif; ?>
                            <?php if ($text !== '') : ?><blockquote class="nv-pw-tp__text"><?php echo esc_html($text); ?></blockquote><?php endif; ?>
                            <?php if ($name !== '' || $avatar !== '') : ?>
                                <figcaption class="nv-pw-tp__author">
                                    <?php if ($avatar !== '') : ?><img class="nv-pw-tp__avatar" src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy"><?php endif; ?>
                                    <span class="nv-pw-tp__who">
                                        <?php
                                        echo esc_html($name);
                                        if ($name !== '' && $date !== '') echo ' – ';
                                        if ($date !== '') echo esc_html($date);
                                        ?>
                                    </span>
                                </figcaption>
                            <?php endif; ?>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($cta_text !== '') : ?>
                <div class="nv-pw-tp__foot"><a class="nv-pw-tp__cta" href="<?php echo esc_url($cta_url !== '' ? $cta_url : '#'); ?>"<?php echo $cta_target; ?>><?php echo esc_html($cta_text); ?></a></div>
            <?php endif; ?>
        </section>
        <?php
    }
}
