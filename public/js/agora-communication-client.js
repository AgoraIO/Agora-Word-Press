/*
 * JS Interface for Agora.io SDK
 */
// create client instances for camera (client) and screen share (screenClient)
var agoraClient = AgoraRTC.createClient({mode: 'rtc', codec: 'vp8'});

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

var mainStreamId; // reference to main stream
var screenShareActive = false; // flag for screen share 

window.AGORA_COMMUNICATION_CLIENT = {
  initClientAndJoinChannel: initClientAndJoinChannel,
  agoraJoinChannel: agoraJoinChannel,
  agoraLeaveChannel: agoraLeaveChannel
};

function initClientAndJoinChannel(agoraAppId, channelName) {
  window.AGORA_RTM_UTILS.setupRTM(agoraAppId, channelName);

  // init Agora SDK
  agoraClient.init(agoraAppId, function () {
    AgoraRTC.Logger.info("AgoraRTC client initialized");
    agoraJoinChannel(channelName, function(err){
      if (err) {
        console.error(err);

        // TODO: Show Global error!
        window.AGORA_RTM_UTILS.leaveChannel();
      }
    }); // join channel upon successfull init
  }, function (err) {
    AgoraRTC.Logger.error("[ERROR] : AgoraRTC client init failed", err);
    window.AGORA_RTM_UTILS.leaveChannel();
  });
}


// join a channel
async function agoraJoinChannel(channelName, cb) {
  var userId = window.userID || 0; // set to null to auto generate uid on successfull connection
  window.channel_type = 'communication';

  if(window.pre_call_device_test_enabled){
    let hasVideo = await isVideoAvailable()
    await createTmpCameraStream(userId, hasVideo);
  } else{
    window.AGORA_UTILS.setupAgoraListeners();
    
    var token = window.AGORA_TOKEN_UTILS.agoraGenerateToken();
    agoraClient.join(token, channelName, userId, async function(uid) {
      AgoraRTC.Logger.info("User " + uid + " join channel successfully");
      window.localStreams.camera.id = uid; // keep track of the stream uid 
      
      try {
        if(!jQuery.isEmptyObject( window.localStreams.camera.stream )){
          window.localStreams.camera.stream.stop();
        }
        await window.AGORA_RTM_UTILS.joinChannel(uid);
        await createCameraStream(uid);
        window.localStreams.uid = uid;
        cb && cb(null)
      } catch(err) {
        AgoraRTC.Logger.error("[ERROR] : join channel failed", err);
        cb && cb(err)
      }

    }, function(err) {
        AgoraRTC.Logger.error("[ERROR] : join channel failed", err);
        cb && cb(err)
    });
  }
}

//camera validation 
async function isVideoAvailable() {
  let md = navigator.mediaDevices;
  if (!md || !md.enumerateDevices) return false;

  const devices = await md.enumerateDevices()
  return devices.some(device => 'videoinput' === device.kind);
}

