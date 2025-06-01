<?php

/**
 * Secure API Configuration - Create this as includes/config-secure.php
 * This file contains the default API keys for the plugin
 */

if (!defined('ABSPATH')) {
  exit;
}

// Default API key for the plugin (your ScreenshotOne key)
define('SRP_DEFAULT_API_KEY', 'V3mF4QholiL8Qw'); // Replace with your actual key

// Mediamodifier API key for device mockups
define('SRP_MEDIAMODIFIER_API_KEY', '11aeed3b-9a1f-434f-ba06-c578de93ebae');

// Premium features API endpoint (for future use)
define('SRP_PREMIUM_API_ENDPOINT', 'https://your-server.com/api/');

// Plugin security settings
define('SRP_ENCRYPTION_KEY', 'your-unique-encryption-key-here');

// API rate limits for free vs premium
define('SRP_FREE_MONTHLY_LIMIT', 10);
define('SRP_PREMIUM_MONTHLY_LIMIT', 100);

// Mediamodifier API limits
define('SRP_MEDIAMODIFIER_FREE_LIMIT', 100); // 100 free calls per month
define('SRP_MEDIAMODIFIER_CACHE_DURATION', 3600); // 1 hour cache

/**
 * Get the appropriate API key
 */
function srp_get_api_key()
{
  // Check if user has premium and their own API key
  $user_settings = get_option('srp_user_settings', []);

  if (!empty($user_settings['premium_api_key'])) {
    return $user_settings['premium_api_key'];
  }

  // Use default plugin API key
  return SRP_DEFAULT_API_KEY;
}

/**
 * Get Mediamodifier API key for device mockups
 */
function srp_get_mediamodifier_api_key()
{
  // Check if user has their own Mediamodifier API key
  $mockup_settings = get_option('srp_device_mockup_settings', []);

  if (!empty($mockup_settings['mediamodifier_api_key'])) {
    return $mockup_settings['mediamodifier_api_key'];
  }

  // Use default plugin Mediamodifier API key
  return defined('SRP_MEDIAMODIFIER_API_KEY') ? SRP_MEDIAMODIFIER_API_KEY : '';
}

/**
 * Check if user has premium features
 */
function srp_is_premium_user()
{
  $user_settings = get_option('srp_user_settings', []);
  return !empty($user_settings['premium_api_key']);
}

/**
 * Check if high-quality mockups are available
 */
function srp_has_premium_mockups()
{
  return !empty(srp_get_mediamodifier_api_key());
}

/**
 * Get Mediamodifier usage stats
 */
function srp_get_mediamodifier_usage()
{
  $usage = get_option('srp_mediamodifier_usage', [
    'calls_this_month' => 0,
    'month' => date('Y-m'),
    'total_calls' => 0
  ]);

  // Reset if new month
  $current_month = date('Y-m');
  if ($usage['month'] !== $current_month) {
    $usage['calls_this_month'] = 0;
    $usage['month'] = $current_month;
    update_option('srp_mediamodifier_usage', $usage);
  }

  return $usage;
}

/**
 * Increment Mediamodifier usage counter
 */
function srp_increment_mediamodifier_usage()
{
  $usage = srp_get_mediamodifier_usage();
  $usage['calls_this_month']++;
  $usage['total_calls']++;
  update_option('srp_mediamodifier_usage', $usage);

  return $usage;
}

/**
 * Check if within Mediamodifier limits
 */
function srp_can_use_mediamodifier()
{
  $usage = srp_get_mediamodifier_usage();
  $limit = SRP_MEDIAMODIFIER_FREE_LIMIT;

  // If user has their own API key, they likely have higher limits
  $mockup_settings = get_option('srp_device_mockup_settings', []);
  if (!empty($mockup_settings['mediamodifier_api_key'])) {
    $limit = 5000; // Assume premium plan limit
  }

  return $usage['calls_this_month'] < $limit;
}
