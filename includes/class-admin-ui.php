<?php

/**
 * Admin UI Class - Create this as includes/class-admin-ui.php
 */

if (!defined('ABSPATH')) {
  exit;
}

class SRP_Admin_UI
{

  public function add_menu_pages()
  {
    // Main menu page
    add_menu_page(
      __('Screen Recorder Pro', 'screen-recorder-pro'),
      __('Screen Recorder', 'screen-recorder-pro'),
      'manage_options',
      'screen-recorder',
      [$this, 'render_main_page'],
      'dashicons-video-alt3',
      30
    );

    // All Recordings submenu
    add_submenu_page(
      'screen-recorder',
      __('All Recordings', 'screen-recorder-pro'),
      __('All Recordings', 'screen-recorder-pro'),
      'edit_posts',
      'screen-recorder-all',
      [$this, 'render_recordings_page']
    );

    // Settings submenu
    add_submenu_page(
      'screen-recorder',
      __('Settings', 'screen-recorder-pro'),
      __('Settings', 'screen-recorder-pro'),
      'manage_options',
      'screen-recorder-settings',
      [$this, 'render_settings_page']
    );
  }

  /**
   * Modern dashboard page
   */
  public function render_main_page()
  {
    $recordings_manager = new SRP_Recordings_Manager();
    $recent_recordings = $recordings_manager->get_all(3);
    $total_recordings = $recordings_manager->get_count_by_status('completed');
    $settings = get_option('srp_settings', []);
    $api_key_set = !empty($settings['api_key']);

?>
    <div class="wrap srp-dashboard">
      <?php $this->render_modern_styles(); ?>

      <div class="srp-header">
        <div class="srp-header-content">
          <div class="srp-header-text">
            <h1 class="srp-title">
              <span class="dashicons dashicons-video-alt3"></span>
              <?php _e('Screen Recorder Pro', 'screen-recorder-pro'); ?>
            </h1>
            <p class="srp-subtitle"><?php _e('Create beautiful scrolling videos of your website pages', 'screen-recorder-pro'); ?></p>
          </div>
          <div class="srp-header-stats">
            <div class="srp-stat-card">
              <div class="srp-stat-number"><?php echo $total_recordings; ?></div>
              <div class="srp-stat-label"><?php _e('Total Recordings', 'screen-recorder-pro'); ?></div>
            </div>
          </div>
        </div>
      </div>

      <?php if (!$api_key_set): ?>
        <div class="srp-notice srp-notice-warning">
          <div class="srp-notice-icon">
            <span class="dashicons dashicons-warning"></span>
          </div>
          <div class="srp-notice-content">
            <h3><?php _e('API Key Required', 'screen-recorder-pro'); ?></h3>
            <p><?php _e('Please configure your ScreenshotOne API key to start creating recordings.', 'screen-recorder-pro'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=screen-recorder-settings'); ?>" class="srp-button srp-button-primary">
              <?php _e('Configure Settings', 'screen-recorder-pro'); ?>
            </a>
          </div>
        </div>
      <?php endif; ?>

      <div class="srp-main-content">
        <div class="srp-grid">
          <!-- Create Recording Card -->
          <div class="srp-card srp-card-primary">
            <div class="srp-card-header">
              <h2><?php _e('Create New Recording', 'screen-recorder-pro'); ?></h2>
              <p><?php _e('Generate a smooth scrolling video of any webpage', 'screen-recorder-pro'); ?></p>
            </div>

            <div class="srp-card-body">
              <form id="srp-recording-form" class="srp-form">
                <div class="srp-form-group">
                  <label for="srp-url" class="srp-label">
                    <span class="dashicons dashicons-admin-links"></span>
                    <?php _e('Website URL', 'screen-recorder-pro'); ?>
                  </label>
                  <input type="url" id="srp-url" class="srp-input" placeholder="https://example.com" />
                  <div class="srp-help-text"><?php _e('Enter the full URL of the page you want to record', 'screen-recorder-pro'); ?></div>
                </div>

                <div class="srp-form-row">
                  <div class="srp-form-group">
                    <label for="srp-duration" class="srp-label">
                      <span class="dashicons dashicons-clock"></span>
                      <?php _e('Duration', 'screen-recorder-pro'); ?>
                    </label>
                    <select id="srp-duration" class="srp-select">
                      <option value="3">3 seconds</option>
                      <option value="5" selected>5 seconds</option>
                      <option value="8">8 seconds</option>
                      <option value="10">10 seconds</option>
                      <option value="15">15 seconds</option>
                    </select>
                  </div>

                  <div class="srp-form-group">
                    <label for="srp-viewport" class="srp-label">
                      <span class="dashicons dashicons-desktop"></span>
                      <?php _e('Viewport Size', 'screen-recorder-pro'); ?>
                    </label>
                    <select id="srp-viewport" class="srp-select">
                      <option value="1920x1080">1920×1080 (Full HD)</option>
                      <option value="1440x900" selected>1440×900 (Desktop)</option>
                      <option value="1280x720">1280×720 (HD)</option>
                      <option value="768x1024">768×1024 (Tablet)</option>
                    </select>
                  </div>
                </div>

                <button type="button" id="srp-create-recording" class="srp-button srp-button-primary srp-button-large" <?php echo !$api_key_set ? 'disabled' : ''; ?>>
                  <span class="srp-button-icon dashicons dashicons-video-alt3"></span>
                  <?php _e('Create Recording', 'screen-recorder-pro'); ?>
                </button>
              </form>

              <!-- Status Messages -->
              <div id="srp-recording-status" class="srp-status-message" style="display: none;">
                <div class="srp-spinner"></div>
                <span id="srp-status-text"><?php _e('Creating your recording...', 'screen-recorder-pro'); ?></span>
              </div>

              <div id="srp-recording-result" class="srp-success-message" style="display: none;">
                <div class="srp-success-icon">
                  <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="srp-success-content">
                  <h4><?php _e('Recording Created Successfully!', 'screen-recorder-pro'); ?></h4>
                  <div class="srp-success-actions">
                    <a href="#" id="srp-view-recording" class="srp-button srp-button-secondary">
                      <?php _e('View in Media Library', 'screen-recorder-pro'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=screen-recorder-all'); ?>" class="srp-button srp-button-secondary">
                      <?php _e('View All Recordings', 'screen-recorder-pro'); ?>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Recordings Card -->
          <?php if (!empty($recent_recordings)): ?>
            <div class="srp-card">
              <div class="srp-card-header">
                <h2><?php _e('Recent Recordings', 'screen-recorder-pro'); ?></h2>
                <a href="<?php echo admin_url('admin.php?page=screen-recorder-all'); ?>" class="srp-link">
                  <?php _e('View all', 'screen-recorder-pro'); ?>
                </a>
              </div>

              <div class="srp-card-body">
                <div class="srp-recordings-list">
                  <?php foreach ($recent_recordings as $recording): ?>
                    <?php $post = get_post($recording->post_id); ?>
                    <div class="srp-recording-item">
                      <div class="srp-recording-info">
                        <div class="srp-recording-title">
                          <?php echo $post ? esc_html($post->post_title) : __('External URL', 'screen-recorder-pro'); ?>
                        </div>
                        <div class="srp-recording-url"><?php echo esc_html(parse_url($recording->url, PHP_URL_HOST)); ?></div>
                        <div class="srp-recording-date"><?php echo human_time_diff(strtotime($recording->created_at)); ?> ago</div>
                      </div>
                      <div class="srp-recording-actions">
                        <?php if ($recording->attachment_id): ?>
                          <a href="<?php echo admin_url('post.php?post=' . $recording->attachment_id . '&action=edit'); ?>"
                            class="srp-button srp-button-small">
                            <span class="dashicons dashicons-edit"></span>
                          </a>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <script>
        jQuery(document).ready(function($) {
          // Always hide status messages on page load
          $('#srp-recording-status, #srp-recording-result').hide();

          $('#srp-create-recording').on('click', function() {
            var $button = $(this);
            var url = $('#srp-url').val().trim();
            var duration = $('#srp-duration').val();
            var viewport = $('#srp-viewport').val().split('x');

            if (!url) {
              alert('<?php _e('Please enter a valid URL', 'screen-recorder-pro'); ?>');
              return;
            }

            // Reset and show status
            $('#srp-recording-result').hide();
            $('#srp-recording-status').show();
            $button.prop('disabled', true).addClass('loading');

            $.post(ajaxurl, {
                action: 'srp_create_recording',
                nonce: '<?php echo wp_create_nonce('srp_ajax_nonce'); ?>',
                post_id: 1, // Default fallback
                url: url,
                duration: duration,
                viewport_width: viewport[0],
                viewport_height: viewport[1],
                format: 'mp4',
                scenario: 'scroll'
              })
              .done(function(response) {
                $('#srp-recording-status').hide();

                if (response.success) {
                  $('#srp-view-recording').attr('href', '<?php echo admin_url('post.php?post='); ?>' + response.data.attachment_id + '&action=edit');
                  $('#srp-recording-result').show();

                  // Clear form
                  $('#srp-url').val('');

                  // Auto-hide after 10 seconds
                  setTimeout(function() {
                    $('#srp-recording-result').fadeOut();
                  }, 10000);
                } else {
                  alert('Error: ' + (response.data.message || 'Unknown error occurred'));
                }
              })
              .fail(function() {
                $('#srp-recording-status').hide();
                alert('Network error occurred. Please try again.');
              })
              .always(function() {
                $button.prop('disabled', false).removeClass('loading');
              });
          });
        });
      </script>
    </div>
  <?php
  }

  /**
   * All Recordings page with modern design
   */
  public function render_recordings_page()
  {
    $recordings_manager = new SRP_Recordings_Manager();
    $recordings = $recordings_manager->get_all();

  ?>
    <div class="wrap srp-dashboard">
      <?php $this->render_modern_styles(); ?>

      <div class="srp-header">
        <div class="srp-header-content">
          <div class="srp-header-text">
            <h1 class="srp-title"><?php _e('All Recordings', 'screen-recorder-pro'); ?></h1>
            <p class="srp-subtitle"><?php _e('Manage and share your screen recordings', 'screen-recorder-pro'); ?></p>
          </div>
          <div class="srp-header-actions">
            <a href="<?php echo admin_url('admin.php?page=screen-recorder'); ?>" class="srp-button srp-button-primary">
              <span class="dashicons dashicons-plus-alt"></span>
              <?php _e('New Recording', 'screen-recorder-pro'); ?>
            </a>
          </div>
        </div>
      </div>

      <?php if (empty($recordings)): ?>
        <div class="srp-empty-state">
          <div class="srp-empty-icon">
            <span class="dashicons dashicons-video-alt3"></span>
          </div>
          <h2><?php _e('No recordings yet', 'screen-recorder-pro'); ?></h2>
          <p><?php _e('Create your first screen recording to get started', 'screen-recorder-pro'); ?></p>
          <a href="<?php echo admin_url('admin.php?page=screen-recorder'); ?>" class="srp-button srp-button-primary">
            <?php _e('Create Recording', 'screen-recorder-pro'); ?>
          </a>
        </div>
      <?php else: ?>
        <div class="srp-recordings-grid">
          <?php foreach ($recordings as $recording): ?>
            <?php
            $post = get_post($recording->post_id);
            $post_title = $post ? $post->post_title : parse_url($recording->url, PHP_URL_HOST);
            $video_url = $recording->attachment_id ? wp_get_attachment_url($recording->attachment_id) : '';
            $shortcode = "[screen_recording id=\"{$recording->id}\"]";
            ?>
            <div class="srp-recording-card">
              <div class="srp-recording-preview">
                <?php if ($video_url && $recording->status === 'completed'): ?>
                  <video class="srp-preview-video" muted>
                    <source src="<?php echo esc_attr($video_url); ?>" type="video/mp4">
                  </video>
                  <div class="srp-preview-overlay">
                    <button type="button" class="srp-preview-btn"
                      data-video-url="<?php echo esc_attr($video_url); ?>"
                      data-title="<?php echo esc_attr($post_title); ?>">
                      <span class="dashicons dashicons-controls-play"></span>
                    </button>
                  </div>
                <?php else: ?>
                  <div class="srp-preview-placeholder">
                    <span class="dashicons dashicons-video-alt3"></span>
                  </div>
                <?php endif; ?>
              </div>

              <div class="srp-recording-content">
                <h3 class="srp-recording-title"><?php echo esc_html($post_title); ?></h3>
                <div class="srp-recording-url"><?php echo esc_html(parse_url($recording->url, PHP_URL_HOST)); ?></div>
                <div class="srp-recording-meta">
                  <span class="srp-status srp-status-<?php echo esc_attr($recording->status); ?>">
                    <?php echo esc_html(ucfirst($recording->status)); ?>
                  </span>
                  <span class="srp-date"><?php echo human_time_diff(strtotime($recording->created_at)); ?> ago</span>
                </div>

                <div class="srp-shortcode-section">
                  <label class="srp-shortcode-label"><?php _e('Shortcode', 'screen-recorder-pro'); ?></label>
                  <div class="srp-shortcode-input-group">
                    <input type="text" class="srp-shortcode-input"
                      value="<?php echo esc_attr($shortcode); ?>"
                      readonly onclick="this.select()">
                    <button type="button" class="srp-copy-btn"
                      data-shortcode="<?php echo esc_attr($shortcode); ?>">
                      <span class="dashicons dashicons-admin-page"></span>
                    </button>
                  </div>
                </div>

                <div class="srp-recording-actions">
                  <?php if ($recording->attachment_id): ?>
                    <a href="<?php echo admin_url('post.php?post=' . $recording->attachment_id . '&action=edit'); ?>"
                      class="srp-button srp-button-secondary srp-button-small">
                      <?php _e('Edit', 'screen-recorder-pro'); ?>
                    </a>
                  <?php endif; ?>

                  <button type="button" class="srp-button srp-button-danger srp-button-small delete-recording"
                    data-recording-id="<?php echo esc_attr($recording->id); ?>">
                    <?php _e('Delete', 'screen-recorder-pro'); ?>
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Modern Preview Modal -->
      <div id="srp-preview-modal" class="srp-modal" style="display: none;">
        <div class="srp-modal-backdrop"></div>
        <div class="srp-modal-container">
          <div class="srp-modal-header">
            <h3 id="srp-modal-title"><?php _e('Recording Preview', 'screen-recorder-pro'); ?></h3>
            <button type="button" class="srp-modal-close">
              <span class="dashicons dashicons-no-alt"></span>
            </button>
          </div>
          <div class="srp-modal-body">
            <video id="srp-modal-video" controls>
              <?php _e('Your browser does not support the video tag.', 'screen-recorder-pro'); ?>
            </video>
          </div>
        </div>
      </div>

      <script>
        jQuery(document).ready(function($) {
          // Preview functionality
          $('.srp-preview-btn').on('click', function() {
            var videoUrl = $(this).data('video-url');
            var title = $(this).data('title');

            $('#srp-modal-title').text(title);
            $('#srp-modal-video').attr('src', videoUrl);
            $('#srp-preview-modal').show();
          });

          // Close modal
          $('.srp-modal-close, .srp-modal-backdrop').on('click', function() {
            $('#srp-preview-modal').hide();
            $('#srp-modal-video')[0].pause();
          });

          // Copy shortcode
          $('.srp-copy-btn').on('click', function() {
            var shortcode = $(this).data('shortcode');
            var input = $(this).siblings('.srp-shortcode-input')[0];

            input.select();
            input.setSelectionRange(0, 99999);

            try {
              document.execCommand('copy');
              $(this).addClass('copied');
              setTimeout(() => {
                $(this).removeClass('copied');
              }, 2000);
            } catch (err) {
              alert('Shortcode: ' + shortcode);
            }
          });

          // Delete recording
          $('.delete-recording').on('click', function() {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this recording?', 'screen-recorder-pro')); ?>')) {
              return;
            }

            var recordingId = $(this).data('recording-id');
            var $card = $(this).closest('.srp-recording-card');

            $.post(ajaxurl, {
              action: 'srp_delete_recording',
              recording_id: recordingId,
              nonce: '<?php echo wp_create_nonce('srp_ajax_nonce'); ?>'
            }, function(response) {
              if (response.success) {
                $card.fadeOut(300, function() {
                  $(this).remove();
                });
              } else {
                alert('Error: ' + response.data.message);
              }
            });
          });

          // Video hover effects
          $('.srp-preview-video').on('mouseenter', function() {
            this.play();
          }).on('mouseleave', function() {
            this.pause();
            this.currentTime = 0;
          });
        });
      </script>
    </div>
  <?php
  }

