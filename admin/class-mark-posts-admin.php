<?php
/**
 * Mark Posts Class
 *
 * @package   Mark_Posts
 * @author    Michael Schoenrock <hello@michaelschoenrock.com>, Sven Hofmann <info@hofmannsven.com>
 * @license   GPL-2.0+
 * @copyright 2014 Michael Schoenrock
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


class Mark_Posts_Admin {

	/**
	 * Instance of this class
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/**
		 * Call $plugin_slug from public plugin class
		 */
		$plugin            = Mark_Posts::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();
		$get_mark_posts_setup = get_option( 'mark_posts_settings' );

		// Load admin style sheet and JavaScript
		add_action( 'admin_enqueue_scripts', array( $this, 'mark_posts_enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'mark_posts_enqueue_admin_scripts' ) );

		// Add the options page and menu item
		add_action( 'admin_menu', array( $this, 'mark_posts_add_plugin_admin_menu' ) );

		/**
		 * Add dashboard
		 *
		 * @since    1.0.8
		 */
		if(ISSET($get_mark_posts_setup['mark_posts_dashboard'])):
			$mark_posts_dashboard = $get_mark_posts_setup['mark_posts_dashboard'];
			if ( !empty($mark_posts_dashboard) ) :
				add_action( 'wp_dashboard_setup', array( $this, 'mark_posts_dashboard_widget' ) );
			endif;
		endif;

		// Add an action link pointing to the options page
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'mark_posts_add_action_links' ) );

		// Add quick edit and bulk edit actions
		add_action( 'bulk_edit_custom_box', array( $this, 'mark_posts_display_quickedit_box' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'mark_posts_display_quickedit_box' ), 10, 2 );
		// Add JavaScript for quick edit and bulk edit actions
		add_action( 'admin_print_scripts-edit.php', array( $this, 'mark_posts_edit_scripts' ), 10, 2 );

		// Add metabox
		add_action( 'add_meta_boxes', array( $this, 'mark_posts_add_meta_box' ) );
		// Save action for metabox
		add_action( 'save_post', array( $this, 'mark_posts_save' ) );
		// Save action for quick edit
		add_action( 'save_post', array( $this, 'mark_posts_save_quick_edit' ), 10, 2 );
		// Save action for attachment metabox
		add_action( 'edit_attachment', array( $this, 'mark_posts_save' ), 10, 1 );
		// Save action for bulk edit
		add_action( 'wp_ajax_mark_posts_save_bulk_edit', array( $this, 'mark_posts_save_bulk_edit' ) );
		// Trash action
		add_action( 'trash_post', array( $this, 'mark_posts_trash' ), 1, 1 );
		// Delete action
		add_action( 'delete_post', array( $this, 'mark_posts_delete' ), 10 );

		/**
		 * Custom admin post columns (custom post types only)
		 *
		 * @since    1.0.0
		 */
		$mark_posts_posttypes = $get_mark_posts_setup['mark_posts_posttypes'];
		foreach ( $mark_posts_posttypes as $post_type ) {
			add_filter( 'manage_' . $post_type . '_posts_columns', array( $this, 'mark_posts_column_head' ), 10, 2 );
			add_action( 'manage_' . $post_type . '_posts_custom_column', array( $this, 'mark_posts_column_content' ), 10, 2 );
		}

		/**
		 * Custom admin post columns (attachments only)
		 *
		 * @since    1.1.2
		 */
		if ( in_array('attachment',$mark_posts_posttypes) ) {
			add_filter('manage_media_columns', array( $this, 'mark_posts_column_head' ), 1);
			add_action('manage_media_custom_column', array( $this, 'mark_posts_column_content' ), 1, 2);
		}

	}

	/**
	 * Return an instance of this class
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered
	 */
	public function mark_posts_enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), WP_MARK_POSTS_VERSION );

	}

	/**
	 * Register and enqueue admin-specific JavaScript
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered
	 */
	public function mark_posts_enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		global $pagenow;
		if ( $pagenow == 'options-general.php' || $pagenow == 'edit.php' || $pagenow == 'post.php' || $pagenow == 'upload.php' ) :
			wp_enqueue_style( 'wp-color-picker' ); // see http://make.wordpress.org/core/2012/11/30/new-color-picker-in-wp-3-5/
			wp_enqueue_script( $this->plugin_slug . '-post-list-marker', plugins_url( 'assets/js/markposts.js', __FILE__ ), array( 'wp-color-picker' ), WP_MARK_POSTS_VERSION, true );
		endif;

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu
	 *
	 * @since    1.0.0
	 */
	public function mark_posts_add_plugin_admin_menu() {

		// add a settings page for this plugin to the Settings menu
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Mark Posts', $this->plugin_slug ),
			__( 'Mark Posts', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'mark_posts_display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin
	 *
	 * @since    1.0.0
	 */
	public function mark_posts_display_plugin_admin_page() {
		include_once( 'views/admin.php' );
	}

	/**
	 * Register custom dashboard widget
	 *
	 * @since    1.0.0
	 */
	public function mark_posts_dashboard_widget() {
		global $wp_meta_boxes;
		$this->plugin_screen_hook_suffix = wp_add_dashboard_widget(
			'mark_posts_info_widget',
			'Mark Posts',
			array( $this, 'mark_posts_dashboard_info' )
		);
		add_action( 'admin_enqueue_scripts', array( $this, 'mark_posts_enqueue_dashboard_styles' ) );
		add_action( 'admin_head', array( $this, 'mark_posts_custom_dashboard_styles' ) );
	}

	/**
	 * Render the dashboard widget
	 *
	 * @since    1.0.0
	 */
	public function mark_posts_dashboard_info() {
		include_once( 'views/dashboard.php' );
	}

	/**
	 * Load additional dashboard styles
	 *
	 * @since    1.0.0
	 */
	public function mark_posts_enqueue_dashboard_styles() {
		wp_enqueue_style( $this->plugin_slug . '-dashboard-styles', plugins_url( 'assets/css/dashboard.css', __FILE__ ), array(), WP_MARK_POSTS_VERSION );
	}

	/**
	 * Build custom dashboard styles
	 *
	 * @since    1.0.0
	 */
	public function mark_posts_custom_dashboard_styles() {
		$marker_args = array(
			'hide_empty' => true
		);
		$markers     = get_terms( 'marker', $marker_args );
		echo '<style>';

		foreach ( $markers as $marker ) :
			echo '.mark-posts-' . $marker->slug . ' a:before { color: ' . $marker->description . '} ';
		endforeach;

		echo '</style>';
	}

	/**
	 * Add settings action link to the plugins page
	 *
	 * @since    1.0.0
	 *
	 * @param $links
	 *
	 * @return array associative array of plugin action links
	 */
	public function mark_posts_add_action_links( $links ) {
		// add a 'Settings' link to the front of the actions list for this plugin
		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	/**
	 * Adds a box to the main column on the edit screens
	 *
	 * @since    1.0.0
	 */
	public function mark_posts_add_meta_box() {

		$get_mark_posts_setup = get_option( 'mark_posts_settings' );
		$mark_posts_posttypes = $get_mark_posts_setup['mark_posts_posttypes'];

		if ( ! empty( $mark_posts_posttypes ) ) {
			foreach ( $mark_posts_posttypes as $mark_posts_posttype ) {
				add_meta_box(
					'mark_posts_options',
					__( 'Mark Posts Options', 'mark-posts' ),
					array( $this, 'mark_posts_inner_meta_box' ),
					$mark_posts_posttype,
					'side'
				);
			}
		}
	}

	/**
	 * Prints the box content
	 *
	 * @since    1.0.0
	 *
	 * @param $post Information about the post e.g. 'ID'
	 */
	public function mark_posts_inner_meta_box( $post ) {

		// Add an nonce field so we can check for it later
		wp_nonce_field( 'mark_posts_inner_meta_box', 'mark_posts_inner_meta_box_nonce' );

		echo '<p>' . __( 'Mark this post as:', 'mark-posts' ) . '</p>';

		// Get available markers as select dropdown
		$markers = new Mark_Posts_Marker();
		echo $markers->mark_posts_select( $post->ID );

		echo '<span class="mark-posts-color"></span>';

		echo '<p>' . sprintf( __( 'Click <a href="%s">here</a> to manage Marker categories.', 'mark-posts' ), esc_url( 'options-general.php?page=mark-posts' ) ) . '</p>';

	}

	/**
	 * Save the meta when the post is saved
	 *
	 * @since    1.0.0
	 *
	 * @param $post_id ID of the post e.g. '1'
	 *
	 * @return mixed
	 */
	public function mark_posts_save( $post_id ) {
		// Check if our nonce is set.
		if ( ! isset( $_POST['mark_posts_inner_meta_box_nonce'] ) ) {
			return $post_id;
		}

		$nonce = $_POST['mark_posts_inner_meta_box_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'mark_posts_inner_meta_box' ) ) {
			return $post_id;
		}

		// If this is an autosave, our form has not been submitted,
		// so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}

		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		/* OK, its safe for us to mark_posts_save the data now. */

		// Sanitize the user input.
		$mydata = sanitize_text_field( $_POST['mark_posts_term_id'] );
		$myterm = get_term( $mydata, 'marker' );

		// Update the meta field.
		update_post_meta( $post_id, 'mark_posts_term_id', $mydata );

		// Update taxonomy count
		@wp_update_term_count_now( $mydata, 'marker' );

		// Clear transient dashboard stats
		delete_transient( 'marker_posts_stats' );

	}

	/**
	 * Update taxonomy count if posts get permanently deleted
	 *
	 * @since    1.0.7
	 *
	 * @param $post_id ID of the post e.g. '1'
	 */
	public function mark_posts_delete ( $post_id ) {
		// Retrieve post meta value from the database
		if ( isset( $post_id ) ) :
			$term = get_post_meta( $post_id, 'mark_posts_term_id', true );
			if ( ! empty( $term ) ) :
				wp_set_object_terms( $post_id, $term, 'marker' );
			else :
				wp_set_object_terms( $post_id, NULL, 'marker' ); // clear/remove all marker from post with $post_id
			endif;
			// Clear transient dashboard stats
			delete_transient( 'marker_posts_stats' );
		endif;
	}

	/**
	 * Update dashboard stats if posts get trashed
	 *
	 * @since    1.0.7
	 *
	 * @param $post_id ID of the post e.g. '1'
	 */
	public function mark_posts_trash ( $post_id ) {
		// Clear transient dashboard stats
		delete_transient( 'marker_posts_stats' );
	}

	/**
	 * Custom quick edit box
	 *
	 * @since    1.0.0
	 *
	 * @param $column_name Custom column name e.g. 'mark_posts_term_id'
	 */
	public function mark_posts_display_quickedit_box( $column_name ) {

		switch ( $column_name ) {
			case 'mark_posts_term_id':
				?>
				<fieldset class="inline-edit-col-right mark-posts-quickedit">
					<div class="inline-edit-col">
						<div class="inline-edit-group">
							<label class="inline-edit-status alignleft">
								<span class="title"><?php _e( 'Marker', 'mark-posts' ); ?></span>
								<?php

								// Get available markers as select dropdown
								$markers = new Mark_Posts_Marker();
								echo $markers->mark_posts_select();

								?>
							</label>
						</div>
					</div>
				</fieldset>
				<?php
				break;
		}
	}

	/**
	 * Save quick edit
	 *
	 * @since    1.0.0
	 *
	 * @param $post_id ID of the post e.g. '1'
	 * @param $post    Information about the post e.g. 'post_type'
	 *
	 * @return mixed
	 */
	public function mark_posts_save_quick_edit( $post_id, $post ) {
		// pointless if $_POST is empty (this happens on bulk edit)
		if ( empty( $_POST ) ) {
			return $post_id;
		}

		// verify quick edit nonce
		if ( isset( $_POST['_inline_edit'] ) && ! wp_verify_nonce( $_POST['_inline_edit'], 'inlineeditnonce' ) ) {
			return $post_id;
		}

		// don't mark_posts_save for autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// dont mark_posts_save for revisions
		if ( isset( $post->post_type ) && $post->post_type == 'revision' ) {
			return $post_id;
		}

		$mark_posts_fields = array( 'mark_posts_term_id' );

		foreach ( $mark_posts_fields as $mark_field ) :
			if ( array_key_exists( $mark_field, $_POST ) ) :
				// update post meta
				update_post_meta( $post_id, $mark_field, $_POST[$mark_field] );

				// update terms
				$term = get_term( $_POST[$mark_field], 'marker' );
				if ( ! empty( $term->name ) ) :
					wp_set_object_terms( $post_id, $term->name, 'marker' );
				else :
					wp_set_object_terms( $post_id, NULL, 'marker' ); // clear/remove all marker from post with $post_id
				endif;

				// Clear transient dashboard stats
				delete_transient( 'marker_posts_stats' );
			endif;
		endforeach;
	}

	/**
	 * Save bulk edit
	 *
	 * @since    1.0.0
	 */
	public function mark_posts_save_bulk_edit() {

		// we need the post IDs
		$post_ids = ( isset( $_POST['post_ids'] ) && ! empty( $_POST['post_ids'] ) ) ? $_POST['post_ids'] : NULL;

		// if we have post IDs
		if ( ! empty( $post_ids ) && is_array( $post_ids ) ) {

			$mark_posts_fields = array( 'mark_posts_term_id' );

			foreach ( $mark_posts_fields as $mark_field ) :

				// if it has a value, doesn't update if empty on bulk
				if ( isset( $_POST[$mark_field] ) && ! empty( $_POST[$mark_field] ) ) {

					// update for each post ID
					foreach ( $post_ids as $post_id ) {
						// update post meta
						update_post_meta( $post_id, $mark_field, $_POST[$mark_field] );

						// update terms
						$term = get_term( $_POST[$mark_field], 'marker' );
						wp_set_object_terms( $post_id, $term->name, 'marker' );

						// Clear transient dashboard stats
						delete_transient( 'marker_posts_stats' );
					}

				}

			endforeach;

		}

	}

	/**
	 * Enqueue quick edit and bulk edit script in admin footer
	 *
	 * @since    1.0.0
	 */
	public function mark_posts_edit_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-quick-bulk-edit', plugins_url( 'assets/js/admin-edit.js', __FILE__ ), array( 'jquery', 'inline-edit-post' ), WP_MARK_POSTS_VERSION, true );
	}

	/**
	 * Set admin column
	 *
	 * @since    1.0.0
	 *
	 * @param $columns Set custom admin column ID and name
	 *
	 * @return mixed
	 */
	public function mark_posts_column_head( $columns ) {
		$columns['mark_posts_term_id'] = __( 'Marker', 'mark-posts' );

		return $columns;
	}

	/**
	 * Show column content
	 *
	 * @since    1.0.0
	 *
	 * @param $column_name Custom column name e.g. 'mark_posts_term_id'
	 * @param $post_id     ID of the post e.g. '1'
	 */
	public function mark_posts_column_content( $column_name, $post_id ) {

		switch ( $column_name ) {

			case 'mark_posts_term_id':
				$value = get_post_meta( $post_id, 'mark_posts_term_id', true );
				if ( ISSET( $value ) ) {
					$term = get_term( $value, 'marker' );
					if ( $term ) {
						if ( ISSET ( $term->description ) && ISSET ( $term->name ) ) {
							echo '<div id="mark_posts_term_id-' . $post_id . '" class="mark-posts-marker" style="background:' . $term->description . '" data-val="' . $term->term_id . '" data-background="' . $term->description . '">' . $term->name . '</div>';
						}
					}
				}
				break;

		}
	}

}