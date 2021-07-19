

window.AGORA_RTM_UTILS = {
	connectionState: null,
	setupRTM: function(agoraAppId, channelName) {
		window.rtmClient = AgoraRTM.createInstance(agoraAppId, {logFilter: AgoraRTM.LOG_FILTER_ERROR});
  		window.rtmChannel = rtmClient.createChannel(channelName);

		window.rtmClient.on('ConnectionStateChanged', (newState, reason) => {
			window.AGORA_RTM_UTILS.connectionState = newState;
			console.log('RTM connection state changed to ' + newState + ' reason: ' + reason);
		});

		// event listener for receiving a peer-to-peer message.
		window.rtmClient.on('MessageFromPeer', (evt, senderId) => { 
			// console.log('Received RTM from peer:', evt)
			const { text } = evt;
			// text: text of the received message; senderId: User ID of the sender.
			AgoraRTC.Logger.info('AgoraRTM peer msg from user ' + senderId + ' received: \n' + text);
			const processed = processRtmRequest(text);
			if (!processed) {
				const msgEvent = {detail:{senderId, text}};
				window.dispatchEvent(new CustomEvent('agora.rtmMessageFromPeer', msgEvent))
			}
		});

		// event listener for receiving a channel message
		window.rtmChannel.on('ChannelMessage', (evt, senderId) => {
			// console.log('Received RTM from channel:', evt)
			const { text } = evt;
			// text: text of the received channel message; senderId: user ID of the sender.
			AgoraRTC.Logger.info('AgoraRTM msg from user ' + senderId + ' received: \n' + text);
			const processed = processRtmRequest(text);
			if (!processed) {
				const msgEvent = {detail:{senderId, text}};
				window.dispatchEvent(new CustomEvent('agora.rtmMessageFromChannel', msgEvent))
			}
		});

		window.rtmChannel.on('MemberJoined', memberId => {
			console.info('arrived joined:', memberId);
			updateUsersCount()

			// if i'm sharing my screen, update the new users layouts
		    if (window.localStreams && window.localStreams.screen.id && window.localStreams.screen.id>1) {
		      const msg = {
		        description: undefined,
		        messageType: 'TEXT',
		        rawMessage: undefined,
		        text: window.localStreams.screen.id + ': start screen share'
		      }
		      window.AGORA_RTM_UTILS.sendPeerMessage(msg, memberId);
		    }
		})

		window.rtmChannel.on('MemberLeft', memberId => {
			if(window.isAdminUser){ //If current user is admin user and the peer leaves then update the raise hand requests object
				removeRaiseHandReqContent(memberId);
			}
			updateUsersCount()
		})

		window.rtmChannel.onMemberCountUpdated = updateUsersCount;

		window.addEventListener("beforeunload", function(event) {
			window.AGORA_RTM_UTILS.leaveChannel();
		});
	},

	joinChannel: function(uid, next) {
		function runJoin(cb) {
			const token = window.AGORA_TOKEN_UTILS.agoraGenerateToken();
			const numberUID = uid < 1000 ? uid + 1000 : uid;
			const finalUID = 'x' + String(numberUID);

			const successToken = (err, token) => {
				if (err) {
					console.error('Token error', err);
					cb && cb(err, null);
					alert('Your Token Server is not Configured, this page will reload!');
					window.location.reload(true);
					return;
				}

				const loginData = { token, uid: finalUID };
				console.log('RTM UID:', loginData.uid)
				window.rtmClient.login(loginData).then(() => {
					console.log('Agora RTM client login success');

					window.rtmChannel.join().then(() => {
						cb && cb();
						window.dispatchEvent(new CustomEvent('agora.rtm_init'));
					}).catch(err => {
						console.error('RTM Join Error', err)
						window.rtmChannel = null;
						cb && cb(err, null)
					})

				}).catch(err => {
					console.error('Agora RTM login failure!', err);
					cb && cb(err, null)
				});
			};

			window.AGORA_UTILS.agora_generateAjaxTokenRTM(successToken, finalUID);
		}
		if (next) {
			runJoin(next)
		} else {
			return new Promise((resolve, reject) => {
				runJoin(function(err) {
					if (err) { reject(err); }
					else { resolve(); }
				})
			})
		}
	},

	leaveChannel: function() {
		if (window.AGORA_RTM_UTILS.connectionState===AgoraRTM.ConnectionState.CONNECTED) {
			window.rtmChannel.leave().then(() => {
				window.rtmClient.logout().then(() => {
					console.log('Agora RTM disconnected!')
					window.AGORA_RTM_UTILS.connectionState = AgoraRTM.ConnectionState.DISCONNECTED;
					jQuery('#count-users').html('');
				}).catch(err => {
					console.error('Failing logout RTM Client', err.message)
				})
			}).catch(err => {
		      console.error('Failing leaving rtm channel', err.message)
		    })
		} else {
			console.log('RTM channel already leaved')
		}
	},

	sendChannelMessage: function(msg, cb) {
		if (window.AGORA_RTM_UTILS.connectionState===AgoraRTM.ConnectionState.CONNECTED) {
			window.rtmChannel.sendMessage(msg, {enableHistoricalMessaging: false}).then(() => {
	          if (typeof cb === 'function') { cb() }
	        }).catch(error => {
	          // console.error('RTM Error', error)
	          console.log("Channel disconnected, message can't be sent:", msg)
	        });
	    	return true;
		}

		console.error('RTM Client is not connected!')

		return false;
	},

	sendPeerMessage: function(msg, peerId) {
		window.rtmClient.sendMessageToPeer(msg, peerId).then(() => {
			console.info('Reported ScreenShare to RTM client', peerId)
		}).catch(error => {
			console.error('RTM Error', error)
		});
	},

	sendChatMessage: function(msg, cb) {
		const chatMsg = {
          description: undefined,
          messageType: 'TEXT',
          rawMessage: undefined,
          text: msg
        }
        window.AGORA_RTM_UTILS.sendChannelMessage(chatMsg, cb);
	}
}


function updateUsersCount() {
	window.rtmChannel.getMembers().then(members => {
		// console.log('USERS ON THIS CHANNEL:', members.length)
		jQuery('#count-users').html(members.length);
	}).catch(err => {
		console.error('Members count error.', err);
		jQuery('#count-users').html('*');
	})
}

function processRtmRequest(value) {
	if (value.indexOf('start screen share')>0) {
		const msgParts = value.split(':');
		const uid = msgParts[0];
		if (window.screenshareClients[uid]) {
			console.log(uid, 'Already added as screenshare')
			return;
		}
		console.log('Adding remote screen share:', uid)

		window.screenshareClients[uid] = 1;

	    // in case the screen stream is already shown in the layout, it's needed to udpate the layout:
	    if (window.remoteStreams[uid]) {
			/*Add Streams to large view if there is no stream that is pinned in large screen */
			if(window.pinnedUser==''){
				// first remove the current screen stream from the normal users layout
				const screenStream = window.remoteStreams[uid].stream;
				window.AGORA_UTILS.deleteRemoteStream(uid);
				screenStream.stop();
				
				const usersCount = Object.keys(window.remoteStreams).length + 1
				window.AGORA_UTILS.updateUsersCounter(usersCount);

				window.AGORA_SCREENSHARE_UTILS.addRemoteScreenshare(screenStream);
				window.screenshareClients[uid] = screenStream;
			}
	  	}

	  	return true;
	}

	return false;
}