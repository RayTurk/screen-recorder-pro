<?php

/**
 * Device Mockup Handler - Add this to includes/class-device-mockups.php
 */

if (!defined('ABSPATH')) {
  exit;
}

class SRP_Device_Mockups
{

  public static function get_device_options()
  {
    return [
      'none' => [
        'name' => __('No Device Frame', 'screen-recorder-pro'),
        'type' => 'none'
      ],
      'iphone_15_pro' => [
        'name' => __('iPhone 15 Pro', 'screen-recorder-pro'),
        'type' => 'mobile',
        'viewport' => '393x852',
        'frame_width' => 430,
        'frame_height' => 890
      ],
      'iphone_15_pro_max' => [
        'name' => __('iPhone 15 Pro Max', 'screen-recorder-pro'),
        'type' => 'mobile',
        'viewport' => '430x932',
        'frame_width' => 470,
        'frame_height' => 970
      ],
      'samsung_s24' => [
        'name' => __('Samsung Galaxy S24', 'screen-recorder-pro'),
        'type' => 'mobile',
        'viewport' => '384x854',
        'frame_width' => 420,
        'frame_height' => 890
      ],
      'ipad_pro_11' => [
        'name' => __('iPad Pro 11"', 'screen-recorder-pro'),
        'type' => 'tablet',
        'viewport' => '834x1194',
        'frame_width' => 870,
        'frame_height' => 1230
      ],
      'ipad_air' => [
        'name' => __('iPad Air', 'screen-recorder-pro'),
        'type' => 'tablet',
        'viewport' => '820x1180',
        'frame_width' => 860,
        'frame_height' => 1220
      ],
      'surface_pro' => [
        'name' => __('Microsoft Surface Pro', 'screen-recorder-pro'),
        'type' => 'tablet',
        'viewport' => '912x1368',
        'frame_width' => 950,
        'frame_height' => 1400
      ]
    ];
  }

  public static function render_device_frame($video_url, $device_type = 'none', $options = [])
  {
    if ($device_type === 'none') {
      return self::render_plain_video($video_url, $options);
    }

    $devices = self::get_device_options();
    if (!isset($devices[$device_type])) {
      return self::render_plain_video($video_url, $options);
    }

    $device = $devices[$device_type];

    $classes = [
      'srp-device-mockup',
      'srp-device-' . $device['type'],
      'srp-device-' . str_replace('_', '-', $device_type)
    ];

    if (!empty($options['class'])) {
      $classes[] = $options['class'];
    }

    $style = '';
    if (!empty($options['style'])) {
      $style = $options['style'];
    }

    $video_attrs = [
      'controls' => $options['controls'] ?? 'true',
      'autoplay' => $options['autoplay'] ?? 'false',
      'loop' => $options['loop'] ?? 'false',
      'muted' => $options['muted'] ?? 'false'
    ];

    ob_start();
?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" style="<?php echo esc_attr($style); ?>">
      <div class="srp-device-frame">
        <div class="srp-device-screen">
          <video class="srp-device-video"
            <?php if ($video_attrs['controls'] === 'true'): ?>controls<?php endif; ?>
            <?php if ($video_attrs['autoplay'] === 'true'): ?>autoplay muted<?php endif; ?>
            <?php if ($video_attrs['loop'] === 'true'): ?>loop<?php endif; ?>
            <?php if ($video_attrs['muted'] === 'true'): ?>muted<?php endif; ?>>
            <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
            <p><?php _e('Your browser does not support the video tag.', 'screen-recorder-pro'); ?></p>
          </video>
        </div>
      </div>
    </div>

    <?php self::render_device_styles($device_type); ?>

  <?php
    return ob_get_clean();
  }

  private static function render_plain_video($video_url, $options)
  {
    $classes = ['screen-recording-video'];
    if (!empty($options['class'])) {
      $classes[] = $options['class'];
    }

    $style = '';
    if (!empty($options['style'])) {
      $style = $options['style'];
    }
    if (!empty($options['width']) && $options['width'] !== 'auto') {
      $style .= 'width: ' . $options['width'] . ';';
    }
    if (!empty($options['height']) && $options['height'] !== 'auto') {
      $style .= 'height: ' . $options['height'] . ';';
    }

    $video_attrs = [
      'controls' => $options['controls'] ?? 'true',
      'autoplay' => $options['autoplay'] ?? 'false',
      'loop' => $options['loop'] ?? 'false',
      'muted' => $options['muted'] ?? 'false'
    ];

    ob_start();
  ?>
    <video class="<?php echo esc_attr(implode(' ', $classes)); ?>" style="<?php echo esc_attr($style); ?>"
      <?php if ($video_attrs['controls'] === 'true'): ?>controls<?php endif; ?>
      <?php if ($video_attrs['autoplay'] === 'true'): ?>autoplay muted<?php endif; ?>
      <?php if ($video_attrs['loop'] === 'true'): ?>loop<?php endif; ?>
      <?php if ($video_attrs['muted'] === 'true'): ?>muted<?php endif; ?>>
      <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
      <p><?php _e('Your browser does not support the video tag.', 'screen-recorder-pro'); ?></p>
    </video>
  <?php
    return ob_get_clean();
  }

