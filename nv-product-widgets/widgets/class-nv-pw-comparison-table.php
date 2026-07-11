<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Comparison_Table extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-comparison-table'; }
    public function get_title(): string { return 'NV: Comparison Table'; }
    public function get_icon(): string { return 'eicon-table'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['comparison', 'before after', 'versus', 'table', 'benefits']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', [
            'label' => __('Content', 'nv-product-widgets'),
        ]);

        $this->add_control('eyebrow', [
            'label' => __('Overline (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Funktion #2', 'nv-product-widgets'),
        ]);

        $this->add_control('headline', [
            'label' => __('Headline', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Sluta vifta - börja njuta.', 'nv-product-widgets'),
        ]);

        $this->add_control('intro', [
            'label' => __('Subheadline / intro text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __("Före BuzzBlock™ var varje sommarkväll en förhandling med myggor och knott.\nMed BuzzBlock™ är det en installation - sedan är kvällen din.", 'nv-product-widgets'),
        ]);

        $this->add_control('left_header', [
            'label' => __('Left column header', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('FÖRUT', 'nv-product-widgets'),
        ]);

        $this->add_control('left_icon', [
            'label' => __('Left header icon', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '✕',
        ]);

        $this->add_control('right_header', [
            'label' => __('Right column header', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('NU MED BUZZBLOCK™', 'nv-product-widgets'),
        ]);

        $this->add_control('right_icon', [
            'label' => __('Right header icon', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '✓',
        ]);

        $repeater = new \Elementor\Repeater();
        $repeater->add_control('left_text', [
            'label' => __('Left comparison text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Myggspray på huden - kletigt, luktar, måste appliceras om', 'nv-product-widgets'),
        ]);
        $repeater->add_control('right_text', [
            'label' => __('Right comparison text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('<strong>Inga kemikalier</strong> - ren sommarluft innanför nätet', 'nv-product-widgets'),
        ]);

        $this->add_control('rows', [
            'label' => __('Comparison rows', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'title_field' => '{{{ left_text }}}',
            'default' => [
                [
                    'left_text' => __('Myggspray på huden - kletigt, luktar, måste appliceras om', 'nv-product-widgets'),
                    'right_text' => __('<strong>Inga kemikalier</strong> - ren sommarluft innanför nätet', 'nv-product-widgets'),
                ],
                [
                    'left_text' => __('Thermacell och gasdrivna avskräckare - batterier, patroner, känsliga för vind', 'nv-product-widgets'),
                    'right_text' => __('<strong>Fysisk barriär</strong> - fungerar alltid, inga förbrukningsartiklar', 'nv-product-widgets'),
                ],
                [
                    'left_text' => __('Barnen krafsar sig och vill springa in', 'nv-product-widgets'),
                    'right_text' => __('<strong>Barnen sitter kvar</strong> vid bordet hela middagen', 'nv-product-widgets'),
                ],
                [
                    'left_text' => __('Gästerna börjar vifta - stämningen sjunker', 'nv-product-widgets'),
                    'right_text' => __('<strong>Stämningen hålls</strong> - ingen distraheras', 'nv-product-widgets'),
                ],
                [
                    'left_text' => __('Mygg OCH knott - dubbelt problem, en enda lösning räcker inte', 'nv-product-widgets'),
                    'right_text' => __('<strong>Stoppar båda</strong> - maskstorlek ≤1 mm blockerar även knott', 'nv-product-widgets'),
                ],
                [
                    'left_text' => __('Kvällen avslutas tidigt, "vi går in nu"', 'nv-product-widgets'),
                    'right_text' => __('<strong>Kvällen fortsätter</strong> tills du bestämmer', 'nv-product-widgets'),
                ],
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();

        $eyebrow = trim((string) ($settings['eyebrow'] ?? ''));
        $headline = trim((string) ($settings['headline'] ?? ''));
        $intro = trim((string) ($settings['intro'] ?? ''));
        $left_header = trim((string) ($settings['left_header'] ?? ''));
        $left_icon = trim((string) ($settings['left_icon'] ?? ''));
        $right_header = trim((string) ($settings['right_header'] ?? ''));
        $right_icon = trim((string) ($settings['right_icon'] ?? ''));

        $rows = [];
        foreach (($settings['rows'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $left_text = trim((string) ($row['left_text'] ?? ''));
            $right_text = trim((string) ($row['right_text'] ?? ''));
            if ($left_text === '' && $right_text === '') {
                continue;
            }

            $rows[] = [
                'left_text' => $left_text,
                'right_text' => $right_text,
            ];
        }

        if ($headline === '' && $intro === '' && empty($rows)) {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Comparison Table', '≠');
            }
            return;
        }

        $left_label = trim($left_header . ($left_icon !== '' ? ' ' . $left_icon : ''));
        $right_label = trim($right_header . ($right_icon !== '' ? ' ' . $right_icon : ''));

        ?>
        <div class="nv-pw-comparison-table">
            <?php if ($eyebrow !== '') : ?>
                <p class="nv-pw-comparison-table__eyebrow"><?php echo esc_html($eyebrow); ?></p>
            <?php endif; ?>
            <?php if ($headline !== '') : ?>
                <h3 class="nv-pw-comparison-table__headline"><?php echo esc_html($headline); ?></h3>
            <?php endif; ?>
            <?php if ($intro !== '') : ?>
                <div class="nv-pw-comparison-table__intro"><?php echo wp_kses_post(wpautop($intro)); ?></div>
            <?php endif; ?>

            <?php if (!empty($rows)) : ?>
                <div class="nv-pw-comparison-table__table-wrap">
                    <table class="nv-pw-comparison-table__table">
                        <thead>
                            <tr>
                                <th scope="col">
                                    <span class="nv-pw-comparison-table__header-text"><?php echo esc_html($left_header); ?></span>
                                    <?php if ($left_icon !== '') : ?>
                                        <span class="nv-pw-comparison-table__icon nv-pw-comparison-table__icon--left" aria-hidden="true"><?php echo esc_html($left_icon); ?></span>
                                    <?php endif; ?>
                                </th>
                                <th scope="col">
                                    <span class="nv-pw-comparison-table__header-text"><?php echo esc_html($right_header); ?></span>
                                    <?php if ($right_icon !== '') : ?>
                                        <span class="nv-pw-comparison-table__icon nv-pw-comparison-table__icon--right" aria-hidden="true"><?php echo esc_html($right_icon); ?></span>
                                    <?php endif; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row) : ?>
                                <tr>
                                    <td data-label="<?php echo esc_attr($left_label); ?>"><?php echo wp_kses_post(wpautop($row['left_text'])); ?></td>
                                    <td data-label="<?php echo esc_attr($right_label); ?>"><?php echo wp_kses_post(wpautop($row['right_text'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
