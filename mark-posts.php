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
 * Description:       Simply mark and highlight posts, pages and posts of custom post types within the posts overview.
 * Version:           1.1.1
 * Author:            <a href="http://www.aliquit.de" target="_blank">Michael Schoenrock</a>, <a href="http://hofmannsven.com" target="_blank">Sven Hofmann</a>
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
 * plugin version
 *
 */
if ( ! defined( 'WP_MARK_POSTS_VERSION' ) ) {
	define( 'WP_MARK_POSTS_VERSION', '1.1.1' );
}

/*
 * plugin dir path
 *
 */
if ( ! defined( 'WP_MARK_POSTS_PATH' ) ) {
	define( 'WP_MARK_POSTS_PATH', plugin_dir_path( __FILE__ ) );
}

/*
 * plugin's class file
 *
 */
require_once( plugin_dir_path( __FILE__ ) . 'public/class-mark-posts.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 */
register_activation_hook( __FILE__, array( 'Mark_Posts', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Mark_Posts', 'deactivate' ) );

/*
 * Load the plugin text domain for translation
 *
 */
function mark_posts_load_textdomain() {
	load_plugin_textdomain( 'mark-posts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'mark_posts_load_textdomain', 1 );

/*
 * Add action plugins_loaded
 *
 */
add_action( 'plugins_loaded', array( 'Mark_Posts', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-mark-posts-marker.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-mark-posts-admin.php' );
	add_action( 'plugins_loaded', array( 'Mark_Posts_Admin', 'get_instance' ) );

}