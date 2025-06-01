<?php

/**
 * Simplified Shortcode Handler Class
 * Replace your includes/class-shortcode-handler.php with this version
 */

if (!defined('ABSPATH')) {
  exit;
}

class SRP_Shortcode_Handler
{
  private $recordings_manager;

  public function __construct()
  {
    $this->recordings_manager = new SRP_Recordings_Manager();
  }

  /**
   * Render the screen recording shortcode
   * Usage: [screen_recording id="123" device_frame="true" width="800" height="600" autoplay="false" controls="true"]
   */
  public function render($atts)
  {
    $atts = shortcode_atts([
      'id' => 0,
      'device_frame' => 'auto', // 'true', 'false', or 'auto' (uses recording setting)
      'width' => '100%',
      'height' => 'auto',
      'autoplay' => 'true',
      'controls' => 'false',
      'loop' => 'true',
      'muted' => 'true',
      'class' => 'screen-recording-video',
      'style' => ''
    ], $atts, 'screen_recording');

    $recording_id = intval($atts['id']);

    if (!$recording_id) {
      return '<p><em>' . __('Invalid recording ID.', 'screen-recorder-pro') . '</em></p>';
    }

    // Get the recording from database
    $recording = $this->recordings_manager->get($recording_id);

    if (!$recording) {
      return '<p><em>' . __('Recording not found.', 'screen-recorder-pro') . '</em></p>';
    }

    if ($recording->status !== 'completed') {
      return '<p><em>' . __('Recording not completed yet.', 'screen-recorder-pro') . '</em></p>';
    }

    // Get the video URL
    $video_url = '';

    if ($recording->attachment_id) {
      $video_url = wp_get_attachment_url($recording->attachment_id);
    }

    if (!$video_url && !empty($recording->video_url)) {
      $video_url = $recording->video_url;
    }

    if (!$video_url) {
      return '<p><em>' . __('Video file not found.', 'screen-recorder-pro') . '</em></p>';
    }

    // Determine if we should show device frame
    $show_device_frame = false;
    $options = maybe_unserialize($recording->options);

    if ($atts['device_frame'] === 'true') {
      $show_device_frame = true;
    } elseif ($atts['device_frame'] === 'false') {
      $show_device_frame = false;
    } elseif ($atts['device_frame'] === 'auto' && $options) {
      // Use the setting from when the recording was created
      $show_device_frame = isset($options['show_device_frame']) ? $options['show_device_frame'] : false;
    }

    // Get device configuration
    $device_config = null;
    if ($show_device_frame && $options && isset($options['device_key'])) {
      $device_options = ScreenRecorderPro::get_device_viewport_options();
      $device_config = $device_options[$options['device_key']] ?? null;
    }

    // Render the video
    if ($show_device_frame && $device_config && $device_config['device_frame']) {
      return $this->render_device_frame($video_url, $device_config, $atts);
    } else {
      return $this->render_plain_video($video_url, $atts);
    }
  }

  /**
   * Render video with device frame
   */
  private function render_device_frame($video_url, $device_config, $atts)
  {
    $frame_type = $device_config['frame_type'] ?? 'generic';
    $device_type = $device_config['type'];

    $classes = [
      'srp-device-mockup',
      'srp-device-' . $frame_type,
      'srp-device-type-' . $device_type
    ];

    if (!empty($atts['class'])) {
      $classes[] = $atts['class'];
    }

    $video_attrs = [
      'controls' => false,
      'autoplay' => true,
      'loop' => true,
      'muted' => true
    ];

    $video_attributes = implode(' ', array_filter($video_attrs));

    ob_start();
?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>"
      style="<?php echo esc_attr($atts['style']); ?>">

      <div class="srp-device-frame">
        <!-- Device decorations -->
        <?php $this->render_device_decorations($frame_type); ?>

        <!-- Video screen area -->
        <div class="srp-device-screen">
          <video class="srp-device-video" <?php echo $video_attributes; ?> preload="metadata" autoplay
            muted
            playsinline
            loop>
            <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
            <p><?php _e('Your browser does not support the video tag.', 'screen-recorder-pro'); ?></p>
          </video>

          <!-- Realistic reflection overlay -->
          <div class="srp-screen-reflection"></div>
        </div>
      </div>
    </div>

    <?php $this->render_device_styles(); ?>

  <?php
    return ob_get_clean();
  }

