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
    stream: {}
  },
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
function agoraJoinChannel() {
  window.AGORA_RTM_UTILS.setupRTM(window.agoraAppId, window.channelName);

  window.agoraToken = window.AGORA_TOKEN_UTILS.agoraGenerateToken(); // rendered on PHP
  var userId = window.userID || 0; // set to null to auto generate uid on successfull connection

  // set the role
  window.agoraClient.setClientRole(window.agoraCurrentRole, function() {
    AgoraRTC.Logger.info('Client role set as host.');
  }, function(e) {
    AgoraRTC.Logger.error('setClientRole failed', e);
  });
  
  window.agoraClient.join(window.agoraToken, window.channelName, userId, function(uid) {
    window.AGORA_RTM_UTILS.joinChannel(uid, function(){
      console.log('RTM Joined!!!')
    });
    
    createCameraStream(uid, {});
    window.localStreams.uid = uid; // keep track of the stream uid  
    AgoraRTC.Logger.info('User ' + uid + ' joined channel successfully');
    setupLiveStreamListeners();
  }, function(err) {
      AgoraRTC.Logger.error('[ERROR] : join channel failed', err);
  });
}

// video streams for channel
function createCameraStream(uid, deviceIds) {
  AgoraRTC.Logger.info('Creating stream with sources: ' + JSON.stringify(deviceIds));

  var localStream = AgoraRTC.createStream({
    streamID: uid,
    audio: true,
    video: true,
    screen: false
  });
  localStream.setVideoProfile(window.cameraVideoProfile);

  // The user has granted access to the camera and mic.
  localStream.on("accessAllowed", function() {
    if(window.devices.cameras.length === 0 && window.devices.mics.length === 0) {
      AgoraRTC.Logger.info('[DEBUG] : checking for cameras & mics');
      window.AGORA_UTILS.getCameraDevices();
      window.AGORA_UTILS.getMicDevices();
    }
    AgoraRTC.Logger.info("accessAllowed");
  });
  // The user has denied access to the camera and mic.
  localStream.on("accessDenied", function() {
    AgoraRTC.Logger.warning("accessDenied");
  });

  localStream.init(function() {
    // window.AGORA_BROADCAST_UI.calculateVideoScreenSize();
    
    AgoraRTC.Logger.info('getUserMedia successfully');
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

    window.localStreams.camera.stream = localStream; // keep track of the camera stream for later

    jQuery('#buttons-container').fadeIn();
  }, function (err) {
    AgoraRTC.Logger.error('[ERROR] : getUserMedia failed', err);
  });
}

function agoraLeaveChannel() {

  window.agoraClient.leave(function callbackLeave() {
    AgoraRTC.Logger.info('client leaves channel');
    window.localStreams.camera.stream.stop() // stop the camera stream playback
    window.localStreams.camera.stream.close(); // clean up and close the camera stream
    window.agoraClient.unpublish(window.localStreams.camera.stream); // unpublish the camera stream
    //disable the UI elements
    jQuery('#mic-btn').prop('disabled', true);
    jQuery('#video-btn').prop('disabled', true);
    jQuery('#exit-btn').prop('disabled', true);
    jQuery("#add-rtmp-btn").prop("disabled", true);
    jQuery("#rtmp-config-btn").prop("disabled", true);
    jQuery("#cloud-recording-btn").prop("disabled", true);

    window.localStreams.camera.stream = null;

    window.dispatchEvent(new CustomEvent("agora.leaveChannel"));
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
  var externalUrl = jQuery('#input_external_url').val();
  
  // set live transcoding config
  window.agoraClient.addInjectStreamUrl(externalUrl, window.injectStreamConfig)
  window.injectedStreamURL = externalUrl;
  // TODO: ADD view for external url (similar to rtmp url)
}
// window.AGORA_BROADCAST_CLIENT.addExternalSource = addExternalSource;

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

window.AGORA_UTILS.setupAgoraListeners();