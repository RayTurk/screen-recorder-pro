<?php

/**
 * Optimized and Responsive Device Mockup Handler
 * Replace your class-device-mockups.php with this optimized version
 */

if (!defined('ABSPATH')) {
  exit;
}

class SRP_Device_Mockups
{
  private static $frames_dir = '';
  private static $frames_url = '';

  public static function init()
  {
    self::$frames_dir = SRP_PLUGIN_DIR . 'assets/frames/';
    self::$frames_url = SRP_PLUGIN_URL . 'assets/frames/';

    if (!file_exists(self::$frames_dir)) {
      wp_mkdir_p(self::$frames_dir);
    }
  }

  /**
   * Get available device frame configurations
   */
  public static function get_device_frames()
  {
    return [
      // Mobile Device
      'mobile_iphone_xr' => [
        'name' => __('Mobile (iPhone XR)', 'screen-recorder-pro'),
        'type' => 'mobile',
        'frame_file' => 'mobile-iphone-xr.png',
        'screen_area' => [
          'x' => 75,
          'y' => 71,
          'width' => 828,
          'height' => 1792
        ],
        'total_size' => [
          'width' => 979,
          'height' => 1934
        ],
        'base_scale' => 0.25,
        'max_width' => 350  // Maximum display width in pixels
      ],

      // Tablet Portrait
      'tablet_ipad_air_portrait' => [
        'name' => __('Tablet (iPad Air 2020) Portrait', 'screen-recorder-pro'),
        'type' => 'tablet',
        'frame_file' => 'tablet-ipad-air-portrait.png',
        'screen_area' => [
          'x' => 110,
          'y' => 114,
          'width' => 1640,
          'height' => 2360
        ],
        'total_size' => [
          'width' => 1864,
          'height' => 2584
        ],
        'base_scale' => 0.25,
        'max_width' => 500
      ],

      // Tablet Landscape
      'tablet_ipad_air_landscape' => [
        'name' => __('Tablet (iPad Air 2020) Landscape', 'screen-recorder-pro'),
        'type' => 'tablet',
        'frame_file' => 'tablet-ipad-air-landscape.png',
        'screen_area' => [
          'x' => 158,
          'y' => 120,
          'width' => 1440,
          'height' => 900
        ],
        'total_size' => [
          'width' => 2584,
          'height' => 1864
        ],
        'base_scale' => 0.35,
        'max_width' => 700
      ],

      // Laptop
      'laptop_macbook_pro' => [
        'name' => __('Laptop (MacBook Pro)', 'screen-recorder-pro'),
        'type' => 'laptop',
        'frame_file' => 'laptop-macbook-pro.png',
        'screen_area' => [
          'x' => 396,
          'y' => 143,
          'width' => 2560,
          'height' => 1600
        ],
        'total_size' => [
          'width' => 3352,
          'height' => 1974
        ],
        'base_scale' => 0.25,
        'max_width' => 800
      ],

      // Desktop
      'desktop_imac_pro' => [
        'name' => __('Desktop (iMac Pro)', 'screen-recorder-pro'),
        'type' => 'desktop',
        'frame_file' => 'desktop-imac-pro.png',
        'screen_area' => [
          'x' => 228,
          'y' => 241,
          'width' => 5120,
          'height' => 2880
        ],
        'total_size' => [
          'width' => 5576,
          'height' => 4610
        ],
        'base_scale' => 0.15,
        'max_width' => 900
      ]
    ];
  }

  /**
   * Render device frame with responsive scaling
   */
  public static function render_device_frame($video_url, $device_type = 'none', $options = [])
  {
    if ($device_type === 'none') {
      return self::render_plain_video($video_url, $options);
    }

    $frames = self::get_device_frames();
    if (!isset($frames[$device_type])) {
      return self::render_plain_video($video_url, $options);
    }

    $frame_config = $frames[$device_type];
    $frame_path = self::$frames_dir . $frame_config['frame_file'];
    $frame_url = self::$frames_url . $frame_config['frame_file'];

    if (!file_exists($frame_path)) {
      return self::render_plain_video($video_url, $options);
    }

    return self::render_responsive_frame($video_url, $frame_config, $frame_url, $options);
  }

