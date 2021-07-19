<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
  die( '-1' );
}


function agoraio_admin_save_button( $channel_id ) {
  static $button = '';

  if ( ! empty( $button ) ) {
    echo $button;
    return;
  }

  $nonce = wp_create_nonce( 'agoraio-save-channel_' . $channel_id );

  $onclick = sprintf(
      "this.form._wpnonce.value = '%s';"
    . " this.form.action.value = 'save';"
    . " return true;",
    $nonce );

  $button = sprintf(
    '<input type="submit" class="button-primary" name="agoraio-save" value="%1$s" onclick="%2$s" />',
    esc_attr( __( 'Save', 'agoraio' ) ),
    $onclick );

  echo $button;
}

function agora_render_setting_row_select($id, $title, $options, $settings, $prefix=null) {
  $input_id = !empty($prefix) ? $prefix.'-'.$id : $id;
  ?>
  <tr id="<?php echo $id; ?>-row">
    <th scope="row"><label for="<?php echo $input_id ?>"><?php echo $title; ?></label></th>
    <td>
      <select id="<?php echo $input_id ?>" name="<?php echo $input_id ?>" style="<?php if($id == "recording_layout"){ echo "float:left;"; } ?>">
        <?php 
        //If no layout is selected default layout will be best fit
        if($id == "recording_layout" && $settings[$input_id]==''){
          $settings[$input_id] = 1;
        }
        foreach ($options as $key => $value) {
          $selected = ($settings[$input_id]==$key) ? 'selected="selected"' : '';
          echo '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
        } ?>
      </select>

      <?php 
      /* Add and show recording layout images with the option */
      if($id == "recording_layout"){ 
        $layoutImg = plugins_url('wp-agora-io')."/imgs/recordings/".$settings[$input_id]."-layout.png";
        ?>
        <div class="recording_layout_image_section">
          <a href="<?php echo $layoutImg; ?>" target="_blank">
            <div>
              <img src="<?php echo $layoutImg; ?>" height="30" width="50">
            </div>
          </a>
        </div>  
      <?php } ?>

    </td>
  </tr>
  <?php
}

function agora_render_setting_row($id, $title, $settings, $prefix, $inputType="number") {
  $input_id = !empty($prefix) ? $prefix.'-'.$id : $id;
  ?>
  <tr id="<?php echo $id; ?>-row">
    <th scope="row"><label for="<?php echo $input_id ?>"><?php echo $title ?></label></th>
    <td>
      <input
        <?php echo $inputType!=='number' ? 'class="regular-text"' : 'min="0"'; ?>
        id="<?php echo $input_id ?>"
        name="<?php echo $input_id ?>"
        type="<?php echo $inputType ?>"
        value="<?php echo isset($settings[$input_id]) ? $settings[$input_id] : '' ?>">
    </td>
  </tr>
  <?php
}

?>

