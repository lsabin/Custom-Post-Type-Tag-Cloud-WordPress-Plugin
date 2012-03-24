<?php /*

Plugin Name: Custom Post Type Tag Cloud
Plugin URI: http://www.jenwachter.com/custom-post-type-tag-cloud
Description: Modifies the native WordPress Tag Cloud widget to be able to choose taxonomies from all post types to render in a tag cloud format.
Version: 1.0
Author: Jen Wachter
Author URL: http://www.jenwachter.com
License:

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

class Custom_Post_Type_Tag_Cloud_Widget extends WP_Widget {
	
	// widget setup
	function __construct() {
		$widget_ops = array ( 'description' => __( 'Display your most used tags by post type from any post type in cloud format.' ) );
		parent::__construct( 'tag_cloud_custom_posts', 'Custom Post Type Tag Cloud', $widget_ops );
	}
	
	
	// function from WP Tag Cloud widget
	function _get_current_taxonomy( $instance ) {
		if ( !empty($instance['taxonomy']) && taxonomy_exists($instance['taxonomy'] ) )
			return $instance['taxonomy'];
		return 'post_tag';
	}
	
	// function from WP Tag Cloud widget (untouched)
	function widget( $args, $instance ) {
		extract( $args );
		$current_taxonomy = $this->_get_current_taxonomy( $instance );
		if ( !empty( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			if ( 'post_tag' == $current_taxonomy ) {
				$title = __('Tags');
			} else {
				$tax = get_taxonomy( $current_taxonomy );
				$title = $tax->labels->name;
			}
		}
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo $before_widget;
		if ( $title )
			echo $before_title . $title . $after_title;
		echo '<div class="tagcloud">';
		wp_tag_cloud( apply_filters( 'widget_tag_cloud_args', array('taxonomy' => $current_taxonomy ) ) );
		echo "</div>\n";
		echo $after_widget;
	}
	
	
	function update( $new_instance, $old_instance ) {
		
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['post_type'] = $new_instance['post_type'];
		$instance['taxonomy'] = $new_instance['taxonomy'];
		return $instance;
	}
	
	
	function form( $instance ) {
		
		// find current values for the form
		$current_post_type = $instance['post_type'];
		$current_taxonomy = $this->_get_current_taxonomy( $instance );
		
		// find post types with taxonomies
		$post_types_with_tax = array();
		$all_post_types = get_post_types( array ( 'public' => true ) );
		
		foreach ( $all_post_types as $k => $v ) {
			if ( $taxonomy = get_object_taxonomies( $k ) ) {
				$post_types_with_tax[] = $k;
			}
		}
		
		// display the form ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ) ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php if ( isset( $instance['title'] ) ) echo esc_attr( $instance['title'] ) ?>" />
		</p>
		
		<p>
			<label for="post_type"><?php _e( 'Post Type:' ) ?></label>
			<select autocomplete="off" class="widefat tag-cloud-custom-posts-post-type" id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>">
			
			<?php foreach ( $post_types_with_tax as $v ) : ?>
				<option value="<?php echo $v; ?>" <?php selected( $v, $current_post_type ); ?>><?php echo $v; ?></option>
			<?php endforeach; ?>
			</select>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Taxonomy:' ) ?></label>
			<div class="taxonomies">
				<select autocomplete="off" class="widefat" id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>">
				<?php foreach ( get_object_taxonomies( $current_post_type ) as $taxonomy ) :
					if ( $taxonomy != 'post_format' ) : ?>
						<option value="<?php echo esc_attr($taxonomy) ?>" <?php selected($taxonomy, $current_taxonomy) ?>><?php echo get_taxonomy($taxonomy)->labels->name ?></option>
					<? endif; ?>
				<?php endforeach; ?>
				</select>
			</div>
		</p>
	<?php
	}
	
	function register() {
		register_widget("Custom_Post_Type_Tag_Cloud_Widget");
	}
	
}
add_action( "widgets_init", array( 'Custom_Post_Type_Tag_Cloud_Widget', 'register' ) );


// gets the taxonomies of the chosen post type
function cpt_tag_cloud_get_taxonomies_callback() { ?>
	
	<?php foreach ( get_object_taxonomies( $_POST['post_type'] ) as $taxonomy ) :
		$tax = get_taxonomy( $taxonomy ); ?>
		<option value="<?php echo esc_attr( $taxonomy ) ?>"><?php echo $tax->labels->name; ?></option>
	<?php
	endforeach;
	die();
}
add_action( 'wp_ajax_cpt_tag_cloud_get_taxonomies', 'cpt_tag_cloud_get_taxonomies_callback' );


// when a custom post type is chosen in the form, load taxonomies of chosen post type
function cpt_tag_cloud_get_taxonomies_javascript() { ?>
	
	<script type="text/javascript" >
	jQuery(document).ready(function($) {
		
		function cpt_tag_cloud_get_taxonomies(value, parent) {
			var data = {
				action: 'cpt_tag_cloud_get_taxonomies',
				post_type: value
			};
			jQuery.post(ajaxurl, data, function(response) {
				jQuery('#' + parent + ' .taxonomies select').html(response);
			});
		}
		
		jQuery(".tag-cloud-custom-posts-post-type").live('change', function() {
						
			// find the right select list to populate
			var parent = jQuery(this).parents('div.widget');
			parent = jQuery(parent).attr('id');
					
			var value = jQuery(this).val();
			cpt_tag_cloud_get_taxonomies(value, parent);
		});
	});
	</script>
	
<?php }
add_action('admin_head', 'cpt_tag_cloud_get_taxonomies_javascript');
?>