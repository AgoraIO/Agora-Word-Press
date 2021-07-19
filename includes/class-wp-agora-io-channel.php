<?php

class WP_Agora_Channel {

  const post_type = 'agora_channel';

  private static $found_items = 0;
  private static $current = null;
  private static $defaultVideoSettings = array(
    'external-rtmpServerURL' => '',
    'external-streamKey' => '',
    'external-width' => 640,
    'external-height' => 360,
    'external-videoBitrate' => 400,
    'external-videoFramerate' => 15,
    'external-lowLatency' => false,
    'external-audioSampleRate' => 48000,
    'external-audioBitrate' => 48,
    'external-audioChannels' => 1,
    'external-videoGop' => 30,
    'external-videoCodecProfile' => 100,
    'external-backgroundColor' => '#efefef',
    'inject-width' => 640,
    'inject-height' => 360,
    'inject-videoBitrate' => 400,
    'inject-videoFramerate' => 15,
    'inject-lowLatency' => false,
    'inject-audioSampleRate' => 48000,
    'inject-audioBitrate' => 48,
    'inject-audioChannels' => 1,
    'inject-videoGop' => 30,
    'inject-videoCodecProfile' => 100,
    'inject-backgroundColor' => '#efefef',
  );
  private static $defaultAppearanceSettings = array(
    'splashImageURL' => '',
    'noHostImageURL' => '',
    'watchButtonText' => 'Watch the Live Stream',
    'watchButtonIcon' => true,
    'activeButtonColor' => '#343a40',
    'disabledButtonColor' => '#dc3545',
  );

  // https://docs.agora.io/en/cloud-recording/cloud_recording_api_rest?platform=All%20Platforms#parameters-1
  private static $defaultRecordingSettings = array(
    'vendor' => '',
    'region' => 0,
    'bucket' => '',
    'accessKey' => '',
    'secretKey' => '',
    'protoType' => '',
    'recording_layout' => ''
  );

  // private channel attrs
  private $id;
  private $name;
  private $title;
  private $locale;
  private $properties = array();
  // private $videoSettings = array();
  // private $appearanceSettings = array();

  public static function init_agoraio() {
    register_post_type( self::post_type, array(
      'labels' => array(
        'name' => __( 'Channels', 'agoraio' ),
        'singular_name' => __( 'Channel', 'agoraio' ),
      ),
      'rewrite' => false,
      'query_var' => false,
      'public' => false,
      'capability_type' => 'page',
    ));

    // Just in case, in the future I need to do something after register post type
    do_action( 'agora_init' );
  }

  public static function count() {
    return self::$found_items;
  }

  public static function get_current() {
    return self::$current;
  }

  public static function find( $args = '' ) {
    $defaults = array(
      'post_status' => 'any',
      'posts_per_page' => -1,
      'offset' => 0,
      'orderby' => 'ID',
      'order' => 'ASC',
    );

    $args = wp_parse_args( $args, $defaults );

    $args['post_type'] = self::post_type;

    $q = new WP_Query();
    $posts = $q->query( $args );

    self::$found_items = $q->found_posts;

    $objs = array();

    foreach ( (array) $posts as $post ) {
      $objs[] = new self( $post );
    }

    return $objs;
  }


  public static function get_template( $args = '' ) {
    global $l10n;

    $defaults = array( 'locale' => null, 'title' => '' );
    $args = wp_parse_args( $args, $defaults );

    $locale = $args['locale'];
    $title = $args['title'];

    if ( $locale ) {
      $mo_orig = $l10n['agoraio'];
      wpcf7_load_textdomain( $locale );
    }

    self::$current = $channel = new self;
    $channel->title = ( $title ? $title : __( 'Untitled', 'agoraio' ) );
    $channel->locale = ( $locale ? $locale : get_user_locale() );

    // $properties = $channel->get_properties();

    // foreach ( $properties as $key => $value ) {
      // $properties[$key] = WPCF7_ContactFormTemplate::get_default( $key );
    // }

    // $channel->properties = $properties;

    $channel = apply_filters( 'agoraio_default_pack', $channel, $args );

    if ( isset( $mo_orig ) ) {
      $l10n['agoraio'] = $mo_orig;
    }

    return $channel;
  }

