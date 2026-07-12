<?php
if (!defined('ABSPATH')) exit;

/**
 * NV Brand Tokens — set brand accent, colours, corner radius and font once under
 * Settings → NV Brand. They are output as :root CSS custom properties
 * (--nv-brand-*) so custom CSS and NV widgets can reference them, and an optional
 * brand font is applied across all NV widgets. Per-widget Elementor colours still
 * win — these are the global defaults / foundation.
 */
final class NV_PW_Brand {
    const OPTION = 'nv_pw_brand';

    public static function init(): void {
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('wp_head', [__CLASS__, 'output_css'], 20);
        add_action('elementor/editor/after_enqueue_styles', [__CLASS__, 'output_css']);
    }

    public static function defaults(): array {
        return [
            'enabled'        => 'no',
            'accent'         => '#3B37C4',
            'heading_color'  => '#14161D',
            'text_color'     => '#4A5160',
            'radius'         => '12',
            'font'           => '',
        ];
    }

    public static function get(): array {
        $saved = get_option(self::OPTION, []);
        return wp_parse_args(is_array($saved) ? $saved : [], self::defaults());
    }

    public static function register_settings(): void {
        register_setting(self::OPTION, self::OPTION, [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize'],
            'default' => self::defaults(),
        ]);
    }

    public static function sanitize($input): array {
        $d = self::defaults();
        $input = is_array($input) ? $input : [];
        return [
            'enabled'       => (($input['enabled'] ?? 'no') === 'yes') ? 'yes' : 'no',
            'accent'        => sanitize_hex_color((string) ($input['accent'] ?? $d['accent'])) ?: $d['accent'],
            'heading_color' => sanitize_hex_color((string) ($input['heading_color'] ?? $d['heading_color'])) ?: $d['heading_color'],
            'text_color'    => sanitize_hex_color((string) ($input['text_color'] ?? $d['text_color'])) ?: $d['text_color'],
            'radius'        => (string) max(0, min(40, (int) ($input['radius'] ?? $d['radius']))),
            'font'          => sanitize_text_field((string) ($input['font'] ?? '')),
        ];
    }

    public static function add_menu(): void {
        add_options_page(
            __('NV Brand', 'nv-product-widgets'),
            __('NV Brand', 'nv-product-widgets'),
            'manage_options',
            'nv-pw-brand',
            [__CLASS__, 'render_page']
        );
    }

    public static function render_page(): void {
        if (!current_user_can('manage_options')) return;
        $o = self::get();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('NV Brand Tokens', 'nv-product-widgets'); ?></h1>
            <p><?php esc_html_e('Set your brand look once. These become global CSS variables (--nv-brand-accent, --nv-brand-heading, --nv-brand-text, --nv-brand-radius, --nv-brand-font) and an optional brand font applied across all NV widgets.', 'nv-product-widgets'); ?></p>
            <form method="post" action="options.php">
                <?php settings_fields(self::OPTION); ?>
                <table class="form-table" role="presentation">
                    <tr><th scope="row"><?php esc_html_e('Apply brand font to NV widgets', 'nv-product-widgets'); ?></th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[enabled]" value="yes" <?php checked($o['enabled'], 'yes'); ?>> <?php esc_html_e('Use the brand font below across NV widgets', 'nv-product-widgets'); ?></label></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Accent colour', 'nv-product-widgets'); ?></th>
                        <td><input type="text" name="<?php echo esc_attr(self::OPTION); ?>[accent]" value="<?php echo esc_attr($o['accent']); ?>" class="regular-text" placeholder="#3B37C4"></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Heading colour', 'nv-product-widgets'); ?></th>
                        <td><input type="text" name="<?php echo esc_attr(self::OPTION); ?>[heading_color]" value="<?php echo esc_attr($o['heading_color']); ?>" class="regular-text" placeholder="#14161D"></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Text colour', 'nv-product-widgets'); ?></th>
                        <td><input type="text" name="<?php echo esc_attr(self::OPTION); ?>[text_color]" value="<?php echo esc_attr($o['text_color']); ?>" class="regular-text" placeholder="#4A5160"></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Corner radius (px)', 'nv-product-widgets'); ?></th>
                        <td><input type="number" name="<?php echo esc_attr(self::OPTION); ?>[radius]" value="<?php echo esc_attr($o['radius']); ?>" min="0" max="40" class="small-text"></td></tr>
                    <tr><th scope="row"><?php esc_html_e('Brand font family', 'nv-product-widgets'); ?></th>
                        <td><input type="text" name="<?php echo esc_attr(self::OPTION); ?>[font]" value="<?php echo esc_attr($o['font']); ?>" class="regular-text" placeholder="'Poppins', sans-serif">
                        <p class="description"><?php esc_html_e('A CSS font-family stack. The font itself must already load on your site (via the theme or Elementor). Leave blank to keep the default NV fonts.', 'nv-product-widgets'); ?></p></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function output_css(): void {
        $o = self::get();
        $font = trim((string) $o['font']);
        $css = ':root{'
            . '--nv-brand-accent:' . esc_html($o['accent']) . ';'
            . '--nv-brand-heading:' . esc_html($o['heading_color']) . ';'
            . '--nv-brand-text:' . esc_html($o['text_color']) . ';'
            . '--nv-brand-radius:' . esc_html($o['radius']) . 'px;'
            . ($font !== '' ? '--nv-brand-font:' . esc_html($font) . ';' : '')
            . '}';
        // Opt-in: apply the brand font across NV widgets.
        if ($o['enabled'] === 'yes' && $font !== '') {
            $css .= '[class^="nv-pw-"],[class*=" nv-pw-"]{font-family:var(--nv-brand-font)!important;}';
        }
        echo "\n<style id=\"nv-pw-brand\">" . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