// video streams for channel
function createCameraStream(uid, next) {

  window.channel_type = 'communication';

  async function runCameraStream(cb) {
    
    let canJoinAsHost = await window.AGORA_COMMUNICATION_UI.canJoinAsHost();
    console.log("hlwcanJoinAsHost", canJoinAsHost)
    
    if(canJoinAsHost){
      const hasVideo = await isVideoAvailable()
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
      localStream.on("accessAllowed", async function() {
        
        /* To handle the case if user allows camera and microphone access at the same time */
        let canJoinAsHost = await window.AGORA_COMMUNICATION_UI.canJoinAsHost();
        console.log("hlwcanJoinAsHost", canJoinAsHost)
        
        if(canJoinAsHost){
          if(window.devices.cameras.length === 0 && window.devices.mics.length === 0) {
            AgoraRTC.Logger.info('[DEBUG] : checking for cameras & mics');
            window.AGORA_UTILS.getCameraDevices();
            window.AGORA_UTILS.getMicDevices();
          }
          AgoraRTC.Logger.info("accessAllowed");
          if(!hasVideo){
            const msg = {
              text: "USER_JOINED_WITHOUT_VIDEO**"+uid,
              messageType: "TEXT"
            }
            window.AGORA_RTM_UTILS.sendChannelMessage(msg)
          }
        } else {
          window.AGORA_COMMUNICATION_UI.joinAsAudience();
        }
      });

      localStream.on("accessDenied", function() {
        // alert('denied!')
      })

      localStream.init(async function initSuccess() {
        jQuery('#rejoin-container').hide();
        jQuery('#buttons-container').removeClass('hidden');

        var thisBtn = jQuery('#rejoin-btn');
        thisBtn.prop("disabled", false);
        thisBtn.find('.spinner-border').hide();

        AgoraRTC.Logger.info("getUserMedia successfully");

        try {
          jQuery(".main-screen-stream-section").css('display', 'block');

          localStream.play('local-video'); // play the given stream within the local-video div

          // publish local stream
          window.agoraClient.publish(localStream, function (err) {
              AgoraRTC.Logger.error("[ERROR] : publish local stream error: " + err);
          });
          
          showRaiseHandInCommunication();

          window.AGORA_COMMUNICATION_UI.enableUiControls(localStream); // move after testing

          jQuery('body .agora-footer').css('display', 'flex');

          window.localStreams.camera.stream = localStream; // keep track of the camera stream for later

          /* Mute Audios and Videos Based on Mute All Users Settings- Enabled */
          if(window.mute_all_users_audio_video){
              /* Mute if video is there and user has not unmuted their video - on Refresh (through session storage) */
              if((localStream.getVideoTrack() && localStream.getVideoTrack().enabled) && (sessionStorage.getItem("muteVideo")!="0")){
                jQuery("#video-btn").trigger('click');
              }
              /* Mute if audio is there and user has not unmuted their audio - on Refresh (through session storage) */
              if((localStream.getAudioTrack() && localStream.getAudioTrack().enabled) && (sessionStorage.getItem("muteAudio")!="0")){
                jQuery("#mic-btn").trigger('click');
              }
          } 
          else { /* Mute Audios and Videos Based on Mute All Users Settings- Disabled */
            /* If user has muted audio on Refresh (Check through session storage value) */
            if(sessionStorage.getItem("muteAudio")=="1"){
              jQuery("#mic-btn").trigger('click');
            }
            /* If user has muted video on Refresh (Check through session storage value) */
            if(sessionStorage.getItem("muteVideo")=="1"){
              jQuery("#video-btn").trigger('click');
            }
          }
        
          // window.AGORA_COMMUNICATION_UI.enableUiControls(localStream); // move after testing
          window.localStreams.camera.stream = localStream; // keep track of the camera stream for later

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

          cb && cb(null)
        } catch(ex) {
          // TODO: Show this error somewhere
          AgoraRTC.Logger.error('Stream error...', ex);
          agoraLeaveChannel();
          alert("Your video cannot be started!")
          cb && cb(ex)
        }
      }, function initError(err) {
        AgoraRTC.Logger.error("[ERROR] : getUserMedia failed", err);

        if (err.msg==='NotAllowedError') {

          const msg = {
            text: "USER_JOINED_WITHOUT_PERMISSIONS**"+uid,
            messageType:"TEXT"
          }
          window.AGORA_RTM_UTILS.sendChannelMessage(msg)
          
          window.AGORA_COMMUNICATION_UI.enableExit()
          window.AGORA_UTILS.showPermissionsModal()
        } else {
          cb && cb(err)
        }

      });
    }  else {
        window.AGORA_COMMUNICATION_UI.joinAsAudience();
      }
  }

  if (next) {
    runCameraStream(next);
  } else {
    return new Promise((resolve, reject) => {
      runCameraStream(err => {
        if (err) { reject(err); }
        else { resolve() }
      })
    })
  }
}

function agoraLeaveChannel() {
  
  if(screenShareActive) {
    window.AGORA_SCREENSHARE_UTILS.stopScreenShare();
  }

  window.dispatchEvent(new CustomEvent("agora.leavingChannel"));

  agoraClient.leave(function() {
    AgoraRTC.Logger.info("client leaves channel");
    const camStream = window.localStreams.camera.stream;
    if (camStream && camStream.stop) {
      camStream.stop() // stop the camera stream playback
      agoraClient.unpublish(camStream); // unpublish the camera stream
      camStream.close(); // clean up and close the camera stream
    }
    jQuery(".remote-stream-container").empty() // clean up the remote feeds
    //disable the UI elements
    jQuery("#mic-btn").prop("disabled", true);
    jQuery("#video-btn").prop("disabled", true);
    jQuery("#screen-share-btn").prop("disabled", true);
    jQuery("#exit-btn").prop("disabled", true);
    jQuery("#cloud-recording-btn").prop("disabled", true);
    
    // hide the mute/no-video overlays
    window.AGORA_UTILS.toggleVisibility("#mute-overlay", false); 
    window.AGORA_UTILS.toggleVisibility("#no-local-video", false);

    jQuery('#rejoin-container').show();
    jQuery('#buttons-container').addClass('hidden');

    // leave also RTM Channel
    window.AGORA_RTM_UTILS.leaveChannel();

    window.dispatchEvent(new CustomEvent("agora.leavedChannel"));
    
    // show the modal overlay to join
    // jQuery("#modalForm").modal("show"); 
  }, function(err) {
    AgoraRTC.Logger.error("client leave failed ", err); //error handling
  });
}
