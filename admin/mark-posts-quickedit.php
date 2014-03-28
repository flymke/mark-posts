<?php
/**
 * Mark Posts Admin
 *
 * @package   Mark_Posts_Admin
 * @author    Michael Schoenrock <hello@michaelschoenrock.com>, Sven Hofmann <info@hofmannsven.com>
 * @license   GPL-2.0+
 * @copyright 2014 Michael Schoenrock
 */

/**
 *  Custom admin post columns (custom post types only)
 *
 * @since    1.0.0
 */

$get_mark_posts_setup = get_option( 'mark_posts_settings' );
$mark_posts_posttypes = $get_mark_posts_setup['mark_posts_posttypes'];

foreach($mark_posts_posttypes as $post_type) {
    add_filter( 'manage_'.$post_type.'_posts_columns', 'mark_posts_column_head', 10, 2 );
    add_action( 'manage_'.$post_type.'_posts_custom_column', 'mark_posts_column_content', 10, 2 );
}

/**
 * Create admin column
 *
 * @since    1.0.0
 */
function mark_posts_column_head( $columns ) {
    $columns['mark_posts_term_id'] = __('Marker', 'mark-posts');
    return $columns;
}

/**
 * Show column content
 *
 * @since    1.0.0
 */
function mark_posts_column_content( $column_name, $post_id ) {

    switch( $column_name ) {

        case 'mark_posts_term_id':
            $value = get_post_meta( $post_id, 'mark_posts_term_id', true );
            if( ISSET($value) ) {
                $term = get_term( $value, 'marker' );
                if( $term ) {
                    if( ISSET ($term->description) && ISSET ($term->name) ) {
                    echo '<div id="mark_posts_term_id-' . $post_id . '" class="mark-posts-marker" style="background:'.$term->description.'" data-val="'.$term->term_id.'" data-background="'.$term->description.'">'.$term->name.'</div>';
                }
            }
        }
        break;

    }
}

/**
 * Save quick edit
 *
 * @since    1.0.0
 */
add_action( 'save_post', 'save_mark_posts_quick_edit', 10, 2 );
function save_mark_posts_quick_edit( $post_id, $post ) {

    // pointless if $_POST is empty (this happens on bulk edit)
    if ( empty( $_POST ) )
        return $post_id;

    // verify quick edit nonce
    if ( isset( $_POST[ '_inline_edit' ] ) && ! wp_verify_nonce( $_POST[ '_inline_edit' ], 'inlineeditnonce' ) )
        return $post_id;

    // don't save for autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return $post_id;

    // dont save for revisions
    if ( isset( $post->post_type ) && $post->post_type == 'revision' )
        return $post_id;

    $mark_posts_fields = array( 'mark_posts_term_id' );

    foreach( $mark_posts_fields as $mark_field ) :
        if ( array_key_exists( $mark_field, $_POST ) ) :
            // update post meta
            update_post_meta( $post_id, $mark_field, $_POST[ $mark_field ] );

            // update terms
            $term = get_term( $_POST[ $mark_field ], 'marker' );
            if ( !empty($term->name) )
                wp_set_object_terms( $post_id, $term->name, 'marker' );
        endif;
    endforeach;
}

/**
 * Save bulk edit
 *
 * @since    1.0.0
 */
add_action( 'wp_ajax_save_mark_posts_bulk_edit', 'save_mark_posts_bulk_edit' );
function save_mark_posts_bulk_edit() {

    // we need the post IDs
    $post_ids = ( isset( $_POST[ 'post_ids' ] ) && !empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : NULL;

    // if we have post IDs
    if ( ! empty( $post_ids ) && is_array( $post_ids ) ) {

        $mark_posts_fields = array( 'mark_posts_term_id' );

        foreach( $mark_posts_fields as $mark_field ) :

            // if it has a value, doesn't update if empty on bulk
            if ( isset( $_POST[ $mark_field ] ) && !empty( $_POST[ $mark_field ] ) ) {

                // update for each post ID
                foreach( $post_ids as $post_id ) {
                    // update post meta
                    update_post_meta( $post_id, $mark_field, $_POST[ $mark_field ] );

                    // update terms
                    $term = get_term( $_POST[ $mark_field ], 'marker' );
                    wp_set_object_terms( $post_id, $term->name, 'marker' );
                }

            }

        endforeach;

    }

}

?>