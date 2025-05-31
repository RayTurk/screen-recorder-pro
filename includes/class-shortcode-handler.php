<?php

/**
 * Shortcode Handler Class - Create this as includes/class-shortcode-handler.php
 */

if (!defined('ABSPATH')) {
  exit;
}

class SRP_Shortcode_Handler {
  private $recordings_manager;

  public function __construct() {
    $this->recordings_manager = new SRP_Recordings_Manager();
  }

  /**
   * Render the screen recording shortcode
   * Usage: [screen_recording id="123" width="800" height="600" autoplay="false" controls="true"]
   */
  public function render($atts) {
    $atts = shortcode_atts([
      'id' => 0,
      'width' => '100%',
      'height' => 'auto',
      'autoplay' => 'true',
      'controls' => 'false',
      'loop' => 'true',
      'muted' => 'true',
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

    // Build video attributes
    $video_atts = [
      'src' => esc_url($video_url),
      'class' => esc_attr($atts['class']),
      'style' => esc_attr($atts['style'])
    ];

    // Add width
    if ($atts['width'] !== 'auto') {
      if (is_numeric($atts['width'])) {
        $video_atts['width'] = $atts['width'];
      } else {
        $video_atts['style'] .= 'width: ' . $atts['width'] . ';';
      }
    }

    // Add height
    if ($atts['height'] !== 'auto') {
      if (is_numeric($atts['height'])) {
        $video_atts['height'] = $atts['height'];
      } else {
        $video_atts['style'] .= 'height: ' . $atts['height'] . ';';
      }
    }

    // Add boolean attributes
    $boolean_atts = [];
    if ($atts['controls'] === 'true') {
      $boolean_atts[] = 'controls';
    }
    if ($atts['autoplay'] === 'true') {
      $boolean_atts[] = 'autoplay';
    }
    if ($atts['loop'] === 'true') {
      $boolean_atts[] = 'loop';
    }
    if ($atts['muted'] === 'true') {
      $boolean_atts[] = 'muted';
    }

    // Build the video tag
    $video_html = '<video';
    
    // Add regular attributes
    foreach ($video_atts as $attr => $value) {
      if ($attr === 'src') continue; // Handle src separately
      $video_html .= ' ' . $attr . '="' . $value . '"';
    }
    
    // Add boolean attributes
    foreach ($boolean_atts as $attr) {
      $video_html .= ' ' . $attr;
    }
    
    $video_html .= '>';
    
    // Add source and fallback
    $video_html .= '<source src="' . esc_url($video_url) . '" type="video/mp4">';
    $video_html .= '<p>' . __('Your browser does not support the video tag.', 'screen-recorder-pro') . '</p>';
    $video_html .= '</video>';

    return $video_html;
  }

  /**
   * Debug method to check what's in the database
   */
  public function debug_recording($recording_id) {
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