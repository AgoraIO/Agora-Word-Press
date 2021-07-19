<div class="<?php if(isset($channelSettings['type']) && $channelSettings['type'] == 'communication'){ echo 'communication-raise-hand-btn'; } else { echo 'fabs raise-hand-icon-fabs'; } ?>"
style="<?php if(!isset($channelSettings['type']) || $channelSettings['type'] != 'communication'){ echo 'display: none;'; } ?>"
>
<!-- Hide Raise Hand Icon by defeault for audience mode in boradcast channel type -->

	<div class="raise-hand-icon" id="raiseHand">
        <button class="btnIcon"><i class="far fa-hand-paper" title="Raise Hand"></i></button>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function(){
        showRaiseHandInCommunication();
    });

	window.addEventListener('agora.rtm_init', function loadChatApp() {
        if(window.agoraMode == 'audience' ||  window.agoraMode == 'communication'){
        const headTag = document.head || document.getElementsByTagName("head")[0];

        const chatStyles = document.createElement("link");
        chatStyles.rel = "stylesheet";
        chatStyles.href = `${window.agora_base_url}css/raise-hand.css`;
        headTag.appendChild(chatStyles);

        const arleneLib = document.createElement("script")
        arleneLib.type = "text/javascript";
        arleneLib.src = `${window.agora_base_url}js/raise-hand.js`;
        arleneLib.setAttribute('id', 'wp-agora-raise-hand-js');
        arleneLib.async = true;
        headTag.appendChild(arleneLib);
    } else {
        if(jQuery("script#wp-agora-raise-hand-js").length > 0){
            jQuery("#wp-agora-raise-hand-js").remove();
        }
    }
    });
</script>