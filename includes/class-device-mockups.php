<?php

/**
 * Enhanced Device Mockup Handler with Mediamodifier API Integration
 * Replace your existing class-device-mockups.php with this enhanced version
 */

if (!defined('ABSPATH')) {
  exit;
}

class SRP_Device_Mockups
{
  private static $mediamodifier_api_key = null;
  private static $cache_duration = 3600; // 1 hour cache

  public static function init()
  {
    // Get API key from secure config
    self::$mediamodifier_api_key = srp_get_mediamodifier_api_key();
  }

  public static function get_device_options()
  {
    return [
      'none' => [
        'name' => __('No Device Frame', 'screen-recorder-pro'),
        'type' => 'none'
      ],
      // High-quality device options with Mediamodifier mockup IDs
      'iphone' => [
        'name' => __('iPhone - Premium', 'screen-recorder-pro'),
        'type' => 'mobile',
        'viewport' => '393x852',
        'mediamodifier_id' => '964', // Using working mockup ID from your example
        'fallback_css' => true
      ],
      'iphone_15_pro' => [
        'name' => __('iPhone 15 Pro - Premium', 'screen-recorder-pro'),
        'type' => 'mobile',
        'viewport' => '393x852',
        'mediamodifier_id' => '964', // Using working mockup ID
        'fallback_css' => true
      ],
      'iphone_15_pro_max' => [
        'name' => __('iPhone 15 Pro Max - Premium', 'screen-recorder-pro'),
        'type' => 'mobile',
        'viewport' => '430x932',
        'mediamodifier_id' => '964', // Using working mockup ID
        'fallback_css' => true
      ],
      'samsung_s24' => [
        'name' => __('Samsung Galaxy S24 - Premium', 'screen-recorder-pro'),
        'type' => 'mobile',
        'viewport' => '384x854',
        'mediamodifier_id' => '964', // Using working mockup ID for now
        'fallback_css' => true
      ],
      'ipad_pro_11' => [
        'name' => __('iPad Pro 11" - Premium', 'screen-recorder-pro'),
        'type' => 'tablet',
        'viewport' => '834x1194',
        'mediamodifier_id' => '964', // You'll need to find iPad mockup ID
        'fallback_css' => true
      ],
      'macbook_pro' => [
        'name' => __('MacBook Pro - Premium', 'screen-recorder-pro'),
        'type' => 'laptop',
        'viewport' => '1440x900',
        'mediamodifier_id' => '964', // You'll need to find MacBook mockup ID
        'fallback_css' => true
      ]
    ];
  }

  public static function render_device_frame($video_url, $device_type = 'none', $options = [])
  {
    if ($device_type === 'none') {
      return self::render_plain_video($video_url, $options);
    }

    $devices = self::get_device_options();
    if (!isset($devices[$device_type])) {
      return self::render_plain_video($video_url, $options);
    }

    $device = $devices[$device_type];

    // Try premium API first, fallback to CSS if not available
    if (!empty(self::$mediamodifier_api_key) && !empty($device['mediamodifier_id'])) {
      $premium_result = self::render_premium_mockup($video_url, $device, $options);
      if ($premium_result !== false) {
        return $premium_result;
      }
    }

    // Fallback to CSS-based mockup
    if (!empty($device['fallback_css'])) {
      return self::render_css_mockup($video_url, $device_type, $options);
    }

    return self::render_plain_video($video_url, $options);
  }

