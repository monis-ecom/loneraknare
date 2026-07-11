<?php
if (!defined('ABSPATH')) exit;

class NV_PW_FAQ extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-faq'; }
    public function get_title(): string { return 'NV: FAQ'; }
    public function get_icon(): string { return 'eicon-accordion'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['faq', 'accordion', 'question', 'answer']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_faq', [
            'label' => 'FAQ Items',
        ]);
        $this->add_control('faq_title', [
            'label' => 'Section Title',
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => 'Vanliga frågor',
        ]);
        $this->add_control('source', [
            'label' => 'Source',
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'manual',
            'options' => ['manual' => 'Manual', 'product_meta' => 'Product Meta (NV Commerce Core)'],
        ]);
        $this->add_control('faq_items', [
            'label' => 'FAQ Items',
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => [
                ['name' => 'question', 'label' => 'Question', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Question?'],
                ['name' => 'answer', 'label' => 'Answer', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Answer here.'],
            ],
            'title_field' => '{{{ question }}}',
            'condition' => ['source' => 'manual'],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_style', [
            'label' => 'Style',
            'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control('title_color', [
            'label' => 'Title Color',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#111318',
            'selectors' => ['{{WRAPPER}} .nv-pw-faq__title' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('question_color', [
            'label' => 'Question Color',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#111318',
            'selectors' => ['{{WRAPPER}} .nv-pw-faq__question' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('answer_color', [
            'label' => 'Answer Color',
            'type' => \Elementor\Controls_Manager::COLOR,
            'default' => '#5F6874',
            'selectors' => ['{{WRAPPER}} .nv-pw-faq__answer' => 'color: {{VALUE}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $items = [];

        if ($settings['source'] === 'product_meta') {
            $product = NV_PW_Editor_Helper::get_preview_product();
            if ($product instanceof WC_Product) {
                $faq_json = get_post_meta($product->get_id(), '_nv_pdp_faq_items', true);
                if (is_string($faq_json) && $faq_json !== '') {
                    $decoded = json_decode($faq_json, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $item) {
                            if (!empty($item['q']) && !empty($item['a'])) {
                                $items[] = ['question' => $item['q'], 'answer' => $item['a']];
                            }
                        }
                    }
                }
            }
        } else {
            $items = $settings['faq_items'] ?? [];
        }

        if (empty($items)) {
            if (NV_PW_Editor_Helper::is_editor()) {
                echo '<p style="color:#999;font-family:Manrope,sans-serif;font-size:13px;">Add FAQ items in the widget settings.</p>';
            }
            return;
        }

        $title = $settings['faq_title'] ?? '';
        ?>
        <div class="nv-pw-faq">
            <?php if ($title !== '') : ?>
                <h3 class="nv-pw-faq__title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            <div class="nv-pw-faq__list">
                <?php foreach ($items as $index => $item) :
                    $q = esc_html($item['question'] ?? '');
                    $a = wp_kses_post($item['answer'] ?? '');
                    if ($q === '') continue;
                    ?>
                    <details class="nv-pw-faq__item"<?php echo $index === 0 ? ' open' : ''; ?>>
                        <summary class="nv-pw-faq__question"><?php echo $q; ?></summary>
                        <div class="nv-pw-faq__answer"><?php echo $a; ?></div>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
