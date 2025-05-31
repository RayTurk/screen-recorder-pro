<?php

/**
 * Recordings Manager Class - Create this as includes/class-recordings-manager.php
 */

if (!defined('ABSPATH')) {
  exit;
}

class SRP_Recordings_Manager {
  private $table_name;

  public function __construct() {
    global $wpdb;
    $this->table_name = $wpdb->prefix . 'srp_recordings';
  }

  /**
   * Create the recordings table
   */
  public function create_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$this->table_name} (
      id bigint(20) NOT NULL AUTO_INCREMENT,
      post_id bigint(20) NOT NULL DEFAULT 0,
      url text NOT NULL,
      status varchar(20) NOT NULL DEFAULT 'processing',
      options longtext,
      attachment_id bigint(20) DEFAULT NULL,
      video_url text DEFAULT NULL,
      api_response longtext,
      created_at datetime DEFAULT CURRENT_TIMESTAMP,
      updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY post_id (post_id),
      KEY status (status),
      KEY created_at (created_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Check if the table was created successfully
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
    if ($table_exists) {
      error_log('SRP: Database table created successfully: ' . $this->table_name);
    } else {
      error_log('SRP: ERROR - Failed to create database table: ' . $this->table_name);
    }
  }

  /**
   * Create a new recording
   */
  public function create($data) {
    global $wpdb;

    $defaults = [
      'post_id' => 0,
      'url' => '',
      'status' => 'processing',
      'options' => '',
      'attachment_id' => null,
      'video_url' => null,
      'api_response' => ''
    ];

    $data = wp_parse_args($data, $defaults);

    // Serialize arrays
    if (is_array($data['options'])) {
      $data['options'] = serialize($data['options']);
    }
    if (is_array($data['api_response'])) {
      $data['api_response'] = serialize($data['api_response']);
    }

    $inserted = $wpdb->insert(
      $this->table_name,
      [
        'post_id' => intval($data['post_id']),
        'url' => sanitize_text_field($data['url']),
        'status' => sanitize_text_field($data['status']),
        'options' => $data['options'],
        'attachment_id' => $data['attachment_id'] ? intval($data['attachment_id']) : null,
        'video_url' => $data['video_url'] ? esc_url_raw($data['video_url']) : null,
        'api_response' => $data['api_response']
      ],
      [
        '%d', // post_id
        '%s', // url
        '%s', // status
        '%s', // options
        '%d', // attachment_id
        '%s', // video_url
        '%s'  // api_response
      ]
    );

    if ($inserted === false) {
      error_log('SRP: Database insert failed: ' . $wpdb->last_error);
      return false;
    }

    $recording_id = $wpdb->insert_id;
    error_log('SRP: Recording created successfully with ID: ' . $recording_id);
    
    return $recording_id;
  }

  /**
   * Get a recording by ID
   */
  public function get($id) {
    global $wpdb;

    $recording = $wpdb->get_row(
      $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id)
    );

    if ($recording) {
      // Unserialize arrays
      if (!empty($recording->options)) {
        $recording->options = maybe_unserialize($recording->options);
      }
      if (!empty($recording->api_response)) {
        $recording->api_response = maybe_unserialize($recording->api_response);
      }
    }

    return $recording;
  }

  /**
   * Get recording by post ID
   */
  public function get_by_post_id($post_id) {
    global $wpdb;

    $recording = $wpdb->get_row(
      $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE post_id = %d ORDER BY created_at DESC LIMIT 1", $post_id)
    );

    if ($recording) {
      // Unserialize arrays
      if (!empty($recording->options)) {
        $recording->options = maybe_unserialize($recording->options);
      }
      if (!empty($recording->api_response)) {
        $recording->api_response = maybe_unserialize($recording->api_response);
      }
    }

    return $recording;
  }

  /**
   * Update a recording
   */
  public function update($id, $data) {
    global $wpdb;

    // Serialize arrays
    if (isset($data['options']) && is_array($data['options'])) {
      $data['options'] = serialize($data['options']);
    }
    if (isset($data['api_response']) && is_array($data['api_response'])) {
      $data['api_response'] = serialize($data['api_response']);
    }

    $format = [];
    foreach ($data as $key => $value) {
      if (in_array($key, ['post_id', 'attachment_id'])) {
        $format[] = '%d';
      } else {
        $format[] = '%s';
      }
    }

    $updated = $wpdb->update(
      $this->table_name,
      $data,
      ['id' => $id],
      $format,
      ['%d']
    );

    if ($updated === false) {
      error_log('SRP: Database update failed: ' . $wpdb->last_error);
      return false;
    }

    return true;
  }

  /**
   * Delete a recording
   */
  public function delete($id) {
    global $wpdb;

    $deleted = $wpdb->delete(
      $this->table_name,
      ['id' => $id],
      ['%d']
    );

    return $deleted !== false;
  }

  /**
   * Get all recordings
   */
  public function get_all($limit = 50, $offset = 0) {
    global $wpdb;

    $recordings = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $limit,
        $offset
      )
    );

    // Unserialize data for each recording
    foreach ($recordings as $recording) {
      if (!empty($recording->options)) {
        $recording->options = maybe_unserialize($recording->options);
      }
      if (!empty($recording->api_response)) {
        $recording->api_response = maybe_unserialize($recording->api_response);
      }
    }

    return $recordings;
  }

  /**
   * Get count by status
   */
  public function get_count_by_status($status = 'completed') {
    global $wpdb;

    return $wpdb->get_var(
      $wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s", $status)
    );
  }

  /**
   * Get monthly count
   */
  public function get_monthly_count($month) {
    global $wpdb;

    return $wpdb->get_var(
      $wpdb->prepare(
        "SELECT COUNT(*) FROM {$this->table_name} WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s",
        $month
      )
    );
  }

  /**
   * Cleanup old recordings
   */
  public function cleanup_old_recordings($days = 30) {
    global $wpdb;

    $deleted = $wpdb->query(
      $wpdb->prepare(
        "DELETE FROM {$this->table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
        $days
      )
    );

    return $deleted;
  }

  /**
   * Debug method to check table structure
   */
  public function debug_table() {
    global $wpdb;
    
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
    if (!$table_exists) {
      return 'Table does not exist: ' . $this->table_name;
    }

    $columns = $wpdb->get_results("DESCRIBE {$this->table_name}");
    
    $debug_info = "Table: {$this->table_name}\n";
    $debug_info .= "Columns:\n";
    foreach ($columns as $column) {
      $debug_info .= "- {$column->Field} ({$column->Type})\n";
    }

    return $debug_info;
  }
}