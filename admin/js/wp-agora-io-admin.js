// var cloudRegions = {
// 	// North America in China?
//   'qiniu': ['East China', 'North China', 'South China', 'North America'],

//   'aws': ['US_EAST_1', 'US_EAST_2', 'US_WEST_1', 'US_WEST_2', 'EU_WEST_1', 'EU_WEST_2', 'EU_WEST_3', 'EU_CENTRAL_1', 'AP_SOUTHEAST_1', 'AP_SOUTHEAST_2', 'AP_NORTHEAST_1', 'AP_NORTHEAST_2', 'SA_EAST_1', 'CA_CENTRAL_1', 'AP_SOUTH_1', 'CN_NORTH_1', 'CN_NORTHWEST_1', 'US_GOV_WEST_1'],
  
//   'alibaba': ['CN_Hangzhou', 'CN_Shanghai', 'CN_Qingdao', 'CN_Beijing', 'CN_Zhangjiakou', 'CN_Huhehaote', 'CN_Shenzhen', 'CN_Hongkong', 'US_West_1', 'US_East_1', 'AP_Southeast_1', 'AP_Southeast_2', 'AP_Southeast_3', 'AP_Southeast_5', 'AP_Northeast_1', 'AP_South_1', 'EU_Central_1', 'EU_West_1', 'EU_East_1'],
// };

cloudRegions = JSON.parse(cloudRegions);

console.log("hlwcloudRegions", cloudRegions)

function updateSettingValue(settingName, newValue, callback) {
	var data = { action: 'save-agora-setting' };
	data[settingName] = newValue;
	
	var ajaxParams = {
		type: 'POST',
		url: ajaxurl, // from wp admin...
		data
	};
	
	jQuery.ajax(ajaxParams).then(function(data) {
		callback && callback(null, data);
	}).fail(function(error) {
		console.error('Ajax Error:', error);
		callback && callback(error, null);
	});
}

// jQuery App for each Setting box...
function applicationSettingsForm() {
	const scope = this;
	const settingName = scope.id;
	const srcLoader = AGORA_ADMIN_URL + 'css/loader.svg';
	var agoraSaving = false;

	jQuery(scope).parent().find('a.button').click(function(evt){
		evt.preventDefault();
		const actionButton = jQuery(this);
		actionButton.hide();
		
		const parentActions = actionButton.parent();
		parentActions.addClass('align-right')
		parentActions.addClass('agora-settings-actions')

		const valueBox = jQuery(scope).find('.value').eq(0);
		const isMasked = valueBox.data('masked');

		const currentValue = valueBox.text().trim();
		const showValue = isMasked ? '' : currentValue;
		// console.log('Value:', currentValue);

		const input = jQuery('<input type="text" style="width:100%" value="'+showValue+'" />');
		jQuery(valueBox).html(input);

		const saveBtn = jQuery('<a href="#" class="button button-primary" id="'+settingName+'-save'+'" style="margin:0 10px">Save</a>');
		const cancelBtn = jQuery('<a href="#" class="button button-secondary" id="'+settingName+'-cancel'+'">Cancel</a>');
		const errorBox = jQuery('<span class="error error-message"></span>');
		const agoraLoader = jQuery('<span class="agora-loader" style="display:none"><img src="' + srcLoader + '" width="32" /></span>');
		parentActions.append(agoraLoader);
		parentActions.append(errorBox);
		parentActions.append(saveBtn);
		parentActions.append(cancelBtn);

		const hideSaveButtons = function() {
			saveBtn.remove();
			cancelBtn.remove();
			errorBox.remove();
			agoraLoader.remove();

			parentActions.removeClass('align-right');
			actionButton.show();
		}

		const saveOption = function(btnEvt){
			btnEvt.preventDefault();
			if (agoraSaving) {
				return false;
			}

			var newValue = input.val();
			if (!newValue || newValue.length===0) {
				errorBox.text('Please insert a valid value')
				return false;
			}
			agoraLoader.show();
			errorBox.text('');
			saveBtn.attr('disabled', 'disabled');
			agoraSaving = true;
			updateSettingValue(settingName, newValue, function(err, res) {
				if (!err && res.updated===true) {
					hideSaveButtons();

					if (isMasked) {
						var out = '';
						for(var i=0; i<newValue.length-4;i++) out += '*';
						newValue = out + newValue.substring(newValue.length-4);
					}
					valueBox.html(newValue);
				} else {
					// TODO: Improve error messages!
					errorBox.text('Data not saved. Please, try again');
				}
				agoraSaving = false;
				agoraLoader.hide();
				saveBtn.attr('disabled', false);
			});

		};
		saveBtn.click(saveOption);
		input.keypress(function(evt){
			if (evt.keyCode===13) {
				evt.preventDefault();
				saveOption(evt);
			}
		})

		cancelBtn.click(function(btnEvt){
			btnEvt.preventDefault();
			hideSaveButtons();
			valueBox.html(currentValue);
		});

	});
};