<div class="wrap agoraio" id="agoraio-new-channel">
  <h1 class="wp-heading-inline">
    <?php
    if ( $post->initial() ) {
      echo esc_html( __( 'Add New Channel', 'agoraio' ) );
    } else {
      echo esc_html( __( 'Edit Channel', 'agoraio' ) );
    }
  ?></h1>
  <hr class="wp-header-end">

  <?php
  if ( $post ) :

    if ( current_user_can( 'edit_posts', $post_id ) ) {
      $disabled = '';
    } else {
      $disabled = ' disabled="disabled"';
    }
  ?>

  <form method="post" action="<?php echo esc_url( add_query_arg( array( 'post' => $post_id ), menu_page_url( 'agoraio', false ) ) ); ?>" id="agoraio-admin-form-element"<?php do_action( 'agoraio_post_edit_form_tag' ); ?>>
  <?php
    if ( current_user_can( 'edit_posts', $post_id ) ) {
      wp_nonce_field( 'agoraio-save-channel_' . $post_id );
    }
  ?>

  <input type="hidden" id="post_ID" name="post_ID" value="<?php echo (int) $post_id; ?>" />
  <input type="hidden" id="agoraio-locale" name="agoraio-locale" value="<?php echo esc_attr( $post->locale() ); ?>" />
  <input type="hidden" id="hiddenaction" name="action" value="save" />
  <input type="hidden" id="active-tab" name="active-tab" value="<?php echo isset( $_GET['active-tab'] ) ? (int) $_GET['active-tab'] : '0'; ?>" />


  <div id="poststuff">
    <div id="post-body" class="metabox-holder columns-1">
      <div id="post-body-content">
        <div id="titlediv">
          <div id="titlewrap">
            <label class="screen-reader-text" id="title-prompt-text" for="title">
              <?php echo __( 'Channel name', 'agoraio' ); ?>
            </label>
            <input
              type="text"
              name="post_title"
              size=30
              value="<?php echo $post->initial() ? '' : $post->title() ?>"
              id="title"
              spellcheck="true"
              autocomplete="off"
              placeholder="<?php echo __( 'Channel name', 'agoraio' ); ?>"
              required
              <?php echo current_user_can( 'edit_posts', $post_id ) ? '' : 'disabled="disabled"' ?>
            />
          </div><!-- #titlewrap -->
          <div class="inside">
          <?php if ( ! $post->initial() ) { ?>
            <p class="description">
            <label for="agora-shortcode"><?php echo esc_html( __( "Copy this shortcode and paste it into your post, page, or text widget content:", 'agoraio' ) ); ?></label>
            <span class="shortcode wp-ui-highlight">
              <input type="text" id="agora-shortcode" 
                onfocus="this.select();"
                readonly="readonly"
                class="large-text code"
                value="<?php echo esc_attr( $post->shortcode() ); ?>" />
            </span>
            </p>
          <?php } ?>
          </div>
        </div><!-- #titlediv -->
      </div><!-- #post-body-content -->

      <!--  metabox here  -->
      <div id="postbox-container-1" class="postbox-container">
        <?php do_action( 'agoraio_channel_form_settings', $post ); ?>
      </div>

      <div id="postbox-container-2" class="postbox-container">
        <?php do_action( 'agoraio_channel_form_appearance', $post ); ?>
      </div>

      <div id="postbox-container-3" class="postbox-container">
        <?php do_action( 'agoraio_channel_form_recording', $post ); ?>
        <?php do_action( 'agoraio_channel_form_chat_support', $post ); ?>
        <?php //do_action( 'agoraio_channel_form_ghost_mode', $post ); ?>
        <?php //do_action( 'agoraio_channel_form_layout', $post ); ?>

        <p class="submit"><?php agoraio_admin_save_button( $post_id ); ?></p>
      </div>
    </div>
  </div>

  </form>
  <?php endif; ?>

</div><!-- end wrap -->


<?php

