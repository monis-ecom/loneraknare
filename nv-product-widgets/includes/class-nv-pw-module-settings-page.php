<?php
if (!defined('ABSPATH')) exit;

final class NV_PW_Module_Settings_Page {
    private const MENU_SLUG = 'nv-pw-module-settings';

    public static function init(): void {
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_menu', [self::class, 'register_menu'], 42);
    }

    public static function register_settings(): void {
        register_setting(
            'nv_pw_module_settings_group',
            NV_PW_Module_Bridge::MODULE_OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [self::class, 'sanitize_settings'],
                'default' => NV_PW_Module_Bridge::get_default_module_settings(),
            ]
        );
    }

    /**
     * @param mixed $input
     * @return array<string,mixed>
     */
    public static function sanitize_settings($input): array {
        $data = is_array($input) ? $input : [];
        $sanitized = NV_PW_Module_Bridge::sanitize_module_settings($data);
        NV_PW_Module_Bridge::reset_cache();
        return $sanitized;
    }

    public static function register_menu(): void {
        add_submenu_page(
            'nv-product-templates',
            __('AS Product Widgets Settings', 'nv-product-widgets'),
            __('Settings', 'nv-product-widgets'),
            'manage_woocommerce',
            self::MENU_SLUG,
            [self::class, 'render_page']
        );
    }

    public static function render_page(): void {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $option_key = NV_PW_Module_Bridge::MODULE_OPTION_KEY;
        $settings = NV_PW_Module_Bridge::get_module_settings();
        ?>
        <div class="wrap nv-admin-clean nvpw-settings-admin-clean">
            <style>
                .nv-admin-clean{max-width:1180px}.nv-admin-clean__hero,.nv-admin-clean__panel{background:#fff;border:1px solid #dcdcde;border-radius:8px;box-sizing:border-box;margin:16px 0;padding:18px 20px}.nv-admin-clean__hero h1{align-items:center;display:flex;flex-wrap:wrap;gap:10px;line-height:1.2;margin:0 0 8px}.nv-admin-clean__hero p{color:#50575e;font-size:14px;margin:0;max-width:860px}.nv-admin-clean__version{background:#f6f7f7;border:1px solid #dcdcde;border-radius:999px;color:#50575e;font-size:12px;font-weight:600;line-height:1;padding:4px 8px}.nv-admin-clean .form-table{margin-top:0}.nv-admin-clean .form-table th{width:240px}.nv-admin-clean input.regular-text,.nv-admin-clean input.large-text,.nv-admin-clean textarea.large-text{max-width:100%;width:100%}.nv-admin-clean .submit{margin-bottom:0;padding-bottom:0}@media (max-width:782px){.nv-admin-clean .form-table th{width:auto}.nv-admin-clean__hero,.nv-admin-clean__panel{padding:14px}}
            </style>
            <div class="nv-admin-clean__hero">
                <h1>
                    <?php esc_html_e('AS Product Widgets Settings', 'nv-product-widgets'); ?>
                    <span class="nv-admin-clean__version">v<?php echo esc_html(NV_PW_VERSION); ?></span>
                </h1>
                <p><?php esc_html_e('Enable or disable trust/decision modules and manage global Size Chart defaults.', 'nv-product-widgets'); ?></p>
            </div>

            <div class="nv-admin-clean__panel">
            <form method="post" action="options.php">
                <?php settings_fields('nv_pw_module_settings_group'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Payment Logos', 'nv-product-widgets'); ?></th>
                        <td>
                            <select name="<?php echo esc_attr($option_key); ?>[payment_logos_enabled]">
                                <option value="yes" <?php selected($settings['payment_logos_enabled'] ?? 'yes', 'yes'); ?>><?php esc_html_e('Enabled', 'nv-product-widgets'); ?></option>
                                <option value="no" <?php selected($settings['payment_logos_enabled'] ?? 'yes', 'no'); ?>><?php esc_html_e('Disabled', 'nv-product-widgets'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Trust Badges', 'nv-product-widgets'); ?></th>
                        <td>
                            <select name="<?php echo esc_attr($option_key); ?>[trust_badges_enabled]">
                                <option value="yes" <?php selected($settings['trust_badges_enabled'] ?? 'yes', 'yes'); ?>><?php esc_html_e('Enabled', 'nv-product-widgets'); ?></option>
                                <option value="no" <?php selected($settings['trust_badges_enabled'] ?? 'yes', 'no'); ?>><?php esc_html_e('Disabled', 'nv-product-widgets'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Reasons To Buy', 'nv-product-widgets'); ?></th>
                        <td>
                            <select name="<?php echo esc_attr($option_key); ?>[reasons_to_buy_enabled]">
                                <option value="yes" <?php selected($settings['reasons_to_buy_enabled'] ?? 'yes', 'yes'); ?>><?php esc_html_e('Enabled', 'nv-product-widgets'); ?></option>
                                <option value="no" <?php selected($settings['reasons_to_buy_enabled'] ?? 'yes', 'no'); ?>><?php esc_html_e('Disabled', 'nv-product-widgets'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Size Chart', 'nv-product-widgets'); ?></th>
                        <td>
                            <select name="<?php echo esc_attr($option_key); ?>[size_chart_enabled]">
                                <option value="yes" <?php selected($settings['size_chart_enabled'] ?? 'yes', 'yes'); ?>><?php esc_html_e('Enabled', 'nv-product-widgets'); ?></option>
                                <option value="no" <?php selected($settings['size_chart_enabled'] ?? 'yes', 'no'); ?>><?php esc_html_e('Disabled', 'nv-product-widgets'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="nvpw-size-chart-button"><?php esc_html_e('Size Chart Button Label', 'nv-product-widgets'); ?></label></th>
                        <td>
                            <input id="nvpw-size-chart-button" class="regular-text" type="text" name="<?php echo esc_attr($option_key); ?>[size_chart_button_label]" value="<?php echo esc_attr((string) ($settings['size_chart_button_label'] ?? __('Size Chart', 'nv-product-widgets'))); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="nvpw-size-chart-title"><?php esc_html_e('Global Size Chart Title', 'nv-product-widgets'); ?></label></th>
                        <td>
                            <input id="nvpw-size-chart-title" class="regular-text" type="text" name="<?php echo esc_attr($option_key); ?>[size_chart_global_title]" value="<?php echo esc_attr((string) ($settings['size_chart_global_title'] ?? __('Size Guide', 'nv-product-widgets'))); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="nvpw-size-chart-content"><?php esc_html_e('Global Size Chart Content', 'nv-product-widgets'); ?></label></th>
                        <td>
                            <textarea id="nvpw-size-chart-content" class="large-text" rows="10" name="<?php echo esc_attr($option_key); ?>[size_chart_global_content]"><?php echo esc_textarea((string) ($settings['size_chart_global_content'] ?? '')); ?></textarea>
                            <p class="description"><?php esc_html_e('Basic HTML is allowed. Product-level mode can be inherit/off/custom.', 'nv-product-widgets'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save Settings', 'nv-product-widgets')); ?>
            </form>
            </div>
        </div>
        <?php
    }

    public static function get_menu_slug(): string {
        return self::MENU_SLUG;
    }
}
