<?php
if (!defined('ABSPATH')) exit;

final class NV_PW_Size_Chart {
    private static bool $trigger_rendered = false;

    public static function init(): void {
        add_action('add_meta_boxes', [self::class, 'add_product_metabox']);
        add_action('save_post_product', [self::class, 'save_product_metabox'], 10, 2);

        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('woocommerce_before_add_to_cart_button', [self::class, 'render_trigger'], 28);
        add_action('wp_footer', [self::class, 'render_modal'], 45);
    }

    public static function add_product_metabox(): void {
        add_meta_box(
            'nv_pw_size_chart',
            __('NV Size Chart', 'nv-product-widgets'),
            [self::class, 'render_product_metabox'],
            'product',
            'side',
            'default'
        );
    }

    public static function render_product_metabox(\WP_Post $post): void {
        wp_nonce_field('nv_pw_size_chart_save', 'nv_pw_size_chart_nonce');

        $mode = sanitize_key((string) get_post_meta($post->ID, NV_PW_Module_Bridge::SIZE_CHART_MODE_META, true));
        if (!in_array($mode, ['inherit', 'off', 'custom'], true)) {
            $mode = 'inherit';
        }

        $title = (string) get_post_meta($post->ID, NV_PW_Module_Bridge::SIZE_CHART_TITLE_META, true);
        $content = (string) get_post_meta($post->ID, NV_PW_Module_Bridge::SIZE_CHART_CONTENT_META, true);
        ?>
        <p style="margin-top:0;">
            <label for="nv-pw-size-chart-mode"><strong><?php esc_html_e('Override Mode', 'nv-product-widgets'); ?></strong></label><br />
            <select id="nv-pw-size-chart-mode" name="nv_pw_size_chart_mode" style="width:100%;">
                <option value="inherit" <?php selected($mode, 'inherit'); ?>><?php esc_html_e('Inherit global chart from Product Widgets settings', 'nv-product-widgets'); ?></option>
                <option value="off" <?php selected($mode, 'off'); ?>><?php esc_html_e('Hide size chart for this product', 'nv-product-widgets'); ?></option>
                <option value="custom" <?php selected($mode, 'custom'); ?>><?php esc_html_e('Use custom chart for this product', 'nv-product-widgets'); ?></option>
            </select>
        </p>
        <p>
            <label for="nv-pw-size-chart-title"><strong><?php esc_html_e('Custom Title (optional)', 'nv-product-widgets'); ?></strong></label><br />
            <input id="nv-pw-size-chart-title" type="text" name="nv_pw_size_chart_title" value="<?php echo esc_attr($title); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="nv-pw-size-chart-content"><strong><?php esc_html_e('Custom Content (basic HTML allowed)', 'nv-product-widgets'); ?></strong></label><br />
            <textarea id="nv-pw-size-chart-content" name="nv_pw_size_chart_content" rows="6" style="width:100%;"><?php echo esc_textarea($content); ?></textarea>
        </p>
        <p style="margin-bottom:0;color:#646970;">
            <?php esc_html_e('Global defaults are managed in Product Widgets Settings.', 'nv-product-widgets'); ?>
        </p>
        <?php
    }

    public static function save_product_metabox(int $post_id, \WP_Post $post): void {
        if (
            !isset($_POST['nv_pw_size_chart_nonce']) ||
            !wp_verify_nonce((string) $_POST['nv_pw_size_chart_nonce'], 'nv_pw_size_chart_save') ||
            (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
            !current_user_can('edit_post', $post_id)
        ) {
            return;
        }

        $mode = isset($_POST['nv_pw_size_chart_mode']) ? sanitize_key((string) wp_unslash((string) $_POST['nv_pw_size_chart_mode'])) : 'inherit';
        if (!in_array($mode, ['inherit', 'off', 'custom'], true)) {
            $mode = 'inherit';
        }
        update_post_meta($post_id, NV_PW_Module_Bridge::SIZE_CHART_MODE_META, $mode);

        $title = isset($_POST['nv_pw_size_chart_title'])
            ? sanitize_text_field((string) wp_unslash((string) $_POST['nv_pw_size_chart_title']))
            : '';
        $content = isset($_POST['nv_pw_size_chart_content'])
            ? wp_kses_post((string) wp_unslash((string) $_POST['nv_pw_size_chart_content']))
            : '';

        if ($title === '') {
            delete_post_meta($post_id, NV_PW_Module_Bridge::SIZE_CHART_TITLE_META);
        } else {
            update_post_meta($post_id, NV_PW_Module_Bridge::SIZE_CHART_TITLE_META, $title);
        }

        if (trim($content) === '') {
            delete_post_meta($post_id, NV_PW_Module_Bridge::SIZE_CHART_CONTENT_META);
        } else {
            update_post_meta($post_id, NV_PW_Module_Bridge::SIZE_CHART_CONTENT_META, $content);
        }
    }

    public static function enqueue_assets(): void {
        if (is_admin() || !function_exists('is_product') || !is_product()) {
            return;
        }

        $payload = self::get_current_payload();
        if (!$payload['enabled']) {
            return;
        }

        wp_enqueue_style(
            'nv-product-widgets',
            NV_PW_URL . 'assets/css/nv-product-widgets.css',
            [],
            NV_PW_VERSION
        );

        wp_enqueue_script(
            'nv-pw-size-chart',
            NV_PW_URL . 'assets/js/nv-size-chart.js',
            [],
            NV_PW_VERSION,
            true
        );
    }

    public static function render_trigger(): void {
        if (self::$trigger_rendered || is_admin() || !function_exists('is_product') || !is_product()) {
            return;
        }

        $payload = self::get_current_payload();
        if (!$payload['enabled']) {
            return;
        }

        self::$trigger_rendered = true;
        ?>
        <button type="button" class="nv-pw-size-chart-trigger" data-nv-pw-size-chart-open>
            <?php echo esc_html((string) $payload['buttonLabel']); ?>
        </button>
        <?php
    }

    public static function render_modal(): void {
        if (is_admin() || !function_exists('is_product') || !is_product()) {
            return;
        }

        $payload = self::get_current_payload();
        if (!$payload['enabled']) {
            return;
        }

        $title = (string) ($payload['title'] ?? __('Size Guide', 'nv-product-widgets'));
        $content = (string) ($payload['content'] ?? '');
        if (trim($content) === '') {
            return;
        }
        ?>
        <div class="nv-pw-size-chart-modal" id="nvPwSizeChartModal" hidden>
            <button type="button" class="nv-pw-size-chart-modal__overlay" data-nv-pw-size-chart-close aria-label="<?php esc_attr_e('Close size chart', 'nv-product-widgets'); ?>"></button>
            <div class="nv-pw-size-chart-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="nvPwSizeChartTitle">
                <div class="nv-pw-size-chart-modal__header">
                    <h3 class="nv-pw-size-chart-modal__title" id="nvPwSizeChartTitle"><?php echo esc_html($title); ?></h3>
                    <button type="button" class="nv-pw-size-chart-modal__close" data-nv-pw-size-chart-close aria-label="<?php esc_attr_e('Close size chart', 'nv-product-widgets'); ?>">×</button>
                </div>
                <div class="nv-pw-size-chart-modal__content">
                    <?php echo wp_kses_post(wpautop($content)); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * @return array<string,mixed>
     */
    private static function get_current_payload(): array {
        $product_id = 0;
        if (function_exists('get_queried_object_id')) {
            $product_id = (int) get_queried_object_id();
        }

        return NV_PW_Module_Bridge::get_size_chart_payload($product_id);
    }
}
