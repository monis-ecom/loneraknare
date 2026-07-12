<?php
if (!defined('ABSPATH')) exit;

/**
 * NV: Social / UGC Feed — an Instagram-style grid of customer content (manual
 * curation): image + handle + link, with a hover overlay. Real-customer proof
 * that refreshes by editing the list rather than a testimonials block.
 */
class NV_PW_Social_Feed extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-social-feed'; }
    public function get_title(): string { return 'NV: Social / UGC Feed'; }
    public function get_icon(): string { return 'eicon-instagram-gallery'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['social', 'instagram', 'ugc', 'feed', 'customer', 'photos', 'social proof']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('heading', ['label' => __('Heading', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Följ oss @nordiskavaruhuset', 'nv-product-widgets'), 'label_block' => true]);
        $this->add_control('subheading', ['label' => __('Subheading', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('Så här använder våra kunder produkterna.', 'nv-product-widgets')]);
        $this->add_control('follow_text', ['label' => __('Follow button text (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Följ oss', 'nv-product-widgets')]);
        $this->add_control('follow_url', ['label' => __('Follow button link', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::URL, 'default' => ['url' => ''], 'condition' => ['follow_text!' => '']]);
        $r = new \Elementor\Repeater();
        $r->add_control('image', ['label' => __('Image', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::MEDIA, 'default' => ['url' => \Elementor\Utils::get_placeholder_image_src()]]);
        $r->add_control('handle', ['label' => __('Handle / caption', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('@kund', 'nv-product-widgets')]);
        $r->add_control('link', ['label' => __('Post link', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::URL, 'default' => ['url' => '']]);
        $this->add_control('posts', [
            'label' => __('Posts', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ handle || "Post" }}}',
            'default' => array_fill(0, 6, ['image' => ['url' => \Elementor\Utils::get_placeholder_image_src()], 'handle' => '@kund']),
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_layout', ['label' => __('Layout', 'nv-product-widgets')]);
        $this->add_responsive_control('columns', ['label' => __('Columns', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 6, 'tablet_default' => 3, 'mobile_default' => 2, 'min' => 2, 'max' => 8, 'selectors' => ['{{WRAPPER}} .nv-pw-sf' => '--nv-sf-cols: {{VALUE}};']]);
        $this->add_responsive_control('gap', ['label' => __('Gap', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 30]], 'default' => ['size' => 10, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-sf' => '--nv-sf-gap: {{SIZE}}px;']]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('radius', ['label' => __('Corner radius', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 32]], 'default' => ['size' => 10, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-sf' => '--nv-sf-radius: {{SIZE}}px;']]);
        $this->add_control('accent', ['label' => __('Accent (button / overlay)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#3B37C4', 'selectors' => ['{{WRAPPER}} .nv-pw-sf' => '--nv-sf-accent: {{VALUE}};']]);
        $this->add_control('heading_color', ['label' => __('Heading color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-sf' => '--nv-sf-heading: {{VALUE}};']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $rows = is_array($s['posts'] ?? null) ? $s['posts'] : [];
        $posts = [];
        foreach ($rows as $it) {
            if (is_array($it) && !empty($it['image']['url'])) $posts[] = $it;
        }
        if (empty($posts)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Social Feed — add posts', '📸');
            return;
        }

        $heading = trim((string) ($s['heading'] ?? ''));
        $sub = trim((string) ($s['subheading'] ?? ''));
        $follow_text = trim((string) ($s['follow_text'] ?? ''));
        $follow_url = isset($s['follow_url']['url']) ? (string) $s['follow_url']['url'] : '';
        $follow_target = !empty($s['follow_url']['is_external']) ? ' target="_blank" rel="noopener"' : '';
        $ig = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>';
        ?>
        <div class="nv-pw-sf">
            <?php if ($heading !== '' || $sub !== '') : ?>
                <div class="nv-pw-sf__head">
                    <?php if ($heading !== '') : ?><h2 class="nv-pw-sf__heading"><?php echo esc_html($heading); ?></h2><?php endif; ?>
                    <?php if ($sub !== '') : ?><p class="nv-pw-sf__sub"><?php echo esc_html($sub); ?></p><?php endif; ?>
                    <?php if ($follow_text !== '') : ?><a class="nv-pw-sf__follow" href="<?php echo esc_url($follow_url !== '' ? $follow_url : '#'); ?>"<?php echo $follow_target; ?>><?php echo $ig; // phpcs:ignore ?> <?php echo esc_html($follow_text); ?></a><?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="nv-pw-sf__grid">
                <?php foreach ($posts as $it) :
                    $url = (string) $it['image']['url'];
                    $handle = trim((string) ($it['handle'] ?? ''));
                    $link = isset($it['link']['url']) ? (string) $it['link']['url'] : '';
                    $target = !empty($it['link']['is_external']) ? ' target="_blank" rel="noopener"' : '';
                    $tag = $link !== '' ? 'a' : 'div';
                    ?>
                    <<?php echo $tag; ?> class="nv-pw-sf__item"<?php echo $link !== '' ? ' href="' . esc_url($link) . '"' . $target : ''; ?>>
                        <img class="nv-pw-sf__img" src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr($handle); ?>" loading="lazy">
                        <span class="nv-pw-sf__overlay">
                            <span class="nv-pw-sf__ig" aria-hidden="true"><?php echo $ig; // phpcs:ignore ?></span>
                            <?php if ($handle !== '') : ?><span class="nv-pw-sf__handle"><?php echo esc_html($handle); ?></span><?php endif; ?>
                        </span>
                    </<?php echo $tag; ?>>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
