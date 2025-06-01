<?php

/**
 * Shortcode Handler Class - Create this as includes/class-shortcode-handler.php
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
   * Render the screen recording shortcode
   * Usage: [screen_recording id="123" device="iphone_15_pro" width="800" height="600" autoplay="false" controls="true"]
   */
  public function render($atts)
  {
    $atts = shortcode_atts([
      'id' => 0,
      'device' => 'none', // New device mockup option
      'width' => '100%',
      'height' => 'auto',
      'autoplay' => 'false',
      'controls' => 'true',
      'loop' => 'false',
      'muted' => 'false',
      'class' => 'screen-recording-video',
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
      // Try to get URL from attachment ID
      $video_url = wp_get_attachment_url($recording->attachment_id);
    }

    if (!$video_url && !empty($recording->video_url)) {
      // Fallback to stored video_url
      $video_url = $recording->video_url;
    }

    if (!$video_url) {
      return '<p><em>' . __('Video file not found.', 'screen-recorder-pro') . '</em></p>';
    }

    // Use device mockup system
    return SRP_Device_Mockups::render_device_frame($video_url, $atts['device'], $atts);
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

    $debug_info = [
      'ID' => $recording->id ?? 'N/A',
      'Status' => $recording->status ?? 'N/A',
      'Attachment ID' => $recording->attachment_id ?? 'NULL',
      'Video URL' => $recording->video_url ?? 'NULL',
      'WP Attachment URL' => ($recording->attachment_id ?? false) ? wp_get_attachment_url($recording->attachment_id) : 'N/A',
      'Post ID' => $recording->post_id ?? 'N/A',
      'URL' => $recording->url ?? 'N/A',
      'Created' => $recording->created_at ?? 'N/A'
    ];

    // Also check table structure
    $table_debug = $this->recordings_manager->debug_table();

    $output = '<pre>Recording Debug Info:' . "\n";
    foreach ($debug_info as $key => $value) {
      $output .= $key . ': ' . $value . "\n";
    }
    $output .= "\n" . $table_debug . '</pre>';

    return $output;
  }
}
