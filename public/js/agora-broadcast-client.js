/**
 * Agora Broadcast Client 
 */
 // create client instance
window.agoraClient = AgoraRTC.createClient({mode: 'live', codec: 'vp8'}); // h264 better detail at a higher motion


const AGORA_RADIX_DECIMAL = 10;
const AGORA_RADIX_HEX = 16;
// stream references (keep track of active streams) 
window.remoteStreams = {}; // remote streams obj struct [id : stream] 

// keep track of streams
window.localStreams = {
  uid: '',
  camera: {
    camId: '',
    micId: '',
    stream: {},
    userDetails: {}
  },
  tmpCameraStream: {},
  screen: {
    id: "",
    stream: {}
  }
};

// keep track of devices
window.devices = {
  cameras: [],
  mics: []
}

window.AGORA_BROADCAST_CLIENT = {
  startLiveTranscoding: startLiveTranscoding,
  addExternalSource: addExternalSource,
  agoraLeaveChannel: agoraLeaveChannel,
  agoraJoinChannel: agoraJoinChannel
};

// join a channel
async function agoraJoinChannel() {

  var userId = window.userID || 0; // set to null to auto generate uid on successfull connection
  window.channel_type = 'broadcast';

  if(window.pre_call_device_test_enabled){
    let hasVideo = await detectWebcam();
    await createTmpCameraStream(userId, hasVideo);
  } 
  else{
    window.AGORA_UTILS.setupAgoraListeners();
    window.AGORA_RTM_UTILS.setupRTM(window.agoraAppId, window.channelName);

    window.agoraToken = window.AGORA_TOKEN_UTILS.agoraGenerateToken(); // rendered on PHP
    var userId = window.userID || 0; // set to null to auto generate uid on successfull connection

    // set the role
    window.agoraClient.setClientRole(window.agoraCurrentRole, function() {
      AgoraRTC.Logger.info('Client role set as host.');
    }, function(e) {
      AgoraRTC.Logger.error('setClientRole failed', e);
    });
    
    window.agoraClient.join(window.agoraToken, window.channelName, userId, async function agoraClientJoin(uid) {
      await window.AGORA_RTM_UTILS.joinChannel(uid);
      
      createCameraStream(uid, {});
      window.localStreams.uid = uid; // keep track of the stream uid  
      AgoraRTC.Logger.info('User ' + uid + ' joined channel successfully');

    }, function(err) {
        AgoraRTC.Logger.error('[ERROR] : join channel failed', err);
    });

    window.agoraClient.on('stream-published', function (evt) {
      AgoraRTC.Logger.info("Publish local stream successfully");
    });
  }
}

//camera validation 
async function detectWebcam() {
  let md = navigator.mediaDevices;
  if (!md || !md.enumerateDevices) return false;

  const devices = await md.enumerateDevices()
  return devices.some(device => 'videoinput' === device.kind);
}

