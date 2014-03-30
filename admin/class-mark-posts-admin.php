<?php

/**
 * Mark Posts Class
 *
 * @package   Mark_Posts_Admin
 * @author    Michael Schoenrock <hello@michaelschoenrock.com>, Sven Hofmann <info@hofmannsven.com>
 * @license   GPL-2.0+
 * @copyright 2014 Michael Schoenrock
 */
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

		// Load admin style sheet and JavaScript
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add dashboard
		add_action( 'wp_dashboard_setup', array( $this, 'mark_posts_dashboard_widget' ) );

		// Add an action link pointing to the options page
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		// Add quick edit and bulk edit actions
		add_action( 'bulk_edit_custom_box', array( $this, 'display_mark_posts_quickedit_box' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'display_mark_posts_quickedit_box' ), 10, 2 );
		// Add JavaScript for quick edit and bulk edit actions
		add_action( 'admin_print_scripts-edit.php', array( $this, 'mark_posts_edit_scripts' ), 10, 2 );

		// Add metabox
		add_action( 'add_meta_boxes', array( $this, 'mark_posts_add_meta_box' ) );
		// Save action for metabox
		add_action( 'save_post', array( $this, 'save' ) );
		// Save action for quick edit
		add_action( 'save_post', array( $this, 'save_mark_posts_quick_edit' ), 10, 2 );
		// Save action for bulk edit
		add_action( 'wp_ajax_save_mark_posts_bulk_edit', array( $this, 'save_mark_posts_bulk_edit' ) );

		/**
		 *  Custom admin post columns (custom post types only)
		 *
		 * @since    1.0.0
		 */

		$get_mark_posts_setup = get_option( 'mark_posts_settings' );
		$mark_posts_posttypes = $get_mark_posts_setup['mark_posts_posttypes'];

		foreach ( $mark_posts_posttypes as $post_type ) {
			add_filter( 'manage_' . $post_type . '_posts_columns', array( $this, 'mark_posts_column_head' ), 10, 2 );
			add_action( 'manage_' . $post_type . '_posts_custom_column', array( $this, 'mark_posts_column_content' ), 10, 2 );
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
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Mark_Posts::VERSION );

	}

	/**
	 * Register and enqueue admin-specific JavaScript
	 *
	 * @return    null    Return early if no settings page is registered
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), Mark_Posts::VERSION );
		}

		// see http://make.wordpress.org/core/2012/11/30/new-color-picker-in-wp-3-5/
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( $this->plugin_slug . '-colorpicker', plugins_url( 'assets/js/colorpicker.js', __FILE__ ), array( 'wp-color-picker' ), Mark_Posts::VERSION, true );
		wp_enqueue_script( $this->plugin_slug . '-post-list-marker', plugins_url( 'assets/js/markposts.js', __FILE__ ), array(), Mark_Posts::VERSION, true );

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE: Alternative menu locations are available via WordPress administration menu functions.
		 *
		 * Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 * For reference: http://codex.wordpress.org/Roles_and_Capabilities
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Mark Posts', $this->plugin_slug ),
			__( 'Mark Posts', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
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
		wp_enqueue_style( $this->plugin_slug . '-dashboard-styles', plugins_url( 'assets/css/dashboard.css', __FILE__ ), array(), Mark_Posts::VERSION );
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
			echo '.mark-posts-' . $marker->slug . ' span:before { color: ' . $marker->description . '} ';
		endforeach;

		echo '</style>';
	}

	/**
	 * Add settings action link to the plugins page
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

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
	 */
	public function mark_posts_inner_meta_box( $post ) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'mark_posts_inner_meta_box', 'mark_posts_inner_meta_box_nonce' );

		// Use get_post_meta to retrieve an existing value from the database.
		$value = get_post_meta( $post->ID, 'mark_posts_term_id', true );

		// Display the form, using the current value.

		$content = '<p>' . __( 'Mark this post as:', 'mark-posts' ) . '</p>';

		// Get Marker terms from DB
		$markers_terms = get_terms( 'marker', 'hide_empty=0' );
		$content .= '<select id="mark_posts_term_id" name="mark_posts_term_id">';
		$content .= '<option value="">---</option>';
		foreach ( $markers_terms as $marker_term ) {
			if ( ISSET( $value ) && $marker_term->term_id == $value ) {
				$content .= '<option value="' . $marker_term->term_id . '" data-color="' . $marker_term->description . '" selected="selected">' . $marker_term->name . '</option>';
				$color_selected = $marker_term->description;
			} else {
				$content .= '<option value="' . $marker_term->term_id . '" data-color="' . $marker_term->description . '">' . $marker_term->name . '</option>';
			}
		}
		$content .= '</select>';

		if ( ISSET( $color_selected ) ) {
			$content .= '<span class="mark-posts-color" style="background:' . $color_selected . '"></span>';
		} else {
			$content .= '<span class="mark-posts-color"></span>';
		}

		$content .= '<p>' . sprintf( __( 'Click <a href="%s">here</a> to manage Marker categories.', 'mark-posts' ), esc_url( 'options-general.php?page=mark-posts' ) ) . '</p>';

		echo $content;
	}

	/**
	 * Save the meta when the post is saved
	 *
	 * @since    1.0.0
	 */
	public function save( $post_id ) {
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

		/* OK, its safe for us to save the data now. */

		// Sanitize the user input.
		$mydata = sanitize_text_field( $_POST['mark_posts_term_id'] );
		$myterm = get_term( $mydata, 'marker' );

		// Update the meta field.
		update_post_meta( $post_id, 'mark_posts_term_id', $mydata );

		// Update taxonomy count
		if ( ! empty( $myterm->name ) ) {
			wp_set_object_terms( $post_id, $myterm->name, 'marker' );
		}

	}

	/**
	 * Custom quick edit box
	 *
	 * @since    1.0.0
	 */
	public function display_mark_posts_quickedit_box() {
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<div class="inline-edit-group">
					<label class="inline-edit-status alignleft">
						<span class="title"><?php _e( 'Marker', 'mark-posts' ); ?></span>
						<?php
						$markers_terms = get_terms( 'marker', 'hide_empty=0' );
						$content = '<select name="mark_posts_term_id">';
						$content .= '<option value="">---</option>';
						foreach ( $markers_terms as $marker_term ) {
							$content .= '<option value="' . $marker_term->term_id . '" data-color="' . $marker_term->description . '">' . $marker_term->name . '</option>';
						}
						$content .= '</select>';
						echo $content;
						?>
					</label>
				</div>
			</div>
		</fieldset>
	<?php
	}


	/**
	 * Save quick edit
	 *
	 * @since    1.0.0
	 */
	public function save_mark_posts_quick_edit( $post_id, $post ) {
		// pointless if $_POST is empty (this happens on bulk edit)
		if ( empty( $_POST ) ) {
			return $post_id;
		}

		// verify quick edit nonce
		if ( isset( $_POST['_inline_edit'] ) && ! wp_verify_nonce( $_POST['_inline_edit'], 'inlineeditnonce' ) ) {
			return $post_id;
		}

		// don't save for autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		// dont save for revisions
		if ( isset( $post->post_type ) && $post->post_type == 'revision' )
			return $post_id;

		$mark_posts_fields = array( 'mark_posts_term_id' );

		foreach ( $mark_posts_fields as $mark_field ) :
			if ( array_key_exists( $mark_field, $_POST ) ) :
				// update post meta
				update_post_meta( $post_id, $mark_field, $_POST[$mark_field] );

				// update terms
				$term = get_term( $_POST[$mark_field], 'marker' );
				if ( ! empty( $term->name ) )
					wp_set_object_terms( $post_id, $term->name, 'marker' );
			endif;
		endforeach;
	}

	/**
	 * Save bulk edit
	 *
	 * @since    1.0.0
	 */
	public function save_mark_posts_bulk_edit() {

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
		wp_enqueue_script( $this->plugin_slug . '-quick-bulk-edit', plugins_url( 'assets/js/admin-edit.js', __FILE__ ), array( 'jquery', 'inline-edit-post' ), Mark_Posts::VERSION, true );
	}

	/**
	 * Create admin column
	 *
	 * @since    1.0.0
	 */
	public function mark_posts_column_head( $columns ) {
		$columns['mark_posts_term_id'] = __( 'Marker', 'mark-posts' );

		return $columns;
	}

	/**
	 * Show column content
	 *
	 * @since    1.0.0
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