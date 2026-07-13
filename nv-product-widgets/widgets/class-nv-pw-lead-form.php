<?php
if (!defined('ABSPATH')) exit;

/**
 * NV: Lead Form — a configurable multi-field contact / quote / consult form.
 * Fields (text, email, phone, textarea, select) with per-field required + width.
 * Submissions are stored under "NV Leads" and emailed to the store owner via the
 * shared NV_PW_Leads handler.
 */
class NV_PW_Lead_Form extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-lead-form'; }
    public function get_title(): string { return 'NV: Lead Form'; }
    public function get_icon(): string { return 'eicon-form-horizontal'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['lead', 'form', 'contact', 'quote', 'consult', 'enquiry', 'kontakt', 'formulär']; }
    public function get_script_depends(): array { return ['nv-lead-form']; }

    protected function register_controls(): void {
        /* ── Content ── */
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('heading', ['label' => __('Heading', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Request a quote', 'nv-product-widgets'), 'label_block' => true]);
        $this->add_control('subtext', ['label' => __('Subtext', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('Tell us what you need and we’ll get back to you within one business day.', 'nv-product-widgets')]);
        $this->add_control('button_text', ['label' => __('Button text', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Send request', 'nv-product-widgets')]);
        $this->add_control('success', ['label' => __('Success message', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('Thanks — we’ve got your request and will be in touch shortly.', 'nv-product-widgets')]);
        $this->add_control('redirect', ['label' => __('Redirect after submit (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::URL, 'default' => ['url' => '']]);
        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        /* ── Fields ── */
        $this->start_controls_section('section_fields', ['label' => __('Fields', 'nv-product-widgets')]);
        $r = new \Elementor\Repeater();
        $r->add_control('label', ['label' => __('Label', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Field', 'nv-product-widgets')]);
        $r->add_control('type', ['label' => __('Type', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'text', 'options' => [
            'text' => __('Text', 'nv-product-widgets'),
            'email' => __('Email', 'nv-product-widgets'),
            'tel' => __('Phone', 'nv-product-widgets'),
            'textarea' => __('Message (textarea)', 'nv-product-widgets'),
            'select' => __('Dropdown', 'nv-product-widgets'),
        ]]);
        $r->add_control('placeholder', ['label' => __('Placeholder', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $r->add_control('options', ['label' => __('Dropdown options (one per line)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => '', 'condition' => ['type' => 'select']]);
        $r->add_control('required', ['label' => __('Required', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $r->add_control('width', ['label' => __('Width', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'full', 'options' => ['full' => __('Full', 'nv-product-widgets'), 'half' => __('Half', 'nv-product-widgets')]]);
        $this->add_control('fields', [
            'label' => __('Fields', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ label }}}',
            'default' => [
                ['label' => __('Name', 'nv-product-widgets'), 'type' => 'text', 'required' => 'yes', 'width' => 'half'],
                ['label' => __('Email', 'nv-product-widgets'), 'type' => 'email', 'required' => 'yes', 'width' => 'half'],
                ['label' => __('Phone', 'nv-product-widgets'), 'type' => 'tel', 'required' => '', 'width' => 'full'],
                ['label' => __('How can we help?', 'nv-product-widgets'), 'type' => 'textarea', 'required' => 'yes', 'width' => 'full'],
            ],
        ]);
        $this->end_controls_section();

        /* ── Style ── */
        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('align', [
            'label' => __('Heading alignment', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::CHOOSE,
            'options' => [
                'left' => ['title' => __('Left', 'nv-product-widgets'), 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Center', 'nv-product-widgets'), 'icon' => 'eicon-text-align-center'],
            ],
            'default' => 'left',
            'selectors' => ['{{WRAPPER}} .nv-pw-lf__head' => 'text-align: {{VALUE}};'],
        ]);
        $this->add_control('bg', ['label' => __('Card background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => ['{{WRAPPER}} .nv-pw-lf' => '--nv-lf-bg: {{VALUE}};']]);
        $this->add_control('heading_color', ['label' => __('Heading color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-lf' => '--nv-lf-heading: {{VALUE}};']]);
        $this->add_control('text_color', ['label' => __('Text / label color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .nv-pw-lf' => '--nv-lf-text: {{VALUE}};']]);
        $this->add_control('field_bg', ['label' => __('Field background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#F5F6FB', 'selectors' => ['{{WRAPPER}} .nv-pw-lf' => '--nv-lf-field-bg: {{VALUE}};']]);
        $this->add_control('accent', ['label' => __('Accent (focus)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#3B37C4', 'selectors' => ['{{WRAPPER}} .nv-pw-lf' => '--nv-lf-accent: {{VALUE}};']]);
        $this->add_control('btn_bg', ['label' => __('Button background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#14161D', 'selectors' => ['{{WRAPPER}} .nv-pw-lf' => '--nv-lf-btn-bg: {{VALUE}};']]);
        $this->add_control('btn_color', ['label' => __('Button text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => ['{{WRAPPER}} .nv-pw-lf' => '--nv-lf-btn-color: {{VALUE}};']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $rows = is_array($s['fields'] ?? null) ? $s['fields'] : [];
        $fields = [];
        foreach ($rows as $f) {
            if (is_array($f) && trim((string) ($f['label'] ?? '')) !== '') $fields[] = $f;
        }
        if (empty($fields)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Lead Form — add fields', '📝');
            return;
        }

        $heading = trim((string) ($s['heading'] ?? ''));
        $subtext = trim((string) ($s['subtext'] ?? ''));
        $button = trim((string) ($s['button_text'] ?? '')) ?: __('Submit', 'nv-product-widgets');
        $success = trim((string) ($s['success'] ?? '')) ?: __('Thank you!', 'nv-product-widgets');
        $redirect = isset($s['redirect']['url']) ? (string) $s['redirect']['url'] : '';
        $base = 'nv-lf-' . $this->get_id();
        ?>
        <form class="nv-pw-lead nv-pw-lf" data-nv-lead data-source="lead" data-success="<?php echo esc_attr($success); ?>"<?php if ($redirect !== '') echo ' data-redirect="' . esc_url($redirect) . '"'; ?>>
            <div class="nv-pw-lf__body" data-nv-lead-body>
                <?php if ($heading !== '' || $subtext !== '') : ?>
                    <div class="nv-pw-lf__head">
                        <?php if ($heading !== '') : ?><h3 class="nv-pw-lf__heading<?php echo NV_PW_Headline::mod($s); ?>"><?php echo esc_html($heading); ?></h3><?php endif; ?>
                        <?php if ($subtext !== '') : ?><p class="nv-pw-lf__subtext"><?php echo esc_html($subtext); ?></p><?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="nv-pw-lf__grid">
                    <?php foreach ($fields as $i => $f) :
                        $label = trim((string) ($f['label'] ?? ''));
                        $type = in_array(($f['type'] ?? 'text'), ['text', 'email', 'tel', 'textarea', 'select'], true) ? (string) $f['type'] : 'text';
                        $ph = trim((string) ($f['placeholder'] ?? ''));
                        $req = (($f['required'] ?? '') === 'yes');
                        $width = ($f['width'] ?? 'full') === 'half' ? 'half' : 'full';
                        $fid = $base . '-' . (int) $i;
                        $common = 'class="nv-pw-lf__control" id="' . esc_attr($fid) . '" data-nv-field data-type="' . esc_attr($type) . '" data-label="' . esc_attr($label) . '" name="nv_f_' . (int) $i . '"' . ($req ? ' required data-required="1"' : '') . ($ph !== '' ? ' placeholder="' . esc_attr($ph) . '"' : '');
                        ?>
                        <div class="nv-pw-lf__field nv-pw-lf__field--<?php echo esc_attr($width); ?>">
                            <label class="nv-pw-lf__label" for="<?php echo esc_attr($fid); ?>"><?php echo esc_html($label); ?><?php if ($req) : ?> <span class="nv-pw-lf__req" aria-hidden="true">*</span><?php endif; ?></label>
                            <?php if ($type === 'textarea') : ?>
                                <textarea <?php echo $common; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> rows="4"></textarea>
                            <?php elseif ($type === 'select') :
                                $opts = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($f['options'] ?? ''))));
                                ?>
                                <select <?php echo $common; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                                    <option value=""><?php echo esc_html($ph !== '' ? $ph : __('Choose…', 'nv-product-widgets')); ?></option>
                                    <?php foreach ($opts as $opt) : ?><option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option><?php endforeach; ?>
                                </select>
                            <?php else : ?>
                                <input type="<?php echo esc_attr($type); ?>" <?php echo $common; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $type === 'email' ? ' autocomplete="email"' : ($type === 'tel' ? ' autocomplete="tel"' : ''); ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <input type="text" class="nv-pw-lead__hp" name="nv_pw_hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                <div class="nv-pw-lf__actions">
                    <button type="submit" class="nv-pw-lf__btn" data-nv-lead-submit><?php echo esc_html($button); ?></button>
                    <p class="nv-pw-lead__msg" data-nv-lead-msg role="status" aria-live="polite"></p>
                </div>
            </div>
            <div class="nv-pw-lf__done nv-pw-lead__done" data-nv-lead-done hidden>
                <span class="nv-pw-lf__done-ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                <p class="nv-pw-lf__done-text"><?php echo esc_html($success); ?></p>
            </div>
        </form>
        <?php
    }
}
