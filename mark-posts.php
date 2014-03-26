<?php
/**
 * Mark Posts
 *
 * @package   Mark_Posts
 * @author    Michael Schoenrock <hello@michaelschoenrock.com>, Sven Hofmann <info@hofmannsven.com>
 * @license   GPL-2.0+
 * @link      http://flymke.github.io/mark-posts/
 * @copyright 2014 Michael Schoenrock
 *
 * @wordpress-plugin
 * Plugin Name:       Mark Posts
 * Plugin URI:        http://flymke.github.io/mark-posts/
 * Description:       Mark and highlight posts, pages and posts of custom post types within the posts overview
 * Version:           1.0.0
 * Author:            <a href="http://www.aliquit.de" target="_blank">Michael Schoenrock</a>, <a href="http://www.hofmannsven.com" target="_blank">Sven Hofmann</a>
 * Author URI:        http://www.aliquit.de
 * Contributor:       Sven Hofmann
 * Contributor URI:   http://hofmannsven.com
 * Text Domain:       mark-posts
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/flymke/mark-posts
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

/*
 *
 * plugin varion
 *
 */
if (!defined('WP_MARK_POSTS_VERSION')) define('WP_MARK_POSTS_VERSION', '1.0.0');

/*
 *
 * plugin dir path
 *
 */
if (!defined('WP_MARK_POSTS_PATH')) define('WP_MARK_POSTS_PATH', plugin_dir_path( __FILE__ ));

/*
 *
 * plugin's class file
 *
 */
require_once( plugin_dir_path( __FILE__ ) . 'public/class-mark-posts.php' );



add_action( 'save_post', 'manage_wp_posts_using_bulk_quick_edit_save_post', 10, 2 );
function manage_wp_posts_using_bulk_quick_edit_save_post( $post_id, $post ) {

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

    if ( array_key_exists( '_mark_posts_term_id', $_POST ) ) :
        // update post meta
        update_post_meta( $post_id, '_mark_posts_term_id', $_POST[ '_mark_posts_term_id' ] );

        // update terms
        $term = get_term( $_POST[ '_mark_posts_term_id' ], 'marker' );
        wp_set_object_terms( $post_id, $term->name, 'marker' );
    endif;
}


add_action( 'wp_ajax_manage_wp_posts_using_bulk_quick_save_bulk_edit', 'manage_wp_posts_using_bulk_quick_save_bulk_edit' );
function manage_wp_posts_using_bulk_quick_save_bulk_edit() {

    // we need the post IDs
    $post_ids = ( isset( $_POST[ 'post_ids' ] ) && !empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : NULL;

    // if we have post IDs
    if ( ! empty( $post_ids ) && is_array( $post_ids ) ) {

        // if it has a value, doesn't update if empty on bulk
        if ( isset( $_POST[ '_mark_posts_term_id' ] ) && !empty( $_POST[ '_mark_posts_term_id' ] ) ) {

            // update for each post ID
            foreach( $post_ids as $post_id ) {
                // update post meta
                update_post_meta( $post_id, '_mark_posts_term_id', $_POST[ '_mark_posts_term_id' ] );

                // update terms
                $term = get_term( $_POST[ '_mark_posts_term_id' ], 'marker' );
                wp_set_object_terms( $post_id, $term->name, 'marker' );
            }

        }

    }

}


/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 *
 */
register_activation_hook( __FILE__, array( 'Mark_Posts', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Mark_Posts', 'deactivate' ) );

/*
 * Add action plugins_loaded 
 *
 *
 */
add_action( 'plugins_loaded', array( 'Mark_Posts', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-mark-posts-admin.php' );
	add_action( 'plugins_loaded', array( 'Mark_Posts_Admin', 'get_instance' ) );

}
