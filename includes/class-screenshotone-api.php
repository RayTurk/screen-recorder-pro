<?php

/**
 * ScreenshotOne API Integration
 * This class is now simplified and only used for API validation and utility functions
 */

if (!defined('ABSPATH')) {
  exit;
}

class SRP_ScreenshotOne_API
{
  private $api_base = 'https://api.screenshotone.com';
  private $api_key;

  public function __construct()
  {
    $settings = get_option('srp_settings', []);
    $this->api_key = $settings['api_key'] ?? '';
  }

  /**
   * Validate API key by making a simple test request
   */
  public function validate_api_key($api_key = null)
  {
    $test_key = $api_key ?: $this->api_key;

    if (empty($test_key)) {
      return false;
    }

    // Test with a small animate request
    $test_url = $this->api_base . '/animate?' . http_build_query([
      'url' => 'https://example.com',
      'access_key' => $test_key,
      'format' => 'mp4',
      'duration' => '1',
      'viewport_width' => 400,
      'viewport_height' => 300
    ]);

    $context = stream_context_create([
      'http' => [
        'method' => 'GET',
        'timeout' => 30,
        'header' => ['User-Agent: WordPress-ScreenRecorderPro-Validation/1.0']
      ]
    ]);

    $response = file_get_contents($test_url, false, $context);

    if ($response === false) {
      error_log('SRP API: Validation failed for key: ' . substr($test_key, 0, 8) . '...');
      return false;
    }

    error_log('SRP API: Validation successful for key: ' . substr($test_key, 0, 8) . '...');
    return true;
  }

  /**
   * Get usage stats (placeholder for future implementation)
   */
  public function get_usage_stats()
  {
    // ScreenshotOne doesn't provide usage stats via API
    // We track this locally in our plugin
    return [
      'used' => 0,
      'limit' => 0,
      'remaining' => 0
    ];
  }

  /**
   * Get watermark CSS for free version
   */
  public function get_watermark_css($text = '')
  {
    if (empty($text)) {
      $text = 'Created with Screen Recorder Pro';
    }

    $css = '
      body::after {
        content: "' . esc_attr($text) . '";
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        font-family: Arial, sans-serif;
        font-size: 14px;
        z-index: 999999;
        pointer-events: none;
      }
    ';

    return base64_encode($css);
  }

  /**
   * Build animate URL (utility function)
   */
  public function build_animate_url($url, $options = [])
  {
    if (empty($this->api_key)) {
      return new WP_Error('no_api_key', 'API key not configured');
    }

    $params = array_merge([
      'access_key' => $this->api_key,
      'url' => $url,
      'scenario' => 'scroll',
      'format' => 'mp4',
      'duration' => 5,
      'viewport_width' => 1440,
      'viewport_height' => 900,
      'block_cookie_banners' => 'true',
      'block_trackers' => 'true',
      'scroll_duration' => '1500',
      'scroll_start_immediately' => 'true',
      'scroll_complete' => 'true'
    ], $options);

    return $this->api_base . '/animate?' . http_build_query($params);
  }
}
