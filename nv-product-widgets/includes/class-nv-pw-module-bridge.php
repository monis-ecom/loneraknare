<?php
if (!defined('ABSPATH')) exit;

final class NV_PW_Module_Bridge {
    public const MODULE_OPTION_KEY = 'nv_pw_module_settings';
    private const LEGACY_HUB_OPTION_KEY = 'nvmh_module_settings';
    private const MIGRATION_FLAG_OPTION_KEY = 'nv_pw_module_settings_migrated';
    public const SIZE_CHART_MODE_META = '_nv_pw_size_chart_mode';
    public const SIZE_CHART_TITLE_META = '_nv_pw_size_chart_title';
    public const SIZE_CHART_CONTENT_META = '_nv_pw_size_chart_content';

    /** @var array<string,mixed>|null */
    private static ?array $module_settings = null;

    /**
     * @return array<string,mixed>
     */
    public static function get_default_module_settings(): array {
        return [
            'payment_logos_enabled' => 'yes',
            'trust_badges_enabled' => 'yes',
            'reasons_to_buy_enabled' => 'yes',
            'size_chart_enabled' => 'yes',
            'size_chart_button_label' => __('Size Chart', 'nv-product-widgets'),
            'size_chart_global_title' => __('Size Guide', 'nv-product-widgets'),
            'size_chart_global_content' => '',
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function sanitize_module_settings(array $input): array {
        $defaults = self::get_default_module_settings();

        return [
            'payment_logos_enabled' => (($input['payment_logos_enabled'] ?? 'yes') === 'no') ? 'no' : 'yes',
            'trust_badges_enabled' => (($input['trust_badges_enabled'] ?? 'yes') === 'no') ? 'no' : 'yes',
            'reasons_to_buy_enabled' => (($input['reasons_to_buy_enabled'] ?? 'yes') === 'no') ? 'no' : 'yes',
            'size_chart_enabled' => (($input['size_chart_enabled'] ?? 'yes') === 'no') ? 'no' : 'yes',
            'size_chart_button_label' => sanitize_text_field((string) ($input['size_chart_button_label'] ?? $defaults['size_chart_button_label'])),
            'size_chart_global_title' => sanitize_text_field((string) ($input['size_chart_global_title'] ?? $defaults['size_chart_global_title'])),
            'size_chart_global_content' => wp_kses_post((string) ($input['size_chart_global_content'] ?? $defaults['size_chart_global_content'])),
        ];
    }

    public static function reset_cache(): void {
        self::$module_settings = null;
    }

    public static function maybe_migrate_from_legacy_hub(): void {
        if (get_option(self::MIGRATION_FLAG_OPTION_KEY, '0') === '1') {
            return;
        }

        $existing = get_option(self::MODULE_OPTION_KEY, null);
        if (is_array($existing) && !empty($existing)) {
            update_option(self::MIGRATION_FLAG_OPTION_KEY, '1', false);
            return;
        }

        $legacy = get_option(self::LEGACY_HUB_OPTION_KEY, []);
        if (!is_array($legacy) || empty($legacy)) {
            update_option(self::MIGRATION_FLAG_OPTION_KEY, '1', false);
            return;
        }

        $defaults = self::get_default_module_settings();
        $candidate = [
            'payment_logos_enabled' => (($legacy['payment_logos_enabled'] ?? $defaults['payment_logos_enabled']) === 'no') ? 'no' : 'yes',
            'trust_badges_enabled' => (($legacy['trust_badges_enabled'] ?? $defaults['trust_badges_enabled']) === 'no') ? 'no' : 'yes',
            'reasons_to_buy_enabled' => (($legacy['reasons_to_buy_enabled'] ?? $defaults['reasons_to_buy_enabled']) === 'no') ? 'no' : 'yes',
            'size_chart_enabled' => (($legacy['size_chart_enabled'] ?? $defaults['size_chart_enabled']) === 'no') ? 'no' : 'yes',
            'size_chart_button_label' => (string) ($legacy['size_chart_button_label'] ?? $defaults['size_chart_button_label']),
            'size_chart_global_title' => (string) ($legacy['size_chart_global_title'] ?? $defaults['size_chart_global_title']),
            'size_chart_global_content' => (string) ($legacy['size_chart_global_content'] ?? $defaults['size_chart_global_content']),
        ];

        update_option(self::MODULE_OPTION_KEY, self::sanitize_module_settings($candidate), false);
        update_option(self::MIGRATION_FLAG_OPTION_KEY, '1', false);
    }

    /**
     * @return array<string,mixed>
     */
    public static function get_module_settings(): array {
        if (self::$module_settings !== null) {
            return self::$module_settings;
        }

        self::maybe_migrate_from_legacy_hub();
        $defaults = self::get_default_module_settings();

        $saved = get_option(self::MODULE_OPTION_KEY, []);
        if (!is_array($saved)) {
            $saved = [];
        }

        $merged = wp_parse_args($saved, $defaults);
        self::$module_settings = self::sanitize_module_settings($merged);

        return self::$module_settings;
    }

    public static function is_module_enabled(string $module_key): bool {
        $settings = self::get_module_settings();
        $value = $settings[$module_key . '_enabled'] ?? 'yes';
        return $value === 'yes';
    }

    /**
     * @return array<string,mixed>
     */
    public static function get_size_chart_payload(int $product_id): array {
        $settings = self::get_module_settings();

        $payload = [
            'enabled' => (($settings['size_chart_enabled'] ?? 'yes') === 'yes'),
            'buttonLabel' => (string) ($settings['size_chart_button_label'] ?? __('Size Chart', 'nv-product-widgets')),
            'title' => (string) ($settings['size_chart_global_title'] ?? __('Size Guide', 'nv-product-widgets')),
            'content' => (string) ($settings['size_chart_global_content'] ?? ''),
        ];

        if (!$payload['enabled'] || $product_id <= 0) {
            $payload['enabled'] = false;
            return $payload;
        }

        $mode = sanitize_key((string) get_post_meta($product_id, self::SIZE_CHART_MODE_META, true));
        if (!in_array($mode, ['inherit', 'off', 'custom'], true)) {
            $mode = 'inherit';
        }

        if ($mode === 'off') {
            $payload['enabled'] = false;
            return $payload;
        }

        if ($mode === 'custom') {
            $custom_title = sanitize_text_field((string) get_post_meta($product_id, self::SIZE_CHART_TITLE_META, true));
            $custom_content = wp_kses_post((string) get_post_meta($product_id, self::SIZE_CHART_CONTENT_META, true));

            if ($custom_title !== '') {
                $payload['title'] = $custom_title;
            }
            if ($custom_content !== '') {
                $payload['content'] = $custom_content;
            }
        }

        if (trim((string) $payload['content']) === '') {
            $payload['enabled'] = false;
        }

        return $payload;
    }
}
