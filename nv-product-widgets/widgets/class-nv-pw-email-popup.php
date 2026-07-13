<?php
if (!defined('ABSPATH')) exit;

/**
 * NV: Email Popup — a timed / exit-intent / scroll-triggered popup that reuses
 * the NV Email Capture engine (stores to "NV Leads", emails the owner). Frequency
 * capping keeps it from nagging returning visitors.
 */
class NV_PW_Email_Popup extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-email-popup'; }
    public function get_title(): string { return 'NV: Email Popup'; }
    public function get_icon(): string { return 'eicon-lightbox'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['popup', 'email', 'exit intent', 'newsletter', 'lead', 'modal', 'rabatt']; }
    public function get_script_depends(): array { return ['nv-popup', 'nv-lead-form']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('heading', ['label' => __('Heading', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Få 10% på din första order', 'nv-product-widgets'), 'label_block' => true]);
        $this->add_control('subtext', ['label' => __('Subtext', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('Gå med i vår lista för tidig tillgång och exklusiva erbjudanden.', 'nv-product-widgets')]);
        $this->add_control('image', ['label' => __('Side image (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::MEDIA]);
        $this->add_control('placeholder', ['label' => __('Email placeholder', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Din e-post', 'nv-product-widgets')]);
        $this->add_control('button_text', ['label' => __('Button text', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Hämta rabatt', 'nv-product-widgets')]);
        $this->add_control('consent', ['label' => __('Consent text (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => '']);
        $this->add_control('success', ['label' => __('Success message', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('Tack! Kolla din inkorg för rabattkoden.', 'nv-product-widgets')]);
        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        $this->start_controls_section('section_trigger', ['label' => __('Trigger', 'nv-product-widgets')]);
        $this->add_control('trigger', ['label' => __('Show on', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'time', 'options' => ['time' => __('After a delay', 'nv-product-widgets'), 'scroll' => __('Scroll depth', 'nv-product-widgets'), 'exit' => __('Exit intent (desktop)', 'nv-product-widgets')]]);
        $this->add_control('delay', ['label' => __('Delay (seconds)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 5, 'min' => 0, 'max' => 120, 'condition' => ['trigger' => 'time']]);
        $this->add_control('scroll', ['label' => __('Scroll depth (%)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 40, 'min' => 5, 'max' => 100, 'condition' => ['trigger' => 'scroll']]);
        $this->add_control('frequency', ['label' => __('Show again after (days)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 7, 'min' => 0, 'max' => 90, 'description' => __('0 = show once per browser session.', 'nv-product-widgets')]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('bg', ['label' => __('Panel background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => ['{{WRAPPER}} .nv-pw-pop__panel' => 'background: {{VALUE}};']]);
        $this->add_control('heading_color', ['label' => __('Heading color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-pop' => '--nv-pop-heading: {{VALUE}};']]);
        $this->add_control('text_color', ['label' => __('Text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-pop' => '--nv-pop-text: {{VALUE}};']]);
        $this->add_control('btn_bg', ['label' => __('Button background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#14161D', 'selectors' => ['{{WRAPPER}} .nv-pw-pop' => '--nv-pop-btn-bg: {{VALUE}};']]);
        $this->add_control('btn_color', ['label' => __('Button text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => ['{{WRAPPER}} .nv-pw-pop' => '--nv-pop-btn-color: {{VALUE}};']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $heading = trim((string) ($s['heading'] ?? ''));
        $subtext = trim((string) ($s['subtext'] ?? ''));
        $placeholder = trim((string) ($s['placeholder'] ?? '')) ?: __('Din e-post', 'nv-product-widgets');
        $button = trim((string) ($s['button_text'] ?? '')) ?: __('Skicka', 'nv-product-widgets');
        $consent = trim((string) ($s['consent'] ?? ''));
        $success = trim((string) ($s['success'] ?? '')) ?: __('Tack!', 'nv-product-widgets');
        $img = isset($s['image']['url']) ? (string) $s['image']['url'] : '';
        $trigger = in_array(($s['trigger'] ?? 'time'), ['time', 'scroll', 'exit'], true) ? (string) $s['trigger'] : 'time';
        $delay = max(0, (int) ($s['delay'] ?? 5));
        $scroll = max(5, min(100, (int) ($s['scroll'] ?? 40)));
        $freq = max(0, (int) ($s['frequency'] ?? 7));
        $uid = 'nv-pop-' . $this->get_id();
        $is_editor = NV_PW_Editor_Helper::is_editor();
        ?>
        <div class="nv-pw-pop<?php echo $is_editor ? ' is-open nv-pw-pop--preview' : ''; ?>" data-nv-pop data-trigger="<?php echo esc_attr($trigger); ?>" data-delay="<?php echo esc_attr((string) $delay); ?>" data-scroll="<?php echo esc_attr((string) $scroll); ?>" data-freq="<?php echo esc_attr((string) $freq); ?>" data-key="<?php echo esc_attr($uid); ?>" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr($heading); ?>">
            <div class="nv-pw-pop__overlay" data-nv-pop-close></div>
            <div class="nv-pw-pop__panel<?php echo $img !== '' ? ' nv-pw-pop__panel--split' : ''; ?>">
                <button type="button" class="nv-pw-pop__close" data-nv-pop-close aria-label="<?php esc_attr_e('Stäng', 'nv-product-widgets'); ?>">✕</button>
                <?php if ($img !== '') : ?><div class="nv-pw-pop__media" style="background-image:url('<?php echo esc_url($img); ?>');"></div><?php endif; ?>
                <form class="nv-pw-lead nv-pw-pop__form" data-nv-lead data-source="email" data-success="<?php echo esc_attr($success); ?>">
                    <div class="nv-pw-lead__body" data-nv-lead-body>
                        <?php if ($heading !== '') : ?><h3 class="nv-pw-pop__heading<?php echo NV_PW_Headline::mod($s); ?>"><?php echo NV_PW_Headline::html($heading); ?></h3><?php endif; ?>
                        <?php if ($subtext !== '') : ?><p class="nv-pw-pop__subtext"><?php echo esc_html($subtext); ?></p><?php endif; ?>
                        <label class="nv-pw-pop__field">
                            <span class="screen-reader-text"><?php echo esc_html($placeholder); ?></span>
                            <input type="email" class="nv-pw-pop__input" id="<?php echo esc_attr($uid); ?>-email" data-nv-field data-type="email" data-label="<?php esc_attr_e('Email', 'nv-product-widgets'); ?>" name="nv_email" placeholder="<?php echo esc_attr($placeholder); ?>" autocomplete="email" required>
                        </label>
                        <button type="submit" class="nv-pw-pop__btn" data-nv-lead-submit><?php echo esc_html($button); ?></button>
                        <?php if ($consent !== '') : ?><label class="nv-pw-pop__consent"><input type="checkbox" required> <span><?php echo esc_html($consent); ?></span></label><?php endif; ?>
                        <input type="text" class="nv-pw-lead__hp" name="nv_pw_hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                        <p class="nv-pw-lead__msg" data-nv-lead-msg role="status" aria-live="polite"></p>
                    </div>
                    <div class="nv-pw-pop__done nv-pw-lead__done" data-nv-lead-done hidden>
                        <span class="nv-pw-pop__done-ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                        <p class="nv-pw-pop__done-text"><?php echo esc_html($success); ?></p>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}
