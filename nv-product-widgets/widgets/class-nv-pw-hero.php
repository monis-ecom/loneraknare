<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Hero extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-hero'; }
    public function get_title(): string { return 'NV: Hero Banner'; }
    public function get_icon(): string { return 'eicon-banner'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['hero', 'banner', 'header', 'cta', 'landing']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);

        $this->add_control('layout', [
            'label' => __('Layout', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'split',
            'options' => [
                'split' => __('Split (image beside text)', 'nv-product-widgets'),
                'centered' => __('Centered (image below)', 'nv-product-widgets'),
                'overlay' => __('Overlay (text over image)', 'nv-product-widgets'),
                'minimal' => __('Minimal (no image)', 'nv-product-widgets'),
            ],
        ]);

        $this->add_control('eyebrow', [
            'label' => __('Overline (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('NU PÅ REA', 'nv-product-widgets'),
        ]);
        $this->add_control('headline', [
            'label' => __('Headline', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Mindre stress.', 'nv-product-widgets'),
            'label_block' => true,
        ]);
        $this->add_control('headline_highlight', [
            'label' => __('Headline highlight (accent color)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Bättre sömn.', 'nv-product-widgets'),
            'label_block' => true,
        ]);
        $this->add_control('subheadline', [
            'label' => __('Subheadline', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Den smarta lösningen som hjälper tusentals svenskar att varva ner och sova bättre – varje natt.', 'nv-product-widgets'),
        ]);
        $this->add_control('rating_text', [
            'label' => __('Rating text (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('4.8/5 baserat på 2 400+ recensioner', 'nv-product-widgets'),
        ]);
        $this->add_control('rating_avatars', [
            'label' => __('Rating avatars (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::GALLERY,
            'default' => [],
            'description' => __('Small stacked customer avatars shown next to the rating.', 'nv-product-widgets'),
        ]);

        $bullets = new \Elementor\Repeater();
        $bullets->add_control('text', [
            'label' => __('Benefit', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Kliniskt testad', 'nv-product-widgets'),
        ]);
        $this->add_control('bullets', [
            'label' => __('Benefit bullets', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $bullets->get_controls(),
            'title_field' => '{{{ text }}}',
            'default' => [
                ['text' => __('Balanserar nervsystemet', 'nv-product-widgets')],
                ['text' => __('Snabb, naturlig effekt', 'nv-product-widgets')],
                ['text' => __('Kliniskt testad', 'nv-product-widgets')],
            ],
        ]);

        $this->add_control('cta_text', [
            'label' => __('Primary button text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Köp nu', 'nv-product-widgets'),
        ]);
        $this->add_control('cta_link', [
            'label' => __('Primary button link', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::URL,
            'default' => ['url' => '#'],
        ]);
        $this->add_control('cta2_text', [
            'label' => __('Secondary button text (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Läs mer', 'nv-product-widgets'),
        ]);
        $this->add_control('cta2_link', [
            'label' => __('Secondary button link', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::URL,
            'default' => ['url' => '#'],
        ]);
        $this->add_control('guarantee', [
            'label' => __('Guarantee line (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('30 dagars nöjd-kund-garanti', 'nv-product-widgets'),
        ]);
        $this->add_control('image', [
            'label' => __('Image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => ['url' => \Elementor\Utils::get_placeholder_image_src()],
        ]);
        $this->add_control('image_frame', [
            'label' => __('Image frame', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'none',
            'options' => [
                'none' => __('None', 'nv-product-widgets'),
                'phone' => __('Phone mockup', 'nv-product-widgets'),
                'browser' => __('Browser window', 'nv-product-widgets'),
            ],
            'description' => __('Wrap the image in a device mockup (Hero Pro style).', 'nv-product-widgets'),
        ]);
        $this->add_control('image_side', [
            'label' => __('Image position', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'right',
            'options' => ['right' => __('Right', 'nv-product-widgets'), 'left' => __('Left', 'nv-product-widgets')],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('bg', [
            'label' => __('Background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#F6F7FB',
            'selectors' => ['{{WRAPPER}} .nv-pw-hero' => '--nv-hero-bg: {{VALUE}};'],
        ]);
        $this->add_control('accent', [
            'label' => __('Accent color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#312E81',
            'selectors' => ['{{WRAPPER}} .nv-pw-hero' => '--nv-hero-accent: {{VALUE}};'],
        ]);
        $this->add_control('heading_color', [
            'label' => __('Heading color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#14161D',
            'selectors' => ['{{WRAPPER}} .nv-pw-hero' => '--nv-hero-heading: {{VALUE}};'],
        ]);
        $this->add_control('text_color', [
            'label' => __('Text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#4B5563',
            'selectors' => ['{{WRAPPER}} .nv-pw-hero' => '--nv-hero-text: {{VALUE}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $headline = trim((string) ($s['headline'] ?? ''));
        $highlight = trim((string) ($s['headline_highlight'] ?? ''));
        if ($headline === '' && $highlight === '') {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Hero Banner', '🏔️');
            }
            return;
        }
        $eyebrow = trim((string) ($s['eyebrow'] ?? ''));
        $sub = trim((string) ($s['subheadline'] ?? ''));
        $rating = trim((string) ($s['rating_text'] ?? ''));
        $guarantee = trim((string) ($s['guarantee'] ?? ''));
        $img = isset($s['image']['url']) ? (string) $s['image']['url'] : '';
        $frame = in_array(($s['image_frame'] ?? 'none'), ['none', 'phone', 'browser'], true) ? (string) $s['image_frame'] : 'none';
        $rating_avatars = [];
        foreach ((array) ($s['rating_avatars'] ?? []) as $av) {
            if (is_array($av) && !empty($av['url'])) $rating_avatars[] = (string) $av['url'];
        }
        $rating_avatars = array_slice($rating_avatars, 0, 5);
        $side = ($s['image_side'] ?? 'right') === 'left' ? 'left' : 'right';
        $layout = in_array(($s['layout'] ?? 'split'), ['split', 'centered', 'overlay', 'minimal'], true) ? (string) $s['layout'] : 'split';

        $bullets = [];
        foreach (($s['bullets'] ?? []) as $b) {
            if (is_array($b) && trim((string) ($b['text'] ?? '')) !== '') $bullets[] = trim((string) $b['text']);
        }

        $cta_text = trim((string) ($s['cta_text'] ?? ''));
        $cta2_text = trim((string) ($s['cta2_text'] ?? ''));
        $cta_url = isset($s['cta_link']['url']) ? (string) $s['cta_link']['url'] : '';
        $cta2_url = isset($s['cta2_link']['url']) ? (string) $s['cta2_link']['url'] : '';
        $cta_target = !empty($s['cta_link']['is_external']) ? ' target="_blank" rel="noopener"' : '';
        $cta2_target = !empty($s['cta2_link']['is_external']) ? ' target="_blank" rel="noopener"' : '';
        ?>
        <div class="nv-pw-hero nv-pw-hero--L-<?php echo esc_attr($layout); ?> nv-pw-hero--img-<?php echo esc_attr($side); ?>">
            <div class="nv-pw-hero__text">
                <?php if ($eyebrow !== '') : ?><span class="nv-pw-hero__eyebrow"><?php echo esc_html($eyebrow); ?></span><?php endif; ?>
                <h2 class="nv-pw-hero__headline">
                    <?php echo esc_html($headline); ?><?php if ($highlight !== '') : ?> <span class="nv-pw-hero__hl"><?php echo esc_html($highlight); ?></span><?php endif; ?>
                </h2>
                <?php if ($rating !== '' || !empty($rating_avatars)) : ?>
                    <div class="nv-pw-hero__rating">
                        <?php if (!empty($rating_avatars)) : ?>
                            <span class="nv-pw-hero__avatars" aria-hidden="true">
                                <?php foreach ($rating_avatars as $av) : ?><img class="nv-pw-hero__avatar" src="<?php echo esc_url($av); ?>" alt="" loading="lazy"><?php endforeach; ?>
                            </span>
                        <?php endif; ?>
                        <span class="nv-pw-hero__stars" aria-hidden="true">
                            <?php for ($i = 0; $i < 5; $i++) : ?><svg viewBox="0 0 24 24" width="16" height="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><?php endfor; ?>
                        </span>
                        <?php if ($rating !== '') : ?><span class="nv-pw-hero__rating-text"><?php echo esc_html($rating); ?></span><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($sub !== '') : ?><p class="nv-pw-hero__sub"><?php echo esc_html($sub); ?></p><?php endif; ?>
                <?php if (!empty($bullets)) : ?>
                    <ul class="nv-pw-hero__bullets">
                        <?php foreach ($bullets as $b) : ?>
                            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><?php echo esc_html($b); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <div class="nv-pw-hero__actions">
                    <?php if ($cta_text !== '') : ?><a class="nv-pw-hero__btn nv-pw-hero__btn--primary" href="<?php echo esc_url($cta_url !== '' ? $cta_url : '#'); ?>"<?php echo $cta_target; ?>><?php echo esc_html($cta_text); ?></a><?php endif; ?>
                    <?php if ($cta2_text !== '') : ?><a class="nv-pw-hero__btn nv-pw-hero__btn--ghost" href="<?php echo esc_url($cta2_url !== '' ? $cta2_url : '#'); ?>"<?php echo $cta2_target; ?>><?php echo esc_html($cta2_text); ?></a><?php endif; ?>
                </div>
                <?php if ($guarantee !== '') : ?>
                    <p class="nv-pw-hero__guarantee"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg><?php echo esc_html($guarantee); ?></p>
                <?php endif; ?>
            </div>
            <?php if ($img !== '') : ?>
                <div class="nv-pw-hero__media nv-pw-hero__media--frame-<?php echo esc_attr($frame); ?>">
                    <?php if ($frame === 'phone') : ?>
                        <span class="nv-pw-hero__device nv-pw-hero__device--phone"><span class="nv-pw-hero__notch" aria-hidden="true"></span><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($headline . ' ' . $highlight); ?>" loading="lazy"></span>
                    <?php elseif ($frame === 'browser') : ?>
                        <span class="nv-pw-hero__device nv-pw-hero__device--browser"><span class="nv-pw-hero__bar" aria-hidden="true"><i></i><i></i><i></i></span><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($headline . ' ' . $highlight); ?>" loading="lazy"></span>
                    <?php else : ?>
                        <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($headline . ' ' . $highlight); ?>" loading="lazy">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
