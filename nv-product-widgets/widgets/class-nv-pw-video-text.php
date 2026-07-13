<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Video_Text extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-video-text'; }
    public function get_title(): string { return 'NV: Video + Text'; }
    public function get_icon(): string { return 'eicon-video-playlist'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['video', 'text', 'split', 'media', 'youtube', 'vimeo']; }
    public function get_script_depends(): array { return ['nv-video']; }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);

        $this->add_control('source', [
            'label' => __('Source', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'file',
            'options' => [
                'file' => __('Uploaded file (MP4)', 'nv-product-widgets'),
                'youtube' => __('YouTube', 'nv-product-widgets'),
                'vimeo' => __('Vimeo', 'nv-product-widgets'),
            ],
        ]);
        $this->add_control('url', [
            'label' => __('YouTube / Vimeo URL or ID', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'condition' => ['source!' => 'file'],
            'label_block' => true,
        ]);
        $this->add_control('file', [
            'label' => __('Video file (MP4)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'media_types' => ['video'],
            'condition' => ['source' => 'file'],
        ]);
        $this->add_control('fallback_url', [
            'label' => __('Fallback embed URL (used if no file)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'condition' => ['source' => 'file'],
            'label_block' => true,
        ]);
        $this->add_control('poster', [
            'label' => __('Poster image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
        ]);
        $this->add_control('aspect', [
            'label' => __('Video shape', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '16-9',
            'options' => ['16-9' => __('Wide 16:9', 'nv-product-widgets'), '1-1' => __('Square 1:1', 'nv-product-widgets'), '9-16' => __('Vertical 9:16', 'nv-product-widgets')],
        ]);
        $this->add_control('video_side', [
            'label' => __('Video position', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'left',
            'options' => ['left' => __('Left', 'nv-product-widgets'), 'right' => __('Right', 'nv-product-widgets')],
        ]);
        $this->add_control('eyebrow', [
            'label' => __('Overline (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('SE HUR DEN FUNGERAR', 'nv-product-widgets'),
        ]);
        $this->add_control('headline', [
            'label' => __('Headline', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Enkel att använda – direkt ur lådan.', 'nv-product-widgets'),
            'label_block' => true,
        ]);
        $this->add_control('text', [
            'label' => __('Text', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXTAREA,
            'default' => __('Se hur snabbt du kommer igång. Inga krångliga steg – bara resultat.', 'nv-product-widgets'),
        ]);

        $b = new \Elementor\Repeater();
        $b->add_control('text', ['label' => __('Bullet', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => __('Klart på minuter', 'nv-product-widgets')]);
        $this->add_control('bullets', [
            'label' => __('Bullets (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $b->get_controls(),
            'title_field' => '{{{ text }}}',
            'default' => [
                ['text' => __('Inga verktyg behövs', 'nv-product-widgets')],
                ['text' => __('Klart på minuter', 'nv-product-widgets')],
            ],
        ]);
        $this->add_control('cta_text', ['label' => __('Button text (optional)', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('cta_link', ['label' => __('Button link', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::URL, 'default' => ['url' => '#'], 'condition' => ['cta_text!' => '']]);

        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_control('accent', ['label' => __('Accent color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#312E81', 'selectors' => ['{{WRAPPER}} .nv-pw-vt' => '--nv-vt-accent: {{VALUE}};']]);
        $this->add_control('heading_color', ['label' => __('Heading color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#14161D', 'selectors' => ['{{WRAPPER}} .nv-pw-vt' => '--nv-vt-heading: {{VALUE}};']]);
        $this->add_control('text_color', ['label' => __('Text color', 'nv-product-widgets'), 'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#4B5563', 'selectors' => ['{{WRAPPER}} .nv-pw-vt' => '--nv-vt-text: {{VALUE}};']]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $type = in_array(($s['source'] ?? 'youtube'), ['file', 'youtube', 'vimeo'], true) ? (string) $s['source'] : 'youtube';
        $poster = isset($s['poster']['url']) ? (string) $s['poster']['url'] : '';
        $embed = ''; $file = '';
        if ($type === 'file') {
            $file = isset($s['file']['url']) ? (string) $s['file']['url'] : '';
            if ($file === '') {
                $fb = NV_PW_Video_Slider::auto_embed((string) ($s['fallback_url'] ?? ''));
                if ($fb !== '') { $embed = $fb; $type = 'embed'; }
            }
        } else {
            $embed = NV_PW_Video_Slider::build_embed($type, (string) ($s['url'] ?? ''));
            if ($poster === '' && $type === 'youtube') {
                $poster = NV_PW_Video_Slider::youtube_thumb((string) ($s['url'] ?? ''));
            }
        }

        $headline = trim((string) ($s['headline'] ?? ''));
        if ($embed === '' && $file === '' && $headline === '') {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Video + Text — add a video link', '🎬');
            }
            return;
        }

        $side = ($s['video_side'] ?? 'left') === 'right' ? 'right' : 'left';
        $aspect = in_array(($s['aspect'] ?? '16-9'), ['16-9', '1-1', '9-16'], true) ? (string) $s['aspect'] : '16-9';
        $eyebrow = trim((string) ($s['eyebrow'] ?? ''));
        $text = trim((string) ($s['text'] ?? ''));
        $bullets = [];
        foreach (($s['bullets'] ?? []) as $bb) {
            if (is_array($bb) && trim((string) ($bb['text'] ?? '')) !== '') $bullets[] = trim((string) $bb['text']);
        }
        $cta_text = trim((string) ($s['cta_text'] ?? ''));
        $cta_url = isset($s['cta_link']['url']) ? (string) $s['cta_link']['url'] : '';
        $cta_target = !empty($s['cta_link']['is_external']) ? ' target="_blank" rel="noopener"' : '';
        $has_video = ($embed !== '' || $file !== '');
        ?>
        <div class="nv-pw-vt nv-pw-vt--video-<?php echo esc_attr($side); ?>" data-nv-video>
            <?php if ($has_video) : ?>
                <div class="nv-pw-vt__media">
                    <div class="nv-pw-video nv-pw-ar nv-pw-ar--<?php echo esc_attr($aspect); ?>" data-type="<?php echo esc_attr($type); ?>" <?php if ($embed !== '') : ?>data-embed="<?php echo esc_url($embed); ?>"<?php endif; ?> <?php if ($file !== '') : ?>data-file="<?php echo esc_url($file); ?>"<?php endif; ?>>
                        <?php if ($poster !== '') : ?>
                            <img class="nv-pw-video__poster" src="<?php echo esc_url($poster); ?>" alt="<?php echo esc_attr($headline); ?>" loading="lazy">
                        <?php else : ?>
                            <span class="nv-pw-video__poster nv-pw-video__poster--blank" aria-hidden="true"></span>
                        <?php endif; ?>
                        <button type="button" class="nv-pw-video__play" aria-label="<?php echo esc_attr__('Spela upp video', 'nv-product-widgets'); ?>"><svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg></button>
                    </div>
                </div>
            <?php endif; ?>
            <div class="nv-pw-vt__body">
                <?php if ($eyebrow !== '') : ?><span class="nv-pw-vt__eyebrow"><?php echo esc_html($eyebrow); ?></span><?php endif; ?>
                <?php if ($headline !== '') : ?><h3 class="nv-pw-vt__headline<?php echo NV_PW_Headline::mod($s); ?>"><?php echo esc_html($headline); ?></h3><?php endif; ?>
                <?php if ($text !== '') : ?><p class="nv-pw-vt__text"><?php echo esc_html($text); ?></p><?php endif; ?>
                <?php if (!empty($bullets)) : ?>
                    <ul class="nv-pw-vt__bullets">
                        <?php foreach ($bullets as $bx) : ?>
                            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><?php echo esc_html($bx); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($cta_text !== '') : ?><a class="nv-pw-vt__btn" href="<?php echo esc_url($cta_url !== '' ? $cta_url : '#'); ?>"<?php echo $cta_target; ?>><?php echo esc_html($cta_text); ?></a><?php endif; ?>
            </div>
        </div>
        <?php
    }
}