// video streams for channel
async function createCameraStream(uid, deviceIds) {

  window.channel_type = 'broadcast';

  AgoraRTC.Logger.info('Creating stream with sources: ' + JSON.stringify(deviceIds));
  const hasVideo = await detectWebcam()

  let streamSpec = {
    streamID: uid,
    audio: true,
    video: hasVideo,
    screen: false
  };

  if(sessionStorage.getItem("microphoneId")!=null){
    streamSpec.microphoneId = sessionStorage.getItem("microphoneId");
  }

  if(sessionStorage.getItem("cameraId")!=null){
    streamSpec.cameraId = sessionStorage.getItem("cameraId");
  }

  const localStream = AgoraRTC.createStream(streamSpec);

  localStream.setVideoProfile(window.cameraVideoProfile);

  // The user has granted access to the camera and mic.
  localStream.on("accessAllowed", function() {
    if(window.devices.cameras.length === 0 && window.devices.mics.length === 0) {
      AgoraRTC.Logger.info('[DEBUG] : checking for cameras & mics');
      window.AGORA_UTILS.getCameraDevices();
      window.AGORA_UTILS.getMicDevices();
    }
    AgoraRTC.Logger.info("accessAllowed");
    if(!hasVideo){
      const msg = {
        text: "USER_JOINED_WITHOUT_PERMISSIONS",
        messageType:"TEXT"
      }
      console.log('sending message')
      window.AGORA_RTM_UTILS.sendChannelMessage(msg, cb)
    }
  });
  // The user has denied access to the camera and mic.
  localStream.on("accessDenied", function() {
    AgoraRTC.Logger.warning("accessDenied");
  });

  localStream.init(function() {
    // window.AGORA_BROADCAST_UI.calculateVideoScreenSize();
    
    AgoraRTC.Logger.info('getUserMedia successfully');
     jQuery(".main-screen-stream-section").css('display', 'block');
    localStream.play('full-screen-video'); // play the local stream on the main div
    // publish local stream

    if(jQuery.isEmptyObject(window.localStreams.camera.stream)) {
      window.AGORA_BROADCAST_UI.enableUiControls(localStream); // move after testing
    } else {
      //reset controls
      jQuery("#mic-btn").prop("disabled", false);
      jQuery("#video-btn").prop("disabled", false);
      jQuery("#exit-btn").prop("disabled", false);
    }
    
    window.agoraClient.publish(localStream, function (err) {
      err && AgoraRTC.Logger.error('[ERROR] : publish local stream error: ' + err);
    });
    
    jQuery('body .agora-footer').css('display', 'flex');

    window.localStreams.camera.stream = localStream; // keep track of the camera stream for later
    
    /* Mute Audios and Videos Based on Mute All Users Settings */
    if(window.mute_all_users_audio_video){
      /* Mute if video is there and user has not unmuted their video - on Refresh (through session storage) */
      if((localStream.getVideoTrack() && localStream.getVideoTrack().enabled)  && (sessionStorage.getItem("muteVideo")!="0")){
          jQuery("#video-btn").trigger('click');
      }
      /* Mute if audio is there and user has not unmuted their audio - on Refresh (through session storage) */
      if((localStream.getAudioTrack() && localStream.getAudioTrack().enabled) && (sessionStorage.getItem("muteAudio")!="0")){
          jQuery("#mic-btn").trigger('click');
      }
    } else { /* Mute Audios and Videos Based on Mute All Users Settings- Disabled */
      /* If user has muted audio on Refresh (Check through session storage value) */
      if(sessionStorage.getItem("muteAudio")=="1"){
        jQuery("#mic-btn").trigger('click');
      }
      /* If user has muted video on Refresh (Check through session storage value) */
      if(sessionStorage.getItem("muteVideo")=="1"){
        jQuery("#video-btn").trigger('click');
      }
    }

    window.AGORA_UTILS.agora_getUserAvatar(localStream.getId(), function getUserAvatar(avatarData) {
      let userAvatar = '';
      if (avatarData && avatarData.user && avatarData.avatar) {
        userAvatar = avatarData.avatar
      }
      if(userAvatar!=''){
        jQuery('body #no-local-video').html('<img src="'+userAvatar.url+'" width="'+userAvatar.width+'" height="'+userAvatar.height+'" />')
      }
      window.localStreams.camera.userDetails = {avtar: userAvatar};
    });

    jQuery('#buttons-container').fadeIn();
  }, function (err) {
    AgoraRTC.Logger.error('[ERROR] : getUserMedia failed', err);

    if (err.msg==='NotAllowedError') {
      const msg = {
        text: "USER_JOINED_WITHOUT_PERMISSIONS**"+uid,
        messageType:"TEXT"
      }
      window.AGORA_RTM_UTILS.sendChannelMessage(msg)
      window.AGORA_COMMUNICATION_UI.enableExit()
      window.AGORA_UTILS.showPermissionsModal()
    }
  });
}

function agoraLeaveChannel() {

  window.dispatchEvent(new CustomEvent("agora.leavingChannel"));

  window.agoraClient.leave(function callbackLeave() {
    AgoraRTC.Logger.info('client leaves channel');
    window.localStreams.camera.stream.stop() // stop the camera stream playback
    window.localStreams.camera.stream.close(); // clean up and close the camera stream
    window.agoraClient.unpublish(window.localStreams.camera.stream); // unpublish the camera stream
    if (window.injectedStreamURL && window.injectedStreamURL != "") {
      window.agoraClient.removeInjectStreamUrl(window.injectedStreamURL);
    }
    jQuery("#remote-streams").empty() // clean up the remote feeds
    //disable the UI elements
    jQuery('#mic-btn').prop('disabled', true);
    jQuery('#video-btn').prop('disabled', true);
    jQuery('#screen-share-btn').prop('disabled', true);
    jQuery('#exit-btn').prop('disabled', true);
    jQuery("#add-rtmp-btn").prop("disabled", true);
    jQuery("#rtmp-config-btn").prop("disabled", true);
    jQuery("#start-RTMP-broadcast").prop("disabled", true);
    jQuery("#cloud-recording-btn").prop("disabled", true);

    window.localStreams.camera.stream = null;

    // leave also RTM Channel
    window.AGORA_RTM_UTILS.leaveChannel();

    window.dispatchEvent(new CustomEvent("agora.leavedChannel"));
  }, function(err) {
    AgoraRTC.Logger.error('client leave failed ', err); //error handling
  });
}
// window.AGORA_BROADCAST_CLIENT.agoraLeaveChannel = agoraLeaveChannel;


function startLiveTranscoding() {
  AgoraRTC.Logger.info("Start live transcoding..."); 
  const rtmpURL = window.defaultConfigRTMP.rtmpServerURL;
  const rtmpKey = window.defaultConfigRTMP.streamKey;

  if (!rtmpURL || rtmpURL.indexOf('://')<0) {
    alert('Please, configure a valid RTMP URL on your "External Networks" settings')
    return false;
  }

  // set live transcoding config
  window.defaultConfigRTMP.transcodingUsers[0].uid = window.localStreams.uid;
  window.agoraClient.setLiveTranscoding(window.defaultConfigRTMP);

  if (rtmpURL.length>0) {
    const sep = rtmpURL.lastIndexOf('/')===rtmpURL.length-1 ? '' : '/';
    window.externalBroadcastUrl = rtmpURL + sep + rtmpKey;
    console.log(window.externalBroadcastUrl);

    window.agoraClient.startLiveStreaming(window.externalBroadcastUrl, true)
    // addExternalTransmitionMiniView(window.externalBroadcastUrl)
  }
}

