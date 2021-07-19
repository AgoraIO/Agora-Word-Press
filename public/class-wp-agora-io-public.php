<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.agora.io
 * @since      1.0.0
 *
 * @package    WP_Agora
 * @subpackage WP_Agora/public
 */
class WP_Agora_Public {

	private $plugin_name;
	public $version;

	public static $recordings_regions = array();

	private static $shortcodeRendered = array();

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->settings = null;
		
		self::$recordings_regions['qiniu'] = ['East China', 'North China', 'South China', 'North America'];
		self::$recordings_regions['aws'] = ['US_EAST_1', 'US_EAST_2', 'US_WEST_1', 'US_WEST_2', 'EU_WEST_1', 'EU_WEST_2', 'EU_WEST_3', 'EU_CENTRAL_1', 'AP_SOUTHEAST_1', 'AP_SOUTHEAST_2', 'AP_NORTHEAST_1', 'AP_NORTHEAST_2', 'SA_EAST_1', 'CA_CENTRAL_1', 'AP_SOUTH_1', 'CN_NORTH_1', 'CN_NORTHWEST_1', 'US_GOV_WEST_1'];
		self::$recordings_regions['alibaba'] = ['CN_Hangzhou', 'CN_Shanghai', 'CN_Qingdao', 'CN_Beijing', 'CN_Zhangjiakou', 'CN_Huhehaote', 'CN_Shenzhen', 'CN_Hongkong', 'US_West_1', 'US_East_1', 'AP_Southeast_1', 'AP_Southeast_2', 'AP_Southeast_3', 'AP_Southeast_5', 'AP_Northeast_1', 'AP_South_1', 'EU_Central_1', 'EU_West_1', 'EU_East_1'];

		//self::$recordings_regions = (object) self::$recordings_regions;

		// Declaration of shortcodes and widgets
		add_shortcode( 'agora-communication', array($this, 'agoraCommunicationShortcode') );
		add_shortcode( 'agora-broadcast', array($this, 'agoraBroadcastShortcode') );

		add_shortcode( 'agora-recordings', array($this, 'agoraRecordingsList') );

		$this->settings = get_option($this->plugin_name);
		if (!$this->settings) {
			$this->settings = array();
		}

		// Use this in case of you need custom pages or templates...
		// add_filter( 'template_include', array($this, 'agora_pages'), 99 );
		if (isset($this->settings['customerID'])) {
			require_once plugin_dir_path(dirname( __FILE__ )) . 'includes/class-wp-agora-cloud-recording.php';
			new AgoraCloudRecording($this->settings, $this);
		}

		$ajaxTokenServer = array($this, 'ajaxTokenServer');
	    add_action( 'wp_ajax_generate_token', $ajaxTokenServer );
	    add_action( 'wp_ajax_nopriv_generate_token', $ajaxTokenServer );

	    $userAvatarAjax = array($this, 'getUserAvatar');
	    add_action( 'wp_ajax_get_user_avatar', $userAvatarAjax );
	    add_action( 'wp_ajax_nopriv_get_user_avatar', $userAvatarAjax );

	    $globalColorsAjax = array($this, 'getglobalColors');
	    add_action( 'wp_ajax_get_global_colors', $globalColorsAjax );
	    add_action( 'wp_ajax_nopriv_get_global_colors', $globalColorsAjax );

		/* Ajax to handle Chat File Upload */
		$uploadChatFileAjax = array($this, 'uploadChatFile');
	    add_action( 'wp_ajax_upload_chat_file', $uploadChatFileAjax );
	    add_action( 'wp_ajax_nopriv_upload_chat_file', $uploadChatFileAjax );

		/* Ajax to handle Chat History if it is enabled */
		$saveChatAjax = array($this, 'saveChat');
	    add_action( 'wp_ajax_save_chat', $saveChatAjax );
	    add_action( 'wp_ajax_nopriv_save_chat', $saveChatAjax );

