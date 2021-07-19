<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap agoraio" id="agoraio">
  <h1><?php echo esc_html( __( 'Agora Video Settings', 'agoraio' ) ); ?></h1>

  <?php do_action( 'agoraio_admin_notices', 'agoraio-settings', agora_current_action() ); ?>


  <div class="card">
    <h2 class="title">Global Settings</h2>
    <br class="clear" />
    <div class="inside">
      <div class="flex app-setting" id="globalColors">
        <div class="col label">
        <table class="form-table">
          <tr>
            <th scope="row"><label for="unselectedVideoControlsButtonsColor"><?php _e('Video Controls Buttons Color Unselected', 'agoraio') ?></label></th>
            <td>
              <input
                id="unselectedVideoControlsButtonsColor"
                name="unselectedVideoControlsButtonsColor"
                type="text"
                class="agora-color-picker inputBoxGS"
                value="<?php echo $agora_options['global_colors']['unselectedVideoControlsButtonsColor'] ?>"
                data-default-color="#1E73BE">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="selectedVideoControlsButtonsColor"><?php _e('Video Controls Buttons Color Selected', 'agoraio') ?></label></th>
            <td>
              <input
                id="selectedVideoControlsButtonsColor"
                name="selectedVideoControlsButtonsColor"
                type="text"
                class="agora-color-picker inputBoxGS"
                value="<?php echo $agora_options['global_colors']['selectedVideoControlsButtonsColor'] ?>"
                data-default-color="#1E73BE">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="otherButtonsColor"><?php _e('Other Buttons Color', 'agoraio') ?></label></th>
            <td>
              <input
                id="otherButtonsColor"
                name="otherButtonsColor"
                type="text"
                class="agora-color-picker inputBoxGS"
                value="<?php echo $agora_options['global_colors']['otherButtonsColor'] ?>"
                data-default-color="#ffffff">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="backgroundColorVideoMuted"><?php _e('Background Color - Video Muted', 'agoraio') ?></label></th>
            <td>
              <input
                id="backgroundColorVideoMuted"
                name="backgroundColorVideoMuted"
                type="text"
                class="agora-color-picker inputBoxGS"
                value="<?php echo $agora_options['global_colors']['backgroundColorVideoMuted'] ?>"
                data-default-color="#ffffff">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="backgroundColorPanels"><?php _e('Background Color - Panels', 'agoraio') ?></label></th>
            <td>
              <input
                id="backgroundColorPanels"
                name="backgroundColorPanels"
                type="text"
                class="agora-color-picker inputBoxGS"
                value="<?php echo $agora_options['global_colors']['backgroundColorPanels'] ?>"
                data-default-color="#ffffff">
            </td>
          </tr>
        </table>
        </div>
        
      </div>
      <p>
        <a href="#" class="button-primary" id="globalColors-save" style="margin:0 10px">Save</a>
      </p>
      <span class="error error-messageglobalColors"></span>
    </div>
  </div>

  <div class="card">
    <h2 class="title">App ID</h2>
    <div class="infobox">
      agora.io
      <br />
      <a href="https://docs.agora.io" target="blank">Agora.io Docs</a>
    </div>

    <br class="clear" />

    <div class="inside">
      <p>
        <?php
        $desc = __('App ID are issued to app developers by Agora to identify the projects and organizations. After signing up at [AGORA_DASH_LINK]Agora Dashboard[/AGORA_DASH_LINK] you can create multiple projects, and each project will have a unique App ID. See [HELP_LINK]Getting an App ID[/HELP_LINK]', 'agoraio');
        echo str_replace(
          array("[AGORA_DASH_LINK]", "[/AGORA_DASH_LINK]", "[HELP_LINK]", "[/HELP_LINK]"),
          array(
            "<a href='https://dashboard.agora.io/' target='blank'>", "</a>",
            "<a href='https://docs.agora.io/en/Agora%20Platform/channel_key?platform=All%20Platforms#step-1-get-an-app-id' target='blank'>", "</a>",),
          $desc);
        ?>
      </p>
      <div class="flex app-setting" id="appId">
        <div class="col label">
          App ID
        </div>
        <div class="col value" data-masked="true">
          <?php
          $value = isset($agora_options['appId']) ? $agora_options['appId'] : false;
          if ($value) {
            for($i=0;$i<strlen($value)-4;$i++) echo "*";
            echo substr($value, strlen($value)-4);
          }
          ?>
        </div>
      </div>
      <p>
        <a href="#" class="button">Change App ID</a>
      </p>
    </div>
  </div>

  <!-- ===== Token Server ===== -->
  <div class="card">
    <h2 class="title">App Certificate</h2>
    <div class="infobox">
      agora.io
      <br />
      <a href="https://dashboard.agora.io/projects" target="blank">Agora.io Dashboard</a>
    </div>

    <br class="clear" />

    <div class="inside">
      <p>
        <?php _e('Please enable your App Certificate Server on your Agora Dashboard, then copy here your <b>App Certificate</b>.', 'agoraio');
        ?></p>
      <div class="flex app-setting" id="appCertificate">
        <div class="col label">
          App Certificate
        </div>
        <div class="col value" data-masked="true">
          <?php
          $value = isset($agora_options['appCertificate']) ? $agora_options['appCertificate'] : '';
          if ($value) {
            for($i=0;$i<strlen($value)-4;$i++) echo "*";
            echo substr($value, strlen($value)-4);
          }
          ?>
        </div>
      </div>
      <p>
        <a href="#" class="button">Change App Certificate</a>
      </p>
    </div>
  </div>


  <div class="card">
    <h2 class="title">RESTFul Customer ID</h2>
    <div class="infobox">
      agora.io
      <br />
      <a href="https://docs.agora.io/en/faq/restful_authentication" target="blank">Agora.io RESTFul Docs</a>
    </div>

    <br class="clear" />

    <div class="inside">
      <p>
        <?php
        _e('You need to use your Customer ID to access to the RESTFul API and enable Cloud Recording', 'agoraio');
        ?></p>
      <div class="flex app-setting" id="customerID">
        <div class="col label">
          RESTFul Customer ID
        </div>
        <div class="col value" data-masked="true">
          <?php
          $value = isset($agora_options['customerID']) ? $agora_options['customerID'] : '';
          if ($value) {
            for($i=0;$i<strlen($value)-4;$i++) echo "*";
            echo substr($value, strlen($value)-4);
          }
          ?>
        </div>
      </div>
      <p>
        <a href="#" class="button">Change Customer ID</a>
      </p>
    </div>
  </div>

  <div class="card">
    <h2 class="title">RESTFul Customer Certificate</h2>
    <div class="infobox">
      agora.io
      <br />
      <a href="https://docs.agora.io/en/faq/restful_authentication" target="blank">Agora.io RESTFul Docs</a>
    </div>

    <br class="clear" />

    <div class="inside">
      <p>
        <?php
        _e('You need to use your Customer Certificate to enable Cloud Recording', 'agoraio');
        ?></p>
      <div class="flex app-setting" id="customerCertificate">
        <div class="col label">
          RESTFul Customer Certificate
        </div>
        <div class="col value" data-masked="true">
          <?php
          $value = isset($agora_options['customerCertificate']) ? $agora_options['customerCertificate'] : '';
          if ($value) {
            for($i=0;$i<strlen($value)-4;$i++) echo "*";
            echo substr($value, strlen($value)-4);
          }
          ?>
        </div>
      </div>
      <p>
        <a href="#" class="button">Change Customer Certificate</a>
      </p>
    </div>
  </div>

  <div class="card">
    <h2 class="title">Chat Support</h2>
    
    <br class="clear" />

    <div class="inside">
      <p><?php _e('Global setting to enable or disable internal Agora Chat.', 'agoraio'); ?></p>
      <div class="flex" id="agora-chat">
        <div class="col label">
          Agora Chat
        </div>
        <?php
        $value = isset($agora_options['agora-chat']) ? $agora_options['agora-chat'] : '';
        // $value = "https://4045media-cloudrecordings.s3.amazonaws.com";
        $chatCheck = $value==='enabled' ? 'checked' : '';
        ?>
        <div class="col value" data-masked="true">
          <label class="switch">
            <input type="checkbox" <?php echo $chatCheck ?> id="agora-chat-check" value="chat-enabled">
            <span class="slider round"></span>
          </label>
          <span id="chat-status-text"
           data-enabled="<?php _e('enabled', 'agoraio'); ?>"
           data-disabled="<?php _e('disabled', 'agoraio') ?>"></span>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <h2 class="title">More Settings</h2>
    
    <br class="clear" />

    <div class="inside">
      <p><?php _e('Global settings.', 'agoraio'); ?></p>
      <div class="flex" id="agora-chat-loggedin">
        <div class="col label">
          Agora Chat for logged in users
        </div>
        <?php
        $value = isset($agora_options['agora-chat-loggedin']) ? $agora_options['agora-chat-loggedin'] : '';
        // $value = "https://4045media-cloudrecordings.s3.amazonaws.com";
        $chatCheck = $value==='enabled' ? 'checked' : '';
        ?>
        <div class="col value" data-masked="true">
          <label class="switch">
            <input type="checkbox" <?php echo $chatCheck ?> id="agora-chat-check-loggedin" value="chat-enabled-loggedin">
            <span class="slider round"></span>
          </label>
          <span id="chat-status-text-loggedin"
           data-enabled="<?php _e('enabled', 'agoraio'); ?>"
           data-disabled="<?php _e('disabled', 'agoraio') ?>"></span>
        </div>
      </div>

    </div>
  </div>

