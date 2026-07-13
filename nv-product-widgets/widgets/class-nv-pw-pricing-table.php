<?php
if (!defined('ABSPATH')) exit;

/**
 * NV: Pricing Table — side-by-side plan / tier columns with feature ticks, a
 * highlighted "best value" column and a per-column CTA. For subscriptions,
 * service tiers or good/better/best offers.
 */
class NV_PW_Pricing_Table extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-pricing-table'; }
    public function get_title(): string { return 'NV: Pricing Table'; }
    public function get_icon(): string { return 'eicon-price-table'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['pricing', 'table', 'plans', 'tiers', 'subscription', 'compare', 'priser']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);
        $this->add_control('heading', ['label' => __('Heading (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '', 'label_block' => true]);
        $this->add_control('subheading', ['label' => __('Subheading (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => '']);
        $r = new \Elementor\Repeater();
        $r->add_control('name', ['label' => __('Plan name', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Plan', 'nv-product-widgets')]);
        $r->add_control('price', ['label' => __('Price', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '299 kr']);
        $r->add_control('period', ['label' => __('Period (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('/ mån', 'nv-product-widgets')]);
        $r->add_control('description', ['label' => __('Description (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $r->add_control('features', ['label' => __('Features (one per line)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => __("Feature one\nFeature two\nFeature three", 'nv-product-widgets'), 'description' => __('Prefix a line with "-" to show it as not included.', 'nv-product-widgets')]);
        $r->add_control('badge', ['label' => __('Badge (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $r->add_control('highlighted', ['label' => __('Highlight this plan', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SWITCHER, 'default' => '']);
        $r->add_control('button_text', ['label' => __('Button text', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Välj plan', 'nv-product-widgets')]);
        $r->add_control('button_link', ['label' => __('Button link', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::URL, 'default' => ['url' => '']]);
        $this->add_control('plans', [
            'label' => __('Plans', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ name }}}',
            'default' => [
                ['name' => __('Bas', 'nv-product-widgets'), 'price' => '299 kr', 'period' => __('/ mån', 'nv-product-widgets'), 'features' => __("1 par\nFri frakt\n- Prioriterad support", 'nv-product-widgets'), 'button_text' => __('Välj Bas', 'nv-product-widgets')],
                ['name' => __('Populär', 'nv-product-widgets'), 'price' => '499 kr', 'period' => __('/ mån', 'nv-product-widgets'), 'features' => __("2 par\nFri frakt\nPrioriterad support", 'nv-product-widgets'), 'badge' => __('Populärast', 'nv-product-widgets'), 'highlighted' => 'yes', 'button_text' => __('Välj Populär', 'nv-product-widgets')],
                ['name' => __('Pro', 'nv-product-widgets'), 'price' => '599 kr', 'period' => __('/ mån', 'nv-product-widgets'), 'features' => __("3 par\nFri frakt\nPrioriterad support", 'nv-product-widgets'), 'button_text' => __('Välj Pro', 'nv-product-widgets')],
            ],
        ]);
        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('accent', ['label' => __('Accent (highlight / button)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '', 'description' => __('Leave blank to use your global brand accent (Settings → NV Brand).', 'nv-product-widgets'), 'selectors' => ['{{WRAPPER}} .nv-pw-pt' => '--nv-pt-accent: {{VALUE}};']]);
        $this->add_control('card_bg', ['label' => __('Card background', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#FFFFFF', 'selectors' => ['{{WRAPPER}} .nv-pw-pt' => '--nv-pt-card: {{VALUE}};']]);
        $this->add_control('check_color', ['label' => __('Check color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#1D9E75', 'selectors' => ['{{WRAPPER}} .nv-pw-pt' => '--nv-pt-check: {{VALUE}};']]);
        $this->add_control('radius', ['label' => __('Card radius', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 28]], 'default' => ['size' => 16, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .nv-pw-pt' => '--nv-pt-radius: {{SIZE}}px;']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $rows = is_array($s['plans'] ?? null) ? $s['plans'] : [];
        $plans = [];
        foreach ($rows as $p) {
            if (is_array($p) && trim((string) ($p['name'] ?? '')) !== '') $plans[] = $p;
        }
        if (empty($plans)) {
            if (NV_PW_Editor_Helper::is_editor()) NV_PW_Editor_Helper::render_placeholder('NV: Pricing Table — add plans', '💳');
            return;
        }

        $heading = trim((string) ($s['heading'] ?? ''));
        $sub = trim((string) ($s['subheading'] ?? ''));
        ?>
        <div class="nv-pw-pt" style="--nv-pt-count: <?php echo count($plans); ?>;">
            <?php if ($heading !== '' || $sub !== '') : ?>
                <div class="nv-pw-pt__head">
                    <?php if ($heading !== '') : ?><h2 class="nv-pw-pt__heading<?php echo NV_PW_Headline::mod($s); ?>"><?php echo NV_PW_Headline::html($heading); ?></h2><?php endif; ?>
                    <?php if ($sub !== '') : ?><p class="nv-pw-pt__sub"><?php echo esc_html($sub); ?></p><?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="nv-pw-pt__grid">
                <?php foreach ($plans as $p) :
                    $hl = (($p['highlighted'] ?? '') === 'yes');
                    $badge = trim((string) ($p['badge'] ?? ''));
                    $btn = trim((string) ($p['button_text'] ?? ''));
                    $url = isset($p['button_link']['url']) ? (string) $p['button_link']['url'] : '';
                    $target = !empty($p['button_link']['is_external']) ? ' target="_blank" rel="noopener"' : '';
                    $features = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($p['features'] ?? ''))), static function ($l) { return $l !== ''; });
                    ?>
                    <div class="nv-pw-pt__card<?php echo $hl ? ' is-highlighted' : ''; ?>">
                        <?php if ($badge !== '') : ?><span class="nv-pw-pt__badge"><?php echo esc_html($badge); ?></span><?php endif; ?>
                        <div class="nv-pw-pt__name"><?php echo esc_html((string) $p['name']); ?></div>
                        <div class="nv-pw-pt__price"><?php echo esc_html(trim((string) ($p['price'] ?? ''))); ?><?php if (trim((string) ($p['period'] ?? '')) !== '') : ?><span class="nv-pw-pt__period"><?php echo esc_html(trim((string) $p['period'])); ?></span><?php endif; ?></div>
                        <?php if (trim((string) ($p['description'] ?? '')) !== '') : ?><div class="nv-pw-pt__desc"><?php echo esc_html(trim((string) $p['description'])); ?></div><?php endif; ?>
                        <?php if (!empty($features)) : ?>
                            <ul class="nv-pw-pt__features">
                                <?php foreach ($features as $f) :
                                    $off = (strpos($f, '-') === 0);
                                    $label = $off ? trim(ltrim($f, '- ')) : $f;
                                    ?>
                                    <li class="nv-pw-pt__feature<?php echo $off ? ' is-off' : ''; ?>">
                                        <?php if ($off) : ?>
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                        <?php else : ?>
                                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                        <?php endif; ?>
                                        <span><?php echo esc_html($label); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($btn !== '') : ?><a class="nv-pw-pt__btn" href="<?php echo esc_url($url !== '' ? $url : '#'); ?>"<?php echo $target; ?>><?php echo esc_html($btn); ?></a><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