function activateAgoraTabs() {

	// store tabs variables
	var tabs = document.querySelectorAll("ul.nav-tabs > li");

	for (i = 0; i < tabs.length; i++) {
		tabs[i].addEventListener("click", switchTab);
	}

	function switchTab(event) {
		event.preventDefault();
		if (event.target.disabled===true) {
			return false;
		}

		document.querySelector("ul.nav-tabs li.active").classList.remove("active");
		document.querySelector(".tab-pane.active").classList.remove("active");

		var clickedTab = event.currentTarget;
		var anchor = event.target;
		var activePaneID = anchor.getAttribute("href");

		clickedTab.classList.add("active");
		document.querySelector(activePaneID).classList.add("active");
	}
}


function removeChip(chipEl) {
	chipEl.parentElement.remove();
	const list = jQuery('#broadcast-users-list');
	if (list.find('.chip').length===0) {
		list.find('.helper-text').show();
	}
}
window.removeChip = removeChip;

function cancelAddMoreUsersRow() {
	jQuery('#host').val(null)
	toggleAddMoreUsersRow(false);
}

function showAddMoreUsersRow() {
	toggleAddMoreUsersRow(true);
}

function toggleAddMoreUsersRow(show) {
	jQuery('#add-user-error-msg').hide();
	const rootBtn = jQuery('#add-more-users');
	const row = jQuery('#add-more-users-controls');

	if (show) {
		rootBtn.hide();
		row.show();
	} else {
		row.hide();
		rootBtn.show();
	}
}

function loadHostBroadcastUsers(usersRaw) {
	const allUsersOptions = jQuery('#host option');
	const usernames = {}
	allUsersOptions.each((index, option) => {
		usernames[option.value] = option.text
	});
	// console.log('U:', usernames);
	usersRaw.forEach(uid => {
		renderUserChip(uid, usernames[uid])
	});
}

function renderUserChip(userId, name) {
	if (!userId || !name) {
		return;
	}
	
	const chipHTML = `
	<div class="chip" data-user-id="${userId}">
	  <img src="%img_avatar%" alt="user-${userId}" width="96" height="96">
	  ${name}
	  <span class="closebtn" onclick="window.removeChip(this)">&times;</span>
	</div>`;

	jQuery('#add-more-buttons').hide();
	jQuery('#add-more-loader').show();

	var params = {
      action: 'get_user_avatar', // wp ajax action
      uid: userId, // needed to get the avatar from the WP user
    };
    var ajaxParams = {
		type: 'POST',
		url: ajaxurl, // from wp admin...
		data: params
	};
	jQuery.ajax(ajaxParams).then(function(data) {
		const out = chipHTML.replace('%img_avatar%', data.avatar.url)
		jQuery('#broadcast-users-list').append(out);
		jQuery('#broadcast-users-list').find('.helper-text').hide();
		jQuery('#add-more-loader').hide();
		jQuery('#add-more-buttons').show();
		cancelAddMoreUsersRow();

		const usersRow = document.getElementById('broadcast-users-list');
		usersRow.style.borderColor = "#666"
	}).fail(function(error) {
		console.error('Avatar not supported:', error);
		callback && callback(error, null);
	});
}