  private static function render_device_styles($device_type)
  {
    static $rendered_styles = [];

    if (in_array($device_type, $rendered_styles)) {
      return; // Already rendered
    }

    $rendered_styles[] = $device_type;

  ?>
    <style>
      /* Device Mockup Styles */
      .srp-device-mockup {
        display: inline-block;
        position: relative;
        margin: 20px auto;
      }

      .srp-device-frame {
        position: relative;
        background: #000;
        border-radius: 25px;
        padding: 20px;
        box-shadow: 0 8px 40px rgba(0, 0, 0, 0.3);
      }

      .srp-device-screen {
        position: relative;
        background: #000;
        border-radius: 15px;
        overflow: hidden;
      }

      .srp-device-video {
        width: 100%;
        height: 100%;
        display: block;
        border-radius: 15px;
      }

      /* iPhone 15 Pro Styles */
      .srp-device-iphone-15-pro .srp-device-frame {
        width: 250px;
        height: 520px;
        background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
        border-radius: 35px;
        padding: 15px;
        position: relative;
      }

      .srp-device-iphone-15-pro .srp-device-frame::before {
        content: '';
        position: absolute;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 25px;
        background: #000;
        border-radius: 0 0 15px 15px;
        z-index: 10;
      }

      .srp-device-iphone-15-pro .srp-device-frame::after {
        content: '';
        position: absolute;
        top: 18px;
        left: 50%;
        transform: translateX(-50%);
        width: 8px;
        height: 8px;
        background: #333;
        border-radius: 50%;
        z-index: 11;
      }

      .srp-device-iphone-15-pro .srp-device-screen {
        width: 220px;
        height: 490px;
        border-radius: 25px;
      }

      /* iPhone 15 Pro Max Styles */
      .srp-device-iphone-15-pro-max .srp-device-frame {
        width: 280px;
        height: 580px;
        background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
        border-radius: 38px;
        padding: 15px;
        position: relative;
      }

      .srp-device-iphone-15-pro-max .srp-device-frame::before {
        content: '';
        position: absolute;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        width: 70px;
        height: 25px;
        background: #000;
        border-radius: 0 0 15px 15px;
        z-index: 10;
      }

      .srp-device-iphone-15-pro-max .srp-device-frame::after {
        content: '';
        position: absolute;
        top: 18px;
        left: 50%;
        transform: translateX(-50%);
        width: 8px;
        height: 8px;
        background: #333;
        border-radius: 50%;
        z-index: 11;
      }

      .srp-device-iphone-15-pro-max .srp-device-screen {
        width: 250px;
        height: 550px;
        border-radius: 28px;
      }

      /* Samsung Galaxy S24 Styles */
      .srp-device-samsung-s24 .srp-device-frame {
        width: 260px;
        height: 530px;
        background: linear-gradient(145deg, #1a1a1a, #0d0d0d);
        border-radius: 25px;
        padding: 12px;
        position: relative;
      }

      .srp-device-samsung-s24 .srp-device-frame::before {
        content: '';
        position: absolute;
        top: 8px;
        left: 50%;
        transform: translateX(-50%);
        width: 40px;
        height: 4px;
        background: #333;
        border-radius: 2px;
        z-index: 10;
      }

      .srp-device-samsung-s24 .srp-device-screen {
        width: 236px;
        height: 506px;
        border-radius: 20px;
      }

      /* iPad Pro 11" Styles */
      .srp-device-ipad-pro-11 .srp-device-frame {
        width: 400px;
        height: 560px;
        background: linear-gradient(145deg, #e8e8e8, #d0d0d0);
        border-radius: 20px;
        padding: 20px;
        position: relative;
      }

      .srp-device-ipad-pro-11 .srp-device-frame::before {
        content: '';
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 4px;
        background: #666;
        border-radius: 2px;
        z-index: 10;
      }

      .srp-device-ipad-pro-11 .srp-device-screen {
        width: 360px;
        height: 520px;
        border-radius: 12px;
      }

      /* iPad Air Styles */
      .srp-device-ipad-air .srp-device-frame {
        width: 380px;
        height: 540px;
        background: linear-gradient(145deg, #f0f0f0, #d8d8d8);
        border-radius: 18px;
        padding: 18px;
        position: relative;
      }

      .srp-device-ipad-air .srp-device-frame::before {
        content: '';
        position: absolute;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        width: 8px;
        height: 8px;
        background: #666;
        border-radius: 50%;
        z-index: 10;
      }

      .srp-device-ipad-air .srp-device-screen {
        width: 344px;
        height: 504px;
        border-radius: 10px;
      }

      /* Surface Pro Styles */
      .srp-device-surface-pro .srp-device-frame {
        width: 420px;
        height: 600px;
        background: linear-gradient(145deg, #2d2d2d, #1a1a1a);
        border-radius: 8px;
        padding: 25px;
        position: relative;
      }

      .srp-device-surface-pro .srp-device-frame::before {
        content: 'Surface';
        position: absolute;
        bottom: 8px;
        right: 15px;
        color: #666;
        font-size: 10px;
        font-family: 'Segoe UI', sans-serif;
        z-index: 10;
      }

      .srp-device-surface-pro .srp-device-screen {
        width: 370px;
        height: 550px;
        border-radius: 4px;
      }

      /* Responsive adjustments */
      @media (max-width: 768px) {
        .srp-device-mockup {
          transform: scale(0.8);
        }
      }

      @media (max-width: 480px) {
        .srp-device-mockup {
          transform: scale(0.6);
        }
      }
    </style>
<?php
  }
}