  /**
   * Settings page
   */
  public function render_settings_page()
  {
    $settings = get_option('srp_settings', []);

  ?>
    <div class="wrap srp-dashboard">
      <?php $this->render_modern_styles(); ?>

      <div class="srp-header">
        <div class="srp-header-content">
          <div class="srp-header-text">
            <h1 class="srp-title"><?php _e('Settings', 'screen-recorder-pro'); ?></h1>
            <p class="srp-subtitle"><?php _e('Configure your Screen Recorder Pro settings', 'screen-recorder-pro'); ?></p>
          </div>
        </div>
      </div>

      <div class="srp-main-content">
        <div class="srp-card">
          <div class="srp-card-header">
            <h2><?php _e('API Configuration', 'screen-recorder-pro'); ?></h2>
          </div>

          <div class="srp-card-body">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="srp-form">
              <input type="hidden" name="action" value="srp_save_settings">
              <?php wp_nonce_field('srp_save_settings', 'srp_settings_nonce'); ?>

              <div class="srp-form-group">
                <label for="api_key" class="srp-label">
                  <span class="dashicons dashicons-admin-network"></span>
                  <?php _e('ScreenshotOne API Key', 'screen-recorder-pro'); ?>
                </label>
                <input type="text" id="api_key" name="srp_settings[api_key]"
                  value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>"
                  class="srp-input"
                  placeholder="Enter your ScreenshotOne access key" />
                <div class="srp-help-text">
                  <?php _e('Get your API key from', 'screen-recorder-pro'); ?>
                  <a href="https://screenshotone.com" target="_blank">ScreenshotOne.com</a>
                </div>
              </div>

              <div class="srp-form-group">
                <label for="default_duration" class="srp-label">
                  <span class="dashicons dashicons-clock"></span>
                  <?php _e('Default Duration', 'screen-recorder-pro'); ?>
                </label>
                <select id="default_duration" name="srp_settings[default_duration]" class="srp-select">
                  <option value="3" <?php selected($settings['default_duration'] ?? 5, 3); ?>>3 seconds</option>
                  <option value="5" <?php selected($settings['default_duration'] ?? 5, 5); ?>>5 seconds</option>
                  <option value="8" <?php selected($settings['default_duration'] ?? 5, 8); ?>>8 seconds</option>
                  <option value="10" <?php selected($settings['default_duration'] ?? 5, 10); ?>>10 seconds</option>
                  <option value="15" <?php selected($settings['default_duration'] ?? 5, 15); ?>>15 seconds</option>
                </select>
              </div>

              <button type="submit" class="srp-button srp-button-primary">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Save Settings', 'screen-recorder-pro'); ?>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  <?php
  }