// window.AGORA_BROADCAST_CLIENT.startLiveTranscoding = startLiveTranscoding;

function addExternalSource() {
  const externalUrl = jQuery('#input_external_url').val();
  
  // set live transcoding config
  window.injectedStreamURL = externalUrl;
  window.agoraClient.addInjectStreamUrl(externalUrl, window.injectStreamConfig)
}

// RTMP Connection (UI Component)
function addExternalTransmitionMiniView(rtmpURL) {
  var container = jQuery('#rtmp-controlers');
  // append the remote stream template to #remote-streams
  container.append(
    jQuery('<div/>', {'id': 'rtmp-container',  'class': 'container row justify-content-end mb-2'}).append(
      jQuery('<div/>', {'class': 'pulse-container'}).append(
          jQuery('<button/>', {'id': 'rtmp-toggle', 'class': 'btn btn-lg col-flex pulse-button pulse-anim mt-2'})
      ),
      jQuery('<input/>', {'id': 'rtmp-url', 'val': rtmpURL, 'class': 'form-control col-flex" value="rtmps://live.facebook.com', 'type': 'text', 'disabled': true}),
      jQuery('<button/>', {'id': 'removeRtmpUrl', 'class': 'btn btn-lg col-flex close-btn'}).append(
        jQuery('<i/>', {'class': 'fas fa-xs fa-trash'})
      )
    )
  );
  
  jQuery('#rtmp-toggle').click(function() {
    if (jQuery(this).hasClass('pulse-anim')) {
      window.agoraClient.stopLiveStreaming(window.externalBroadcastUrl)
    } else {
      window.agoraClient.startLiveStreaming(externalBroadcastUrl, true)
    }
    jQuery(this).toggleClass('pulse-anim');
    jQuery(this).blur();
  });

  jQuery('#removeRtmpUrl').click(function() { 
    window.agoraClient.stopLiveStreaming(window.externalBroadcastUrl);
    window.externalBroadcastUrl = '';
    jQuery('#rtmp-container').remove();
  });
}


window.addEventListener('agora.rtm_init', function() {
  setupLiveStreamListeners();
  setupInjectStreamsListeners();
});

function setupLiveStreamListeners() {
  function toggleStreamButton(err, status) {
    const thisBtn    = jQuery("#start-RTMP-broadcast");
    const loaderIcon = thisBtn.find('#rtmp-loading-icon');
    const configIcon = thisBtn.find('#rtmp-config-icon');
    const labelStart = thisBtn.parent().find('#label-stream-start');
    const labelStop = thisBtn.parent().find('#label-stream-stop');

    if (thisBtn.hasClass('load-rec')) {
      thisBtn.toggleClass('load-rec');
      configIcon.show()
      loaderIcon.hide()
    }

    if (!err && status==='started') {
      thisBtn.addClass('btn-danger');
      labelStart.hide();
      labelStop.show();

    } else if (!err && status==='stopped') {
      thisBtn.removeClass('btn-danger');
      labelStart.show();
      labelStop.hide();
    }

    if (err && err.reason) {
      window.AGORA_UTILS.showErrorMessage(err.reason)
    }
  }

  window.agoraClient.on('liveStreamingStarted', function (evt) {
    console.log("Live streaming started", evt);
    toggleStreamButton(null, 'started')
  }); 

  window.agoraClient.on('liveStreamingFailed', function (evt) {
    console.log("Live streaming failed", evt);
    toggleStreamButton(evt)
  }); 

  window.agoraClient.on('liveStreamingStopped', function (evt) {
    console.log("Live streaming stopped", evt);
    toggleStreamButton(null, 'stopped')
  });

  window.agoraClient.on('liveTranscodingUpdated', function (evt) {
    console.log("Live streaming updated", evt);
  });
}

function setupInjectStreamsListeners() {
  window.agoraClient.on('streamInjectedStatus', function (evt) {
    console.log("Live streaming Injected Status:", evt);

    const thisBtn = jQuery('#add-rtmp-btn');
    const loaderIcon = thisBtn.find('#add-rtmp-loading-icon');
    const captureIcon = thisBtn.find('#add-rtmp-icon');

    if (evt.reason && evt.reason.indexOf('fail')>=0) {
      window.AGORA_UTILS.showErrorMessage(evt.reason);
      loaderIcon.hide();
      captureIcon.show();
    }
  });

  window.agoraClient.on('exception', function (ex) {
    console.log("Agora Exception:", ex);
  });
}

//window.AGORA_UTILS.setupAgoraListeners();
