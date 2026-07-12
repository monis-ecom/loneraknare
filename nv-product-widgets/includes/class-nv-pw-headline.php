<?php
if (!defined('ABSPATH')) exit;

/**
 * Shared "Headline style" control for widgets with a headline. Lets each widget
 * switch its headline between the default sans style and an editorial serif-italic
 * display style (the Newsreader look from the brand design system).
 *
 * Usage in a widget:
 *   $this->add_control('nv_hl_style', NV_PW_Headline::args());
 *   ... class="nv-pw-x__heading<?php echo NV_PW_Headline::mod($s); ?>"
 */
final class NV_PW_Headline {
    /** Control args for the shared "Headline style" select. */
    public static function args(): array {
        return [
            'label' => __('Headline style', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => [
                ''          => __('Default (sans)', 'nv-product-widgets'),
                'editorial' => __('Editorial (serif italic)', 'nv-product-widgets'),
            ],
            'description' => __('“Editorial” uses the Newsreader serif display style from your design system.', 'nv-product-widgets'),
        ];
    }

    /** Modifier class for the headline element based on the setting. */
    public static function mod(array $settings, string $key = 'nv_hl_style'): string {
        return (($settings[$key] ?? '') === 'editorial') ? ' nv-hl--editorial' : '';
    }
}