  /**
   * Render responsive video with PNG frame overlay
   */
  private static function render_responsive_frame($video_url, $frame_config, $frame_url, $options)
  {
    $container_id = 'srp-frame-' . uniqid();
    $max_width = $frame_config['max_width'];
    $aspect_ratio = $frame_config['total_size']['height'] / $frame_config['total_size']['width'];

    $classes = ['srp-responsive-device-frame', 'srp-device-' . str_replace('_', '-', array_search($frame_config, self::get_device_frames()))];
    if (!empty($options['class'])) {
      $classes[] = $options['class'];
    }

    $video_attrs = self::get_video_attributes($options);

    ob_start();

    // Pre-calculate all percentages to avoid PHP syntax errors
    $aspect_ratio_percent = ($aspect_ratio * 100);
    $left_percent = ($frame_config['screen_area']['x'] / $frame_config['total_size']['width']) * 100;
    $top_percent = ($frame_config['screen_area']['y'] / $frame_config['total_size']['height']) * 100;
    $width_percent = ($frame_config['screen_area']['width'] / $frame_config['total_size']['width']) * 100;
    $height_percent = ($frame_config['screen_area']['height'] / $frame_config['total_size']['height']) * 100;

?>
    <div id="<?php echo esc_attr($container_id); ?>" class="<?php echo esc_attr(implode(' ', $classes)); ?>"
      style="<?php echo esc_attr($options['style'] ?? ''); ?>">

      <!-- Responsive container -->
      <div class="srp-responsive-container"
        style="max-width: <?php echo $max_width; ?>px;
                  width: 100%;
                  margin: 20px auto;
                  position: relative;">

        <!-- Frame wrapper with aspect ratio -->
        <div class="srp-frame-wrapper"
          style="position: relative;
                    width: 100%;
                    height: 0;
                    padding-bottom: <?php echo $aspect_ratio_percent; ?>%;">

          <!-- Video positioned absolutely within wrapper -->
          <video class="srp-responsive-video"
            style="position: absolute;
                        left: <?php echo $left_percent; ?>%;
                        top: <?php echo $top_percent; ?>%;
                        width: <?php echo $width_percent; ?>%;
                        height: <?php echo $height_percent; ?>%;
                        object-fit: cover;
                        border-radius: 0;"
            <?php echo implode(' ', $video_attrs); ?>>
            <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
            <p><?php _e('Your browser does not support the video tag.', 'screen-recorder-pro'); ?></p>
          </video>

          <!-- PNG frame overlay -->
          <img src="<?php echo esc_url($frame_url); ?>"
            alt="<?php echo esc_attr($frame_config['name']); ?> frame"
            class="srp-responsive-frame-overlay"
            style="position: absolute;
                      top: 0;
                      left: 0;
                      width: 100%;
                      height: 100%;
                      pointer-events: none;
                      z-index: 10;"
            loading="lazy">

        </div>
      </div>
    </div>

    <?php self::render_responsive_styles(); ?>

    <?php if (($options['interactive'] ?? true) !== false): ?>
      <script>
        jQuery(document).ready(function($) {
          // Intersection Observer for lazy loading
          if ('IntersectionObserver' in window) {
            var video = $('#<?php echo esc_js($container_id); ?> video')[0];
            var observer = new IntersectionObserver(function(entries) {
              entries.forEach(function(entry) {
                if (entry.isIntersecting && video.readyState === 0) {
                  video.load();
                  observer.unobserve(video);
                }
              });
            });
            observer.observe(video);
          }
        });
      </script>
    <?php endif; ?>

  <?php
    return ob_get_clean();
  }

  /**
   * Get screen border radius based on device type
   */
  private static function get_screen_border_radius($device_type)
  {
    switch ($device_type) {
      case 'mobile':
        return '3%';
      case 'tablet':
        return '2%';
      case 'laptop':
      case 'desktop':
        return '1%';
      default:
        return '0%';
    }
  }

  /**
   * Get video attributes array
   */
  private static function get_video_attributes($options)
  {
    $attrs = [];

    if (($options['controls'] ?? 'false') === 'true') {
      $attrs[] = 'controls';
    }
    if (($options['autoplay'] ?? 'true') === 'true') {
      $attrs[] = 'autoplay';
    }
    if (($options['loop'] ?? 'true') === 'true') {
      $attrs[] = 'loop';
    }
    if (($options['muted'] ?? 'true') === 'true') {
      $attrs[] = 'muted';
    }

    $attrs[] = 'playsinline';
    $attrs[] = 'preload="metadata"';

    return $attrs;
  }

  /**
   * Plain video without frame
   */
  private static function render_plain_video($video_url, $options)
  {
    $classes = ['screen-recording-video'];
    if (!empty($options['class'])) {
      $classes[] = $options['class'];
    }

    $style = $options['style'] ?? '';
    if (!empty($options['width']) && $options['width'] !== 'auto') {
      $style .= 'max-width: ' . $options['width'] . ';';
    }

    $video_attrs = self::get_video_attributes($options);

    ob_start();
  ?>
    <video class="<?php echo esc_attr(implode(' ', $classes)); ?>"
      style="<?php echo esc_attr($style); ?>"
      <?php echo implode(' ', $video_attrs); ?>>
      <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
      <p><?php _e('Your browser does not support the video tag.', 'screen-recorder-pro'); ?></p>
    </video>

    <style>
      .screen-recording-video {
        width: 100%;
        max-width: 800px;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        display: block;
        margin: 20px auto;
      }
    </style>
  <?php
    return ob_get_clean();
  }