  /**
   * Save settings
   */
  public function save_settings()
  {
    if (!wp_verify_nonce($_POST['srp_settings_nonce'], 'srp_save_settings')) {
      wp_die('Security check failed');
    }

    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized');
    }

    $settings = [
      'api_key' => sanitize_text_field($_POST['srp_settings']['api_key']),
      'default_duration' => intval($_POST['srp_settings']['default_duration']),
      'default_format' => 'mp4',
      'default_scenario' => 'scroll'
    ];

    update_option('srp_settings', $settings);

    wp_redirect(admin_url('admin.php?page=screen-recorder-settings&updated=1'));
    exit;
  }

  /**
   * Render modern CSS styles
   */
  private function render_modern_styles()
  {
  ?>
    <style>
      /* Modern Dashboard Styles */
      .srp-dashboard {
        background: #f0f0f1;
        margin: 0 0 0 -20px;
        padding: 0;
        min-height: 100vh;
      }

      .srp-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 20px;
        margin: 0 0 30px 0;
      }

      .srp-header-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .srp-title {
        font-size: 32px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .srp-title .dashicons {
        font-size: 36px;
      }

      .srp-subtitle {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 16px;
      }

      .srp-main-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
      }

      .srp-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
      }

      .srp-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: all 0.3s ease;
      }

      .srp-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
      }

      .srp-card-primary {
        border-top: 4px solid #667eea;
      }

      .srp-card-header {
        padding: 24px;
        border-bottom: 1px solid #e5e5e5;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .srp-card-header h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 600;
        color: #1e1e1e;
      }

      .srp-card-header p {
        margin: 4px 0 0 0;
        color: #757575;
        font-size: 14px;
      }

      .srp-card-body {
        padding: 24px;
      }

      .srp-form-group {
        margin-bottom: 20px;
      }

      .srp-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
      }

      .srp-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #1e1e1e;
        margin-bottom: 8px;
      }

      .srp-input,
      .srp-select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e5e5;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
        background: white;
      }

      .srp-input:focus,
      .srp-select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      }

      .srp-help-text {
        font-size: 13px;
        color: #757575;
        margin-top: 6px;
      }

      .srp-button {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
      }

      .srp-button-primary {
        background: #667eea;
        color: white;
      }

      .srp-button-primary:hover {
        background: #5a6fd8;
        transform: translateY(-2px);
      }

      .srp-button-secondary {
        background: #f8f9fa;
        color: #495057;
        border: 1px solid #dee2e6;
      }

      .srp-button-secondary:hover {
        background: #e9ecef;
        color: #495057;
      }

      .srp-button-danger {
        background: #dc3545;
        color: white;
      }

      .srp-button-danger:hover {
        background: #c82333;
      }

      .srp-button-large {
        padding: 16px 32px;
        font-size: 16px;
      }

      .srp-button-small {
        padding: 8px 16px;
        font-size: 12px;
      }

      .srp-button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }

      .srp-button.loading {
        opacity: 0.8;
      }

      .srp-button-icon {
        font-size: 16px;
      }

      .srp-status-message {
        background: #e3f2fd;
        border: 1px solid #90caf9;
        border-radius: 8px;
        padding: 16px;
        margin-top: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #1565c0;
      }

      .srp-success-message {
        background: #e8f5e8;
        border: 1px solid #4caf50;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        color: #2e7d32;
      }

      .srp-success-icon {
        background: #4caf50;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }

      .srp-success-content h4 {
        margin: 0 0 12px 0;
        color: #2e7d32;
      }

      .srp-success-actions {
        display: flex;
        gap: 12px;
      }

      .srp-spinner {
        width: 20px;
        height: 20px;
        border: 2px solid #e3f2fd;
        border-top: 2px solid #1565c0;
        border-radius: 50%;
        animation: spin 1s linear infinite;
      }

      @keyframes spin {
        0% {
          transform: rotate(0deg);
        }

        100% {
          transform: rotate(360deg);
        }
      }

      .srp-notice {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin: 0 20px 30px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        border-left: 4px solid #ff9800;
      }

      .srp-notice-warning {
        border-left-color: #ff9800;
      }

      .srp-notice-icon {
        color: #ff9800;
        font-size: 24px;
      }

      .srp-notice-content h3 {
        margin: 0 0 8px 0;
        color: #1e1e1e;
      }

      .srp-notice-content p {
        margin: 0 0 16px 0;
        color: #757575;
      }

      .srp-stat-card {
        background: rgba(255, 255, 255, 0.2);
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        backdrop-filter: blur(10px);
      }

      .srp-stat-number {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 4px;
      }

      .srp-stat-label {
        font-size: 14px;
        opacity: 0.9;
      }

      .srp-recordings-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
      }

      .srp-recording-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px;
        background: #f8f9fa;
        border-radius: 8px;
        transition: all 0.3s ease;
      }

      .srp-recording-item:hover {
        background: #e9ecef;
      }

      .srp-recording-title {
        font-weight: 600;
        color: #1e1e1e;
        margin-bottom: 4px;
      }

      .srp-recording-url {
        font-size: 12px;
        color: #757575;
        margin-bottom: 4px;
      }

      .srp-recording-date {
        font-size: 12px;
        color: #6c757d;
      }

      .srp-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
      }

      .srp-link:hover {
        color: #5a6fd8;
      }

      /* Recordings Grid */
      .srp-recordings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
        padding: 0 20px;
      }

      .srp-recording-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
      }

      .srp-recording-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
      }

      .srp-recording-preview {
        position: relative;
        aspect-ratio: 16/9;
        background: #f8f9fa;
        overflow: hidden;
      }

      .srp-preview-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }

      .srp-preview-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-size: 48px;
      }

      .srp-preview-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
      }

      .srp-recording-preview:hover .srp-preview-overlay {
        opacity: 1;
      }

      .srp-preview-btn {
        background: white;
        border: none;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
      }

      .srp-preview-btn:hover {
        transform: scale(1.1);
      }

      .srp-preview-btn .dashicons {
        font-size: 24px;
        color: #333;
      }

      .srp-recording-content {
        padding: 20px;
      }

      .srp-recording-title {
        font-size: 16px;
        font-weight: 600;
        color: #1e1e1e;
        margin: 0 0 4px 0;
      }

      .srp-recording-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 12px 0;
      }

      .srp-status {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
      }

      .srp-status-completed {
        background: #d4edda;
        color: #155724;
      }

      .srp-status-processing {
        background: #fff3cd;
        color: #856404;
      }

      .srp-status-failed {
        background: #f8d7da;
        color: #721c24;
      }

      .srp-date {
        font-size: 12px;
        color: #6c757d;
      }

      .srp-shortcode-section {
        margin: 16px 0;
      }

      .srp-shortcode-label {
        font-size: 12px;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 8px;
        display: block;
      }

      .srp-shortcode-input-group {
        display: flex;
        gap: 8px;
      }

      .srp-shortcode-input {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        font-family: monospace;
        font-size: 12px;
        background: #f8f9fa;
      }

      .srp-copy-btn {
        padding: 8px;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
      }

      .srp-copy-btn:hover {
        background: #f8f9fa;
      }

      .srp-copy-btn.copied {
        background: #d4edda;
        color: #155724;
      }

      .srp-recording-actions {
        display: flex;
        gap: 8px;
        margin-top: 16px;
      }

      /* Modal */
      .srp-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 999999;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .srp-modal-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
      }

      .srp-modal-container {
        position: relative;
        background: white;
        border-radius: 12px;
        width: 90%;
        max-width: 800px;
        max-height: 90%;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
      }

      .srp-modal-header {
        padding: 20px;
        border-bottom: 1px solid #e5e5e5;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .srp-modal-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
      }

      .srp-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #6c757d;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.3s ease;
      }

      .srp-modal-close:hover {
        background: #f8f9fa;
        color: #495057;
      }

      .srp-modal-body {
        padding: 20px;
      }

      #srp-modal-video {
        width: 100%;
        max-height: 500px;
        border-radius: 8px;
      }

      /* Empty State */
      .srp-empty-state {
        text-align: center;
        padding: 80px 20px;
        color: #6c757d;
      }

      .srp-empty-icon {
        font-size: 80px;
        margin-bottom: 20px;
        opacity: 0.5;
      }

      .srp-empty-state h2 {
        font-size: 24px;
        color: #495057;
        margin-bottom: 12px;
      }

      .srp-empty-state p {
        font-size: 16px;
        margin-bottom: 24px;
      }

      /* Responsive */
      @media (max-width: 768px) {
        .srp-grid {
          grid-template-columns: 1fr;
        }

        .srp-header-content {
          flex-direction: column;
          text-align: center;
          gap: 20px;
        }

        .srp-form-row {
          grid-template-columns: 1fr;
        }

        .srp-recordings-grid {
          grid-template-columns: 1fr;
        }

        .srp-success-actions {
          flex-direction: column;
        }
      }
    </style>
<?php
  }
}
?>