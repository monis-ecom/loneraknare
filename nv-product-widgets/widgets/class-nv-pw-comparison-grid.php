<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Comparison_Grid extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-comparison-grid'; }
    public function get_title(): string { return 'NV: Comparison Grid'; }
    public function get_icon(): string { return 'eicon-table'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['comparison', 'grid', 'versus', 'competitors', 'us vs them', 'jamforelse']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_head', ['label' => __('Heading', 'nv-product-widgets')]);
        $this->add_control('eyebrow', ['label' => __('Overline', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('JÄMFÖRELSE', 'nv-product-widgets')]);
        $this->add_control('headline', ['label' => __('Headline', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Varför vi vinner', 'nv-product-widgets'), 'label_block' => true]);
        $this->add_control('intro', ['label' => __('Intro', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __('Se hur vi står oss mot andra märken.', 'nv-product-widgets')]);
        $this->add_control('split_layout', ['label' => __('Marketing column beside table', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => '', 'description' => __('Puts the heading, a benefit checklist and the button in a column to the left of the comparison matrix (section.store "US vs Other Brands" style).', 'nv-product-widgets')]);
        $b = new \Elementor\Repeater();
        $b->add_control('text', ['label' => __('Benefit', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Snabb effekt', 'nv-product-widgets')]);
        $this->add_control('bullets', [
            'label' => __('Benefit checklist (marketing column)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $b->get_controls(),
            'title_field' => '{{{ text }}}',
            'condition' => ['split_layout' => 'yes'],
            'default' => [
                ['text' => __('Fall asleep quicker', 'nv-product-widgets')],
                ['text' => __('Relax & restorative sleep', 'nv-product-widgets')],
                ['text' => __('Wake up refreshed', 'nv-product-widgets')],
                ['text' => __('Plant based', 'nv-product-widgets')],
            ],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_cols', ['label' => __('Columns', 'nv-product-widgets')]);
        $this->add_control('us_header', ['label' => __('Your column header', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Vi', 'nv-product-widgets')]);
        $this->add_control('us_badge', ['label' => __('Your column badge (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('BÄST', 'nv-product-widgets')]);
        $this->add_control('us_image', ['label' => __('Your column image (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::MEDIA]);
        $this->add_control('c1_header', ['label' => __('Competitor 1 header', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Andra märken', 'nv-product-widgets')]);
        $this->add_control('c2_header', ['label' => __('Competitor 2 header (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('c3_header', ['label' => __('Competitor 3 header (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->end_controls_section();

        $this->start_controls_section('section_rows', ['label' => __('Rows', 'nv-product-widgets')]);
        $this->add_control('rows_help', ['type' => \Elementor\Controls_Manager::RAW_HTML, 'raw' => __('Tip: type <b>yes</b>/<b>ja</b> for a check, <b>no</b>/<b>nej</b>/<b>-</b> for a cross, or any text (e.g. "24 tim", "$$") for a value.', 'nv-product-widgets')]);
        $r = new \Elementor\Repeater();
        $r->add_control('label', ['label' => __('Feature', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Snabb effekt', 'nv-product-widgets')]);
        $r->add_control('us', ['label' => __('You', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'yes']);
        $r->add_control('c1', ['label' => __('Competitor 1', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'no']);
        $r->add_control('c2', ['label' => __('Competitor 2', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'no']);
        $r->add_control('c3', ['label' => __('Competitor 3', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'no']);
        $this->add_control('rows', [
            'label' => __('Rows', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ label }}}',
            'default' => [
                ['label' => __('Snabb effekt', 'nv-product-widgets'), 'us' => 'yes', 'c1' => 'no', 'c2' => 'no', 'c3' => 'no'],
                ['label' => __('Kliniskt testad', 'nv-product-widgets'), 'us' => 'yes', 'c1' => 'no', 'c2' => 'yes', 'c3' => 'no'],
                ['label' => __('Fri frakt', 'nv-product-widgets'), 'us' => 'yes', 'c1' => 'yes', 'c2' => 'no', 'c3' => 'no'],
                ['label' => __('Nöjd-kund-garanti', 'nv-product-widgets'), 'us' => 'yes', 'c1' => 'no', 'c2' => 'no', 'c3' => 'no'],
            ],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('accent', ['label' => __('Accent (your column)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#3B37C4', 'selectors' => ['{{WRAPPER}} .nv-pw-cg' => '--nv-cg-accent: {{VALUE}};']]);
        $this->add_control('check_color', ['label' => __('Check (✓) color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#1D9E75', 'selectors' => ['{{WRAPPER}} .nv-pw-cg' => '--nv-cg-check: {{VALUE}};']]);
        $this->add_control('cross_color', ['label' => __('Cross (✕) color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#D14B41', 'selectors' => ['{{WRAPPER}} .nv-pw-cg' => '--nv-cg-cross: {{VALUE}};']]);
        $this->add_control('header_color', ['label' => __('Column header color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#14161D', 'selectors' => ['{{WRAPPER}} .nv-pw-cg' => '--nv-cg-header: {{VALUE}};']]);
        $this->add_control('card_bg', ['label' => __('Card background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => ['{{WRAPPER}} .nv-pw-cg' => '--nv-cg-card-bg: {{VALUE}};']]);
        $this->add_control('highlight_labels', ['label' => __('Highlight the feature column', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => 'yes']);
        $this->add_control('label_col_bg', ['label' => __('Feature column background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#3B37C4', 'selectors' => ['{{WRAPPER}} .nv-pw-cg' => '--nv-cg-label-bg: {{VALUE}};'], 'condition' => ['highlight_labels' => 'yes']]);
        $this->add_control('label_col_color', ['label' => __('Feature column text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => ['{{WRAPPER}} .nv-pw-cg' => '--nv-cg-label-color: {{VALUE}};'], 'condition' => ['highlight_labels' => 'yes']]);
        $this->add_control('radius', ['label' => __('Card radius', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 32]], 'default' => ['size' => 18, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-cg__table' => 'border-radius: {{SIZE}}{{UNIT}};']]);
        $this->add_control('cta_text', ['label' => __('Button text (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('cta_link', ['label' => __('Button link', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::URL, 'default' => ['url' => '#'], 'condition' => ['cta_text!' => '']]);
        $this->end_controls_section();
    }

    private function cell(string $v, bool $is_us): string {
        $v = trim($v);
        $lc = function_exists('mb_strtolower') ? mb_strtolower($v) : strtolower($v);
        $yes = ['yes', 'ja', 'y', 'true', '✓', 'check', 'v'];
        $no = ['no', 'nej', 'n', 'false', '✗', 'x', '-', '–', '—', ''];
        if (in_array($lc, $yes, true)) {
            return '<span class="nv-pw-cg__mark nv-pw-cg__mark--yes' . ($is_us ? ' is-us' : '') . '" aria-label="' . esc_attr__('Ja', 'nv-product-widgets') . '"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>';
        }
        if (in_array($lc, $no, true)) {
            return '<span class="nv-pw-cg__mark nv-pw-cg__mark--no" aria-label="' . esc_attr__('Nej', 'nv-product-widgets') . '"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg></span>';
        }
        return '<span class="nv-pw-cg__val">' . esc_html($v) . '</span>';
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $rows_in = is_array($s['rows'] ?? null) ? $s['rows'] : [];
        $rows = [];
        foreach ($rows_in as $r) {
            if (is_array($r) && trim((string) ($r['label'] ?? '')) !== '') $rows[] = $r;
        }
        if (empty($rows)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Comparison Grid', '📊');
            return;
        }

        // Build competitor column keys that have a header
        $comp = [];
        foreach (['c1', 'c2', 'c3'] as $k) {
            $h = trim((string) ($s[$k . '_header'] ?? ''));
            if ($h !== '') $comp[$k] = $h;
        }

        $eyebrow = trim((string) ($s['eyebrow'] ?? ''));
        $headline = trim((string) ($s['headline'] ?? ''));
        $intro = trim((string) ($s['intro'] ?? ''));
        $us_header = trim((string) ($s['us_header'] ?? 'Vi')) ?: 'Vi';
        $us_badge = trim((string) ($s['us_badge'] ?? ''));
        $us_image = isset($s['us_image']['url']) ? (string) $s['us_image']['url'] : '';
        $total_cols = 1 + count($comp);
        $cta_text = trim((string) ($s['cta_text'] ?? ''));
        $cta_url = isset($s['cta_link']['url']) ? (string) $s['cta_link']['url'] : '';
        $cta_target = !empty($s['cta_link']['is_external']) ? ' target="_blank" rel="noopener"' : '';
        $hl_labels = (($s['highlight_labels'] ?? 'yes') === 'yes');
        $split = (($s['split_layout'] ?? '') === 'yes');
        $bullets = [];
        if ($split) {
            foreach ((array) ($s['bullets'] ?? []) as $bl) {
                if (is_array($bl) && trim((string) ($bl['text'] ?? '')) !== '') $bullets[] = trim((string) $bl['text']);
            }
        }

        // The marketing column (heading + checklist + CTA) is shared between layouts;
        // in split mode it sits left of the table, otherwise it stacks above it.
        $head_html = '';
        if ($eyebrow !== '' || $headline !== '' || $intro !== '' || ($split && (!empty($bullets) || $cta_text !== ''))) {
            ob_start(); ?>
            <div class="nv-pw-cg__head">
                <?php if ($eyebrow !== '') : ?><span class="nv-pw-cg__eyebrow"><?php echo esc_html($eyebrow); ?></span><?php endif; ?>
                <?php if ($headline !== '') : ?><h3 class="nv-pw-cg__headline"><?php echo esc_html($headline); ?></h3><?php endif; ?>
                <?php if ($intro !== '') : ?><p class="nv-pw-cg__intro"><?php echo esc_html($intro); ?></p><?php endif; ?>
                <?php if ($split && !empty($bullets)) : ?>
                    <ul class="nv-pw-cg__bullets">
                        <?php foreach ($bullets as $bt) : ?>
                            <li><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><?php echo esc_html($bt); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($split && $cta_text !== '') : ?><a class="nv-pw-cg__cta" href="<?php echo esc_url($cta_url !== '' ? $cta_url : '#'); ?>"<?php echo $cta_target; ?>><?php echo esc_html($cta_text); ?></a><?php endif; ?>
            </div>
            <?php $head_html = (string) ob_get_clean();
        }
        ?>
        <div class="nv-pw-cg<?php echo $hl_labels ? ' nv-pw-cg--hl' : ''; ?><?php echo $split ? ' nv-pw-cg--split' : ''; ?>" style="--nv-cg-cols: <?php echo (int) $total_cols; ?>;">
            <?php if (!$split && $head_html !== '') echo $head_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php if ($split) : ?><div class="nv-pw-cg__split"><?php if ($head_html !== '') echo $head_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><div class="nv-pw-cg__tablewrap"><?php endif; ?>

            <div class="nv-pw-cg__table" role="table">
                <div class="nv-pw-cg__row nv-pw-cg__row--head" role="row">
                    <span class="nv-pw-cg__cell nv-pw-cg__cell--label" role="columnheader"></span>
                    <span class="nv-pw-cg__cell nv-pw-cg__cell--us" role="columnheader">
                        <?php if ($us_badge !== '') : ?><span class="nv-pw-cg__badge"><?php echo esc_html($us_badge); ?></span><?php endif; ?>
                        <?php if ($us_image !== '') : ?><img class="nv-pw-cg__colimg" src="<?php echo esc_url($us_image); ?>" alt="<?php echo esc_attr($us_header); ?>" loading="lazy"><?php endif; ?>
                        <span class="nv-pw-cg__colname"><?php echo esc_html($us_header); ?></span>
                    </span>
                    <?php foreach ($comp as $k => $h) : ?>
                        <span class="nv-pw-cg__cell" role="columnheader"><span class="nv-pw-cg__colname"><?php echo esc_html($h); ?></span></span>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($rows as $row) : ?>
                    <div class="nv-pw-cg__row" role="row">
                        <span class="nv-pw-cg__cell nv-pw-cg__cell--label" role="cell"><?php echo esc_html((string) ($row['label'] ?? '')); ?></span>
                        <span class="nv-pw-cg__cell nv-pw-cg__cell--us" role="cell"><?php echo $this->cell((string) ($row['us'] ?? ''), true); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        <?php foreach ($comp as $k => $h) : ?>
                            <span class="nv-pw-cg__cell" role="cell"><?php echo $this->cell((string) ($row[$k] ?? ''), false); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($split) : ?></div><?php /* .nv-pw-cg__tablewrap */ ?></div><?php /* .nv-pw-cg__split */ ?><?php endif; ?>

            <?php if (!$split && $cta_text !== '') : ?>
                <div class="nv-pw-cg__foot"><a class="nv-pw-cg__cta" href="<?php echo esc_url($cta_url !== '' ? $cta_url : '#'); ?>"<?php echo $cta_target; ?>><?php echo esc_html($cta_text); ?></a></div>
            <?php endif; ?>
        </div>
        <?php
    }
}
