<?php

/**
 * Updated Shortcode Handler Class for PNG Device Frames
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
   * Render the screen recording shortcode with PNG frame support
   * Usage: [screen_recording id="123" device_frame="true" width="800" height="600" autoplay="false" controls="true"]
   */
  public function render($atts)
  {
    $atts = shortcode_atts([
      'id' => 0,
      'device_frame' => 'auto', // 'true', 'false', or 'auto' (uses recording setting)
      'device_type' => '',      // Override device type: 'iphone_15_pro', 'macbook_pro_14', etc.
      'width' => 'auto',
      'height' => 'auto',
      'autoplay' => 'true',
      'controls' => 'false',
      'loop' => 'true',
      'muted' => 'true',
      'class' => '',
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

    // Determine device frame settings
    $show_device_frame = false;
    $device_type = 'none';
    $options = maybe_unserialize($recording->options);

    // Override device type if specified in shortcode
    if (!empty($atts['device_type'])) {
      $device_type = $atts['device_type'];
      $show_device_frame = true;
    } else {
      // Use settings from recording
      if ($atts['device_frame'] === 'true') {
        $show_device_frame = true;
      } elseif ($atts['device_frame'] === 'false') {
        $show_device_frame = false;
      } elseif ($atts['device_frame'] === 'auto' && $options) {
        $show_device_frame = isset($options['show_device_frame']) ? $options['show_device_frame'] : false;
      }

      // Get device type from recording options
      if ($show_device_frame && $options && isset($options['device_key'])) {
        $device_type = $this->map_device_key_to_frame_type($options['device_key']);
      }
    }

    // Prepare render options
    $render_options = [
      'controls' => $atts['controls'],
      'autoplay' => $atts['autoplay'],
      'loop' => $atts['loop'],
      'muted' => $atts['muted'],
      'class' => $atts['class'],
      'style' => $atts['style'],
      'width' => $atts['width'],
      'height' => $atts['height']
    ];

    // Use the new PNG device mockup system
    if (class_exists('SRP_Device_Mockups')) {
      return SRP_Device_Mockups::render_device_frame($video_url, $device_type, $render_options);
    }

    // Fallback to plain video if mockup class not available
    return $this->render_plain_video($video_url, $render_options);
  }

  /**
   * Map old device keys to new frame types
   */
  private function map_device_key_to_frame_type($device_key)
  {
    $mapping = [
      // Mobile mappings
      'phone_iphone_15_pro' => 'mobile_iphone_xr',
      'phone_iphone_15_pro_max' => 'mobile_iphone_xr',
      'phone_samsung_s24' => 'mobile_iphone_xr',
      'mobile_iphone_xr' => 'mobile_iphone_xr',

      // Tablet mappings
      'tablet_ipad_pro' => 'tablet_ipad_air_portrait',
      'tablet_ipad' => 'tablet_ipad_air_portrait',
      'tablet_ipad_air_portrait' => 'tablet_ipad_air_portrait',
      'tablet_ipad_air_landscape' => 'tablet_ipad_air_landscape',

      // Laptop mappings
      'laptop_macbook' => 'laptop_macbook_pro',
      'laptop_generic' => 'laptop_macbook_pro',
      'laptop_macbook_pro' => 'laptop_macbook_pro',
      'macbook_pro_14' => 'laptop_macbook_pro',
      'macbook_air_13' => 'laptop_macbook_pro',

      // Desktop mappings
      'desktop_1920' => 'desktop_imac_pro',
      'desktop_1440' => 'desktop_imac_pro',
      'desktop_1280' => 'desktop_imac_pro',
      'imac_24' => 'desktop_imac_pro',
      'desktop_imac_pro' => 'desktop_imac_pro'
    ];

    return $mapping[$device_key] ?? 'none';
  }

  /**
   * Render plain video without device frame (fallback)
   */
  private function render_plain_video($video_url, $options)
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

    $video_attrs = [];
    if (($options['controls'] ?? 'false') === 'true') {
      $video_attrs[] = 'controls';
    }
    if (($options['autoplay'] ?? 'true') === 'true') {
      $video_attrs[] = 'autoplay';
    }
    if (($options['loop'] ?? 'true') === 'true') {
      $video_attrs[] = 'loop';
    }
    if (($options['muted'] ?? 'true') === 'true') {
      $video_attrs[] = 'muted';
    }
    $video_attrs[] = 'playsinline';

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
        max-width: 100%;
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
   * Debug method to check what's in the database
   */
  public function debug_recording($recording_id)
  {
    $recording = $this->recordings_manager->get($recording_id);

    if (!$recording) {
      return 'Recording not found in database.';
    }

    $options = maybe_unserialize($recording->options);

    // Check frame file availability
    $frame_status = '';
    if ($options && isset($options['device_key'])) {
      $frame_type = $this->map_device_key_to_frame_type($options['device_key']);
      if ($frame_type !== 'none' && class_exists('SRP_Device_Mockups')) {
        $frames = SRP_Device_Mockups::get_device_frames();
        if (isset($frames[$frame_type])) {
          $frame_file = $frames[$frame_type]['frame_file'];
          $frame_path = SRP_PLUGIN_DIR . 'assets/frames/' . $frame_file;
          $frame_status = file_exists($frame_path) ? 'PNG Available' : 'PNG Missing (using CSS fallback)';
        }
      }
    }

    $debug_info = [
      'ID' => $recording->id ?? 'N/A',
      'Status' => $recording->status ?? 'N/A',
      'Attachment ID' => $recording->attachment_id ?? 'NULL',
      'Video URL' => $recording->video_url ?? 'NULL',
      'WP Attachment URL' => ($recording->attachment_id ?? false) ? wp_get_attachment_url($recording->attachment_id) : 'N/A',
      'Post ID' => $recording->post_id ?? 'N/A',
      'Original URL' => $recording->url ?? 'N/A',
      'Device Key (Old)' => $options['device_key'] ?? 'N/A',
      'Frame Type (New)' => $options && isset($options['device_key']) ? $this->map_device_key_to_frame_type($options['device_key']) : 'N/A',
      'Device Frame Enabled' => isset($options['show_device_frame']) ? ($options['show_device_frame'] ? 'Yes' : 'No') : 'N/A',
      'Frame Asset Status' => $frame_status ?: 'N/A',
      'Viewport' => ($options['viewport_width'] ?? 'N/A') . 'x' . ($options['viewport_height'] ?? 'N/A'),
      'Created' => $recording->created_at ?? 'N/A'
    ];

    $output = '<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace;">';
    $output .= '<h3>Recording Debug Info (ID: ' . $recording_id . ')</h3>';
    $output .= '<table style="width: 100%; border-collapse: collapse;">';

    foreach ($debug_info as $key => $value) {
      $output .= '<tr style="border-bottom: 1px solid #eee;">';
      $output .= '<td style="padding: 8px; font-weight: bold; width: 200px;">' . esc_html($key) . ':</td>';
      $output .= '<td style="padding: 8px;">' . esc_html($value) . '</td>';
      $output .= '</tr>';
    }

    $output .= '</table>';

    // Show example shortcodes
    $output .= '<h4>Example Shortcodes:</h4>';
    $output .= '<p><strong>Default:</strong> <code>[screen_recording id="' . $recording_id . '"]</code></p>';
    $output .= '<p><strong>With Frame:</strong> <code>[screen_recording id="' . $recording_id . '" device_frame="true"]</code></p>';
    $output .= '<p><strong>Without Frame:</strong> <code>[screen_recording id="' . $recording_id . '" device_frame="false"]</code></p>';
    $output .= '<p><strong>Custom Device:</strong> <code>[screen_recording id="' . $recording_id . '" device_type="iphone_15_pro"]</code></p>';
    $output .= '<p><strong>With Controls:</strong> <code>[screen_recording id="' . $recording_id . '" controls="true" autoplay="false"]</code></p>';

    $output .= '</div>';

    return $output;
  }

  /**
   * Get available frame types for admin
   */
  public function get_available_frame_types()
  {
    if (class_exists('SRP_Device_Mockups')) {
      return SRP_Device_Mockups::get_device_frames();
    }
    return [];
  }
}
