<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Logo_Scroller extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-logo-scroller'; }
    public function get_title(): string { return 'NV: Logo Scroller'; }
    public function get_icon(): string { return 'eicon-carousel'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['logo', 'brands', 'partners', 'scroll', 'trust']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('Content', 'nv-product-widgets'),
        ]);

        $this->add_control('heading', [
            'label' => __('Heading (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Trusted by leading brands', 'nv-product-widgets'),
        ]);

        $repeater = new \Elementor\Repeater();
        $repeater->add_control('logo', [
            'label' => __('Logo', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
        ]);
        $repeater->add_control('alt', [
            'label' => __('Alt text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);

        $this->add_control('logos', [
            'label' => __('Logos', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => '{{{ alt || "Logo" }}}',
            'default' => [
                ['alt' => 'Klarna'],
                ['alt' => 'Swish'],
                ['alt' => 'Visa'],
                ['alt' => 'Mastercard'],
            ],
        ]);

        $this->add_control('speed_seconds', [
            'label' => __('Loop duration (seconds)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 28,
            'min' => 8,
            'max' => 180,
        ]);

        $this->add_control('grayscale', [
            'label' => __('Use grayscale logos', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'label_on' => __('Yes', 'nv-product-widgets'),
            'label_off' => __('No', 'nv-product-widgets'),
            'return_value' => 'yes',
            'default' => 'yes',
        ]);

        $this->add_control('chip_style', [
            'label' => __('Logo style', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'plain',
            'options' => [
                'plain' => __('Plain', 'nv-product-widgets'),
                'bordered' => __('Bordered chips', 'nv-product-widgets'),
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render(): void {
        if (!NV_PW_Module_Bridge::is_module_enabled('payment_logos')) {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Logo Scroller (Disabled in Product Widgets settings)', '🏷️');
            }
            return;
        }

        $settings = $this->get_settings_for_display();
        $heading = trim((string) ($settings['heading'] ?? ''));
        $speed = max(8, (int) ($settings['speed_seconds'] ?? 28));
        $use_grayscale = ($settings['grayscale'] ?? 'yes') === 'yes';
        $bordered = ($settings['chip_style'] ?? 'plain') === 'bordered';

        $logos = [];
        foreach (($settings['logos'] ?? []) as $logo_row) {
            if (!is_array($logo_row)) {
                continue;
            }

            $url = (string) ($logo_row['logo']['url'] ?? '');
            if ($url === '') {
                continue;
            }

            $logos[] = [
                'url' => $url,
                'alt' => trim((string) ($logo_row['alt'] ?? '')),
            ];
        }

        if (empty($logos)) {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Logo Scroller', '🏷️');
            }
            return;
        }

        $loop_logos = array_merge($logos, $logos);
        ?>
        <div class="nv-pw-logo-scroller<?php echo $bordered ? ' nv-pw-logo-scroller--bordered' : ''; ?>" style="--nv-pw-logo-duration: <?php echo esc_attr((string) $speed); ?>s;">
            <?php if ($heading !== '') : ?>
                <h3 class="nv-pw-logo-scroller__heading"><?php echo esc_html($heading); ?></h3>
            <?php endif; ?>
            <div class="nv-pw-logo-scroller__viewport" aria-label="<?php esc_attr_e('Partner logos', 'nv-product-widgets'); ?>">
                <div class="nv-pw-logo-scroller__track<?php echo $use_grayscale ? ' is-grayscale' : ''; ?>">
                    <?php foreach ($loop_logos as $logo) : ?>
                        <span class="nv-pw-logo-scroller__item">
                            <img src="<?php echo esc_url($logo['url']); ?>" alt="<?php echo esc_attr($logo['alt']); ?>" loading="lazy" />
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