<?php /* Drag-drop */ ?>

<?php 
$chatPos = isset($agora_options['agora-chat-position']) ? strtolower($agora_options['agora-chat-position']) : '';
$remoteSpeakersPos = isset($agora_options['agora-remote-speakers-position']) ? $agora_options['agora-remote-speakers-position'] : '';
?>

<div class="card">
  <h2 class="title">Component's Position Settings</h2>

  <div id="">
      <table id="wp-aora-io-drag-drop" >
          <tbody id="containment">
              <tr class="wp-agora-io-drop-top-row">
                  <td colspan="3" class="wp-agora-io-drop-top">
                    <div class="wp-agora-io-drop">
                      <?php if($remoteSpeakersPos == '' || $remoteSpeakersPos == 'top'){ ?>
                        <div class="wp-agora-io-draggable remote-speakers-draggable">Remote Speakers</div>
                      <?php } ?> 
                      <?php if($chatPos == 'top'){ ?>
                        <div class="wp-agora-io-draggable chat-draggable">Chat</div>
                      <?php } ?> 
                    </div>
                  </td>
              </tr>
              <tr class="wp-agora-io-drop-middle-row">
                  <td width="20%" class="wp-agora-io-drop-left">
                    <div class="wp-agora-io-drop">
                      <?php if($remoteSpeakersPos == 'left'){ ?>
                        <div class="wp-agora-io-draggable remote-speakers-draggable">Remote Speakers</div>
                      <?php } ?>
                      <?php if($chatPos == 'left'){ ?>
                        <div class="wp-agora-io-draggable chat-draggable">Chat</div>
                      <?php } ?>
                    </div>
                  </td>
                  <td width="60%" class="wp-agora-io-drop-overlaid">
                    <div class="wp-agora-io-drop">
                      <?php if($remoteSpeakersPos == 'overlaid'){ ?>
                        <div class="wp-agora-io-draggable remote-speakers-draggable">Remote Speakers</div>
                      <?php } ?>
                      <?php if($chatPos == '' || $chatPos == 'overlaid'){ ?>
                        <div class="wp-agora-io-draggable chat-draggable">Chat</div>
                      <?php } ?>  
                    </div>
                  </td>
                  <td width="20%" class="wp-agora-io-drop-right">
                    <div class="wp-agora-io-drop">
                      <?php if($remoteSpeakersPos == 'right'){ ?>
                        <div class="wp-agora-io-draggable remote-speakers-draggable">Remote Speakers</div>
                      <?php } ?>
                      <?php if($chatPos == 'right'){ ?>
                        <div class="wp-agora-io-draggable chat-draggable">Chat</div>
                      <?php } ?>
                    </div>
                  </td>
              </tr>
              <tr class="wp-agora-io-drop-bottom-row">
                  <td colspan="3" class="wp-agora-io-drop-bottom">
                    <div class="wp-agora-io-drop">
                      <?php if($remoteSpeakersPos == 'bottom'){ ?>
                        <div class="wp-agora-io-draggable remote-speakers-draggable">Remote Speakers</div>
                      <?php } ?>
                      <?php if($chatPos == 'bottom'){ ?>
                        <div class="wp-agora-io-draggable chat-draggable">Chat</div>
                      <?php } ?>
                    </div>
                  </td>
              </tr>
          </tbody>
      </table>
  </div><!-- /#page-wrapper -->
