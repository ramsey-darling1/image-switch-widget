<?php
/**
 * Plugin Name: Image Switch Widget
 * Plugin URI: none
 * Description: A simple image widget that displays a new image on each page load
 * Version: 2.0
 * Author: Ramsey Darling
 * Author URI: http://vcwebdesign.com
 * License: free
 */

 
/**
 *this widget will display a new image each time the page is refreshed.
 *kinda old school, but some clients are kinda old school
 *the best things in life are free, do whatever you want with it
 **/

 
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
		//first thing we need to do is grab every image from a certain folder.
		
		$imgs = 'wp-content/plugins/image_switch_widget/image_switch/*.*';
		
		//print_r(glob($imgs));
		
		foreach(glob($imgs) as $image){
			//here we make sure that the file in the directory is an image
			if(preg_match('/jpg/',$image) or preg_match('/png/',$image) or preg_match('/jpeg/',$image) or preg_match('/JPG/',$image) or preg_match('/PNG/',$image)){
				//add the images to an array
				$images[] = $image;
			}
		}
	
		if(isset($images)){
			if(!empty($images)){
				$random_key = array_rand($images);//pick an image from the array at random
				//display the image
				echo '<img src="/'.$images[$random_key].'" alt="Random Image" />';
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

      

