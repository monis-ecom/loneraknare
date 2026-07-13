<?php
if (!defined('ABSPATH')) exit;

/**
 * NV: Media helper — lets any widget with a single media slot offer a
 * self-hosted MP4/WebM video as an alternative to a still image. Register the
 * controls with NV_PW_Media::add_controls($this) (giving the widget's existing
 * image control the condition ['nv_media_type' => 'image']), then in render()
 * branch on NV_PW_Media::is_video($settings) and call
 * NV_PW_Media::render_video($settings).
 */
class NV_PW_Media {

    /**
     * Register the "Media type" switch + self-hosted video controls.
     *
     * @param \Elementor\Widget_Base $w              The widget instance.
     * @param array                  $base_condition Optional condition (e.g.
     *                                               ['layout' => ['split']])
     *                                               applied to every control so
     *                                               they only show when the
     *                                               media slot is relevant.
     */
    public static function add_controls(\Elementor\Widget_Base $w, array $base_condition = []): void {
        $type_ctrl = [
            'label'   => __('Media type', 'nv-product-widgets'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'image',
            'options' => [
                'image' => __('Image', 'nv-product-widgets'),
                'video' => __('Self-hosted video', 'nv-product-widgets'),
            ],
        ];
        if ($base_condition) $type_ctrl['condition'] = $base_condition;
        $w->add_control('nv_media_type', $type_ctrl);

        $vc = array_merge($base_condition, ['nv_media_type' => 'video']);

        $w->add_control('nv_video', [
            'label'       => __('Video (MP4 / WebM)', 'nv-product-widgets'),
            'type'        => \Elementor\Controls_Manager::MEDIA,
            'media_types' => ['video'],
            'condition'   => $vc,
        ]);
        $w->add_control('nv_video_poster', [
            'label'       => __('Poster image (optional)', 'nv-product-widgets'),
            'type'        => \Elementor\Controls_Manager::MEDIA,
            'media_types' => ['image'],
            'description' => __('Shown before the video loads / plays.', 'nv-product-widgets'),
            'condition'   => $vc,
        ]);
        $w->add_control('nv_video_autoplay', [
            'label'     => __('Autoplay', 'nv-product-widgets'),
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => $vc,
        ]);
        $w->add_control('nv_video_loop', [
            'label'     => __('Loop', 'nv-product-widgets'),
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => $vc,
        ]);
        $w->add_control('nv_video_muted', [
            'label'       => __('Muted', 'nv-product-widgets'),
            'type'        => \Elementor\Controls_Manager::SWITCHER,
            'default'     => 'yes',
            'description' => __('Autoplay is only allowed by browsers when the video is muted.', 'nv-product-widgets'),
            'condition'   => $vc,
        ]);
        $w->add_control('nv_video_controls', [
            'label'     => __('Show player controls', 'nv-product-widgets'),
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => '',
            'condition' => $vc,
        ]);
    }

    /** True when the widget is set to video mode and a video file is chosen. */
    public static function is_video(array $s): bool {
        return (($s['nv_media_type'] ?? 'image') === 'video') && trim((string) ($s['nv_video']['url'] ?? '')) !== '';
    }

    /** Output the <video> element for the chosen self-hosted video. */
    public static function render_video(array $s, string $class = 'nv-pw-media__video'): void {
        $url = trim((string) ($s['nv_video']['url'] ?? ''));
        if ($url === '') return;

        $poster   = trim((string) ($s['nv_video_poster']['url'] ?? ''));
        $autoplay = (($s['nv_video_autoplay'] ?? 'yes') === 'yes');
        $loop     = (($s['nv_video_loop'] ?? 'yes') === 'yes');
        $muted    = (($s['nv_video_muted'] ?? 'yes') === 'yes');
        $controls = (($s['nv_video_controls'] ?? '') === 'yes');
        if ($autoplay) $muted = true; // browsers block autoplay unless muted

        $attrs = ' playsinline preload="metadata"';
        if ($autoplay) $attrs .= ' autoplay';
        if ($loop)     $attrs .= ' loop';
        if ($muted)    $attrs .= ' muted';
        if ($controls) $attrs .= ' controls';
        if ($poster !== '') $attrs .= ' poster="' . esc_url($poster) . '"';
        ?>
        <video class="<?php echo esc_attr($class); ?>"<?php echo $attrs; // phpcs:ignore ?>>
            <source src="<?php echo esc_url($url); ?>" type="<?php echo esc_attr(self::mime($url)); ?>">
        </video>
        <?php
    }

    /** Best-effort MIME type from the file extension. */
    private static function mime(string $url): string {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?: $url, PATHINFO_EXTENSION));
        $map = [
            'mp4'  => 'video/mp4',
            'm4v'  => 'video/mp4',
            'webm' => 'video/webm',
            'ogv'  => 'video/ogg',
            'ogg'  => 'video/ogg',
            'mov'  => 'video/quicktime',
        ];
        return $map[$ext] ?? 'video/mp4';
    }
}