</div>

<script>
jQuery(document).ready(function() {

    let currentComponent = '';

    jQuery(".wp-agora-io-draggable").draggable({ 
        cursor: "crosshair", 
        revert: "invalid",
        drag: function(event, ui) {
          if(jQuery(event.target).hasClass('remote-speakers-draggable')){
            currentComponent = 'remoteSpeakers';
          } else if(jQuery(event.target).hasClass('chat-draggable')){
            currentComponent = 'chat';
          }
        }
    });

    jQuery(".wp-agora-io-drop").droppable({ 
        accept: ".wp-agora-io-draggable", 
        activeClass: "over",
        drop: function(event, ui) {

            jQuery(this).removeClass("border").removeClass("over");

            /* Get drop position based on its class */
            let position = jQuery(event.target).parents('td').attr('class').split('wp-agora-io-drop-')[1];

            /* Update Remote Speaker position - If it's position is changed */
            if(currentComponent == 'remoteSpeakers'){
              agoraComponentPositionChange('agora-remote-speakers-position', position);
            }

            /* Update Chat position - If it's position is changed */
            if(currentComponent == 'chat'){
              agoraComponentPositionChange('agora-chat-position', position);
            }

            var dropped = ui.draggable;
            var droppedOn = jQuery(this);
            jQuery(dropped).detach().css({top: 0,left: 0}).appendTo(droppedOn);    
        },
        over: function(event, ui) {
          // Enable all the .droppable elements
          jQuery('.wp-agora-io-drop').droppable('enable');

          let position = jQuery(this).parents('td').attr('class').split('wp-agora-io-drop-')[1];
          // If the droppable element we're hovered over already contains a .draggable element, or if remote speakers position to be set as overlaid 
          // don't allow another one to be dropped on it
          //Remote Speakers position as overlaid does not make any sense, so ignore it. Also igored top position for chat
          if((jQuery(this).has('.wp-agora-io-draggable').length) || (currentComponent == 'remoteSpeakers' && position == 'overlaid') || (currentComponent == 'chat' && position == 'top')) {
            jQuery(this).droppable('disable');
          }
        }
    });

});
</script>
<?php /* Drag-drop */ ?>

  <script type="text/javascript">
    window.AGORA_ADMIN_URL = '<?php echo plugin_dir_url(__DIR__ . '/../index.php'); ?>';
  </script>

</div>
