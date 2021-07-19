<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Create a new table class that will extend the WP_List_Table
 *
 * @link       https://www.agora.io
 * @since      1.0.0
 *
 * @package    WP_Agora
 * @subpackage WP_Agora/admin
 */
class Agora_Channels_List_Table extends WP_List_Table {

  public static function define_columns() {
    $columns = array(
      'cb' => '<input type="checkbox" />',
      'title' => __( 'Title', 'agoraio' ),
      'type' => __( 'Type', 'agoraio' ),
      'shortcode' => __( 'Shortcode', 'agoraio' ),
      'recordings' => __( 'Recordings', 'agoraio' ),
      'date' => __( 'Date', 'agoraio' ),
    );

    return $columns;
  }

  public function __construct() {
    parent::__construct( array(
      'singular' => 'channel',
      'plural' => 'channels',
      'ajax' => false,
    ) );
  }

  public function get_columns() {
    $cols = get_column_headers( get_current_screen() );
    // die("<pre>".print_r($cols, true)."</pre>");
    return $cols;
  }

  protected function get_sortable_columns() {
    $columns = array(
      'title' => array( 'title', true ),
      'date' => array( 'date', false ),
    );

    return $columns;
  }

  public function column_cb( $item ) {
    return sprintf(
      '<input type="checkbox" name="%1$s[]" value="%2$s" />',
      $this->_args['singular'],
      $item->id()
    );
  }

  protected function get_bulk_actions() {
    $actions = array(
      'delete' => __( 'Delete', 'agoraio' ),
    );

    return $actions;
  }
  
  public function prepare_items() {
    $current_screen = get_current_screen();
    $per_page = $this->get_items_per_page( 'agoraio_per_page' );
    
    // $this->_column_headers = $this->get_column_info();

    /** Process bulk action */
    // $this->process_bulk_action();

    $args = array(
      'posts_per_page' => $per_page,
      'orderby' => 'title',
      'order' => 'ASC',
      'offset' => ( $this->get_pagenum() - 1 ) * $per_page,
    );

    if ( ! empty( $_REQUEST['s'] ) ) {
      $args['s'] = sanitize_key($_REQUEST['s']);
    }

    if ( ! empty( $_REQUEST['orderby'] ) ) {
      if ( 'title' == sanitize_key($_REQUEST['orderby']) ) {
        $args['orderby'] = 'title';
      } elseif ( 'date' == sanitize_key($_REQUEST['orderby']) ) {
        $args['orderby'] = 'date';
      }
    }

    if ( ! empty( $_REQUEST['order'] ) ) {
      if ( 'asc' == strtolower( sanitize_key($_REQUEST['order']) ) ) {
        $args['order'] = 'ASC';
      } elseif ( 'desc' == strtolower( sanitize_key($_REQUEST['order']) ) ) {
        $args['order'] = 'DESC';
      }
    }

    $this->items = WP_Agora_Channel::find( $args );

    $total_items = WP_Agora_Channel::count();
    $total_pages = ceil( $total_items / $per_page );

    $this->set_pagination_args( array(
      'total_items' => $total_items,
      'total_pages' => $total_pages,
      'per_page' => $per_page,
    ) );


    // $this->items = self::get_channels( $per_page, $current_page );
  }

