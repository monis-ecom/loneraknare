<?php
if (!defined('ABSPATH')) exit;

/**
 * NV PW: Product Template System
 *
 * Provides a lightweight ShopLentor-style product template builder.
 */
final class NV_PW_Template_System {

    const CPT                = 'nv_prod_tmpl';
    const META_KEY           = '_nv_product_template';
    const TEMPLATE_KIND_META = '_nv_pw_template_kind';
    const IMPORT_FINGERPRINT_META = '_nv_pw_import_fingerprint';
    const DEFAULT_TEMPLATES_OPTION = 'nv_pw_default_templates';

    public static function init(): void {
        $instance = new self();

        // Register CPT
        add_action('init', [$instance, 'register_cpt']);

        // Make CPT editable with Elementor
        add_action('elementor/init', [$instance, 'register_with_elementor']);

        // Add product meta box
        add_action('add_meta_boxes', [$instance, 'add_product_metabox']);
        add_action('save_post_product', [$instance, 'save_product_metabox'], 10, 2);

        // Intercept template loading for products
        add_filter('template_include', [$instance, 'maybe_load_custom_template'], 99);

        // Admin menu
        add_action('admin_menu', [$instance, 'add_admin_menu']);

        // Add "Edit in Elementor" link in template list
        add_filter('post_row_actions', [$instance, 'add_edit_elementor_link'], 10, 2);

        // Admin actions
        add_action('admin_post_nv_pw_create_template', [$instance, 'handle_create_template']);
        add_action('admin_post_nv_pw_quick_edit_template', [$instance, 'handle_quick_edit_template']);
        add_action('admin_post_nv_pw_duplicate_template', [$instance, 'handle_duplicate_template']);
        add_action('admin_post_nv_pw_delete_template', [$instance, 'handle_delete_template']);
        add_action('admin_post_nv_pw_export_template', [$instance, 'handle_export_template']);
        add_action('admin_post_nv_pw_export_templates_bulk', [$instance, 'handle_export_templates_bulk']);
        add_action('admin_post_nv_pw_bulk_template_action', [$instance, 'handle_bulk_template_action']);
        add_action('admin_post_nv_pw_import_template', [$instance, 'handle_import_template']);
        add_action('admin_post_nv_pw_set_default_template', [$instance, 'handle_set_default_template']);
        add_action('admin_post_nv_pw_clear_default_template', [$instance, 'handle_clear_default_template']);

        // Ajax: search products for the assignment table
        add_action('wp_ajax_nv_pw_search_products', [$instance, 'ajax_search_products']);

        // Keep Elementor "Go Back to WordPress" safe for our hidden template CPT.
        add_action('admin_init', [$instance, 'redirect_elementor_exit']);

        // Prevent LiteSpeed from treating the Elementor return page as edit mode.
        add_action('litespeed_init', [$instance, 'clear_litespeed_elementor_referrer_on_template_return'], 0);
    }

    /* ─────────────────────────────────────────────────────────
       1. CUSTOM POST TYPE
    ───────────────────────────────────────────────────────── */

