
(function initChatApp($) {
	const TOKEN_SEP = 'ππ';	
	const textarea = document.querySelector("#chatSend");
	const chatMsgWindow = $('#chat_fullscreen');
	const chatToggleBtn = $( window.agoraMode==='audience' ? '#chatToggleBtn' : '#chat-btn');
	const chatMinBtn = $('#chat-minimize');
	const chatNameInput = document.querySelector('#chat_name');
	const chatAlert = document.querySelector('#chat-alert');
	const chatRoot = $('.chat');
	let lastUserChat = '';
	let isUserJoinedChat = false;


	if (window.userID > 0) {
		document.querySelector('.fab_field.non-user').style.display='none';
		const dataJoin = 'CHAT-JOIN' + TOKEN_SEP + window.userID + TOKEN_SEP + window.wp_username;
		window.AGORA_RTM_UTILS.sendChatMessage(dataJoin);
		chatMsgWindow.css('display', 'block');
		jQuery(".chat .fab_field.user").css('display', 'flex');

		const localData = window.userID + TOKEN_SEP + window.wp_username;
		showUserNotify(localData, 'welcome')

		textarea.addEventListener('focus', scrollOnMobileView);
	} else {
		document.querySelector('.fab_field.user').style.display='none';
		chatMsgWindow.css('display', 'none');
		initNonUserActions();
	}

	// hide chat UI to start
	$('#fab_send').click(sendMessage);

	$('#chat_converse').css('display', 'none');
	$('#chat_body').css('display', 'none');
	$('#chat_form').css('display', 'none');

	//Toggle chat and links
	function toggleChatWindow() {

		if (window.AGORA_RTM_UTILS.connectionState!==AgoraRTM.ConnectionState.CONNECTED) {
			return false;
		}

		// chatToggleBtn.toggleClass('is-float');
		$('.prime').toggleClass('zmdi-comment-outline');
		$('.prime').toggleClass('zmdi-close');
		$('.prime').toggleClass('is-active');
		$('.prime').toggleClass('is-visible');
		$('.fab').toggleClass('is-visible');
		chatRoot.toggleClass('is-visible');

		if (chatRoot.hasClass('is-visible')) {
			scrollToBottm();
			if (window.wp_username) {
				textarea.focus()
				isUserJoinedChat = true;
			} else {
				chatNameInput.focus()
			}
		}

		// available only on broadcast and communication. not on audience view
		if (chatAlert) {
			chatAlert.style.opacity = 0;
		}
	}

	chatMinBtn.click(toggleChatWindow);
	chatToggleBtn.click(toggleChatWindow);

	window.toggleChatWindow = toggleChatWindow;


	function initNonUserActions() {
		document.querySelector('#fab_save_user').addEventListener('click', validateGuestUser);
		chatNameInput.addEventListener('keyup', validateKeyGuestUser);

		function validateKeyGuestUser(e) {
			if ( e.key==='Enter' ) { return validateGuestUser() }
		}

		function validateGuestUser() {
			const name = chatNameInput.value.trim();
			const label = document.getElementById('label_chat_name');
			if (name.length===0) {
				label.classList.add('error');
				return;
			}
			if (name.length<3) {
				label.classList.add('error');
				label.innerText = document.getElementById('error_name_length').value;
				return;
			}

			if (name.search(/\$|@|#|!|\?|<|>|\(|\)|;|:|\"|\'|\\|\{|\}|\[|\]|%|=|\+|\?|\/|\./) >= 0) {
				label.classList.add('error');
				label.innerText = document.getElementById('error_name_invalid').value;
				return;	
			}

			label.classList.remove('error')
			label.innerText = ''
			window.wp_username = name
			
			// Report to other about me!
			const data = 'CHAT-JOIN' + TOKEN_SEP + window.userID + TOKEN_SEP + window.wp_username;
			window.AGORA_RTM_UTILS.sendChatMessage(data);

			const localData = window.userID + TOKEN_SEP + window.wp_username;
			showUserNotify(localData, 'welcome')

			// Change the UI
			document.querySelector('.fab_field.non-user').style.display='none'
			document.querySelector('.fab_field.user').style.display='block'
			chatMsgWindow.css('display', 'block')
			jQuery(".chat .fab_field.user").css('display', 'flex');

			isUserJoinedChat = true;

			textarea.focus()
		}

		chatNameInput.addEventListener('focus', scrollOnMobileView)
	}

	function scrollOnMobileView() {
		if (window.innerWidth < 561) {
			this.scrollIntoView({behavior: "smooth", block: "center"});
		}
	}


	// resizable text area
	function calcHeight(value) {
	  let numberOfLineBreaks = (value.match(/\n/g) || []).length;
	  // min-height + lines x line-height + padding + border
	  let newHeight = 25;
	  if(numberOfLineBreaks > 0) {
	    newHeight = 25 + numberOfLineBreaks * 25 + 12 + 2;
	  }
	  
	  return newHeight;
	}

	// resize on key-up event
	textarea && textarea.addEventListener("keydown", resizeTextArea);

	function resizeTextArea(e) {
		if (e && e.key === 'Enter' && !e.shiftKey) {
			e.preventDefault();
			sendMessage();
	    	// textarea.value = textarea.value.substring(0, -1)
	    	return false;
	    }
	    textarea.style.height = calcHeight(textarea.value) + "px";
	}

	// chat msg UI
	function scrollToBottm() {
	  chatMsgWindow.animate({
	    scrollTop: chatMsgWindow[0].scrollHeight
	  }, 500);
	}

	function addLocalMsg(msg) {
		const msgLine = $('<div/>', {class: 'chat-msg-line local'});

		const user = window.wp_username;
		if (user !== lastUserChat) {
			lastUserChat = user;
			const msgTime = getMessageTime();
			const labelTxt = `${user} <time>${msgTime}</time>`;
			msgLine.append($('<label>', {class:'chat_username'}).append(labelTxt))
		}

		msgLine.append(
			$('<span/>', {'class': 'chat_msg_item chat_msg_item_local_user'}).append(msg)
		);
		chatMsgWindow.append(msgLine);
		// scroll to bottom
		scrollToBottm();
	}

	function getMessageTime() {
		const now = new Date();
		let hour = now.getHours();
		let suffix = 'am';
		if (hour===0) {
			hour = 12;
		}
		if (hour>12) {
			hour-=12;
			suffix = 'pm';
		}
		return `${hour}:${now.getMinutes()} ${suffix}`;
	}

	/* Handle File */
	var files = []; var processingFiles = [];

	/* Append File Name as message on select */
	jQuery("input[type='file']").change(function(){

		let fileName = jQuery(this).val().split(/(\\|\/)/g).pop();

		var file = jQuery(document).find('input[type="file"]');
		var individual_file = file[0].files[0];

		let obj = {name: fileName, data: individual_file};
		files.push(obj);

		let index = files.length-1;		
		appendTmpFileBeforeSend(index);
    });

	function appendTmpFileBeforeSend(index){
		let fileName = files[index].name;
		jQuery('body .agora .chat_converse').append("<div class='tmp_fileMsg' id='tmp_fileMsg-"+index+"'><div class='fileName'>"+fileName+"</div><div class='action'><a class='remove' rel='"+index+"'>X</a></div></div>");
		jQuery(document).find('input[type="file"]').val('');
	}

	function addLocalFileMsg(index, fileName) {
		const msgLine = $('<div/>', {class: 'chat-msg-line local'});

		const user = window.wp_username;
		if (user !== lastUserChat) {
			lastUserChat = user;
			const msgTime = getMessageTime();
			const labelTxt = `${user} <time>${msgTime}</time>`;
			msgLine.append($('<label>', {class:'chat_username'}).append(labelTxt))
		}

		msgLine.append(
			$('<span/>', {'class': 'chat_msg_item chat_msg_item_local_user'}).append(fileName+'<div class="progress"><div class="chat-file-progress-bar progress-bar-'+index+'"></div></div>')
		);
		chatMsgWindow.append(msgLine);
		// scroll to bottom
		scrollToBottm();
	}

	function uploadFile(index){

		console.log("hlwUploadfiles", processingFiles)
		
		let fileData = processingFiles[index].data;
		let fileName = processingFiles[index].name;

		var fd = new FormData();
		fd.append("file", fileData);
		fd.append("channel_id", window.channelId);
		fd.append('action', 'upload_chat_file');  

		jQuery.ajax({
			xhr: function() {
				var xhr = new window.XMLHttpRequest();
				xhr.upload.addEventListener("progress", function(evt) {
					if (evt.lengthComputable) {
						var percentComplete = ((evt.loaded / evt.total) * 100);
						jQuery(".progress-bar-"+index).width(percentComplete + '%');
						//jQuery(".progress-bar-"+index).html(percentComplete+'%');
					}
				}, false);
				return xhr;
			},
			type: 'POST',
			url: ajax_url,
			data: fd,
			contentType: false,
			cache: false,
			processData:false,
			beforeSend: function(){
				jQuery("body #tmp_fileMsg-"+index).remove();
				addLocalFileMsg(index, fileName);
				jQuery(".progress-bar-"+index).width('0%');
				//jQuery('#uploadStatus').html('<img src="images/loading.gif"/>');
			},
			error:function(){
				jQuery('#uploadStatus').html('<p style="color:#EA4335;">File upload failed, please try again.</p>');
			},
			success: function(resp){
				let response = JSON.parse(resp);
				if(response.status == 'ok'){
					const data = 'CHAT-FILE' + TOKEN_SEP + window.userID + TOKEN_SEP+ window.wp_username + TOKEN_SEP + fileName + TOKEN_SEP + response.fileURL;
					window.AGORA_RTM_UTILS.sendChatMessage(data, function() {
						saveChat('file', '<a href="'+response.fileURL+'">'+fileName+'</a>');
						jQuery(".progress-bar-"+index).css('background-color', '#228b22');
					});
				} else if(response.status == 'err'){
					jQuery(".progress-bar-"+index).css('background-color', '#8E020C');
					//$('#uploadStatus').html('<p style="color:#EA4335;">Please select a valid file to upload.</p>');
				}
			}
		});
	}

	jQuery("body").on("click", ".tmp_fileMsg .remove", function(){
		const index = jQuery(this).attr('rel');
		files.splice(index, 1);
		jQuery("body #tmp_fileMsg-"+index).remove();
	});

	/* End Handle File */

	/* Handle Chat History */

	function saveChat(type, msg){
		if(window.chat_history_enabled){
			var params = {
				action: 'save_chat', // wp ajax action
				channel_id: window.channelId,
				uid: window.userID,
				uname: window.wp_username,
				type: type,
				msg: msg
			};
			window.AGORA_UTILS.agoraApiRequest(ajax_url, params);
		}
	}

	function getPreviousChats(){

		let month = new Date().getMonth()+1;
		let todayDate = new Date().getFullYear()+'-'+month+'-'+new Date().getDate();
		console.log("hnjitodayDate",todayDate)
		var params = {
			action: 'get_previous_chats', // wp ajax action
			channel_id: window.channelId,
			timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
			username: window.wp_username,
			todayDate: todayDate 
		};

		window.AGORA_UTILS.agoraApiRequest(ajax_url, params).done(function(data) {
			let chatData = JSON.parse(data);
			console.log("previosChatsData", chatData.length)
			if(chatData.length>0){
				chatData.forEach(function (chat, index) {
					let avatarElement = '';
					let chat_msg_line_class = 'remote uid'+chat.user_id;
					let chat_msg_item_class = 'chat_msg_item_remote_user';
					if(chat.isLocalMessage){
						chat_msg_line_class = 'local';
						chat_msg_item_class = 'chat_msg_item_local_user';
					} else {
						avatarElement = $('<div/>', {'class': 'chat_avatar'});
						loadUserAvatar(chat.user_id, avatarElement[0]);
					}

					const msgLine = $('<div/>', {class: 'chat-msg-line ' + chat_msg_line_class});
			
					if(typeof chatData[index-1]=='undefined' || chatData[index].username!=chatData[index-1].username) {
						const labelTxt = `${chat.username} <time>${chat.time}</time>`;
						msgLine.append($('<label>', {class:'chat_username'}).append(labelTxt))
					}

					msgLine.append(
						$('<div/>', {'class': 'chat_msg_item '+chat_msg_item_class})
						.append(avatarElement)
						.append( $('<span/>').append(chat.message) )
					)

					chatMsgWindow.append(msgLine);

				});				  
			}
		}).fail(function(err) {
			console.error('Avatar not available:', err);
		});
	}
	/* End Handle Chat History */

	function showUserNotify(msgData, type) {
		const blocksMsg = msgData.split(TOKEN_SEP);
		const uid  = blocksMsg[0];
		const user = (blocksMsg[1] == 'undefined') ? 'anonymous user' : blocksMsg[1];

		const joinMsgEl = document.querySelector('#chat_notify_user_'+type);

		let joinMsg =  user + ' ' + joinMsgEl.value;
		if (type==='welcome') {
			// joinMsg = joinMsgEl.value + ' ' + user;
			joinMsg = user + ' joined the channel';
			getPreviousChats();
		} else if (type==='leave') {
			joinMsg = user + ' left the channel';
		}

		chatMsgWindow.append(
			$('<div/>', {class: 'chat-msg-line chat-notify uid' + uid})
			.append(`-- ${joinMsg} --`)
		);
	}

	window.AGORA_CHAT = {
		showUserNotify: showUserNotify,
		TOKEN_SEP: TOKEN_SEP
	};

	function addRemoteMsg(uidRTM, data) {
		if((isUserJoinedChat && window.chat_history_enabled) || !(window.chat_history_enabled)){
			let blocksMsg = data.split(TOKEN_SEP);
			let msg  = blocksMsg[2];
			let uid  = blocksMsg[0];
			let user = blocksMsg[1];

			let msgLink = '';

			if(blocksMsg[0] == 'CHAT-FILE'){
				uid  = blocksMsg[1];
				user = blocksMsg[2];
				msg  = blocksMsg[3];
				msgLink = blocksMsg[4];
			} 
			
			const msgLine = $('<div/>', {class: 'chat-msg-line remote uid' + uid});
				
			if (user !== lastUserChat) {
				lastUserChat = user;
				const msgTime = getMessageTime();
				const labelTxt = `${user} <time>${msgTime}</time>`;
				msgLine.append($('<label>', {class:'chat_username'}).append(labelTxt))
			}

			const avatarElement = $('<div/>', {'class': 'chat_avatar'});
			loadUserAvatar(uid, avatarElement[0]);

			if(msgLink!=''){
				msg = `<a href='${msgLink}' target='_blank'>${msg}</a>`;
			}

			msgLine.append(
				$('<div/>', {'class': 'chat_msg_item chat_msg_item_remote_user'})
				.append(avatarElement)
				.append( $('<span/>').append(msg) )
			)	

			chatMsgWindow.append(msgLine);
		}

		if (chatRoot.hasClass('is-visible')){
			scrollToBottm();
		} else if (chatAlert) {
			chatAlert.style.opacity = 1;
		}
		
	}

	function sendMessage() {
	  const msg = textarea.value.replace(/\n/g, '<br/>');
	  if (msg.length>0) {
	  	const uid = window.userID;
	  	const data = `CHATππ${uid}ππ${window.wp_username}ππ${msg}`;
		window.AGORA_RTM_UTILS.sendChatMessage(data, function() {
			addLocalMsg(msg);
			textarea.value = ""; // after the message is sent clear the text area.
			resizeTextArea();
		});
	  } 
	  
	  if(files.length>0){
		files.forEach(function (item, index) {
			processingFiles[index] = item;
			uploadFile(index);
		});	
		files = [];
	  }
	  if(msg!=""){
		saveChat('text', msg);
	  }
	}

	const loadedAvatars = {};
	function loadUserAvatar(uid, div) {
		function setSourceAvatar(url) {
			div.style.backgroundImage = `url(${url})`;
			div.style.backgroundSize = 'cover';
			div.style.backgroundRepeat = 'no-repeat';
		}

		if (loadedAvatars[uid]) {
			setSourceAvatar(loadedAvatars[uid])
			return;
		}
		const appendAvatar = function(avatarData) {
			if (avatarData && avatarData.avatar) {
				const url = avatarData.avatar.url
				setSourceAvatar(url);
				loadedAvatars[uid] = url;
			}
		};

		window.AGORA_UTILS.agora_getUserAvatar(uid, appendAvatar);
	}

	window.AGORA_RTM_UTILS.addLocalMsg = addLocalMsg;
	window.AGORA_RTM_UTILS.addRemoteMsg = addRemoteMsg;

	window.addEventListener('agora.rtmMessageFromChannel', receiveRTMMessage);

	// Event listener when user leave the current channel, on agora-rtm.js
	window.addEventListener('agora.leavingChannel', function leaveChat() {
		if (chatRoot.hasClass('is-visible')) {
			window.toggleChatWindow();
		}
		chatMsgWindow.empty();
		const uid = window.userID;
	  	const data = 'CHAT-LEAVE' + TOKEN_SEP + uid + TOKEN_SEP + window.wp_username;
		window.AGORA_RTM_UTILS.sendChatMessage(data);
	})


})(jQuery);