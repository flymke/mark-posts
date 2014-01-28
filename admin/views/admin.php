<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Mark_Posts
 * @author    Michael Schoenrock <hello@michaelschoenrock.com>
 * @license   GPL-2.0+
 * @link
 * @copyright 2014 Michael Schoenrock
 */
?>

<?php

// create options
$default_marker_post_types = array( 'posts', 'pages' );
add_option( 'default_mark_posts_posttypes', $default_marker_post_types );

// save form data
function validate_form() {

	if ( $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {

	    //print_r($_POST);

	    $markers = explode(",", $_POST['markers']);
		$markertypes = $_POST['markertypes'];

		if($_POST['markertypes']) {
			foreach($_POST['markertypes'] as $markertype) {
				$update_marker_post_types = array_push($default_marker_post_types, $markertype);
			}
			update_option( 'default_mark_posts_posttypes', $update_marker_post_types );
		}

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
	}
}

// get all available post types
function get_all_types() {
	$all_post_types = get_post_types();
    foreach( $all_post_types as $one_post_type ) {
		// do not show attachments, revisions, or nav menu items
		if( !in_array( $one_post_type, array( 'attachment', 'revision', 'nav_menu_item' ) ) ) {
			echo '<p><input name="markertypes[]" type="checkbox" value="' . $one_post_type . '"';
            	/*
            	if( in_array( $mark_posts_post_type, $mark_posts_settings['post_types'] ) ) {
					echo ' checked="checked"';
				}
				*/
			echo ' /> ' . $one_post_type . '</p>';
		}
	}
}

function show_settings() {

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

		foreach($markers_terms as $marker_term) {
			echo '<tr valign="top"><th scope="row"><input type="text" name="markernames[]" value="'.$marker_term->name.'"></th>';
			echo '<td width="130"><input type="text" name="colors[]" value="'.$marker_term->description.'" class="my-color-field" data-default-color="#effeff" /></td>';
			echo '<td>[<a href="#">' . __('delete', 'mark-posts') . '</a>]</td>';
			echo '<input type="hidden" name="term_ids[]" value="'.$marker_term->term_id.'"/>';

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
