jQuery(document).ready(function(){

    if(!window.pre_call_device_test_enabled){
        jQuery('body .agora-footer').css('display', 'flex');
    }

    if(window.pre_call_device_test_enabled){
        jQuery("body #local-video").css('width', '50%');

        let volume_indicator_div = '<div class="slidecontainer"><input type="range" min="1" max="100">';

        jQuery("body #screen-users").append("<div id='test-device-section'>Camera <br><div id='test-device-camera-list'><select id='test-device-camera-options'></select></div>Microphone <br /><div id='test-device-mic-list'><select id='test-device-mic-options'></select></div> <div class='test-device-volume-indicator'>"+volume_indicator_div+"</div> <div class='action-buttons'><button onclick='publishLocalStream()'>Click to Join</button></div></div>");
    }

    var i = 0;

    if(window.pre_call_device_test_enabled){
        var volumeData = {avg: 0, val: 0};
        console.log("tunAudoLevelInterval")
        var currStreamInterval = setInterval(function(){
            console.log("setIntervalRun")
            if(typeof window.localStreams.camera.stream!='undefined' && !jQuery.isEmptyObject(window.localStreams.camera.stream)){
                console.log("setIntervalRunLoclStream")
                console.log("audiLevel", window.localStreams.camera.stream.getAudioLevel())

                // if(sessionStorage.getItem("deviceTested")=="Yes"){
                //     clearInterval(currStreamInterval);
                // }
            }
        }, 1000);
    }
});


function publishLocalStream (localStream, channelType){

    sessionStorage.setItem("deviceTested", "Yes");

    jQuery("body #local-video").css('width', '100%');

    jQuery('body div#test-device-section').remove();

    jQuery('body .agora-footer').css('display', 'flex');

    // publish local stream
    window.agoraClient.publish(localStream, function (err) {
        AgoraRTC.Logger.error("[ERROR] : publish local stream error: " + err);
    });

    if(channelType == 'communication'){
        window.AGORA_COMMUNICATION_UI.enableUiControls(localStream); // move after testing
    }

    window.localStreams.camera.stream = localStream; // keep track of the camera stream for later

    /* Mute Audios and Videos Based on Mute All Users Settings */
    if(window.mute_all_users_audio_video){
        if(localStream.getVideoTrack() && localStream.getVideoTrack().enabled){
            jQuery("#video-btn").trigger('click');
        }
        if(localStream.getAudioTrack() && localStream.getAudioTrack().enabled){
            jQuery("#mic-btn").trigger('click');
        }
    }
}