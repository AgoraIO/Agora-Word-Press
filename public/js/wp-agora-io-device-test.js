var currStreamInterval = '';

jQuery(document).ready(function(){

    if(window.pre_call_device_test_enabled && window.agoraMode!='audience'){
        jQuery("body #local-video").css('width', '50%');
        jQuery("body #full-screen-video").css('width', '50%');

        let volume_indicator_div = '<span class="test-device-volumeScaleHolder"><span id="test-device-progressBar"><span id="test-device-myVolume"></span></span></span>';
        let camera_devices_div = "<div class='d-flex_align'><span>Camera</span><div id='test-device-camera-list'><select id='test-device-camera-options'></select></div></div>";
        let testMicButton = "<button onClick='startMicrophoneTesting()'>Start</button>";
        let mic_devices_div = "<div class='d-flex_align'><span>Microphone </span><div id='test-device-mic-list'><select id='test-device-mic-options'></select></div></div><div id='test_microphone_div'>"+testMicButton+"</div>";
        let action_button_div = "<div class='action-buttons'><button class='click_to_join'>Click to Join</button></div>";
        
        let main_div_id = "screen-users";
        /* Code with Reemote Streams on right side - use for future */
        // if(wiwindow.isSpeakerViewWithRemoteRight){
        //     main_div_id = 'main-screen-stream-section';
        //     jQuery(".main-screen-stream-section").css('display', 'flex');
        // }
        jQuery("body #"+main_div_id).append("<div id='test-device-section'> "+camera_devices_div+mic_devices_div+" <div class='test-device-volume-indicator'>"+volume_indicator_div+"</div> "+action_button_div+" </div>");
    }

    jQuery('body').on('click', '.click_to_join', function(){
        window.AGORA_UTILS.joinVideoCall(window.localStreams.camera.stream, 'playRemoteStream');
    });
});

/* Show Audio Level on device test - Microphone */
function startMicrophoneTesting(){
    jQuery("#test_microphone_div").html("<button onClick='stopMicrophoneTesting()'>Stop</button>");
    jQuery(".test-device-volume-indicator").show();

    currStreamInterval = setInterval(function(){
        if(typeof window.localStreams.tmpCameraStream!='undefined' && !jQuery.isEmptyObject(window.localStreams.tmpCameraStream)){
            
            let volume = window.localStreams.tmpCameraStream.getAudioLevel().toFixed(3)*100;
            jQuery('body #test-device-myVolume').css('width', volume+'%')

            /* Clear Interval when stream is published */
            if(sessionStorage.getItem("preCallDeviceTested")=="1"){
                clearInterval(currStreamInterval);
            }
        }
    }, 200);
}

/* Stop or Remove Audio Level on device test - Microphone */
function stopMicrophoneTesting(){
    jQuery("#test_microphone_div").html("<button onClick='startMicrophoneTesting()'>Start</button>");
    jQuery(".test-device-volume-indicator").hide();
    if(currStreamInterval!=''){
        clearInterval(currStreamInterval);
    }
}