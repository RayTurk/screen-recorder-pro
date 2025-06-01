<?php

/**
 * Secure API Configuration - Create this as includes/config-secure.php
 * This file contains the default API key for the plugin
 */

if (!defined('ABSPATH')) {
  exit;
}

// Default API key for the plugin (your ScreenshotOne key)
define('SRP_DEFAULT_API_KEY', 'agCc144ZfFjg2Q'); // Replace with your actual key

// Premium features API endpoint (for future use)
define('SRP_PREMIUM_API_ENDPOINT', 'https://your-server.com/api/');

// Plugin security settings
define('SRP_ENCRYPTION_KEY', 'your-unique-encryption-key-here');

// API rate limits for free vs premium
define('SRP_FREE_MONTHLY_LIMIT', 10);
define('SRP_PREMIUM_MONTHLY_LIMIT', 100);

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
 * Check if user has premium features
 */
function srp_is_premium_user()
{
  $user_settings = get_option('srp_user_settings', []);
  return !empty($user_settings['premium_api_key']);
}
