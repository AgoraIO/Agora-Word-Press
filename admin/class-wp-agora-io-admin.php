<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.agora.io
 * @since      1.0.0
 *
 * @package    WP_Agora
 * @subpackage WP_Agora/admin
 */
class WP_Agora_Admin {

	private $plugin_name;
	private $version;

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action('admin_menu', array($this,'register_admin_menu_pages'));
		// add_action('admin_init', array($this,'register_agora_settings'));
		if (is_admin()) {
			add_action( 'admin_enqueue_scripts', array($this, 'agora_enqueue_color_picker') );
		}

		// https://hugh.blog/2012/07/27/wordpress-add-plugin-settings-link-to-plugins-page/
		$name = $plugin_name.'/wp-agora-io.php';
		// add_filter('plugin_action_links_'.$name, array($this, 'plugin_add_settings_link') );

		add_action('wp_ajax_save-agora-setting', array($this, 'saveAjaxSettings'));
	}


	public function saveAjaxSettings() {
		global $wpdb;

		unset($_POST['action']);
		$keys = array_keys($_POST);
		$key = $keys[0];
		if($key != 'global_colors'){
			$value = sanitize_text_field( $_POST[$key] );
		}else{
			$value = sanitize_text_field($_POST[$key]);
		}
		
		$options = sanitize_option($this->plugin_name, get_option($this->plugin_name));
		$old_value = $options;

		if (!$options) {
			$options = array();
		}
		$options[$key] = $value;

		$r = false; 
		if (!$old_value) { 
			//echo '<pre>';print_r($this->plugin_name); echo '</pre>';die;
			$r = update_option( $this->plugin_name, $options);
			
		} else {
			// $r = update_option($this->plugin_name, $options);
			$serialized_value = maybe_serialize( $options );
				$update_args = array(
			'option_value' => $serialized_value,
			);
			
	    	$r = $wpdb->update(
					$wpdb->options,
					$update_args,
					array( 'option_name' => $this->plugin_name ) );
		}


		header('Content-Type: application/json');
		echo json_encode(array('updated' => ($r===true || $r===0 || $r===1)));
		wp_die();
	}

	public function register_admin_menu_pages() {
		global $_wp_last_object_menu;

		$_wp_last_object_menu++;
		$this->options = get_option( 'agoraio_data' );
		// create new admin page here...
		add_menu_page(
			__('Agora.io', 'agoraio'), 
			__('Agora.io', 'agoraio'), 
			'manage_options', 'agoraio',
			array($this, 'include_agora_channels_page'), 'dashicons-admin-settings',
			$_wp_last_object_menu );

		$list = add_submenu_page( 'agoraio',
			__( 'Agora Channels', 'agoraio' ),
			__( 'Agora Channels', 'agoraio' ),
			'manage_options', 'agoraio',
			array($this, 'include_agora_channels_page') );

		add_action( 'load-' . $list, array($this, 'agora_load_channel_pages'), 10, 0 );

		$addnew = add_submenu_page( 'agoraio',
			__( 'Add New Agora Channel', 'agoraio' ),
			__( 'Add New Channel', 'agoraio' ),
			'manage_options', 'agoraio-new-channel',
			array($this, 'include_agora_new_channel_page') );

		add_action( 'load-' . $addnew, array($this, 'agora_load_channel_pages'), 10, 0 );

		$settings = add_submenu_page( 'agoraio',
			__( 'Agora Settings', 'agoraio' ),
			__( 'Settings', 'agoraio' ),
			'manage_options', 'agoraio-settings',
			array($this, 'include_agora_settings_page') );

		add_action( 'load-' . $settings, array($this, 'agora_load_settings_pages'), 10, 0 );

	}

	public function include_agora_channels_page() {
		if ( $post = WP_Agora_Channel::get_current() ) {
			$this->create_agora_metaboxes_form();
			$post_id = $post->initial() ? -1 : $post->id();
			include_once('views/agora-admin-new-channel.php');
			return;
		}

		if ( ! class_exists( 'Agora_Channels_List_Table' ) ) {
		  require_once( 'class-agora-channels-list-table.php' );
		}
		$this->channels_obj = new Agora_Channels_List_Table();
		$this->channels_obj->prepare_items();
		include_once('views/agora-admin-channels.php');
	}

	private function create_agora_metaboxes_form() {
		add_meta_box(
    	'agora-form-settings',
    	__('Channel Settings', 'agoraio'),
    	'render_agoraio_channel_form_settings',
    	null,
    	'agora_channel_settings'
    );

    // Apperance metabox
    add_meta_box(
    	'agora-form-appearance',
    	__('Channel Appearance', 'agoraio'),
    	'render_agoraio_channel_form_appearance',
    	null,
    	'agora_channel_appearance'
    );

    // Recording metabox
    add_meta_box(
    	'agora-form-recording',
    	__('Channel Recording', 'agoraio'),
    	'render_agoraio_channel_form_recording',
    	null,
    	'agora_channel_recording'
    );

    // Custom Settings metabox
    add_meta_box(
    	'agora-form-chat',
    	__('Settings', 'agoraio'),
    	'render_agoraio_channel_form_chat_support',
    	null,
    	'agora_chat_support'
    );

    // Ghost Mode metabox
    /*add_meta_box(
    	'agora-ghost-mode',
    	__('Ghost Mode', 'agoraio'),
    	'render_agoraio_channel_form_ghost_mode',
    	null,
    	'agora_ghost_mode'
    );

    // Layout metabox
    add_meta_box(
    	'agora-layout',
    	__('Layout', 'agoraio'),
    	'render_agoraio_channel_form_layout',
    	null,
    	'agora_layout'
    );*/

		add_action( 'agoraio_channel_form_settings', array($this, 'handle_channel_form_metabox_settings'), 10, 1 );
		add_action( 'agoraio_channel_form_appearance', array($this, 'handle_channel_form_metabox_appearance'), 10, 1 );
		add_action( 'agoraio_channel_form_recording', array($this, 'handle_channel_form_metabox_recording'), 10, 1 );
		add_action( 'agoraio_channel_form_chat_support', array($this, 'handle_channel_form_chat_support'), 10, 1 );
		//add_action( 'agoraio_channel_form_ghost_mode', array($this, 'handle_channel_form_ghost_mode'), 10, 1 );
		//add_action( 'agoraio_channel_form_layout', array($this, 'handle_channel_form_layout'), 10, 1 );
	}

	public function include_agora_new_channel_page() {
		$post = WP_Agora_Channel::get_current();

		if ( !$post ) {
			$post = WP_Agora_Channel::get_template();
		}
		// die("<pre>P:".print_r($post, true)."</pre>");
 
    $this->create_agora_metaboxes_form();

		$post_id = $post->initial() ? -1 : $post->id();
		include_once('views/agora-admin-new-channel.php');
	}


	public function agora_enqueue_color_picker() {
		wp_enqueue_style ( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}


	// http://fieldmanager.org/docs/misc/adding-fields-after-the-title/
	// https://metabox.io/how-to-create-custom-meta-boxes-custom-fields-in-wordpress/
	public function handle_channel_form_metabox_settings($channel) {
	global $wp_meta_boxes;

		do_meta_boxes( get_current_screen(), 'agora_channel_settings', $channel );
		unset( $wp_meta_boxes['post']['agora_channel_settings'] );
	}

	public function handle_channel_form_metabox_appearance($channel) {
		global $wp_meta_boxes;

		do_meta_boxes( get_current_screen(), 'agora_channel_appearance', $channel );
		unset( $wp_meta_boxes['post']['agora_channel_appearance'] );
	}

	public function handle_channel_form_metabox_recording($channel) {
		global $wp_meta_boxes;

		do_meta_boxes( get_current_screen(), 'agora_channel_recording', $channel );
		unset( $wp_meta_boxes['post']['agora_channel_recording'] );
	}

	public function handle_channel_form_chat_support($channel) {
		global $wp_meta_boxes;

		do_meta_boxes( get_current_screen(), 'agora_chat_support', $channel );
		unset( $wp_meta_boxes['post']['agora_chat_support'] );
	}

	/*public function handle_channel_form_ghost_mode($channel) {
		global $wp_meta_boxes;

		do_meta_boxes( get_current_screen(), 'agora_ghost_mode', $channel );
		unset( $wp_meta_boxes['post']['agora_ghost_mode'] );
	}

	public function handle_channel_form_layout($channel) {
		global $wp_meta_boxes;

		do_meta_boxes( get_current_screen(), 'agora_layout', $channel );
		unset( $wp_meta_boxes['post']['agora_layout'] );
	}*/

	public function include_agora_settings_page() {
		$agora_options = get_option($this->plugin_name);
		include_once('views/agora-admin-settings.php');
	}

	// action load after post requests on new channel page
	public function agora_load_channel_pages() {
		global $plugin_page;
		$current_screen = get_current_screen();

		$action = agora_current_action();

		// die("<pre>AGORA Load action:".print_r($action, true)."</pre>");
		do_action(
			'agoraio_admin_load',
			isset( $_GET['page'] ) ? trim( sanitize_key($_GET['page']) ) : '',
			$action
		);

		if ( 'save' === $action ) {
			$id = isset( $_POST['post_ID'] ) ? sanitize_key($_POST['post_ID']) : '-1';
			check_admin_referer( 'agoraio-save-channel_' . $id );

			// save form data
			$agoraio_channel = $this->save_channel( $_POST );

			$query = array(
				'post' => $agoraio_channel ? $id : 0,
				'active-tab' => isset( $_POST['active-tab'] ) ? (int) sanitize_key($_POST['active-tab']) : 0,
			);

			if ( ! $agoraio_channel ) {
				$query['message'] = 'failed';
			} elseif ( -1 == $id ) {
				$query['message'] = 'created';
			} else {
				$query['message'] = 'saved';
			}
			$redirect_to = add_query_arg( $query, menu_page_url( 'agoraio', false ) );
			wp_safe_redirect( $redirect_to );
			exit();
		}

		if ( 'delete' == $action ) {
			if ( !empty( $_POST['post_ID'] ) ) {
				check_admin_referer( 'agora_delete_channel_' . sanitize_key($_POST['post_ID']) );
			} elseif ( isset($_REQUEST['channel']) && !is_array($_REQUEST['channel']) ) {
				check_admin_referer( 'agora_delete_channel_' . sanitize_key($_REQUEST['channel']) );
			} else {
				// TODO: Fix this validation later...
				// check_admin_referer( 'bulk-posts' );
			}

			$posts = empty( $_POST['post_ID'] )
				? (array) sanitize_key($_REQUEST['channel'])
				: (array) sanitize_key($_POST['post_ID']);

			$deleted = 0;

			foreach ( $posts as $post ) {
				$post = WP_Agora_Channel::get_instance( $post );
				
				if ( empty( $post ) ) {
					continue;
				}

				if ( ! current_user_can( 'edit_posts', $post->id() ) ) {
					wp_die( __( 'You are not allowed to delete this item.', 'agoraio' ) );
				}

				if ( ! $post->delete() ) {
					wp_die( __( 'Error in deleting.', 'agoraio' ) );
				}

				$deleted += 1;
			}

			$query = array();

			if ( ! empty( $deleted ) ) {
				$query['message'] = 'deleted';
			}

			$redirect_to = add_query_arg( $query, menu_page_url( 'agoraio', false ) );

			wp_safe_redirect( $redirect_to );
			exit();
		}

		$channel = null;
		if ( 'agoraio-new-channel' == $plugin_page ) {
			/* $channel = WP_Agora_Channel::get_template( array(
				'locale' => isset( $_GET['locale'] ) ? $_GET['locale'] : null,
			) ); */
		} else if ( ! empty( $_GET['channel'] ) ) {
			$channel = WP_Agora_Channel::get_instance( sanitize_key($_GET['channel']) );
			// die("<pre>EDIT: ".print_r($channel, true)."</pre>");
		}

		if ( $channel && current_user_can('edit_posts', $channel->id()) ) {
			// die("EDIT: <pre>".print_r($channel, true)."</pre>");
			// $this->include_agora_new_channel_page();

		} else {
			if ( ! class_exists( 'Agora_Channels_List_Table' ) ) {
			  require_once( 'class-agora-channels-list-table.php' );
			}

			add_filter( 'manage_' . $current_screen->id . '_columns',
				array( 'Agora_Channels_List_Table', 'define_columns' ), 10, 0 );

			add_screen_option( 'per_page', array(
				'default' => 20,
				'option' => 'agoraio_per_page',
			) );
		}

	}

	private function save_channel( $args ) {
		$args = wp_unslash( $args );
		
		$id = isset( $args['post_ID'] ) ? sanitize_key($args['post_ID']) : '-1';
		$args['id'] = (int) $id;

		if ( -1 == $args['id'] ) {
			$channel = WP_Agora_Channel::get_template();
		} else {
			$channel = WP_Agora_Channel::get_instance( $args['id'] );
		}

		$channel->save($args);

		return $channel;
	}

	public function agora_load_settings_pages() {
		global $plugin_page;
		$current_screen = get_current_screen();
	}

	public function plugin_add_settings_link($links) {
		$url = 'options-general.php?page='.$this->settings_slug;
		$links[] = '<a href="'. esc_url( get_admin_url(null, $url) ) .'">'.__('Settings').'</a>';

		return $links;
	}

	// Admin styles for settings pages...
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-agora-io-admin.css', array(), $this->version, 'all' );

		/* jQuery UI drag-drop CSS */
		wp_enqueue_style( $this->plugin_name.'-drag-drop-custom-css', plugin_dir_url( __FILE__ ) . 'css/wp-agora-io-drag-drop.css', $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name.'-jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css', $this->version, 'all' );

	}

	// Admin scripts for ajax requests on settings pages...
	public function enqueue_scripts() {
		
		$recordings_regions = WP_Agora_Public::$recordings_regions;
		?>
		<script> 
			var plugineBaseURL = "<?php echo plugins_url('wp-agora-io'); ?>";
			var cloudRegions = '<?php print_r(json_encode($recordings_regions)); ?>'; 
		</script>
		<?php 
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-agora-io-admin.js', array( 'jquery' ), $this->version, false );

		/* jQuery UI drag-drop JS */
		wp_enqueue_script( $this->plugin_name.'-jquery-ui', 'https://code.jquery.com/ui/1.12.1/jquery-ui.js', array( 'jquery' ), $this->version, false );
	}

}


function agora_current_action() {
	if ( isset( $_REQUEST['action'] ) and -1 != $_REQUEST['action'] ) {
		return sanitize_key($_REQUEST['action']);
	}

	return false;
}