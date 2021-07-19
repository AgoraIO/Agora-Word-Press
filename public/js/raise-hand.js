
(function initRaiseHand($) {

	let raisedHand = false;

    if(window.agoraMode == 'audience'){

        /* Handle On Page Refresh */

        /* If user has raised hand and the refresh, skipping 0 - for logged out users, as on refresh, a random uid will be generated. */
        if(sessionStorage.getItem("raisedHandReqUserId")!=0 && sessionStorage.getItem("raisedHandReqUserId") == window.userID){
            jQuery("#raiseHand").attr("id", "cancelRaiseHand");
            jQuery("#cancelRaiseHand .hand-icon").attr('title', 'Cancel Raise Hand Request');
            
            //Again Send Peer Message if user has raised the hand, as we are updating the request object when user leave
            handleRaiseHandRequest('raiseHandOnRefresh');
        }
    }

    async function handleRaiseHandRequest(cond='') {
        if(window.agoraMode == 'communication'){
            let userName = 'Guest User with id - '+window.localStreams.uid;
            if(typeof window.wp_username!='undefined' && window.wp_username!=""){
                userName = window.wp_username;
            }
            const msg = {
                description: undefined,
                messageType: 'TEXT',
                rawMessage: undefined,
                text: 'RAISE-HAND-' + userName
            }
            try{
                window.AGORA_RTM_UTILS.sendChannelMessage(msg);
                //alert("Your Raise hand request has been sent.");
                showToastMsg('Raise Hand Request', "Your Raise hand request has been sent.");
            } catch(e) {

            }
        } else {
            let canJoinAsHostByAgoraLimit = await window.AGORA_UTILS.canJoinAsHostByAgoraLimit();
            if(canJoinAsHostByAgoraLimit){
                window.rtmChannel.getMembers().then(members => {
                    const adminUserRTMId = generateRTMUidfromStreamId(window.adminUser);
                    if(members.indexOf(adminUserRTMId) > -1){

                        let userName = 'Guest User with id - '+window.audienceUserId;
                        if(typeof window.wp_username!='undefined' && window.wp_username!=""){
                            userName = window.wp_username;
                        }

                        let memberId = adminUserRTMId;
                        const msg = {
                            description: undefined,
                            messageType: 'TEXT',
                            rawMessage: undefined,
                            text: 'RAISE-HAND-REQUEST-' + userName
                        }
                        try{
                            window.AGORA_RTM_UTILS.sendPeerMessage(msg, memberId);
                            jQuery("#raiseHand").attr("id", "cancelRaiseHand");
                            jQuery("#cancelRaiseHand .hand-icon").attr('title', 'Cancel Raise Hand Request');
                            if(canHandleStateOnRefresh()){
                                sessionStorage.setItem("raisedHandReqUserId", window.userID);
                            }
                            raisedHand = true;

                            //Not show alert on Page Refresh
                            if(cond!='raiseHandOnRefresh'){
                                //alert("Your Raise hand request has been sent.");
                                showToastMsg('Raise Hand Request', "Your Raise hand request has been sent.");
                            }
                        } catch(e){

                        }
                    } else {
                        //alert("You cannot raise hand right now as Admin User is not available.")
                        showToastMsg('Error', "You cannot raise hand right now as Admin User is not available.");
                    }
                })
            } else {
                showToastMsg('Error', "You cannot raise hand as host limit has been reached.");
            }
        }


        // const raiseHand = 'RAISE-HAND-' + window.audienceUserId;
        // window.AGORA_RTM_UTILS.sendChatMessage(raiseHand);
        // jQuery("#raiseHand").attr("id", "cancelRaiseHand");
        // raisedHand = true;


    }

    /* When user raises hand */
    jQuery("body").on("click","#raiseHand", function(){
        handleRaiseHandRequest();
    });

    /* When user cancel raise hand request */
    jQuery("body").on("click","#cancelRaiseHand", function handleCancelRaiseHandRequest() {

        window.rtmChannel.getMembers().then(members => {
            const adminUserRTMId = generateRTMUidfromStreamId(window.adminUser);
            if(members.indexOf(adminUserRTMId) > -1){

                let memberId = adminUserRTMId;
                const msg = {
                    description: undefined,
                    messageType: 'TEXT',
                    rawMessage: undefined,
                    text: 'CANCEL-RAISE-HAND-REQUEST-'+ window.audienceUserId
                }
                try{
                    window.AGORA_RTM_UTILS.sendPeerMessage(msg, memberId);
                    jQuery("#cancelRaiseHand").attr("id", "raiseHand");
                    jQuery("#raiseHand .hand-icon").attr('title', 'Raise Hand');
                    if(canHandleStateOnRefresh()){
                        sessionStorage.removeItem("raisedHandReqUserId");
                    }
                    raisedHand = false;
                } catch(e){

                }
            } else {
                //alert("You cannot cancel raise hand right now as Admin User is not available.")
                showToastMsg('Error', "You cannot cancel raise hand right now as Admin User is not available.");
            }
        })


    });


})(jQuery);