  public static function get_instance( $post ) {
    $post = get_post( $post );

    if ( !$post || self::post_type !== get_post_type( $post ) ) {
      return false;
    }

    return self::$current = new self( $post );
  }

  private function __construct( $post = null ) {
    $post = get_post( $post );

    if ( $post && self::post_type == get_post_type( $post ) ) {
      $this->id = $post->ID;
      $this->name = $post->post_name;
      $this->title = $post->post_title;
      $this->locale = get_post_meta( $post->ID, '_locale', true );

      // $properties = $this->get_properties();
      $videoSettings = get_post_meta( $this->id, 'channel_video_settings', true );
      $appearanceSettings = get_post_meta( $this->id, 'channel_appearance_settings', true );
      $recordingSettings = get_post_meta( $this->id, 'channel_recording_settings', true );
      $channelType = get_post_meta( $this->id, 'channel_type', true );
      $channelUserHost = get_post_meta( $this->id, 'channel_user_host', true );
      
      if(get_post_meta( $this->id, 'ghost_mode', true )){
        $GhostMode = get_post_meta( $this->id, 'ghost_mode', true );
      }else{
        $GhostMode = 0;
      }
      
      if(get_post_meta( $this->id, 'channel_layout', true )){
        $channelLayout = get_post_meta( $this->id, 'channel_layout', true );
      }else{
        $channelLayout = 'grid';
      }
      if(get_post_meta( $this->id, 'chat_support_loggedin', true )){
        $ChatSupportloggedin = get_post_meta( $this->id, 'chat_support_loggedin', true );
      }else{
        $ChatSupportloggedin = 0;
      }
      if(get_post_meta( $this->id, 'mute_all_users', true )){
        $MuteAllUsers = get_post_meta( $this->id, 'mute_all_users', true );
      }else{
        $MuteAllUsers = 0;
      }
      if(get_post_meta( $this->id, 'chat_history', true )){
        $ChatHistory = get_post_meta( $this->id, 'chat_history', true );
      }else{
        $ChatHistory = 0;
      }
      if(get_post_meta( $this->id, 'pre_call_video', true )){
        $PreCallVideo = get_post_meta( $this->id, 'pre_call_video', true );
      }else{
        $PreCallVideo = 0;
      }

      if(get_post_meta( $this->id, 'admin_user', true)){
        $admin_user = get_post_meta( $this->id, 'admin_user', true);
      } else {
        $admin_user = '';
      }

      if(get_post_meta( $this->id, 'admin_user_unmute_forcefully', true)){
        $admin_user_unmute_forcefully = get_post_meta( $this->id, 'admin_user_unmute_forcefully', true);
      } else {
        $admin_user_unmute_forcefully = 0;
      }
      
      if(get_post_meta( $this->id, 'max_host_users', true)){
        $max_host_users = get_post_meta( $this->id, 'max_host_users', true);
      } else {
        $max_host_users = '';
      }

      $this->properties = array(
        'type' => $channelType,
        'host' => $channelUserHost,
        'settings' => $videoSettings,
        'appearance' => $appearanceSettings,
        'recording' => $recordingSettings,
        'chat_support_loggedin' => $ChatSupportloggedin,
        'ghost_mode' => $GhostMode,
        'channel_layout' => $channelLayout,
        'mute_all_users' => $MuteAllUsers,
        'chat_history' => $ChatHistory,
        'pre_call_video' => $PreCallVideo,
        'admin_user' => $admin_user,
        'admin_user_unmute_forcefully' => $admin_user_unmute_forcefully,
        'max_host_users' => $max_host_users
      );
      
      // $this->upgrade();
    }

    do_action( 'agoraio_channel', $this );
  }

