<?php
/**
 * Mark Posts
 *
 * @package   Mark_Posts
 * @author    Michael Schoenrock <hello@michaelschoenrock.com>
 * @license   GPL-2.0+
 * @link      
 * @copyright 2014 Michael Schoenrock
 *
 * @wordpress-plugin
 * Plugin Name:       Mark Posts
 * Plugin URI:        
 * Description:       Mark and highlight posts, pages and posts of custom post types within the posts overview 
 * Version:           1.0.0
 * Author:            Michael Schoenrock
 * Author URI:        http://www.michaelschoenrock.com
 * Text Domain:       mark-posts-de
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/<owner>/<repo>
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
 * plugin's class file
 *
 */
require_once( plugin_dir_path( __FILE__ ) . 'public/class-mark-posts.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 *
 */
register_activation_hook( __FILE__, array( 'Mark_Posts', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Mark_Posts', 'deactivate' ) );

/*
 * @TODOdone:
 *
 *
 */
add_action( 'plugins_loaded', array( 'Mark_Posts', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

/*
 *
 * If you want to include Ajax within the dashboard, change the following
 * conditional to:
 *
 * if ( is_admin() ) {
 *   ...
 * }
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-mark-posts-admin.php' );
	add_action( 'plugins_loaded', array( 'Mark_Posts_Admin', 'get_instance' ) );

}
