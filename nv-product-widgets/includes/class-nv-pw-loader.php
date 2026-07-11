<?php
if (!defined('ABSPATH')) exit;

final class NV_PW_Loader {
    public static function init(): void {
        require_once NV_PW_DIR . 'includes/class-nv-pw-module-bridge.php';
        require_once NV_PW_DIR . 'includes/class-nv-pw-size-chart.php';
        require_once NV_PW_DIR . 'includes/class-nv-pw-module-settings-page.php';
        require_once NV_PW_DIR . 'includes/class-nv-pw-bundle-cart.php';
        NV_PW_Size_Chart::init();
        NV_PW_Module_Settings_Page::init();
        NV_PW_Bundle_Cart::init();

        // Register Elementor widgets
        add_action('elementor/widgets/register', [new self(), 'register_widgets']);

        // Enqueue frontend + editor styles
        add_action('elementor/frontend/after_enqueue_styles', [new self(), 'enqueue_styles']);
        add_action('elementor/editor/after_enqueue_styles',   [new self(), 'enqueue_styles']);
        add_action('elementor/preview/enqueue_styles',        [new self(), 'enqueue_styles']);

        // Register scripts
        add_action('elementor/frontend/after_register_scripts', [new self(), 'register_scripts']);

        // Admin dashboard stylesheet
        add_action('admin_enqueue_scripts', function (string $hook): void {
            if (strpos($hook, 'nv-product-templates') !== false || strpos($hook, 'nv-pw-module-settings') !== false) {
                $aspw_css_path = NV_PW_DIR . 'assets/css/as-pw-admin.css';
                $aspw_css_ver  = file_exists($aspw_css_path) ? (string) filemtime($aspw_css_path) : NV_PW_VERSION;
                wp_enqueue_style('aspw-admin', NV_PW_URL . 'assets/css/as-pw-admin.css', [], $aspw_css_ver);
            }
        });

        // Initialize the product template system
        require_once NV_PW_DIR . 'includes/class-nv-pw-template-system.php';
        NV_PW_Template_System::init();
    }