  public function get_properties() {
    $properties = (array) $this->properties;
    $properties = wp_parse_args( $properties, array(
      'type' => '',
      'host' => array(),
      'settings' => array_merge(self::$defaultVideoSettings),
      'appearance' => array_merge(self::$defaultAppearanceSettings),
      'recording' => array_merge(self::$defaultRecordingSettings),
      'chat_support_loggedin' => 0,
      'ghost_mode' => 0,
      'channel_layout' => 'grid',
      'mute_all_users' => 0,
      'chat_history' => 0,
      'pre_call_video' => 0,
      'admin_user_unmute_forcefully' => 0,
      'admin_user' => '',
      'max_host_users' => ''
    ) );
    $properties = (array) apply_filters( 'agoraio_channel_properties', $properties, $this );
    return $properties;
  }

  public function save( $args ) {
    if ( $args['post_ID']==='-1' ) {
      // echo "<pre>".print_r('insert_post', true)."</pre>";
      $post_id = wp_insert_post( array(
        'post_type' => self::post_type,
        'post_status' => 'publish',
        'post_title' => sanitize_text_field($args['post_title']),
      ) );
    } else {
      $post_id = wp_update_post( array(
        'ID' => (int) $args['post_ID'],
        'post_status' => 'publish',
        'post_title' => sanitize_text_field($args['post_title']),
      ) );
    }
    
    $videoSettings = array();
    array_map(function($key) use ($args, &$videoSettings) {
      $videoSettings[$key] = sanitize_text_field($args[$key]);
      return $videoSettings[$key];
    }, array_keys(self::$defaultVideoSettings));

    $appearanceSettings = array();
    array_map(function($key) use ($args, &$appearanceSettings) {
      if ($key==='splashImageURL' || $key==='noHostImageURL') {
        $value = esc_url_raw($args[$key]);
      } else if ($key==='watchButtonText') {
        $value = sanitize_text_field($args[$key]);
      } else {
        $value = sanitize_text_field($args[$key]);
      }
      $appearanceSettings[$key] = $value;
      return $value;
    }, array_keys(self::$defaultAppearanceSettings));

    $recordingSettings = array();
    array_map(function($key) use ($args, &$recordingSettings) {
      $recordingSettings[$key] = sanitize_text_field($args[$key]);
      return $recordingSettings[$key];
    }, array_keys(self::$defaultRecordingSettings));
    
    update_post_meta($post_id, 'channel_video_settings', $videoSettings);
    update_post_meta($post_id, 'channel_appearance_settings', $appearanceSettings);
    update_post_meta($post_id, 'channel_recording_settings', $recordingSettings);
    update_post_meta($post_id, 'chat_support_loggedin', sanitize_key($args['chat_support_loggedin']));
    update_post_meta($post_id, 'ghost_mode', sanitize_key($args['ghost_mode']));
    update_post_meta($post_id, 'channel_layout', sanitize_key($args['channel_layout']));
    update_post_meta($post_id, 'channel_type', sanitize_key($args['type']));
    update_post_meta($post_id, 'mute_all_users', sanitize_key($args['mute_all_users']));
    update_post_meta($post_id, 'chat_history', sanitize_key($args['chat_history']));
    update_post_meta($post_id, 'pre_call_video', sanitize_key($args['pre_call_video']));
    update_post_meta($post_id, 'admin_user', sanitize_key($args['admin_user']));
    update_post_meta($post_id, 'admin_user_unmute_forcefully', sanitize_key($args['admin_user_unmute_forcefully']));
    update_post_meta($post_id, 'max_host_users', sanitize_key($args['max_host_users']));

    if (isset($args['host'])) {
      if (is_array($args['host'])) {
        $hosts = array_map('sanitize_key', $args['host']);
      } else {
        $hosts = sanitize_key($args['host']);
      }
      update_post_meta($post_id, 'channel_user_host', $hosts);
    }

    unset($args['_wp_http_referer']);
    unset($args['agoraio-locale']);
    unset($args['agoraio-save']);
    unset($args['active-tab']);
    unset($args['_wpnonce']);
    unset($args['action']);
    unset($args['post']);
    unset($args['page']);

    $args['id'] = $post_id;

    do_action( 'agoraio_after_save', $args );

    return $args;
  }

