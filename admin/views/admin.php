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
function validate_form() {
		
	if ( $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
            
	    //print_r($_POST);
	    
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
	
		echo '<h3 class="title">Marker Categories</h3>';
	
		echo '<table class="form-table"><tbody>';
		
		foreach($markers_terms as $marker_term) {
			echo '<tr valign="top"><th scope="row"><input type="text" name="markernames[]" value="'.$marker_term->name.'"></th>';
			echo '<td width="130"><input type="text" name="colors[]" value="'.$marker_term->description.'" class="my-color-field" data-default-color="#effeff" /></td>';
			echo '<td>[<a href="#">delete</a>]</td>';
			echo '<input type="hidden" name="term_ids[]" value="'.$marker_term->term_id.'"/>';
			
		}
		
		echo '</tbody></table>';
	
	}
	
	submit_button();

	?>
		<hr />
		<h3 class="title">Add new Marker Categories</h3>
		<p>Add new marker types - for example (please separate them by comma):<br /><strong><em>Ready to go, Not quite finished, Not finished yet</em></strong></p>
			<textarea name="markers" style="width:60%;height:120px;"></textarea>
			<?php submit_button(); ?>
		</form>
	
<?php } ?>

<div class="wrap">

	<?php screen_icon(); ?>
	
	<?php validate_form(); ?>
	
	<h2><?php echo esc_html( get_admin_page_title() ); ?> Options</h2>

	<!-- Provide markup for your options page here. -->
	
	<?php show_settings(); ?>
	
</div>