    public function register_widgets($widgets_manager): void {
        // Load the editor helper first (required by all widgets)
        require_once NV_PW_DIR . 'includes/class-nv-pw-editor-helper.php';

        $widget_files = [
            /* Original widgets */
            'class-nv-pw-product-gallery',
            'class-nv-pw-product-title',
            'class-nv-pw-product-price',
            'class-nv-pw-add-to-cart',
            'class-nv-pw-faq',

            /* ShopLentor replacements (v1.3.0) */
            'class-nv-pw-short-description',
            'class-nv-pw-product-description',
            'class-nv-pw-related-products',
            'class-nv-pw-upsells',

            /* NEW v1.4.0 */
            'class-nv-pw-bundle-selector',

            /* NEW v1.6.0 reusable offer blocks */
            'class-nv-pw-offer-ticker',
            'class-nv-pw-logo-scroller',
            'class-nv-pw-trust-badges',
            'class-nv-pw-benefits-list',
            'class-nv-pw-media-headline-text',
            'class-nv-pw-cta-block',
            'class-nv-pw-comparison-table',

            /* NEW v1.6.5 */
            'class-nv-pw-live-viewers',
            'class-nv-pw-offer-countdown',

            /* NEW v1.7.24 — Proof pack */
            'class-nv-pw-before-after',
            'class-nv-pw-testimonials',
            'class-nv-pw-guarantee',

            /* NEW v1.7.25 — Layout pack */
            'class-nv-pw-hero',
            'class-nv-pw-feature',
            'class-nv-pw-steps',
            'class-nv-pw-tabs',

            /* NEW v1.7.26 — Video pack */
            'class-nv-pw-video-slider',
            'class-nv-pw-video-text',

            /* NEW v1.7.27 — Section pack A (lighter) */
            'class-nv-pw-featured-product',
            'class-nv-pw-content-slider',
            'class-nv-pw-scrolling-images',
            'class-nv-pw-scrolling-text',
            'class-nv-pw-announcement-bar',
            'class-nv-pw-divider',

            /* NEW v1.7.28 — Section pack B (heavier) */
            'class-nv-pw-shoppable-video',
            'class-nv-pw-bundle-builder',

            /* NEW v1.7.30 — Phase C-2 */
            'class-nv-pw-comparison-grid',

            /* NEW v1.7.31 — Phase C-3 */
            'class-nv-pw-scrolling-before-after',

            /* NEW v1.7.32 — Phase D */
            'class-nv-pw-hotspots',

            /* NEW v1.7.33 — Phase E (CourtX replica parity) */
            'class-nv-pw-rating-summary',
            'class-nv-pw-sticky-atc',

            /* NEW v1.7.36 — Phase F */
            'class-nv-pw-stats-counter',

            /* NEW v1.7.37 — Phase G */
            'class-nv-pw-quantity-breaks',

            /* NEW v1.7.40 — Phase H (live-data conversion) */
            'class-nv-pw-reviews',
            'class-nv-pw-free-shipping-bar',

            /* NEW v1.7.44 — Phase I */
            'class-nv-pw-review-wall',
        ];

        foreach ($widget_files as $file) {
            require_once NV_PW_DIR . 'widgets/' . $file . '.php';
        }

        $widgets_manager->register(new NV_PW_Product_Gallery());
        $widgets_manager->register(new NV_PW_Product_Title());
        $widgets_manager->register(new NV_PW_Product_Price());
        $widgets_manager->register(new NV_PW_Add_To_Cart());
        $widgets_manager->register(new NV_PW_FAQ());
        $widgets_manager->register(new NV_PW_Short_Description());
        $widgets_manager->register(new NV_PW_Product_Description());
        $widgets_manager->register(new NV_PW_Related_Products());
        $widgets_manager->register(new NV_PW_Upsells());

        /* v1.4.0 */
        $widgets_manager->register(new NV_PW_Bundle_Selector());

        /* v1.6.0 */
        $widgets_manager->register(new NV_PW_Offer_Ticker());
        $widgets_manager->register(new NV_PW_Logo_Scroller());
        $widgets_manager->register(new NV_PW_Trust_Badges());
        $widgets_manager->register(new NV_PW_Benefits_List());
        $widgets_manager->register(new NV_PW_Media_Headline_Text());
        $widgets_manager->register(new NV_PW_CTA_Block());
        $widgets_manager->register(new NV_PW_Comparison_Table());

        /* v1.6.5 */
        $widgets_manager->register(new NV_PW_Live_Viewers());
        $widgets_manager->register(new NV_PW_Offer_Countdown());

        /* v1.7.24 — Proof pack */
        $widgets_manager->register(new NV_PW_Before_After());
        $widgets_manager->register(new NV_PW_Testimonials());
        $widgets_manager->register(new NV_PW_Guarantee());

        /* v1.7.25 — Layout pack */
        $widgets_manager->register(new NV_PW_Hero());
        $widgets_manager->register(new NV_PW_Feature());
        $widgets_manager->register(new NV_PW_Steps());
        $widgets_manager->register(new NV_PW_Tabs());

        /* v1.7.26 — Video pack */
        $widgets_manager->register(new NV_PW_Video_Slider());
        $widgets_manager->register(new NV_PW_Video_Text());

        /* v1.7.27 — Section pack A */
        $widgets_manager->register(new NV_PW_Featured_Product());
        $widgets_manager->register(new NV_PW_Content_Slider());
        $widgets_manager->register(new NV_PW_Scrolling_Images());
        $widgets_manager->register(new NV_PW_Scrolling_Text());
        $widgets_manager->register(new NV_PW_Announcement_Bar());
        $widgets_manager->register(new NV_PW_Divider());

        /* v1.7.28 — Section pack B */
        $widgets_manager->register(new NV_PW_Shoppable_Video());
        $widgets_manager->register(new NV_PW_Bundle_Builder());

        /* v1.7.30 — Phase C-2 */
        $widgets_manager->register(new NV_PW_Comparison_Grid());

        /* v1.7.31 — Phase C-3 */
        $widgets_manager->register(new NV_PW_Scrolling_Before_After());

        /* v1.7.32 — Phase D */
        $widgets_manager->register(new NV_PW_Hotspots());

        /* v1.7.33 — Phase E */
        $widgets_manager->register(new NV_PW_Rating_Summary());
        $widgets_manager->register(new NV_PW_Sticky_ATC());

        /* v1.7.36 — Phase F */
        $widgets_manager->register(new NV_PW_Stats_Counter());

        /* v1.7.37 — Phase G */
        $widgets_manager->register(new NV_PW_Quantity_Breaks());

        /* v1.7.40 — Phase H */
        $widgets_manager->register(new NV_PW_Reviews());
        $widgets_manager->register(new NV_PW_Free_Shipping_Bar());

        /* v1.7.44 — Phase I */
        $widgets_manager->register(new NV_PW_Review_Wall());
    }

