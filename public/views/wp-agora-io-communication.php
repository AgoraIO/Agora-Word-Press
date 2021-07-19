<?php
$channelSettings    = $channel->get_properties();
$videoSettings      = $channelSettings['settings'];
$appearanceSettings = $channelSettings['appearance'];
$current_user       = wp_get_current_user();
$channel_layout = $channelSettings['channel_layout'];

$remoteSpeakersPos = isset($agora->settings['agora-remote-speakers-position']) ? $agora->settings['agora-remote-speakers-position'] : '';

/* Code with Reemote Streams on right side - use for future */
// $isSpeakerView = false;
// if($channel_layout == 'speaker'){
//   $isSpeakerView = true;
// }

?>
<div id="agora-root" class="agora agora-communication">
  <section class="agora-container">
    <?php require_once "parts/header.php" ?>

    <div class="agora-content">
      <?php require_once "parts/header-controls.php" ?>

      <div id="screen-zone" class="screen <?php //if($isSpeakerView){ echo 'speaker-view'; } ?> agora-screen-users-<?php if($remoteSpeakersPos == '') { echo 'top'; } else { echo $remoteSpeakersPos; } ?>">

        <?php 
        /* Code with Reemote Streams on right side - use for future */
        /*if($isSpeakerView){ ?>
          <div class="main-screen">
            <div id="main-screen-stream-section" class="main-screen-stream-section">
              <div id="local-video" class="user">
                <div id="mute-overlay" class="mute-overlay"><i class="fas fa-microphone-slash"></i></div>
                <div id="no-local-video" class="no-video-overlay text-center"><i class="fas fa-user"></i></div>
              </div>  
            </div>
          </div>
        <?php } else { */ ?>
          <div id="screen-users" class="screen-users screen-users-1">
            <div id="local-video" class="user">
            
              <div id="mute-overlay" class="mute-overlay"><i class="fas fa-microphone-slash"></i></div>
              <div id="no-local-video" class="no-video-overlay text-center"><i class="fas fa-user"></i></div>
            </div>
          </div>
        <?php //} ?>
      </div>
    </div>

    <?php require_once "parts/footer-communication.php" ?>
  </section>

  <?php require_once "parts/scripts-common.php" ?>
  <script>
    window.agoraMode = 'communication';

    window.addEventListener('load', function() {
      // window.agoraLogLevel = window.location.href.indexOf('localhost')>=0 ?  : AgoraRTC.Logger.NONE;
      AgoraRTC.Logger.enableLogUpload();
      AgoraRTC.Logger.setLogLevel(AgoraRTC.Logger.ERROR);
      window.AGORA_COMMUNICATION_CLIENT.initClientAndJoinChannel(window.agoraAppId, window.channelName);
    });
  </script>
</div>