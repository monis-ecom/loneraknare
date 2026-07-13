<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Testimonials extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-testimonials'; }
    public function get_title(): string { return 'NV: Testimonials'; }
    public function get_icon(): string { return 'eicon-testimonial'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['testimonials', 'reviews', 'recensioner', 'social proof', 'stars', 'omdomen']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('Content', 'nv-product-widgets'),
        ]);

        $this->add_control('heading', [
            'label' => __('Heading', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Vad våra kunder säger', 'nv-product-widgets'),
        ]);
        $this->add_control('subheading', [
            'label' => __('Subheading (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => '',
        ]);
        $this->add_control('columns', [
            'label' => __('Columns', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '3',
            'options' => ['1' => '1', '2' => '2', '3' => '3'],
        ]);

        $this->add_control('layout', [
            'label' => __('Layout', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'grid',
            'options' => [
                'grid' => __('Grid of cards', 'nv-product-widgets'),
                'carousel' => __('Carousel (swipe + arrows)', 'nv-product-widgets'),
                'marquee' => __('Marquee (continuous auto-scroll)', 'nv-product-widgets'),
                'header' => __('Rating header + cards', 'nv-product-widgets'),
                'single' => __('Single big quote', 'nv-product-widgets'),
                'compact' => __('Compact cards', 'nv-product-widgets'),
            ],
        ]);
        $this->add_control('agg_score', [
            'label' => __('Aggregate score', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '4.8',
            'condition' => ['layout' => 'header'],
        ]);
        $this->add_control('agg_count', [
            'label' => __('Reviews count text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('baserat på 2 400+ recensioner', 'nv-product-widgets'),
            'condition' => ['layout' => 'header'],
        ]);
        $this->add_control('marquee_speed', [
            'label' => __('Auto-scroll duration (seconds)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 15, 'max' => 120]],
            'default' => ['size' => 45, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-tm__marquee-track' => '--nv-tm-marq-dur: {{SIZE}}s;'],
            'condition' => ['layout' => 'marquee'],
        ]);
        $this->add_control('marquee_width', [
            'label' => __('Card width (marquee)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 220, 'max' => 460]],
            'default' => ['size' => 330, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-tm--L-marquee' => '--nv-tm-marq-w: {{SIZE}}px;'],
            'condition' => ['layout' => 'marquee'],
        ]);

        $this->add_control('carousel_heading', [
            'label' => __('Carousel options', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['layout' => 'carousel'],
        ]);
        $this->add_control('show_arrows', [
            'label' => __('Show arrows', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
            'condition' => ['layout' => 'carousel'],
        ]);
        $this->add_control('show_dots', [
            'label' => __('Show dots', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
            'condition' => ['layout' => 'carousel'],
        ]);
        $this->add_control('autoplay', [
            'label' => __('Autoplay', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => '',
            'condition' => ['layout' => 'carousel'],
        ]);
        $this->add_control('autoplay_speed', [
            'label' => __('Autoplay delay (seconds)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 5,
            'min' => 2, 'max' => 20, 'step' => 1,
            'condition' => ['layout' => 'carousel', 'autoplay' => 'yes'],
        ]);
        $this->add_responsive_control('card_width', [
            'label' => __('Card width', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 220, 'max' => 480]],
            'default' => ['size' => 320, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-tm' => '--nv-tm-card-w: {{SIZE}}{{UNIT}};'],
            'condition' => ['layout' => 'carousel'],
        ]);

        $repeater = new \Elementor\Repeater();
        $repeater->add_control('stars', [
            'label' => __('Stars', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '5',
            'options' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'],
        ]);
        $repeater->add_control('quote', [
            'label' => __('Quote', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Bästa köpet jag gjort i år – fungerade direkt och känns gediget.', 'nv-product-widgets'),
        ]);
        $repeater->add_control('name', [
            'label' => __('Name', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Anna L.', 'nv-product-widgets'),
        ]);
        $repeater->add_control('meta', [
            'label' => __('Meta / role', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Verifierad köpare', 'nv-product-widgets'),
        ]);
        $repeater->add_control('avatar', [
            'label' => __('Avatar (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => ['url' => ''],
        ]);
        $repeater->add_control('verified', [
            'label' => __('Verified badge', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('items', [
            'label' => __('Testimonials', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => '{{{ name }}}',
            'default' => [
                ['stars' => '5', 'quote' => __('Bästa köpet jag gjort i år – fungerade direkt och känns gediget.', 'nv-product-widgets'), 'name' => __('Anna L.', 'nv-product-widgets'), 'meta' => __('Verifierad köpare', 'nv-product-widgets'), 'verified' => 'yes'],
                ['stars' => '5', 'quote' => __('Snabb leverans och precis som beskrivet. Rekommenderas verkligen!', 'nv-product-widgets'), 'name' => __('Johan M.', 'nv-product-widgets'), 'meta' => __('Verifierad köpare', 'nv-product-widgets'), 'verified' => 'yes'],
                ['stars' => '5', 'quote' => __('Kvaliteten överträffade förväntningarna. Köper igen.', 'nv-product-widgets'), 'name' => __('Sara K.', 'nv-product-widgets'), 'meta' => __('Verifierad köpare', 'nv-product-widgets'), 'verified' => 'yes'],
            ],
        ]);

        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        $this->start_controls_section('section_style', [
            'label' => __('Style', 'nv-product-widgets'),
            'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control('card_bg', [
            'label' => __('Card background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#FFFFFF',
            'selectors' => ['{{WRAPPER}} .nv-pw-tm' => '--nv-tm-card-bg: {{VALUE}};'],
        ]);
        $this->add_control('text_color', [
            'label' => __('Text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#1F2430',
            'selectors' => ['{{WRAPPER}} .nv-pw-tm' => '--nv-tm-text: {{VALUE}};'],
        ]);
        $this->add_control('star_color', [
            'label' => __('Star color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#F5A623',
            'selectors' => ['{{WRAPPER}} .nv-pw-tm' => '--nv-tm-star: {{VALUE}};'],
        ]);
        $this->add_control('carousel_accent', [
            'label' => __('Carousel accent (arrows / active dot)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#312E81',
            'selectors' => ['{{WRAPPER}} .nv-pw-tm' => '--nv-tm-accent: {{VALUE}};'],
            'condition' => ['layout' => 'carousel'],
        ]);
        $this->end_controls_section();
    }

    private function stars_html(int $count): string {
        $count = max(0, min(5, $count));
        $out = '<div class="nv-pw-tm__stars" aria-label="' . esc_attr(sprintf(__('%d av 5 stjärnor', 'nv-product-widgets'), $count)) . '">';
        for ($i = 1; $i <= 5; $i++) {
            $cls = $i <= $count ? ' is-on' : '';
            $out .= '<svg class="nv-pw-tm__star' . $cls . '" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
        }
        $out .= '</div>';
        return $out;
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $rows = is_array($s['items'] ?? null) ? $s['items'] : [];
        $items = [];
        foreach ($rows as $r) {
            if (!is_array($r)) continue;
            $quote = trim((string) ($r['quote'] ?? ''));
            if ($quote === '') continue;
            $items[] = $r;
        }
        if (empty($items)) {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Testimonials', '⭐');
            }
            return;
        }

        $heading = trim((string) ($s['heading'] ?? ''));
        $subheading = trim((string) ($s['subheading'] ?? ''));
        $cols = in_array(($s['columns'] ?? '3'), ['1', '2', '3'], true) ? (string) $s['columns'] : '3';
        $layout = in_array(($s['layout'] ?? 'grid'), ['grid', 'carousel', 'marquee', 'header', 'single', 'compact'], true) ? (string) $s['layout'] : 'grid';
        $is_carousel = ($layout === 'carousel');
        $is_marquee = ($layout === 'marquee');
        if ($is_carousel && function_exists('wp_enqueue_script')) {
            wp_enqueue_script('nv-testimonials');
        }
        $render_items = $is_marquee ? array_merge($items, $items) : $items;
        $agg_score = trim((string) ($s['agg_score'] ?? ''));
        $agg_count = trim((string) ($s['agg_count'] ?? ''));

        $show_arrows = $is_carousel && (($s['show_arrows'] ?? 'yes') === 'yes');
        $show_dots   = $is_carousel && (($s['show_dots'] ?? 'yes') === 'yes');
        $autoplay    = $is_carousel && (($s['autoplay'] ?? '') === 'yes');
        $autoplay_ms = max(2, (int) ($s['autoplay_speed'] ?? 5)) * 1000;

        $carousel_attrs = '';
        if ($is_carousel) {
            $carousel_attrs = ' data-nv-tm-carousel';
            if ($autoplay) {
                $carousel_attrs .= ' data-nv-tm-autoplay="1" data-nv-tm-interval="' . esc_attr((string) $autoplay_ms) . '"';
            }
        }
        ?>
        <div class="nv-pw-tm nv-pw-tm--L-<?php echo esc_attr($layout); ?>" style="--nv-tm-cols: <?php echo esc_attr($cols); ?>;"<?php echo $carousel_attrs; ?>>
            <?php if ($layout === 'header' && ($agg_score !== '' || $agg_count !== '')) : ?>
                <div class="nv-pw-tm__agg">
                    <span class="nv-pw-tm__agg-score"><?php echo esc_html($agg_score); ?></span>
                    <span class="nv-pw-tm__stars nv-pw-tm__agg-stars" aria-hidden="true"><?php for ($z = 0; $z < 5; $z++) : ?><svg class="nv-pw-tm__star is-on" viewBox="0 0 24 24" width="18" height="18"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><?php endfor; ?></span>
                    <?php if ($agg_count !== '') : ?><span class="nv-pw-tm__agg-count"><?php echo esc_html($agg_count); ?></span><?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($heading !== '' || $subheading !== '') : ?>
                <div class="nv-pw-tm__head">
                    <?php if ($heading !== '') : ?><h3 class="nv-pw-tm__heading<?php echo NV_PW_Headline::mod($s); ?>"><?php echo NV_PW_Headline::html($heading); ?></h3><?php endif; ?>
                    <?php if ($subheading !== '') : ?><p class="nv-pw-tm__sub"><?php echo esc_html($subheading); ?></p><?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($is_carousel) : ?>
            <div class="nv-pw-tm__carousel">
                <?php if ($show_arrows) : ?>
                <button type="button" class="nv-pw-tm__arrow nv-pw-tm__arrow--prev" data-nv-tm-prev aria-label="<?php echo esc_attr__('Previous reviews', 'nv-product-widgets'); ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($is_marquee) : ?>
            <div class="nv-pw-tm__marquee">
                <div class="nv-pw-tm__marquee-track">
            <?php else : ?>
            <div class="nv-pw-tm__grid<?php echo $is_carousel ? ' nv-pw-tm__grid--carousel' : ''; ?>"<?php echo $is_carousel ? ' data-nv-tm-track tabindex="0"' : ''; ?>>
            <?php endif; ?>
                <?php foreach ($render_items as $it) :
                    $stars = (int) ($it['stars'] ?? 5);
                    $name = trim((string) ($it['name'] ?? ''));
                    $meta = trim((string) ($it['meta'] ?? ''));
                    $avatar = isset($it['avatar']['url']) ? (string) $it['avatar']['url'] : '';
                    $verified = (($it['verified'] ?? 'yes') === 'yes');
                    $initial = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '★';
                    ?>
                    <figure class="nv-pw-tm__card">
                        <?php echo $this->stars_html($stars); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <blockquote class="nv-pw-tm__quote"><?php echo esc_html((string) ($it['quote'] ?? '')); ?></blockquote>
                        <figcaption class="nv-pw-tm__foot">
                            <?php if ($avatar !== '') : ?>
                                <img class="nv-pw-tm__avatar" src="<?php echo esc_url($avatar); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
                            <?php else : ?>
                                <span class="nv-pw-tm__avatar nv-pw-tm__avatar--initial" aria-hidden="true"><?php echo esc_html($initial); ?></span>
                            <?php endif; ?>
                            <span class="nv-pw-tm__who">
                                <?php if ($name !== '') : ?><strong class="nv-pw-tm__name"><?php echo esc_html($name); ?></strong><?php endif; ?>
                                <?php if ($meta !== '' || $verified) : ?>
                                    <span class="nv-pw-tm__meta">
                                        <?php if ($verified) : ?><svg class="nv-pw-tm__check" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg><?php endif; ?>
                                        <?php echo esc_html($meta); ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>
            <?php if ($is_marquee) : ?>
                </div>
            </div>
            <?php else : ?>
            </div>
            <?php endif; ?>
            <?php if ($is_carousel) : ?>
                <?php if ($show_arrows) : ?>
                <button type="button" class="nv-pw-tm__arrow nv-pw-tm__arrow--next" data-nv-tm-next aria-label="<?php echo esc_attr__('Next reviews', 'nv-product-widgets'); ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
                <?php endif; ?>
            </div>
            <?php if ($show_dots) : ?>
            <div class="nv-pw-tm__dots" data-nv-tm-dots role="tablist" aria-label="<?php echo esc_attr__('Reviews pagination', 'nv-product-widgets'); ?>">
                <?php foreach ($items as $di => $it) : ?>
                    <button type="button" class="nv-pw-tm__dot<?php echo $di === 0 ? ' is-active' : ''; ?>" data-nv-tm-dot="<?php echo (int) $di; ?>" aria-label="<?php echo esc_attr(sprintf(__('Go to review %d', 'nv-product-widgets'), $di + 1)); ?>"></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}
