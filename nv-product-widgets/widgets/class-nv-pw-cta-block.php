<?php
if (!defined('ABSPATH')) exit;

class NV_PW_CTA_Block extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-cta-block'; }
    public function get_title(): string { return 'NV: CTA Block'; }
    public function get_icon(): string { return 'eicon-call-to-action'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['cta', 'call to action', 'button', 'conversion']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('Content', 'nv-product-widgets'),
        ]);

        $this->add_control('eyebrow', [
            'label' => __('Eyebrow', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Redo att komma igång?', 'nv-product-widgets'),
        ]);

        $this->add_control('headline', [
            'label' => __('Headline', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Välj paket och beställ idag', 'nv-product-widgets'),
        ]);

        $this->add_control('description', [
            'label' => __('Description', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Få snabb leverans, trygg betalning och support från vårt svenska team.', 'nv-product-widgets'),
        ]);

        $this->add_control('primary_text', [
            'label' => __('Primary button text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Köp nu', 'nv-product-widgets'),
        ]);

        $this->add_control('primary_url', [
            'label' => __('Primary button URL', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://',
            'default' => ['url' => ''],
        ]);

        $this->add_control('secondary_text', [
            'label' => __('Secondary link text (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Se alla paket', 'nv-product-widgets'),
        ]);

        $this->add_control('secondary_url', [
            'label' => __('Secondary link URL', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://',
            'default' => ['url' => ''],
        ]);

        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();

        $eyebrow = trim((string) ($settings['eyebrow'] ?? ''));
        $headline = trim((string) ($settings['headline'] ?? ''));
        $description = trim((string) ($settings['description'] ?? ''));

        $primary_text = trim((string) ($settings['primary_text'] ?? ''));
        $primary_url = trim((string) ($settings['primary_url']['url'] ?? ''));
        $secondary_text = trim((string) ($settings['secondary_text'] ?? ''));
        $secondary_url = trim((string) ($settings['secondary_url']['url'] ?? ''));

        $has_primary = ($primary_text !== '' && $primary_url !== '');
        $has_secondary = ($secondary_text !== '' && $secondary_url !== '');

        if ($headline === '' && $description === '' && !$has_primary && !$has_secondary) {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: CTA Block', '🎯');
            }
            return;
        }

        if ($has_primary) {
            $this->add_render_attribute('primary_link', 'href', esc_url($primary_url));
            if (!empty($settings['primary_url']['is_external'])) {
                $this->add_render_attribute('primary_link', 'target', '_blank');
            }
            if (!empty($settings['primary_url']['nofollow'])) {
                $this->add_render_attribute('primary_link', 'rel', 'nofollow');
            }
        }

        if ($has_secondary) {
            $this->add_render_attribute('secondary_link', 'href', esc_url($secondary_url));
            if (!empty($settings['secondary_url']['is_external'])) {
                $this->add_render_attribute('secondary_link', 'target', '_blank');
            }
            if (!empty($settings['secondary_url']['nofollow'])) {
                $this->add_render_attribute('secondary_link', 'rel', 'nofollow');
            }
        }

        ?>
        <div class="nv-pw-cta-block">
            <?php if ($eyebrow !== '') : ?>
                <p class="nv-pw-cta-block__eyebrow"><?php echo esc_html($eyebrow); ?></p>
            <?php endif; ?>
            <?php if ($headline !== '') : ?>
                <h3 class="nv-pw-cta-block__headline"><?php echo esc_html($headline); ?></h3>
            <?php endif; ?>
            <?php if ($description !== '') : ?>
                <p class="nv-pw-cta-block__description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>

            <?php if ($has_primary || $has_secondary) : ?>
                <div class="nv-pw-cta-block__actions">
                    <?php if ($has_primary) : ?>
                        <a class="nv-pw-cta-block__button" <?php echo $this->get_render_attribute_string('primary_link'); ?>>
                            <?php echo esc_html($primary_text); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($has_secondary) : ?>
                        <a class="nv-pw-cta-block__link" <?php echo $this->get_render_attribute_string('secondary_link'); ?>>
                            <?php echo esc_html($secondary_text); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