  /**
   * Render responsive CSS styles
   */
  private static function render_responsive_styles()
  {
    static $styles_rendered = false;
    if ($styles_rendered) return;
    $styles_rendered = true;

  ?>
    <style>
      /* Responsive Device Frame Styles */
      .srp-responsive-device-frame {
        display: block;
        margin: 20px auto;
        position: relative;
        width: 100%;
      }

      .srp-responsive-container {
        filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.3));
        transition: transform 0.3s ease, filter 0.3s ease;
      }

      .srp-responsive-container:hover {
        transform: translateY(-2px);
        filter: drop-shadow(0 15px 40px rgba(0, 0, 0, 0.4));
      }

      .srp-frame-wrapper {
        background: transparent;
      }

      .srp-responsive-frame-overlay {
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
      }

      .srp-responsive-video {
        transition: opacity 0.3s ease;
      }

      /* Mobile-first responsive breakpoints */
      @media (max-width: 480px) {
        .srp-responsive-container {
          max-width: 90% !important;
          margin: 15px auto !important;
        }
      }

      @media (max-width: 768px) {
        .srp-responsive-container {
          max-width: 85% !important;
          margin: 18px auto !important;
        }
      }

      @media (max-width: 1024px) {
        .srp-responsive-container {
          max-width: 90% !important;
        }
      }

      /* High DPI displays */
      @media (-webkit-min-device-pixel-ratio: 2),
      (min-resolution: 192dpi) {
        .srp-responsive-frame-overlay {
          image-rendering: -webkit-optimize-contrast;
          image-rendering: crisp-edges;
        }
      }

      /* Reduce motion for accessibility */
      @media (prefers-reduced-motion: reduce) {
        .srp-responsive-container {
          transition: none;
        }

        .srp-responsive-container:hover {
          transform: none;
        }
      }

      /* Print styles */
      @media print {
        .srp-responsive-device-frame {
          break-inside: avoid;
        }
      }
    </style>
  <?php
  }

  /**
   * Check frame assets (simplified, no debug output)
   */
  public static function check_frame_assets()
  {
    $frames = self::get_device_frames();
    $missing = [];
    $existing = [];

    foreach ($frames as $device_key => $frame_config) {
      $frame_path = self::$frames_dir . $frame_config['frame_file'];
      if (file_exists($frame_path)) {
        $existing[] = [
          'device' => $device_key,
          'name' => $frame_config['name'],
          'file' => $frame_config['frame_file'],
          'size' => size_format(filesize($frame_path))
        ];
      } else {
        $missing[] = [
          'device' => $device_key,
          'name' => $frame_config['name'],
          'file' => $frame_config['frame_file']
        ];
      }
    }

    return [
      'existing' => $existing,
      'missing' => $missing,
      'frames_dir' => self::$frames_dir,
      'frames_url' => self::$frames_url
    ];
  }

  /**
   * Add settings page for frame management
   */
  public static function add_frame_settings_page()
  {
    add_submenu_page(
      'screen-recorder',
      __('Device Frames', 'screen-recorder-pro'),
      __('Device Frames', 'screen-recorder-pro'),
      'manage_options',
      'screen-recorder-frames',
      [__CLASS__, 'render_frame_settings_page']
    );
  }

  public static function render_frame_settings_page()
  {
    $frame_status = self::check_frame_assets();
  ?>
    <div class="wrap">
      <h1><?php _e('Device Frame Assets', 'screen-recorder-pro'); ?></h1>

      <div class="notice notice-info">
        <p><strong><?php _e('Frame Assets Directory:', 'screen-recorder-pro'); ?></strong>
          <code><?php echo esc_html($frame_status['frames_dir']); ?></code>
        </p>
      </div>

      <?php if (!empty($frame_status['existing'])): ?>
        <h2><?php _e('Available Frames', 'screen-recorder-pro'); ?> (<?php echo count($frame_status['existing']); ?>)</h2>
        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th><?php _e('Device', 'screen-recorder-pro'); ?></th>
              <th><?php _e('File', 'screen-recorder-pro'); ?></th>
              <th><?php _e('Size', 'screen-recorder-pro'); ?></th>
              <th><?php _e('Preview', 'screen-recorder-pro'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($frame_status['existing'] as $frame): ?>
              <tr>
                <td><strong><?php echo esc_html($frame['name']); ?></strong></td>
                <td><code><?php echo esc_html($frame['file']); ?></code></td>
                <td><?php echo esc_html($frame['size']); ?></td>
                <td>
                  <img src="<?php echo esc_url($frame_status['frames_url'] . $frame['file']); ?>"
                    alt="<?php echo esc_attr($frame['name']); ?>"
                    style="max-width: 80px; height: auto;">
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <?php if (!empty($frame_status['missing'])): ?>
        <h2><?php _e('Missing Frame Files', 'screen-recorder-pro'); ?> (<?php echo count($frame_status['missing']); ?>)</h2>
        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th><?php _e('Device', 'screen-recorder-pro'); ?></th>
              <th><?php _e('Expected File', 'screen-recorder-pro'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($frame_status['missing'] as $frame): ?>
              <tr>
                <td><strong><?php echo esc_html($frame['name']); ?></strong></td>
                <td><code><?php echo esc_html($frame['file']); ?></code></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
<?php
  }
}

// Initialize the frame system
add_action('init', ['SRP_Device_Mockups', 'init']);
add_action('admin_menu', ['SRP_Device_Mockups', 'add_frame_settings_page'], 25);