  /**
   * Render device decorations (notch, home button, etc.)
   */
  private function render_device_decorations($frame_type)
  {
  ?>
    <div class="srp-device-decorations">
      <?php if ($frame_type === 'iphone'): ?>
        <div class="srp-notch"></div>
        <div class="srp-home-indicator"></div>
      <?php elseif ($frame_type === 'android'): ?>
        <div class="srp-camera-hole"></div>
      <?php elseif ($frame_type === 'macbook'): ?>
        <div class="srp-laptop-hinge"></div>
      <?php endif; ?>
    </div>
  <?php
  }

  /**
   * Render plain video without device frame
   */
  private function render_plain_video($video_url, $atts)
  {
    $classes = ['screen-recording-video'];
    if (!empty($atts['class'])) {
      $classes[] = $atts['class'];
    }

    $style = $atts['style'];
    if (!empty($atts['width']) && $atts['width'] !== 'auto') {
      $style .= 'max-width: ' . $atts['width'] . ';';
    }
    if (!empty($atts['height']) && $atts['height'] !== 'auto') {
      $style .= 'height: ' . $atts['height'] . ';';
    }

    $video_attrs = [
      'controls' => false,
      'autoplay' => true,
      'loop' => true,
      'muted' => true
    ];

    $video_attributes = implode(' ', array_filter($video_attrs));

    ob_start();
  ?>
    <video class="<?php echo esc_attr(implode(' ', $classes)); ?> "
      style="<?php echo esc_attr($style); ?>"
      <?php echo $video_attributes; ?> autoplay
      muted
      playsinline
      loop>
      <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
      <p><?php _e('Your browser does not support the video tag.', 'screen-recorder-pro'); ?></p>
    </video>
  <?php
    return ob_get_clean();
  }