// Render the content of the metabox with Channel Settings
function render_agoraio_channel_form_settings($channel) {
  // echo "<pre>Settings:".print_r(, true)."</pre>";
  $props = $channel->get_properties();
  $type = $props['type'];
  $userHost = is_array($props['host']) ? $props['host'] : array($props['host']);
  $settings = $props['settings'];
  // echo("<pre>".print_r($userHost, true)."</pre>");
  ?>
  <ul class="nav nav-tabs">
    <li class="active">
      <a href="#tab-1" id="link-tab-1">
        <i class="dashicons-before dashicons-admin-plugins"> </i>
        <?php _e('Type and Permissions', 'agoraio') ?>
      </a>
    </li>
    <li><a href="#tab-2" id="link-tab-2">
      <i class="dashicons-before dashicons-share"> </i>
      <?php _e('Push to External Networks', 'agoraio') ?>
    </a></li>
    <li><a href="#tab-3" id="link-tab-3">
      <i class="dashicons-before dashicons-admin-settings"> </i>
      <?php _e('Inject External Streams', 'agoraio') ?>
    </a></li>
  </ul>
  <div class="tab-content">
    <div id="tab-1" class="tab-pane active">
      <table class="form-table">
        <?php
        $typeOptions = array(
          '' => __('Select Type', 'agoraio'),
          'broadcast' => __('Broadcast', 'agoraio'),
          'communication' => __('Communication', 'agoraio'),
        );
        agora_render_setting_row_select(
          'type',
          __('Channel type', 'agoraio'),
          $typeOptions,
          $props
        ) ?>
        <tr id="broadcast-host-row">
          <th scope="row"><label for="host"><?php _e('Broadcast Users', 'agoraio'); ?></label></th>
          <td>
            <div id="broadcast-users-list" data-load-users='<?php echo json_encode($userHost) ?>'>
              <span class="helper-text help-add-more-users"><?php _e('Please add at least one user', 'agoraio') ?></span>
            </div>
            <button id="add-more-users" class="button-secondary" type="button">Add User</button>
            <div id="add-more-users-controls" class="add-more-users-controls">
              <?php
              $dropdownParams = array(
                "id" => "host",
                "name" => "host",
                "class" => "large-dropdown",
              );
              // if (!empty($userHost)) { $dropdownParams['selected'] = $userHost; }
              wp_dropdown_users($dropdownParams);
              ?>
              <span id="add-more-buttons">
                <button id="agora-add-user" class="button-primary" type="button">Add</button>
                <button id="agora-cancel-add-user" class="button-cancel" type="button">Cancel</button>
              </span>
              <span id="add-more-loader" class="agora-loader" style="display: none">
                <img src="<?php echo plugin_dir_url(__DIR__ . '/../index.php') ?>css/loader.svg" width="38" alt="agora-loader" />
              </span>
              <span id="add-user-error-msg" class="notice notice-error" style="display:none">
                <?php _e('User already added.', 'agoraio') ?>
              </span>
            </div>

          </td>
        </tr>
      </table>
    </div>

    <div id="tab-2" class="tab-pane">
      <p class="desc"><?php _e("Agora.io supports publishing streams to CDN's CDN (Content Delivery Networks) using live transcoding . Transcoding sets the audio/video profiles and the picture-in-picture layout for the stream to be pushed to the CDN. Configure the live transcoding settings. <a href='https://docs.agora.io/en/Interactive%20Broadcast/cdn_streaming_android?platform=Android#introduction' target='_blank'>(more)</a>", 'agoraio'); ?></p>
      <hr/>
      <?php agora_render_video_settings($settings, 'external'); ?>
    </div>

    <div id="tab-3" class="tab-pane">
      <?php // echo "<pre>".print_r($settings, true)."</pre>"; ?>
      <p class="desc"><?php _e('Injecting external media streams refers to pulling an external audio or video stream to an ongoing Agora.io live broadcast channel, so that the hosts and audience in the channel can hear and see the stream while interacting with each other.', 'agoraio'); ?></p>
      <hr/>
      <?php agora_render_video_settings($settings, 'inject'); ?>
    </div>
  </div>
  <?php
}


function agora_render_video_settings($settings, $prefix) {
  ?>
  <table class="form-table">
    <?php

    if ($prefix==='external') {
      agora_render_setting_row('rtmpServerURL', __('RTMP Server URL', 'agoraio'), $settings, $prefix, "text");
      agora_render_setting_row('streamKey', __('Stream key', 'agoraio'), $settings, $prefix, "text");
    }
    
    agora_render_setting_row('width', __('Width', 'agoraio'), $settings, $prefix);
    agora_render_setting_row('height', __('Height', 'agoraio'), $settings, $prefix);
    agora_render_setting_row('videoBitrate', __('Video Bitrate', 'agoraio'), $settings, $prefix);
    agora_render_setting_row('videoFramerate', __('Video Framerate', 'agoraio'), $settings, $prefix);
    agora_render_setting_row('videoGop', __('Video GOP', 'agoraio'), $settings, $prefix);
    agora_render_setting_row_select(
      'lowLatency',
      __('Low Latency', 'agoraio'),
      array(
        'false' => __('High latency with assured quality (default)', 'agoraio'),
        'true' => __('Low latency with unassured quality', 'agoraio')
      ),
      $settings, $prefix
    );
    agora_render_setting_row_select(
      'audioSampleRate',
      __('Audio Sample Rate', 'agoraio'),
      array(
        44100 => __('44.1 kHz (default)', 'agoraio'),
        48000 => __('48 kHz', 'agoraio'),
        32000 => __('32 kHz', 'agoraio'),
      ),
      $settings, $prefix
    );
    agora_render_setting_row('audioBitrate', __('Audio Bitrate', 'agoraio'), $settings, $prefix);
    agora_render_setting_row_select(
      'audioChannels',
      __('Audio Channels', 'agoraio'),
      array(1 => __('Mono (default)', 'agoraio'), 2 => __('Dual sound channels', 'agoraio')),
      $settings, $prefix
    );
    agora_render_setting_row_select(
      'videoCodecProfile',
      __('Video Codec Profile', 'agoraio'),
      array(
        100 => __('High (default)', 'agoraio'),
        77 => __('Main', 'agoraio'),
        66 => __('Baseline', 'agoraio'),
      ),
      $settings, $prefix
    );
    ?>
    <tr>
      <th scope="row"><label for="backgroundColor"><?php _e('Background Color', 'agoraio') ?></label></th>
      <td>
        <input
          id="<?php echo $prefix.'-'; ?>backgroundColor"
          name="<?php echo $prefix.'-'; ?>backgroundColor"
          type="text"
          class="agora-color-picker"
          value="<?php echo $settings[$prefix.'-backgroundColor'] ?>"
          data-default-color="#ffffff">
      </td>
    </tr>
  </table>
  <?php
}

