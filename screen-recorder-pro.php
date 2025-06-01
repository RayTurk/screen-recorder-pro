<?php

/**
 * Plugin Name: Screen Recorder Pro
 * Plugin URI: https://yoursite.com/screen-recorder-pro
 * Description: Record animated screenshots and videos of your WordPress pages and posts
 * Version: 1.0.0
 * Author: Raymond Turk
 * License: GPL v2 or later
 * Text Domain: screen-recorder-pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Define plugin constants
define('SRP_VERSION', '1.0.0');
define('SRP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SRP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SRP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load plugin classes
$includes = [
  'includes/config-secure.php',
  'includes/class-screenshotone-api.php',
  'includes/class-recordings-manager.php',
  'includes/class-admin-ui.php',
  'includes/class-shortcode-handler.php',
  'includes/class-device-mockups.php'
];

foreach ($includes as $include) {
  $path = SRP_PLUGIN_DIR . $include;
  if (file_exists($path)) {
    include_once $path;
  }
}

// Initialize Freemius (optional)
if (!function_exists('srp_fs')) {
  function srp_fs()
  {
    global $srp_fs;
    if (!isset($srp_fs)) {
      $freemius_file = SRP_PLUGIN_DIR . 'freemius/start.php';
      if (file_exists($freemius_file)) {
        require_once $freemius_file;
        $srp_fs = fs_dynamic_init([
          'id' => 'YOUR_FREEMIUS_ID',
          'slug' => 'screen-recorder-pro',
          'type' => 'plugin',
          'public_key' => 'YOUR_PUBLIC_KEY',
          'is_premium' => true,
          'has_addons' => false,
          'has_paid_plans' => true,
          'menu' => [
            'slug' => 'screen-recorder',
            'parent' => ['slug' => 'tools.php'],
          ],
        ]);
      } else {
        // Mock object for testing
        class SRP_Freemius_Mock
        {
          public function is_plan($plan)
          {
            return false;
          }
          public function get_upgrade_url()
          {
            return admin_url('admin.php?page=screen-recorder-settings');
          }
        }
        $srp_fs = new SRP_Freemius_Mock();
      }
    }
    return $srp_fs;
  }
}

// Main plugin class
class ScreenRecorderPro
{
  private static $instance = null;
  private $api;
  private $recordings_manager;
  private $admin_ui;
  private $shortcode_handler;

  public static function get_instance()
  {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function __construct()
  {
    $this->init();
    $this->hooks();
  }

  private function init()
  {
    $this->api = new SRP_ScreenshotOne_API();
    $this->recordings_manager = new SRP_Recordings_Manager();
    $this->admin_ui = new SRP_Admin_UI();
    $this->shortcode_handler = new SRP_Shortcode_Handler();
  }

  private function hooks()
  {
    // Activation/Deactivation hooks
    register_activation_hook(__FILE__, [$this, 'activate']);
    register_deactivation_hook(__FILE__, [$this, 'deactivate']);

    // Admin hooks
    add_action('admin_menu', [$this->admin_ui, 'add_menu_pages']);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

    // AJAX handlers
    add_action('wp_ajax_srp_create_recording', [$this, 'ajax_create_recording']);
    add_action('wp_ajax_srp_check_recording_status', [$this, 'ajax_check_status']);
    add_action('wp_ajax_srp_delete_recording', [$this, 'ajax_delete_recording']);
    add_action('wp_ajax_srp_get_recording_count', [$this, 'ajax_get_recording_count']);

    // Shortcodes
    add_shortcode('screen_recording', [$this->shortcode_handler, 'render']);

    // Add recording column to posts/pages
    add_filter('manage_post_posts_columns', [$this, 'add_recording_column']);
    add_filter('manage_page_posts_columns', [$this, 'add_recording_column']);
    add_action('manage_posts_custom_column', [$this, 'render_recording_column'], 10, 2);
    add_action('manage_pages_custom_column', [$this, 'render_recording_column'], 10, 2);
  }

  public function activate()
  {
    $this->recordings_manager->create_table();

    add_option('srp_settings', [
      'api_key' => '',
      'default_format' => 'mp4',
      'default_duration' => 15,
      'default_scenario' => 'scroll',
      'watermark_enabled' => true,
      'watermark_text' => 'Created with Screen Recorder Pro'
    ]);

    if (!wp_next_scheduled('srp_cleanup_temp_files')) {
      wp_schedule_event(time(), 'daily', 'srp_cleanup_temp_files');
    }
  }

  public function deactivate()
  {
    wp_clear_scheduled_hook('srp_cleanup_temp_files');
  }

  public function enqueue_admin_assets($hook)
  {
    if (strpos($hook, 'screen-recorder') === false && !in_array($hook, ['post.php', 'post-new.php'])) {
      return;
    }

    wp_enqueue_style('srp-admin', SRP_PLUGIN_URL . 'assets/css/admin.css', [], SRP_VERSION);
    wp_enqueue_script('srp-admin', SRP_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], SRP_VERSION, true);

    wp_localize_script('srp-admin', 'srp_ajax', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('srp_ajax_nonce'),
      'strings' => [
        'recording' => __('Recording...', 'screen-recorder-pro'),
        'processing' => __('Processing...', 'screen-recorder-pro'),
        'error' => __('An error occurred', 'screen-recorder-pro'),
        'confirm_delete' => __('Are you sure you want to delete this recording?', 'screen-recorder-pro')
      ]
    ]);
  }

  /**
   * Get combined device/viewport options with updated responsive frames
   */
  public static function get_device_viewport_options()
  {
    return [
      // Device frame options
      'mobile_iphone_xr' => [
        'name' => __('Mobile (iPhone XR)', 'screen-recorder-pro'),
        'type' => 'mobile',
        'width' => 414,
        'height' => 896,
        'device_frame' => true
      ],
      'tablet_ipad_air_portrait' => [
        'name' => __('Tablet (iPad Air 2020) Portrait', 'screen-recorder-pro'),
        'type' => 'tablet',
        'width' => 820,
        'height' => 1180,
        'device_frame' => true
      ],
      'tablet_ipad_air_landscape' => [
        'name' => __('Tablet (iPad Air 2020) Landscape', 'screen-recorder-pro'),
        'type' => 'tablet',
        'width' => 1180,
        'height' => 820,
        'device_frame' => true
      ],
      'laptop_macbook_pro' => [
        'name' => __('Laptop (MacBook Pro)', 'screen-recorder-pro'),
        'type' => 'laptop',
        'width' => 1440,
        'height' => 900,
        'device_frame' => true
      ],
      'desktop_imac_pro' => [
        'name' => __('Desktop (iMac Pro)', 'screen-recorder-pro'),
        'type' => 'desktop',
        'width' => 1920,
        'height' => 1080,
        'device_frame' => true
      ],

      // Plain viewport options (no frames)
      'viewport_1920' => [
        'name' => __('Desktop - Full HD (1920×1080)', 'screen-recorder-pro'),
        'type' => 'desktop',
        'width' => 1920,
        'height' => 1080,
        'device_frame' => false
      ],
      'viewport_1440' => [
        'name' => __('Desktop - Standard (1440×900)', 'screen-recorder-pro'),
        'type' => 'desktop',
        'width' => 1440,
        'height' => 900,
        'device_frame' => false
      ],
      'viewport_1280' => [
        'name' => __('Desktop - Compact (1280×720)', 'screen-recorder-pro'),
        'type' => 'desktop',
        'width' => 1280,
        'height' => 720,
        'device_frame' => false
      ]
    ];
  }

  /**
   * AJAX handler to create recordings with usage limit check
   */
  public function ajax_create_recording()
  {
    check_ajax_referer('srp_ajax_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
      wp_send_json_error(['message' => 'Unauthorized']);
    }

    // Check usage limits FIRST
    if (!$this->check_usage_limits()) {
      $current_usage = $this->get_current_usage();
      $limit = srp_is_premium_user() ? SRP_PREMIUM_MONTHLY_LIMIT : SRP_FREE_MONTHLY_LIMIT;

      wp_send_json_error([
        'message' => sprintf(
          __('You have reached your monthly recording limit (%d/%d). Upgrade to premium for more recordings.', 'screen-recorder-pro'),
          $current_usage,
          $limit
        )
      ]);
    }

    $post_id = intval($_POST['post_id'] ?? 0);

    // Get URL from direct input or from post_id
    $url = '';
    if (!empty($_POST['url'])) {
      $url = esc_url_raw($_POST['url']);
    } elseif ($post_id) {
      $url = get_permalink($post_id);
    }

    if (empty($url)) {
      wp_send_json_error(['message' => 'No URL provided']);
    }

    // Get device/viewport settings
    $device_key = sanitize_text_field($_POST['device'] ?? 'mobile_iphone_xr');
    $device_options = self::get_device_viewport_options();
    $device_config = $device_options[$device_key] ?? $device_options['mobile_iphone_xr'];

    // Check if device frame is enabled
    $show_device_frame = isset($_POST['show_device_frame']) ? (bool)$_POST['show_device_frame'] : false;

    $options = [
      'format' => sanitize_text_field($_POST['format'] ?? 'mp4'),
      'duration' => intval($_POST['duration'] ?? 5),
      'scenario' => sanitize_text_field($_POST['scenario'] ?? 'scroll'),
      'viewport_width' => $device_config['width'],
      'viewport_height' => $device_config['height'],
      'device_type' => $device_config['type'],
      'device_key' => $device_key,
      'show_device_frame' => $show_device_frame,
      'post_id' => $post_id
    ];

    $api_key = srp_get_api_key();

    if (empty($api_key)) {
      wp_send_json_error(['message' => __('Plugin not properly configured. Please contact support.', 'screen-recorder-pro')]);
    }

    // Create the video
    $result = $this->create_and_download_video($url, $api_key, $options);

    if (is_wp_error($result)) {
      wp_send_json_error(['message' => $result->get_error_message()]);
    }

    // Save to database
    $recording_id = $this->recordings_manager->create([
      'post_id' => $post_id,
      'url' => $url,
      'status' => 'completed',
      'options' => $options,
      'attachment_id' => $result['attachment_id'],
      'video_url' => $result['file_url']
    ]);

    if (!$recording_id) {
      wp_send_json_error(['message' => __('Failed to save recording.', 'screen-recorder-pro')]);
    }

    wp_send_json_success([
      'recording_id' => $recording_id,
      'attachment_id' => $result['attachment_id'],
      'video_url' => $result['file_url'],
      'message' => __('Recording created successfully!', 'screen-recorder-pro')
    ]);
  }

  /**
   * Create and download scrolling video
   */
  private function create_and_download_video($target_url, $access_key, $options = [])
  {
    // Build API parameters
    $query = [
      'access_key' => $access_key,
      'url' => $target_url,
      'scenario' => 'scroll',
      'format' => $options['format'] ?? 'mp4',
      'duration' => $options['duration'] ?? '5',
      'scroll_duration' => '1500',
      'scroll_start_immediately' => 'true',
      'scroll_complete' => 'true',
      'viewport_width' => $options['viewport_width'] ?? '414',
      'viewport_height' => $options['viewport_height'] ?? '896',
      'viewport_mobile' => $options['device_type'] === 'mobile' ? 'true' : 'false',
      'block_ads' => 'true',
      'block_banners_by_heuristics' => 'true',
      'block_chats' => 'true',
      'block_cookie_banners' => 'true',
      'timeout' => '60',
      'reduced_motion' => 'false'
    ];

    $api_url = 'https://api.screenshotone.com/animate?' . http_build_query($query);

    $response = wp_remote_get($api_url, [
      'timeout' => 120,
      'headers' => [
        'User-Agent' => 'WordPress-ScreenRecorderPro/' . SRP_VERSION
      ]
    ]);

    if (is_wp_error($response)) {
      $error_message = 'WordPress HTTP Error: ' . $response->get_error_message();
      return new WP_Error('api_error', $error_message);
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
      $response_body = wp_remote_retrieve_body($response);
      return new WP_Error('api_error', 'ScreenshotOne API error (HTTP ' . $response_code . ')');
    }

    $video_data = wp_remote_retrieve_body($response);

    if (empty($video_data)) {
      return new WP_Error('api_error', 'ScreenshotOne API returned empty response');
    }

    return $this->save_video_to_wordpress($video_data, $target_url, $options);
  }

  /**
   * Save video to WordPress media library
   */
  private function save_video_to_wordpress($video_data, $source_url, $options)
  {
    // Generate filename
    $post = get_post($options['post_id'] ?? 0);
    $post_title = $post ? sanitize_title($post->post_title) : 'page';
    $device_suffix = $options['device_key'] ?? 'mobile';
    $filename = $post_title . '_' . $device_suffix . '_' . date('Y-m-d_H-i-s') . '.mp4';

    // Get upload directory
    $upload_dir = wp_upload_dir();
    if ($upload_dir['error']) {
      return new WP_Error('upload_dir_error', $upload_dir['error']);
    }

    // Create directory
    $video_dir = $upload_dir['basedir'] . '/screen-recordings/';
    if (!file_exists($video_dir)) {
      wp_mkdir_p($video_dir);
    }

    $file_path = $video_dir . $filename;

    // Save file
    if (file_put_contents($file_path, $video_data) === false) {
      return new WP_Error('file_save_error', 'Failed to save video file');
    }

    // Add to media library
    $file_url = $upload_dir['baseurl'] . '/screen-recordings/' . $filename;

    $attachment = [
      'guid' => $file_url,
      'post_mime_type' => 'video/mp4',
      'post_title' => sanitize_file_name(basename($filename, '.mp4')),
      'post_content' => 'Screen recording of ' . $source_url,
      'post_status' => 'inherit'
    ];

    $attachment_id = wp_insert_attachment($attachment, $file_path, $options['post_id'] ?? 0);

    if (is_wp_error($attachment_id)) {
      return $attachment_id;
    }

    // Generate metadata
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
    wp_update_attachment_metadata($attachment_id, $attachment_data);

    return [
      'attachment_id' => $attachment_id,
      'file_path' => $file_path,
      'file_url' => $file_url,
      'file_size' => filesize($file_path)
    ];
  }

  public function ajax_check_status()
  {
    check_ajax_referer('srp_ajax_nonce', 'nonce');
    $recording_id = intval($_POST['recording_id']);
    $recording = $this->recordings_manager->get($recording_id);

    if (!$recording) {
      wp_send_json_error(['message' => 'Recording not found']);
    }

    wp_send_json_success([
      'status' => $recording->status,
      'attachment_id' => $recording->attachment_id ?? 0,
      'video_url' => $recording->attachment_id ? wp_get_attachment_url($recording->attachment_id) : ''
    ]);
  }

  public function ajax_delete_recording()
  {
    check_ajax_referer('srp_ajax_nonce', 'nonce');

    if (!current_user_can('delete_posts')) {
      wp_die('Unauthorized');
    }

    $recording_id = intval($_POST['recording_id']);

    if ($this->recordings_manager->delete($recording_id)) {
      wp_send_json_success(['message' => __('Recording deleted', 'screen-recorder-pro')]);
    } else {
      wp_send_json_error(['message' => __('Failed to delete recording', 'screen-recorder-pro')]);
    }
  }

  /**
   * Check usage limits with free tier limit of 10
   */
  private function check_usage_limits()
  {
    $is_premium = srp_is_premium_user();
    $monthly_limit = $is_premium ? SRP_PREMIUM_MONTHLY_LIMIT : 10; // Free tier: 10 recordings
    $current_month = date('Y-m');
    $usage_count = $this->recordings_manager->get_monthly_count($current_month);
    return $usage_count < $monthly_limit;
  }

  /**
   * Get current month usage
   */
  private function get_current_usage()
  {
    $current_month = date('Y-m');
    return $this->recordings_manager->get_monthly_count($current_month);
  }

  /**
   * AJAX handler to get recording count with usage info
   */
  public function ajax_get_recording_count()
  {
    check_ajax_referer('srp_ajax_nonce', 'nonce');

    $total_recordings = $this->recordings_manager->get_count_by_status('completed');
    $current_usage = $this->get_current_usage();
    $is_premium = srp_is_premium_user();
    $monthly_limit = $is_premium ? SRP_PREMIUM_MONTHLY_LIMIT : 10;

    wp_send_json_success([
      'total_recordings' => $total_recordings,
      'current_usage' => $current_usage,
      'monthly_limit' => $monthly_limit,
      'is_premium' => $is_premium,
      'usage_display' => $current_usage . '/' . $monthly_limit
    ]);
  }

  public function add_recording_column($columns)
  {
    $columns['screen_recording'] = __('Recording', 'screen-recorder-pro');
    return $columns;
  }

  public function render_recording_column($column, $post_id)
  {
    if ($column === 'screen_recording') {
      $recording = $this->recordings_manager->get_by_post_id($post_id);
      if ($recording && $recording->status === 'completed') {
        echo '<span class="dashicons dashicons-video-alt3" style="color: #46b450;" title="Recording available"></span>';
      } else {
        echo '<span class="dashicons dashicons-video-alt3" style="color: #ccc;" title="No recording"></span>';
      }
    }
  }
}

// Initialize plugin
ScreenRecorderPro::get_instance();
