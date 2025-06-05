<?php

/**
 * Updated Config Secure with Proper Freemius Protection
 * Replace your includes/config-secure.php with this version
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

// API rate limits (updated for Freemius integration)
define('SRP_FREE_MONTHLY_LIMIT', 10);        // Free tier: 10 recordings
define('SRP_PRO_MONTHLY_LIMIT', 999999);     // Pro tier: Unlimited

// Mediamodifier API limits
define('SRP_MEDIAMODIFIER_FREE_LIMIT', 100); // 100 free calls per month
define('SRP_MEDIAMODIFIER_CACHE_DURATION', 3600); // 1 hour cache

/**
 * Get the appropriate API key with Freemius integration
 */
function srp_get_api_key()
{
  // Check if user has premium and their own API key (PREMIUM ONLY)
  if (function_exists('srp_fs') && srp_fs()->can_use_premium_code__premium_only()) {
    $user_settings = get_option('srp_user_settings', []);
    if (!empty($user_settings['premium_api_key'])) {
      return $user_settings['premium_api_key'];
    }
  }

  // Use default plugin API key for all users
  return SRP_DEFAULT_API_KEY;
}

/**
 * Get Mediamodifier API key for device mockups
 */
function srp_get_mediamodifier_api_key()
{
  // Check if user has their own Mediamodifier API key (PREMIUM ONLY)
  if (function_exists('srp_fs') && srp_fs()->can_use_premium_code__premium_only()) {
    $mockup_settings = get_option('srp_device_mockup_settings', []);
    if (!empty($mockup_settings['mediamodifier_api_key'])) {
      return $mockup_settings['mediamodifier_api_key'];
    }
  }

  // Use default plugin Mediamodifier API key
  return defined('SRP_MEDIAMODIFIER_API_KEY') ? SRP_MEDIAMODIFIER_API_KEY : '';
}

/**
 * Check if user has premium features using Freemius
 */
function srp_is_premium_user()
{
  // If Freemius is not loaded, default to free
  if (!function_exists('srp_fs')) {
    return false;
  }

  // Use Freemius to check if user can use premium code
  return srp_fs()->can_use_premium_code();
}

/**
 * Get user's plan name
 */
function srp_get_plan_name()
{
  if (!function_exists('srp_fs')) {
    return 'Free';
  }

  if (srp_fs()->can_use_premium_code()) {
    return 'Pro';
  }

  return 'Free';
}

/**
 * Get usage limit based on plan
 */
function srp_get_usage_limit()
{
  if (!function_exists('srp_fs')) {
    return SRP_FREE_MONTHLY_LIMIT;
  }

  // Premium users get unlimited
  if (srp_fs()->can_use_premium_code()) {
    return SRP_PRO_MONTHLY_LIMIT; // Unlimited
  }

  return SRP_FREE_MONTHLY_LIMIT; // 10 recordings
}

/**
 * Check if user can create more recordings
 */
function srp_can_create_recording()
{
  if (!function_exists('srp_fs')) {
    // Fallback behavior if Freemius not loaded
    return true;
  }

  // Pro users always can
  if (srp_fs()->can_use_premium_code()) {
    return true;
  }

  // Check free user limits
  if (class_exists('SRP_Recordings_Manager')) {
    $recordings_manager = new SRP_Recordings_Manager();
    $current_month = date('Y-m');
    $usage_count = $recordings_manager->get_monthly_count($current_month);

    return $usage_count < SRP_FREE_MONTHLY_LIMIT;
  }

  return true; // Default to allowing if we can't check
}

/**
 * Get upgrade URL from Freemius
 */
function srp_get_upgrade_url()
{
  if (!function_exists('srp_fs')) {
    return admin_url('admin.php?page=screen-recorder');
  }

  return srp_fs()->get_upgrade_url();
}

/**
 * Get checkout URL for contextual upsells
 */
function srp_get_checkout_url()
{
  if (!function_exists('srp_fs')) {
    return admin_url('admin.php?page=screen-recorder');
  }

  return srp_fs()->checkout_url();
}

/**
 * Check if showing to free users (for upsells)
 */
function srp_should_show_upgrade_prompts()
{
  if (!function_exists('srp_fs')) {
    return false;
  }

  // Show to users who haven't paid (even if they have premium version)
  return srp_fs()->is_not_paying();
}

/**
 * Check if we should show premium features in UI
 */
function srp_show_premium_features()
{
  if (!function_exists('srp_fs')) {
    return false;
  }

  // Show premium features if user can use premium code
  return srp_fs()->can_use_premium_code();
}

// Legacy functions for backward compatibility
function srp_has_premium_mockups()
{
  return !empty(srp_get_mediamodifier_api_key());
}

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

function srp_increment_mediamodifier_usage()
{
  $usage = srp_get_mediamodifier_usage();
  $usage['calls_this_month']++;
  $usage['total_calls']++;
  update_option('srp_mediamodifier_usage', $usage);

  return $usage;
}

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
