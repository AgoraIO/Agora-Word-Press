window.screenshareAvailable = false;
window.screenClient = AgoraRTC.createClient({mode: 'rtc', codec: 'vp8'});
window.screenshareClients = {}; // remote streams from screen shares
window.screenClient.init(window.agoraAppId, function (e) {
  AgoraRTC.Logger.info("AgoraRTC screenClient initialized", e);
  window.screenshareAvailable = true;
  // window.AGORA_SCREENSHARE_UTILS.joinChannelAsScreenShare(cb);
}, function (err) {
  AgoraRTC.Logger.error("[ERROR] : AgoraRTC screenClient init failed", err);
  // cb(err, null);
});

window.AGORA_SCREENSHARE_UTILS = {

  toggleScreenShareBtn: function () {

    if(window.unselectedVideoControlsButtonsColor!="" && jQuery('#screen-share-btn').hasClass('btn-danger')){
      jQuery('#screen-share-btn').css('background-color', window.unselectedVideoControlsButtonsColor);
    } else if(window.selectedVideoControlsButtonsColor!="") {
      jQuery('#screen-share-btn').css('background-color', window.selectedVideoControlsButtonsColor);
    }

    jQuery('#screen-share-btn').toggleClass('btn-danger');
    jQuery('#screen-share-icon').toggleClass('fa-share-square').toggleClass('fa-times-circle');
  },

  // SCREEN SHARING
  initScreenShare: function (cb) {

    if (!window.screenshareAvailable) {
      alert('Screenshare is not available!')
      return;
    }

    // TODO: Validate window.remoteStreams length !== 0 ?
    if (Object.keys(window.screenshareClients).length>0) {
      cb('Screen sharing in progress', null);
      window.AGORA_UTILS.showErrorMessage('There is another screen sharing session in progress');
      return;
    }


    const userId = null; // window.userID or set to null to auto generate uid on successfull connection
    
    
    // Create the stream for screen sharing.
    const screenStream = AgoraRTC.createStream({
      // streamID: uid,
      audio: false, // Set the audio attribute as false to avoid any echo during the call.
      video: false,
      screen: true, // screen stream
      screenAudio: true,
      mediaSource:  'screen', // Firefox: 'screen', 'application', 'window' (select one)
    });

    screenStream.setScreenProfile(screenVideoProfile); // set the profile of the screen
    screenStream.init(function successInit() {
      AgoraRTC.Logger.info("getScreen successful");
      window.localStreams.screen.stream = screenStream; // keep track of the screen stream

      const successJoin = function successJoin(uid) {
        console.log(' Full join', uid)
        window.localStreams.screen.id = uid;  // keep track of the uid of the screen stream.
        if (window.AGORA_RTM_UTILS) {
          const msg = {
            description: undefined,
            messageType: 'TEXT',
            rawMessage: undefined,
            text: `${uid}: start screen share`
          }
          window.AGORA_RTM_UTILS.sendChannelMessage(msg);
        }

        window.screenClient.on('stream-published', function (evt) {
          AgoraRTC.Logger.info("Publish screen stream successfully");
          
          // debugger;
          if (typeof mainStreamId !== 'undefined') {
            remoteStreams[mainStreamId].stop(); // stop the main video stream playback
            
            if (window.AGORA_COMMUNICATION_CLIENT.addRemoteStreamView) {
              window.AGORA_COMMUNICATION_CLIENT.addRemoteStreamView(remoteStreams[mainStreamId]); // send the main video stream to a container
            }
          }
        });
        
        window.screenClient.on('stopScreenSharing', function (evt) {
          AgoraRTC.Logger.info("screen sharing stopped", err);
        });

        window.screenClient.publish(screenStream, function (err) {
          AgoraRTC.Logger.error("[ERROR] : publish screen stream error: " + err);
        });

        // access to real MediaStream from browser:
        window.localStreams.screen.stream.stream.getVideoTracks()[0].onended = function() {
          window.AGORA_SCREENSHARE_UTILS.stopScreenShare();
          window.AGORA_SCREENSHARE_UTILS.toggleScreenShareBtn();
          const loaderIcon = jQuery("#screen-share-btn").find('.spinner-border');
          const closeIcon = jQuery('#screen-share-icon');
          loaderIcon.hide();
          closeIcon.show();
        }

        jQuery("#screen-share-btn").prop("disabled", false); // enable button
        window.screenShareActive = true;
        cb(null, true);
      };
      const failedJoin = function failedJoin(err) {
        AgoraRTC.Logger.error("[ERROR] : join channel as screen-share failed", err);
        cb(err, null);
      };

      window.AGORA_UTILS.agora_generateAjaxToken(function resultToken(err, token) {
        if (err) {
          AgoraRTC.Logger.error("[TOKEN ERROR] : Get Token failed:", err);
          cb(err, null);
          return false;
        }

        window.screenClient.join(token, window.channelName, userId, successJoin, failedJoin);

      });

    }, function errorInit(err) {
      AgoraRTC.Logger.error("[ERROR] : getScreen failed", err);
      window.localStreams.screen.id = ""; // reset screen stream id
      window.localStreams.screen.stream = {}; // reset the screen stream
      window.screenShareActive = false; // resest screenShare
      cb(err, null);
      // window.AGORA_SCREENSHARE_UTILS.toggleScreenShareBtn(); // toggle the button icon back (will appear disabled)
      if (err && err.info) {
        alert('ScreenShare Error: ' + err.info);
      }
    });

  },

  stopScreenShare: function (cb) {
    //localStreams.screen.stream.muteVideo(); // disable the local video stream (will send a mute signal)
    localStreams.screen.stream.isPlaying() && localStreams.screen.stream.stop(); // stop playing the local stream
    // localStreams.camera.stream.enableVideo(); // enable the camera feed

    // var videoContainer = window.agoraMode==='communication' ? 'local-video' : 'full-screen-video';
    // localStreams.camera.stream && localStreams.camera.stream.play(videoContainer); // play the camera within the full-screen-video div
   
    // jQuery("#video-btn").prop("disabled",false);
    window.screenClient.leave(function() {
      AgoraRTC.Logger.info("screen client leaves channel");
      jQuery("#screen-share-btn").prop("disabled", false); // enable button

      window.screenClient.unpublish(localStreams.screen.stream); // unpublish the screen client
      localStreams.screen.stream.close(); // close the screen client stream
      localStreams.screen.id = ""; // reset the screen id
      localStreams.screen.stream = {}; // reset the stream obj
      window.screenShareActive = false; 
      cb && typeof cb==='function' && cb(null, true);
    }, function(err) {
      AgoraRTC.Logger.info("client leave failed ", err); //error handling
      cb && typeof cb==='function' && cb(err, null);
    }); 
  },


  addRemoteScreenshare: function (remoteStream, isLocal=false, content='') {
    let streamsContainer = jQuery('#screen-zone');
    //streamsContainer.toggleClass('sharescreen');
    streamsContainer.addClass('sharescreen');
    
    let streamId = remoteStream.getId();
    console.log('Adding remote screen share:', streamId);

    /* Code with Reemote Streams on right side - use for future */
    // if(window.isSpeakerViewWithRemoteRight){
    //   /* Set Screen Share Stream in main large screen */

    //   isMainStreamLocal = false;
    //   mainStreamId = jQuery('.main-screen #main-screen-stream-section div:first-child').attr('id');
    //   if(mainStreamId == 'local-video' || mainStreamId == 'full-screen-video'){
    //     isMainStreamLocal = true;
    //   } else {
    //     mainStreamId = jQuery('.main-screen #main-screen-stream-section').find('.remote-stream-container').attr('rel');
    //   }

    //   /* Stop Large Screen Stream */
    //   if(isMainStreamLocal){
    //     window.localStreams.camera.stream.stop();
    //   } else {
    //     window.remoteStreams[mainStreamId].stream.stop();
    //     jQuery('#'+mainStreamId+'_container .resume-userclick').remove();
    //   }

    //   let currMainStream = jQuery('.main-screen #main-screen-stream-section').html();

    //   streamsContainer = jQuery('.main-screen #main-screen-stream-section');
    //   streamsContainer.html(
    //     jQuery('<div/>', {'id': streamId + '_container',  'class': 'screenshare-container'}).append(
    //       jQuery('<div/>', {'id': streamId + '_mute', 'class': 'mute-overlay'}).append(
    //           jQuery('<i/>', {'class': 'fas fa-microphone-slash'})
    //       ),
    //       jQuery('<div/>', {'id': streamId + '_no-video', 'class': 'no-video-overlay text-center'}).append(
    //         jQuery('<i/>', {'class': 'fas fa-user'})
    //       ),
    //       jQuery('<div/>', {'id': 'agora_remote_' + streamId, 'class': 'remote-video'})
    //     )
    //   );

    //   jQuery("#screen-users").append("<div class='remote-stream-main-container'>"+currMainStream+"</div>");

    //   /* Play Large Screen Stream after moving into right side stream */
    //   if(isMainStreamLocal){
    //     window.localStreams.camera.stream.play(mainStreamId);
    //   } else {
    //     let remoteStream = window.remoteStreams[mainStreamId].stream;
        
    //     remoteStream.play('agora_remote_' + mainStreamId, function(err){
    //       if ((err && err.status !== "aborted") || (err && err.audio && err.audio.status !== "aborted")){
    //         jQuery('body #' + mainStreamId + '_container').prepend(
    //           addAudioErrorGesture(mainStreamId)
    //         )
    //       }  
    //       handleGhostMode(mainStreamId, 'remote');
    //     });
    //   }

    // } else 
    if(isLocal){ /* If the stream that is going to be in large screen is local */
      streamId = window.agoraMode==='communication' ? 'local-video' : 'full-screen-video';
      streamsContainer.append(
        jQuery('<div/>', {'id': streamId + '_container',  'class': 'screenshare-container', 'rel': streamId}).append(content)
      );
    } else {
      streamsContainer.append(
        jQuery('<div/>', {'id': streamId + '_container',  'class': 'screenshare-container', 'rel': streamId}).append(
          jQuery('<div/>', {'id': streamId + '_mute', 'class': 'mute-overlay'}).append(
              jQuery('<i/>', {'class': 'fas fa-microphone-slash'})
          ),
          jQuery('<div/>', {'id': streamId + '_no-video', 'class': 'no-video-overlay text-center'}).append(
            jQuery('<i/>', {'class': 'fas fa-user'})
          ),
          jQuery('<div/>', {'id': 'agora_remote_' + streamId, 'class': 'remote-video'})
        )
      );
    }

    /* If the stream that is going to be in large screen is local */
    if(isLocal){
    const localStreamDivId =  window.agoraMode==='communication' ? 'local-video' : 'full-screen-video';
    var remoteEl = document.getElementById(localStreamDivId);
    } else {
      var remoteEl = document.getElementById(streamId + '_container');
    }
    const divWidth = remoteEl.getBoundingClientRect().width;

    /* Code with Reemote Streams on right side - use for future */
    //if(!window.isSpeakerViewWithRemoteRight){
    // remoteEl.style.height = (divWidth / 1.35) + 'px'; // ratio 16:10
    //}

    remoteEl.style.height = (divWidth / 1.35) + 'px'; // ratio 16:10

    remoteEl.style.width = '100%';

    if(isLocal){
      streamId = window.agoraMode==='communication' ? 'local-video' : 'full-screen-video';
      remoteStream.play(streamId);
      var videoEl = document.getElementById(streamId).querySelector('video');
    } else {
      // Play the new screen stream
      remoteStream.play('agora_remote_' + streamId);
      var videoEl = document.getElementById('agora_remote_' + streamId).querySelector('video');
      window.AGORA_UTILS.handleStreamMuteOnPlay(remoteStream);
    }
    
    videoEl.style.objectFit = 'contain';
    videoEl.style.objectPosition = 'top';
    if(isLocal){
      handleGhostMode(streamId, 'local');
    } else {
      handleGhostMode(streamId, 'remote');
    }
  },
}