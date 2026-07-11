<?php
if (!defined('ABSPATH')) exit;

/**
 * NV: Review Wall — a full testimonials section: eyebrow badge, headline with an
 * accent word, subheading, CTA, and a continuously auto-scrolling wall of rich
 * review cards (avatar, name, role, stars, text with an optional highlighted phrase).
 * Themeable (light / dark / soft) with full color control.
 */
class NV_PW_Review_Wall extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-review-wall'; }
    public function get_title(): string { return 'NV: Review Wall'; }
    public function get_icon(): string { return 'eicon-testimonial-carousel'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['reviews', 'testimonials', 'wall', 'marquee', 'auto scroll', 'recensioner', 'social proof']; }

    protected function register_controls(): void {
        /* ── Header ── */
        $this->start_controls_section('section_head', ['label' => __('Header', 'nv-product-widgets')]);
        $this->add_control('eyebrow', ['label' => __('Eyebrow badge', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('KUNDOMDÖMEN', 'nv-product-widgets')]);
        $this->add_control('headline_1', ['label' => __('Headline (before accent)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Därför älskar spelare', 'nv-product-widgets'), 'label_block' => true]);
        $this->add_control('headline_accent', ['label' => __('Headline accent word', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('PadelFlex™', 'nv-product-widgets'), 'label_block' => true]);
        $this->add_control('headline_2', ['label' => __('Headline (after accent)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('subheading', ['label' => __('Subheading', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Här är vad de säger.', 'nv-product-widgets'), 'label_block' => true]);
        $this->add_control('cta_text', ['label' => __('Button text (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('cta_link', ['label' => __('Button link', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::URL, 'default' => ['url' => ''], 'condition' => ['cta_text!' => '']]);
        $this->add_control('rating_heading', ['label' => __('Aggregate rating bar', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('show_rating', ['label' => __('Show rating bar', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('rating_value', ['label' => __('Rating (0–5)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'min' => 0, 'max' => 5, 'step' => 0.1, 'default' => 4.9, 'condition' => ['show_rating' => 'yes']]);
        $this->add_control('rating_text', ['label' => __('Rating label', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('4,9/5 · baserat på 9 000+ recensioner', 'nv-product-widgets'), 'label_block' => true, 'condition' => ['show_rating' => 'yes']]);
        $this->add_control('rating_position', ['label' => __('Rating bar position', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'top', 'options' => ['top' => __('Above headline', 'nv-product-widgets'), 'bottom' => __('Below subheading', 'nv-product-widgets')], 'condition' => ['show_rating' => 'yes']]);
        $this->add_control('align', [
            'label' => __('Header alignment', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::CHOOSE,
            'options' => [
                'left' => ['title' => __('Left', 'nv-product-widgets'), 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Center', 'nv-product-widgets'), 'icon' => 'eicon-text-align-center'],
            ],
            'default' => 'center',
            'selectors' => ['{{WRAPPER}} .nv-pw-rw__head' => 'text-align: {{VALUE}};'],
        ]);
        $this->end_controls_section();

        /* ── Reviews ── */
        $this->start_controls_section('section_reviews', ['label' => __('Reviews', 'nv-product-widgets')]);
        $r = new \Elementor\Repeater();
        $r->add_control('avatar', ['label' => __('Author image', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::MEDIA, 'media_types' => ['image']]);
        $r->add_control('name', ['label' => __('Author name', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Anna Lind', 'nv-product-widgets')]);
        $r->add_control('role', ['label' => __('Role / location', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Verifierad köpare', 'nv-product-widgets')]);
        $r->add_control('stars', ['label' => __('Stars', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '5', 'options' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5']]);
        $r->add_control('text', ['label' => __('Review text', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('Bästa köpet i år. Jag spelar smärtfritt igen efter bara två veckor.', 'nv-product-widgets')]);
        $r->add_control('highlight', ['label' => __('Highlight phrase (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '', 'description' => __('If this exact phrase appears in the text above, it gets a highlight background.', 'nv-product-widgets')]);
        $this->add_control('items', [
            'label' => __('Reviews', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ name }}}',
            'default' => [
                ['name' => __('Sarah Jenkins', 'nv-product-widgets'), 'role' => __('Verifierad köpare', 'nv-product-widgets'), 'stars' => '5', 'text' => __('Jag var skeptisk först, men efter 14 dagar kan jag inte tänka mig att spela utan dem.', 'nv-product-widgets'), 'highlight' => __('kan jag inte tänka mig', 'nv-product-widgets')],
                ['name' => __('Marcus Thorne', 'nv-product-widgets'), 'role' => __('Padelspelare, Stockholm', 'nv-product-widgets'), 'stars' => '5', 'text' => __('Perfekt balans mellan stöd och komfort. Rekommenderas verkligen.', 'nv-product-widgets'), 'highlight' => __('Perfekt balans', 'nv-product-widgets')],
                ['name' => __('David Chen', 'nv-product-widgets'), 'role' => __('Verifierad köpare', 'nv-product-widgets'), 'stars' => '5', 'text' => __('Sällan lämnar jag recensioner, men de här förtjänar det. Fantastisk produkt.', 'nv-product-widgets'), 'highlight' => __('förtjänar det', 'nv-product-widgets')],
                ['name' => __('Elena Rodriguez', 'nv-product-widgets'), 'role' => __('Klubbspelare', 'nv-product-widgets'), 'stars' => '5', 'text' => __('Skillnaden märktes redan första matchen. Total game changer.', 'nv-product-widgets'), 'highlight' => __('första matchen', 'nv-product-widgets')],
            ],
        ]);
        $this->end_controls_section();

        /* ── Layout ── */
        $this->start_controls_section('section_layout', ['label' => __('Layout', 'nv-product-widgets')]);
        $this->add_control('auto_scroll', ['label' => __('Continuous auto-scroll', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('direction', ['label' => __('Scroll direction', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'left', 'options' => ['left' => __('Left', 'nv-product-widgets'), 'right' => __('Right', 'nv-product-widgets')], 'condition' => ['auto_scroll' => 'yes']]);
        $this->add_control('speed', ['label' => __('Scroll duration (seconds)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 20, 'max' => 140]], 'default' => ['size' => 60, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-rw__track' => '--nv-rw-dur: {{SIZE}}s;'], 'condition' => ['auto_scroll' => 'yes']]);
        $this->add_control('columns', ['label' => __('Columns (when auto-scroll off)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '4', 'options' => ['2' => '2', '3' => '3', '4' => '4'], 'condition' => ['auto_scroll' => '']]);
        $this->add_control('card_width', ['label' => __('Card width', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 240, 'max' => 460]], 'default' => ['size' => 320, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-rw' => '--nv-rw-card-w: {{SIZE}}px;']]);
        $this->end_controls_section();

        /* ── Style ── */
        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('theme', ['label' => __('Theme', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'light', 'options' => ['light' => __('Light', 'nv-product-widgets'), 'dark' => __('Dark', 'nv-product-widgets'), 'soft' => __('Soft (tinted)', 'nv-product-widgets')]]);
        $this->add_control('accent', ['label' => __('Accent (badge / accent word / button)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-rw' => '--nv-rw-accent: {{VALUE}};']]);
        $this->add_control('star_color', ['label' => __('Star color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-rw' => '--nv-rw-star: {{VALUE}};']]);
        $this->add_control('highlight_color', ['label' => __('Highlight background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-rw' => '--nv-rw-hl: {{VALUE}};']]);
        $this->add_control('bg', ['label' => __('Section background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-rw' => '--nv-rw-bg: {{VALUE}};']]);
        $this->add_control('card_bg', ['label' => __('Card background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-rw' => '--nv-rw-card: {{VALUE}};']]);
        $this->add_control('text_color', ['label' => __('Review text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-rw' => '--nv-rw-text: {{VALUE}};']]);
        $this->add_control('name_color', ['label' => __('Author name color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-rw' => '--nv-rw-name: {{VALUE}};']]);
        $this->add_control('heading_color', ['label' => __('Headline color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-rw' => '--nv-rw-heading: {{VALUE}};']]);
        $this->end_controls_section();
    }

    private function stars_html(int $count): string {
        $count = max(0, min(5, $count));
        $out = '<span class="nv-pw-rw__stars" aria-label="' . esc_attr(sprintf(__('%d/5', 'nv-product-widgets'), $count)) . '">';
        for ($i = 1; $i <= 5; $i++) {
            $on = $i <= $count ? ' is-on' : '';
            $out .= '<svg class="nv-pw-rw__star' . $on . '" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
        }
        return $out . '</span>';
    }

    private function rating_bar_html(float $rating, string $text): string {
        $rating = max(0, min(5, $rating));
        $pct = ($rating / 5) * 100;
        $out = '<div class="nv-pw-rw__rating">';
        $out .= '<span class="nv-pw-rw__rating-stars" role="img" aria-label="' . esc_attr($text !== '' ? $text : sprintf(__('%s of 5', 'nv-product-widgets'), $rating)) . '" style="--nv-rw-fill:' . esc_attr((string) $pct) . '%">';
        $out .= '<span class="nv-pw-rw__rating-base">★★★★★</span><span class="nv-pw-rw__rating-fill" aria-hidden="true">★★★★★</span></span>';
        if ($text !== '') $out .= '<span class="nv-pw-rw__rating-text">' . esc_html($text) . '</span>';
        return $out . '</div>';
    }

    private function text_html(string $text, string $highlight): string {
        $safe = esc_html($text);
        $highlight = trim($highlight);
        if ($highlight !== '') {
            $safe_hl = esc_html($highlight);
            $safe = str_ireplace($safe_hl, '<mark class="nv-pw-rw__hl">' . $safe_hl . '</mark>', $safe);
        }
        return $safe;
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $rows = is_array($s['items'] ?? null) ? $s['items'] : [];
        $items = [];
        foreach ($rows as $it) {
            if (is_array($it) && trim((string) ($it['text'] ?? '')) !== '') $items[] = $it;
        }
        if (empty($items)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Review Wall', '💬');
            return;
        }

        $theme = in_array(($s['theme'] ?? 'light'), ['light', 'dark', 'soft'], true) ? (string) $s['theme'] : 'light';
        $auto = (($s['auto_scroll'] ?? 'yes') === 'yes');
        $direction = ($s['direction'] ?? 'left') === 'right' ? 'right' : 'left';
        $cols = in_array(($s['columns'] ?? '4'), ['2', '3', '4'], true) ? (string) $s['columns'] : '4';

        $eyebrow = trim((string) ($s['eyebrow'] ?? ''));
        $h1 = trim((string) ($s['headline_1'] ?? ''));
        $accent = trim((string) ($s['headline_accent'] ?? ''));
        $h2 = trim((string) ($s['headline_2'] ?? ''));
        $sub = trim((string) ($s['subheading'] ?? ''));

        $show_rating = (($s['show_rating'] ?? 'yes') === 'yes');
        $rating_pos = ($s['rating_position'] ?? 'top') === 'bottom' ? 'bottom' : 'top';
        $rating_bar = $show_rating ? $this->rating_bar_html((float) ($s['rating_value'] ?? 4.9), trim((string) ($s['rating_text'] ?? ''))) : '';
        $cta_text = trim((string) ($s['cta_text'] ?? ''));
        $cta_url = isset($s['cta_link']['url']) ? (string) $s['cta_link']['url'] : '';
        $cta_target = !empty($s['cta_link']['is_external']) ? ' target="_blank" rel="noopener"' : '';

        $render_items = $auto ? array_merge($items, $items) : $items;
        ?>
        <section class="nv-pw-rw nv-pw-rw--<?php echo esc_attr($theme); ?>" style="--nv-rw-cols: <?php echo esc_attr($cols); ?>;">
            <?php if ($eyebrow !== '' || $h1 !== '' || $accent !== '' || $sub !== '' || $cta_text !== '' || $rating_bar !== '') : ?>
                <div class="nv-pw-rw__head">
                    <?php if ($eyebrow !== '') : ?><span class="nv-pw-rw__eyebrow"><?php echo esc_html($eyebrow); ?></span><?php endif; ?>
                    <?php if ($rating_bar !== '' && $rating_pos === 'top') echo $rating_bar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php if ($h1 !== '' || $accent !== '') : ?>
                        <h2 class="nv-pw-rw__headline"><?php
                            echo esc_html($h1);
                            if ($accent !== '') echo ($h1 !== '' ? ' ' : '') . '<span class="nv-pw-rw__accent">' . esc_html($accent) . '</span>';
                            if ($h2 !== '') echo ' ' . esc_html($h2);
                        ?></h2>
                    <?php endif; ?>
                    <?php if ($sub !== '') : ?><p class="nv-pw-rw__sub"><?php echo esc_html($sub); ?></p><?php endif; ?>
                    <?php if ($rating_bar !== '' && $rating_pos === 'bottom') echo $rating_bar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php if ($cta_text !== '') : ?><a class="nv-pw-rw__cta" href="<?php echo esc_url($cta_url !== '' ? $cta_url : '#'); ?>"<?php echo $cta_target; ?>><?php echo esc_html($cta_text); ?></a><?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="nv-pw-rw__wall<?php echo $auto ? ' nv-pw-rw__wall--marquee nv-pw-rw__wall--' . esc_attr($direction) : ' nv-pw-rw__wall--grid'; ?>">
                <div class="nv-pw-rw__track">
                    <?php foreach ($render_items as $it) :
                        $name = trim((string) ($it['name'] ?? ''));
                        $role = trim((string) ($it['role'] ?? ''));
                        $stars = (int) ($it['stars'] ?? 5);
                        $avatar = isset($it['avatar']['url']) ? (string) $it['avatar']['url'] : '';
                        $initial = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '★';
                        ?>
                        <figure class="nv-pw-rw__card">
                            <div class="nv-pw-rw__author">
                                <?php if ($avatar !== '') : ?>
                                    <img class="nv-pw-rw__avatar" src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
                                <?php else : ?>
                                    <span class="nv-pw-rw__avatar nv-pw-rw__avatar--initial" aria-hidden="true"><?php echo esc_html($initial); ?></span>
                                <?php endif; ?>
                                <span class="nv-pw-rw__who">
                                    <?php if ($name !== '') : ?><strong class="nv-pw-rw__name"><?php echo esc_html($name); ?></strong><?php endif; ?>
                                    <?php if ($role !== '') : ?><span class="nv-pw-rw__role"><?php echo esc_html($role); ?></span><?php endif; ?>
                                </span>
                            </div>
                            <?php echo $this->stars_html($stars); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <blockquote class="nv-pw-rw__text"><?php echo $this->text_html((string) ($it['text'] ?? ''), (string) ($it['highlight'] ?? '')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></blockquote>
                        </figure>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }
}
