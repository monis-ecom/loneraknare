<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Featured_Product extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-featured-product'; }
    public function get_title(): string { return 'NV: Featured Product'; }
    public function get_icon(): string { return 'eicon-product-related'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['featured', 'product', 'spotlight', 'promo', 'highlight']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);

        $this->add_control('layout', [
            'label' => __('Layout', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'split',
            'options' => [
                'split' => __('Split (image + info)', 'nv-product-widgets'),
                'card' => __('Centered card', 'nv-product-widgets'),
                'banner' => __('Wide banner (text overlay)', 'nv-product-widgets'),
            ],
        ]);
        $this->add_control('image_side', [
            'label' => __('Image position', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'left',
            'options' => ['left' => __('Left', 'nv-product-widgets'), 'right' => __('Right', 'nv-product-widgets')],
            'condition' => ['layout' => 'split'],
        ]);
        $this->add_control('image', [
            'label' => __('Product image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'default' => ['url' => \Elementor\Utils::get_placeholder_image_src()],
        ]);
        $this->add_control('badge', [
            'label' => __('Badge (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('BÄSTSÄLJARE', 'nv-product-widgets'),
        ]);
        $this->add_control('title', [
            'label' => __('Product title', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Premium Sömnmask', 'nv-product-widgets'),
            'label_block' => true,
        ]);
        $this->add_control('rating', [
            'label' => __('Stars (0-5)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '5',
            'options' => ['0' => '0', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'],
        ]);
        $this->add_control('rating_text', [
            'label' => __('Rating text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('(2 400+ recensioner)', 'nv-product-widgets'),
        ]);
        $this->add_control('text', [
            'label' => __('Short description', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Blockerar 100% ljus och hjälper dig somna snabbare – natt efter natt.', 'nv-product-widgets'),
        ]);
        $this->add_control('price', [
            'label' => __('Price', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('299 kr', 'nv-product-widgets'),
        ]);
        $this->add_control('old_price', [
            'label' => __('Old price (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('399 kr', 'nv-product-widgets'),
        ]);
        $this->add_control('cta_text', [
            'label' => __('Button text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Köp nu', 'nv-product-widgets'),
        ]);
        $this->add_control('cta_link', [
            'label' => __('Button link', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::URL,
            'default' => ['url' => '#'],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('bg', ['label' => __('Background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => ['{{WRAPPER}} .nv-pw-fp' => '--nv-fp-bg: {{VALUE}};']]);
        $this->add_control('accent', ['label' => __('Accent color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#312E81', 'selectors' => ['{{WRAPPER}} .nv-pw-fp' => '--nv-fp-accent: {{VALUE}};']]);
        $this->add_control('heading_color', ['label' => __('Heading color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#14161D', 'selectors' => ['{{WRAPPER}} .nv-pw-fp' => '--nv-fp-heading: {{VALUE}};']]);
        $this->add_control('text_color', ['label' => __('Text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#4B5563', 'selectors' => ['{{WRAPPER}} .nv-pw-fp' => '--nv-fp-text: {{VALUE}};']]);
        $this->end_controls_section();
    }

    private function stars(int $n): string {
        $n = max(0, min(5, $n));
        $o = '<span class="nv-pw-fp__stars" aria-hidden="true">';
        for ($i = 1; $i <= 5; $i++) {
            $o .= '<svg class="nv-pw-fp__star' . ($i <= $n ? ' is-on' : '') . '" viewBox="0 0 24 24" width="16" height="16"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
        }
        return $o . '</span>';
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $title = trim((string) ($s['title'] ?? ''));
        $img = isset($s['image']['url']) ? (string) $s['image']['url'] : '';
        if ($title === '' && $img === '') {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Featured Product', '⭐');
            }
            return;
        }
        $layout = in_array(($s['layout'] ?? 'split'), ['split', 'card', 'banner'], true) ? (string) $s['layout'] : 'split';
        $side = ($s['image_side'] ?? 'left') === 'right' ? 'right' : 'left';
        $badge = trim((string) ($s['badge'] ?? ''));
        $rating = (int) ($s['rating'] ?? 5);
        $rating_text = trim((string) ($s['rating_text'] ?? ''));
        $text = trim((string) ($s['text'] ?? ''));
        $price = trim((string) ($s['price'] ?? ''));
        $old = trim((string) ($s['old_price'] ?? ''));
        $cta = trim((string) ($s['cta_text'] ?? ''));
        $cta_url = isset($s['cta_link']['url']) ? (string) $s['cta_link']['url'] : '';
        $cta_target = !empty($s['cta_link']['is_external']) ? ' target="_blank" rel="noopener"' : '';
        ?>
        <div class="nv-pw-fp nv-pw-fp--<?php echo esc_attr($layout); ?> nv-pw-fp--img-<?php echo esc_attr($side); ?>">
            <div class="nv-pw-fp__media">
                <?php if ($img !== '') : ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy"><?php endif; ?>
                <?php if ($badge !== '') : ?><span class="nv-pw-fp__badge"><?php echo esc_html($badge); ?></span><?php endif; ?>
            </div>
            <div class="nv-pw-fp__info">
                <?php if ($title !== '') : ?><h3 class="nv-pw-fp__title"><?php echo esc_html($title); ?></h3><?php endif; ?>
                <?php if ($rating > 0 || $rating_text !== '') : ?>
                    <div class="nv-pw-fp__rating"><?php echo $this->stars($rating); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php if ($rating_text !== '') : ?><span class="nv-pw-fp__rating-text"><?php echo esc_html($rating_text); ?></span><?php endif; ?></div>
                <?php endif; ?>
                <?php if ($text !== '') : ?><p class="nv-pw-fp__text"><?php echo esc_html($text); ?></p><?php endif; ?>
                <?php if ($price !== '' || $old !== '') : ?>
                    <div class="nv-pw-fp__prices"><?php if ($price !== '') : ?><span class="nv-pw-fp__price"><?php echo esc_html($price); ?></span><?php endif; ?><?php if ($old !== '') : ?><span class="nv-pw-fp__old"><?php echo esc_html($old); ?></span><?php endif; ?></div>
                <?php endif; ?>
                <?php if ($cta !== '') : ?><a class="nv-pw-fp__btn" href="<?php echo esc_url($cta_url !== '' ? $cta_url : '#'); ?>"<?php echo $cta_target; ?>><?php echo esc_html($cta); ?></a><?php endif; ?>
            </div>
        </div>
        <?php
    }
}
