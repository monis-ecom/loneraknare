<?php
if (!defined('ABSPATH')) exit;

/**
 * NV: Email Capture — an inline newsletter / opt-in block. Heading, subtext,
 * email field + button, optional consent checkbox, and a success state.
 * Submissions are stored under "NV Leads" and emailed to the store owner via
 * the shared NV_PW_Leads handler.
 */
class NV_PW_Email_Capture extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-email-capture'; }
    public function get_title(): string { return 'NV: Email Capture'; }
    public function get_icon(): string { return 'eicon-mail'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['email', 'newsletter', 'capture', 'opt-in', 'signup', 'subscribe', 'lead', 'nyhetsbrev']; }
    public function get_script_depends(): array { return ['nv-lead-form']; }

    protected function register_controls(): void {
        /* ── Content ── */
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('layout', ['label' => __('Layout', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'inline', 'options' => ['inline' => __('Inline (field + button in a row)', 'nv-product-widgets'), 'stacked' => __('Stacked', 'nv-product-widgets')]]);
        $this->add_control('heading', ['label' => __('Heading', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Get 10% off your first order', 'nv-product-widgets'), 'label_block' => true]);
        $this->add_control('subtext', ['label' => __('Subtext', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('Join our list for early access, tips and exclusive offers.', 'nv-product-widgets')]);
        $this->add_control('placeholder', ['label' => __('Email placeholder', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Enter your email', 'nv-product-widgets')]);
        $this->add_control('button_text', ['label' => __('Button text', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Sign me up', 'nv-product-widgets')]);
        $this->add_control('consent', ['label' => __('Consent text (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => '', 'description' => __('If set, a required consent checkbox is shown (e.g. "I agree to receive emails").', 'nv-product-widgets')]);
        $this->add_control('success', ['label' => __('Success message', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('You’re in! Check your inbox to confirm.', 'nv-product-widgets')]);
        $this->add_control('redirect', ['label' => __('Redirect after signup (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::URL, 'default' => ['url' => ''], 'description' => __('Leave blank to show the success message instead.', 'nv-product-widgets')]);
        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        /* ── Style ── */
        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('align', [
            'label' => __('Alignment', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::CHOOSE,
            'options' => [
                'left' => ['title' => __('Left', 'nv-product-widgets'), 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Center', 'nv-product-widgets'), 'icon' => 'eicon-text-align-center'],
            ],
            'default' => 'center',
            'selectors' => ['{{WRAPPER}} .nv-pw-ec' => '--nv-ec-align: {{VALUE}};'],
        ]);
        $this->add_control('bg', ['label' => __('Background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#F5F6FB', 'selectors' => ['{{WRAPPER}} .nv-pw-ec' => '--nv-ec-bg: {{VALUE}};']]);
        $this->add_control('heading_color', ['label' => __('Heading color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-ec' => '--nv-ec-heading: {{VALUE}};']]);
        $this->add_control('text_color', ['label' => __('Text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-ec' => '--nv-ec-text: {{VALUE}};']]);
        $this->add_control('field_bg', ['label' => __('Field background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => ['{{WRAPPER}} .nv-pw-ec' => '--nv-ec-field-bg: {{VALUE}};']]);
        $this->add_control('btn_bg', ['label' => __('Button background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#14161D', 'selectors' => ['{{WRAPPER}} .nv-pw-ec' => '--nv-ec-btn-bg: {{VALUE}};']]);
        $this->add_control('btn_color', ['label' => __('Button text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => ['{{WRAPPER}} .nv-pw-ec' => '--nv-ec-btn-color: {{VALUE}};']]);
        $this->add_control('radius', ['label' => __('Corner radius', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 40]], 'default' => ['size' => 12, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-ec' => '--nv-ec-radius: {{SIZE}}px;']]);
        $this->add_control('max_width', ['label' => __('Max width', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 320, 'max' => 900]], 'default' => ['size' => 560, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-ec' => '--nv-ec-maxw: {{SIZE}}px;']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $layout = ($s['layout'] ?? 'inline') === 'stacked' ? 'stacked' : 'inline';
        $heading = trim((string) ($s['heading'] ?? ''));
        $subtext = trim((string) ($s['subtext'] ?? ''));
        $placeholder = trim((string) ($s['placeholder'] ?? '')) ?: __('Enter your email', 'nv-product-widgets');
        $button = trim((string) ($s['button_text'] ?? '')) ?: __('Sign up', 'nv-product-widgets');
        $consent = trim((string) ($s['consent'] ?? ''));
        $success = trim((string) ($s['success'] ?? '')) ?: __('Thank you!', 'nv-product-widgets');
        $redirect = isset($s['redirect']['url']) ? (string) $s['redirect']['url'] : '';
        $uid = 'nv-ec-' . $this->get_id();
        ?>
        <form class="nv-pw-lead nv-pw-ec nv-pw-ec--<?php echo esc_attr($layout); ?>" data-nv-lead data-source="email" data-success="<?php echo esc_attr($success); ?>"<?php if ($redirect !== '') echo ' data-redirect="' . esc_url($redirect) . '"'; ?>>
            <div class="nv-pw-ec__body" data-nv-lead-body>
                <?php if ($heading !== '') : ?><h3 class="nv-pw-ec__heading<?php echo NV_PW_Headline::mod($s); ?>"><?php echo NV_PW_Headline::html($heading); ?></h3><?php endif; ?>
                <?php if ($subtext !== '') : ?><p class="nv-pw-ec__subtext"><?php echo esc_html($subtext); ?></p><?php endif; ?>
                <div class="nv-pw-ec__row">
                    <label class="nv-pw-ec__field">
                        <span class="screen-reader-text"><?php echo esc_html($placeholder); ?></span>
                        <input type="email" class="nv-pw-ec__input" id="<?php echo esc_attr($uid); ?>" data-nv-field data-type="email" data-label="<?php esc_attr_e('Email', 'nv-product-widgets'); ?>" name="nv_email" placeholder="<?php echo esc_attr($placeholder); ?>" autocomplete="email" required>
                    </label>
                    <button type="submit" class="nv-pw-ec__btn" data-nv-lead-submit><?php echo esc_html($button); ?></button>
                </div>
                <?php if ($consent !== '') : ?>
                    <label class="nv-pw-ec__consent"><input type="checkbox" required> <span><?php echo esc_html($consent); ?></span></label>
                <?php endif; ?>
                <input type="text" class="nv-pw-lead__hp" name="nv_pw_hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                <p class="nv-pw-lead__msg" data-nv-lead-msg role="status" aria-live="polite"></p>
            </div>
            <div class="nv-pw-ec__done nv-pw-lead__done" data-nv-lead-done hidden>
                <span class="nv-pw-ec__done-ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                <p class="nv-pw-ec__done-text"><?php echo esc_html($success); ?></p>
            </div>
        </form>
        <?php
    }
}
