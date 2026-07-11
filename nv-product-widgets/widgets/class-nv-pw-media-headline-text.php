<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Media_Headline_Text extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-media-headline-text'; }
    public function get_title(): string { return 'NV: Media + Headline + Text'; }
    public function get_icon(): string { return 'eicon-image-box'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['media', 'image', 'headline', 'text', 'story']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('Content', 'nv-product-widgets'),
        ]);

        $this->add_control('media', [
            'label' => __('Image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
        ]);

        $this->add_control('media_position', [
            'label' => __('Image position', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'left',
            'options' => [
                'left' => __('Left', 'nv-product-widgets'),
                'right' => __('Right', 'nv-product-widgets'),
            ],
        ]);

        $this->add_control('eyebrow', [
            'label' => __('Eyebrow (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Varför det fungerar', 'nv-product-widgets'),
        ]);

        $this->add_control('headline', [
            'label' => __('Headline', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Effektiv lösning för din vardag', 'nv-product-widgets'),
        ]);

        $this->add_control('body', [
            'label' => __('Body text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Beskriv huvudfördelen med 2-3 meningar. Håll texten enkel, konkret och lätt att skanna.', 'nv-product-widgets'),
        ]);

        $this->add_control('button_text', [
            'label' => __('Button text (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Läs mer', 'nv-product-widgets'),
        ]);

        $this->add_control('button_url', [
            'label' => __('Button URL', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://',
            'default' => ['url' => ''],
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);

        $this->add_responsive_control('content_align', [
            'label' => __('Text alignment', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::CHOOSE,
            'options' => [
                'left'   => ['title' => __('Left', 'nv-product-widgets'), 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Center', 'nv-product-widgets'), 'icon' => 'eicon-text-align-center'],
                'right'  => ['title' => __('Right', 'nv-product-widgets'), 'icon' => 'eicon-text-align-right'],
            ],
            'selectors' => ['{{WRAPPER}} .nv-pw-media-text__content' => 'text-align: {{VALUE}};'],
        ]);

        $this->add_control('eyebrow_heading', ['label' => __('Eyebrow', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('eyebrow_color', [
            'label' => __('Eyebrow color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nv-pw-media-text__eyebrow' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'eyebrow_typography',
            'selector' => '{{WRAPPER}} .nv-pw-media-text__eyebrow',
        ]);

        $this->add_control('headline_heading', ['label' => __('Headline', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('headline_color', [
            'label' => __('Headline color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nv-pw-media-text__headline' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'headline_typography',
            'selector' => '{{WRAPPER}} .nv-pw-media-text__headline',
        ]);

        $this->add_control('body_heading', ['label' => __('Body', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('body_color', [
            'label' => __('Body color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .nv-pw-media-text__body' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'body_typography',
            'selector' => '{{WRAPPER}} .nv-pw-media-text__body',
        ]);

        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();

        $image_url = (string) ($settings['media']['url'] ?? '');
        $eyebrow = trim((string) ($settings['eyebrow'] ?? ''));
        $headline = trim((string) ($settings['headline'] ?? ''));
        $body = trim((string) ($settings['body'] ?? ''));
        $button_text = trim((string) ($settings['button_text'] ?? ''));
        $button_url = trim((string) ($settings['button_url']['url'] ?? ''));

        if ($headline === '' && $body === '' && $image_url === '') {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Media + Headline + Text', '🖼️');
            }
            return;
        }

        $position = ($settings['media_position'] ?? 'left') === 'right' ? 'right' : 'left';
        $has_button = ($button_text !== '' && $button_url !== '');

        if ($has_button) {
            $this->add_render_attribute('link', 'href', esc_url($button_url));
            if (!empty($settings['button_url']['is_external'])) {
                $this->add_render_attribute('link', 'target', '_blank');
            }
            if (!empty($settings['button_url']['nofollow'])) {
                $this->add_render_attribute('link', 'rel', 'nofollow');
            }
        }

        ?>
        <div class="nv-pw-media-text nv-pw-media-text--<?php echo esc_attr($position); ?>">
            <?php if ($image_url !== '') : ?>
                <div class="nv-pw-media-text__media">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($headline); ?>" loading="lazy" />
                </div>
            <?php endif; ?>

            <div class="nv-pw-media-text__content">
                <?php if ($eyebrow !== '') : ?>
                    <p class="nv-pw-media-text__eyebrow"><?php echo esc_html($eyebrow); ?></p>
                <?php endif; ?>
                <?php if ($headline !== '') : ?>
                    <h3 class="nv-pw-media-text__headline"><?php echo esc_html($headline); ?></h3>
                <?php endif; ?>
                <?php if ($body !== '') : ?>
                    <div class="nv-pw-media-text__body"><?php echo wp_kses_post(wpautop($body)); ?></div>
                <?php endif; ?>
                <?php if ($has_button) : ?>
                    <a class="nv-pw-media-text__button" <?php echo $this->get_render_attribute_string('link'); ?>>
                        <?php echo esc_html($button_text); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
