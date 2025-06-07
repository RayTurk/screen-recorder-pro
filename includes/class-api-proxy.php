<?php

/**
 * API Proxy Class for Netlify Functions Integration
 */

if (!defined('ABSPATH')) {
  exit;
}

class SRP_API_Proxy
{

  private $api_endpoint;
  private $plugin_version;

  public function __construct()
  {
    // Your live Netlify Functions API
    $this->api_endpoint = 'https://screenrecorderpro-api.netlify.app/.netlify/functions/';
    $this->plugin_version = defined('SRP_VERSION') ? SRP_VERSION : '1.0.0';
  }

  /**
   * Create recording via Netlify Functions
   */
  public function create_recording($url, $options = [])
  {

    // Get user's license info
    $license_data = $this->get_user_license_data();

    if (!$license_data['can_create']) {
      return new WP_Error('license_limit', $license_data['message']);
    }

    // Prepare request data for Netlify API
    $request_data = [
      'url' => $url,
      'options' => $options,
      'license_key' => $license_data['license_key'],
      'site_url' => get_site_url(),
      'user_id' => get_current_user_id(),
      'plugin_version' => $this->plugin_version
    ];

    error_log('SRP: Making API request to: ' . $this->api_endpoint . 'create-recording');

    // Make request to Netlify function
    $response = wp_remote_post($this->api_endpoint . 'create-recording', [
      'headers' => [
        'Content-Type' => 'application/json',
        'User-Agent' => 'ScreenRecorderPro-WordPress/' . $this->plugin_version,
        'X-Plugin-License' => $license_data['license_key']
      ],
      'body' => wp_json_encode($request_data),
      'timeout' => 120, // 2 minutes for video creation
      'sslverify' => true
    ]);

    if (is_wp_error($response)) {
      error_log('SRP: API request failed: ' . $response->get_error_message());
      return new WP_Error('api_error', 'Connection failed: ' . $response->get_error_message());
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    error_log('SRP: API response code: ' . $response_code);

    $data = json_decode($response_body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      error_log('SRP: Invalid JSON response');
      return new WP_Error('api_error', 'Invalid API response');
    }

    // Handle different response codes
    switch ($response_code) {
      case 200:
        // Success - video data is in base64 format
        if (isset($data['video_data'])) {
          return $this->handle_base64_video($data, $url, $options);
        } else {
          return new WP_Error('api_error', 'No video data received');
        }

      case 400:
        return new WP_Error('validation_error', $data['message'] ?? 'Bad request');

      case 402:
        return new WP_Error('usage_limit', $data['message'] ?? 'Usage limit reached');

      case 403:
        return new WP_Error('license_invalid', $data['message'] ?? 'Invalid license');

      case 500:
        return new WP_Error('server_error', $data['message'] ?? 'Server error occurred');

      default:
        return new WP_Error('api_error', 'Unknown API error (HTTP ' . $response_code . ')');
    }
  }

  /**
   * Handle base64 video data from Netlify Functions
   */
  private function handle_base64_video($api_response, $source_url, $options)
  {

    error_log('SRP: Processing base64 video data...');

    // Decode the base64 video data
    $video_data = base64_decode($api_response['video_data']);

    if (!$video_data) {
      error_log('SRP: Failed to decode base64 video data');
      return new WP_Error('decode_error', 'Failed to decode video data');
    }

    error_log('SRP: Decoded video size: ' . strlen($video_data) . ' bytes');

    // Save to WordPress media library
    $save_result = $this->save_video_to_wordpress($video_data, $source_url, $options);

    if (is_wp_error($save_result)) {
      return $save_result;
    }

    return [
      'attachment_id' => $save_result['attachment_id'],
      'file_url' => $save_result['file_url'],
      'file_size' => $save_result['file_size'],
      'duration' => $api_response['duration'] ?? 5
    ];
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

    // Save file from binary data
    if (file_put_contents($file_path, $video_data) === false) {
      error_log('SRP: Failed to save video file to: ' . $file_path);
      return new WP_Error('file_save_error', 'Failed to save video file');
    }

    error_log('SRP: Video saved to: ' . $file_path);

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

    error_log('SRP: Media attachment created with ID: ' . $attachment_id);

    return [
      'attachment_id' => $attachment_id,
      'file_path' => $file_path,
      'file_url' => $file_url,
      'file_size' => filesize($file_path)
    ];
  }

  /**
   * Get user's license data
   */
  private function get_user_license_data()
  {

    // Default for free users
    $license_data = [
      'license_key' => 'free',
      'plan' => 'free',
      'can_create' => false,
      'usage_limit' => 1,
      'current_usage' => 0,
      'message' => 'Free trial used. Upgrade to continue.'
    ];

    // Check if Freemius is available and user is registered
    if (!function_exists('srp_fs') || !srp_fs()->is_registered()) {
      return $this->check_free_usage($license_data);
    }

    // Get Freemius license key
    $license_key = srp_fs()->get_site()->secret_key ?? 'free';
    $license_data['license_key'] = $license_key;

    // Free users
    if (!srp_fs()->is_paying()) {
      return $this->check_free_usage($license_data);
    }

    // Paying users
    $plan = srp_fs()->get_plan();
    if ($plan) {
      $license_data['plan'] = strtolower($plan->name);

      switch (strtolower($plan->name)) {
        case 'starter':
          $license_data['usage_limit'] = 50;
          break;
        case 'pro':
          $license_data['usage_limit'] = 200;
          break;
        case 'agency':
          $license_data['usage_limit'] = 500;
          break;
      }

      // For paying users, let the API handle usage limits for now
      $license_data['can_create'] = true;
      $license_data['message'] = 'Active subscription';
    }

    return $license_data;
  }

  /**
   * Check free user usage (1 total recording)
   */
  private function check_free_usage($license_data)
  {
    if (class_exists('SRP_Recordings_Manager')) {
      $recordings_manager = new SRP_Recordings_Manager();
      $total_recordings = $recordings_manager->get_count_by_status('completed');

      $license_data['current_usage'] = $total_recordings;
      $license_data['can_create'] = $total_recordings < 1;

      if ($total_recordings >= 1) {
        $license_data['message'] = 'Free recording used. Upgrade to create unlimited recordings.';
      } else {
        $license_data['message'] = 'Free trial available';
        $license_data['can_create'] = true;
      }
    }

    return $license_data;
  }

  /**
   * Get user's current status/usage
   */
  public function get_user_status()
  {
    return $this->get_user_license_data();
  }

  /**
   * Test connection to Netlify Functions
   */
  public function test_connection()
  {
    $response = wp_remote_get($this->api_endpoint . 'validate-license', [
      'timeout' => 30,
      'sslverify' => true
    ]);

    if (is_wp_error($response)) {
      error_log('SRP: Connection test failed: ' . $response->get_error_message());
      return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    error_log('SRP: Connection test response code: ' . $response_code);

    return $response_code === 200;
  }
}
