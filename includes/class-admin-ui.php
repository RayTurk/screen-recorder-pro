
      $capability = 'manage_options';
    }

    error_log('SRP: Using capability: ' . $capability);

    // Add main menu page
    $main_page = add_menu_page(
      __('Screen Recorder Pro', 'screen-recorder-pro'),
      __('Screen Recorder', 'screen-recorder-pro'),
      $capability,
      'screen-recorder',
      [$this, 'render_main_page'],
      'dashicons-video-alt3',
      30
    );

    error_log('SRP: Main page result: ' . ($main_page ? $main_page : 'FAILED'));

    // Add submenu pages only if main page was successful
    if ($main_page) {
      // All Recordings submenu
      $recordings_page = add_submenu_page(
        'screen-recorder',
        __('All Recordings', 'screen-recorder-pro'),
        __('All Recordings', 'screen-recorder-pro'),
        $capability,
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

      // Account submenu
      add_submenu_page(
        'screen-recorder',
        __('Account', 'screen-recorder-pro'),
        __('Account', 'screen-recorder-pro'),
        $capability,
        'screen-recorder-account',
        [$this, 'render_account_page']
      );

      // Debug submenu (only for admins and when WP_DEBUG is on)
      if ((current_user_can('manage_options') || is_super_admin()) && defined('WP_DEBUG') && WP_DEBUG) {
        add_submenu_page(
          'screen-recorder',
          __('Debug Info', 'screen-recorder-pro'),
          __('Debug Info', 'screen-recorder-pro'),
          'manage_options',
          'screen-recorder-debug',
          [$this, 'render_debug_page']
        );
      }

      error_log('SRP: Recordings page result: ' . ($recordings_page ? $recordings_page : 'FAILED'));
    } else {
      error_log('SRP: Failed to create main menu page');
    }
  }

  /**
   * Main dashboard page
   */
  public function render_main_page()
  {
    if (!current_user_can('edit_posts')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $recordings_manager = new SRP_Recordings_Manager();
    $total_recordings = $recordings_manager->get_count_by_status('completed');
    $current_usage = $this->get_current_usage();
    $monthly_limit = $this->get_usage_limit();
    $is_premium = function_exists('srp_fs') ? srp_fs()->is_paying() : false;

    ?>
    <div class="wrap srp-dashboard">
      <?php $this->render_modern_styles(); ?>

      <div class="srp-header">
        <div class="srp-header-content">
          <div class="srp-header-text">
            <h1 class="srp-title">
              <span class="dashicons dashicons-video-alt3"></span>
              <?php _e('Screen Recorder Pro', 'screen-recorder-pro'); ?>
              <span class="srp-version">v<?php echo SRP_VERSION; ?></span>
            </h1>
            <p class="srp-subtitle"><?php _e('Create engaging screen recordings of your WordPress content', 'screen-recorder-pro'); ?></p>
          </div>
          <div class="srp-header-stats">
            <div class="srp-stat-card">
              <div class="srp-stat-number"><?php echo $total_recordings; ?></div>
              <div class="srp-stat-label"><?php _e('Total Recordings', 'screen-recorder-pro'); ?></div>
            </div>
            <div class="srp-stat-card">
              <div class="srp-stat-number"><?php echo $is_premium ? $current_usage . '/' . $monthly_limit : ($current_usage >= 1 ? '1/1' : '0/1'); ?></div>
              <div class="srp-stat-label"><?php echo $is_premium ? __('This Month', 'screen-recorder-pro') : __('Free Trial', 'screen-recorder-pro'); ?></div>
            </div>
          </div>
        </div>
      </div>

      <?php if (!$is_premium): ?>
        <?php if ($current_usage >= 1): ?>
          <!-- Free recording used - show upgrade prompt -->
          <div class="srp-notice srp-notice-upgrade">
            <span class="srp-notice-icon dashicons dashicons-star-filled"></span>
            <div class="srp-notice-content">
              <h3><?php _e('Free Recording Used!', 'screen-recorder-pro'); ?></h3>
              <p>
                <?php printf(
                  __('You\'ve used your free recording. <a href="%s" target="_blank"><strong>Upgrade to Pro</strong></a> to create unlimited recordings starting at just $9/month.', 'screen-recorder-pro'),
                  function_exists('srp_fs') ? srp_fs()->get_upgrade_url() : '#'
                ); ?>
              </p>
              <div class="srp-upgrade-buttons">
                <a href="<?php echo function_exists('srp_fs') ? srp_fs()->get_upgrade_url() : '#'; ?>"
                   class="srp-button srp-button-primary" target="_blank">
                  <span class="dashicons dashicons-star-filled"></span>
                  <?php _e('Upgrade Now', 'screen-recorder-pro'); ?>
                </a>
              </div>
            </div>
          </div>
        <?php else: ?>
          <!-- Free recording available -->
          <div class="srp-notice srp-notice-info">
            <span class="srp-notice-icon dashicons dashicons-info"></span>
            <div class="srp-notice-content">
              <h3><?php _e('Try Screen Recorder Pro Free!', 'screen-recorder-pro'); ?></h3>
              <p>
                <?php _e('Create your first recording for free to see how it works. Then upgrade for unlimited recordings.', 'screen-recorder-pro'); ?>
              </p>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <div class="srp-main-content">
        <div class="srp-grid">
          <!-- Quick Recording Form -->
          <div class="srp-card srp-card-primary">
            <div class="srp-card-header">
              <h2>
                <?php if (!$is_premium && $current_usage >= 1): ?>
                  <?php _e('Create More Recordings', 'screen-recorder-pro'); ?>
                <?php else: ?>
                  <?php _e('Create New Recording', 'screen-recorder-pro'); ?>
                <?php endif; ?>
              </h2>
              <p>
                <?php if (!$is_premium && $current_usage >= 1): ?>
                  <?php _e('Upgrade to create unlimited recordings', 'screen-recorder-pro'); ?>
                <?php else: ?>
                  <?php _e('Record any URL or WordPress page/post', 'screen-recorder-pro'); ?>
                <?php endif; ?>
              </p>
            </div>
            <div class="srp-card-body">
              <?php if (!$is_premium && $current_usage >= 1): ?>
                <!-- Show upgrade form instead of recording form -->
                <div class="srp-upgrade-form">
                  <div class="srp-upgrade-icon">
                    <span class="dashicons dashicons-star-filled"></span>
                  </div>
                  <h3><?php _e('Upgrade to Continue Recording', 'screen-recorder-pro'); ?></h3>
                  <p><?php _e('You\'ve used your free recording. Choose a plan to create unlimited recordings:', 'screen-recorder-pro'); ?></p>

                  <div class="srp-pricing-cards">
                    <div class="srp-pricing-card">
                      <h4>Starter</h4>
                      <div class="srp-price">$9<span>/month</span></div>
                      <ul>
                        <li>25 recordings/month</li>
                        <li>Up to 7 second duration</li>
                        <li>All device frames</li>
                        <li>No watermarks</li>
                      </ul>
                      <a href="<?php echo function_exists('srp_fs') ? srp_fs()->get_upgrade_url() : '#'; ?>"
                         class="srp-button srp-button-primary">Choose Starter</a>
                    </div>

                    <div class="srp-pricing-card srp-recommended">
                      <div class="srp-recommended-badge">Most Popular</div>
                      <h4>Pro</h4>
                      <div class="srp-price">$19<span>/month</span></div>
                      <ul>
                        <li>100 recordings/month</li>
                        <li>Up to 7 second duration</li>
                        <li>Premium device frames</li>
                        <li>Priority support</li>
                      </ul>
                      <a href="<?php echo function_exists('srp_fs') ? srp_fs()->get_upgrade_url() : '#'; ?>"
                         class="srp-button srp-button-primary">Choose Pro</a>
                    </div>
                  </div>
                </div>
              <?php else: ?>
                <!-- Show normal recording form -->
                <form id="srp-quick-record-form" class="srp-form">
                  <?php wp_nonce_field('srp_ajax_nonce', 'srp_nonce'); ?>

                  <?php if (!$is_premium): ?>
                    <div class="srp-free-trial-banner">
                      <span class="dashicons dashicons-star-filled"></span>
                      <strong><?php _e('Free Trial Recording', 'screen-recorder-pro'); ?></strong>
                      <span><?php _e('- See how it works before upgrading', 'screen-recorder-pro'); ?></span>
                    </div>
                  <?php endif; ?>

                  <div class="srp-form-group">
                    <label for="record_url" class="srp-label">
                      <span class="dashicons dashicons-admin-links"></span>
                      <?php _e('URL to Record', 'screen-recorder-pro'); ?>
                    </label>
                    <input type="url" id="record_url" name="url" class="srp-input"
                      placeholder="https://example.com" required />
                  </div>

                  <div class="srp-form-row">
                    <div class="srp-form-group">
                      <label for="record_device" class="srp-label">
                        <span class="dashicons dashicons-smartphone"></span>
                        <?php _e('Device/Viewport', 'screen-recorder-pro'); ?>
                      </label>
                      <select id="record_device" name="device" class="srp-select">
                        <?php foreach (ScreenRecorderPro::get_device_viewport_options() as $key => $device): ?>
                          <option value="<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($device['name']); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="srp-form-group">
                      <label for="record_duration" class="srp-label">
                        <span class="dashicons dashicons-clock"></span>
                        <?php _e('Duration (seconds)', 'screen-recorder-pro'); ?>
                      </label>
                      <select id="record_duration" name="duration" class="srp-select">
                        <?php
                        $duration_options = ScreenRecorderPro::get_duration_options();
                        foreach ($duration_options as $value => $label): ?>
                          <option value="<?php echo esc_attr($value); ?>" <?php selected($value, '5'); ?>>
                            <?php echo esc_html($label); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <div class="srp-help-text">
                        <?php if ($is_premium): ?>
                          <?php _e('Premium: Up to 7 seconds. Longer recordings available when we upgrade our infrastructure at 10+ subscribers.', 'screen-recorder-pro'); ?>
                        <?php else: ?>
                          <?php printf(
                            __('Free: Up to 5 seconds. <a href="%s" target="_blank">Upgrade to Pro</a> for 7-second recordings and priority processing.', 'screen-recorder-pro'),
                            function_exists('srp_fs') ? srp_fs()->get_upgrade_url() : '#'
                          ); ?>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                  <div class="srp-form-group">
                    <label class="srp-checkbox-label">
                      <input type="checkbox" name="show_device_frame" value="1" checked>
                      <span class="srp-checkbox-custom"></span>
                      <span class="srp-label-text">
                        <span class="dashicons dashicons-smartphone"></span>
                        <?php _e('Show device frame (when available)', 'screen-recorder-pro'); ?>
                      </span>
                    </label>
                    <div class="srp-help-text">
                      <?php _e('Display the recording inside a realistic device mockup for better presentation.', 'screen-recorder-pro'); ?>
                    </div>
                  </div>

                  <button type="submit" class="srp-button srp-button-primary srp-button-large" id="srp-record-btn">
                    <span class="dashicons dashicons-video-alt3"></span>
                    <?php if (!$is_premium): ?>
                      <?php _e('Create Free Recording', 'screen-recorder-pro'); ?>
                    <?php else: ?>
                      <?php _e('Create Recording', 'screen-recorder-pro'); ?>
                    <?php endif; ?>
                  </button>
                </form>

                <div id="srp-recording-status" class="srp-status-message" style="display: none;">
                  <span class="srp-spinner"></span>
                  <span id="srp-status-text"><?php _e('Creating recording...', 'screen-recorder-pro'); ?></span>
                </div>

                <div id="srp-recording-success" class="srp-success-message" style="display: none;">
                  <div class="srp-success-icon">
                    <span class="dashicons dashicons-yes"></span>
                  </div>
                  <div class="srp-success-content">
                    <h4><?php _e('Recording Created Successfully!', 'screen-recorder-pro'); ?></h4>
                    <p>
                      <?php if (!$is_premium): ?>
                        <?php _e('Your free trial recording is ready! Upgrade now to create unlimited recordings.', 'screen-recorder-pro'); ?>
                      <?php else: ?>
                        <?php _e('Your screen recording is ready to use.', 'screen-recorder-pro'); ?>
                      <?php endif; ?>
                    </p>
                    <div class="srp-success-actions">
                      <a href="#" id="srp-view-recording" class="srp-button srp-button-primary">
                        <?php _e('View Recording', 'screen-recorder-pro'); ?>
                      </a>
                      <?php if (!$is_premium): ?>
                        <a href="<?php echo function_exists('srp_fs') ? srp_fs()->get_upgrade_url() : '#'; ?>"
                           class="srp-button srp-button-secondary" target="_blank">
                          <?php _e('Upgrade Now', 'screen-recorder-pro'); ?>
                        </a>
                      <?php else: ?>
                        <a href="<?php echo admin_url('admin.php?page=screen-recorder-all'); ?>" class="srp-button srp-button-secondary">
                          <?php _e('View All Recordings', 'screen-recorder-pro'); ?>
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Recent Recordings Sidebar -->
          <div class="srp-card">
            <div class="srp-card-header">
              <h2><?php _e('Recent Recordings', 'screen-recorder-pro'); ?></h2>
              <a href="<?php echo admin_url('admin.php?page=screen-recorder-all'); ?>" class="srp-link">
                <?php _e('View All', 'screen-recorder-pro'); ?>
              </a>
            </div>
            <div class="srp-card-body">
              <?php $this->render_recent_recordings_list(); ?>
            </div>
          </div>
        </div>

        <!-- Usage Stats -->
        <div class="srp-card">
          <div class="srp-card-header">
            <h2>
              <?php if ($is_premium): ?>
                <?php _e('Usage This Month', 'screen-recorder-pro'); ?>
              <?php else: ?>
                <?php _e('Free Trial Status', 'screen-recorder-pro'); ?>
              <?php endif; ?>
            </h2>
          </div>
          <div class="srp-card-body">
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
              <div style="font-size: 24px; font-weight: bold; margin-bottom: 8px;">
                <?php if ($is_premium): ?>
                  <?php echo $current_usage; ?> / <?php echo $monthly_limit; ?>
                <?php else: ?>
                  <?php echo $current_usage >= 1 ? 'Used' : 'Available'; ?>
                <?php endif; ?>
              </div>
              <div style="color: #6c757d; margin-bottom: 16px;">
                <?php if ($is_premium): ?>
                  <?php _e('Recordings This Month', 'screen-recorder-pro'); ?>
                <?php else: ?>
                  <?php _e('Free Trial Recording', 'screen-recorder-pro'); ?>
                <?php endif; ?>
              </div>
              <?php if (!$is_premium): ?>
                <div style="background: #e9ecef; height: 8px; border-radius: 4px; margin-bottom: 16px;">
                  <div style="background: <?php echo $current_usage >= 1 ? '#dc3545' : '#28a745'; ?>; height: 100%; border-radius: 4px; width: <?php echo $current_usage >= 1 ? '100' : '0'; ?>%;"></div>
                </div>
                <a href="<?php echo function_exists('srp_fs') ? srp_fs()->get_upgrade_url() : '#'; ?>" class="srp-button srp-button-primary">
                  <?php _e('Upgrade for Unlimited Recordings', 'screen-recorder-pro'); ?>
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      jQuery(document).ready(function($) {
        $('#srp-quick-record-form').on('submit', function(e) {
          e.preventDefault();

          const $form = $(this);
          const $btn = $('#srp-record-btn');
          const $status = $('#srp-recording-status');
          const $success = $('#srp-recording-success');

          // Show loading state
          $btn.prop('disabled', true).addClass('loading');
          $status.show();
          $success.hide();

          // Prepare form data
          const formData = {
            action: 'srp_create_recording',
            nonce: $('#srp_nonce').val(),
            url: $('#record_url').val(),
            device: $('#record_device').val(),
            duration: $('#record_duration').val(),
            show_device_frame: $('#show_device_frame').is(':checked') ? 1 : 0
          };

          // Make AJAX request
          $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
              if (response.success) {
                $status.hide();
                $success.show();

                // Update view recording link
                if (response.data.video_url) {
                  $('#srp-view-recording').attr('href', response.data.video_url);
                }

                // Reset form
                $form[0].reset();
              } else {
                alert(response.data.message || 'An error occurred');
                $status.hide();
              }
            },
            error: function() {
              alert('Network error occurred');
              $status.hide();
            },
            complete: function() {
              $btn.prop('disabled', false).removeClass('loading');
            }
          });
        });
      });
    </script>
    <?php
  }

  /**
   * All Recordings page
   */
  public function render_recordings_page()
  {
    if (!current_user_can('edit_posts')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

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
            <div class="srp-recording-card">
              <div class="srp-recording-preview">
                <?php if ($recording->attachment_id): ?>
                  <video class="srp-preview-video" poster="">
                    <source src="<?php echo wp_get_attachment_url($recording->attachment_id); ?>" type="video/mp4">
                  </video>
                  <div class="srp-preview-overlay">
                    <button class="srp-preview-btn" onclick="srpPlayVideo('<?php echo wp_get_attachment_url($recording->attachment_id); ?>', '<?php echo esc_js($recording->url); ?>')">
                      <span class="dashicons dashicons-controls-play"></span>
                    </button>
                  </div>
                <?php else: ?>
                  <div class="srp-preview-placeholder">
                    <span class="dashicons dashicons-format-video"></span>
                  </div>
                <?php endif; ?>
              </div>

              <div class="srp-recording-content">
                <h3 class="srp-recording-title">
                  <?php echo esc_html($recording->url); ?>
                </h3>

                <div class="srp-recording-meta">
                  <span class="srp-status srp-status-<?php echo esc_attr($recording->status); ?>">
                    <?php echo esc_html(ucfirst($recording->status)); ?>
                  </span>
                  <span class="srp-recording-date">
                    <?php echo human_time_diff(strtotime($recording->created_at), current_time('timestamp')) . ' ago'; ?>
                  </span>
                </div>

                <?php if ($recording->options): ?>
                  <div class="srp-recording-device">
                    <?php
                    // Handle already unserialized data
                    $options = $recording->options;

                    // If it's still a string, try to decode it
                    if (is_string($options)) {
                      // Try JSON decode first, then unserialize as fallback
                      $decoded_options = json_decode($options, true);
                      if ($decoded_options === null && json_last_error() !== JSON_ERROR_NONE) {
                        // If JSON decode fails, try unserialize
                        $options = maybe_unserialize($options);
                      } else {
                        $options = $decoded_options;
                      }
                    }

                    // Now safely access the options array
                    if (is_array($options)) {
                      $device_key = $options['device_key'] ?? 'unknown';
                      $device_options = ScreenRecorderPro::get_device_viewport_options();
                      $device_name = $device_options[$device_key]['name'] ?? 'Unknown Device';
                      echo esc_html($device_name);

                      if (!empty($options['show_device_frame'])): ?>
                        <span class="srp-frame-indicator" title="Device frame enabled">
                          <span class="dashicons dashicons-smartphone"></span>
                        </span>
                      <?php endif;
                    } else {
                      echo 'Unknown Device';
                    }
                    ?>
                  </div>
                <?php endif; ?>

                <?php if ($recording->attachment_id): ?>
                  <div class="srp-shortcode-section">
                    <div class="srp-shortcode-label">
                      <span class="dashicons dashicons-shortcode"></span>
                      <?php _e('Shortcode', 'screen-recorder-pro'); ?>
                    </div>
                    <div class="srp-shortcode-input-group">
                      <input type="text" class="srp-shortcode-input" readonly
                        value='[screen_recording id="<?php echo $recording->attachment_id; ?>"]'>
                      <button class="srp-copy-btn" onclick="srpCopyShortcode(this)" title="Copy shortcode">
                        <span class="dashicons dashicons-admin-page"></span>
                      </button>
                    </div>
                  </div>
                <?php endif; ?>

                <div class="srp-recording-actions">
                  <?php if ($recording->attachment_id): ?>
                    <a href="<?php echo wp_get_attachment_url($recording->attachment_id); ?>"
                      class="srp-button srp-button-secondary srp-button-small" target="_blank">
                      <span class="dashicons dashicons-download"></span>
                      <?php _e('Download', 'screen-recorder-pro'); ?>
                    </a>
                  <?php endif; ?>

                  <button class="srp-button srp-button-danger srp-button-small"
                    onclick="srpDeleteRecording(<?php echo $recording->id; ?>)">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Delete', 'screen-recorder-pro'); ?>
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Video Modal -->
    <div id="srp-video-modal" class="srp-modal" style="display: none;">
      <div class="srp-modal-backdrop" onclick="srpCloseModal()"></div>
      <div class="srp-modal-container">
        <div class="srp-modal-header">
          <h3 id="srp-modal-title"><?php _e('Screen Recording', 'screen-recorder-pro'); ?></h3>
          <button class="srp-modal-close" onclick="srpCloseModal()">
            <span class="dashicons dashicons-no-alt"></span>
          </button>
        </div>
        <div class="srp-modal-body">
          <video id="srp-modal-video" controls style="width: 100%; max-height: 500px;">
            <source src="" type="video/mp4">
          </video>
        </div>
      </div>
    </div>

    <script>
      function srpPlayVideo(videoUrl, title) {
        const modal = document.getElementById('srp-video-modal');
        const video = document.getElementById('srp-modal-video');
        const titleEl = document.getElementById('srp-modal-title');

        video.src = videoUrl;
        titleEl.textContent = title;
        modal.style.display = 'flex';

        video.play();
      }

      function srpCloseModal() {
        const modal = document.getElementById('srp-video-modal');
        const video = document.getElementById('srp-modal-video');

        video.pause();
        video.src = '';
        modal.style.display = 'none';
      }

      function srpCopyShortcode(btn) {
        const input = btn.previousElementSibling;
        input.select();
        document.execCommand('copy');

        const icon = btn.querySelector('.dashicons');
        const originalClass = icon.className;

        icon.className = 'dashicons dashicons-yes';
        btn.classList.add('copied');

        setTimeout(() => {
          icon.className = originalClass;
          btn.classList.remove('copied');
        }, 2000);
      }

      function srpDeleteRecording(recordingId) {
        if (!confirm('Are you sure you want to delete this recording?')) {
          return;
        }

        jQuery.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            action: 'srp_delete_recording',
            recording_id: recordingId,
            nonce: '<?php echo wp_create_nonce('srp_ajax_nonce'); ?>'
          },
          success: function(response) {
            if (response.success) {
              location.reload();
            } else {
              alert(response.data.message || 'Failed to delete recording');
            }
          },
          error: function() {
            alert('Network error occurred');
          }
        });
      }
    </script>
    <?php
  }

  /**
   * Settings page - Clean and focused
   */
  public function render_settings_page()
  {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle form submission
    if (isset($_POST['srp_settings_nonce']) && wp_verify_nonce($_POST['srp_settings_nonce'], 'srp_save_settings')) {
      $settings = [
        'default_duration' => intval($_POST['default_duration'] ?? 5),
        'default_device' => sanitize_text_field($_POST['default_device'] ?? 'mobile_iphone_xr'),
        'show_device_frame' => isset($_POST['show_device_frame']) ? 1 : 0,
        'auto_add_to_media' => isset($_POST['auto_add_to_media']) ? 1 : 0,
        'cleanup_old_recordings' => intval($_POST['cleanup_old_recordings'] ?? 30)
      ];

      update_option('srp_settings', $settings);
      echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'screen-recorder-pro') . '</p></div>';
    }

    $settings = get_option('srp_settings', [
      'default_duration' => 5,
      'default_device' => 'mobile_iphone_xr',
      'show_device_frame' => 1,
      'auto_add_to_media' => 1,
      'cleanup_old_recordings' => 30
    ]);

    ?>
    <div class="wrap srp-dashboard">
      <?php $this->render_modern_styles(); ?>

      <div class="srp-header">
        <div class="srp-header-content">
          <div class="srp-header-text">
            <h1 class="srp-title">
              <span class="dashicons dashicons-admin-settings"></span>
              <?php _e('Settings', 'screen-recorder-pro'); ?>
            </h1>
            <p class="srp-subtitle"><?php _e('Configure your recording preferences and plugin behavior', 'screen-recorder-pro'); ?></p>
          </div>
        </div>
      </div>

      <div class="srp-main-content">
        <form method="post" class="srp-settings-form">
          <?php wp_nonce_field('srp_save_settings', 'srp_settings_nonce'); ?>

          <!-- Recording Defaults -->
          <div class="srp-card">
            <div class="srp-card-header">
              <h2><?php _e('Default Recording Settings', 'screen-recorder-pro'); ?></h2>
              <p><?php _e('Set default options for new recordings', 'screen-recorder-pro'); ?></p>
            </div>
            <div class="srp-card-body">
              <table class="form-table">
                <tr>
                  <th scope="row">
                    <label for="default_duration"><?php _e('Default Duration', 'screen-recorder-pro'); ?></label>
                  </th>
                  <td>
                    <select name="default_duration" id="default_duration" class="regular-text">
                      <?php
                      $duration_options = ScreenRecorderPro::get_duration_options();
                      foreach ($duration_options as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['default_duration'], $value); ?>>
                          <?php echo esc_html($label); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Default recording length for new recordings', 'screen-recorder-pro'); ?></p>
                  </td>
                </tr>

                <tr>
                  <th scope="row">
                    <label for="default_device"><?php _e('Default Device/Viewport', 'screen-recorder-pro'); ?></label>
                  </th>
                  <td>
                    <select name="default_device" id="default_device" class="regular-text">
                      <?php
                      $device_options = ScreenRecorderPro::get_device_viewport_options();
                      foreach ($device_options as $key => $device): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['default_device'], $key); ?>>
                          <?php echo esc_html($device['name']); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Default device frame for new recordings', 'screen-recorder-pro'); ?></p>
                  </td>
                </tr>

                <tr>
                  <th scope="row"><?php _e('Device Frame', 'screen-recorder-pro'); ?></th>
                  <td>
                    <label>
                      <input type="checkbox" name="show_device_frame" value="1" <?php checked($settings['show_device_frame'], 1); ?>>
                      <?php _e('Show device frame by default', 'screen-recorder-pro'); ?>
                    </label>
                    <p class="description"><?php _e('Display recordings inside device mockups when available', 'screen-recorder-pro'); ?></p>
                  </td>
                </tr>
              </table>
            </div>
          </div>

          <!-- Media Library Settings -->
          <div class="srp-card">
            <div class="srp-card-header">
              <h2><?php _e('Media Library', 'screen-recorder-pro'); ?></h2>
              <p><?php _e('Control how recordings are handled in your media library', 'screen-recorder-pro'); ?></p>
            </div>
            <div class="srp-card-body">
              <table class="form-table">
                <tr>
                  <th scope="row"><?php _e('Auto-add to Media Library', 'screen-recorder-pro'); ?></th>
                  <td>
                    <label>
                      <input type="checkbox" name="auto_add_to_media" value="1" <?php checked($settings['auto_add_to_media'], 1); ?>>
                      <?php _e('Automatically add recordings to WordPress Media Library', 'screen-recorder-pro'); ?>
                    </label>
                    <p class="description"><?php _e('Recordings will appear in your Media Library for easy access', 'screen-recorder-pro'); ?></p>
                  </td>
                </tr>

                <tr>
                  <th scope="row">
                    <label for="cleanup_old_recordings"><?php _e('Cleanup Old Recordings', 'screen-recorder-pro'); ?></label>
                  </th>
                  <td>
                    <select name="cleanup_old_recordings" id="cleanup_old_recordings" class="regular-text">
                      <option value="0" <?php selected($settings['cleanup_old_recordings'], 0); ?>><?php _e('Never delete', 'screen-recorder-pro'); ?></option>
                      <option value="7" <?php selected($settings['cleanup_old_recordings'], 7); ?>><?php _e('After 7 days', 'screen-recorder-pro'); ?></option>
                      <option value="30" <?php selected($settings['cleanup_old_recordings'], 30); ?>><?php _e('After 30 days', 'screen-recorder-pro'); ?></option>
                      <option value="90" <?php selected($settings['cleanup_old_recordings'], 90); ?>><?php _e('After 90 days', 'screen-recorder-pro'); ?></option>
                    </select>
                    <p class="description"><?php _e('Automatically clean up old recordings to save storage space', 'screen-recorder-pro'); ?></p>
                  </td>
                </tr>
              </table>
            </div>
          </div>

          <!-- System Information -->
          <div class="srp-card">
            <div class="srp-card-header">
              <h2><?php _e('System Information', 'screen-recorder-pro'); ?></h2>
            </div>
            <div class="srp-card-body">
              <table class="form-table">
                <tr>
                  <th scope="row"><?php _e('Plugin Version', 'screen-recorder-pro'); ?></th>
                  <td><code><?php echo SRP_VERSION; ?></code></td>
                </tr>
                <tr>
                  <th scope="row"><?php _e('API Status', 'screen-recorder-pro'); ?></th>
                  <td>
                    <?php
                    if (class_exists('SRP_API_Proxy')) {
                      $api_proxy = new SRP_API_Proxy();
                      $connection_ok = $api_proxy->test_connection();

                      if ($connection_ok) {
                        echo '<span style="color: #46b450;">✅ ' . __('Connected', 'screen-recorder-pro') . '</span>';
                      } else {
                        echo '<span style="color: #dc3232;">❌ ' . __('Connection Failed', 'screen-recorder-pro') . '</span>';
                      }
                    } else {
                      echo '<span style="color: #dc3232;">❌ ' . __('API Proxy Not Available', 'screen-recorder-pro') . '</span>';
                    }
                    ?>
                  </td>
                </tr>
                <tr>
                  <th scope="row"><?php _e('Total Recordings', 'screen-recorder-pro'); ?></th>
                  <td>
                    <?php
                    if (class_exists('SRP_Recordings_Manager')) {
                      $recordings_manager = new SRP_Recordings_Manager();
                      $total = $recordings_manager->get_count_by_status('completed');
                      echo '<strong>' . $total . '</strong> ' . __('recordings created', 'screen-recorder-pro');
                    }
                    ?>
                  </td>
                </tr>
              </table>
            </div>
          </div>

          <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Settings', 'screen-recorder-pro'); ?>" />
          </p>
        </form>
      </div>
    </div>
    <?php
  }

  /**
   * Account page - License and billing info via Freemius
   */
  public function render_account_page()
  {
    if (!current_user_can('edit_posts')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $is_premium = function_exists('srp_fs') && srp_fs()->is_paying();
    $user_status = null;

    if (class_exists('SRP_API_Proxy')) {
      $api_proxy = new SRP_API_Proxy();
      $user_status = $api_proxy->get_user_status();
    }

    ?>
    <div class="wrap srp-dashboard">
      <?php $this->render_modern_styles(); ?>

      <div class="srp-header">
        <div class="srp-header-content">
          <div class="srp-header-text">
            <h1 class="srp-title">
              <span class="dashicons dashicons-admin-users"></span>
              <?php _e('Account', 'screen-recorder-pro'); ?>
            </h1>
            <p class="srp-subtitle"><?php _e('Manage your license, billing, and account settings', 'screen-recorder-pro'); ?></p>
          </div>
        </div>
      </div>

      <div class="srp-main-content">
        <!-- License Status -->
        <div class="srp-card">
          <div class="srp-card-header">
            <h2><?php _e('License Status', 'screen-recorder-pro'); ?></h2>
          </div>
          <div class="srp-card-body">
            <?php if (function_exists('srp_fs')): ?>
              <table class="form-table">
                <tr>
                  <th scope="row"><?php _e('Plan', 'screen-recorder-pro'); ?></th>
                  <td>
                    <strong style="color: <?php echo $is_premium ? '#46b450' : '#0073aa'; ?>;">
                      <?php echo $is_premium ? __('Pro', 'screen-recorder-pro') : __('Free', 'screen-recorder-pro'); ?>
                    </strong>
                    <?php if (!$is_premium): ?>
                      <a href="<?php echo srp_fs()->get_upgrade_url(); ?>" class="button button-primary" style="margin-left: 10px;">
                        <?php _e('Upgrade to Pro', 'screen-recorder-pro'); ?>
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>

                <?php if ($user_status): ?>
                <tr>
                  <th scope="row"><?php _e('Usage This Month', 'screen-recorder-pro'); ?></th>
                  <td>
                    <strong><?php echo $user_status['current_usage']; ?></strong> /
                    <strong><?php echo $is_premium ? $user_status['usage_limit'] : $user_status['usage_limit']; ?></strong>
                    <?php _e('recordings', 'screen-recorder-pro'); ?>

                    <?php if (!$is_premium && $user_status['current_usage'] >= $user_status['usage_limit']): ?>
                      <br><span style="color: #dc3232;">
                        <?php _e('Limit reached. Upgrade to continue creating recordings.', 'screen-recorder-pro'); ?>
                      </span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endif; ?>

                <tr>
                  <th scope="row"><?php _e('Site URL', 'screen-recorder-pro'); ?></th>
                  <td><code><?php echo get_site_url(); ?></code></td>
                </tr>

                <?php if ($is_premium && srp_fs()->get_license()): ?>
                <tr>
                  <th scope="row"><?php _e('License Key', 'screen-recorder-pro'); ?></th>
                  <td>
                    <code style="background: #f0f0f0; padding: 5px;">
                      <?php echo substr(srp_fs()->get_license()->secret_key, 0, 8) . '...'; ?>
                    </code>
                    <p class="description"><?php _e('Your license is automatically managed by the plugin', 'screen-recorder-pro'); ?></p>
                  </td>
                </tr>
                <?php endif; ?>
              </table>

              <?php if ($is_premium): ?>
                <h3><?php _e('Billing Management', 'screen-recorder-pro'); ?></h3>
                <p>
                  <a href="<?php echo srp_fs()->get_account_url(); ?>" class="button button-secondary" target="_blank">
                    <?php _e('Manage Billing & Subscription', 'screen-recorder-pro'); ?>
                  </a>
                </p>
              <?php endif; ?>

            <?php else: ?>
              <p><?php _e('Freemius integration not available.', 'screen-recorder-pro'); ?></p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Support & Contact -->
        <div class="srp-card">
          <div class="srp-card-header">
            <h2><?php _e('Support & Contact', 'screen-recorder-pro'); ?></h2>
          </div>
          <div class="srp-card-body">
            <div class="srp-support-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
              <div>
                <h4><?php _e('Need Help?', 'screen-recorder-pro'); ?></h4>
                <p><?php _e('Get support for technical questions, feature requests, or billing issues.', 'screen-recorder-pro'); ?></p>

                <?php if (function_exists('srp_fs')): ?>
                  <p>
                    <a href="<?php echo srp_fs()->contact_url(); ?>" class="button button-secondary">
                      <?php _e('Contact Support', 'screen-recorder-pro'); ?>
                    </a>
                  </p>
                <?php else: ?>
                  <p>
                    <a href="mailto:support@yoursite.com" class="button button-secondary">
                      <?php _e('Email Support', 'screen-recorder-pro'); ?>
                    </a>
                  </p>
                <?php endif; ?>
              </div>

              <div>
                <h4><?php _e('Documentation', 'screen-recorder-pro'); ?></h4>
                <p><?php _e('Learn how to get the most out of Screen Recorder Pro.', 'screen-recorder-pro'); ?></p>
                <ul>
                  <li><a href="#" target="_blank"><?php _e('Getting Started Guide', 'screen-recorder-pro'); ?></a></li>
                  <li><a href="#" target="_blank"><?php _e('Using Device Frames', 'screen-recorder-pro'); ?></a></li>
                  <li><a href="#" target="_blank"><?php _e('Shortcode Reference', 'screen-recorder-pro'); ?></a></li>
                  <li><a href="#" target="_blank"><?php _e('FAQ', 'screen-recorder-pro'); ?></a></li>
                </ul>
              </div>
            </div>

            <hr style="margin: 20px 0;">

            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
              <h4 style="margin-top: 0;"><?php _e('Feature Requests & Feedback', 'screen-recorder-pro'); ?></h4>
              <p><?php _e('Have an idea for improving Screen Recorder Pro? We\'d love to hear from you!', 'screen-recorder-pro'); ?></p>
              <p>
                <a href="mailto:feedback@yoursite.com" class="button button-secondary">
                  <?php _e('Send Feedback', 'screen-recorder-pro'); ?>
                </a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
  }

  /**
   * Debug page to help troubleshoot issues
   */
  public function render_debug_page()
  {
    if (!current_user_can('manage_options') && !is_super_admin()) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $current_user = wp_get_current_user();
    ?>
    <div class="wrap">
      <h1>🛠️ Screen Recorder Pro - Debug Information</h1>

      <div style="background: #f9f9f9; padding: 20px; margin: 20px 0;">
        <h2>Plugin Status</h2>
        <table class="widefat">
          <tr>
            <td><strong>Plugin Version:</strong></td>
            <td><?php echo SRP_VERSION; ?></td>
          </tr>
          <tr>
            <td><strong>WordPress Version:</strong></td>
            <td><?php echo get_bloginfo('version'); ?></td>
          </tr>
          <tr>
            <td><strong>PHP Version:</strong></td>
            <td><?php echo PHP_VERSION; ?></td>
          </tr>
          <tr>
            <td><strong>Plugin Active:</strong></td>
            <td><?php echo is_plugin_active(SRP_PLUGIN_BASENAME) ? '✅ YES' : '❌ NO'; ?></td>
          </tr>
          <tr>
            <td><strong>Freemius Loaded:</strong></td>
            <td><?php echo function_exists('srp_fs') ? '✅ YES' : '❌ NO'; ?></td>
          </tr>
          <?php if (function_exists('srp_fs')): ?>
            <tr>
              <td><strong>Freemius Connected:</strong></td>
              <td><?php echo srp_fs()->is_registered() ? '✅ YES' : '❌ NO'; ?></td>
            </tr>
            <tr>
              <td><strong>Premium User:</strong></td>
              <td><?php echo srp_fs()->is_paying() ? '✅ YES' : '❌ NO'; ?></td>
            </tr>
          <?php endif; ?>
        </table>
      </div>

      <div style="background: #f9f9f9; padding: 20px; margin: 20px 0;">
        <h2>User Information</h2>
        <table class="widefat">
          <tr>
            <td><strong>User ID:</strong></td>
            <td><?php echo $current_user->ID; ?></td>
          </tr>
          <tr>
            <td><strong>Username:</strong></td>
            <td><?php echo $current_user->user_login; ?></td>
          </tr>
          <tr>
            <td><strong>User Roles:</strong></td>
            <td><?php echo implode(', ', $current_user->roles); ?></td>
          </tr>
          <tr>
            <td><strong>Is Super Admin:</strong></td>
            <td><?php echo is_super_admin() ? '✅ YES' : '❌ NO'; ?></td>
          </tr>
          <tr>
            <td><strong>Can manage_options:</strong></td>
            <td><?php echo current_user_can('manage_options') ? '✅ YES' : '❌ NO'; ?></td>
          </tr>
          <tr>
            <td><strong>Can edit_posts:</strong></td>
            <td><?php echo current_user_can('edit_posts') ? '✅ YES' : '❌ NO'; ?></td>
          </tr>
        </table>
      </div>

      <div style="background: #d4edda; padding: 20px; margin: 20px 0;">
        <h2>🔗 Direct Access Links</h2>
        <ul>
          <li><a href="<?php echo admin_url('admin.php?page=screen-recorder'); ?>">Main Dashboard</a></li>
          <li><a href="<?php echo admin_url('admin.php?page=screen-recorder-all'); ?>">All Recordings</a></li>
          <li><a href="<?php echo admin_url('admin.php?page=screen-recorder-settings'); ?>">Settings</a></li>
          <li><a href="<?php echo admin_url('admin.php?page=screen-recorder-account'); ?>">Account</a></li>
        </ul>
      </div>
    </div>
    <?php
  }

  /**
   * Helper method to get current usage
   */
  private function get_current_usage()
  {
    if (class_exists('SRP_Recordings_Manager')) {
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
    return 0;
  }

  /**
   * Helper method to get usage limit
   */
  private function get_usage_limit()
  {
    if (function_exists('srp_fs') && srp_fs()->is_paying()) {
      $plan = srp_fs()->get_plan();
      if ($plan) {
        switch (strtolower($plan->name)) {
          case 'starter': return 25;
          case 'pro': return 100;
          case 'agency': return 300;
        }
      }
      return 100; // Default for pro users
    }
    return 1; // Free plan limit
  }

  /**
   * Render recent recordings list
   */
  private function render_recent_recordings_list()
  {
    if (class_exists('SRP_Recordings_Manager')) {
      $recordings_manager = new SRP_Recordings_Manager();
      $recent_recordings = $recordings_manager->get_recent(5);

      if (empty($recent_recordings)) {
        echo '<p style="color: #6c757d; text-align: center; padding: 20px;">';
        echo __('No recordings yet', 'screen-recorder-pro');
        echo '</p>';
        return;
      }

      echo '<div class="srp-recordings-list">';
      foreach ($recent_recordings as $recording) {
        echo '<div class="srp-recording-item">';
        echo '<div>';
        echo '<div class="srp-recording-title">' . esc_html(wp_trim_words($recording->url, 5)) . '</div>';
        echo '<div class="srp-recording-date">' . human_time_diff(strtotime($recording->created_at), current_time('timestamp')) . ' ago</div>';

        // Handle options data properly
        if ($recording->options) {
          $options = $recording->options;

          // If it's still a string, try to decode it
          if (is_string($options)) {
            // Try JSON decode first, then unserialize as fallback
            $decoded_options = json_decode($options, true);
            if ($decoded_options === null && json_last_error() !== JSON_ERROR_NONE) {
              // If JSON decode fails, try unserialize
              $options = maybe_unserialize($options);
            } else {
              $options = $decoded_options;
            }
          }

          // Now safely access the options array
          if (is_array($options)) {
            $device_key = $options['device_key'] ?? 'unknown';
            $device_options = ScreenRecorderPro::get_device_viewport_options();
            $device_name = $device_options[$device_key]['name'] ?? 'Unknown Device';
            echo '<div class="srp-recording-device">' . esc_html($device_name) . '</div>';
          }
        }

        echo '</div>';
        if ($recording->attachment_id) {
          echo '<a href="' . wp_get_attachment_url($recording->attachment_id) . '" class="srp-link" target="_blank">';
          echo '<span class="dashicons dashicons-external"></span>';
          echo '</a>';
        }
        echo '</div>';
      }
      echo '</div>';
    }
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

      .srp-header-actions {
        display: flex;
        gap: 12px;
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

      .srp-version {
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
      }

      .srp-subtitle {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 16px;
      }

      .srp-header-stats {
        display: flex;
        gap: 20px;
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

      .srp-help-text a {
        color: #667eea;
        text-decoration: none;
      }

      .srp-help-text a:hover {
        text-decoration: underline;
      }

      .srp-checkbox-label {
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        padding: 12px 16px;
        border: 2px solid #e5e5e5;
        border-radius: 8px;
        transition: all 0.3s ease;
        background: white;
      }

      .srp-checkbox-label:hover {
        border-color: #667eea;
        background: #f8f9ff;
      }

      .srp-checkbox-label input[type="checkbox"] {
        display: none;
      }

      .srp-checkbox-custom {
        width: 20px;
        height: 20px;
        border: 2px solid #ddd;
        border-radius: 4px;
        position: relative;
        transition: all 0.3s ease;
        background: white;
        flex-shrink: 0;
      }

      .srp-checkbox-label input[type="checkbox"]:checked+.srp-checkbox-custom {
        background: #667eea;
        border-color: #667eea;
      }

      .srp-checkbox-label input[type="checkbox"]:checked+.srp-checkbox-custom::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-size: 12px;
        font-weight: bold;
      }

      .srp-label-text {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #1e1e1e;
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
        color: white;
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

      .srp-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        margin-left: 8px;
      }

      .srp-badge-pro {
        background: #fff3e0;
        color: #ef6c00;
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

      .srp-notice-icon {
        color: #ff9800;
        font-size: 24px;
      }

      .srp-notice-content h3 {
        margin: 0 0 8px 0;
        color: #1e1e1e;
      }

      .srp-notice-content p {
        margin: 0;
        color: #757575;
      }

      .srp-stat-card {
        background: rgba(255, 255, 255, 0.2);
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        backdrop-filter: blur(10px);
        min-width: 100px;
      }

      .srp-stat-number {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 4px;
      }

      .srp-stat-label {
        font-size: 12px;
        opacity: 0.9;
        text-transform: uppercase;
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

      .srp-recording-date {
        font-size: 12px;
        color: #6c757d;
      }

      .srp-recording-device {
        font-size: 12px;
        color: #667eea;
        font-weight: 500;
        margin: 4px 0;
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

      .srp-shortcode-section {
        margin: 16px 0;
      }

      .srp-shortcode-label {
        font-size: 12px;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
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
        font-size: 11px;
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

        .srp-header-stats {
          justify-content: center;
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