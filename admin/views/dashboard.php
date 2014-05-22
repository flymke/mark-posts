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
 * Get available markers
 *
 * @since     1.0.0
 */
$marker_args = array(
	'hide_empty' => true
);
$markers     = get_terms( 'marker', $marker_args );

/**
 * Build marker stats for each post type
 *
 * @since     1.0.7
 */
if ( ! empty( $markers ) ) :

	$get_mark_posts_setup = get_option( 'mark_posts_settings' );
	$mark_posts_posttypes = $get_mark_posts_setup['mark_posts_posttypes'];
	$marker_stats         = '';

	foreach ( $mark_posts_posttypes as $mark_posts_posttype ) :

		$marked_posts = '';

		foreach ( $markers as $marker ) :
			$post_args   = array(
				'post_type'      => $mark_posts_posttype,
				'taxonomy'       => $marker->taxonomy,
				'term'           => $marker->slug,
				'post_status'    => 'publish',
				'posts_per_page' => - 1,
			);
			$posts       = get_posts( $post_args );
			$posts_count = count( $posts );

			if ( ! empty( $posts_count ) ) :
				$marked_posts .= '<li class="mark-posts-info mark-posts-' . $marker->slug . '">';
				$marked_posts .= '<a a href="edit.php?post_type=' . $mark_posts_posttype . '&marker=' . $marker->slug . '">' . $posts_count . ' ' . $marker->name . '</a>';
				$marked_posts .= '</li>';
			endif;

		endforeach; // end of marker loop

		$marker_post_type_object = get_post_type_object( $mark_posts_posttype );

		if ( ! empty( $marked_posts ) ) :
			$marker_stats .= '<h3 class="mark_posts_headline">' . $marker_post_type_object->labels->name . '</h3>';
			$marker_stats .= '<ul class="markers_right_now">';
			$marker_stats .= $marked_posts;
			$marker_stats .= '</ul>';
		endif;

	endforeach; // end of post type loop

	if ( ! empty( $marker_stats ) ) :
		echo $marker_stats;
	else :
		_e( 'No marked posts yet.', 'mark-posts' );
	endif;

else :
	_e( 'No marked posts yet.', 'mark-posts' );
endif;