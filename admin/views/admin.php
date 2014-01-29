<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Mark_Posts
 * @author    Michael Schoenrock <hello@michaelschoenrock.com>
 * @contributor Sven Hofmann <info@hofmannsven.com>
 * @license   GPL-2.0+
 * @copyright 2014 Michael Schoenrock
 */
?>

<?php

// create options
$default_marker_post_types = array( 'posts', 'pages' );
add_option( 'default_mark_posts_posttypes', $default_marker_post_types );

// save form data
function validate_form() {
	
	if ( $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit']) ) {

		// update marker posttypes
		if(ISSET( $_POST['markertypes']) )
			$markertypes = $_POST['markertypes'];
		else
			$markertypes = array();
			
		$get_mark_posts_settings = get_option( 'mark_posts_settings' );
		$set_mark_posts_settings = $markertypes;
		$get_mark_posts_settings['mark_posts_posttypes'] = $set_mark_posts_settings;
	
		update_option( 'mark_posts_settings', $get_mark_posts_settings );

		// update marker terms
	    $markers = explode(",", $_POST['markers']);
	    foreach($markers as $marker) {
			$marker = trim($marker);
			wp_insert_term( $marker, 'marker' );
	    }

	    // update markers
	    $i=0;
	    if($_POST['markernames']) {
			foreach($_POST['markernames'] as $markername) {
		    	wp_update_term($_POST['term_ids'][$i], 'marker', array(
					'name' => $markername,
					'slug' => sanitize_title($markername),
					'description' => $_POST['colors'][$i]
				));
				$i++;
			}
	    }
	    
	    echo display_settings_updated();
	    
	}
}

function display_settings_updated() {
	
	return '<div id="message" class="updated">
		<p>'._x('Settings saved.', 'mark-posts').'</p>
		</div>';

}

// get all available post types
function get_all_types() {
	$all_post_types = get_post_types();

	$option = get_option( 'mark_posts_settings' );
	$mark_posts_settings = isset( $option['mark_posts_posttypes'] ) ? $option['mark_posts_posttypes'] : 'post';

    foreach( $all_post_types as $one_post_type ) {
		// do not show attachments, revisions, or nav menu items
		if( !in_array( $one_post_type, array( 'attachment', 'revision', 'nav_menu_item' ) ) ) {
			echo '<p><input name="markertypes[]" type="checkbox" value="' . $one_post_type . '"';
            	if ( isset( $option['mark_posts_posttypes'] ) ) :
            		if ( in_array( $one_post_type, $option['mark_posts_posttypes'] ) ) :
            			echo ' checked="checked"';
            		endif;
            	endif;
			echo ' /> ' . ucfirst( $one_post_type ) . '</p>';
		}
	}
}

function show_settings() {
	
	// set default colors
	$default_colors = array('#81d742', '#eeee22', '#dd3333', '#1e73be', '#dd9933', '#8224e3');

	?>
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
	<?php

	// Get Marker terms from DB
	$markers_terms = get_terms( 'marker', 'hide_empty=0' );
	$markers_registered = '';
	foreach($markers_terms as $marker_term) {
		$markers_registered .= $marker_term->name;
		$markers_registered .= ', ';
	}
	$markers_registered = rtrim($markers_registered, ", "); // cut trailing comma and space

	if(!empty($markers_terms)) {

		echo '<h3 class="title">' . __('Marker Categories', 'mark-posts') . '</h3>';

		echo '<table class="form-table"><tbody>';
		
		$i=0;
		foreach($markers_terms as $marker_term) {
			
			if($marker_term->description != '')
				$color = $default_colors[$i];
			else
				$color = $default_colors[$i];
				
			echo '<tr valign="top"><th scope="row"><input type="text" name="markernames[]" value="'.$marker_term->name.'"></th>';
			echo '<td width="130"><input type="text" name="colors[]" value="'.$color.'" class="my-color-field" data-default-color="'.$color.'"/></td>';
			echo '<td>[<a href="#">' . __('delete', 'mark-posts') . '</a>]</td>';
			echo '<input type="hidden" name="term_ids[]" value="'.$marker_term->term_id.'"/>';
			$i++;
		}

		echo '</tbody></table>';

	}

	submit_button();

	?>

		<hr />
		<h3 class="title"><?php _e('Add new Marker Categories', 'mark-posts'); ?></h3>
		<p>
			<?php _e('Add new marker types - for example (please separate them by comma):', 'mark-posts'); ?><br />
			<strong><em><?php _e('Ready to go, Not quite finished, Not finished yet', 'mark-posts'); ?></em></strong>
		</p>

		<textarea name="markers" style="width:60%;height:120px;"></textarea>

		<hr />
		<h3 class="title"><?php _e('Enable/Disable Marker', 'mark-posts'); ?></h3>
		<p>
			<?php _e('Enable/Disable Markers for specific post types...', 'mark-posts'); ?>
		</p>

		<?php

			get_all_types();
			submit_button();

		?>

	</form>

<?php } ?>

<div class="wrap">

	<?php screen_icon(); ?>
	
	<?php validate_form(); ?>

	<h2><?php _e('Mark Posts Options', 'mark-posts'); ?></h2>

	<?php show_settings(); ?>

</div>
