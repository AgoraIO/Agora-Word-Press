<?php 

// Shortcode [agora-communication]
function renderCommnicationShortcode($agora, $attrs) {

  // Avoid duplicated shortcode rendered
  if (WP_Agora_Public::isShortcodeRendered('[agora-communication]')) {
    return "<!-- Shortcode Already Rendered: ".print_r($agora, true)." -->";
  }

  $instance = $agora->getShortcodeAttrs('agora-communication', $attrs);

  if (($err = $agora->validateShortcode($instance))!==false) {
    return $err;
  }

  $agora->enqueueShortcodeStyles('communication');
  
  wp_enqueue_script(
    'AgoraCommunicationClient',
    plugin_dir_url( __FILE__ ) . 'js/agora-communication-client.js',
    array('AgoraSDK'), $agora->version );

  $channel = WP_Agora_Channel::get_instance($instance['channel_id']);
  if ($channel) {
    $props = $channel->get_properties();
    $current_user = wp_get_current_user();

    ob_start();

    $host = is_array($props['host']) ? $props['host'] : array($props['host']);
    
    /* If user is in the list of broadcast users */
    if ( in_array($current_user->ID, $host) ) { ?>
      <script> window.joinAsHost = 1; </script>
    <?php } else { ?>
      <script> window.joinAsHost = 0; </script>
    <?php } ?>

    <script>
      if(sessionStorage.getItem("channelType")!= null){ //Check If there is any state value from session storage
        //Clear Previous state values if it was not from communication (it is from broadcast)
        //That will be the case when a user just change the URL (from broadcast to communication) and hits enter
        if(sessionStorage.getItem("channelType")!= 'communication'){
          sessionStorage.clear();
        }
      }

      //Save Value in Session Storage just to save the current channel type for reference - just for state rference on Refresh (that current state was from communication)
      sessionStorage.setItem("channelType", "communication");
    </script>
    
    <?php require_once('views/wp-agora-io-communication.php');

    $out = ob_get_clean();
  } else {
    $out = "<!-- Agora: No channel found! -->";
  }


  WP_Agora_Public::addShortcodeRendered('[agora-communication]');
  return $out;
}