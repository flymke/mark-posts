<?php
/**
 * Renders the view for the dashboard widget.
 *
 * @package      Mark_Posts
 * @author       Michael Schoenrock <hello@michaelschoenrock.com>, Sven Hofmann <info@hofmannsven.com>
 * @license      GPL-2.0+
 * @copyright    2014 Michael Schoenrock
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Get marker terms
 *
 * @since     1.0.0
 */
$marker_args = array(
	'hide_empty' => true
);
$markers = get_terms( 'marker', $marker_args );

/**
 * Get all markers but trashed
 *
 * @since     1.0.7
 */
global $wpdb;
$marker_items = '';
if ( ! empty( $markers ) ) :
	foreach ( $markers as $marker ) :
		$trashed = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts p JOIN $wpdb->term_relationships rl ON p.ID = rl.object_id WHERE rl.term_taxonomy_id = $marker->term_id AND p.post_status = 'trash' LIMIT 1" );
		$count   = $marker->count - $trashed;
		if ( $count > 0 ) :
			$marker_items .= '<li class="mark-posts-info mark-posts-' . $marker->slug . '"><span>' . $count . ' ' . $marker->name . '</span></li>';
		endif;
	endforeach;
endif;

?>

<div class="main">
	<ul id="markers_right_now">
		<?php
		if ( ! empty( $marker_items ) ) :
			echo $marker_items;
		else :
			_e( 'No marked posts yet.', 'mark-posts' );
		endif;
		?>
	</ul><!-- /#markers_right_now -->
</div><!-- /.main -->