    public function register_cpt(): void {
        register_post_type(self::CPT, [
            'label'               => 'Product Templates',
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false, // Hidden — we use our own admin page
            'show_in_nav_menus'   => false,
            'exclude_from_search' => true,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'elementor'],
            'capability_type'     => 'post',
            'has_archive'         => false,
            'rewrite'             => false,
            // Elementor editor uses a preview iframe. It must be able to query this CPT.
            'query_var'           => self::CPT,
            'publicly_queryable'  => true,
        ]);
    }

    /* ─────────────────────────────────────────────────────────
       2. ELEMENTOR INTEGRATION
    ───────────────────────────────────────────────────────── */

    public function register_with_elementor(): void {
        // Allow Elementor to edit this CPT
        add_filter('elementor/utils/get_public_post_types', function(array $types): array {
            $types[self::CPT] = 'Product Templates';
            return $types;
        });

        // Compatibility: include this CPT in Elementor's document post type list.
        add_filter('elementor/documents/get/post_types', function(array $types): array {
            if (!in_array(self::CPT, $types, true)) {
                $types[] = self::CPT;
            }
            return $types;
        });

        add_post_type_support(self::CPT, 'elementor');
    }

    /**
     * Elementor exits the editor via post.php?action=edit. For this hidden CPT we
     * route users back to the plugin template screen instead of the missing-item page.
     */
    public function redirect_elementor_exit(): void {
        global $pagenow;

        if ($pagenow !== 'post.php') {
            return;
        }

        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
        $action  = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : '';

        if (!empty($_GET['nv_pw_direct_edit'])) {
            return;
        }

        if ($action !== 'edit' || $post_id <= 0) {
            return;
        }

        $post = get_post($post_id);
        if (!$post instanceof \WP_Post) {
            return;
        }

        // Elementor can occasionally bounce through a revision ID when exiting.
        if ($post->post_type === 'revision' && (int) $post->post_parent > 0) {
            $parent = get_post((int) $post->post_parent);
            if ($parent instanceof \WP_Post) {
                $post = $parent;
            }
        }

        if ($post->post_type !== self::CPT) {
            return;
        }

        if (!current_user_can('edit_post', (int) $post->ID)) {
            return;
        }

        wp_safe_redirect(add_query_arg([
            'page'         => 'nv-product-templates',
            'nv_type'      => 'single_product',
            'nv_pw_notice' => 'elementor_exit',
            'template_id'  => (int) $post->ID,
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * LiteSpeed Cache disables all features when the HTTP referrer contains
     * action=elementor. Our post-Elementor admin page is not edit mode, so clear
     * that false-positive referrer before LiteSpeed's Elementor preload runs.
     */
    public function clear_litespeed_elementor_referrer_on_template_return(): void {
        if (function_exists('is_admin') && !is_admin()) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : '';
        if ($page !== 'nv-product-templates') {
            return;
        }

        $notice = isset($_GET['nv_pw_notice']) ? sanitize_key((string) wp_unslash($_GET['nv_pw_notice'])) : '';
        if ($notice !== 'elementor_exit') {
            return;
        }

        $referer = isset($_SERVER['HTTP_REFERER']) ? (string) wp_unslash($_SERVER['HTTP_REFERER']) : '';
        if ($referer === '' || strpos($referer, 'action=elementor') === false) {
            return;
        }

        unset($_SERVER['HTTP_REFERER']);
    }

    /* ─────────────────────────────────────────────────────────
       3. PRODUCT META BOX
    ───────────────────────────────────────────────────────── */

    public function add_product_metabox(): void {
        add_meta_box(
            'nv_pw_product_template',
            '🎨 NV Custom Template',
            [$this, 'render_product_metabox'],
            'product',
            'side',
            'default'
        );
    }

    public function render_product_metabox(\WP_Post $post): void {
        wp_nonce_field('nv_pw_template_save', 'nv_pw_template_nonce');

        $current_template = get_post_meta($post->ID, self::META_KEY, true);
        $default_template_id = $this->get_default_template_id('single_product');
        $default_option_label = $default_template_id > 0
            ? '— Use default NV template: ' . get_the_title($default_template_id) . ' —'
            : '— Use default theme layout —';

        $templates = get_posts([
            'post_type'      => self::CPT,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'   => self::TEMPLATE_KIND_META,
                    'value' => 'single_product',
                ],
                [
                    'key'     => self::TEMPLATE_KIND_META,
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);
        ?>
        <p style="font-family:-apple-system,sans-serif;font-size:12px;color:#646970;margin:0 0 8px;">
            Assign a custom Elementor template, or leave blank to use the site default.
        </p>
        <select name="nv_product_template" style="width:100%;margin-bottom:10px;">
            <option value=""><?php echo esc_html($default_option_label); ?></option>
            <?php foreach ($templates as $tmpl) : ?>
                <option value="<?php echo esc_attr($tmpl->ID); ?>"
                    <?php selected($current_template, $tmpl->ID); ?>>
                    <?php echo esc_html($tmpl->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($current_template && get_post($current_template)) :
            $edit_url = add_query_arg([
                'post'   => $current_template,
                'action' => 'elementor',
            ], admin_url('post.php'));
            ?>
            <a href="<?php echo esc_url($edit_url); ?>" target="_blank"
               style="font-family:-apple-system,sans-serif;font-size:12px;color:#0f766e;text-decoration:none;">
                ✏️ Edit template in Elementor →
            </a>
        <?php endif; ?>
        <?php if (empty($templates)) : ?>
            <p style="font-family:-apple-system,sans-serif;font-size:12px;color:#999;margin:8px 0 0;">
                No templates yet.
                <a href="<?php echo esc_url(add_query_arg([
                    'page'        => 'nv-product-templates',
                    'show_create' => 1,
                    'nv_type'     => 'single_product',
                ], admin_url('admin.php'))); ?>"
                   style="color:#0f766e;">Create one →</a>
            </p>
        <?php endif; ?>
        <?php
    }

    public function save_product_metabox(int $post_id, \WP_Post $post): void {
        if (
            !isset($_POST['nv_pw_template_nonce']) ||
            !wp_verify_nonce($_POST['nv_pw_template_nonce'], 'nv_pw_template_save') ||
            defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
            !current_user_can('edit_post', $post_id)
        ) {
            return;
        }

        $template_id = isset($_POST['nv_product_template']) ? intval($_POST['nv_product_template']) : 0;
        if ($template_id) {
            update_post_meta($post_id, self::META_KEY, $template_id);
        } else {
            delete_post_meta($post_id, self::META_KEY);
        }
    }

    /* ─────────────────────────────────────────────────────────
       4. TEMPLATE SWAP (template_include filter)
    ───────────────────────────────────────────────────────── */

    public function maybe_load_custom_template(string $template): string {
        if (is_singular('product')) {
            $product_id = get_the_ID();
            $template_id = (int) get_post_meta($product_id, self::META_KEY, true);
            if ($template_id <= 0) {
                $template_id = $this->get_default_template_id('single_product');
            }

            if (apply_filters('nv_pw_skip_custom_template', false, (int) $product_id, (int) $template_id)) {
                return $template;
            }

            if ($template_id <= 0) {
                return $template;
            }

            $tmpl_post = get_post($template_id);
            if (!$tmpl_post || $tmpl_post->post_status !== 'publish' || $tmpl_post->post_type !== self::CPT) {
                return $template;
            }

            $wrapper = NV_PW_DIR . 'templates/product-template.php';
            if (file_exists($wrapper)) {
                set_query_var('nv_pw_template_id', $template_id);
                return $wrapper;
            }

            return $template;
        }

        if (function_exists('is_shop') && is_shop()) {
            $template_id = $this->get_default_template_id('shop');
            if ($template_id <= 0 || !class_exists('\Elementor\Plugin')) {
                return $template;
            }

            if (apply_filters('nv_pw_skip_shop_template', false, (int) $template_id)) {
                return $template;
            }

            $wrapper = NV_PW_DIR . 'templates/shop-template.php';
            if (file_exists($wrapper)) {
                set_query_var('nv_pw_shop_template_id', $template_id);
                return $wrapper;
            }
        }

        return $template;
    }

    /* ─────────────────────────────────────────────────────────
       5. ADMIN MENU PAGE
    ───────────────────────────────────────────────────────── */

    public function add_admin_menu(): void {
        add_menu_page(
            'AS Product Widgets',
            'AS Product Widgets',
            'manage_woocommerce',
            'nv-product-templates',
            [$this, 'render_admin_page'],
            'dashicons-layout',
            '56.22'
        );
    }

    public function render_admin_page(): void {
        $types          = $this->get_template_types();
        $selected_type  = $this->sanitize_template_type($_GET['nv_type'] ?? 'all', true);
        $selected_status = $this->sanitize_template_status_filter($_GET['nv_status'] ?? 'all');
        $open_create_modal = isset($_GET['show_create']) || isset($_GET['error']);
        $quick_edit_requested_id = isset($_GET['quick_edit']) ? (int) $_GET['quick_edit'] : 0;
        $quick_edit_post = null;
        $open_quick_edit_modal = false;
        $quick_edit_title_value = '';
        $quick_edit_status_value = 'draft';
        $quick_edit_assigned_product = 0;
        $usage_map      = $this->get_template_usage_map();
        $all_templates  = $this->get_templates_for_admin('all', 'all');
        $templates      = $this->get_templates_for_admin($selected_type, $selected_status);
        $status_views   = $this->get_template_status_views($selected_type);
        $default_kind   = ($selected_type !== 'all') ? $selected_type : 'single_product';
        $create_options = $this->get_duplicate_source_options();
        $product_options = $this->get_product_assignment_options();
        $default_template_ids = $this->get_default_template_ids();

        // Stats computation
        $stat_total     = count($all_templates);
        $stat_published = 0;
        $stat_draft     = 0;
        $type_counts    = [];
        foreach ($types as $slug => $_cfg) {
            $type_counts[$slug] = 0;
        }
        $type_counts['all'] = $stat_total;
        foreach ($all_templates as $t) {
            if ($t['post']->post_status === 'publish') {
                $stat_published++;
            } else {
                $stat_draft++;
            }
            if (isset($type_counts[$t['kind']])) {
                $type_counts[$t['kind']]++;
            }
        }
        $stat_defaults = 0;
        foreach ($default_template_ids as $did) {
            if ((int) $did > 0) {
                $stat_defaults++;
            }
        }

        $stat_pub_pct = $stat_total > 0 ? (int) round(($stat_published / $stat_total) * 100) : 0;

        // Most recently modified template (dashboard insight)
        $recent_title = '';
        $recent_date  = '';
        $recent_ts    = 0;
        foreach ($all_templates as $t) {
            $ts = strtotime($t['post']->post_modified ?: $t['post']->post_modified_gmt);
            if ($ts && $ts > $recent_ts) {
                $recent_ts    = $ts;
                $recent_title = (string) ($t['post']->post_title ?: 'Untitled');
                $recent_date  = date_i18n('M j, Y', $ts);
            }
        }

        // URLs
        $bulk_export_current_url = wp_nonce_url(
            add_query_arg([
                'action'  => 'nv_pw_export_templates_bulk',
                'scope'   => 'current',
                'nv_type' => $selected_type,
                'nv_status' => $selected_status,
            ], admin_url('admin-post.php')),
            'nv_pw_export_templates_bulk'
        );
        $bulk_export_all_url = wp_nonce_url(
            add_query_arg([
                'action'  => 'nv_pw_export_templates_bulk',
                'scope'   => 'all',
                'nv_type' => 'all',
            ], admin_url('admin-post.php')),
            'nv_pw_export_templates_bulk'
        );
        $product_export_url = admin_url('edit.php?post_type=product&page=product_exporter');
        $product_import_url = admin_url('edit.php?post_type=product&page=product_importer');

        if ($quick_edit_requested_id > 0) {
            $candidate = get_post($quick_edit_requested_id);
            if ($candidate instanceof \WP_Post && $this->is_supported_duplicate_source($candidate)) {
                $quick_edit_post = $candidate;
                $open_quick_edit_modal = true;
                $quick_edit_title_value = (string) ($candidate->post_title ?: '');
                $quick_edit_status_value = $candidate->post_status === 'publish' ? 'publish' : 'draft';

                if ($candidate->post_type === self::CPT) {
                    $assigned = get_posts([
                        'post_type'      => 'product',
                        'post_status'    => ['publish', 'draft', 'private'],
                        'posts_per_page' => 1,
                        'fields'         => 'ids',
                        'meta_key'       => self::META_KEY,
                        'meta_value'     => (string) $candidate->ID,
                        'orderby'        => 'ID',
                        'order'          => 'ASC',
                    ]);
                    if (!empty($assigned)) {
                        $quick_edit_assigned_product = (int) $assigned[0];
                    }
                }
            }
        }
        ?>
        <div class="wrap aspw-wrap">

            <!-- Header -->
            <div class="aspw-header">
                <div class="aspw-header__inner">
                    <div class="aspw-header__left">
                        <div class="aspw-header__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/>
                                <rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>
                            </svg>
                        </div>
                        <div class="aspw-header__title-group">
                            <h1 class="aspw-header__title">
                                AS Product Widgets
                                <span class="aspw-header__version">v<?php echo esc_html(NV_PW_VERSION); ?></span>
                            </h1>
                            <span class="aspw-header__subtitle">Elementor product templates for WooCommerce</span>
                        </div>
                    </div>
                    <div class="aspw-header__right">
                        <button type="button" class="aspw-header__theme-btn" id="aspw-theme-toggle" aria-label="Switch to dark mode" title="Switch to dark mode">
                            <svg id="aspw-theme-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                        </button>
                        <a id="nv-pw-open-create-modal"
                           href="<?php echo esc_url(add_query_arg([
                                'page'        => 'nv-product-templates',
                                'show_create' => 1,
                                'nv_type'     => $selected_type,
                            ], admin_url('admin.php'))); ?>"
                           class="aspw-header__cta">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            New Template
                        </a>
                        <button type="button" class="aspw-header__menu-btn" id="aspw-header-menu-toggle" aria-label="More actions">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg>
                        </button>
                        <div id="aspw-header-dropdown" class="aspw-dropdown">
                            <a class="aspw-dropdown__item" href="<?php echo esc_url($bulk_export_current_url); ?>">
                                <span class="aspw-dropdown__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></span>
                                Export This Tab
                            </a>
                            <a class="aspw-dropdown__item" href="<?php echo esc_url($bulk_export_all_url); ?>">
                                <span class="aspw-dropdown__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></span>
                                Export All Templates
                            </a>
                            <div class="aspw-dropdown__separator"></div>
                            <a class="aspw-dropdown__item aspw-dropdown__item--muted" href="<?php echo esc_url($product_export_url); ?>">
                                <span class="aspw-dropdown__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5"/><path d="M21 3l-7 7"/><path d="M21 14v5a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h5"/></svg></span>
                                Export Products CSV
                            </a>
                            <a class="aspw-dropdown__item aspw-dropdown__item--muted" href="<?php echo esc_url($product_import_url); ?>">
                                <span class="aspw-dropdown__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5"/><path d="M21 3l-7 7"/><path d="M21 14v5a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h5"/></svg></span>
                                Import Products CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php $this->render_admin_notices(); ?>

            <!-- Always-visible Import Panel (v1.7.33) -->
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="aspw-import-panel" id="nv-pw-template-import-form">
                <input type="hidden" name="action" value="nv_pw_import_template">
                <input type="hidden" name="nv_type" value="<?php echo esc_attr($selected_type); ?>">
                <?php wp_nonce_field('nv_pw_import_template'); ?>
                <label class="aspw-import-panel__zone" id="nv-pw-template-dropzone" for="nv-pw-template-files" tabindex="0">
                    <span class="aspw-import-panel__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                    </span>
                    <span class="aspw-import-panel__copy">
                        <span class="aspw-import-panel__title">Drop template JSON here to import</span>
                        <span class="aspw-import-panel__meta" id="nv-pw-template-upload-status">or click to choose files — imports instantly</span>
                    </span>
                </label>
                <input class="aspw-upload-input" id="nv-pw-template-files" type="file" name="template_files[]" accept=".json,application/json" multiple required>
                <button type="submit" class="aspw-btn-secondary aspw-upload-submit">Upload Templates</button>
            </form>

            <!-- Stats Bar -->
            <div class="aspw-stats">
                <div class="aspw-stats__card">
                    <div class="aspw-stats__head">
                        <span class="aspw-stats__chip aspw-stats__chip--total">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M4 7 12 3l8 4-8 4-8-4Z"/><path d="m4 12 8 4 8-4"/><path d="m4 17 8 4 8-4"/></svg>
                        </span>
                        <span class="aspw-stats__pill aspw-stats__pill--total">All</span>
                    </div>
                    <div class="aspw-stats__number"><?php echo (int) $stat_total; ?></div>
                    <div class="aspw-stats__label">Total Templates</div>
                </div>
                <div class="aspw-stats__card">
                    <div class="aspw-stats__head">
                        <span class="aspw-stats__chip aspw-stats__chip--published">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        </span>
                        <span class="aspw-stats__pill aspw-stats__pill--published"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M8 7h9v9"/></svg><?php echo (int) $stat_pub_pct; ?>%</span>
                    </div>
                    <div class="aspw-stats__number"><?php echo (int) $stat_published; ?></div>
                    <div class="aspw-stats__label">Published</div>
                </div>
                <div class="aspw-stats__card">
                    <div class="aspw-stats__head">
                        <span class="aspw-stats__chip aspw-stats__chip--draft">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/></svg>
                        </span>
                        <span class="aspw-stats__pill aspw-stats__pill--draft">Pending</span>
                    </div>
                    <div class="aspw-stats__number"><?php echo (int) $stat_draft; ?></div>
                    <div class="aspw-stats__label">Drafts</div>
                </div>
                <div class="aspw-stats__card">
                    <div class="aspw-stats__head">
                        <span class="aspw-stats__chip aspw-stats__chip--default">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        </span>
                        <span class="aspw-stats__pill aspw-stats__pill--default"><?php echo $stat_defaults > 0 ? 'Active' : 'None'; ?></span>
                    </div>
                    <div class="aspw-stats__number"><?php echo (int) $stat_defaults; ?></div>
                    <div class="aspw-stats__label">Defaults Set</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="aspw-quickbar">
                <span class="aspw-quickbar__label">Quick actions</span>
                <a class="aspw-qa-btn aspw-qa-btn--new" href="<?php echo esc_url(add_query_arg(['page' => 'nv-product-templates', 'show_create' => 1, 'nv_type' => $selected_type], admin_url('admin.php'))); ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    New template
                </a>
                <button type="button" class="aspw-qa-btn aspw-qa-btn--import" id="aspw-qa-import">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                    Import
                </button>
                <a class="aspw-qa-btn aspw-qa-btn--export" href="<?php echo esc_url($bulk_export_current_url); ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Export this tab
                </a>
                <?php if ($recent_title !== '') : ?>
                <span class="aspw-quickbar__insight">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    Last edited <strong><?php echo esc_html($recent_title); ?></strong> &middot; <?php echo esc_html($recent_date); ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Tabs -->
            <div class="aspw-tabs">
                <div class="aspw-tabs__scroll">
                    <?php foreach ($types as $slug => $config) :
                        $url = add_query_arg([
                            'page'    => 'nv-product-templates',
                            'nv_type' => $slug,
                            'nv_status' => $selected_status,
                        ], admin_url('admin.php'));
                        $active_class = ($selected_type === $slug) ? ' is-active' : '';
                        ?>
                        <a href="<?php echo esc_url($url); ?>" class="aspw-tabs__tab<?php echo esc_attr($active_class); ?>">
                            <?php echo esc_html($config['label']); ?>
                            <span class="aspw-tabs__count"><?php echo (int) ($type_counts[$slug] ?? 0); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="aspw-status-links" aria-label="Template status filters">
                <?php $status_index = 0; ?>
                <?php foreach ($status_views as $status_slug => $status_view) :
                    $status_url = add_query_arg([
                        'page'      => 'nv-product-templates',
                        'nv_type'   => $selected_type,
                        'nv_status' => $status_slug,
                    ], admin_url('admin.php'));
                    $status_active_class = ($selected_status === $status_slug) ? ' is-active' : '';
                    if ($status_index > 0) : ?>
                        <span class="aspw-status-separator">|</span>
                    <?php endif; ?>
                    <a class="aspw-status-link<?php echo esc_attr($status_active_class); ?>" href="<?php echo esc_url($status_url); ?>">
                        <?php echo esc_html($status_view['label']); ?>
                        <span class="aspw-status-count">(<?php echo (int) $status_view['count']; ?>)</span>
                    </a>
                    <?php $status_index++; ?>
                <?php endforeach; ?>
            </div>

            <!-- Modals -->
            <div id="nv-pw-create-modal" class="aspw-modal-backdrop<?php echo $open_create_modal ? ' is-open' : ''; ?>" aria-hidden="<?php echo $open_create_modal ? 'false' : 'true'; ?>">
                <div class="aspw-modal" role="dialog" aria-modal="true" aria-labelledby="nv-pw-modal-title">
                    <div class="aspw-modal__header">
                        <h2 id="nv-pw-modal-title" class="aspw-modal__title">Create New Template</h2>
                        <button type="button" class="aspw-modal__close" id="nv-pw-close-create-modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="aspw-modal__body">
                        <p style="margin-top:0;color:#6B7280;">Choose a type, set a name, and optionally duplicate an existing template.</p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="nv_pw_create_template">
                            <?php wp_nonce_field('nv_pw_create_template'); ?>
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <tr>
                                        <th scope="row"><label for="nv_pw_template_name">Template Name</label></th>
                                        <td><input id="nv_pw_template_name" name="template_name" type="text" class="regular-text" placeholder="Example: NV Master Single Product V3" required></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="nv_pw_template_type">Template Type</label></th>
                                        <td>
                                            <select id="nv_pw_template_type" name="template_type">
                                                <?php foreach ($types as $slug => $config) :
                                                    if ($slug === 'all') { continue; } ?>
                                                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($default_kind, $slug); ?>><?php echo esc_html($config['label']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="nv_pw_duplicate_from">Duplicate From</label></th>
                                        <td>
                                            <select id="nv_pw_duplicate_from" name="duplicate_from" style="min-width:420px;max-width:100%;">
                                                <option value="0">Start from blank template</option>
                                                <?php foreach ($create_options as $option) : ?>
                                                    <option value="<?php echo esc_attr($option['id']); ?>"><?php echo esc_html($option['label']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="aspw-modal__actions">
                                <button type="submit" name="nv_pw_create_mode" value="create_edit" class="aspw-btn-primary">Create and Edit in Elementor</button>
                                <button type="submit" name="nv_pw_create_mode" value="create" class="aspw-btn-cancel">Create Template</button>
                                <button type="button" class="aspw-btn-cancel" id="nv-pw-cancel-create-modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="nv-pw-quick-edit-modal" class="aspw-modal-backdrop<?php echo $open_quick_edit_modal ? ' is-open' : ''; ?>" aria-hidden="<?php echo $open_quick_edit_modal ? 'false' : 'true'; ?>">
                <div class="aspw-modal" role="dialog" aria-modal="true" aria-labelledby="nv-pw-quick-edit-title">
                    <div class="aspw-modal__header">
                        <h2 id="nv-pw-quick-edit-title" class="aspw-modal__title">Quick Edit Template</h2>
                        <button type="button" class="aspw-modal__close" id="nv-pw-close-quick-edit-modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="aspw-modal__body">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="nv_pw_quick_edit_template">
                            <input type="hidden" name="template_id" id="nv-pw-quick-edit-template-id" value="<?php echo esc_attr($quick_edit_post instanceof \WP_Post ? (string) $quick_edit_post->ID : ''); ?>">
                            <input type="hidden" name="nv_type" value="<?php echo esc_attr($selected_type); ?>">
                            <input type="hidden" name="nv_status" value="<?php echo esc_attr($selected_status); ?>">
                            <?php wp_nonce_field('nv_pw_quick_edit_template'); ?>
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <tr>
                                        <th scope="row"><label for="nv-pw-quick-edit-title-input">Template Name</label></th>
                                        <td><input id="nv-pw-quick-edit-title-input" name="template_title" type="text" class="regular-text" value="<?php echo esc_attr($quick_edit_title_value); ?>" required></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="nv-pw-quick-edit-status-input">Status</label></th>
                                        <td>
                                            <select id="nv-pw-quick-edit-status-input" name="template_status">
                                                <option value="draft" <?php selected($quick_edit_status_value, 'draft'); ?>>Draft</option>
                                                <option value="publish" <?php selected($quick_edit_status_value, 'publish'); ?>>Published</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="nv-pw-quick-edit-assign-product">Assign Product</label></th>
                                        <td>
                                            <select id="nv-pw-quick-edit-assign-product" name="assign_product_id" style="min-width:420px;max-width:100%;">
                                                <option value="0">&mdash; No assignment change &mdash;</option>
                                                <?php foreach ($product_options as $product_option) : ?>
                                                    <option value="<?php echo esc_attr((string) $product_option['id']); ?>" <?php selected($quick_edit_assigned_product, (int) $product_option['id']); ?>><?php echo esc_html($product_option['label']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <p style="margin:6px 0 0;color:#9CA3AF;font-size:12px;">Optional: assign one product directly to this template.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="aspw-modal__actions">
                                <button type="submit" class="aspw-btn-primary">Update Template</button>
                                <button type="button" class="aspw-btn-cancel" id="nv-pw-cancel-quick-edit-modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <?php if (empty($templates)) : ?>
                <div class="aspw-empty">
                    <div class="aspw-empty__illustration">
                        <svg width="120" height="80" viewBox="0 0 120 80" fill="none">
                            <rect x="20" y="20" width="60" height="45" rx="6" fill="#EEF2FF" transform="rotate(-4 20 20)"/>
                            <rect x="28" y="14" width="60" height="45" rx="6" fill="#C7D2FE" transform="rotate(-1 28 14)"/>
                            <rect x="36" y="10" width="60" height="45" rx="6" fill="#818CF8" transform="rotate(2 36 10)"/>
                            <rect x="48" y="24" width="36" height="3" rx="1.5" fill="rgba(255,255,255,0.6)"/>
                            <rect x="48" y="31" width="28" height="3" rx="1.5" fill="rgba(255,255,255,0.4)"/>
                            <rect x="48" y="38" width="32" height="3" rx="1.5" fill="rgba(255,255,255,0.3)"/>
                        </svg>
                    </div>
                    <h3 class="aspw-empty__title">No templates yet</h3>
                    <p class="aspw-empty__desc">Create your first template to customize how your products, shop pages, and checkout look.</p>
                    <a id="nv-pw-open-create-modal-empty"
                       href="<?php echo esc_url(add_query_arg([
                            'page'        => 'nv-product-templates',
                            'show_create' => 1,
                            'nv_type'     => $selected_type,
                        ], admin_url('admin.php'))); ?>"
                       class="aspw-empty__cta">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Create Template
                    </a>
                </div>
            <?php else : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="nv_pw_bulk_template_action">
                    <input type="hidden" name="scope" value="selected">
                    <input type="hidden" name="nv_type" value="<?php echo esc_attr($selected_type); ?>">
                    <input type="hidden" name="nv_status" value="<?php echo esc_attr($selected_status); ?>">
                    <?php wp_nonce_field('nv_pw_bulk_template_action'); ?>

                    <div class="aspw-bulk-bar">
                        <select name="bulk_action" aria-label="Bulk action">
                            <option value="">Bulk actions</option>
                            <?php if ($selected_status === 'trash') : ?>
                                <option value="restore_selected">Restore selected templates</option>
                                <option value="delete_permanently">Delete permanently</option>
                            <?php else : ?>
                                <option value="export_selected">Export selected templates</option>
                                <option value="duplicate_selected">Duplicate selected templates</option>
                                <option value="trash_selected">Move to Trash</option>
                            <?php endif; ?>
                        </select>
                        <button type="submit" class="aspw-btn-secondary">Apply</button>
                    </div>

                    <div class="aspw-table-wrap">
                        <table class="aspw-table">
                            <thead>
                                <tr>
                                    <th class="col-check"><input id="nv-pw-select-all-templates" type="checkbox" class="aspw-check" aria-label="Select all templates"></th>
                                    <th class="col-name">Template</th>
                                    <th class="col-type">Type</th>
                                    <th class="col-status">Status</th>
                                    <th class="col-default">Default</th>
                                    <th class="col-assigned">Assigned To</th>
                                    <th class="col-modified">Modified</th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($templates as $tmpl) :
                                    $post        = $tmpl['post'];
                                    $kind        = $tmpl['kind'];
                                    $type_label  = $types[$kind]['label'] ?? 'Template';
                                    $is_trashed  = $post->post_status === 'trash';
                                    $count       = $kind === 'single_product' ? ($usage_map[$post->ID] ?? 0) : null;
                                    $is_defaultable = $this->can_have_default($kind);
                                    $is_default = $is_defaultable && (int) ($default_template_ids[$kind] ?? 0) === (int) $post->ID;
                                    $assigned_product_id_for_quick_edit = 0;
                                    $edit_url    = get_edit_post_link($post->ID, '');
                                    if (!$edit_url) {
                                        $edit_url = add_query_arg(['post' => $post->ID, 'action' => 'edit'], admin_url('post.php'));
                                    }
                                    $edit_url = add_query_arg('nv_pw_direct_edit', '1', $edit_url);
                                    if ($post->post_type === self::CPT) {
                                        $first_assigned = get_posts([
                                            'post_type'      => 'product',
                                            'post_status'    => ['publish', 'draft', 'private'],
                                            'posts_per_page' => 1,
                                            'fields'         => 'ids',
                                            'meta_key'       => self::META_KEY,
                                            'meta_value'     => (string) $post->ID,
                                            'orderby'        => 'ID',
                                            'order'          => 'ASC',
                                        ]);
                                        if (!empty($first_assigned)) {
                                            $assigned_product_id_for_quick_edit = (int) $first_assigned[0];
                                        }
                                    }
                                    $elementor_url = add_query_arg(['post' => $post->ID, 'action' => 'elementor'], admin_url('post.php'));
                                    $quick_edit_url = add_query_arg([
                                        'page'       => 'nv-product-templates',
                                        'nv_type'    => $selected_type,
                                        'nv_status'  => $selected_status,
                                        'quick_edit' => $post->ID,
                                    ], admin_url('admin.php'));
                                    $duplicate   = wp_nonce_url(
                                        add_query_arg(['action' => 'nv_pw_duplicate_template', 'template_id' => $post->ID, 'nv_type' => $selected_type, 'nv_status' => $selected_status], admin_url('admin-post.php')),
                                        'nv_pw_duplicate_template_' . $post->ID
                                    );
                                    $delete      = wp_nonce_url(
                                        add_query_arg(['action' => 'nv_pw_delete_template', 'template_id' => $post->ID, 'nv_type' => $selected_type, 'nv_status' => $selected_status], admin_url('admin-post.php')),
                                        'nv_pw_delete_template_' . $post->ID
                                    );
                                    $restore = wp_nonce_url(
                                        add_query_arg(['action' => 'nv_pw_bulk_template_action', 'bulk_action' => 'restore_selected', 'template_ids[]' => $post->ID, 'nv_type' => $selected_type, 'nv_status' => 'trash'], admin_url('admin-post.php')),
                                        'nv_pw_bulk_template_action'
                                    );
                                    $permanent_delete = wp_nonce_url(
                                        add_query_arg(['action' => 'nv_pw_bulk_template_action', 'bulk_action' => 'delete_permanently', 'template_ids[]' => $post->ID, 'nv_type' => $selected_type, 'nv_status' => 'trash'], admin_url('admin-post.php')),
                                        'nv_pw_bulk_template_action'
                                    );
                                    $export      = wp_nonce_url(
                                        add_query_arg(['action' => 'nv_pw_export_template', 'template_id' => $post->ID], admin_url('admin-post.php')),
                                        'nv_pw_export_template_' . $post->ID
                                    );
                                    $view_url = '';
                                    if ($post->post_status === 'publish') { $view_url = (string) get_permalink($post); }
                                    if ($view_url === '') { $view_url = (string) get_preview_post_link($post); }
                                    if ($view_url === '') { $view_url = $elementor_url; }
                                    $status_is_publish = $post->post_status === 'publish';
                                    $status_label = $is_trashed ? 'Trash' : ($status_is_publish ? 'Published' : 'Draft');
                                    $set_default = '';
                                    $clear_default = '';
                                    if ($is_defaultable) {
                                        $set_default = wp_nonce_url(
                                            add_query_arg(['action' => 'nv_pw_set_default_template', 'template_id' => $post->ID, 'target_kind' => $kind, 'nv_type' => $selected_type], admin_url('admin-post.php')),
                                            'nv_pw_set_default_template_' . $post->ID . '_' . $kind
                                        );
                                        $clear_default = wp_nonce_url(
                                            add_query_arg(['action' => 'nv_pw_clear_default_template', 'template_id' => $post->ID, 'target_kind' => $kind, 'nv_type' => $selected_type], admin_url('admin-post.php')),
                                            'nv_pw_clear_default_template_' . $post->ID . '_' . $kind
                                        );
                                    }
                                    ?>
                                    <tr>
                                        <td class="col-check">
                                            <input class="nv-pw-template-checkbox aspw-check" type="checkbox" name="template_ids[]" value="<?php echo esc_attr((string) $post->ID); ?>" aria-label="<?php echo esc_attr('Select ' . ($post->post_title ?: 'template')); ?>">
                                        </td>
                                        <td class="col-name">
                                            <a href="<?php echo esc_url($edit_url); ?>" class="aspw-tmpl-name"><?php echo esc_html($post->post_title ?: '(no title)'); ?></a>
                                        </td>
                                        <td class="col-type"><span class="aspw-badge aspw-badge--type"><?php echo esc_html($type_label); ?></span></td>
                                        <td class="col-status">
                                            <span class="aspw-badge <?php echo $is_trashed ? 'aspw-badge--trash' : ($status_is_publish ? 'aspw-badge--published' : 'aspw-badge--draft'); ?>">
                                                <?php echo esc_html($status_label); ?>
                                            </span>
                                        </td>
                                        <td class="col-default">
                                            <?php if ($is_trashed) : ?>
                                                <span class="aspw-assigned--na">&mdash;</span>
                                            <?php elseif (!$is_defaultable) : ?>
                                                <span class="aspw-assigned--na">&mdash;</span>
                                            <?php elseif ($is_default) : ?>
                                                <span class="aspw-badge aspw-badge--default">Default</span>
                                                <a href="<?php echo esc_url($clear_default); ?>" class="aspw-link-clear">Clear</a>
                                            <?php elseif (!$status_is_publish) : ?>
                                                <span class="aspw-publish-first">Publish first</span>
                                            <?php else : ?>
                                                <a href="<?php echo esc_url($set_default); ?>" class="aspw-link-default">Set default</a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($count === null) : ?>
                                                <span class="aspw-assigned--na">&mdash;</span>
                                            <?php elseif ($count > 0) : ?>
                                                <span class="aspw-assigned"><?php echo (int) $count; ?> product<?php echo $count !== 1 ? 's' : ''; ?></span>
                                            <?php else : ?>
                                                <span class="aspw-assigned--none">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="aspw-date"><?php echo esc_html(get_the_modified_date('M j, Y', $post->ID)); ?></span></td>
                                        <td class="col-actions">
                                            <div class="aspw-actions">
                                                <?php if ($is_trashed) : ?>
                                                    <a href="<?php echo esc_url($restore); ?>" class="aspw-btn-elementor">Restore</a>
                                                <?php else : ?>
                                                    <a href="<?php echo esc_url($elementor_url); ?>" class="aspw-btn-elementor">
                                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 2h16a2 2 0 012 2v16a2 2 0 01-2 2H4a2 2 0 01-2-2V4a2 2 0 012-2zm5 5v10h2V7H9zm4 0v10h2V7h-2z"/></svg>
                                                        Edit
                                                    </a>
                                                    <?php if ($count !== null) :
                                                        $is_assigned = ($count > 0);
                                                        ?>
                                                        <a href="<?php echo esc_url($quick_edit_url); ?>"
                                                           class="aspw-btn-assign nv-pw-quick-edit-link<?php echo $is_assigned ? ' aspw-btn-assign--set' : ' aspw-btn-assign--empty'; ?>"
                                                           data-template-id="<?php echo esc_attr((string) $post->ID); ?>"
                                                           data-template-title="<?php echo esc_attr((string) ($post->post_title ?: '')); ?>"
                                                           data-template-status="<?php echo esc_attr($status_is_publish ? 'publish' : 'draft'); ?>"
                                                           data-assigned-product-id="<?php echo esc_attr((string) $assigned_product_id_for_quick_edit); ?>"
                                                           title="<?php echo esc_attr($is_assigned ? 'Reassign this template to a different product' : 'Assign this template to a product'); ?>">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                                            <?php echo $is_assigned ? 'Reassign' : 'Assign'; ?>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <div class="aspw-row-dropdown-wrap">
                                                    <button type="button" class="aspw-row-menu-btn" aria-label="More actions">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                                                    </button>
                                                    <div class="aspw-row-dropdown">
                                                        <?php if ($is_trashed) : ?>
                                                            <a class="aspw-row-dropdown__item" href="<?php echo esc_url($restore); ?>">Restore</a>
                                                            <div class="aspw-row-dropdown__sep"></div>
                                                            <a class="aspw-row-dropdown__item aspw-row-dropdown__item--danger" href="<?php echo esc_url($permanent_delete); ?>"
                                                               onclick="return confirm('Delete this template permanently? This cannot be undone.');">Delete permanently</a>
                                                        <?php else : ?>
                                                            <a class="aspw-row-dropdown__item" href="<?php echo esc_url($edit_url); ?>">Edit (WP Editor)</a>
                                                            <a class="aspw-row-dropdown__item nv-pw-quick-edit-link" href="<?php echo esc_url($quick_edit_url); ?>"
                                                               data-template-id="<?php echo esc_attr((string) $post->ID); ?>"
                                                               data-template-title="<?php echo esc_attr((string) ($post->post_title ?: '')); ?>"
                                                               data-template-status="<?php echo esc_attr($status_is_publish ? 'publish' : 'draft'); ?>"
                                                               data-assigned-product-id="<?php echo esc_attr((string) $assigned_product_id_for_quick_edit); ?>">Quick Edit</a>
                                                            <a class="aspw-row-dropdown__item" href="<?php echo esc_url($duplicate); ?>">Duplicate</a>
                                                            <a class="aspw-row-dropdown__item" href="<?php echo esc_url($export); ?>">Export</a>
                                                            <a class="aspw-row-dropdown__item" href="<?php echo esc_url($view_url); ?>" target="_blank" rel="noopener">View</a>
                                                            <div class="aspw-row-dropdown__sep"></div>
                                                            <a class="aspw-row-dropdown__item aspw-row-dropdown__item--danger" href="<?php echo esc_url($delete); ?>"
                                                               onclick="return confirm('<?php echo esc_attr($kind === 'single_product' ? 'Move this template to trash? Products using it will revert to default layout.' : 'Move this template to trash?'); ?>');">Trash</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            <?php endif; ?>

            <script>
                (function () {
                    var modal = document.getElementById('nv-pw-create-modal');
                    var openers = document.querySelectorAll('#nv-pw-open-create-modal, #nv-pw-open-create-modal-empty');
                    var closeBtn = document.getElementById('nv-pw-close-create-modal');
                    var cancelBtn = document.getElementById('nv-pw-cancel-create-modal');
                    var nameInput = document.getElementById('nv_pw_template_name');
                    var quickModal = document.getElementById('nv-pw-quick-edit-modal');
                    var quickCloseBtn = document.getElementById('nv-pw-close-quick-edit-modal');
                    var quickCancelBtn = document.getElementById('nv-pw-cancel-quick-edit-modal');
                    var quickTitleInput = document.getElementById('nv-pw-quick-edit-title-input');
                    var quickStatusInput = document.getElementById('nv-pw-quick-edit-status-input');
                    var quickTemplateIdInput = document.getElementById('nv-pw-quick-edit-template-id');
                    var quickAssignProductInput = document.getElementById('nv-pw-quick-edit-assign-product');
                    var selectAllTemplates = document.getElementById('nv-pw-select-all-templates');

                    function openModal(targetModal, focusField) {
                        if (!targetModal) return;
                        targetModal.classList.add('is-open');
                        targetModal.setAttribute('aria-hidden', 'false');
                        if (focusField) setTimeout(function () { focusField.focus(); }, 30);
                    }
                    function closeModal(targetModal) {
                        if (!targetModal) return;
                        targetModal.classList.remove('is-open');
                        targetModal.setAttribute('aria-hidden', 'true');
                    }

                    openers.forEach(function (el) {
                        el.addEventListener('click', function (e) { e.preventDefault(); openModal(modal, nameInput); });
                    });

                    document.addEventListener('click', function (e) {
                        var link = e.target.closest('.nv-pw-quick-edit-link');
                        if (!link) return;
                        e.preventDefault();
                        if (quickTemplateIdInput) quickTemplateIdInput.value = link.getAttribute('data-template-id') || '';
                        if (quickTitleInput) quickTitleInput.value = link.getAttribute('data-template-title') || '';
                        if (quickStatusInput) quickStatusInput.value = link.getAttribute('data-template-status') || 'draft';
                        if (quickAssignProductInput) quickAssignProductInput.value = link.getAttribute('data-assigned-product-id') || '0';
                        openModal(quickModal, quickTitleInput);
                    });

                    if (selectAllTemplates) {
                        selectAllTemplates.addEventListener('change', function () {
                            document.querySelectorAll('.nv-pw-template-checkbox').forEach(function (cb) { cb.checked = selectAllTemplates.checked; });
                        });
                    }

                    [closeBtn, cancelBtn].forEach(function (btn) { if (btn && modal) btn.addEventListener('click', function () { closeModal(modal); }); });
                    [quickCloseBtn, quickCancelBtn].forEach(function (btn) { if (btn && quickModal) btn.addEventListener('click', function () { closeModal(quickModal); }); });

                    [modal, quickModal].forEach(function (m) {
                        if (!m) return;
                        m.addEventListener('click', function (e) { if (e.target === m) closeModal(m); });
                    });

                    document.addEventListener('keydown', function (e) {
                        if (e.key !== 'Escape') return;
                        if (modal && modal.classList.contains('is-open')) closeModal(modal);
                        if (quickModal && quickModal.classList.contains('is-open')) closeModal(quickModal);
                    });

                    var headerToggle = document.getElementById('aspw-header-menu-toggle');
                    var headerDropdown = document.getElementById('aspw-header-dropdown');
                    if (headerToggle && headerDropdown) {
                        headerToggle.addEventListener('click', function (e) {
                            e.stopPropagation();
                            headerDropdown.classList.toggle('is-open');
                        });
                    }

                    var templateImportForm = document.getElementById('nv-pw-template-import-form');
                    var templateFileInput = document.getElementById('nv-pw-template-files');
                    var templateDropzone = document.getElementById('nv-pw-template-dropzone');
                    var templateUploadStatus = document.getElementById('nv-pw-template-upload-status');
                    var templateUploadSubmit = templateImportForm ? templateImportForm.querySelector('.aspw-upload-submit') : null;
                    var templateImportSubmitting = false;

                    function updateTemplateUploadStatus(message) {
                        if (templateUploadStatus) {
                            templateUploadStatus.textContent = message;
                        }
                    }

                    function lockTemplateImportSubmit(message) {
                        templateImportSubmitting = true;
                        updateTemplateUploadStatus(message);
                        if (templateUploadSubmit) {
                            templateUploadSubmit.disabled = true;
                            templateUploadSubmit.textContent = 'Uploading...';
                        }
                        if (templateDropzone) {
                            templateDropzone.classList.add('is-uploading');
                        }
                    }

                    function getJsonTemplateFiles(fileList) {
                        return Array.prototype.filter.call(fileList || [], function (file) {
                            var name = file && file.name ? file.name : '';
                            var type = file && file.type ? file.type : '';
                            return /\.json$/i.test(name) || type === 'application/json' || type === '';
                        });
                    }

                    function submitTemplateImport(files) {
                        if (templateImportSubmitting || !templateImportForm || !templateFileInput || !files || !files.length) return;

                        var jsonFiles = getJsonTemplateFiles(files);
                        if (!jsonFiles.length) {
                            updateTemplateUploadStatus('Choose JSON files');
                            return;
                        }

                        lockTemplateImportSubmit(jsonFiles.length + ' file' + (jsonFiles.length === 1 ? '' : 's') + ' selected');

                        if (window.DataTransfer && files !== templateFileInput.files) {
                            var transfer = new DataTransfer();
                            jsonFiles.forEach(function (file) { transfer.items.add(file); });
                            templateFileInput.files = transfer.files;
                        }

                        if (!templateFileInput.files || !templateFileInput.files.length) {
                            templateImportSubmitting = false;
                            if (templateUploadSubmit) {
                                templateUploadSubmit.disabled = false;
                                templateUploadSubmit.textContent = 'Upload Templates';
                            }
                            if (templateDropzone) {
                                templateDropzone.classList.remove('is-uploading');
                            }
                            updateTemplateUploadStatus('Choose JSON files');
                            return;
                        }

                        templateImportForm.submit();
                    }

                    if (templateImportForm) {
                        templateImportForm.addEventListener('submit', function (e) {
                            if (templateImportSubmitting) {
                                e.preventDefault();
                                return;
                            }
                            if (templateFileInput && templateFileInput.files && templateFileInput.files.length) {
                                lockTemplateImportSubmit(templateFileInput.files.length + ' file' + (templateFileInput.files.length === 1 ? '' : 's') + ' selected');
                            }
                        });
                    }

                    if (templateFileInput) {
                        templateFileInput.addEventListener('change', function () {
                            submitTemplateImport(templateFileInput.files);
                        });
                    }

                    if (templateDropzone) {
                        ['dragenter', 'dragover'].forEach(function (eventName) {
                            templateDropzone.addEventListener(eventName, function (e) {
                                e.preventDefault();
                                e.stopPropagation();
                                templateDropzone.classList.add('is-dragover');
                            });
                        });

                        ['dragleave', 'drop'].forEach(function (eventName) {
                            templateDropzone.addEventListener(eventName, function (e) {
                                e.preventDefault();
                                e.stopPropagation();
                                templateDropzone.classList.remove('is-dragover');
                            });
                        });

                        templateDropzone.addEventListener('drop', function (e) {
                            submitTemplateImport(e.dataTransfer ? e.dataTransfer.files : null);
                        });

                        templateDropzone.addEventListener('keydown', function (e) {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                templateDropzone.click();
                            }
                        });
                    }

                    document.addEventListener('click', function (e) {
                        var rowBtn = e.target.closest('.aspw-row-menu-btn');
                        if (rowBtn) {
                            e.stopPropagation();
                            var dd = rowBtn.nextElementSibling;
                            document.querySelectorAll('.aspw-row-dropdown.is-open').forEach(function (d) { if (d !== dd) d.classList.remove('is-open'); });
                            dd.classList.toggle('is-open');
                            return;
                        }
                        if (!e.target.closest('.aspw-header__right')) {
                            if (headerDropdown) headerDropdown.classList.remove('is-open');
                        }
                        if (!e.target.closest('.aspw-row-dropdown-wrap')) {
                            document.querySelectorAll('.aspw-row-dropdown.is-open').forEach(function (d) { d.classList.remove('is-open'); });
                        }
                    });
                })();

                (function () {
                    var wrap = document.querySelector('.aspw-wrap');
                    var btn  = document.getElementById('aspw-theme-toggle');
                    var icon = document.getElementById('aspw-theme-icon');
                    if (!wrap || !btn || !icon) return;
                    var MOON = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/>';
                    var SUN  = '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>';
                    function apply(dark) {
                        wrap.classList.toggle('aspw-dark', dark);
                        document.body.classList.toggle('aspw-dark-active', dark);
                        icon.innerHTML = dark ? SUN : MOON;
                        var label = dark ? 'Switch to light mode' : 'Switch to dark mode';
                        btn.setAttribute('aria-label', label);
                        btn.setAttribute('title', label);
                    }
                    var saved = null;
                    try { saved = window.localStorage.getItem('aspwTheme'); } catch (e) {}
                    apply(saved === 'dark');
                    btn.addEventListener('click', function () {
                        var dark = !wrap.classList.contains('aspw-dark');
                        apply(dark);
                        try { window.localStorage.setItem('aspwTheme', dark ? 'dark' : 'light'); } catch (e) {}
                    });
                    var imp = document.getElementById('aspw-qa-import');
                    if (imp) {
                        imp.addEventListener('click', function () {
                            var panel = document.getElementById('nv-pw-template-import-form');
                            var zone = document.getElementById('nv-pw-template-dropzone');
                            if (panel) {
                                panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                if (zone) {
                                    zone.classList.add('is-flash');
                                    setTimeout(function () { zone.classList.remove('is-flash'); }, 900);
                                    try { zone.focus({ preventScroll: true }); } catch (e) { zone.focus(); }
                                }
                            }
                        });
                    }
                })();
            </script>

        </div>
        <?php
    }

    private function render_admin_notices(): void {
        $created_id      = isset($_GET['created']) ? (int) $_GET['created'] : 0;
        $duplicated_id   = isset($_GET['duplicated']) ? (int) $_GET['duplicated'] : 0;
        $imported_id     = isset($_GET['imported']) ? (int) $_GET['imported'] : 0;
        $imported_count  = isset($_GET['imported_count']) ? (int) $_GET['imported_count'] : 0;
        $import_failed_count = isset($_GET['import_failed_count']) ? (int) $_GET['import_failed_count'] : 0;
        $duplicate_skipped_count = isset($_GET['duplicate_skipped_count']) ? (int) $_GET['duplicate_skipped_count'] : 0;
        $duplicated_count = isset($_GET['duplicated_count']) ? (int) $_GET['duplicated_count'] : 0;
        $trashed_count = isset($_GET['trashed']) ? (int) $_GET['trashed'] : 0;
        $restored_count = isset($_GET['restored']) ? (int) $_GET['restored'] : 0;
        $permanently_deleted_count = isset($_GET['permanently_deleted']) ? (int) $_GET['permanently_deleted'] : 0;
        $quick_updated   = isset($_GET['quick_updated']) ? (int) $_GET['quick_updated'] : 0;
        $assigned_product = isset($_GET['assigned_product']) ? (int) $_GET['assigned_product'] : 0;
        $default_set     = isset($_GET['default_set']) ? (int) $_GET['default_set'] : 0;
        $default_cleared = isset($_GET['default_cleared']) ? sanitize_key((string) $_GET['default_cleared']) : '';
        $notice_key      = sanitize_key((string) ($_GET['nv_pw_notice'] ?? ''));

        if ($created_id > 0) {
            $edit_url = add_query_arg(['post' => $created_id, 'action' => 'elementor'], admin_url('post.php'));
            echo '<div class="aspw-notice aspw-notice--success"><p>Template created. <a href="' . esc_url($edit_url) . '">Edit it in Elementor &rarr;</a></p></div>';
        }

        if ($duplicated_id > 0) {
            $edit_url = add_query_arg(['post' => $duplicated_id, 'action' => 'elementor'], admin_url('post.php'));
            echo '<div class="aspw-notice aspw-notice--success"><p>Template duplicated. <a href="' . esc_url($edit_url) . '">Edit the duplicate in Elementor &rarr;</a></p></div>';
        }

        if ($duplicated_count > 0) {
            echo '<div class="aspw-notice aspw-notice--success"><p>' . esc_html((string) $duplicated_count) . ' template' . ($duplicated_count === 1 ? '' : 's') . ' duplicated.</p></div>';
        }

        if ($trashed_count > 0) {
            echo '<div class="aspw-notice aspw-notice--success"><p>' . esc_html((string) $trashed_count) . ' template' . ($trashed_count === 1 ? '' : 's') . ' moved to Trash.</p></div>';
        }

        if ($restored_count > 0) {
            echo '<div class="aspw-notice aspw-notice--success"><p>' . esc_html((string) $restored_count) . ' template' . ($restored_count === 1 ? '' : 's') . ' restored.</p></div>';
        }

        if ($permanently_deleted_count > 0) {
            echo '<div class="aspw-notice aspw-notice--success"><p>' . esc_html((string) $permanently_deleted_count) . ' template' . ($permanently_deleted_count === 1 ? '' : 's') . ' permanently deleted.</p></div>';
        }

        if ($quick_updated > 0) {
            $edit_url = add_query_arg(['post' => $quick_updated, 'action' => 'elementor'], admin_url('post.php'));
            echo '<div class="aspw-notice aspw-notice--success"><p>Template updated. <a href="' . esc_url($edit_url) . '">Edit with Elementor &rarr;</a></p></div>';
        }

        if ($assigned_product > 0) {
            $product_title = get_the_title($assigned_product);
            $product_edit_url = get_edit_post_link($assigned_product, '');
            if ($product_title === '') {
                $product_title = 'Product #' . $assigned_product;
            }
            if ($product_edit_url) {
                echo '<div class="aspw-notice aspw-notice--success"><p>Template assigned to <a href="' . esc_url($product_edit_url) . '">' . esc_html($product_title) . '</a>.</p></div>';
            } else {
                echo '<div class="aspw-notice aspw-notice--success"><p>Template assigned to ' . esc_html($product_title) . '.</p></div>';
            }
        }

        if ($imported_count > 1) {
            $message = (int) $imported_count . ' templates imported.';
            if ($import_failed_count > 0) {
                $message .= ' ' . (int) $import_failed_count . ' file' . ($import_failed_count === 1 ? '' : 's') . ' skipped.';
            }
            echo '<div class="aspw-notice aspw-notice--success"><p>' . esc_html($message) . '</p></div>';
        } elseif ($imported_id > 0) {
            $edit_url = add_query_arg(['post' => $imported_id, 'action' => 'elementor'], admin_url('post.php'));
            $message = 'Template imported.';
            if ($import_failed_count > 0) {
                $message .= ' ' . (int) $import_failed_count . ' file' . ($import_failed_count === 1 ? '' : 's') . ' skipped.';
            }
            echo '<div class="aspw-notice aspw-notice--success"><p>' . esc_html($message) . ' <a href="' . esc_url($edit_url) . '">Edit it in Elementor &rarr;</a></p></div>';
        }

        if ($duplicate_skipped_count > 0) {
            $message = (int) $duplicate_skipped_count . ' duplicate template' . ($duplicate_skipped_count === 1 ? '' : 's') . ' skipped. The template already exists, so it was not imported again.';
            echo '<div class="aspw-notice aspw-notice--warning"><p>' . esc_html($message) . '</p></div>';
        }

        if ($default_set > 0) {
            $title = get_the_title($default_set);
            if ($title === '') {
                $title = 'Template #' . $default_set;
            }
            echo '<div class="aspw-notice aspw-notice--success"><p>Default template set: ' . esc_html($title) . '.</p></div>';
        }

        if ($default_cleared !== '') {
            $types = $this->get_template_types();
            $label = $types[$default_cleared]['label'] ?? 'Template';
            echo '<div class="aspw-notice aspw-notice--success"><p>Default ' . esc_html($label) . ' template cleared.</p></div>';
        }

        if ($notice_key === 'elementor_exit') {
            echo '<div class="aspw-notice aspw-notice--info"><p>Returned from Elementor to Product Templates.</p></div>';
        }

        if ($notice_key === 'shoplentor_duplicated') {
            echo '<div class="aspw-notice aspw-notice--info"><p>ShopLentor template duplicated into NV Builder. Review it in Elementor and replace any ShopLentor-only widgets before deactivating ShopLentor.</p></div>';
        }

        if (isset($_GET['deleted'])) {
            echo '<div class="aspw-notice aspw-notice--success"><p>Template moved to Trash.</p></div>';
        }

        if (isset($_GET['error'])) {
            echo '<div class="aspw-notice aspw-notice--error"><p>Unable to complete that action. Please try again.</p></div>';
        }
    }

    /* ─────────────────────────────────────────────────────────
       6. HELPERS
    ───────────────────────────────────────────────────────── */

    private function get_template_usage_map(): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_value, COUNT(*) as cnt FROM {$wpdb->postmeta}
                 WHERE meta_key = %s AND meta_value != ''
                 GROUP BY meta_value",
                self::META_KEY
            ),
            ARRAY_A
        );

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['meta_value']] = (int) $row['cnt'];
        }

        return $map;
    }

    private function get_product_assignment_options(): array {
        $product_ids = get_posts([
            'post_type'      => 'product',
            'post_status'    => ['publish', 'draft', 'private'],
            'posts_per_page' => 500,
            'fields'         => 'ids',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        $options = [];
        foreach ($product_ids as $product_id) {
            $product_id = (int) $product_id;
            if ($product_id <= 0) {
                continue;
            }
            $title = get_the_title($product_id);
            if (!is_string($title) || $title === '') {
                $title = '(no title)';
            }

            $options[] = [
                'id'    => $product_id,
                'label' => $title . ' (#' . $product_id . ')',
            ];
        }

        return $options;
    }

    private function get_defaultable_template_kinds(): array {
        return ['single_product', 'shop'];
    }

    private function sanitize_template_status_filter($value): string {
        $value = sanitize_key((string) $value);
        if (in_array($value, ['all', 'publish', 'draft', 'trash'], true)) {
            return $value;
        }

        return 'all';
    }

    private function get_template_post_statuses_for_filter(string $status_filter): array {
        if ($status_filter === 'publish') {
            return ['publish'];
        }

        if ($status_filter === 'draft') {
            return ['draft'];
        }

        if ($status_filter === 'trash') {
            return ['trash'];
        }

        return ['publish', 'draft'];
    }

    private function get_template_status_views(string $selected_type): array {
        return [
            'all' => [
                'label' => 'All',
                'count' => count($this->get_templates_for_admin($selected_type, 'all')),
            ],
            'publish' => [
                'label' => 'Published',
                'count' => count($this->get_templates_for_admin($selected_type, 'publish')),
            ],
            'draft' => [
                'label' => 'Draft',
                'count' => count($this->get_templates_for_admin($selected_type, 'draft')),
            ],
            'trash' => [
                'label' => 'Trash',
                'count' => count($this->get_templates_for_admin($selected_type, 'trash')),
            ],
        ];
    }

    private function can_have_default(string $kind): bool {
        return in_array($kind, $this->get_defaultable_template_kinds(), true);
    }

    private function get_default_template_ids(): array {
        $saved = get_option(self::DEFAULT_TEMPLATES_OPTION, []);
        if (!is_array($saved)) {
            return [];
        }

        $ids = [];
        foreach ($this->get_defaultable_template_kinds() as $kind) {
            $ids[$kind] = isset($saved[$kind]) ? absint($saved[$kind]) : 0;
        }

        return $ids;
    }

    private function get_default_template_id(string $kind): int {
        if (!$this->can_have_default($kind)) {
            return 0;
        }

        $ids = $this->get_default_template_ids();
        $template_id = (int) ($ids[$kind] ?? 0);
        if ($template_id <= 0 || !$this->is_valid_default_template($template_id, $kind)) {
            return 0;
        }

        return $template_id;
    }

    private function set_default_template_id(string $kind, int $template_id): bool {
        if (!$this->is_valid_default_template($template_id, $kind)) {
            return false;
        }

        $ids = $this->get_default_template_ids();
        $ids[$kind] = $template_id;

        return (bool) update_option(self::DEFAULT_TEMPLATES_OPTION, $ids, false);
    }

    private function clear_default_template_id(string $kind, int $template_id = 0): void {
        if (!$this->can_have_default($kind)) {
            return;
        }

        $ids = $this->get_default_template_ids();
        if ($template_id > 0 && (int) ($ids[$kind] ?? 0) !== $template_id) {
            return;
        }

        $ids[$kind] = 0;
        update_option(self::DEFAULT_TEMPLATES_OPTION, $ids, false);
    }

    private function is_valid_default_template(int $template_id, string $kind): bool {
        if (!$this->can_have_default($kind)) {
            return false;
        }

        $post = get_post($template_id);
        if (!$post instanceof \WP_Post || $post->post_status !== 'publish') {
            return false;
        }

        if ($kind === 'single_product') {
            return $post->post_type === self::CPT && $this->detect_template_kind($post) === 'single_product';
        }

        if ($kind === 'shop') {
            return $post->post_type === 'elementor_library' && $this->detect_template_kind($post) === 'shop';
        }

        return false;
    }

    private function get_template_types(): array {
        return [
            'all'            => ['label' => 'All'],
            'shop'           => ['label' => 'Shop',           'post_type' => 'elementor_library', 'elementor_type' => 'archive'],
            'single_product' => ['label' => 'Single Product', 'post_type' => self::CPT,           'elementor_type' => 'single-product'],
            'archive'        => ['label' => 'Archive',        'post_type' => 'elementor_library', 'elementor_type' => 'archive'],
            'cart'           => ['label' => 'Cart',           'post_type' => 'elementor_library', 'elementor_type' => 'page'],
            'checkout'       => ['label' => 'Checkout',       'post_type' => 'elementor_library', 'elementor_type' => 'page'],
            'thank_you'      => ['label' => 'Thank You',      'post_type' => 'elementor_library', 'elementor_type' => 'page'],
            'my_account'     => ['label' => 'My Account',     'post_type' => 'elementor_library', 'elementor_type' => 'page'],
            'page'           => ['label' => 'Page',           'post_type' => 'elementor_library', 'elementor_type' => 'page'],
            'section'        => ['label' => 'Section',        'post_type' => 'elementor_library', 'elementor_type' => 'section'],
            'container'      => ['label' => 'Container',      'post_type' => 'elementor_library', 'elementor_type' => 'container'],
            'global_widget'  => ['label' => 'Global Widget',  'post_type' => 'elementor_library', 'elementor_type' => 'widget'],
        ];
    }

    private function sanitize_template_type($value, bool $allow_all = false): string {
        $value = sanitize_key((string) $value);
        $types = $this->get_template_types();

        if ($allow_all && $value === 'all') {
            return 'all';
        }

        if (isset($types[$value]) && $value !== 'all') {
            return $value;
        }

        return 'single_product';
    }

    private function is_supported_duplicate_source(\WP_Post $post): bool {
        if ($post->post_type === self::CPT) {
            return true;
        }

        if ($post->post_type === 'woolentor-template') {
            return true;
        }

        if ($post->post_type !== 'elementor_library') {
            return false;
        }

        $kind = $this->detect_template_kind($post);
        if ($kind !== '') {
            return true;
        }

        $etype = sanitize_key((string) get_post_meta($post->ID, '_elementor_template_type', true));
        return in_array($etype, ['page', 'section', 'container', 'widget', 'archive', 'single', 'single-product', 'product'], true);
    }

    private function normalize_external_template_kind(string $raw): string {
        $raw = strtolower($raw);
        $raw = str_replace(['_', '-'], ' ', $raw);

        if (strpos($raw, 'shop') !== false) {
            return 'shop';
        }
        if (strpos($raw, 'single') !== false || strpos($raw, 'product') !== false) {
            return 'single_product';
        }
        if (strpos($raw, 'archive') !== false || strpos($raw, 'category') !== false) {
            return 'archive';
        }
        if (strpos($raw, 'cart') !== false) {
            return 'cart';
        }
        if (strpos($raw, 'checkout') !== false) {
            return 'checkout';
        }
        if (strpos($raw, 'thank') !== false) {
            return 'thank_you';
        }
        if (strpos($raw, 'account') !== false) {
            return 'my_account';
        }

        return '';
    }

    private function detect_shoplentor_template_kind(\WP_Post $post): string {
        if ($post->post_type !== 'woolentor-template') {
            return '';
        }

        $meta_keys = [
            'woolentor_template_type',
            '_woolentor_template_type',
            'woolentor_template_meta_type',
            '_woolentor_template_meta_type',
            'woolentor_template_builder_type',
            '_woolentor_template_builder_type',
            'wl_template_type',
            '_wl_template_type',
        ];

        foreach ($meta_keys as $meta_key) {
            $value = get_post_meta($post->ID, $meta_key, true);
            if (is_array($value)) {
                $value = implode(' ', array_map('strval', $value));
            }
            $kind = $this->normalize_external_template_kind((string) $value);
            if ($kind !== '') {
                return $kind;
            }
        }

        return $this->normalize_external_template_kind((string) $post->post_title);
    }

    private function detect_template_kind(\WP_Post $post): string {
        $types = $this->get_template_types();

        $saved = sanitize_key((string) get_post_meta($post->ID, self::TEMPLATE_KIND_META, true));
        if ($saved !== '' && isset($types[$saved]) && $saved !== 'all') {
            return $saved;
        }

        if ($post->post_type === self::CPT) {
            return 'single_product';
        }

        if ($post->post_type === 'woolentor-template') {
            return $this->detect_shoplentor_template_kind($post);
        }

        if ($post->post_type !== 'elementor_library') {
            return '';
        }

        $etype = sanitize_key((string) get_post_meta($post->ID, '_elementor_template_type', true));
        if (in_array($etype, ['single', 'single-product', 'product'], true)) {
            return 'single_product';
        }
        if ($etype === 'section') {
            return 'section';
        }
        if ($etype === 'container') {
            return 'container';
        }
        if ($etype === 'widget') {
            return 'global_widget';
        }
        if ($etype === 'archive') {
            return 'archive';
        }

        return 'page';
    }

    private function get_templates_for_admin(string $selected_type, string $status_filter = 'all'): array {
        $templates = [];
        $post_statuses = $this->get_template_post_statuses_for_filter($status_filter);

        if ($selected_type === 'all' || $selected_type === 'single_product') {
            $product_templates = get_posts([
                'post_type'      => self::CPT,
                'post_status'    => $post_statuses,
                'posts_per_page' => -1,
                'orderby'        => 'modified',
                'order'          => 'DESC',
            ]);

            foreach ($product_templates as $post) {
                $templates[] = [
                    'post' => $post,
                    'kind' => 'single_product',
                ];
            }
        }

        if ($selected_type !== 'single_product') {
            $library_args = [
                'post_type'      => 'elementor_library',
                'post_status'    => $post_statuses,
                'posts_per_page' => -1,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'meta_query'     => [],
            ];

            if ($selected_type === 'all') {
                $library_args['meta_query'][] = [
                    'key'     => self::TEMPLATE_KIND_META,
                    'value'   => ['shop', 'archive', 'cart', 'checkout', 'thank_you', 'my_account', 'page', 'section', 'container', 'global_widget'],
                    'compare' => 'IN',
                ];
            } elseif (in_array($selected_type, ['shop', 'cart', 'checkout', 'thank_you', 'my_account'], true)) {
                $library_args['meta_query'][] = [
                    'key'   => self::TEMPLATE_KIND_META,
                    'value' => $selected_type,
                ];
            } else {
                $etype = $this->get_template_types()[$selected_type]['elementor_type'] ?? 'page';
                $library_args['meta_query'] = [
                    'relation' => 'OR',
                    [
                        'key'   => self::TEMPLATE_KIND_META,
                        'value' => $selected_type,
                    ],
                    [
                        'relation' => 'AND',
                        [
                            'key'     => self::TEMPLATE_KIND_META,
                            'compare' => 'NOT EXISTS',
                        ],
                        [
                            'key'   => '_elementor_template_type',
                            'value' => $etype,
                        ],
                    ],
                ];
            }

            $library_templates = get_posts($library_args);
            foreach ($library_templates as $post) {
                $kind = $this->detect_template_kind($post);
                if ($kind === '' || $kind === 'single_product') {
                    continue;
                }
                $templates[] = [
                    'post' => $post,
                    'kind' => $kind,
                ];
            }
        }

        usort($templates, function(array $a, array $b): int {
            return strcmp((string) $b['post']->post_modified_gmt, (string) $a['post']->post_modified_gmt);
        });

        return $templates;
    }

    private function get_duplicate_source_options(): array {
        $options = [];
        $posts = get_posts([
            'post_type'      => [self::CPT, 'elementor_library', 'woolentor-template'],
            'post_status'    => ['publish', 'draft'],
            'posts_per_page' => 250,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ]);

        $types = $this->get_template_types();

        foreach ($posts as $post) {
            if (!$this->is_supported_duplicate_source($post)) {
                continue;
            }

            $kind = $this->detect_template_kind($post);
            if ($kind === '' && $post->post_type !== 'woolentor-template') {
                continue;
            }

            $source_label = $post->post_type === 'woolentor-template' ? 'ShopLentor' : ($types[$kind]['label'] ?? 'Template');
            $label = $source_label . ' · ' . ($post->post_title ?: '(no title)') . ' (#' . $post->ID . ')';
            $options[] = [
                'id'    => $post->ID,
                'label' => $label,
            ];
        }

        return $options;
    }

    /**
     * @param int[] $template_ids
     * @return \WP_Post[]
     */
    private function get_template_posts_for_bulk_action(array $template_ids, string $status_filter): array {
        $posts = [];
        $allowed_statuses = $this->get_template_post_statuses_for_filter($status_filter);

        foreach ($template_ids as $template_id) {
            $template_id = absint($template_id);
            if ($template_id <= 0 || isset($posts[$template_id])) {
                continue;
            }

            $post = get_post($template_id);
            if (!$post instanceof \WP_Post || !$this->is_supported_duplicate_source($post)) {
                continue;
            }

            if (!in_array((string) $post->post_status, $allowed_statuses, true)) {
                continue;
            }

            $posts[$template_id] = $post;
        }

        return array_values($posts);
    }

    private function cleanup_template_before_removal(int $template_id, \WP_Post $post): void {
        if ($post->post_type === self::CPT) {
            global $wpdb;
            $wpdb->delete($wpdb->postmeta, [
                'meta_key'   => self::META_KEY,
                'meta_value' => $template_id,
            ]);
        }

        $kind = $this->detect_template_kind($post);
        if ($this->can_have_default($kind)) {
            $this->clear_default_template_id($kind, $template_id);
        }
    }

    private function build_default_template_name(string $kind): string {
        $types = $this->get_template_types();
        $label = $types[$kind]['label'] ?? 'Template';
        return $label . ' Template ' . wp_date('d M Y H:i');
    }

    private function should_preserve_json_meta_slashes(string $meta_key, string $meta_value): bool {
        if (strpos($meta_key, '_elementor_') !== 0) {
            return false;
        }

        $trimmed = trim($meta_value);
        if ($trimmed === '') {
            return false;
        }

        $first = $trimmed[0];
        if ($first !== '{' && $first !== '[') {
            return false;
        }

        json_decode($trimmed, true);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function maybe_prepare_meta_value_for_insert(string $meta_key, $meta_value) {
        if (is_string($meta_value) && $this->should_preserve_json_meta_slashes($meta_key, $meta_value)) {
            return wp_slash($meta_value);
        }

        return $meta_value;
    }

    private function copy_elementor_document_meta(int $source_id, int $target_id): void {
        foreach (['_elementor_data', '_elementor_page_settings'] as $meta_key) {
            $meta_value = get_post_meta($source_id, $meta_key, true);

            if (is_string($meta_value) && $meta_value !== '') {
                update_post_meta($target_id, $meta_key, $this->maybe_prepare_meta_value_for_insert($meta_key, $meta_value));
                continue;
            }

            if (is_array($meta_value) && !empty($meta_value)) {
                update_post_meta($target_id, $meta_key, $meta_value);
            }
        }
    }

    private function create_template(string $kind, string $name = '') {
        $types = $this->get_template_types();
        if (!isset($types[$kind]) || $kind === 'all') {
            return new \WP_Error('invalid_template_type', 'Invalid template type.');
        }

        $post_type = $types[$kind]['post_type'] ?? self::CPT;
        $post_id = wp_insert_post([
            'post_title'   => $name !== '' ? $name : $this->build_default_template_name($kind),
            'post_type'    => $post_type,
            'post_status'  => 'draft',
            'post_author'  => get_current_user_id(),
            'post_content' => '',
        ], true);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        update_post_meta($post_id, self::TEMPLATE_KIND_META, $kind);
        update_post_meta($post_id, '_elementor_edit_mode', 'builder');
        $etype = $types[$kind]['elementor_type'] ?? 'page';
        update_post_meta($post_id, '_elementor_template_type', $etype);

        return $post_id;
    }

    private function create_template_from_source(int $source_id, string $target_kind, string $name = '') {
        $source = get_post($source_id);
        if (!$source instanceof \WP_Post || !$this->is_supported_duplicate_source($source)) {
            return new \WP_Error('invalid_source', 'Invalid source template.');
        }

        $new_title = trim($name);
        if ($new_title === '') {
            $new_title = ($source->post_title ?: 'Template') . ' Copy';
        }

        $new_id = $this->create_template($target_kind, $new_title);
        if (is_wp_error($new_id)) {
            return $new_id;
        }

        wp_update_post([
            'ID'           => $new_id,
            'post_content' => $source->post_content,
            'post_excerpt' => $source->post_excerpt,
        ]);

        $skip_meta = [
            '_edit_lock',
            '_edit_last',
            '_wp_old_slug',
            self::TEMPLATE_KIND_META,
        ];

        $meta = get_post_meta($source_id);
        foreach ($meta as $meta_key => $values) {
            if (in_array($meta_key, $skip_meta, true)) {
                continue;
            }

            delete_post_meta($new_id, $meta_key);
            foreach ($values as $value) {
                $meta_value = maybe_unserialize($value);
                $meta_value = $this->maybe_prepare_meta_value_for_insert((string) $meta_key, $meta_value);
                add_post_meta($new_id, $meta_key, $meta_value);
            }
        }

        $this->copy_elementor_document_meta((int) $source_id, (int) $new_id);

        update_post_meta($new_id, self::TEMPLATE_KIND_META, $target_kind);
        update_post_meta($new_id, '_elementor_edit_mode', 'builder');
        $types = $this->get_template_types();
        update_post_meta($new_id, '_elementor_template_type', $types[$target_kind]['elementor_type'] ?? 'page');

        return $new_id;
    }

    private function get_template_export_payload(\WP_Post $post): array {
        $kind = $this->detect_template_kind($post);
        if ($kind === '') {
            $kind = 'single_product';
        }

        return [
            'schema'      => 'nv_pw_template_export_v1',
            'exported_at' => gmdate('c'),
            'source_site' => home_url('/'),
            'template'    => [
                'title'      => (string) $post->post_title,
                'content'    => (string) $post->post_content,
                'excerpt'    => (string) $post->post_excerpt,
                'status'     => (string) $post->post_status,
                'kind'       => $kind,
                'post_type'  => (string) $post->post_type,
                'meta'       => $this->get_exportable_meta($post->ID),
                'terms'      => $this->get_exportable_terms($post),
            ],
        ];
    }

    /**
     * @param int[] $template_ids
     * @return \WP_Post[]
     */
    private function get_template_posts_for_bulk_export(string $scope, string $selected_type, array $template_ids = []): array {
        $posts = [];

        if ($scope === 'selected') {
            foreach ($template_ids as $template_id) {
                $post = get_post((int) $template_id);
                if (!$post instanceof \WP_Post || !$this->is_supported_duplicate_source($post)) {
                    continue;
                }
                $posts[(int) $post->ID] = $post;
            }

            return array_values($posts);
        }

        $type_for_export = $scope === 'current' ? $selected_type : 'all';
        foreach ($this->get_templates_for_admin($type_for_export) as $template_row) {
            $post = $template_row['post'] ?? null;
            if (!$post instanceof \WP_Post || !$this->is_supported_duplicate_source($post)) {
                continue;
            }
            $posts[(int) $post->ID] = $post;
        }

        return array_values($posts);
    }

    /**
     * @param \WP_Post[] $posts
     */
    private function get_templates_bulk_export_payload(array $posts, string $scope): array {
        $templates = [];
        foreach ($posts as $post) {
            if (!$post instanceof \WP_Post) {
                continue;
            }

            $payload = $this->get_template_export_payload($post);
            if (is_array($payload['template'] ?? null)) {
                $templates[] = $payload['template'];
            }
        }

        return [
            'schema'      => 'nv_pw_template_bulk_export_v1',
            'exported_at' => gmdate('c'),
            'source_site' => home_url('/'),
            'scope'       => $scope,
            'count'       => count($templates),
            'templates'   => $templates,
        ];
    }

    private function send_template_json_download(array $payload, string $filename): void {
        $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || $json === '') {
            wp_die('Unable to export template.');
        }

        nocache_headers();
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $json;
        exit;
    }

    private function get_exportable_meta(int $post_id): array {
        $all_meta = get_post_meta($post_id);
        $skip_keys = [
            '_edit_lock',
            '_edit_last',
            '_wp_old_slug',
            self::TEMPLATE_KIND_META,
            self::META_KEY,
            self::IMPORT_FINGERPRINT_META,
        ];

        $export_meta = [];
        foreach ($all_meta as $meta_key => $meta_values) {
            if (!is_string($meta_key) || $meta_key === '') {
                continue;
            }
            if (in_array($meta_key, $skip_keys, true) || strpos($meta_key, '_wp_trash_meta_') === 0) {
                continue;
            }

            if (!is_array($meta_values) || empty($meta_values)) {
                continue;
            }

            $normalized = [];
            foreach ($meta_values as $meta_value) {
                $normalized[] = maybe_unserialize($meta_value);
            }

            if (!empty($normalized)) {
                $export_meta[$meta_key] = $normalized;
            }
        }

        return $export_meta;
    }

    private function get_exportable_terms(\WP_Post $post): array {
        $result = [];
        $taxonomies = get_object_taxonomies($post->post_type, 'names');
        if (!is_array($taxonomies)) {
            return $result;
        }

        foreach ($taxonomies as $taxonomy) {
            $terms = get_the_terms($post->ID, $taxonomy);
            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            $slugs = [];
            foreach ($terms as $term) {
                if ($term instanceof \WP_Term) {
                    $slugs[] = $term->slug;
                }
            }

            if (!empty($slugs)) {
                $result[$taxonomy] = array_values(array_unique($slugs));
            }
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $meta
     */
    private function apply_imported_meta(int $post_id, array $meta): void {
        $skip_keys = [
            '_edit_lock',
            '_edit_last',
            '_wp_old_slug',
            self::TEMPLATE_KIND_META,
            self::META_KEY,
            self::IMPORT_FINGERPRINT_META,
        ];

        foreach ($meta as $meta_key => $meta_values) {
            if (!is_string($meta_key) || $meta_key === '') {
                continue;
            }
            if (in_array($meta_key, $skip_keys, true) || strpos($meta_key, '_wp_trash_meta_') === 0) {
                continue;
            }

            delete_post_meta($post_id, $meta_key);

            if (!is_array($meta_values)) {
                $meta_values = [$meta_values];
            }

            foreach ($meta_values as $meta_value) {
                $value_for_save = maybe_unserialize($meta_value);
                $value_for_save = $this->maybe_prepare_meta_value_for_insert($meta_key, $value_for_save);
                add_post_meta($post_id, $meta_key, $value_for_save);
            }
        }
    }

    /**
     * @param array<string,mixed> $terms
     */
    private function apply_imported_terms(int $post_id, string $post_type, array $terms): void {
        $allowed_taxonomies = get_object_taxonomies($post_type, 'names');
        if (!is_array($allowed_taxonomies) || empty($allowed_taxonomies)) {
            return;
        }

        foreach ($terms as $taxonomy => $slugs) {
            if (!is_string($taxonomy) || !in_array($taxonomy, $allowed_taxonomies, true)) {
                continue;
            }
            if (!is_array($slugs)) {
                continue;
            }

            $clean_slugs = [];
            foreach ($slugs as $slug) {
                $slug = sanitize_title((string) $slug);
                if ($slug !== '') {
                    $clean_slugs[] = $slug;
                }
            }

            if (!empty($clean_slugs)) {
                wp_set_object_terms($post_id, array_values(array_unique($clean_slugs)), $taxonomy, false);
            }
        }
    }

    /**
     * @param array<string,mixed> $template
     * @return int|\WP_Error
     */
    private function import_template_payload(array $template, string $fingerprint = '') {
        $kind = $this->sanitize_template_type($template['kind'] ?? 'single_product');
        $title = sanitize_text_field((string) ($template['title'] ?? ''));
        $content = (string) ($template['content'] ?? '');
        $excerpt = (string) ($template['excerpt'] ?? '');
        $status = sanitize_key((string) ($template['status'] ?? 'draft'));

        if (!in_array($status, ['draft', 'publish'], true)) {
            $status = 'draft';
        }

        $new_id = $this->create_template($kind, $title);
        if (is_wp_error($new_id)) {
            return $new_id;
        }

        $updated = wp_update_post([
            'ID'           => (int) $new_id,
            'post_content' => wp_slash($content),
            'post_excerpt' => $excerpt,
            'post_status'  => $status,
        ], true);

        if (is_wp_error($updated)) {
            wp_delete_post((int) $new_id, true);
            return $updated;
        }

        if (is_array($template['meta'] ?? null)) {
            $this->apply_imported_meta((int) $new_id, (array) $template['meta']);
        }
        if (is_array($template['terms'] ?? null)) {
            $post_type = get_post_type((int) $new_id) ?: self::CPT;
            $this->apply_imported_terms((int) $new_id, $post_type, (array) $template['terms']);
        }

        $types = $this->get_template_types();
        update_post_meta((int) $new_id, self::TEMPLATE_KIND_META, $kind);
        update_post_meta((int) $new_id, '_elementor_edit_mode', 'builder');
        update_post_meta((int) $new_id, '_elementor_template_type', $types[$kind]['elementor_type'] ?? 'page');
        if ($fingerprint !== '') {
            update_post_meta((int) $new_id, self::IMPORT_FINGERPRINT_META, $fingerprint);
        }

        return (int) $new_id;
    }

    private function get_import_template_fingerprint(array $template): string {
        $meta = is_array($template['meta'] ?? null) ? (array) $template['meta'] : [];
        foreach ([
            '_edit_lock',
            '_edit_last',
            '_wp_old_slug',
            self::TEMPLATE_KIND_META,
            self::META_KEY,
            self::IMPORT_FINGERPRINT_META,
        ] as $skip_key) {
            unset($meta[$skip_key]);
        }

        $terms = is_array($template['terms'] ?? null) ? (array) $template['terms'] : [];

        $normalized = [
            'title'   => sanitize_text_field((string) ($template['title'] ?? '')),
            'content' => (string) ($template['content'] ?? ''),
            'excerpt' => (string) ($template['excerpt'] ?? ''),
            'kind'    => $this->sanitize_template_type($template['kind'] ?? 'single_product'),
            'meta'    => $this->sort_import_fingerprint_value($meta),
            'terms'   => $this->sort_import_fingerprint_value($terms),
        ];

        $json = wp_json_encode($normalized);
        return is_string($json) && $json !== '' ? hash('sha256', $json) : '';
    }

    private function find_existing_template_for_import(array $template, string $fingerprint): int {
        if ($fingerprint !== '') {
            $fingerprint_matches = get_posts([
                'post_type'      => self::CPT,
                'post_status'    => ['publish', 'draft', 'private', 'pending', 'future'],
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_key'       => self::IMPORT_FINGERPRINT_META,
                'meta_value'     => $fingerprint,
            ]);
            if (!empty($fingerprint_matches)) {
                return (int) $fingerprint_matches[0];
            }
        }

        $title = sanitize_text_field((string) ($template['title'] ?? ''));
        if ($title === '' || $fingerprint === '') {
            return 0;
        }

        $kind = $this->sanitize_template_type($template['kind'] ?? 'single_product');
        $candidates = get_posts([
            'post_type'      => self::CPT,
            'post_status'    => ['publish', 'draft', 'private', 'pending', 'future'],
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'meta_query'     => [
                [
                    'key'   => self::TEMPLATE_KIND_META,
                    'value' => $kind,
                ],
            ],
        ]);

        foreach ($candidates as $candidate) {
            if (!$candidate instanceof \WP_Post || (string) $candidate->post_title !== $title) {
                continue;
            }

            $candidate_payload = $this->get_template_export_payload($candidate);
            $candidate_template = is_array($candidate_payload['template'] ?? null) ? (array) $candidate_payload['template'] : [];
            if ($this->get_import_template_fingerprint($candidate_template) === $fingerprint) {
                update_post_meta((int) $candidate->ID, self::IMPORT_FINGERPRINT_META, $fingerprint);
                return (int) $candidate->ID;
            }
        }

        return 0;
    }

    private function sort_import_fingerprint_value($value) {
        if (!is_array($value)) {
            return $value;
        }

        $keys = array_keys($value);
        $is_list = $keys === array_keys($keys);
        foreach ($value as $key => $item) {
            $value[$key] = $this->sort_import_fingerprint_value($item);
        }

        if ($is_list) {
            return $value;
        }

        ksort($value);
        return $value;
    }

    /**
     * @return array<int,array{name:string,tmp_name:string,error:int}>
     */
    private function get_template_import_uploads(): array {
        $uploads = [];

        foreach (['template_files', 'template_file'] as $field_name) {
            $field = $_FILES[$field_name] ?? null;
            if (!is_array($field) || !array_key_exists('tmp_name', $field)) {
                continue;
            }

            if (is_array($field['tmp_name'])) {
                foreach (array_keys($field['tmp_name']) as $index) {
                    $uploads[] = [
                        'name'     => (string) ($field['name'][$index] ?? ''),
                        'tmp_name' => (string) ($field['tmp_name'][$index] ?? ''),
                        'error'    => (int) ($field['error'][$index] ?? UPLOAD_ERR_NO_FILE),
                    ];
                }
                continue;
            }

            $uploads[] = [
                'name'     => (string) ($field['name'] ?? ''),
                'tmp_name' => (string) ($field['tmp_name'] ?? ''),
                'error'    => (int) ($field['error'] ?? UPLOAD_ERR_NO_FILE),
            ];
        }

        return $uploads;
    }

    /**
     * @param array<string,mixed> $decoded
     * @return array<int,array<string,mixed>>
     */
    private function get_template_payloads_from_import_json(array $decoded): array {
        $template_payloads = [];

        if (
            sanitize_key((string) ($decoded['schema'] ?? '')) === 'nv_pw_template_bulk_export_v1' &&
            is_array($decoded['templates'] ?? null)
        ) {
            foreach ((array) $decoded['templates'] as $template_payload) {
                if (is_array($template_payload)) {
                    $template_payloads[] = $template_payload;
                }
            }
        } elseif (is_array($decoded['template'] ?? null)) {
            $template_payloads[] = (array) $decoded['template'];
        }

        return $template_payloads;
    }

    /* ─────────────────────────────────────────────────────────
       7. ACTION HANDLERS
    ───────────────────────────────────────────────────────── */

    public function handle_create_template(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Permission denied.');
        }

        check_admin_referer('nv_pw_create_template');

        $kind          = $this->sanitize_template_type($_POST['template_type'] ?? 'single_product');
        $name          = sanitize_text_field(wp_unslash($_POST['template_name'] ?? ''));
        $duplicate_from = isset($_POST['duplicate_from']) ? (int) $_POST['duplicate_from'] : 0;
        $mode          = sanitize_key((string) ($_POST['nv_pw_create_mode'] ?? 'create'));

        if ($duplicate_from > 0) {
            $post_id = $this->create_template_from_source($duplicate_from, $kind, $name);
            $redirect_key = 'duplicated';
        } else {
            $post_id = $this->create_template($kind, $name);
            $redirect_key = 'created';
        }

        if (is_wp_error($post_id)) {
            wp_redirect(add_query_arg([
                'page'        => 'nv-product-templates',
                'nv_type'     => $kind,
                'show_create' => 1,
                'error'       => 1,
            ], admin_url('admin.php')));
            exit;
        }

        if ($mode === 'create_edit') {
            wp_redirect(add_query_arg([
                'post'   => (int) $post_id,
                'action' => 'elementor',
            ], admin_url('post.php')));
            exit;
        }

        wp_redirect(add_query_arg([
            'page'        => 'nv-product-templates',
            'nv_type'     => $kind,
            $redirect_key => (int) $post_id,
        ], admin_url('admin.php')));
        exit;
    }

    public function handle_quick_edit_template(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Permission denied.');
        }

        check_admin_referer('nv_pw_quick_edit_template');

        $template_id   = isset($_POST['template_id']) ? (int) $_POST['template_id'] : 0;
        $selected_type = $this->sanitize_template_type($_POST['nv_type'] ?? 'all', true);
        $selected_status = $this->sanitize_template_status_filter($_POST['nv_status'] ?? 'all');
        $assign_product_id = isset($_POST['assign_product_id']) ? (int) $_POST['assign_product_id'] : 0;

        $post = get_post($template_id);
        if (!$post instanceof \WP_Post || !$this->is_supported_duplicate_source($post)) {
            wp_redirect(add_query_arg([
                'page'    => 'nv-product-templates',
                'nv_type' => $selected_type,
                'nv_status' => $selected_status,
                'error'   => 1,
            ], admin_url('admin.php')));
            exit;
        }

        $title = sanitize_text_field(wp_unslash((string) ($_POST['template_title'] ?? '')));
        if ($title === '') {
            $title = $post->post_title ?: 'Template';
        }

        $status = sanitize_key((string) ($_POST['template_status'] ?? 'draft'));
        if (!in_array($status, ['draft', 'publish'], true)) {
            $status = 'draft';
        }

        $updated = wp_update_post([
            'ID'          => $post->ID,
            'post_title'  => $title,
            'post_status' => $status,
        ], true);

        if (is_wp_error($updated)) {
            wp_redirect(add_query_arg([
                'page'    => 'nv-product-templates',
                'nv_type' => $selected_type,
                'nv_status' => $selected_status,
                'error'   => 1,
            ], admin_url('admin.php')));
            exit;
        }

        $assigned_product = 0;
        if ($assign_product_id > 0 && $post->post_type === self::CPT) {
            $product = get_post($assign_product_id);
            if ($product instanceof \WP_Post && $product->post_type === 'product' && current_user_can('edit_post', $assign_product_id)) {
                update_post_meta($assign_product_id, self::META_KEY, (int) $post->ID);
                $assigned_product = (int) $assign_product_id;
            }
        }

        $redirect_args = [
            'page'         => 'nv-product-templates',
            'nv_type'      => $selected_type,
            'nv_status'    => $selected_status,
            'quick_updated'=> (int) $post->ID,
        ];
        if ($assigned_product > 0) {
            $redirect_args['assigned_product'] = $assigned_product;
        }

        wp_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }

    public function handle_duplicate_template(): void {
        $template_id = isset($_GET['template_id']) ? (int) $_GET['template_id'] : 0;
        $selected_status = $this->sanitize_template_status_filter($_GET['nv_status'] ?? 'all');
        if ($template_id <= 0 || !current_user_can('manage_woocommerce')) {
            wp_die('Permission denied.');
        }

        check_admin_referer('nv_pw_duplicate_template_' . $template_id);

        $source = get_post($template_id);
        if (!$source instanceof \WP_Post || !$this->is_supported_duplicate_source($source)) {
            wp_redirect(add_query_arg([
                'page'  => 'nv-product-templates',
                'nv_status' => $selected_status,
                'error' => 1,
            ], admin_url('admin.php')));
            exit;
        }

        $kind = '';
        if (isset($_GET['target_kind'])) {
            $target_kind = sanitize_key((string) $_GET['target_kind']);
            $types = $this->get_template_types();
            if ($target_kind !== '' && isset($types[$target_kind]) && $target_kind !== 'all') {
                $kind = $target_kind;
            }
        }
        if ($kind === '') {
            $kind = $this->detect_template_kind($source);
        }
        if ($kind === '') {
            $kind = 'single_product';
        }

        $new_id = $this->create_template_from_source($template_id, $kind, '');
        if (is_wp_error($new_id)) {
            wp_redirect(add_query_arg([
                'page'    => 'nv-product-templates',
                'nv_type' => $kind,
                'nv_status' => $selected_status,
                'error'   => 1,
            ], admin_url('admin.php')));
            exit;
        }

        wp_redirect(add_query_arg([
            'page'         => 'nv-product-templates',
            'nv_type'      => $kind,
            'nv_status'    => $selected_status,
            'duplicated'   => (int) $new_id,
            'nv_pw_notice' => isset($_GET['source']) && sanitize_key((string) $_GET['source']) === 'shoplentor' ? 'shoplentor_duplicated' : '',
        ], admin_url('admin.php')));
        exit;
    }

    public function handle_set_default_template(): void {
        $template_id = isset($_GET['template_id']) ? (int) $_GET['template_id'] : 0;
        $kind = $this->sanitize_template_type($_GET['target_kind'] ?? 'single_product');
        $selected_type = $this->sanitize_template_type($_GET['nv_type'] ?? $kind, true);

        if ($template_id <= 0 || !current_user_can('manage_woocommerce')) {
            wp_die('Permission denied.');
        }

        check_admin_referer('nv_pw_set_default_template_' . $template_id . '_' . $kind);

        if (!$this->set_default_template_id($kind, $template_id)) {
            wp_redirect(add_query_arg([
                'page'    => 'nv-product-templates',
                'nv_type' => $selected_type,
                'error'   => 1,
            ], admin_url('admin.php')));
            exit;
        }

        wp_redirect(add_query_arg([
            'page'        => 'nv-product-templates',
            'nv_type'     => $selected_type,
            'default_set' => $template_id,
        ], admin_url('admin.php')));
        exit;
    }

    public function handle_clear_default_template(): void {
        $template_id = isset($_GET['template_id']) ? (int) $_GET['template_id'] : 0;
        $kind = $this->sanitize_template_type($_GET['target_kind'] ?? 'single_product');
        $selected_type = $this->sanitize_template_type($_GET['nv_type'] ?? $kind, true);

        if (!current_user_can('manage_woocommerce')) {
            wp_die('Permission denied.');
        }

        check_admin_referer('nv_pw_clear_default_template_' . $template_id . '_' . $kind);
        $this->clear_default_template_id($kind, $template_id);

        wp_redirect(add_query_arg([
            'page'            => 'nv-product-templates',
            'nv_type'         => $selected_type,
            'default_cleared' => $kind,
        ], admin_url('admin.php')));
        exit;
    }

    public function handle_export_template(): void {
        $template_id = isset($_GET['template_id']) ? (int) $_GET['template_id'] : 0;
        if ($template_id <= 0 || !current_user_can('manage_woocommerce')) {
            wp_die('Permission denied.');
        }

        check_admin_referer('nv_pw_export_template_' . $template_id);

        $post = get_post($template_id);
        if (!$post instanceof \WP_Post || !$this->is_supported_duplicate_source($post)) {
            wp_die('Invalid template.');
        }

        $payload = $this->get_template_export_payload($post);
        $this->send_template_json_download(
            $payload,
            'nv-template-' . (int) $post->ID . '-' . gmdate('Ymd-His') . '.json'
        );
    }

    public function handle_export_templates_bulk(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Permission denied.');
        }

        check_admin_referer('nv_pw_export_templates_bulk');

        $selected_type = $this->sanitize_template_type($_REQUEST['nv_type'] ?? 'all', true);
        $scope = sanitize_key((string) ($_REQUEST['scope'] ?? 'all'));
        $bulk_action = sanitize_key((string) ($_POST['bulk_action'] ?? ''));

        if ($bulk_action === 'export_selected') {
            $scope = 'selected';
        }

        if (!in_array($scope, ['all', 'current', 'selected'], true)) {
            $scope = 'all';
        }

        $raw_template_ids = $_POST['template_ids'] ?? [];
        if (!is_array($raw_template_ids)) {
            $raw_template_ids = [$raw_template_ids];
        }

        $template_ids = [];
        foreach ($raw_template_ids as $raw_template_id) {
            $template_id = absint($raw_template_id);
            if ($template_id > 0) {
                $template_ids[] = $template_id;
            }
        }

        $posts = $this->get_template_posts_for_bulk_export($scope, $selected_type, $template_ids);
        if (empty($posts)) {
            wp_die('No templates selected for export.');
        }

        $payload = $this->get_templates_bulk_export_payload($posts, $scope);
        $filename_type = $scope === 'current' ? $selected_type : $scope;
        $filename_type = sanitize_title($filename_type !== '' ? $filename_type : 'templates');
        $this->send_template_json_download(
            $payload,
            'nv-templates-' . $filename_type . '-' . gmdate('Ymd-His') . '.json'
        );
    }

    public function handle_bulk_template_action(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Permission denied.');
        }

        check_admin_referer('nv_pw_bulk_template_action');

        $selected_type = $this->sanitize_template_type($_REQUEST['nv_type'] ?? 'all', true);
        $selected_status = $this->sanitize_template_status_filter($_REQUEST['nv_status'] ?? 'all');
        $bulk_action = sanitize_key((string) ($_REQUEST['bulk_action'] ?? ''));

        $raw_template_ids = $_REQUEST['template_ids'] ?? [];
        if (!is_array($raw_template_ids)) {
            $raw_template_ids = [$raw_template_ids];
        }

        $template_ids = [];
        foreach ($raw_template_ids as $raw_template_id) {
            $template_id = absint($raw_template_id);
            if ($template_id > 0) {
                $template_ids[] = $template_id;
            }
        }

        $redirect_args = [
            'page'      => 'nv-product-templates',
            'nv_type'   => $selected_type,
            'nv_status' => $selected_status,
        ];

        if (empty($template_ids) || $bulk_action === '') {
            wp_redirect(add_query_arg($redirect_args + ['error' => 1], admin_url('admin.php')));
            exit;
        }

        if ($bulk_action === 'export_selected') {
            $posts = $this->get_template_posts_for_bulk_action($template_ids, $selected_status);
            if (empty($posts)) {
                wp_die('No templates selected for export.');
            }

            $payload = $this->get_templates_bulk_export_payload($posts, 'selected');
            $filename_type = sanitize_title($selected_type !== '' ? $selected_type : 'templates');
            $this->send_template_json_download(
                $payload,
                'nv-templates-' . $filename_type . '-selected-' . gmdate('Ymd-His') . '.json'
            );
        }

        if ($bulk_action === 'duplicate_selected') {
            $posts = $this->get_template_posts_for_bulk_action($template_ids, $selected_status);
            $duplicated_count = 0;
            foreach ($posts as $post) {
                if ($post->post_status === 'trash') {
                    continue;
                }

                $kind = $this->detect_template_kind($post);
                if ($kind === '') {
                    $kind = 'single_product';
                }

                $new_id = $this->create_template_from_source((int) $post->ID, $kind, '');
                if (!is_wp_error($new_id)) {
                    $duplicated_count++;
                }
            }

            wp_redirect(add_query_arg($redirect_args + ['duplicated_count' => $duplicated_count], admin_url('admin.php')));
            exit;
        }

        if ($bulk_action === 'trash_selected') {
            $posts = $this->get_template_posts_for_bulk_action($template_ids, $selected_status);
            $trashed_count = 0;
            foreach ($posts as $post) {
                $template_id = (int) $post->ID;
                if ($post->post_status === 'trash') {
                    continue;
                }

                $this->cleanup_template_before_removal($template_id, $post);
                $trashed = wp_trash_post($template_id);
                if ($trashed instanceof \WP_Post) {
                    $trashed_count++;
                }
            }

            wp_redirect(add_query_arg($redirect_args + ['trashed' => $trashed_count], admin_url('admin.php')));
            exit;
        }

        if ($bulk_action === 'restore_selected') {
            $posts = $this->get_template_posts_for_bulk_action($template_ids, 'trash');
            $restored_count = 0;
            foreach ($posts as $post) {
                $template_id = (int) $post->ID;
                $restored = wp_untrash_post($template_id);
                if ($restored instanceof \WP_Post) {
                    $restored_count++;
                }
            }

            wp_redirect(add_query_arg($redirect_args + ['restored' => $restored_count], admin_url('admin.php')));
            exit;
        }

        if ($bulk_action === 'delete_permanently') {
            $posts = $this->get_template_posts_for_bulk_action($template_ids, 'trash');
            $deleted_count = 0;
            foreach ($posts as $post) {
                $template_id = (int) $post->ID;
                $this->cleanup_template_before_removal($template_id, $post);
                $deleted = wp_delete_post($template_id, true);
                if ($deleted instanceof \WP_Post) {
                    $deleted_count++;
                }
            }

            wp_redirect(add_query_arg($redirect_args + ['permanently_deleted' => $deleted_count], admin_url('admin.php')));
            exit;
        }

        wp_redirect(add_query_arg($redirect_args + ['error' => 1], admin_url('admin.php')));
        exit;
    }

    public function handle_import_template(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Permission denied.');
        }

        check_admin_referer('nv_pw_import_template');

        $selected_type = $this->sanitize_template_type($_POST['nv_type'] ?? 'all', true);
        $uploaded_files = $this->get_template_import_uploads();

        if (empty($uploaded_files)) {
            wp_redirect(add_query_arg([
                'page'    => 'nv-product-templates',
                'nv_type' => $selected_type,
                'error'   => 1,
            ], admin_url('admin.php')));
            exit;
        }

        $imported_ids = [];
        $failed_files = 0;
        $duplicate_skipped_count = 0;
        $seen_fingerprints = [];

        foreach ($uploaded_files as $uploaded_file) {
            $error = (int) ($uploaded_file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $tmp_name = (string) ($uploaded_file['tmp_name'] ?? '');
            if ($error !== UPLOAD_ERR_OK || $tmp_name === '' || !is_uploaded_file($tmp_name)) {
                $failed_files++;
                continue;
            }

            $raw = file_get_contents($tmp_name);
            $decoded = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($decoded)) {
                $failed_files++;
                continue;
            }

            $template_payloads = $this->get_template_payloads_from_import_json($decoded);
            if (empty($template_payloads)) {
                $failed_files++;
                continue;
            }

            $file_imported_count = 0;
            $file_duplicate_skipped_count = 0;
            foreach ($template_payloads as $template_payload) {
                $fingerprint = $this->get_import_template_fingerprint($template_payload);
                if ($fingerprint !== '' && isset($seen_fingerprints[$fingerprint])) {
                    $duplicate_skipped_count++;
                    $file_duplicate_skipped_count++;
                    continue;
                }
                if ($fingerprint !== '' && $this->find_existing_template_for_import($template_payload, $fingerprint) > 0) {
                    $seen_fingerprints[$fingerprint] = true;
                    $duplicate_skipped_count++;
                    $file_duplicate_skipped_count++;
                    continue;
                }

                $new_id = $this->import_template_payload($template_payload, $fingerprint);
                if (is_wp_error($new_id)) {
                    continue;
                }

                $imported_ids[] = (int) $new_id;
                $file_imported_count++;
                if ($fingerprint !== '') {
                    $seen_fingerprints[$fingerprint] = true;
                }
            }

            if ($file_imported_count === 0 && $file_duplicate_skipped_count === 0) {
                $failed_files++;
            }
        }

        if (empty($imported_ids)) {
            if ($duplicate_skipped_count > 0) {
                wp_redirect(add_query_arg([
                    'page'                    => 'nv-product-templates',
                    'nv_type'                 => $selected_type,
                    'duplicate_skipped_count' => $duplicate_skipped_count,
                    'import_failed_count'     => $failed_files,
                ], admin_url('admin.php')));
                exit;
            }

            wp_redirect(add_query_arg([
                'page'    => 'nv-product-templates',
                'nv_type' => $selected_type,
                'error'   => 1,
            ], admin_url('admin.php')));
            exit;
        }

        $redirect_args = [
            'page'           => 'nv-product-templates',
            'nv_type'        => $selected_type,
            'imported'       => (int) $imported_ids[0],
            'imported_count' => count($imported_ids),
        ];
        if ($failed_files > 0) {
            $redirect_args['import_failed_count'] = $failed_files;
        }
        if ($duplicate_skipped_count > 0) {
            $redirect_args['duplicate_skipped_count'] = $duplicate_skipped_count;
        }
        wp_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }

    public function handle_delete_template(): void {
        $template_id = isset($_GET['template_id']) ? (int) $_GET['template_id'] : 0;
        if ($template_id <= 0 || !current_user_can('manage_woocommerce')) {
            wp_die('Permission denied.');
        }

        check_admin_referer('nv_pw_delete_template_' . $template_id);

        $selected_type = $this->sanitize_template_type($_GET['nv_type'] ?? 'all', true);
        $selected_status = $this->sanitize_template_status_filter($_GET['nv_status'] ?? 'all');
        $post = get_post($template_id);

        if ($post instanceof \WP_Post) {
            $this->cleanup_template_before_removal($template_id, $post);
        }

        $trashed = wp_trash_post($template_id);
        $redirect_args = [
            'page'      => 'nv-product-templates',
            'nv_type'   => $selected_type,
            'nv_status' => $selected_status,
        ];
        if ($trashed instanceof \WP_Post) {
            $redirect_args['trashed'] = 1;
        } else {
            $redirect_args['error'] = 1;
        }

        wp_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }

    public function ajax_search_products(): void {
        check_ajax_referer('nv_pw_search_products');
        $term    = sanitize_text_field($_GET['q'] ?? '');
        $results = wc_get_products([
            'status' => 'publish',
            'limit'  => 20,
            's'      => $term,
        ]);

        $data = [];
        foreach ($results as $p) {
            $data[] = ['id' => $p->get_id(), 'text' => $p->get_name()];
        }

        wp_send_json_success($data);
    }

    /* ─────────────────────────────────────────────────────────
       8. ROW ACTIONS
    ───────────────────────────────────────────────────────── */

    public function add_edit_elementor_link(array $actions, \WP_Post $post): array {
        if ($post->post_type !== self::CPT && !$this->is_supported_duplicate_source($post)) {
            return $actions;
        }

        $edit_url = add_query_arg(['post' => $post->ID, 'action' => 'elementor'], admin_url('post.php'));
        $actions['edit_elementor'] = '<a href="' . esc_url($edit_url) . '">Edit in Elementor</a>';

        if (!$this->has_row_action_by_token($actions, 'duplicate')) {
            $duplicate_url = wp_nonce_url(
                add_query_arg([
                    'action'      => 'nv_pw_duplicate_template',
                    'template_id' => $post->ID,
                ], admin_url('admin-post.php')),
                'nv_pw_duplicate_template_' . $post->ID
            );
            $actions['nv_duplicate'] = '<a href="' . esc_url($duplicate_url) . '">Duplicate</a>';
        }

        return $actions;
    }

    /**
     * @param array<string,string> $actions
     */
    private function has_row_action_by_token(array $actions, string $token): bool {
        $token = strtolower($token);
        foreach ($actions as $key => $markup) {
            $haystack = strtolower((string) $key . ' ' . wp_strip_all_tags((string) $markup));
            if (strpos($haystack, $token) !== false) {
                return true;
            }
        }

        return false;
    }
}
