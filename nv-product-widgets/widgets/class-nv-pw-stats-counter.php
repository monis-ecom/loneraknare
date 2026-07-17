<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Stats_Counter extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-stats-counter'; }
    public function get_title(): string { return 'NV: Stats Counter'; }
    public function get_icon(): string { return 'eicon-counter'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['counter', 'stats', 'numbers', 'count up', 'metrics', 'siffror', 'statistik', 'social proof']; }
    public function get_script_depends(): array { return ['nv-stats-counter']; }

    private function sep_char(string $key): string {
        switch ($key) {
            case 'comma': return ',';
            case 'dot': return '.';
            case 'space': return ' ';
            case 'none': default: return '';
        }
    }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);

        $this->add_control('columns', [
            'label' => __('Columns', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '2',
            'options' => ['2' => '2', '3' => '3', '4' => '4'],
        ]);
        $this->add_control('duration', [
            'label' => __('Count-up duration (seconds)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 2,
            'min' => 0.5, 'max' => 6, 'step' => 0.1,
        ]);
        $this->add_control('thousand_sep', [
            'label' => __('Thousands separator', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'space',
            'options' => ['space' => __('Space (9 000)', 'nv-product-widgets'), 'comma' => __('Comma (9,000)', 'nv-product-widgets'), 'dot' => __('Dot (9.000)', 'nv-product-widgets'), 'none' => __('None (9000)', 'nv-product-widgets')],
        ]);
        $this->add_control('decimal_sep', [
            'label' => __('Decimal separator', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'dot',
            'options' => ['dot' => __('Dot (98.3)', 'nv-product-widgets'), 'comma' => __('Comma (98,3)', 'nv-product-widgets')],
        ]);

        $r = new \Elementor\Repeater();
        $r->add_control('value', [
            'label' => __('Number', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 9000,
        ]);
        $r->add_control('decimals', [
            'label' => __('Decimals', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 0, 'min' => 0, 'max' => 3,
        ]);
        $r->add_control('prefix', [
            'label' => __('Prefix', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);
        $r->add_control('suffix', [
            'label' => __('Suffix', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);
        $r->add_control('label', [
            'label' => __('Label', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('NÖJDA SPELARE', 'nv-product-widgets'),
        ]);
        $this->add_control('items', [
            'label' => __('Stats', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ value }}} {{{ label }}}',
            'default' => [
                ['value' => 9000, 'decimals' => 0, 'prefix' => '', 'suffix' => '', 'label' => __('NÖJDA SPELARE', 'nv-product-widgets')],
                ['value' => 98.3, 'decimals' => 1, 'prefix' => '', 'suffix' => '%', 'label' => __('NÖJDHET', 'nv-product-widgets')],
            ],
        ]);

        $this->add_control('subtext', [
            'label' => __('Subtext (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('+9 000 spelare. Nästan inga ångrar sig.', 'nv-product-widgets'),
        ]);
        $this->add_control('button_text', [
            'label' => __('Button text (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Beställ nu', 'nv-product-widgets'),
        ]);
        $this->add_control('button_url', [
            'label' => __('Button URL', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::URL,
            'placeholder' => 'https://',
            'default' => ['url' => ''],
        ]);
        $this->add_control('guarantee_text', [
            'label' => __('Guarantee line (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('✓ Nöjd eller pengarna tillbaka i 60 dagar', 'nv-product-widgets'),
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('boxed', [
            'label' => __('Boxed card', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);
        $this->add_control('card_bg', [
            'label' => __('Card background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#F1F2F6',
            'selectors' => ['{{WRAPPER}} .nv-pw-stc' => '--nv-stc-card-bg: {{VALUE}};'],
            'condition' => ['boxed' => 'yes'],
        ]);
        $this->add_control('number_color', [
            'label' => __('Number color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#14161D',
            'selectors' => ['{{WRAPPER}} .nv-pw-stc__num' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'number_typography',
            'selector' => '{{WRAPPER}} .nv-pw-stc__num',
        ]);
        $this->add_control('label_color', [
            'label' => __('Label color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#8A93A3',
            'selectors' => ['{{WRAPPER}} .nv-pw-stc__label' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'label_typography',
            'selector' => '{{WRAPPER}} .nv-pw-stc__label',
        ]);
        $this->add_control('subtext_color', [
            'label' => __('Subtext color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#8A93A3',
            'selectors' => ['{{WRAPPER}} .nv-pw-stc__subtext' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'subtext_typography',
            'selector' => '{{WRAPPER}} .nv-pw-stc__subtext',
        ]);
        $this->add_control('button_heading', ['label' => __('Button', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('button_bg', [
            'label' => __('Button background', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#6C63E0',
            'selectors' => ['{{WRAPPER}} .nv-pw-stc__btn' => 'background: {{VALUE}};'],
        ]);
        $this->add_control('button_color', [
            'label' => __('Button text color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#FFFFFF',
            'selectors' => ['{{WRAPPER}} .nv-pw-stc__btn' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'button_typography',
            'selector' => '{{WRAPPER}} .nv-pw-stc__btn',
        ]);
        $this->add_responsive_control('button_padding', [
            'label' => __('Button padding', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'selectors' => ['{{WRAPPER}} .nv-pw-stc__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);
        $this->add_control('button_radius', [
            'label' => __('Button radius', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 0, 'max' => 48]],
            'selectors' => ['{{WRAPPER}} .nv-pw-stc__btn' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_control('guarantee_heading', ['label' => __('Guarantee line', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('guarantee_color', [
            'label' => __('Guarantee color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#8A93A3',
            'selectors' => ['{{WRAPPER}} .nv-pw-stc__guarantee' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'guarantee_typography',
            'selector' => '{{WRAPPER}} .nv-pw-stc__guarantee',
        ]);
        $this->end_controls_section();
    }

    private function format_value(float $value, int $decimals, string $tsep, string $dsep): string {
        return number_format($value, $decimals, $dsep, $tsep);
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $rows = is_array($s['items'] ?? null) ? $s['items'] : [];
        $stats = [];
        foreach ($rows as $r) {
            if (!is_array($r)) continue;
            $label = trim((string) ($r['label'] ?? ''));
            if (!isset($r['value']) && $label === '') continue;
            $stats[] = $r;
        }
        if (empty($stats)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Stats Counter', '🔢');
            return;
        }

        $cols = in_array(($s['columns'] ?? '2'), ['2', '3', '4'], true) ? (string) $s['columns'] : '2';
        $duration_ms = (int) round(max(0.5, (float) ($s['duration'] ?? 2)) * 1000);
        $tsep = $this->sep_char((string) ($s['thousand_sep'] ?? 'space'));
        $dsep = $this->sep_char((string) ($s['decimal_sep'] ?? 'dot'));
        if ($dsep === '') $dsep = '.';
        $boxed = (($s['boxed'] ?? 'yes') === 'yes');
        $subtext = trim((string) ($s['subtext'] ?? ''));
        $button_text = trim((string) ($s['button_text'] ?? ''));
        $button_url = trim((string) ($s['button_url']['url'] ?? ''));
        $has_button = ($button_text !== '' && $button_url !== '');
        $guarantee = trim((string) ($s['guarantee_text'] ?? ''));

        if ($has_button) {
            $this->add_render_attribute('stc_btn', 'href', esc_url($button_url));
            if (!empty($s['button_url']['is_external'])) $this->add_render_attribute('stc_btn', 'target', '_blank');
            if (!empty($s['button_url']['nofollow'])) $this->add_render_attribute('stc_btn', 'rel', 'nofollow');
        }
        ?>
        <div class="nv-pw-stc<?php echo $boxed ? ' nv-pw-stc--boxed' : ''; ?>" style="--nv-stc-cols: <?php echo esc_attr($cols); ?>;" data-nv-stats>
            <div class="nv-pw-stc__grid">
                <?php foreach ($stats as $st) :
                    $value = (float) ($st['value'] ?? 0);
                    $decimals = max(0, min(3, (int) ($st['decimals'] ?? 0)));
                    $prefix = (string) ($st['prefix'] ?? '');
                    $suffix = (string) ($st['suffix'] ?? '');
                    $label = trim((string) ($st['label'] ?? ''));
                    $formatted = $this->format_value($value, $decimals, $tsep, $dsep);
                    ?>
                    <div class="nv-pw-stc__item">
                        <span class="nv-pw-stc__num" data-nv-count
                              data-to="<?php echo esc_attr((string) $value); ?>"
                              data-decimals="<?php echo esc_attr((string) $decimals); ?>"
                              data-tsep="<?php echo esc_attr($tsep); ?>"
                              data-dsep="<?php echo esc_attr($dsep); ?>"
                              data-prefix="<?php echo esc_attr($prefix); ?>"
                              data-suffix="<?php echo esc_attr($suffix); ?>"
                              data-duration="<?php echo esc_attr((string) $duration_ms); ?>"><?php echo esc_html($prefix . $formatted . $suffix); ?></span>
                        <?php if ($label !== '') : ?><span class="nv-pw-stc__label"><?php echo esc_html($label); ?></span><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($subtext !== '') : ?><p class="nv-pw-stc__subtext"><?php echo esc_html($subtext); ?></p><?php endif; ?>
            <?php if ($has_button) : ?>
                <a class="nv-pw-stc__btn" <?php echo $this->get_render_attribute_string('stc_btn'); ?>><?php echo esc_html($button_text); ?></a>
            <?php endif; ?>
            <?php if ($guarantee !== '') : ?><p class="nv-pw-stc__guarantee"><?php echo esc_html($guarantee); ?></p><?php endif; ?>
        </div>
        <?php
    }
}
