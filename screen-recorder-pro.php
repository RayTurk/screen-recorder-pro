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

// Define plugin constants first
define('SRP_VERSION', '1.0.0');
define('SRP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SRP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SRP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load all classes immediately - don't wait for Freemius
function srp_load_classes()
{
  static $classes_loaded = false;

  if ($classes_loaded) {
    return;
  }

  error_log('SRP: Loading classes...');

  $files_to_load = [
    'includes/config-secure.php',
    'includes/class-recordings-manager.php',
    'includes/class-screenshotone-api.php',
    'includes/class-admin-ui.php',
    'includes/class-shortcode-handler.php',
    'includes/class-device-mockups.php'
  ];

  foreach ($files_to_load as $file) {
    $file_path = SRP_PLUGIN_DIR . $file;
    if (file_exists($file_path)) {
      require_once $file_path;
      error_log("SRP: ✅ Loaded $file");
    } else {
      error_log("SRP: ❌ Missing $file");
    }
  }

  $classes_loaded = true;

  // Verify classes loaded
  $required_classes = ['SRP_Admin_UI', 'SRP_Recordings_Manager', 'ScreenRecorderPro'];
  foreach ($required_classes as $class) {
    if (class_exists($class)) {
      error_log("SRP: ✅ Class $class available");
    } else {
      error_log("SRP: ❌ Class $class missing");
    }
  }
}

// Load classes immediately
srp_load_classes();

// Initialize Freemius after classes are loaded
if (!function_exists('srp_fs')) {
  function srp_fs()
  {
    global $srp_fs;

    if (!isset($srp_fs)) {
      // Include Freemius SDK
      $freemius_path = dirname(__FILE__) . '/vendor/freemius/start.php';
      if (file_exists($freemius_path)) {
        require_once $freemius_path;

        $srp_fs = fs_dynamic_init([
          'id'                  => '19322',
          'slug'                => 'screen-recorder-pro',
          'type'                => 'plugin',
          'public_key'          => 'pk_62c45b5eac4006690c52646deee33',
          'is_premium'          => false,
          'premium_suffix'      => 'Pro',
          'has_premium_version' => true,
          'has_addons'          => false,
          'has_paid_plans'      => true,
          'navigation'          => 'menu',
          'menu'                => [
            'slug'       => 'screen-recorder',
            'first-path' => 'admin.php?page=screen-recorder',
            'support'    => false,
          ],
          'anonymous_mode'      => false,
          'is_live'             => true,
        ]);

        error_log('SRP: ✅ Freemius initialized');
      } else {
        error_log('SRP: ❌ Freemius SDK not found');
      }
    }

    return $srp_fs;
  }

  // Init Freemius
  srp_fs();
  do_action('srp_fs_loaded');
}

// Initialize plugin immediately
function srp_init_plugin()
{
  static $plugin_initialized = false;

  if ($plugin_initialized) {
    return;
  }

  error_log('SRP: Initializing plugin...');

  // Ensure classes are loaded
  srp_load_classes();

  // Initialize main plugin class
  if (class_exists('ScreenRecorderPro')) {
    ScreenRecorderPro::get_instance();
    error_log('SRP: ✅ ScreenRecorderPro initialized');
  } else {
    error_log('SRP: ❌ ScreenRecorderPro class not found');
  }

  $plugin_initialized = true;
}

// Initialize on multiple hooks to ensure it runs
add_action('plugins_loaded', 'srp_init_plugin', 1);
add_action('init', 'srp_init_plugin', 1);
add_action('admin_init', 'srp_init_plugin', 1);

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
    // Initialize components if classes exist
    if (class_exists('SRP_ScreenshotOne_API')) {
      $this->api = new SRP_ScreenshotOne_API();
    }

    if (class_exists('SRP_Recordings_Manager')) {
      $this->recordings_manager = new SRP_Recordings_Manager();
    }

    if (class_exists('SRP_Admin_UI')) {
      $this->admin_ui = new SRP_Admin_UI();
      error_log('SRP: ✅ Admin UI component initialized');
    } else {
      error_log('SRP: ❌ Admin UI component failed to initialize');
    }

    if (class_exists('SRP_Shortcode_Handler')) {
      $this->shortcode_handler = new SRP_Shortcode_Handler();
    }
  }

  private function hooks()
  {
    // Activation/Deactivation hooks
    register_activation_hook(__FILE__, [$this, 'activate']);
    register_deactivation_hook(__FILE__, [$this, 'deactivate']);

    // Admin menu - add with high priority
    if ($this->admin_ui) {
      add_action('admin_menu', [$this->admin_ui, 'add_menu_pages'], 5);
      error_log('SRP: ✅ Admin menu hook added');
    } else {
      error_log('SRP: ❌ Admin menu hook not added - no admin_ui');
    }

    add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

    // AJAX handlers
    add_action('wp_ajax_srp_create_recording', [$this, 'ajax_create_recording']);
    add_action('wp_ajax_srp_check_recording_status', [$this, 'ajax_check_status']);
    add_action('wp_ajax_srp_delete_recording', [$this, 'ajax_delete_recording']);
    add_action('wp_ajax_srp_get_recording_count', [$this, 'ajax_get_recording_count']);

    // Shortcodes
    if ($this->shortcode_handler) {
      add_shortcode('screen_recording', [$this->shortcode_handler, 'render']);
    }

    // Add recording column to posts/pages
    add_filter('manage_post_posts_columns', [$this, 'add_recording_column']);
    add_filter('manage_page_posts_columns', [$this, 'add_recording_column']);
    add_action('manage_posts_custom_column', [$this, 'render_recording_column'], 10, 2);
    add_action('manage_pages_custom_column', [$this, 'render_recording_column'], 10, 2);
  }

  public function activate()
  {
    if ($this->recordings_manager) {
      $this->recordings_manager->create_table();
    }

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
   * Get combined device/viewport options
   */
  public static function get_device_viewport_options()
  {
    return [
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
   * AJAX handler to create recordings with Freemius usage limit check
   */
  public function ajax_create_recording()
  {
    check_ajax_referer('srp_ajax_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
      wp_send_json_error(['message' => 'Unauthorized']);
    }

    // Check usage limits with Freemius integration
    if (!$this->check_usage_limits()) {
      $current_usage = $this->get_current_usage();
      $limit = $this->get_usage_limit();

      if (function_exists('srp_fs') && srp_fs()->is_free_plan()) {
        wp_send_json_error([
          'message' => sprintf(
            __('You have reached your monthly recording limit (%d/%d). <a href="%s" target="_blank">Upgrade to Pro</a> for unlimited recordings.', 'screen-recorder-pro'),
            $current_usage,
            $limit,
            function_exists('srp_fs') ? srp_fs()->get_upgrade_url() : '#'
          )
        ]);
      } else {
        wp_send_json_error([
          'message' => sprintf(
            __('You have reached your monthly recording limit (%d/%d).', 'screen-recorder-pro'),
            $current_usage,
            $limit
          )
        ]);
      }
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
   * Check usage limits with Freemius integration
   */
  private function check_usage_limits()
  {
    $monthly_limit = $this->get_usage_limit();
    $current_month = date('Y-m');
    $usage_count = $this->recordings_manager->get_monthly_count($current_month);

    return $usage_count < $monthly_limit;
  }

  /**
   * Get usage limit based on Freemius plan
   */
  private function get_usage_limit()
  {
    // If Freemius is available and user has premium, unlimited
    if (function_exists('srp_fs') && srp_fs()->can_use_premium_code()) {
      return 999999; // Unlimited for pro users
    }

    // Free plan gets 10 recordings per month
    return 10;
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
   * AJAX handler to get recording count with Freemius usage info
   */
  public function ajax_get_recording_count()
  {
    check_ajax_referer('srp_ajax_nonce', 'nonce');

    $total_recordings = $this->recordings_manager->get_count_by_status('completed');
    $current_usage = $this->get_current_usage();
    $monthly_limit = $this->get_usage_limit();
    $is_premium = function_exists('srp_fs') ? srp_fs()->can_use_premium_code() : false;

    wp_send_json_success([
      'total_recordings' => $total_recordings,
      'current_usage' => $current_usage,
      'monthly_limit' => $monthly_limit,
      'is_premium' => $is_premium,
      'usage_display' => $is_premium ? 'Unlimited' : $current_usage . '/' . $monthly_limit,
      'plan_name' => $is_premium ? 'Pro' : 'Free'
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
