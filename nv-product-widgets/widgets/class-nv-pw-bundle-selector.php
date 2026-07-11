<?php
/**
 * NV PW: Bundle Selector Widget (v1.4.0)
 *
 * A drag-and-drop Elementor widget that renders a visual bundle/package
 * selector card group on any product page template position.
 *
 * Each bundle card shows:
 *   - Optional highlight badge (e.g. "Mest populär")
 *   - Bundle title + short subtitle
 *   - Included items list (✓ checkmarks)
 *   - Sale price + optional original price + "Du sparar X kr" tag
 *   - Add-to-cart button (AJAX for simple products, scroll-to-form for variable)
 *
 * Clicking a bundle card both selects it visually and fires the add-to-cart.
 */
if (!defined('ABSPATH')) exit;

class NV_PW_Bundle_Selector extends \Elementor\Widget_Base {

    /* ── Widget identity ─────────────────────────────────────────── */

    public function get_name(): string    { return 'nv-bundle-selector'; }
    public function get_title(): string   { return 'NV: Bundle Selector'; }
    public function get_icon(): string    { return 'eicon-product-upsell'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array {
        return ['bundle', 'paket', 'package', 'selector', 'upsell', 'product', 'woocommerce'];
    }

    public function get_script_depends(): array {
        return ['nv-bundle-selector'];
    }

    /* ── Controls ────────────────────────────────────────────────── */

    protected function register_controls(): void {

        /* ── Content: Section heading ─ */
        $this->start_controls_section('section_header', [
            'label' => __('Section Heading', 'nv-product-widgets'),
        ]);

        $this->add_control('section_title', [
            'label'   => __('Title', 'nv-product-widgets'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Välj ditt paket', 'nv-product-widgets'),
        ]);

        $this->add_control('section_subtitle', [
            'label'   => __('Subtitle (optional)', 'nv-product-widgets'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);

        $this->end_controls_section();

        /* ── Content: Bundle cards ─ */
        $this->start_controls_section('section_bundles', [
            'label' => __('Bundle Cards', 'nv-product-widgets'),
        ]);

        $this->add_control('layout', [
            'label'   => __('Layout', 'nv-product-widgets'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'row',
            'options' => [
                'row'    => __('Side by side (row)', 'nv-product-widgets'),
                'column' => __('Stacked (column)', 'nv-product-widgets'),
            ],
        ]);

        $this->add_control('bundles', [
            'label'  => __('Bundles', 'nv-product-widgets'),
            'type'   => \Elementor\Controls_Manager::REPEATER,
            'fields' => [
                /* Badge */
                [
                    'name'    => 'badge_text',
                    'label'   => __('Badge text (optional)', 'nv-product-widgets'),
                    'type'    => \Elementor\Controls_Manager::TEXT,
                    'default' => '',
                    'placeholder' => __('e.g. Mest populär', 'nv-product-widgets'),
                ],
                [
                    'name'    => 'badge_style',
                    'label'   => __('Badge colour', 'nv-product-widgets'),
                    'type'    => \Elementor\Controls_Manager::SELECT,
                    'default' => 'teal',
                    'options' => [
                        'teal'  => __('Teal (brand)', 'nv-product-widgets'),
                        'amber' => __('Amber (deal)', 'nv-product-widgets'),
                        'dark'  => __('Dark (premium)', 'nv-product-widgets'),
                    ],
                ],

                /* Bundle name + subtitle */
                [
                    'name'    => 'bundle_title',
                    'label'   => __('Bundle name', 'nv-product-widgets'),
                    'type'    => \Elementor\Controls_Manager::TEXT,
                    'default' => __('Startpaket', 'nv-product-widgets'),
                ],
                [
                    'name'    => 'bundle_subtitle',
                    'label'   => __('Subtitle / tagline', 'nv-product-widgets'),
                    'type'    => \Elementor\Controls_Manager::TEXT,
                    'default' => '',
                    'placeholder' => __('e.g. Det fullständiga systemet', 'nv-product-widgets'),
                ],

                /* What's included */
                [
                    'name'    => 'bundle_items',
                    'label'   => __('Included items (one per line)', 'nv-product-widgets'),
                    'type'    => \Elementor\Controls_Manager::TEXTAREA,
                    'default' => "2× GreenGuard Pro\n1× Spray-refill (500 ml)",
                    'placeholder' => __("2× Product A\n1× Product B", 'nv-product-widgets'),
                ],

                /* Pricing */
                [
                    'name'    => 'bundle_price',
                    'label'   => __('Price (display text)', 'nv-product-widgets'),
                    'type'    => \Elementor\Controls_Manager::TEXT,
                    'default' => '399 kr',
                    'placeholder' => '399 kr',
                ],
                [
                    'name'    => 'bundle_original_price',
                    'label'   => __('Original price (optional, shown struck-through)', 'nv-product-widgets'),
                    'type'    => \Elementor\Controls_Manager::TEXT,
                    'default' => '',
                    'placeholder' => '599 kr',
                ],
                [
                    'name'    => 'bundle_savings',
                    'label'   => __('Savings text (optional)', 'nv-product-widgets'),
                    'type'    => \Elementor\Controls_Manager::TEXT,
                    'default' => '',
                    'placeholder' => __('Du sparar 200 kr', 'nv-product-widgets'),
                ],

                /* WooCommerce product */
                [
                    'name'    => 'bundle_product_id',
                    'label'   => __('WooCommerce Product ID (for add-to-cart)', 'nv-product-widgets'),
                    'type'    => \Elementor\Controls_Manager::NUMBER,
                    'min'     => 0,
                    'default' => 0,
                    'description' => __('Enter the product ID. Leave 0 to scroll to the main ATC form instead.', 'nv-product-widgets'),
                ],

                /* Visual highlight */
                [
                    'name'         => 'bundle_highlighted',
                    'label'        => __('Highlight this card by default', 'nv-product-widgets'),
                    'type'         => \Elementor\Controls_Manager::SWITCHER,
                    'label_on'     => __('Yes', 'nv-product-widgets'),
                    'label_off'    => __('No', 'nv-product-widgets'),
                    'return_value' => 'yes',
                    'default'      => '',
                ],
            ],
            'title_field' => '{{{ bundle_title }}}',
            'default'     => [
                [
                    'badge_text'          => '',
                    'badge_style'         => 'teal',
                    'bundle_title'        => __('Grundpaket', 'nv-product-widgets'),
                    'bundle_subtitle'     => '',
                    'bundle_items'        => "1× GreenGuard Pro",
                    'bundle_price'        => '249 kr',
                    'bundle_original_price' => '',
                    'bundle_savings'      => '',
                    'bundle_product_id'   => 0,
                    'bundle_highlighted'  => '',
                ],
                [
                    'badge_text'          => __('Mest populär', 'nv-product-widgets'),
                    'badge_style'         => 'teal',
                    'bundle_title'        => __('Startpaket', 'nv-product-widgets'),
                    'bundle_subtitle'     => __('Det fullständiga systemet', 'nv-product-widgets'),
                    'bundle_items'        => "2× GreenGuard Pro\n1× Spray-refill (500 ml)",
                    'bundle_price'        => '399 kr',
                    'bundle_original_price' => '599 kr',
                    'bundle_savings'      => __('Du sparar 200 kr', 'nv-product-widgets'),
                    'bundle_product_id'   => 0,
                    'bundle_highlighted'  => 'yes',
                ],
            ],
        ]);

        $this->end_controls_section();

        /* ── Content: Button ─ */
        $this->start_controls_section('section_button', [
            'label' => __('Add-to-Cart Button', 'nv-product-widgets'),
        ]);

        $this->add_control('button_text', [
            'label'   => __('Button text', 'nv-product-widgets'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Lägg i varukorg', 'nv-product-widgets'),
        ]);

        $this->add_control('button_text_no_product', [
            'label'   => __('Button text (no product ID set)', 'nv-product-widgets'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Välj detta paket ↑', 'nv-product-widgets'),
        ]);

        $this->end_controls_section();

        /* ── Style: Typography + colours ─ */
        $this->start_controls_section('section_style', [
            'label' => __('Card Style', 'nv-product-widgets'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('card_border_radius', [
            'label'   => __('Card border radius', 'nv-product-widgets'),
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'default' => ['size' => 16, 'unit' => 'px'],
            'range'   => ['px' => ['min' => 0, 'max' => 30]],
            'selectors' => ['{{WRAPPER}} .nv-pw-bundle__card' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('highlight_color', [
            'label'   => __('Highlight border colour', 'nv-product-widgets'),
            'type'    => \Elementor\Controls_Manager::COLOR,
            'default' => '#0f766e',
            'selectors' => [
                '{{WRAPPER}} .nv-pw-bundle__card.is-selected' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 3px {{VALUE}}33;',
                '{{WRAPPER}} .nv-pw-bundle__card.is-default-highlight' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 3px {{VALUE}}33;',
            ],
        ]);

        $this->add_control('button_bg_color', [
            'label'     => __('Button background', 'nv-product-widgets'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#0f766e',
            'selectors' => ['{{WRAPPER}} .nv-pw-bundle__btn' => 'background: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }


    /* ── Render ──────────────────────────────────────────────────── */

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $bundles  = $settings['bundles'] ?? [];

        if (empty($bundles)) {
            if (NV_PW_Editor_Helper::is_editor()) {
                echo '<p style="color:#999;font-family:Manrope,sans-serif;font-size:13px;">Add bundle cards in the widget settings.</p>';
            }
            return;
        }

        $title          = $settings['section_title']        ?? '';
        $subtitle       = $settings['section_subtitle']     ?? '';
        $layout         = $settings['layout']               ?? 'row';
        $button_text    = $settings['button_text']          ?? __('Lägg i varukorg', 'nv-product-widgets');
        $btn_no_product = $settings['button_text_no_product'] ?? __('Välj detta paket ↑', 'nv-product-widgets');

        $widget_id = $this->get_id();
        ?>
        <div class="nv-pw-bundle nv-pw-bundle--layout-<?php echo esc_attr($layout); ?>"
             data-widget-id="<?php echo esc_attr($widget_id); ?>">

            <?php if ($title !== '') : ?>
                <div class="nv-pw-bundle__header">
                    <h3 class="nv-pw-bundle__title"><?php echo esc_html($title); ?></h3>
                    <?php if ($subtitle !== '') : ?>
                        <p class="nv-pw-bundle__subtitle"><?php echo esc_html($subtitle); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="nv-pw-bundle__grid">
                <?php foreach ($bundles as $index => $bundle) :
                    $is_highlighted = ($bundle['bundle_highlighted'] ?? '') === 'yes';
                    $badge_text     = trim($bundle['badge_text'] ?? '');
                    $badge_style    = $bundle['badge_style']  ?? 'teal';
                    $b_title        = $bundle['bundle_title'] ?? '';
                    $b_subtitle     = $bundle['bundle_subtitle'] ?? '';
                    $b_items_raw    = $bundle['bundle_items'] ?? '';
                    $b_price        = $bundle['bundle_price'] ?? '';
                    $b_orig_price   = $bundle['bundle_original_price'] ?? '';
                    $b_savings      = $bundle['bundle_savings'] ?? '';
                    $b_product_id   = (int) ($bundle['bundle_product_id'] ?? 0);

                    /* Build items array */
                    $items = [];
                    if ($b_items_raw !== '') {
                        foreach (preg_split('/\r\n|\r|\n/', $b_items_raw) as $line) {
                            $line = trim($line);
                            if ($line !== '') {
                                $items[] = $line;
                            }
                        }
                    }

                    $card_classes = 'nv-pw-bundle__card';
                    if ($is_highlighted) {
                        $card_classes .= ' is-default-highlight';
                    }

                    $btn_label = $b_product_id > 0 ? esc_html($button_text) : esc_html($btn_no_product);
                    ?>
                    <div class="<?php echo esc_attr($card_classes); ?>"
                         data-product-id="<?php echo esc_attr((string) $b_product_id); ?>"
                         data-index="<?php echo esc_attr((string) $index); ?>">

                        <?php if ($badge_text !== '') : ?>
                            <span class="nv-pw-bundle__badge nv-pw-bundle__badge--<?php echo esc_attr($badge_style); ?>">
                                <?php echo esc_html($badge_text); ?>
                            </span>
                        <?php endif; ?>

                        <div class="nv-pw-bundle__body">

                            <!-- Title -->
                            <h4 class="nv-pw-bundle__name"><?php echo esc_html($b_title); ?></h4>

                            <?php if ($b_subtitle !== '') : ?>
                                <p class="nv-pw-bundle__subtitle-text"><?php echo esc_html($b_subtitle); ?></p>
                            <?php endif; ?>

                            <!-- Included items -->
                            <?php if (!empty($items)) : ?>
                                <ul class="nv-pw-bundle__items">
                                    <?php foreach ($items as $item) : ?>
                                        <li class="nv-pw-bundle__item">
                                            <span class="nv-pw-bundle__check" aria-hidden="true">✓</span>
                                            <?php echo esc_html($item); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <!-- Pricing -->
                            <div class="nv-pw-bundle__pricing">
                                <span class="nv-pw-bundle__price"><?php echo esc_html($b_price); ?></span>
                                <?php if ($b_orig_price !== '') : ?>
                                    <span class="nv-pw-bundle__orig-price"><?php echo esc_html($b_orig_price); ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($b_savings !== '') : ?>
                                <div class="nv-pw-bundle__savings">
                                    <span class="nv-pw-bundle__savings-icon">💚</span>
                                    <?php echo esc_html($b_savings); ?>
                                </div>
                            <?php endif; ?>

                        </div><!-- /.body -->

                        <!-- CTA -->
                        <div class="nv-pw-bundle__footer">
                            <button class="nv-pw-bundle__btn" type="button"
                                    data-product-id="<?php echo esc_attr((string) $b_product_id); ?>">
                                <?php echo $btn_label; ?>
                            </button>
                        </div>

                    </div><!-- /.card -->
                <?php endforeach; ?>
            </div><!-- /.grid -->

        </div><!-- /.nv-pw-bundle -->
        <?php
    }
}
