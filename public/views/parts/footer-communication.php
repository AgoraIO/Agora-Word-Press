<?php
// $channelSettings is defined on the parent contianer of this file
$recordingSettings = $channelSettings['recording'];
$chat_support_loggedin = 0;
if (array_key_exists("chat_support_loggedin", $channelSettings) && $channelSettings['chat_support_loggedin'] == 1) {
    $chat_support_loggedin = 1;
}
?>
<footer class="agora-footer panel-background-color">
	<div class="buttons-bottom">
		
		<div id="audio-controls" class=" text-center btn-group">
	        <button id="mic-btn" type="button" class="btnIcon">
	            <i id="mic-icon" class="fas fa-microphone"></i>
			</button>

          	<button id="mic-dropdown" type="button" class="btnIcon dropdown-toggle dropdown-toggle-split"
          		data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            	<span class="sr-only"><?php _e('Mic Options Dropdown', 'agoraio'); ?></span>
            </button>
            <div id="mic-list" class="dropdown-menu dropdown-menu-right"></div>

            <small class="btn-title"><?php _e('Mic', 'agoraio') ?></small>
        </div>

        <div id="video-controls" class=" text-center btn-group">
        	<button id="video-btn"  type="button" class="btnIcon">
        		<i id="video-icon" class="fas fa-video"></i>
        	</button>
        	<button id="cam-dropdown" type="button" class="btnIcon dropdown-toggle dropdown-toggle-split"
        		data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        		<span class="sr-only"><?php _e('Video Options Dropdown', 'agoraio'); ?></span>
        	</button>
        	<div id="camera-list" class="dropdown-menu dropdown-menu-right"></div>
            <small class="btn-title"><?php _e('Video', 'agoraio') ?></small>
        </div>

        <div class="btn-separator"></div>

        <div id="change-layout-options-controls" class=" text-center btn-group">
        	<button id="change-layout-options-btn"  type="button" class="btnIcon">
            <i class="fa fa-columns" aria-hidden="true"></i>
        	</button>
        	<button id="change-layout-options-dropdown" type="button" class="btnIcon dropdown-toggle dropdown-toggle-split"
        		data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        		<span class="sr-only"><?php _e('Change Layout Options Dropdown', 'agoraio'); ?></span>
        	</button>
        	<div id="change-layout-options-list" class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item" id="grid">Grid</a>
                <a class="dropdown-item" id="speaker">Speaker</a>
            </div>
            <small class="btn-title"><?php _e('Layout', 'agoraio') ?></small>
        </div>

        <div class="btn-separator"></div>

        <?php require_once('raise-hand.php'); ?>

        <div class="btn-with-title only-desktop ">
    		<button id="screen-share-btn" type="button" class="btnIcon" title="<?php _e('Screen Share', 'agoraio'); ?>">
              <i id="screen-share-icon" class="fas fa-desktop"></i>
              <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display:none"></span>
            </button>
            <small class="btn-title"><?php _e('Share', 'agoraio') ?></small>
        </div>

        <?php require_once('modal-permission-request.php'); ?>


        <?php if(is_array($recordingSettings) && 
            !empty($recordingSettings['bucket']) &&
            !empty($recordingSettings['accessKey'])) : ?>
        <div class="btn-with-title only-desktop">
            <button id="cloud-recording-btn" type="button" class="btnIcon start-rec" title="<?php _e('Start Recording', 'agoraio'); ?>">
                <i id="screen-share-icon" class="fas fa-dot-circle" style="display: none"></i>
                <i id="screen-share-icon" class="inner-icon"></i>
            </button>
            <small class="btn-title"><?php _e('Record', 'agoraio') ?></small>
        </div>
        <?php endif; ?>

        <?php $enableChat = false; ?>
        <?php //if (isset($agora->settings['agora-chat']) && $agora->settings['agora-chat']==='enabled') : ?>
            <?php //$enableChat = true; ?>
        <?php 
            if (isset($agora->settings['agora-chat']) && $agora->settings['agora-chat']==='enabled'){  
                $enableChat = true;
            } 
            if(is_user_logged_in()){
                if (isset($agora->settings['agora-chat-loggedin']) && $agora->settings['agora-chat-loggedin']==='enabled'){  
                    $enableChat = true;
                } 
                if($chat_support_loggedin == 0){
                   $enableChat = false;
                }
            }
            if($enableChat===true){
        ?>
		<div class="btn-separator"></div>
        <div class="btn-with-title">
            <button id="chat-btn" class="btnIcon open-chat" title="<?php _e('Open Chat', 'agoraio'); ?>" type="button">
                <i id="chat-alert" class="fas fa-bell"></i>
                <i id="chat-icon" class="fas fa-comment-alt"></i>
            </button>
            <small class="btn-title"><?php _e('Chat', 'agoraio') ?></small>
        </div>
        <?php } ?>

        <div class="btn-with-title">
            <button id="exit-btn-footer" class="btnIcon btn-danger only-mobile" type="button">
                <i id="leave-call-icon" class="fas fa-phone"></i>
            </button>
            <small class="btn-title only-mobile"><?php _e('Exit', 'agoraio') ?></small>
        </div>
	</div>
    <div class="error-container">
        <span id="error-msg" class="text-danger"></span>
    </div>
    <?php if ($enableChat===true) { require_once('chat-fab.php'); } ?>
</footer>

<?php require_once "toast.php" ?>