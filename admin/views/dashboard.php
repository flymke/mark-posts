<?php
/**
 * Renders the view for the dashboard widget.
 *
 * @package      Mark_Posts
 * @author       Michael Schoenrock <hello@michaelschoenrock.com>, Sven Hofmann <info@hofmannsven.com>
 * @license      GPL-2.0+
 * @copyright    2014 Michael Schoenrock
 */

/**
 * Get marker
 *
 * @since     1.0.0
 */
$marker_args = array(
	'hide_empty' => true
);
$markers = get_terms( 'marker', $marker_args );

?>

<div class="main">
	<ul id="markers_right_now">
		<?php
		if ( ! empty( $markers ) ) :
			foreach ( $markers as $marker ) :
				echo '<li class="mark-posts-info mark-posts-' . $marker->slug . '"><span>' . $marker->count . ' ' . $marker->name . '</span></li>';
			endforeach;
		else:
			_e( 'No marked posts yet.', 'mark-posts' );
		endif;
		?>
	</ul>
	<!-- /#markers_right_now -->
</div><!-- /.main -->