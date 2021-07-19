<?php 

// Shortcode [agora-broadcast]
function renderBroadcastShortcode($agora, $attrs) {

  // Avoid duplicated shortcode rendered
  if (WP_Agora_Public::isShortcodeRendered('[agora-broadcast]')) {
    return "<!-- Shortcode Already Rendered: ".print_r($agora, true)." -->";
  }

  $instance = $agora->getShortcodeAttrs('agora-broadcast', $attrs);

  if (($err = $agora->validateShortcode($instance))!==false) {
    return $err;
  }

  $agora->enqueueShortcodeStyles('broadcast');

  $channel = WP_Agora_Channel::get_instance($instance['channel_id']);
  if ($channel) {
    $props = $channel->get_properties();
    $current_user = wp_get_current_user();

    ob_start();
    $host = is_array($props['host']) ? $props['host'] : array($props['host']);
    if ( in_array($current_user->ID, $host) ) {
      $agoraUserScript = 'js/agora-broadcast-client.js';
      require_once('views/wp-agora-io-broadcast.php');
    } else {
      // $agoraUserScript = 'js/agora-audience-client.js';
      require_once('views/wp-agora-io-audience.php');
    }
    $out = ob_get_clean(); ?>

    <script>
      if(sessionStorage.getItem("channelType")!= null){ //Check If there is any state value from session storage
        //Clear Previous state values if it was not from broadcast (or it is from communication)
        //That will be the case when a user just change the URL (from communication to broadcast) and hits enter
        if(sessionStorage.getItem("channelType")!= 'broadcast'){
          sessionStorage.clear();
        }
      }

      //Save Value in Session Storage just to save the current channel type for reference - just for state rference on Refresh (that current state was from broadcast)
      sessionStorage.setItem("channelType", "broadcast");
    </script>

    <?php
    if (isset($agoraUserScript) && $agoraUserScript!=='') {
      wp_enqueue_script(
        'AgoraBroadcastClient',
        plugin_dir_url( __FILE__ ) . $agoraUserScript,
        array('AgoraSDK'), null );
    }
    WP_Agora_Public::addShortcodeRendered('[agora-broadcast]');
  } else {
    $out = '<!-- Agora: No channel found! -->';
  }

  return $out;
}