  public function process_bulk_action() {

    //Detect when a bulk action is being triggered...
    if ( 'delete' === $this->current_action() ) {

      // In our file that handles the request, verify the nonce.
      $nonce = esc_attr( $_REQUEST['_wpnonce'] );

      if ( ! wp_verify_nonce( $nonce, 'sp_delete_channel' ) ) {
        die( 'Go get a life script kiddies' );
      }
      else {
        self::delete_channel( absint( sanitize_key($_GET['channel']) ) );

        wp_redirect( esc_url( add_query_arg() ) );
        exit;
      }

    }

    // If the delete bulk action is triggered
    if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
         || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
    ) {

      $delete_ids = esc_sql( $_POST['bulk-delete'] );

      // loop over the array of record IDs and delete them
      foreach ( $delete_ids as $id ) {
        self::delete_channel( $id );

      }

      wp_redirect( esc_url( add_query_arg() ) );
      exit;
    }
  }

  /** Text displayed when no channel data is available */
  public function no_items() {
    _e( 'No agora channels avaliable.', 'agoraio' );
  }


  public static function get_channels( $per_page = 5, $page_number = 1 ) {
    return WP_Agora_Channel::find(array(
      'order' => !empty($_REQUEST['order']) ? sanitize_key( $_REQUEST['order'] ) : 'ASC',
      'orderby' => !empty($_REQUEST['orderby']) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : '',
      'posts_per_page' => $per_page,
      'offset' => $page_number-1
    ));
  }

  public static function record_count() {
    global $wpdb;

    $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}channels";

    return $wpdb->get_var( $sql );
  }


  function column_title( $item ) {
    $title = '<strong>' . $item->title() . '</strong>';
    return $title;
  }

  protected function handle_row_actions( $item, $column_name, $primary ) {
    if ( $column_name !== $primary ) {
      return '';
    }

    $edit_nonce = wp_create_nonce( 'agora_edit_channel_' . $item->id() );
    $delete_nonce = wp_create_nonce( 'agora_delete_channel_' . $item->id() );

    $actions = [
      'edit' => sprintf( '<a href="?page=%s&action=%s&channel=%s&_wpnonce=%s">'.__('Edit', 'agoraio').'</a>', esc_attr( $_REQUEST['page'] ), 'edit', absint( $item->id() ), $edit_nonce ),
      'delete' => sprintf( '<a href="?page=%s&action=%s&channel=%s&_wpnonce=%s">'.__('Delete', 'agoraio').'</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item->id() ), $delete_nonce )
    ];

    return $this->row_actions( $actions );
  }

  public function column_shortcode( $item ) {
    $shortcodes = array( $item->shortcode() );

    $output = '';

    foreach ( $shortcodes as $shortcode ) {
      $output .= "\n" . '<span class="shortcode"><input type="text"'
        . ' onfocus="this.select();" readonly="readonly"'
        . ' value="' . esc_attr( $shortcode ) . '"'
        . ' class="large-text code" /></span>';
    }

    return trim( $output );
  }

  public function column_date( $item ) {
    $post = get_post( $item->id() );

    if ( ! $post ) {
      return;
    }

    $t_time = mysql2date( __( 'Y/m/d g:i:s A', 'agoraio' ),
      $post->post_date, true );
    $m_time = $post->post_date;
    $time = mysql2date( 'G', $post->post_date )
      - get_option( 'gmt_offset' ) * 3600;

    $time_diff = time() - $time;

    if ( $time_diff > 0 and $time_diff < 24*60*60 ) {
      $h_time = sprintf(
        /* translators: %s: time since the creation of the contact form */
        __( '%s ago', 'agoraio' ),
        human_time_diff( $time )
      );
    } else {
      $h_time = mysql2date( __( 'Y/m/d', 'agoraio' ), $m_time );
    }

    return sprintf( '<abbr title="%2$s">%1$s</abbr>',
      esc_html( $h_time ),
      esc_attr( $t_time )
    );
  }


  public function column_type( $item ) {
    return $item->type();
  }

  public function column_recordings( $item ){ 
    $isrecordingSettingsDone = $item->isrecordingSettingsDone();  
    $recordingOptions = array(""=>"Type", "composite" => "Composite", "individual" => "Individual");
    $output = 'Please fill recording settings details.';

    /* Show Recordings Shortcode if recording setting is done */
    if($isrecordingSettingsDone){
     $recording_type = $item->getRecordingType();
    ?>
    
    <select class="create_recordings_shortcode" onchange="updateRecordingShortcode(this.value, <?php echo $item->id(); ?>)">
      <?php foreach($recordingOptions as $value=>$option) { ?>
        <option value="<?php echo $value; ?>" <?php if($value == $recording_type){ echo "selected"; } ?>><?php echo $option; ?></option>
      <?php } ?>
    </select>

    <?php
    $shortcode =  $item->shortcode('recording');
    $output = "\n" . '<span class="shortcode recording-shortcode-row-'.$item->id().'"><input type="text"'
      . ' onfocus="this.select();" readonly="readonly"'
      . ' value="' . esc_attr( $shortcode ) . '"'
      . ' class="large-text code" /></span>';
    }

    return trim( $output );
  }

  /**
   * Render a column when no column specific method exists.
   *
   * @param array $item
   * @param string $column_name
   *
   * @return mixed
   */
  public function column_default( $item, $column_name ) {
    // return $column_name;
    return print_r($item, true);
  }
}