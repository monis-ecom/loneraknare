<?php
if (!defined('ABSPATH')) exit;

class NV_PW_Video_Slider extends \Elementor\Widget_Base {
    public function get_name(): string { return 'nv-video-slider'; }
    public function get_title(): string { return 'NV: Video Slider'; }
    public function get_icon(): string { return 'eicon-slider-video'; }
    public function get_categories(): array { return ['woocommerce-elements']; }
    public function get_keywords(): array { return ['video', 'slider', 'reels', 'ugc', 'carousel', 'tiktok']; }
    public function get_script_depends(): array { return ['nv-video']; }

    public static function build_embed(string $type, string $url): string {
        $url = trim($url);
        if ($url === '') return '';
        if ($type === 'youtube') {
            $id = '';
            if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $url, $m)) {
                $id = $m[1];
            } elseif (preg_match('~^[A-Za-z0-9_-]{6,}$~', $url)) {
                $id = $url;
            }
            return $id !== '' ? 'https://www.youtube.com/embed/' . rawurlencode($id) . '?autoplay=1&rel=0&playsinline=1' : '';
        }
        if ($type === 'vimeo') {
            $id = '';
            if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
                $id = $m[1];
            } elseif (preg_match('~^\d+$~', $url)) {
                $id = $url;
            }
            return $id !== '' ? 'https://player.vimeo.com/video/' . rawurlencode($id) . '?autoplay=1' : '';
        }
        return '';
    }

    public static function auto_embed(string $url): string {
        $url = trim($url);
        if ($url === '') return '';
        $yt = self::build_embed('youtube', $url);
        if ($yt !== '') return $yt;
        return self::build_embed('vimeo', $url);
    }

    public static function youtube_thumb(string $url): string {
        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $url, $m)) {
            return 'https://img.youtube.com/vi/' . $m[1] . '/hqdefault.jpg';
        }
        if (preg_match('~^[A-Za-z0-9_-]{6,}$~', trim($url))) {
            return 'https://img.youtube.com/vi/' . trim($url) . '/hqdefault.jpg';
        }
        return '';
    }

    protected function register_controls(): void {
        $this->start_controls_section('section_content', ['label' => __('Content', 'nv-product-widgets')]);

        $this->add_control('heading', [
            'label' => __('Heading (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => __('Se den i verkligheten', 'nv-product-widgets'),
        ]);
        $this->add_control('aspect', [
            'label' => __('Video shape', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '9-16',
            'options' => [
                '9-16' => __('Vertical 9:16 (reels)', 'nv-product-widgets'),
                '1-1' => __('Square 1:1', 'nv-product-widgets'),
                '16-9' => __('Wide 16:9', 'nv-product-widgets'),
            ],
        ]);

        $r = new \Elementor\Repeater();
        $r->add_control('source', [
            'label' => __('Source', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'file',
            'options' => [
                'file' => __('Uploaded file (MP4)', 'nv-product-widgets'),
                'youtube' => __('YouTube', 'nv-product-widgets'),
                'vimeo' => __('Vimeo', 'nv-product-widgets'),
            ],
        ]);
        $r->add_control('url', [
            'label' => __('YouTube / Vimeo URL or ID', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'default' => '',
            'condition' => ['source!' => 'file'],
            'label_block' => true,
        ]);
        $r->add_control('file', [
            'label' => __('Video file (MP4)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
            'media_types' => ['video'],
            'condition' => ['source' => 'file'],
        ]);
        $r->add_control('fallback_url', [
            'label' => __('Fallback embed URL (used if no file)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'condition' => ['source' => 'file'],
            'label_block' => true,
        ]);
        $r->add_control('poster', [
            'label' => __('Poster image', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::MEDIA,
        ]);
        $r->add_control('caption', [
            'label' => __('Caption (optional)', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::TEXT,
        ]);

        $this->add_control('items', [
            'label' => __('Videos', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::REPEATER,
            'fields' => $r->get_controls(),
            'title_field' => '{{{ caption || source }}}',
            'default' => [
                ['source' => 'file', 'caption' => __('Kundrecension', 'nv-product-widgets')],
                ['source' => 'file', 'caption' => __('Så använder du den', 'nv-product-widgets')],
                ['source' => 'file', 'caption' => __('Unboxing', 'nv-product-widgets')],
            ],
        ]);

        $this->add_control('nv_hl_style', NV_PW_Headline::args());
        $this->end_controls_section();

        $this->start_controls_section('section_style', ['label' => __('Style', 'nv-product-widgets'), 'tab' => \Elementor\Controls_Manager::TAB_STYLE]);
        $this->add_responsive_control('card_w', [
            'label' => __('Card width', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::SLIDER,
            'range' => ['px' => ['min' => 160, 'max' => 420]],
            'default' => ['size' => 240, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .nv-pw-vs' => '--nv-vs-card: {{SIZE}}{{UNIT}};'],
        ]);
        $this->add_control('accent', [
            'label' => __('Accent color', 'nv-product-widgets'),
            'type' => \Elementor\Controls_Manager::COLOR, 'default' => '#312E81',
            'selectors' => ['{{WRAPPER}} .nv-pw-vs' => '--nv-vs-accent: {{VALUE}};'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        $rows = is_array($s['items'] ?? null) ? $s['items'] : [];
        $cards = [];
        foreach ($rows as $r) {
            if (!is_array($r)) continue;
            $type = in_array(($r['source'] ?? 'youtube'), ['file', 'youtube', 'vimeo'], true) ? $r['source'] : 'youtube';
            $poster = isset($r['poster']['url']) ? (string) $r['poster']['url'] : '';
            $embed = ''; $file = '';
            if ($type === 'file') {
                $file = isset($r['file']['url']) ? (string) $r['file']['url'] : '';
                if ($file === '') {
                    $embed = self::auto_embed((string) ($r['fallback_url'] ?? ''));
                    if ($embed === '') continue;
                    $type = 'embed';
                }
            } else {
                $embed = self::build_embed($type, (string) ($r['url'] ?? ''));
                if ($embed === '') continue;
                if ($poster === '' && $type === 'youtube') {
                    $poster = self::youtube_thumb((string) ($r['url'] ?? ''));
                }
            }
            $cards[] = ['type' => $type, 'embed' => $embed, 'file' => $file, 'poster' => $poster, 'caption' => trim((string) ($r['caption'] ?? ''))];
        }
        if (empty($cards)) {
            if (NV_PW_Editor_Helper::is_editor()) {
                NV_PW_Editor_Helper::render_placeholder('NV: Video Slider — add videos & links', '🎬');
            }
            return;
        }
        $heading = trim((string) ($s['heading'] ?? ''));
        $aspect = in_array(($s['aspect'] ?? '9-16'), ['9-16', '1-1', '16-9'], true) ? (string) $s['aspect'] : '9-16';
        ?>
        <div class="nv-pw-vs" data-nv-video>
            <div class="nv-pw-vs__head">
                <?php if ($heading !== '') : ?><h3 class="nv-pw-vs__heading<?php echo NV_PW_Headline::mod($s); ?>"><?php echo NV_PW_Headline::html($heading); ?></h3><?php endif; ?>
                <div class="nv-pw-vs__arrows">
                    <button type="button" class="nv-pw-vs__arrow" data-nv-prev aria-label="<?php echo esc_attr__('Föregående', 'nv-product-widgets'); ?>"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>
                    <button type="button" class="nv-pw-vs__arrow" data-nv-next aria-label="<?php echo esc_attr__('Nästa', 'nv-product-widgets'); ?>"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>
                </div>
            </div>
            <div class="nv-pw-vs__track" data-nv-track>
                <?php foreach ($cards as $c) : ?>
                    <div class="nv-pw-vs__card nv-pw-ar nv-pw-ar--<?php echo esc_attr($aspect); ?>">
                        <div class="nv-pw-video" data-type="<?php echo esc_attr($c['type']); ?>" <?php if ($c['embed'] !== '') : ?>data-embed="<?php echo esc_url($c['embed']); ?>"<?php endif; ?> <?php if ($c['file'] !== '') : ?>data-file="<?php echo esc_url($c['file']); ?>"<?php endif; ?>>
                            <?php if ($c['poster'] !== '') : ?>
                                <img class="nv-pw-video__poster" src="<?php echo esc_url($c['poster']); ?>" alt="<?php echo esc_attr($c['caption']); ?>" loading="lazy">
                            <?php else : ?>
                                <span class="nv-pw-video__poster nv-pw-video__poster--blank" aria-hidden="true"></span>
                            <?php endif; ?>
                            <button type="button" class="nv-pw-video__play" aria-label="<?php echo esc_attr__('Spela upp video', 'nv-product-widgets'); ?>">
                                <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                            </button>
                        </div>
                        <?php if ($c['caption'] !== '') : ?><p class="nv-pw-vs__caption"><?php echo esc_html($c['caption']); ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