// Metabox content for Channel Appearance
function render_agoraio_channel_form_appearance($channel) {
  $props = $channel->get_properties();
  $appearance = $props['appearance'];
  ?>
  <table class="form-table">
    <?php
    agora_render_setting_row('splashImageURL', __('Splash Image URL', 'agoraio'), $appearance, '', 'url');
    agora_render_setting_row('noHostImageURL', __('No-host Image URL', 'agoraio'), $appearance, '', 'url');
    agora_render_setting_row('watchButtonText', __('Watch Stream Button Text', 'agoraio'), $appearance, '', 'text');
    agora_render_setting_row_select(
      'watchButtonIcon',
      __('Watch Stream icon', 'agoraio'),
      array(
        'true' => __('Show icon', 'agoraio'),
        'false' => __('Hide icon', 'agoraio')
      ), $appearance, '')
    ?>
    <tr>
      <th scope="row"><label for="activeButtonColor"><?php _e('Active Button Color', 'agoraio') ?></label></th>
      <td>
        <input
          id="activeButtonColor"
          name="activeButtonColor"
          type="text"
          class="agora-color-picker"
          value="<?php echo $appearance['activeButtonColor'] ?>"
          data-default-color="#1E73BE">
      </td>
    </tr>
    <tr>
      <th scope="row"><label for="disabledButtonColor"><?php _e('Disabled Button Color', 'agoraio') ?></label></th>
      <td>
        <input
          id="disabledButtonColor"
          name="disabledButtonColor"
          type="text"
          class="agora-color-picker"
          value="<?php echo $appearance['disabledButtonColor'] ?>"
          data-default-color="#ffffff">
      </td>
    </tr>
  </table>
  <?php
}