		/* Ajax to handle get Chat History if it was saved */
		$getChatsAjax = array($this, 'getChatsFromHistory');
	    add_action( 'wp_ajax_get_previous_chats', $getChatsAjax );
	    add_action( 'wp_ajax_nopriv_get_previous_chats', $getChatsAjax );

		/* Ajax to handle when a Raise hand Request is accepted in Broadcast channel */
		$loadHostViewAjax = array($this, 'load_host_view');
		add_action('wp_ajax_load_host_view', $loadHostViewAjax);
		add_action('wp_ajax_nopriv_load_host_view', $loadHostViewAjax);

		/* Ajax to handle when a user is above the mentioned hosts limit in communication channel */
		$loadAudienceViewAjax = array($this, 'load_audience_view');
		add_action('wp_ajax_load_audience_view', $loadAudienceViewAjax);
		add_action('wp_ajax_nopriv_load_audience_view', $loadAudienceViewAjax);

	    // Page Template loader for FullScreen
	    require_once plugin_dir_path(dirname( __FILE__ )) . 'public/class-wp-agora-page-template.php';
	    new WP_Agora_PageTemplate($this);

	    require_once(__DIR__.'/../includes/token-server/RtcTokenBuilder.php');
	    require_once(__DIR__.'/../includes/token-server/RtmTokenBuilder.php');
	}

	public function getglobalColors() {
		$agora_options = sanitize_option($this->plugin_name, get_option($this->plugin_name));

		header('Content-Type: application/json');
		echo json_encode(array( "global_colors" => $agora_options['global_colors'] ));

		wp_die();
	}

	public function load_host_view(){
		ob_start();

		$page_title = sanitize_text_field($_POST['page_title']);

		$channel_id = sanitize_text_field($_POST['channel_id']);
		$channel = WP_Agora_Channel::get_instance($channel_id);
		$agora = $this;

		$instance = $agora->getShortcodeAttrs('agora-broadcast', []);
		$current_user = wp_get_current_user();
		$agoraUserScript = plugin_dir_url( dirname( __FILE__ ) ).'public/js/agora-broadcast-client.js';
		?>
		<script>
			jQuery('<script />', { type : 'text/javascript', src : "<?php echo $agoraUserScript; ?>"}).appendTo('body');
			window.roleFromAudienceToHost = true;
		</script>
		<?php

		include(__DIR__.'/views/wp-agora-io-broadcast.php');
		wp_die();
	}

	public function load_audience_view(){
		ob_start();

		$page_title = sanitize_text_field($_POST['page_title']);
		$channel_id = sanitize_text_field($_POST['channel_id']);
		$channel = WP_Agora_Channel::get_instance($channel_id);

		$agora = $this;
		$instance = $agora->getShortcodeAttrs('agora-communication', []);
		$current_user = wp_get_current_user();
		?>

		<script>
			window.roleFromHostToAudience = true;
		</script>

		<?php
		include(__DIR__.'/views/wp-agora-io-audience.php');
		wp_die();
	}

	/* Function to get chats from the databse */
	public function getChatsFromHistory(){
		global $wpdb;
		$table_name = $wpdb->prefix . 'agora_io_chats';

		$channel_id = sanitize_text_field($_POST['channel_id']);
		$timezone = sanitize_text_field($_POST['timezone']);
		$username = sanitize_text_field($_POST['username']);
		$todayDate = sanitize_text_field($_POST['todayDate']);

		$getChatsQuery = "SELECT * from $table_name where channel_id = '$channel_id'";
		$results = $wpdb->get_results($getChatsQuery);
		if(!empty($results)){	
			foreach($results as $result){
				$dateInLocalTimezone = strtotime($this->convertToTimezone(date("Y-m-d H:i:s", $result->time), $timezone));
				$result->time = date("Y-m-d h:i a", $dateInLocalTimezone);

				/* If message date is equal to today's date the, return only time */
				if(strtotime($todayDate) == strtotime(date("Y-m-d", $dateInLocalTimezone))){
					$result->time = date("h:i a", $dateInLocalTimezone);
				}
				$result->isLocalMessage = false;
				if((is_user_logged_in() && $result->user_id == get_current_user_id()) || ($username==$result->username)){
					$result->isLocalMessage = true;
				}
			}
		}
		echo json_encode($results);
		wp_die();
	}

	/* Function to save chat in the database if chat is enabled */
	public function saveChat(){
		global $wpdb;

		$channel_id = sanitize_text_field($_POST['channel_id']);
		$user_id = sanitize_text_field($_POST['uid']);
		$username = sanitize_text_field($_POST['uname']);
		$type = sanitize_text_field($_POST['type']);
		$message = sanitize_text_field($_POST['msg']);
		$time = strtotime(date("Y-m-d H:i:s"));
		$created_on = date("Y-m-d H:i:s");

		$table_name = $wpdb->prefix . 'agora_io_chats';

		/* Create Chat History table if it doesn't exit */
		$chat_history_table_sql = "CREATE TABLE IF NOT EXISTS $table_name ( 
			id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
			channel_id INT(255),
			user_id INT NOT NULL DEFAULT 0,
			username VARCHAR(255),
			type VARCHAR(255) DEFAULT 'text',
			time VARCHAR (255),
			message TEXT,
			created_on DATETIME
			)";
		$wpdb->query($chat_history_table_sql );

		$saveChat_query = "INSERT INTO $table_name ( user_id, username, channel_id, type, message, time, created_on) VALUES ('$user_id', '$username', '$channel_id', '$type', '$message', '$time' ,'$created_on')";
		$wpdb->query($saveChat_query);
		wp_die();
	}

	public function uploadChatFile(){
		$response = array(
			'fileURL' => '',
			'status' => 'err'
		);
		//$upload = 'err'; 
		if(!empty($_FILES['file'])){ 

			$channel_id = sanitize_text_field($_POST['channel_id']);

			$targetDirURL = plugin_dir_url( dirname( __FILE__ ) ).'/uploads/'.$channel_id.'/';
			
			// File upload configuration 
			$targetDirPath = plugin_dir_path( dirname( __FILE__ ) ).'/uploads/'.$channel_id.'/';
			if (!file_exists($targetDirPath)) {
				mkdir($targetDirPath);
			}			
			
			$file = sanitize_file_name($_FILES['file']['name']);

			/* Create unique name of file */
			$fileName = pathinfo($file, PATHINFO_FILENAME);
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			$newFileName = $fileName.'-'.uniqid().'.'.$ext;

			$targetFilePath = $targetDirPath.$newFileName; 
			
			// Upload file to the server 
			if(move_uploaded_file($_FILES['file']['tmp_name'], $targetFilePath)){ 
				//$upload = 'ok'; 
				$response = array(
					'fileURL' => $targetDirURL.$newFileName,
					'status' => 'ok'
				);
			}
		} 
		//echo $upload;
		echo json_encode($response);
   		exit();
	}

	public function convertToTimezone($date, $currentTimezoneName){
		$timezone_name = date_default_timezone_get();		
		$date = new DateTime($date, new DateTimeZone($timezone_name));		
		$date->setTimezone(new DateTimeZone($currentTimezoneName));
		$date->format('Y-m-d H:i:s') . "\n";
		return $date->format('Y-m-d H:i:s') . "\n";		
	}

	public function getUserAvatar() {
		$uid = isset($_POST['uid']) ? sanitize_key($_POST['uid']) : 0;
		$avatar = get_avatar_data( $uid, array('size' => 168) );
		$user = get_user_by('ID', $uid);
		$userData = null;
		if ($user) {
			$userData = array(
				'display_name' => $user->data->display_name,
				'user_nicename' => $user->data->user_nicename
			);
		}

		header('Content-Type: application/json');
		echo json_encode(array( "avatar" => $avatar, "user" => $userData  ));

		wp_die();
	}

	public function ajaxTokenServer() {
		
		header('Content-Type: application/json');
		if (!isset($_POST['cid'])) {
			header("HTTP/1.1 404 Channel Not Found"); 
			echo '{"error": "Undefined channel!", "code": "404"}';
			wp_die();
			return;
		}


		$appID = $this->settings['appId'];
	    $appCertificate = $this->settings['appCertificate'];
	    
	    if($appCertificate && strlen($appCertificate)>0) {
			$cid = isset($_POST['cid']) ? sanitize_key($_POST['cid']) : 0;
				
			$current_user = wp_get_current_user();
	    	$uid = isset($_POST['uid']) ? sanitize_key($_POST['uid']) : $current_user->ID; // Get current user id
	    	// die("<pre>".print_r($uid, true)."</pre>");
	    	// $uid = intval($uid);

	    	// RTM or RTC
	    	$tokenType = isset($_POST['type']) ? sanitize_key($_POST['type']) : 'RTC';

			$token = $this->generateNewToken($cid, $uid, $tokenType);

			if (is_wp_error( $token )) {
				header("HTTP/1.1 404 Channel Not Found"); 
				echo '{"error": "Channel!", "code": "404"}';
				wp_die();
				return;
			}

	      echo json_encode(array( "token" => $token ));
	    } else {
	      header("HTTP/1.1 400 Token not configured"); 
				echo '{"error": "Token Server not configured!", "code": "400"}';
	    }

	    wp_die();
	}

	//
	public function generateNewToken($channel_id, $uid, $tokenType) {
		$channel = WP_Agora_Channel::get_instance($channel_id);
		if (!$channel->id()) {
			return new WP_Error('Channel', 'Channel not found');
		}

		$appID = $this->settings['appId'];
    	$appCertificate = $this->settings['appCertificate'];

		$channelName = $channel->title();
		
	    // role should be based on the current user host...
	    // $settings = $channel->get_properties();
	    // $current_user = wp_get_current_user();

	    // TODO: Validate if this should be changed according to the current user and current shortcode from the ajax call...
	    $privilegeExpireTs = 0;

	    if ($tokenType==='rtm') {
	    	// $appID, $appCertificate, $userAccount, $role, $privilegeExpireTs
	    	$role = '1';
	    	$token = AgoraRtmTokenBuilder::buildToken($appID, $appCertificate, $uid, $role, $privilegeExpireTs);
	    } else {
	    	$role = 'Role_Publisher';
	    	$token = AgoraRtcTokenBuilder::buildTokenWithUid($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpireTs);
	    }

	    return $token;
	}

	/**  Render Agora Commnication shortcode **/
	public function agoraCommunicationShortcode( $atts ) {
		require_once("shortcode-agora-communication.php");
		require_once(__DIR__.'/../includes/token-server/RtcTokenBuilder.php');

		return renderCommnicationShortcode( $this, $atts );
	}

	/**  Get Agora Recordings List Shortcode shortcode **/
	public function agoraRecordingsList( $atts ){
		require_once(__DIR__.'/views/wp-agora-io-recordings.php');
		return getRecordingsList($atts);
	}

	/**  Render Agora Broadcast shortcode **/
	public function agoraBroadcastShortcode( $atts ) {
		require_once("shortcode-agora-broadcast.php");
		require_once(__DIR__.'/../includes/token-server/RtcTokenBuilder.php');

		return renderBroadcastShortcode( $this, $atts );
	}

	public function enqueueShortcodeStyles($type) {
		$bootstrap_js = plugin_dir_url( __FILE__ ) . 'js/bootstrap/bootstrap.min.js';
		$bootstrap_popper_js = plugin_dir_url( __FILE__ ) . 'js/bootstrap/popper.min.js';
	  	$fontawesome = plugin_dir_url( __FILE__ ) . 'css/fontawesome/css/all.min.css';


		//wp_enqueue_script( 'AgoraSDK', plugin_dir_url( __FILE__ ).'js/agora/AgoraRTCSDK-3.2.1.100.js', array('jquery'), null );
		wp_enqueue_script( 'AgoraSDK', plugin_dir_url( __FILE__ ).'js/agora/AgoraRTCSDK-3.5.2.js', array('jquery'), null );
		wp_enqueue_script( 'AgoraRTM', plugin_dir_url( __FILE__ ).'js/agora/agora-rtm-sdk-1.2.2.js', array('jquery'), null );
		
		wp_enqueue_style( 'fontawesome', $fontawesome, array('bootstrap'), null, 'all' );
		wp_enqueue_script( 'bootstrap_popper', $bootstrap_popper_js, array('jquery'), null );
		wp_enqueue_script( 'bootstrap_js', $bootstrap_js, array('jquery'), null );

		wp_enqueue_script( $this->plugin_name.'-screen', plugin_dir_url( __FILE__ ) . 'js/screen-share.js', array( 'jquery' ), $this->version, true );

		wp_enqueue_script( 'AgoraRTM-setup', plugin_dir_url( __FILE__ ).'js/agora-rtm.js', array('AgoraRTM'), $this->version, true );

		$scriptUI = $type==='broadcast' ? 'js/broadcast-ui.js' : 'js/communication-ui.js';
		wp_enqueue_script( $this->plugin_name.'-ui', plugin_dir_url( __FILE__ ) . $scriptUI, array( 'jquery' ), $this->version, false );

	}


	public function getShortcodeAttrs($shortcode, $attrs) {
		$instance = shortcode_atts(
	      array(
	        'channel_id' => 0,
	        'audio' => 'true',
	        'video' => 'true',
	        'screen' => 'false',
	        'screenprofile' => '720p_1',
	        'videoprofile' => '480p_9' // https://docs.agora.io/en/Video/API%20Reference/web/interfaces/agorartc.stream.html#setvideoprofile
	      ), $attrs, $shortcode );

	  if(!$instance) { $instance = []; }

	  if ($instance['video']===$instance['screen']) {
	    // Show some error???
	    $instance['screen'] = $instance['video']==='false' ? 'true' : 'false';
	  }

	  return $instance;
	}

	public function validateShortcode($instance) {
		if (((int)$instance['channel_id'])===0) {
	    return '<p class="error">'.__('Please define the <b>channel_id</b> attribute to use on this shortcode', 'agoraio').'</div>';
	  }


	  if (!isset($this->settings['appId'])) {
	    return '<p class="error">'.__('Please configure your <b>Agora App ID</b> before use this shortcode', 'agoraio').'</div>';
	  }

	  return false;
	}


	// Overall public styles
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-agora-styles.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		/* Include JS file to handle audio error on autoplay */
		wp_enqueue_script( $this->plugin_name.'-agora-stream-audioErr', plugin_dir_url( __FILE__ ) . 'js/agora-stream-audioErr.js', array( 'jquery' ), $this->version, false );

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-agora-io-public.js', array( 'jquery' ), $this->version, false );

		/*Include JS file to handle pre-call device test if it was enabled */
		$channelRef = WP_Agora_Channel::get_current();
		if(isset($channelRef)){
			$pre_call_test_enabled = $channelRef->pre_call_video();
			if(isset($pre_call_test_enabled) && $pre_call_test_enabled){
				wp_enqueue_script( $this->plugin_name.'-agora-deviceTest-js', plugin_dir_url( __FILE__ ) . 'js/wp-agora-io-device-test.js', array( 'jquery' ), $this->version, false );
			}
		}

		wp_enqueue_script($this->plugin_name.'-hls-player-js', 'https://cdn.jsdelivr.net/npm/hls.js@latest', array( ), $this->version, false);

		// add data before JS plugin
		// useful to load dynamic settings and env vars
		add_action( 'wp_footer', array($this, 'createPublicJSvars'), 1);
	}

		// Create public JS Variables to pass to external script
	public function createPublicJSvars () {
		$vars = 'var ajax_url="'.admin_url( 'admin-ajax.php' ).'";';
		$vars.= 'var page_title="'.get_the_title().'";';

		// append here more settings vars
		
		// return $vars;
		echo '<script>'.$vars.'</script>';
	}

	public static function isShortcodeRendered($shortcode) {
    return isset(self::$shortcodeRendered[$shortcode]);
  }

  public static function addShortcodeRendered($shortcode) {
    self::$shortcodeRendered[$shortcode] = true;
  }

}