function agoraChatChange() {
	const enabled = document.querySelector('#agora-chat-check').checked;
	const box = document.querySelector('#chat-status-text');
	const statusText = box.dataset[enabled ? 'enabled' : 'disabled'];
	box.innerText = statusText;

	updateSettingValue('agora-chat', statusText, function(err, res) {
		if (!res || !res.updated) {
			// TODO: Show error?
		}
	})
}

/* Function to update component's position through Drag-Drop Builder */
function agoraComponentPositionChange(component, position) {
	updateSettingValue(component, position, function(err, res) {
		if (!res || !res.updated) {
			// TODO: Show error?
		}
	})
}

function agoraChatChangeLoggedin() {
	const enabled = document.querySelector('#agora-chat-check-loggedin').checked;
	const box = document.querySelector('#chat-status-text-loggedin');
	const statusText = box.dataset[enabled ? 'enabled' : 'disabled'];
	box.innerText = statusText;

	updateSettingValue('agora-chat-loggedin', statusText, function(err, res) {
		if (!res || !res.updated) {
			// TODO: Show error?
		}
	})
}
(function( $ ) {
	'use strict';

	$( window ).load(function() {
		$('.app-setting').each(applicationSettingsForm);
		$('.agora-color-picker').wpColorPicker();
		if ($('#agoraio-new-channel').length>0) {
			activateAgoraTabs();
			$('.agora-color-picker').wpColorPicker();

			$('#type').change(validateChannelType);

			$('#protoType').change(validateRecordingType);

			const protoType = $('#protoType').val();
			if (protoType && protoType.length>0) {
				$('#protoType').change();
			}

			const channelType = $('#type').val();
			if (channelType && channelType.length>0) {
				$('#type').change();
			}
			$('#type').attr('required', 'required')

			$('#add-more-users').click(showAddMoreUsersRow);
			$('#agora-add-user').click(addBroadcastUser);
			$('#agora-cancel-add-user').click(cancelAddMoreUsersRow);

			$('#agoraio-admin-form-element').submit(submitNewChannel);

			const usersRow = $('#broadcast-users-list');
			if (usersRow.data('load-users')) {
				loadHostBroadcastUsers( usersRow.data('load-users') );
			}
		}

		if (document.querySelector('#agora-chat-check')) {
			$('#agora-chat-check').change(agoraChatChange);
			agoraChatChange();
		}

		if (document.querySelector('#agora-chat-check-loggedin')) {
			$('#agora-chat-check-loggedin').change(agoraChatChangeLoggedin);
			agoraChatChangeLoggedin();
		}
		//Save new global color settings - start
		jQuery(document).on('click','#globalColors-save',function(){
			$('#globalColors-save').prop('disabled', true);
			const srcLoader = AGORA_ADMIN_URL + 'css/loader.svg';
			const agoraLoader = jQuery('<span class="agora-loader" style="display:none"><img src="' + srcLoader + '" width="32" /></span>');
			jQuery(this).parents('inside').append(agoraLoader);
			agoraLoader.show();
			var settingName = 'global_colors';
			var globalColors = {};

			jQuery('#globalColors').find('.inputBoxGS').each(function(){
				var name_setting = jQuery(this).attr('name');
				var newValue = jQuery(this).val();
				globalColors[name_setting] = newValue;
			});
			updateSettingValue(settingName, globalColors, function(err, res) {
				if (!err && res.updated===true) {
					agoraLoader.hide();
					$('#globalColors-save').prop('disabled', false);
				} else {
					// TODO: Improve error messages!
					jQuery('.error-messageglobalColors').text(err);
				}
			});

		});
		//Save new global color settings - end


		//Save new global color settings - start
		jQuery(document).on('change','.NewSettingField',function(){
			var settingName = jQuery(this).attr('id');
			var newValue = jQuery(this).val();

			updateSettingValue(settingName, newValue, function(err, res) {
				if (!err && res.updated===true) {
					
				} else {
					// TODO: Improve error messages!
					jQuery('.error-messageglobalColors').text(err);
				}
			});

		});
		//Save new global color settings - end
	});


	function submitNewChannel(e) {
		const channelType = $('#type').val();

		// if (channelType==='communication') {
		// 	return true;
		// }

		const users = [];
		const usersRow = jQuery('#broadcast-users-list');
		usersRow.find('.chip').each(function getUserFromChip(){
			const uid = jQuery(this).data('user-id')
			users.push(uid)
		})

		if (users.length===0) {
			usersRow[0].style.borderColor = "red";
			document.getElementById('type').scrollIntoView({behavior: 'smooth'})
			e.preventDefault();
			return false;
		}
		usersRow.find('input').remove()
		users.forEach(uid => {
			usersRow.append(`<input type="hidden" name="host[]" value="${uid}" />`)
		});

		return true;
	}

	function addBroadcastUser() {
		const userId = $('#host').val();
		const name = $("#host option:selected").text();

		const existing = jQuery('#broadcast-users-list').find('[data-user-id="'+userId+'"]');
		if (existing.length>0) {
			jQuery('#add-user-error-msg').show();
			return;
		} else {
			jQuery('#add-user-error-msg').hide();
		}

		renderUserChip(userId, name);
	}

	function validateChannelType() {
		var typeChannel = $(this).val();
		var bhr = $('#broadcast-host-row');
		var mxur = $('#max_host_users-row');
		var linkTab2 = $('#link-tab-2');
		var linkTab3 = $('#link-tab-3');
		var splashImageURL = $('#splashImageURL').parent().parent();
		var watchButtonText = $('#watchButtonText').parent().parent();
		var watchButtonIcon = $('#watchButtonIcon').parent().parent();
		if (typeChannel==='communication') {
			//bhr.hide();
			bhr.show();
			mxur.show();
			linkTab2.parent().hide();
			linkTab3.parent().hide();
			splashImageURL.hide();
			watchButtonText.hide();
			watchButtonIcon.hide();
		} else {
			bhr.show();
			mxur.hide();
			linkTab2.parent().show();
			linkTab3.parent().show();
			splashImageURL.show();
			watchButtonText.show();
			watchButtonIcon.show();
		}


		// on communication, also hide Splash screen, and text and icon  button
	}

	function validateRecordingType() {
		var recordingType = $(this).val();
		var rlr = $('#recording_layout-row');
		if (recordingType==='composite') {
			rlr.show();
		} else {
			rlr.hide();
		}
		// on individual, hide the layout options
	}

	jQuery(document).on('change', 'select#recording_layout', function (e) {
		const recLayout = jQuery(this).val();
		const imgURL = plugineBaseURL+'/imgs/recordings/'+recLayout+'-layout.png';
		jQuery("body tr#recording_layout-row .recording_layout_image_section a").attr('href', imgURL);
		jQuery("body tr#recording_layout-row .recording_layout_image_section img").attr('src', imgURL);
	});

})( jQuery );

function updateRecordingShortcode(recType, channel_id){

	let shortcodeContent = '';
	if(recType!=''){
		shortcodeContent+=`<input type='text' onfocus='this.select();' readonly='readonly' value='[agora-recordings channel_id="${channel_id}" recording_type="${recType}"]' class='large-text code'>`
	}
	jQuery("body .recording-shortcode-row-"+channel_id).html(shortcodeContent);
}
