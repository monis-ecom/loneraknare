<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Live_Viewers extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-live-viewers'; }
    public function get_title(): string { return 'NV: Live Viewers'; }
    public function get_icon(): string { return 'eicon-preview-medium'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['viewer', 'social proof', 'watching', 'nvcb']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('Viewer Settings', 'nv-product-widgets'),
        ]);

        $this->add_control('label_text', [
            'label' => __('Label text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('tittar på detta just nu', 'nv-product-widgets'),
        ]);

        $this->add_control('viewer_min', [
            'label' => __('Minimum viewers', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 8,
            'min' => 1,
            'max' => 999,
        ]);

        $this->add_control('viewer_max', [
            'label' => __('Maximum viewers', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 27,
            'min' => 1,
            'max' => 999,
        ]);

        $this->add_control('refresh_seconds', [
            'label' => __('Refresh interval (seconds)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 45,
            'min' => 10,
            'max' => 600,
        ]);

        $this->add_control('helper_note', [
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw' => __('This widget is powered by NV Conversion Booster script. Keep that plugin active.', 'nv-product-widgets'),
            'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
        ]);

        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();

        $label = trim((string) ($settings['label_text'] ?? ''));
        if ($label === '') {
            $label = 'tittar på detta just nu';
        }

        $min = max(1, (int) ($settings['viewer_min'] ?? 8));
        $max = max($min, (int) ($settings['viewer_max'] ?? 27));
        $refresh = max(10, (int) ($settings['refresh_seconds'] ?? 45));
        ?>
        <p class="nvcb-viewer-count"
           aria-live="polite"
           data-nvcb-viewer-min="<?php echo esc_attr((string) $min); ?>"
           data-nvcb-viewer-max="<?php echo esc_attr((string) $max); ?>"
           data-nvcb-viewer-refresh="<?php echo esc_attr((string) $refresh); ?>"
           data-nvcb-viewer-label="<?php echo esc_attr($label); ?>">
            <span class="nvcb-viewer-icon" aria-hidden="true">👁</span>
            <span class="nvcb-viewer-number">–</span>
            <span class="nvcb-viewer-label"><?php echo esc_html($label); ?></span>
        </p>
        <?php
    }
}
