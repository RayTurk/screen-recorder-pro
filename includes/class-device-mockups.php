<?php

/**
 * Enhanced Device Mockup Handler with PNG Frame Assets
 * Replace your existing class-device-mockups.php with this enhanced version
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
    // Set up frame assets directory
    self::$frames_dir = SRP_PLUGIN_DIR . 'assets/frames/';
    self::$frames_url = SRP_PLUGIN_URL . 'assets/frames/';

    // Create frames directory if it doesn't exist
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
        'frame_file' => 'mobile-iphone-xr.png',  // Rename your current file to this
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
        'scale_factor' => 0.25  // Smaller scale for better display
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
        'scale_factor' => 0.25
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
        'scale_factor' => 0.35
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
        'scale_factor' => 0.25
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
        'scale_factor' => 0.15  // Very small scale due to huge size
      ]
    ];
  }

  /**
   * Render device frame with PNG overlay
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

    // Check if frame file exists
    if (!file_exists($frame_path)) {
      error_log('SRP: Frame file not found: ' . $frame_path);
      return self::render_css_fallback($video_url, $device_type, $options);
    }

    return self::render_png_frame($video_url, $frame_config, $frame_url, $options);
  }

  /**
   * Render video with PNG frame overlay
   */
  private static function render_png_frame($video_url, $frame_config, $frame_url, $options)
  {
    $container_id = 'srp-frame-' . uniqid();
    $scale = $frame_config['scale_factor'];

    $classes = ['srp-png-device-frame', 'srp-device-' . str_replace('_', '-', array_search($frame_config, self::get_device_frames()))];
    if (!empty($options['class'])) {
      $classes[] = $options['class'];
    }

    $video_attrs = self::get_video_attributes($options);

    ob_start();
?>
    <div id="<?php echo esc_attr($container_id); ?>" class="<?php echo esc_attr(implode(' ', $classes)); ?>"
      style="<?php echo esc_attr($options['style'] ?? ''); ?>">

      <!-- Container for positioning -->
      <div class="srp-frame-container"
        style="width: <?php echo $frame_config['total_size']['width'] * $scale; ?>px;
                  height: <?php echo $frame_config['total_size']['height'] * $scale; ?>px;
                  position: relative;
                  margin: 20px auto;">

        <!-- Video element positioned behind frame -->
        <video class="srp-frame-video"
          style="position: absolute;
                      left: <?php echo $frame_config['screen_area']['x'] * $scale; ?>px;
                      top: <?php echo $frame_config['screen_area']['y'] * $scale; ?>px;
                      width: calc(<?php echo $frame_config['screen_area']['width'] * $scale; ?>px + 1px);
                      height: calc(<?php echo $frame_config['screen_area']['height'] * $scale; ?>px + 1px);
                      object-fit: cover;
                      border-radius: <?php echo self::get_screen_border_radius($frame_config['type']); ?>;"
          <?php echo implode(' ', $video_attrs); ?>>
          <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
          <p><?php _e('Your browser does not support the video tag.', 'screen-recorder-pro'); ?></p>
        </video>

        <!-- PNG frame overlay -->
        <img src="<?php echo esc_url($frame_url); ?>"
          alt="<?php echo esc_attr($frame_config['name']); ?> frame"
          class="srp-device-frame-overlay"
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

    <?php self::render_png_frame_styles(); ?>

    <script>
      jQuery(document).ready(function($) {
        // Add hover effects for desktop frames
        $('#<?php echo esc_js($container_id); ?>').hover(
          function() {
            $(this).find('.srp-frame-container').css({
              'transform': 'perspective(1000px) rotateY(-2deg) rotateX(1deg)',
              'transition': 'transform 0.3s ease'
            });
          },
          function() {
            $(this).find('.srp-frame-container').css({
              'transform': 'none'
            });
          }
        );

        // Lazy load video for better performance
        var video = $('#<?php echo esc_js($container_id); ?> video')[0];
        if (video && 'IntersectionObserver' in window) {
          var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
              if (entry.isIntersecting) {
                video.load();
                observer.unobserve(video);
              }
            });
          });
          observer.observe(video);
        }
      });
    </script>

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
        return '15px';
      case 'tablet':
        return '8px';
      case 'laptop':
      case 'desktop':
        return '4px';
      default:
        return '0px';
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
   * CSS fallback for missing PNG files
   */
  private static function render_css_fallback($video_url, $device_type, $options)
  {
    // Use simplified CSS frame as fallback
    $classes = ['srp-css-fallback-frame', 'srp-device-' . str_replace('_', '-', $device_type)];
    if (!empty($options['class'])) {
      $classes[] = $options['class'];
    }

    $video_attrs = self::get_video_attributes($options);

    ob_start();
  ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" style="<?php echo esc_attr($options['style'] ?? ''); ?>">
      <div class="srp-css-device-frame">
        <div class="srp-css-device-screen">
          <video class="srp-css-device-video" <?php echo implode(' ', $video_attrs); ?>>
            <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
            <p><?php _e('Your browser does not support the video tag.', 'screen-recorder-pro'); ?></p>
          </video>
        </div>
      </div>
    </div>

    <?php self::render_css_fallback_styles($device_type); ?>

  <?php
    return ob_get_clean();
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
    if (!empty($options['height']) && $options['height'] !== 'auto') {
      $style .= 'height: ' . $options['height'] . ';';
    }

    $video_attrs = self::get_video_attributes($options);

    ob_start();
  ?>
    <video class="<?php echo esc_attr(implode(' ', $classes)); ?>" style="<?php echo esc_attr($style); ?>"
      <?php echo implode(' ', $video_attrs); ?>>
      <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
      <p><?php _e('Your browser does not support the video tag.', 'screen-recorder-pro'); ?></p>
    </video>
  <?php
    return ob_get_clean();
  }

  /**
   * Render PNG frame styles
   */
  private static function render_png_frame_styles()
  {
    static $styles_rendered = false;
    if ($styles_rendered) return;
    $styles_rendered = true;

  ?>
    <style>
      /* PNG Device Frame Styles */
      .srp-png-device-frame {
        display: inline-block;
        margin: 20px auto;
        position: relative;
      }

      .srp-frame-container {
        filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.3));
        transition: transform 0.3s ease;
      }

      .srp-device-frame-overlay {
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
      }

      .srp-frame-video {
        transition: opacity 0.3s ease;
      }

      /* Responsive scaling */
      @media (max-width: 1200px) {
        .srp-png-device-frame .srp-frame-container {
          transform: scale(0.9);
        }
      }

      @media (max-width: 768px) {
        .srp-png-device-frame .srp-frame-container {
          transform: scale(0.7);
        }
      }

      @media (max-width: 480px) {
        .srp-png-device-frame .srp-frame-container {
          transform: scale(0.5);
        }
      }

      /* Loading state */
      .srp-frame-video[data-loading="true"] {
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .srp-frame-video[data-loading="true"]::before {
        content: "Loading...";
        color: #666;
        font-size: 14px;
      }
    </style>
  <?php
  }

  /**
   * CSS fallback styles
   */
  private static function render_css_fallback_styles($device_type)
  {
    static $rendered_types = [];
    if (in_array($device_type, $rendered_types)) return;
    $rendered_types[] = $device_type;

  ?>
    <style>
      /* CSS Fallback Styles for <?php echo esc_attr($device_type); ?> */
      .srp-css-fallback-frame {
        display: inline-block;
        margin: 20px auto;
        position: relative;
      }

      .srp-css-device-frame {
        background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
        border-radius: 25px;
        padding: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        position: relative;
      }

      .srp-css-device-screen {
        background: #000;
        border-radius: 15px;
        overflow: hidden;
        position: relative;
      }

      .srp-css-device-video {
        width: 100%;
        height: 100%;
        display: block;
      }

      /* Device-specific fallback styles */
      <?php if (strpos($device_type, 'iphone') !== false): ?>.srp-device-<?php echo str_replace('_', '-', $device_type); ?>.srp-css-device-frame {
        width: 280px;
        height: 580px;
        border-radius: 38px;
        padding: 12px;
      }

      .srp-device-<?php echo str_replace('_', '-', $device_type); ?>.srp-css-device-screen {
        width: 256px;
        height: 556px;
        border-radius: 28px;
      }

      <?php elseif (strpos($device_type, 'macbook') !== false): ?>.srp-device-<?php echo str_replace('_', '-', $device_type); ?>.srp-css-device-frame {
        width: 500px;
        height: 320px;
        border-radius: 8px;
        padding: 15px 20px 0 20px;
      }

      .srp-device-<?php echo str_replace('_', '-', $device_type); ?>.srp-css-device-screen {
        width: 460px;
        height: 290px;
        border-radius: 4px;
      }

      <?php endif; ?>
      /* Responsive fallback */
      @media (max-width: 768px) {
        .srp-css-fallback-frame {
          transform: scale(0.8);
        }
      }
    </style>
  <?php
  }

  /**
   * Admin function to check which frame files are missing
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
        <p><?php _e('Upload your PNG frame files to this directory. Each frame should have a transparent screen area where the video will be displayed.', 'screen-recorder-pro'); ?></p>
      </div>

      <?php if (!empty($frame_status['existing'])): ?>
        <h2><?php _e('Available Frames', 'screen-recorder-pro'); ?></h2>
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
                    style="max-width: 100px; height: auto;">
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <?php if (!empty($frame_status['missing'])): ?>
        <h2><?php _e('Missing Frame Files', 'screen-recorder-pro'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
          <thead>
            <tr>
              <th><?php _e('Device', 'screen-recorder-pro'); ?></th>
              <th><?php _e('Expected File', 'screen-recorder-pro'); ?></th>
              <th><?php _e('Status', 'screen-recorder-pro'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($frame_status['missing'] as $frame): ?>
              <tr>
                <td><strong><?php echo esc_html($frame['name']); ?></strong></td>
                <td><code><?php echo esc_html($frame['file']); ?></code></td>
                <td><span style="color: #d63638;"><?php _e('Missing', 'screen-recorder-pro'); ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="notice notice-warning">
          <p><?php _e('Missing frame files will fall back to CSS-based frames with lower visual quality.', 'screen-recorder-pro'); ?></p>
        </div>
      <?php endif; ?>

      <h2><?php _e('Frame Specifications', 'screen-recorder-pro'); ?></h2>
      <p><?php _e('When creating your PNG frame files, use these specifications:', 'screen-recorder-pro'); ?></p>

      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th><?php _e('Device', 'screen-recorder-pro'); ?></th>
            <th><?php _e('PNG Size', 'screen-recorder-pro'); ?></th>
            <th><?php _e('Screen Area', 'screen-recorder-pro'); ?></th>
            <th><?php _e('Notes', 'screen-recorder-pro'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php
          $frames = self::get_device_frames();
          foreach ($frames as $device_key => $config):
          ?>
            <tr>
              <td><strong><?php echo esc_html($config['name']); ?></strong></td>
              <td><?php echo $config['total_size']['width']; ?> × <?php echo $config['total_size']['height']; ?>px</td>
              <td>
                X: <?php echo $config['screen_area']['x']; ?>px,
                Y: <?php echo $config['screen_area']['y']; ?>px<br>
                <?php echo $config['screen_area']['width']; ?> × <?php echo $config['screen_area']['height']; ?>px
              </td>
              <td>
                <?php _e('Transparent screen area', 'screen-recorder-pro'); ?><br>
                <small><?php echo ucfirst($config['type']); ?> device</small>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
<?php
  }
}

// Initialize the frame system
add_action('init', ['SRP_Device_Mockups', 'init']);
add_action('admin_menu', ['SRP_Device_Mockups', 'add_frame_settings_page'], 25);
