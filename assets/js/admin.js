/**
 * Screen Recorder Pro - Admin JavaScript
 * Make sure this file is saved as: assets/js/admin.js
 */

jQuery(document).ready(function ($) {
  console.log('SRP: Admin JS loaded');
  console.log('SRP: AJAX URL:', srp_ajax.ajax_url);
  console.log('SRP: Nonce:', srp_ajax.nonce);

  // Handle the quick record form submission
  $('#srp-quick-record-form').on('submit', function (e) {
    e.preventDefault(); // Prevent default form submission
    console.log('SRP: Form submitted via AJAX');

    const $form = $(this);
    const $btn = $('#srp-record-btn');
    const $status = $('#srp-recording-status');
    const $success = $('#srp-recording-success');

    // Show loading state
    $btn.prop('disabled', true).addClass('loading');
    $btn.find('span').first().removeClass('dashicons-video-alt3').addClass('dashicons-update');
    $btn.find('span').last().text('Creating Recording...');
    $status.show();
    $success.hide();

    // Prepare form data
    const formData = {
      action: 'srp_create_recording',
      nonce: srp_ajax.nonce,
      url: $('#record_url').val(),
      device: $('#record_device').val(),
      duration: $('#record_duration').val(),
      show_device_frame: $('#show_device_frame').is(':checked') ? 1 : 0,
      format: 'mp4',
      scenario: 'scroll'
    };

    console.log('SRP: Sending AJAX data:', formData);

    // Make AJAX request
    $.ajax({
      url: srp_ajax.ajax_url,
      type: 'POST',
      data: formData,
      timeout: 180000, // 3 minutes timeout
      success: function (response) {
        console.log('SRP: AJAX response:', response);

        if (response.success) {
          $status.hide();
          $success.show();

          // Update view recording link
          if (response.data.video_url) {
            $('#srp-view-recording').attr('href', response.data.video_url);
          }

          // Reset form
          $form[0].reset();

          // Show success message
          $('#srp-status-text').text('Recording created successfully!');

        } else {
          console.error('SRP: API Error:', response.data);
          alert(response.data.message || 'An error occurred creating the recording');
          $status.hide();
        }
      },
      error: function (xhr, status, error) {
        console.error('SRP: AJAX Error:', {
          status: status,
          error: error,
          responseText: xhr.responseText
        });

        let errorMessage = 'Network error occurred';
        if (xhr.responseText) {
          try {
            const errorData = JSON.parse(xhr.responseText);
            errorMessage = errorData.message || errorMessage;
          } catch (e) {
            errorMessage = 'Server error: ' + xhr.status;
          }
        }

        alert(errorMessage);
        $status.hide();
      },
      complete: function () {
        // Reset button state
        $btn.prop('disabled', false).removeClass('loading');
        $btn.find('span').first().removeClass('dashicons-update').addClass('dashicons-video-alt3');
        $btn.find('span').last().text('Create Recording');
      }
    });
  });

  // Handle recording deletion
  window.srpDeleteRecording = function (recordingId) {
    if (!confirm(srp_ajax.strings.confirm_delete)) {
      return;
    }

    $.ajax({
      url: srp_ajax.ajax_url,
      type: 'POST',
      data: {
        action: 'srp_delete_recording',
        recording_id: recordingId,
        nonce: srp_ajax.nonce
      },
      success: function (response) {
        if (response.success) {
          location.reload();
        } else {
          alert(response.data.message || 'Failed to delete recording');
        }
      },
      error: function () {
        alert('Network error occurred');
      }
    });
  };

  // Handle shortcode copying
  window.srpCopyShortcode = function (btn) {
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
  };

  // Video modal functions
  window.srpPlayVideo = function (videoUrl, title) {
    const modal = document.getElementById('srp-video-modal');
    const video = document.getElementById('srp-modal-video');
    const titleEl = document.getElementById('srp-modal-title');

    if (modal && video && titleEl) {
      video.src = videoUrl;
      titleEl.textContent = title;
      modal.style.display = 'flex';
      video.play();
    }
  };

  window.srpCloseModal = function () {
    const modal = document.getElementById('srp-video-modal');
    const video = document.getElementById('srp-modal-video');

    if (modal && video) {
      video.pause();
      video.src = '';
      modal.style.display = 'none';
    }
  };

  // Close modal when clicking backdrop
  $(document).on('click', '.srp-modal-backdrop', function () {
    srpCloseModal();
  });

  // Close modal with Escape key
  $(document).on('keyup', function (e) {
    if (e.key === 'Escape') {
      srpCloseModal();
    }
  });

  console.log('SRP: All event handlers attached');
});