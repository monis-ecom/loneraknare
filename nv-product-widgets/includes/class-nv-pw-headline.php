<?php
if (!defined('ABSPATH')) exit;

/**
 * Shared "Headline style" control for widgets with a headline. Lets each widget
 * switch its headline between the default sans style, a full editorial
 * serif-italic display style, and an editorial "mixed" style where only the
 * words you wrap in *asterisks* turn into the Newsreader italic accent — the
 * "Skandinavisk design *för ditt hem*" look from the brand design system.
 *
 * Usage in a widget:
 *   $this->add_control('nv_hl_style', NV_PW_Headline::args());
 *   ... class="nv-pw-x__heading<?php echo NV_PW_Headline::mod($s); ?>">
 *       <?php echo NV_PW_Headline::html($heading); ?>
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
                'editorial' => __('Editorial (all serif italic)', 'nv-product-widgets'),
                'mixed'     => __('Editorial mixed (serif + italic accent)', 'nv-product-widgets'),
            ],
            'description' => __('Wrap the words you want in the elegant Newsreader italic in *asterisks*, e.g. Skandinavisk design *för ditt hem*. “Editorial” italicises the whole headline; “Editorial mixed” keeps the headline upright serif and italicises only the *asterisked* part; “Default” keeps your sans headline but still italicises any *asterisked* accent.', 'nv-product-widgets'),
        ];
    }

    /** Modifier class for the headline element based on the setting. */
    public static function mod(array $settings, string $key = 'nv_hl_style'): string {
        $v = (string) ($settings[$key] ?? '');
        if ($v === 'editorial') return ' nv-hl--editorial';
        if ($v === 'mixed')     return ' nv-hl--mixed';
        return '';
    }

    /**
     * Escape a headline and convert *asterisk-wrapped* runs into an italic
     * Newsreader accent span. Echo the result raw (it is already escaped).
     */
    public static function html($text): string {
        $esc = esc_html((string) $text);
        // esc_html leaves "*" untouched, so pair up *emphasis* markers here.
        return preg_replace('/\*([^*]+)\*/', '<em class="nv-hl-em">$1</em>', $esc);
    }
}
