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
<div id="agora-root" class="agora agora-broadcast">
      <?php /* if(is_array($recordingSettings) && 
            !empty($recordingSettings['bucket']) &&
            !empty($recordingSettings['accessKey'])) : ?>
      <!-- <div id="cloud-recording-container" class="mt-3">
        <button id="cloud-recording-btn" class="btn btn-sm start-rec" title="<?php _e('Start Recording', 'agoraio'); ?>">
          <div class="inner-icon"> </div>
        </button>
      </div> -->
      <?php endif; */ ?>

    <section class="agora-container">
      <?php require_once "parts/header.php" ?>

      <div class="agora-content">
        <?php require_once "parts/header-controls.php" ?>

        <div id="screen-zone" class="screen <?php //if($isSpeakerView){ echo 'speaker-view'; } ?> agora-screen-users-<?php if($remoteSpeakersPos == '') { echo 'top'; } else { echo $remoteSpeakersPos; } ?>">

        <?php 
        /* Code with Reemote Streams on right side - use for future */
        /* if($isSpeakerView){ ?>
          <div class="main-screen">
            <div id="main-screen-stream-section" class="main-screen-stream-section">
              <div id="full-screen-video" class="user">
                <div id="mute-overlay" class="mute-overlay"><i class="fas fa-microphone-slash"></i></div>
                <div id="no-local-video" class="no-video-overlay text-center"><i class="fas fa-user"></i></div>
              </div>  
            </div>
          </div>
        <?php } else { */ ?>

          <div id="screen-users" class="screen-users screen-users-1">
            <div id="full-screen-video" class="user">
              <div id="mute-overlay" class="mute-overlay"><i class="fas fa-microphone-slash"></i></div>
              <div id="no-local-video" class="no-video-overlay text-center"><i class="fas fa-user"></i></div>
            </div>
          </div>
        <?php //} ?>

        </div>
      </div>

      <?php require_once "parts/footer-broadcast.php" ?>
    </section>

    <?php require_once "parts/modal-external-url.php" ?>    

  <?php require_once "parts/scripts-common.php" ?>
  <?php require_once "parts/scripts-broadcast.php" ?>
  <script>
    /**
     * Agora Broadcast Client 
     */
    window.agoraCurrentRole = 'host';
    window.agoraMode = 'broadcast';

    function handleOnLoad(){
      window.mainStreamId = null; // reference to main stream

      // set log level:
      // -- .DEBUG for dev 
      // -- .NONE for prod
      AgoraRTC.Logger.enableLogUpload();
      AgoraRTC.Logger.setLogLevel(AgoraRTC.Logger.ERROR);
      // TODO: set DEBUG or NOE according to the current host (localhost or not)

      // init Agora SDK
      window.agoraClient.init(window.agoraAppId, function () {
        AgoraRTC.Logger.info('AgoraRTC client initialized');
        window.AGORA_BROADCAST_CLIENT.agoraJoinChannel(); // join channel upon successfull init
      }, function (err) {
        AgoraRTC.Logger.error('[ERROR] : AgoraRTC client init failed', err);
      });
    }

    if(typeof window.roleFromAudienceToHost!='undefined'){
      handleOnLoad();
    }
    else{
      window.addEventListener('load', function() {
        handleOnLoad();
      });
    }

  </script>
  <style>
    <?php /* if (isset($appearanceSettings['activeButtonColor']) && $appearanceSettings['activeButtonColor']!=='') { ?>
    .agora #main-container .btn.btn-dark,
    .agora #main-container .btn.btn-dark:hover,
    .agora #main-container .btn.btn-dark:focus {
      background-color: <?php echo $appearanceSettings['activeButtonColor'] ?>;
      border-color: <?php echo $appearanceSettings['activeButtonColor'] ?>;
    }
    <?php } ?>
    <?php if (isset($appearanceSettings['disabledButtonColor']) && $appearanceSettings['disabledButtonColor']!=='') { ?>
    .agora #main-container .btn.btn-danger,
    .agora #main-container .btn.btn-danger:hover,
    .agora #main-container .btn.btn-danger:focus {
      background-color: <?php echo $appearanceSettings['disabledButtonColor'] ?>;
      border-color: <?php echo $appearanceSettings['disabledButtonColor'] ?>;
    }
    <?php } */ ?>
  </style>
</div>
<!-- End Agora Broadcast View -->