<?php

/**
 * Clean Configuration - Users don't need to manage API keys
 * Replace your includes/config-secure.php with this version
 */

if (!defined('ABSPATH')) {
  exit;
}

// API rate limits (updated for clean freemius integration)
define('SRP_FREE_TOTAL_LIMIT', 1);           // Free tier: 1 recording total
define('SRP_STARTER_MONTHLY_LIMIT', 25);     // Starter: 25/month
define('SRP_PRO_MONTHLY_LIMIT', 100);        // Pro: 100/month
define('SRP_AGENCY_MONTHLY_LIMIT', 300);     // Agency: 300/month

/**
 * Get usage limit based on plan
 */
function srp_get_usage_limit()
{
  if (!function_exists('srp_fs') || !srp_fs()->is_registered()) {
    return SRP_FREE_TOTAL_LIMIT; // 1 total recording
  }

  if (!srp_fs()->is_paying()) {
    return SRP_FREE_TOTAL_LIMIT; // 1 total recording
  }

  // Get user's plan
  $plan = srp_fs()->get_plan();
  if (!$plan) {
    return SRP_FREE_TOTAL_LIMIT;
  }

  switch (strtolower($plan->name)) {
    case 'starter':
      return SRP_STARTER_MONTHLY_LIMIT;
    case 'pro':
      return SRP_PRO_MONTHLY_LIMIT;
    case 'agency':
      return SRP_AGENCY_MONTHLY_LIMIT;
    default:
      return SRP_FREE_TOTAL_LIMIT;
  }
}

/**
 * Check if user can create more recordings
 */
function srp_can_create_recording()
{
  if (!function_exists('srp_fs')) {
    return false; // Safety fallback
  }

  // Paying users check monthly limits
  if (srp_fs()->is_paying()) {
    if (class_exists('SRP_Recordings_Manager')) {
      $recordings_manager = new SRP_Recordings_Manager();
      $current_month = date('Y-m');
      $usage_count = $recordings_manager->get_monthly_count($current_month);
      $monthly_limit = srp_get_usage_limit();

      return $usage_count < $monthly_limit;
    }
    return true;
  }

  // Free users check total recordings ever
  if (class_exists('SRP_Recordings_Manager')) {
    $recordings_manager = new SRP_Recordings_Manager();
    $total_recordings = $recordings_manager->get_count_by_status('completed');

    return $total_recordings < SRP_FREE_TOTAL_LIMIT; // Must be less than 1
  }

  return false;
}

/**
 * Get current usage for display
 */
function srp_get_current_usage()
{
  if (!class_exists('SRP_Recordings_Manager')) {
    return 0;
  }

  $recordings_manager = new SRP_Recordings_Manager();

  if (function_exists('srp_fs') && srp_fs()->is_paying()) {
    // Paying users: monthly count
    $current_month = date('Y-m');
    return $recordings_manager->get_monthly_count($current_month);
  } else {
    // Free users: total count ever
    return $recordings_manager->get_count_by_status('completed');
  }
}

/**
 * Get usage display text
 */
function srp_get_usage_display()
{
  $current_usage = srp_get_current_usage();
  $limit = srp_get_usage_limit();

  if (function_exists('srp_fs') && srp_fs()->is_paying()) {
    return $current_usage . '/' . $limit . ' this month';
  } else {
    if ($current_usage >= $limit) {
      return 'Free recording used';
    } else {
      return 'Free recording available';
    }
  }
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
  return srp_fs()->is_paying();
}

/**
 * Get user's plan name
 */
function srp_get_plan_name()
{
  if (!function_exists('srp_fs')) {
    return 'Free';
  }

  if (srp_fs()->is_paying()) {
    $plan = srp_fs()->get_plan();
    return $plan ? ucfirst($plan->name) : 'Pro';
  }

  return 'Free';
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

  // Show to users who haven't paid
  return !srp_fs()->is_paying();
}

/**
 * Check if we should show premium features in UI
 */
function srp_show_premium_features()
{
  if (!function_exists('srp_fs')) {
    return false;
  }

  // Show premium features if user is paying
  return srp_fs()->is_paying();
}