  public function delete() {
    if ( $this->initial() ) {
      resturn;
    }

    if ( wp_delete_post( $this->id, true ) ) {
      $this->id = 0;
      return true;
    }

    return false;
  }

  public function initial() {
    return empty( $this->id );
  }

  public function id() {
    return $this->id;
  }

  public function title() {
    return $this->title;
  }


  public function ghostmode() {
    return (int)$this->properties['ghost_mode'];
  }
  public function channellayout() {
    return $this->properties['channel_layout'];
  }
  public function mute_all_users() {
    return (int)$this->properties['mute_all_users'];
  }

  public function chat_history() {
    return (int)$this->properties['chat_history'];
  }

  public function pre_call_video() {
    return (int)$this->properties['pre_call_video'];
  }

  public function admin_user(){
    return $this->properties['admin_user'];
  }

  public function admin_user_unmute_forcefully(){
    return (int)$this->properties['admin_user_unmute_forcefully'];
  }

  public function max_host_users_limit(){
    return (int)$this->properties['max_host_users'];
  }

  public function host_users(){
    return json_encode(array_flip($this->properties['host']));
  }

  public function admin_user_config(){
    //return $this->admin_user();
    if($this->admin_user()!='' && $this->admin_user()==get_current_user_id()){
      return json_encode(array('is_admin' => 1,  'can_unmute_forecefully' => $this->admin_user_unmute_forcefully()));
    } else {
      return json_encode(array('is_admin' => 0,  'can_unmute_forecefully' => 0));
    }
    
  }

  public function type() {
    return ucfirst( $this->properties['type'] );
  }

  public function set_title( $title ) {
    $title = strip_tags( $title );
    $title = trim( $title );

    if ( '' === $title ) {
      $title = __( 'Untitled', 'contact-form-7' );
    }

    $this->title = $title;
  }

  public function locale() {
    if ( agoraio_is_valid_locale( $this->locale ) ) {
      return $this->locale;
    } else {
      return '';
    }
  }

  public function set_locale( $locale ) {
    $locale = trim( $locale );

    if ( agoraio_is_valid_locale( $locale ) ) {
      $this->locale = $locale;
    } else {
      $this->locale = 'en_US';
    }
  }

  public function isrecordingSettingsDone(){
    //$this->properties['recording']['protoType']
    $recordingSettings = $this->properties['recording'];

    $response = true;

    foreach($recordingSettings as $setting=>$value){
      if($value == ''){
        $response = false;
        break;
      }
    }
    
    return $response;
  }

  public function getRecordingType(){
    return $this->properties['recording']['protoType'];
  }

  public function shortcode($type='') {
    if($type == 'recording'){
      $recording_type = $this->properties['recording']['protoType'];
      return '[agora-recordings channel_id="'.$this->id.'" recording_type="'.$recording_type.'"]';
    } else {
      $type = $this->properties['type'];
      if($type==='broadcast') {
        return '[agora-broadcast channel_id="'.$this->id.'"]';
      } else if($type==='communication') {
        return '[agora-communication channel_id="'.$this->id.'"]';
      }
    }
  }

  public function shortcode_attr( $name ) {
    if ( isset( $this->shortcode_atts[$name] ) ) {
      return (string) $this->shortcode_atts[$name];
    }
  }

}


function agoraio_is_valid_locale($locale) {
  $pattern = '/^[a-z]{2,3}(?:_[a-zA-Z_]{2,})?$/';
  return (bool) preg_match( $pattern, $locale );
}
