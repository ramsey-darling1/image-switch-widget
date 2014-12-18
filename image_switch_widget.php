<?php
/**
 * Plugin Name: Image Switch Widget
 * Plugin URI: none
 * Description: A simple image widget that displays a new image on each page load
 * Version: 2.1
 * Author: Ramsey Darling
 * Author URI: http://vcwebdesign.com
 * License: free
 */

 
/**
 *this widget will display a new image each time the page is refreshed.
 *kinda old school, but whatever
 *the best things in life are free, do whatever you want with it
 **/

session_start();


add_action( 'widgets_init', 'register_image_switch_widget' );



// register the widget  
function register_image_switch_widget() {
	register_widget( 'image_switch_widget' );
}                        
      
class image_switch_widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'image_switch_widget', // Base ID
			'Image Switch Widget', // Name
			array( 'description' => __( 'A simple Image Widget that displays a new image on each page load', 'text_domain' ), ) // Args
		);
	}
	
	public function widget($args, $instance) {
		//display the widget
		//first thing we need to do is grab every image that we can from the database.
		global $wpdb;
		
		$table_name = $wpdb->prefix.'image_switch';
		$dig = "SELECT * FROM {$table_name}";
		$current_images = $wpdb->get_results($dig,ARRAY_A);


		foreach($current_images as $image){
			//add the images to an array
			$images[] = $image['url'];
		}
	
		if(isset($images)){
			if(!empty($images)){
				$random_key = array_rand($images);//pick an image from the array at random
				//display the image
				echo '<img src="'.$images[$random_key].'" alt="Random Image" />';
			}
		}
		
		
	}
	
	public function update($new_instance, $old_instance) {
		//update the widget
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';



		return $instance;

		
	}
	
	public function form($instance) {
		//widget options
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}else{
			$title = __( 'New title', 'text_domain' );
		}
		echo '<p>';
		echo '<label for="'.$this->get_field_id( 'title' ).'">'._e( 'Title:' ).' </label>'; 
		echo '<input class="widefat" id="'.$this->get_field_id( 'title' ).'" name="'.$this->get_field_name( 'title' ).'" type="text" value="'.esc_attr( $title ).' " />';
		echo '</p>';
		
		
	}

}

/**
 *Admin Options Interface
 *
 *instead of doing everything in the widget, we are going to create an admin interface
 */

//add wp admin interface
add_action('admin_menu','image_switch_admin');


//add styles
wp_enqueue_style('image_switch-styles', plugins_url('image_switch_css/image_switch.css',__FILE__));

function image_switch_admin(){
    add_menu_page( 'Image Switch', 'Image Switch', 'manage_options', 'imageswitchadmin', 'admin_display' );
}

function admin_display(){

	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	global $wpdb;

	if(!$_SESSION['image_switch_instance_id']){
		$_SESSION['image_switch_instance_id'] = uniqid(date('mdy'));
	}

	echo '<h1>Image Switch</h1>';
	echo '<p>Upload a New Image:</p><p><em>Images are not sized, please make sure the image you are uploading is the size you want it to be before uploading</em></p>';
	echo '<form method="post" enctype="multipart/form-data"  action="">';
	echo '<input type="file" name="image_switch_images" id="image_switch_images" multiple />';
	echo '<input type="hidden" name="image_switch_action" value="'.$_SESSION['image_switch_instance_id'].'" />';
	echo '<input type="submit" value="Upload" class="image_switch_button" />';
	echo '</form>';

	//upload image
	if(isset($_POST['image_switch_action'])){
		if($_POST['image_switch_action'] == $_SESSION['image_switch_instance_id']){
			//we have made sure that a logged in user is the one submiting the form
			//now upload the image
			if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
			$uploadedfile = $_FILES['image_switch_images'];
			$upload_overrides = array( 'test_form' => false );
			$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
			if ( $movefile ) {
				//the image was successfully uploaded. 
				//save the file path in the database
				$table_name = $wpdb->prefix.'image_switch';
				$image_data = array(
					'time' => time(),
					'name' => 'Image Switch Image',
					'isw_id' => $_SESSION['image_switch_instance_id'],
					'url' => $movefile['url']
					);
				$wpdb->insert($table_name,$image_data);

			    echo '<div class="image_switch_success">File is valid, and was successfully uploaded.</div>';
			    //var_dump( $movefile);
			} else {
			    echo '<div class="image_switch_error">Sorry, we were not able to upload your images at this time.</div>';
			}

			
		}
	}

	echo '<div id="image_switch_admin_image_area">';
	echo '<h2>Current Images in Use</h2>';
	echo '<p>Random image displayed by the widget will be selected from these images:</p>';

	$table_name = $wpdb->prefix.'image_switch';
	$dig = "SELECT * FROM {$table_name}";
	$current_images = $wpdb->get_results($dig,ARRAY_A);
	foreach ($current_images as $image) {
		echo '<div class="image_switch_image_div"><img src="'.$image['url'].'" alt="Image Switch Image" class="image_switch_left" />';
		echo '<div class="image_switch_hidden"><form method="post" action="">';
		echo '<input type="hidden" name="remove_image_id" value="'.$image['id'].'" />';
		echo '<input type="submit" value="Remove" class="image_switch_button" />';
		echo '</form></div></div>';
	}

	echo '<p class="image_switch_brick"></p></div>';

	//delete images
	if(isset($_POST['remove_image_id'])){
		$wpdb->delete($table_name,array('id' => $_POST['remove_image_id']));
	}
	
}

/**
 *Database Table Creation on Install
 *
 */

register_activation_hook( __FILE__, 'image_switch_install' );

function image_switch_install(){
	
	global $wpdb;

	$table_name = $wpdb->prefix.'image_switch';
	$sql = "CREATE TABLE $table_name (
	  id mediumint(9) NOT NULL AUTO_INCREMENT,
	  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	  name tinytext NOT NULL,
	  isw_id VARCHAR(20) NOT NULL,
	  url VARCHAR(555) DEFAULT '' NOT NULL,
	  UNIQUE KEY id (id)
	);";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	//version specific options and update functionality
	$current_version = "2.1";

	add_option("image_switch_db_version", $current_version);

	$installed_version = get_option( "image_switch_db_version" );

	if($installed_version != $current_version){
		//update the database
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  		dbDelta( $sql );

  		update_option( "image_switch_db_version", $current_version );
		

	}

}


