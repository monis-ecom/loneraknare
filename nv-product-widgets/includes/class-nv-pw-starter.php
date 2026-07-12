<?php
if (!defined('ABSPATH')) exit;

/**
 * NV Starter Templates — one-click install of a pre-assembled landing page built
 * from NV widgets, into the plugin's Elementor template library. Saves starting
 * from a blank canvas — the hardest moment for a solo builder.
 */
final class NV_PW_Starter {
    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_post_nv_pw_install_starter', [__CLASS__, 'handle_install']);
    }

    public static function add_menu(): void {
        add_submenu_page(
            'tools.php',
            __('NV Starter Templates', 'nv-product-widgets'),
            __('NV Starter Templates', 'nv-product-widgets'),
            'edit_pages',
            'nv-pw-starter',
            [__CLASS__, 'render_page']
        );
    }

    /** Available starters: key => [label, description, builder]. */
    private static function starters(): array {
        return [
            'product-landing' => [
                'label' => __('Product Landing Page', 'nv-product-widgets'),
                'desc'  => __('Hero → icon USPs → Trustpilot reviews → comparison → FAQ → CTA. The full conversion spine, ready to edit.', 'nv-product-widgets'),
            ],
            'lead-gen' => [
                'label' => __('Lead-Gen Page', 'nv-product-widgets'),
                'desc'  => __('Hero → benefits → testimonials → lead form. For quotes, consults and list building.', 'nv-product-widgets'),
            ],
        ];
    }

    public static function render_page(): void {
        if (!current_user_can('edit_pages')) return;
        $installed = isset($_GET['nv_installed']) ? absint($_GET['nv_installed']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('NV Starter Templates', 'nv-product-widgets'); ?></h1>
            <?php if ($installed) : ?>
                <div class="notice notice-success"><p><?php esc_html_e('Starter template installed.', 'nv-product-widgets'); ?>
                <a href="<?php echo esc_url(admin_url('post.php?post=' . $installed . '&action=elementor')); ?>"><?php esc_html_e('Edit it with Elementor →', 'nv-product-widgets'); ?></a></p></div>
            <?php endif; ?>
            <p><?php esc_html_e('One click assembles a full landing page from NV widgets into your template library. Open it in Elementor, swap the copy and images, and publish.', 'nv-product-widgets'); ?></p>
            <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:16px;">
                <?php foreach (self::starters() as $key => $meta) : ?>
                    <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:18px 20px;max-width:340px;">
                        <h2 style="margin-top:0;font-size:16px;"><?php echo esc_html($meta['label']); ?></h2>
                        <p style="color:#50575e;font-size:13px;"><?php echo esc_html($meta['desc']); ?></p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="nv_pw_install_starter">
                            <input type="hidden" name="starter" value="<?php echo esc_attr($key); ?>">
                            <?php wp_nonce_field('nv_pw_install_starter'); ?>
                            <button type="submit" class="button button-primary"><?php esc_html_e('Install this starter', 'nv-product-widgets'); ?></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public static function handle_install(): void {
        if (!current_user_can('edit_pages')) wp_die('forbidden');
        check_admin_referer('nv_pw_install_starter');
        $key = isset($_POST['starter']) ? sanitize_key((string) $_POST['starter']) : '';
        $starters = self::starters();
        if (!isset($starters[$key])) wp_die('Unknown starter');

        $widgets = $key === 'lead-gen'
            ? ['nv-hero', 'nv-benefits-list', 'nv-testimonials', 'nv-lead-form']
            : ['nv-hero', 'nv-icon-columns', 'nv-trustpilot-wall', 'nv-comparison-grid', 'nv-faq', 'nv-cta-block'];

        $data = self::build_elementor_data($widgets);

        $post_type = class_exists('NV_PW_Template_System') ? NV_PW_Template_System::CPT : 'page';
        $post_id = wp_insert_post([
            'post_title'  => $starters[$key]['label'] . ' (' . __('Starter', 'nv-product-widgets') . ')',
            'post_status' => 'draft',
            'post_type'   => $post_type,
        ], true);

        if (is_wp_error($post_id) || !$post_id) wp_die('Could not create template');

        update_post_meta($post_id, '_elementor_data', wp_slash(wp_json_encode($data)));
        update_post_meta($post_id, '_elementor_edit_mode', 'builder');
        update_post_meta($post_id, '_elementor_template_type', 'page');
        update_post_meta($post_id, '_wp_page_template', 'elementor_canvas');
        if (defined('NV_PW_VERSION')) update_post_meta($post_id, '_elementor_version', NV_PW_VERSION);

        wp_safe_redirect(admin_url('tools.php?page=nv-pw-starter&nv_installed=' . $post_id));
        exit;
    }

    /** Unique 8-char Elementor element id. */
    private static function eid(): string {
        return substr(md5(uniqid('nvpw', true)), 0, 8);
    }

    /** Build canonical Elementor data: one full-width section+column per widget. */
    private static function build_elementor_data(array $widget_types): array {
        $sections = [];
        foreach ($widget_types as $type) {
            $sections[] = [
                'id' => self::eid(),
                'elType' => 'section',
                'settings' => ['padding' => ['unit' => 'px', 'top' => '40', 'bottom' => '40', 'left' => '0', 'right' => '0', 'isLinked' => false]],
                'elements' => [[
                    'id' => self::eid(),
                    'elType' => 'column',
                    'settings' => ['_column_size' => 100, '_inline_size' => null],
                    'elements' => [[
                        'id' => self::eid(),
                        'elType' => 'widget',
                        'widgetType' => $type,
                        'settings' => new stdClass(),
                    ]],
                    'isInner' => false,
                ]],
                'isInner' => false,
            ];
        }
        return $sections;
    }
}
