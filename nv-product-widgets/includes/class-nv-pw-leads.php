<?php
if (!defined('ABSPATH')) exit;

/**
 * NV Leads — server-side capture for the Email Capture and Lead Form widgets.
 *
 * Stores each submission as a private `nv_pw_lead` post (visible under the
 * "NV Leads" admin menu), emails the site owner, and fires the
 * `nv_pw_lead_created` action so integrations (Mailchimp, Klaviyo, a webhook…)
 * can be wired up server-side.
 *
 * Security note: the notification address and any outbound webhook are resolved
 * server-side only — never from the submitted form — so the endpoint can't be
 * abused to email or POST to arbitrary destinations.
 */
final class NV_PW_Leads {
    const CPT = 'nv_pw_lead';

    public static function init(): void {
        add_action('init', [__CLASS__, 'register_cpt']);
        add_action('wp_ajax_nv_pw_lead_submit', [__CLASS__, 'handle_submit']);
        add_action('wp_ajax_nopriv_nv_pw_lead_submit', [__CLASS__, 'handle_submit']);
    }

    public static function register_cpt(): void {
        register_post_type(self::CPT, [
            'labels' => [
                'name'          => __('NV Leads', 'nv-product-widgets'),
                'singular_name' => __('Lead', 'nv-product-widgets'),
                'menu_name'     => __('NV Leads', 'nv-product-widgets'),
                'all_items'     => __('All Leads', 'nv-product-widgets'),
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-email-alt',
            'menu_position'       => 58,
            'capability_type'     => 'post',
            'capabilities'        => ['create_posts' => 'do_not_allow'],
            'map_meta_cap'        => true,
            'supports'            => ['title'],
            'has_archive'         => false,
            'rewrite'             => false,
            'exclude_from_search' => true,
        ]);
    }

    public static function handle_submit(): void {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash((string) $_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'nv_pw_lead')) {
            wp_send_json_error(['message' => __('Security check failed. Please refresh the page and try again.', 'nv-product-widgets')], 400);
        }

        // Honeypot: a filled hidden field means a bot. Pretend success so it stops trying.
        if (!empty($_POST['nv_pw_hp'])) {
            wp_send_json_success(['message' => 'ok']);
        }

        $source = (isset($_POST['source']) && $_POST['source'] === 'lead') ? 'lead' : 'email';
        $page   = isset($_POST['page']) ? esc_url_raw(wp_unslash((string) $_POST['page'])) : '';

        /** @var array<int,array{label:string,value:string}> $fields */
        $fields = [];
        $email  = '';

        if ($source === 'email') {
            $email = isset($_POST['email']) ? sanitize_email(wp_unslash((string) $_POST['email'])) : '';
            if ($email === '' || !is_email($email)) {
                wp_send_json_error(['message' => __('Please enter a valid email address.', 'nv-product-widgets')], 422);
            }
            $fields[] = ['label' => __('Email', 'nv-product-widgets'), 'value' => $email];
        } else {
            $raw     = isset($_POST['fields']) ? wp_unslash((string) $_POST['fields']) : '';
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                wp_send_json_error(['message' => __('Something went wrong. Please try again.', 'nv-product-widgets')], 422);
            }
            $has_value = false;
            foreach ($decoded as $f) {
                if (!is_array($f)) continue;
                $label = sanitize_text_field((string) ($f['label'] ?? ''));
                $type  = sanitize_key((string) ($f['type'] ?? 'text'));
                $value = (string) ($f['value'] ?? '');
                $value = ($type === 'textarea') ? sanitize_textarea_field($value) : sanitize_text_field($value);
                if ($type === 'email' && $value !== '') {
                    if (!is_email($value)) {
                        wp_send_json_error(['message' => __('Please enter a valid email address.', 'nv-product-widgets')], 422);
                    }
                    if ($email === '') $email = sanitize_email($value);
                }
                if ($value !== '') $has_value = true;
                $fields[] = ['label' => ($label !== '' ? $label : ucfirst($type)), 'value' => $value];
            }
            if (!$has_value) {
                wp_send_json_error(['message' => __('Please fill in the form before submitting.', 'nv-product-widgets')], 422);
            }
        }

        // Build a readable title + body.
        $primary = $email;
        if ($primary === '') {
            foreach ($fields as $f) {
                if ($f['value'] !== '') { $primary = $f['value']; break; }
            }
        }
        $prefix = ($source === 'lead') ? __('Lead', 'nv-product-widgets') : __('Signup', 'nv-product-widgets');
        $title  = $prefix . ($primary !== '' ? ': ' . $primary : '');

        $lines = [];
        foreach ($fields as $f) {
            $lines[] = $f['label'] . ': ' . $f['value'];
        }
        $body_text = implode("\n", $lines);

        $post_id = wp_insert_post([
            'post_type'    => self::CPT,
            'post_status'  => 'publish',
            'post_title'   => wp_strip_all_tags($title),
            'post_content' => $body_text,
        ], true);

        if (is_wp_error($post_id) || !$post_id) {
            wp_send_json_error(['message' => __('Could not save your submission. Please try again.', 'nv-product-widgets')], 500);
        }

        update_post_meta($post_id, '_nv_pw_lead_source', $source);
        update_post_meta($post_id, '_nv_pw_lead_email', $email);
        update_post_meta($post_id, '_nv_pw_lead_page', $page);
        update_post_meta($post_id, '_nv_pw_lead_fields', $fields);

        // Notify the store owner. The address is resolved server-side only.
        $notify = apply_filters('nv_pw_lead_notify_email', get_option('admin_email'), $source, $fields);
        if (is_string($notify) && is_email($notify)) {
            $site    = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
            $kind    = ($source === 'lead') ? __('lead', 'nv-product-widgets') : __('email signup', 'nv-product-widgets');
            $subject = sprintf(__('[%1$s] New %2$s', 'nv-product-widgets'), $site, $kind);
            $message = $body_text . "\n\n" . ($page !== '' ? sprintf(__('Page: %s', 'nv-product-widgets'), $page) . "\n" : '');
            wp_mail($notify, $subject, $message);
        }

        // Extension point: wire Mailchimp / Klaviyo / a webhook here (server-side).
        do_action('nv_pw_lead_created', $post_id, $source, $fields, $email);

        wp_send_json_success(['message' => 'ok', 'id' => (int) $post_id]);
    }
}