  /**
   * Render CSS styles for device frames
   */
  private function render_device_styles()
  {
    static $styles_rendered = false;
    if ($styles_rendered) return;
    $styles_rendered = true;

  ?>
    <style>
      /* Device Frame Styles */
      .srp-device-mockup {
        display: inline-block;
        position: relative;
        margin: 20px auto;
        perspective: 1000px;
      }

      .srp-device-frame {
        position: relative;
        background: linear-gradient(145deg, #1a1a1a, #2d2d2d);
        border-radius: 25px;
        padding: 15px;
        box-shadow:
          0 20px 60px rgba(0, 0, 0, 0.4),
          0 8px 20px rgba(0, 0, 0, 0.2),
          inset 0 1px 0 rgba(255, 255, 255, 0.1);
        transform-style: preserve-3d;
        transition: transform 0.3s ease;
      }

      .srp-device-mockup:hover .srp-device-frame {
        transform: rotateY(-3deg) rotateX(2deg);
      }

      .srp-device-screen {
        position: relative;
        background: #000;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: inset 0 0 30px rgba(0, 0, 0, 0.8);
      }

      .srp-device-video {
        width: 100%;
        height: 100%;
        display: block;
        border-radius: 20px;
      }

      .srp-screen-reflection {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 40%;
        background: linear-gradient(135deg,
            rgba(255, 255, 255, 0.08) 0%,
            rgba(255, 255, 255, 0.03) 50%,
            transparent 100%);
        pointer-events: none;
        border-radius: 20px 20px 0 0;
      }

      /* iPhone Frame */
      .srp-device-iphone .srp-device-frame {
        width: 280px;
        height: 580px;
        background: linear-gradient(145deg, #1d1d1f, #000);
        border-radius: 38px;
        padding: 12px;
      }

      .srp-device-iphone .srp-device-screen {
        width: 256px;
        height: 556px;
        border-radius: 28px;
      }

      .srp-device-iphone .srp-notch {
        position: absolute;
        top: 8px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 28px;
        background: #000;
        border-radius: 0 0 20px 20px;
        z-index: 10;
      }

      .srp-device-iphone .srp-home-indicator {
        position: absolute;
        bottom: 8px;
        left: 50%;
        transform: translateX(-50%);
        width: 40px;
        height: 4px;
        background: #333;
        border-radius: 2px;
        z-index: 10;
      }

      /* Android Frame */
      .srp-device-android .srp-device-frame {
        width: 275px;
        height: 570px;
        background: linear-gradient(145deg, #0d0d0d, #1a1a1a);
        border-radius: 28px;
        padding: 10px;
      }

      .srp-device-android .srp-device-screen {
        width: 255px;
        height: 550px;
        border-radius: 22px;
      }

      .srp-device-android .srp-camera-hole {
        position: absolute;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        width: 12px;
        height: 12px;
        background: #000;
        border-radius: 50%;
        z-index: 10;
      }

      /* iPad Frame */
      .srp-device-ipad .srp-device-frame {
        width: 420px;
        height: 600px;
        background: linear-gradient(145deg, #e8e8e8, #d0d0d0);
        border-radius: 22px;
        padding: 18px;
      }

      .srp-device-ipad .srp-device-screen {
        width: 384px;
        height: 564px;
        border-radius: 12px;
      }

      /* MacBook Frame */
      .srp-device-macbook .srp-device-frame {
        width: 500px;
        height: 320px;
        background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
        border-radius: 8px 8px 0 0;
        padding: 15px 20px 0 20px;
        position: relative;
      }

      .srp-device-macbook .srp-device-screen {
        width: 460px;
        height: 290px;
        border-radius: 4px;
      }

      .srp-device-macbook .srp-laptop-hinge {
        position: absolute;
        bottom: -8px;
        left: 0;
        right: 0;
        height: 8px;
        background: linear-gradient(90deg, #1a1a1a, #2d2d2d, #1a1a1a);
      }

      /* Laptop Frame */
      .srp-device-laptop .srp-device-frame {
        width: 480px;
        height: 300px;
        background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
        border-radius: 8px;
        padding: 15px;
      }

      .srp-device-laptop .srp-device-screen {
        width: 450px;
        height: 270px;
        border-radius: 4px;
      }

      /* Responsive */
      @media (max-width: 768px) {
        .srp-device-mockup {
          transform: scale(0.8);
          margin: 15px auto;
        }
      }

      @media (max-width: 480px) {
        .srp-device-mockup {
          transform: scale(0.6);
          margin: 10px auto;
        }
      }

      /* Plain video styles */
      .screen-recording-video {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
      }
    </style>
<?php
  }

  /**
   * Debug method to check what's in the database
   */
  public function debug_recording($recording_id)
  {
    $recording = $this->recordings_manager->get($recording_id);

    if (!$recording) {
      return 'Recording not found in database.';
    }

    $options = maybe_unserialize($recording->options);

    $debug_info = [
      'ID' => $recording->id ?? 'N/A',
      'Status' => $recording->status ?? 'N/A',
      'Attachment ID' => $recording->attachment_id ?? 'NULL',
      'Video URL' => $recording->video_url ?? 'NULL',
      'WP Attachment URL' => ($recording->attachment_id ?? false) ? wp_get_attachment_url($recording->attachment_id) : 'N/A',
      'Post ID' => $recording->post_id ?? 'N/A',
      'URL' => $recording->url ?? 'N/A',
      'Device Key' => $options['device_key'] ?? 'N/A',
      'Device Frame Enabled' => isset($options['show_device_frame']) ? ($options['show_device_frame'] ? 'Yes' : 'No') : 'N/A',
      'Viewport' => ($options['viewport_width'] ?? 'N/A') . 'x' . ($options['viewport_height'] ?? 'N/A'),
      'Created' => $recording->created_at ?? 'N/A'
    ];

    $output = '<pre>Recording Debug Info:' . "\n";
    foreach ($debug_info as $key => $value) {
      $output .= $key . ': ' . $value . "\n";
    }
    $output .= '</pre>';

    return $output;
  }
}