  /**
   * Render premium mockup using Mediamodifier API
   */
  private static function render_premium_mockup($video_url, $device, $options)
  {
    if (empty(self::$mediamodifier_api_key)) {
      return false;
    }

    // Check if we can make API calls (within limits)
    if (!srp_can_use_mediamodifier()) {
      error_log('SRP: Mediamodifier API limit reached for this month');
      return false;
    }

    // Check cache first
    $cache_key = 'srp_mockup_' . md5($video_url . $device['mediamodifier_id']);
    $cached_result = get_transient($cache_key);

    if ($cached_result !== false) {
      return self::render_mockup_result($cached_result, $options);
    }

    try {
      // Step 1: Get mockup details
      $mockup_details = self::get_mediamodifier_mockup_details($device['mediamodifier_id']);
      if (!$mockup_details) {
        return false;
      }

      // Step 2: Generate the mockup
      $mockup_result = self::generate_mediamodifier_mockup($device['mediamodifier_id'], $video_url, $mockup_details);
      if (!$mockup_result) {
        return false;
      }

      // Increment usage counter
      srp_increment_mediamodifier_usage();

      // Cache the result
      set_transient($cache_key, $mockup_result, self::$cache_duration);

      return self::render_mockup_result($mockup_result, $options);
    } catch (Exception $e) {
      error_log('SRP Mockup API Error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Get mockup details from Mediamodifier API
   */
  private static function get_mediamodifier_mockup_details($mockup_id)
  {
    $api_url = 'https://api.mediamodifier.com/mockup/nr/' . $mockup_id;

    $response = wp_remote_get($api_url, [
      'headers' => [
        'Accept' => 'application/json',
        'api_key' => self::$mediamodifier_api_key,
      ],
      'timeout' => 30
    ]);

    if (is_wp_error($response)) {
      error_log('SRP Mockup API Error: ' . $response->get_error_message());
      return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
      error_log('SRP Mockup API Error: HTTP ' . $response_code);
      $body = wp_remote_retrieve_body($response);
      error_log('Response: ' . $body);
      return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Handle the correct response format: data.mockup.layers
    if (!$data || !isset($data['success']) || !$data['success']) {
      error_log('SRP Mockup API Error: API returned success=false');
      return false;
    }

    if (!isset($data['mockup']['layers'])) {
      error_log('SRP Mockup API Error: No layers found in mockup data');
      return false;
    }

    // Return the mockup data including layers
    return $data['mockup'];
  }

  /**
   * Generate mockup using Mediamodifier API v2
   */
  private static function generate_mediamodifier_mockup($mockup_id, $video_url, $mockup_details)
  {
    $api_url = 'https://api.mediamodifier.com/v2/mockup/render';

    // Find the image layer (screen content)
    $screen_layer_id = null;
    if (isset($mockup_details['layers']) && !empty($mockup_details['layers'])) {
      foreach ($mockup_details['layers'] as $layer) {
        if (isset($layer['id']) && isset($layer['type']) && $layer['type'] === 'image') {
          $screen_layer_id = $layer['id'];
          break;
        }
      }

      // If no image layer found, try looking for layers with 'image' in the label
      if (!$screen_layer_id) {
        foreach ($mockup_details['layers'] as $layer) {
          if (isset($layer['id']) && (
            stripos($layer['label'] ?? '', 'image') !== false ||
            stripos($layer['label'] ?? '', 'your') !== false ||
            stripos($layer['layer'] ?? '', 'img') !== false
          )) {
            $screen_layer_id = $layer['id'];
            break;
          }
        }
      }
    }

    if (!$screen_layer_id) {
      error_log('SRP Mockup API Error: No suitable image layer found for screen content');
      error_log('Available layers: ' . print_r($mockup_details['layers'], true));
      return false;
    }

    // Get placeholder dimensions for proper cropping
    $placeholder_width = 1280; // Default width
    $placeholder_height = 720; // Default height

    // Try to get actual placeholder dimensions from the layer
    foreach ($mockup_details['layers'] as $layer) {
      if ($layer['id'] === $screen_layer_id && isset($layer['placeholder'])) {
        $placeholder_width = $layer['placeholder']['width'] ?? $placeholder_width;
        $placeholder_height = $layer['placeholder']['height'] ?? $placeholder_height;
        break;
      }
    }

    // Prepare the render request according to Mediamodifier's v2 format
    $render_data = [
      'nr' => intval($mockup_id),
      'layer_inputs' => [
        [
          'id' => $screen_layer_id,
          'data' => $video_url,
          'crop' => [
            'x' => 0,
            'y' => 0,
            'width' => $placeholder_width,
            'height' => $placeholder_height
          ],
          'checked' => null
        ]
      ]
    ];

    $response = wp_remote_post($api_url, [
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'api_key' => self::$mediamodifier_api_key,
      ],
      'body' => json_encode($render_data),
      'timeout' => 60
    ]);

    if (is_wp_error($response)) {
      error_log('SRP Mockup Render Error: ' . $response->get_error_message());
      return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
      error_log('SRP Mockup Render Error: HTTP ' . $response_code);
      $body = wp_remote_retrieve_body($response);
      error_log('Response body: ' . $body);
      return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !isset($data['url'])) {
      error_log('SRP Mockup Render Error: Invalid response format');
      error_log('Response: ' . $body);
      return false;
    }

    return $data;
  }

  /**
   * Render the final mockup result
   */
  private static function render_mockup_result($mockup_result, $options)
  {
    $classes = ['srp-premium-mockup'];
    if (!empty($options['class'])) {
      $classes[] = $options['class'];
    }

    $style = '';
    if (!empty($options['style'])) {
      $style = $options['style'];
    }

    ob_start();
?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" style="<?php echo esc_attr($style); ?>">
      <img src="<?php echo esc_url($mockup_result['url']); ?>"
        alt="<?php _e('Device mockup', 'screen-recorder-pro'); ?>"
        class="srp-mockup-image"
        loading="lazy" />

      <!-- Overlay video for interaction if needed -->
      <?php if (!empty($options['interactive']) && $options['interactive'] === 'true'): ?>
        <div class="srp-interactive-overlay" onclick="this.style.display='none'">
          <video controls autoplay muted>
            <source src="<?php echo esc_url($mockup_result['video_url'] ?? ''); ?>" type="video/mp4">
          </video>
        </div>
      <?php endif; ?>
    </div>

    <style>
      .srp-premium-mockup {
        display: inline-block;
        position: relative;
        max-width: 100%;
        margin: 20px auto;
      }

      .srp-mockup-image {
        max-width: 100%;
        height: auto;
        display: block;
      }

      .srp-interactive-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
      }

      .srp-interactive-overlay video {
        max-width: 80%;
        max-height: 80%;
      }

      @media (max-width: 768px) {
        .srp-premium-mockup {
          transform: scale(0.9);
        }
      }
    </style>
  <?php
    return ob_get_clean();
  }

  /**
   * Fallback CSS-based mockup (your existing implementation)
   */
  private static function render_css_mockup($video_url, $device_type, $options)
  {
    // Use your existing CSS-based implementation as fallback
    $classes = [
      'srp-device-mockup',
      'srp-device-' . str_replace('_', '-', $device_type),
      'srp-css-fallback'
    ];

    if (!empty($options['class'])) {
      $classes[] = $options['class'];
    }

    $style = '';
    if (!empty($options['style'])) {
      $style = $options['style'];
    }

    $video_attrs = [
      'controls' => $options['controls'] ?? 'true',
      'autoplay' => $options['autoplay'] ?? 'false',
      'loop' => $options['loop'] ?? 'false',
      'muted' => $options['muted'] ?? 'false'
    ];

    ob_start();
  ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" style="<?php echo esc_attr($style); ?>">
      <div class="srp-device-frame">
        <div class="srp-device-screen">
          <video class="srp-device-video"
            <?php if ($video_attrs['controls'] === 'true'): ?>controls<?php endif; ?>
            <?php if ($video_attrs['autoplay'] === 'true'): ?>autoplay muted<?php endif; ?>
            <?php if ($video_attrs['loop'] === 'true'): ?>loop<?php endif; ?>
            <?php if ($video_attrs['muted'] === 'true'): ?>muted<?php endif; ?>>
            <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
            <p><?php _e('Your browser does not support the video tag.', 'screen-recorder-pro'); ?></p>
          </video>
        </div>
      </div>
    </div>

    <?php self::render_enhanced_device_styles($device_type); ?>

  <?php
    return ob_get_clean();
  }

  /**
   * Plain video without device frame
   */
  private static function render_plain_video($video_url, $options)
  {
    $classes = ['screen-recording-video'];
    if (!empty($options['class'])) {
      $classes[] = $options['class'];
    }

    $style = '';
    if (!empty($options['style'])) {
      $style = $options['style'];
    }
    if (!empty($options['width']) && $options['width'] !== 'auto') {
      $style .= 'width: ' . $options['width'] . ';';
    }
    if (!empty($options['height']) && $options['height'] !== 'auto') {
      $style .= 'height: ' . $options['height'] . ';';
    }

    $video_attrs = [
      'controls' => $options['controls'] ?? 'true',
      'autoplay' => $options['autoplay'] ?? 'false',
      'loop' => $options['loop'] ?? 'false',
      'muted' => $options['muted'] ?? 'false'
    ];

    ob_start();
  ?>
    <video class="<?php echo esc_attr(implode(' ', $classes)); ?>" style="<?php echo esc_attr($style); ?>"
      <?php if ($video_attrs['controls'] === 'true'): ?>controls<?php endif; ?>
      <?php if ($video_attrs['autoplay'] === 'true'): ?>autoplay muted<?php endif; ?>
      <?php if ($video_attrs['loop'] === 'true'): ?>loop<?php endif; ?>
      <?php if ($video_attrs['muted'] === 'true'): ?>muted<?php endif; ?>>
      <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
      <p><?php _e('Your browser does not support the video tag.', 'screen-recorder-pro'); ?></p>
    </video>
  <?php
    return ob_get_clean();
  }

  /**
   * Enhanced CSS styles with better visual quality
   */
  private static function render_enhanced_device_styles($device_type)
  {
    static $rendered_styles = [];

    if (in_array($device_type, $rendered_styles)) {
      return;
    }

    $rendered_styles[] = $device_type;

  ?>
    <style>
      /* Enhanced Device Mockup Styles with better quality */
      .srp-device-mockup {
        display: inline-block;
        position: relative;
        margin: 20px auto;
        filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.3));
      }

      .srp-device-frame {
        position: relative;
        background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
        border-radius: 25px;
        padding: 20px;
        box-shadow:
          inset 0 1px 0 rgba(255, 255, 255, 0.1),
          inset 0 -1px 0 rgba(0, 0, 0, 0.2),
          0 8px 40px rgba(0, 0, 0, 0.4);
      }

      .srp-device-screen {
        position: relative;
        background: #000;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.5);
      }

      .srp-device-video {
        width: 100%;
        height: 100%;
        display: block;
        border-radius: 15px;
      }

      /* Enhanced iPhone 15 Pro with more realistic details */
      .srp-device-iphone-15-pro .srp-device-frame {
        width: 250px;
        height: 520px;
        background: linear-gradient(145deg, #1d1d1f, #000);
        border-radius: 35px;
        padding: 15px;
        position: relative;
        box-shadow:
          0 0 0 2px #2d2d2d,
          0 20px 60px rgba(0, 0, 0, 0.6);
      }

      /* Dynamic Island */
      .srp-device-iphone-15-pro .srp-device-frame::before {
        content: '';
        position: absolute;
        top: 12px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 25px;
        background: #000;
        border-radius: 0 0 15px 15px;
        z-index: 10;
        box-shadow: inset 0 -2px 4px rgba(255, 255, 255, 0.1);
      }

      /* Camera lens */
      .srp-device-iphone-15-pro .srp-device-frame::after {
        content: '';
        position: absolute;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        width: 8px;
        height: 8px;
        background: radial-gradient(circle, #333, #000);
        border-radius: 50%;
        z-index: 11;
      }

      .srp-device-iphone-15-pro .srp-device-screen {
        width: 220px;
        height: 490px;
        border-radius: 25px;
      }

      /* Enhanced responsive design */
      @media (max-width: 768px) {
        .srp-device-mockup {
          transform: scale(0.8);
          margin: 10px auto;
        }
      }

      @media (max-width: 480px) {
        .srp-device-mockup {
          transform: scale(0.6);
          margin: 5px auto;
        }
      }

      /* Premium badge for enhanced mockups */
      .srp-css-fallback::after {
        content: 'Enhanced';
        position: absolute;
        top: -10px;
        right: -10px;
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        z-index: 100;
      }
    </style>
  <?php
  }

  /**
   * Add settings page for API configuration
   */
  public static function add_settings_page()
  {
    add_submenu_page(
      'screen-recorder',
      __('Device Mockups', 'screen-recorder-pro'),
      __('Device Mockups', 'screen-recorder-pro'),
      'manage_options',
      'screen-recorder-mockups',
      [__CLASS__, 'render_settings_page']
    );
  }

  public static function render_settings_page()
  {
    if (isset($_POST['save_mockup_settings'])) {
      $settings = [
        'mediamodifier_api_key' => sanitize_text_field($_POST['mediamodifier_api_key'] ?? ''),
        'mockup_quality' => sanitize_text_field($_POST['mockup_quality'] ?? 'high'),
        'cache_duration' => intval($_POST['cache_duration'] ?? 3600)
      ];

      update_option('srp_device_mockup_settings', $settings);
      echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }

    $settings = get_option('srp_device_mockup_settings', []);
  ?>
    <div class="wrap">
      <h1><?php _e('Device Mockup Settings', 'screen-recorder-pro'); ?></h1>

      <form method="post" action="">
        <table class="form-table">
          <tr>
            <th scope="row"><?php _e('Mediamodifier API Key', 'screen-recorder-pro'); ?></th>
            <td>
              <input type="text" name="mediamodifier_api_key"
                value="<?php echo esc_attr($settings['mediamodifier_api_key'] ?? ''); ?>"
                class="regular-text" />
              <p class="description">
                <?php _e('Get your API key from', 'screen-recorder-pro'); ?>
                <a href="https://mediamodifier.com/mockup-api" target="_blank">Mediamodifier</a>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php _e('Mockup Quality', 'screen-recorder-pro'); ?></th>
            <td>
              <select name="mockup_quality">
                <option value="standard" <?php selected($settings['mockup_quality'] ?? 'high', 'standard'); ?>>
                  <?php _e('Standard (CSS-based)', 'screen-recorder-pro'); ?>
                </option>
                <option value="high" <?php selected($settings['mockup_quality'] ?? 'high', 'high'); ?>>
                  <?php _e('High (API-based)', 'screen-recorder-pro'); ?>
                </option>
              </select>
            </td>
          </tr>
        </table>

        <?php submit_button(__('Save Settings', 'screen-recorder-pro'), 'primary', 'save_mockup_settings'); ?>
      </form>
    </div>
<?php
  }
}

// Initialize the enhanced mockups
add_action('init', ['SRP_Device_Mockups', 'init']);
add_action('admin_menu', ['SRP_Device_Mockups', 'add_settings_page'], 20);