// Metabox content for Chat support tab
function render_agoraio_channel_form_chat_support($channel) {
  $props = $channel->get_properties();
  $ChatSupportloggedin = $props['chat_support_loggedin'];
  ?>
  <table class="form-table">
  <?php agora_render_setting_row_select(
      'chat_support_loggedin',
      __('Chat Support Enable?', 'agoraio'),
      array(
        '' => __('Select', 'agoraio'),
        1 => __('Yes', 'agoraio'),
        0 => __('No', 'agoraio')
      ), $props, '');
   ?>
  <?php agora_render_setting_row_select(
      'ghost_mode',
      __('Ghost Mode', 'agoraio'),
      array(
        '' => __('Select', 'agoraio'),
        1 => __('Yes', 'agoraio'),
        0 => __('No', 'agoraio')
      ), $props, '');
   ?>
     <?php agora_render_setting_row_select(
      'channel_layout',
      __('Layout', 'agoraio'),
      array(
        '' => __('Select', 'agoraio'),
        'grid' => __('Grid View', 'agoraio'),
        'speaker' => __('Speaker View', 'agoraio')
      ), $props, '');
   ?>
    <?php agora_render_setting_row_select(
      'mute_all_users',
      __('Mute all users Audio/Video?', 'agoraio'),
      array(
        '' => __('Select', 'agoraio'),
        1 => __('Yes', 'agoraio'),
        0 => __('No', 'agoraio')
      ), $props, '');
   ?>
    <?php agora_render_setting_row_select(
      'chat_history',
      __('Save Chat History?', 'agoraio'),
      array(
        '' => __('Select', 'agoraio'),
        1 => __('Yes', 'agoraio'),
        0 => __('No', 'agoraio')
      ), $props, '');
   ?>
    <?php agora_render_setting_row_select(
      'pre_call_video',
      __('Pre-call/video test?', 'agoraio'),
      array(
        '' => __('Select', 'agoraio'),
        1 => __('Yes', 'agoraio'),
        0 => __('No', 'agoraio')
      ), $props, '');
   ?>

  <?php
    $args = array('fields' => array( 'ID', 'display_name' ) );
    $users = get_users($args);

    $users_options = array(
      '' => __('Select', 'agoraio')
    );

    foreach($users as $user){
      $users_options[$user->ID] = __($user->display_name, 'agoraio');
    }

    agora_render_setting_row_select(
    'admin_user',
    __('Admin User', 'agoraio'),
    $users_options, $props, '');
  ?>

  <?php agora_render_setting_row_select(
      'admin_user_unmute_forcefully',
      __('Can Admin user unmute Audio/Video forcefully?', 'agoraio'),
      array(
        '' => __('Select', 'agoraio'),
        1 => __('Yes', 'agoraio'),
        0 => __('No', 'agoraio')
      ), $props, '');
  ?>

<?php 
  agora_render_setting_row('max_host_users', __('Maximum No. of hosts (Excluding Broadcasters)', 'agoraio'), $props, '', 'text');    
?>

  </table>
  <?php
}


function render_agoraio_channel_form_recording($channel) {
  $props = $channel->get_properties();
  $recording = $props['recording'];
  // die("<pre>".print_r($recording, true)."</pre>");
  ?>
  <table class="form-table">
    <?php
    agora_render_setting_row_select(
      'vendor',
      __('Vendor', 'agoraio'),
      array(
        '' => __('Select Cloud Vendor', 'agoraio'),
        0 => __('Qiniu Cloud', 'agoraio'),
        1 => __('Amazon S3', 'agoraio'),
        2 => __('Alibaba Cloud', 'agoraio')
      ), $recording, '');

    agora_render_setting_row_select(
      'region',
      __('Region', 'agoraio'),
      array('' => __('Please select a vendor', 'agoraio')), $recording, '');
    
    if(isset($recording['region'])) {
      echo '<input type="hidden" id="region-tmp" value="'.$recording['region'].'" />';
    }

    agora_render_setting_row_select(
      'protoType',
      __('Type', 'agoraio'),
      array(
        'composite' => __('Composite', 'agoraio'),
        'individual' => __('Individual', 'agoraio')
      ), $recording, '');

    agora_render_setting_row_select(
      'recording_layout',
      __('Layout', 'agoraio'),
      array(
        1 => __('Best Fit', 'agoraio'),
        0 => __('Floating', 'agoraio'),
        2 => __('Vertical', 'agoraio')
      ), $recording, '');   
    
    agora_render_setting_row('bucket', __('Bucket', 'agoraio'), $recording, '', 'text');

    agora_render_setting_row('accessKey', __('Access Key', 'agoraio'), $recording, '', 'text');

    agora_render_setting_row('secretKey', __('Secret Key', 'agoraio'), $recording, '', 'text');
    ?>
  </table>
  <script>
    function agoraUpdateRegionOptions() {
      var vendor = parseInt(jQuery(this).val(), 10);
      var options = null;
      switch(vendor) {
        case 0: // china
          options = cloudRegions['qiniu'];
          break;
        case 1: // AWS
          options = cloudRegions['aws'];
          break;
        case 2: // Alibaba
          options = cloudRegions['alibaba'];
          break;
        default:
          break;
      }

      var region = jQuery('#region');
      region.empty();
      jQuery.each(options, function(key, value) {
        region.append(jQuery('<option/>').attr('value', key).text(value));
      });

      var tmpVal = jQuery('#region-tmp').val();
      if (tmpVal) {
        region.val( tmpVal );
      }
    }
    window.addEventListener('load', function() {
      jQuery('#vendor').change(agoraUpdateRegionOptions);
      jQuery('#vendor').change();
    });
  </script>
  <?php
}
?>