    public function enqueue_styles(): void {
        wp_enqueue_style('nv-product-widgets', NV_PW_URL . 'assets/css/nv-product-widgets.css', [], NV_PW_VERSION);
    }

    public function register_scripts(): void {
        wp_register_script('nv-product-gallery',       NV_PW_URL . 'assets/js/nv-product-gallery.js',       ['jquery'],   NV_PW_VERSION, true);
        wp_register_script('nv-bundle-selector',       NV_PW_URL . 'assets/js/nv-bundle-selector.js',       [],           NV_PW_VERSION, true);
        wp_register_script('nv-before-after',          NV_PW_URL . 'assets/js/nv-before-after.js',          [],           NV_PW_VERSION, true);
        wp_register_script('nv-tabs',                  NV_PW_URL . 'assets/js/nv-tabs.js',                  [],           NV_PW_VERSION, true);
        wp_register_script('nv-video',                 NV_PW_URL . 'assets/js/nv-video.js',                 [],           NV_PW_VERSION, true);
        wp_register_script('nv-slider',                NV_PW_URL . 'assets/js/nv-slider.js',                [],           NV_PW_VERSION, true);
        wp_register_script('nv-shoppable',             NV_PW_URL . 'assets/js/nv-shoppable.js',             [],           NV_PW_VERSION, true);
        wp_register_script('nv-bundle-builder',        NV_PW_URL . 'assets/js/nv-bundle-builder.js',        [],           NV_PW_VERSION, true);
        wp_register_script('nv-hotspots',              NV_PW_URL . 'assets/js/nv-hotspots.js',              [],           NV_PW_VERSION, true);
        wp_register_script('nv-sticky-atc',            NV_PW_URL . 'assets/js/nv-sticky-atc.js',            [],           NV_PW_VERSION, true);
        wp_localize_script('nv-sticky-atc', 'nvPwBundle', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('nv_pw_bundle'),
            'adding'  => __('Lägger till…', 'nv-product-widgets'),
        ]);
        wp_register_script('nv-testimonials',          NV_PW_URL . 'assets/js/nv-testimonials.js',          [],           NV_PW_VERSION, true);
        wp_register_script('nv-stats-counter',         NV_PW_URL . 'assets/js/nv-stats-counter.js',         [],           NV_PW_VERSION, true);
        wp_register_script('nv-quantity-breaks',       NV_PW_URL . 'assets/js/nv-quantity-breaks.js',       [],           NV_PW_VERSION, true);
        wp_register_script('nv-free-shipping-bar',     NV_PW_URL . 'assets/js/nv-free-shipping-bar.js',     [],           NV_PW_VERSION, true);
        wp_localize_script('nv-quantity-breaks', 'nvPwBundle', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('nv_pw_bundle'),
            'adding'  => __('Lägger till…', 'nv-product-widgets'),
        ]);
        wp_localize_script('nv-bundle-builder', 'nvPwBundle', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('nv_pw_bundle'),
        ]);
    }
}
