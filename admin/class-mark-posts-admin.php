<?php
/**
 * Mark Posts
 *
 * @package   Mark_Posts_Admin
 * @author    Michael Schoenrock <hello@michaelschoenrock.com>
 * @license   GPL-2.0+
 * @link
 * @copyright 2014 Michael Schoenrock
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * If you're interested in introducing public-facing
 * functionality, then refer to `class-plugin-name.php`
 *
 */
class Mark_Posts_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		/*
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		/*
		 * Call $plugin_slug from public plugin class.
		 *
		 */
		$plugin = Mark_Posts::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add dashboard
		add_action('wp_dashboard_setup', array( $this, 'mark_posts_dashboard_widget' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
                
                // Add quick edit custom box
                add_action( 'quick_edit_custom_box', array( $this, 'display_custom_quickedit_box' ), 10, 2 );
                // Important Quick Edit JS Parts
                add_action( 'admin_footer-edit.php', array( $this, 'admin_edit_markposts_foot' ), 11);
                add_action( 'manage_book_posts_custom_column' , array( $this, 'custom_markposts_column' ), 10, 2 );

		/*
		 * Define custom functionality.
		 *
		 * Read more about actions and filters:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */

		//add_action( '@TODO', array( $this, 'action_method_name' ) );
		//add_filter( '@TODO', array( $this, 'filter_method_name' ) );

                // Add metabox
                add_action( 'add_meta_boxes', array( $this, 'mark_posts_add_meta_box' ) );
                // Save Action for metabox
                add_action( 'save_post', array( $this, 'save' ) );

                /**
                 * Post columns
                 *
                 */

                // Display posts_custom_column on selected post types
                $get_mark_posts_setup = get_option( 'mark_posts_settings' );
                $mark_posts_posttypes = $get_mark_posts_setup['mark_posts_posttypes'];

                foreach($mark_posts_posttypes as $post_type) {
                    add_filter( 'manage_'.$post_type.'_posts_columns', array( $this, 'mark_posts_column_head' ), 10 );
                    add_action( 'manage_'.$post_type.'_posts_custom_column', array( $this, 'mark_posts_column_content' ), 10, 2 );
                }

    	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		/*
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

                wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), Mark_Posts::VERSION );

		//$screen = get_current_screen();
		//if ( $this->plugin_screen_hook_suffix == $screen->id ) {
		//}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @return    null    Return early if no settings page is registered.
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
                wp_enqueue_script( 'mark-posts-colorpicker', plugins_url('assets/js/colorpicker.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
                wp_enqueue_script( 'mark-posts-post-list-marker', plugins_url('assets/js/markposts.js', __FILE__ ), array(), false, true );

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 *   For reference: http://codex.wordpress.org/Roles_and_Capabilities
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Mark Posts', $this->plugin_slug ),
			__( 'Mark Posts', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

                //add_action('manage_options','do_on_my_plugin_settings_save');

	}

	/**
	 * Render the settings page for this plugin.
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
    wp_enqueue_style( $this->plugin_slug .'-dashboard-styles', plugins_url( 'assets/css/dashboard.css', __FILE__ ), array(), Mark_Posts::VERSION );
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
    $markers = get_terms( 'marker', $marker_args );
    echo '<style>';

    foreach ( $markers as $marker ) :
      echo '.mark-posts-' . $marker->slug . ' span:before { color: ' . $marker->description . '} ';
    endforeach;

    echo '</style>';
  }

	/**
	 * Add settings action link to the plugins page.
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
	 * NOTE:     Actions are points in the execution of a page or process
	 *           lifecycle that WordPress fires.
	 *
	 *           Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// @TODO: Define your action hook callback here
	}

	/**
	 * NOTE:     Filters are points of execution in which WordPress modifies data
	 *           before saving it or sending it to the browser.
	 *
	 *           Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *           Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// @TODO: Define your filter hook callback here
	}

        /**
        * Adds a box to the main column on the Post and Page edit screens.
        */
        public function mark_posts_add_meta_box() {

	    $get_mark_posts_setup = get_option( 'mark_posts_settings' );
	    $mark_posts_posttypes = $get_mark_posts_setup['mark_posts_posttypes'];

            if( !empty($mark_posts_posttypes) ) {
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
	 * Prints the box content.
	 *
	 * @param WP_Post $post The object for the current post/page.
	 */
	public function mark_posts_inner_meta_box( $post ) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'mark_posts_inner_meta_box', 'mark_posts_inner_meta_box_nonce' );

		// Use get_post_meta to retrieve an existing value from the database.
		$value = get_post_meta( $post->ID, '_mark_posts_term_id', true );

		// Display the form, using the current value.

                $content = '<p>' . __('Mark this post as:', 'mark-posts') . '</p>';

                // Get Marker terms from DB
                $markers_terms = get_terms( 'marker', 'hide_empty=0' );
                $content .= '<select id="mark_posts_term_id" name="mark_posts_term_id">';
                $content .= '<option value="">---</option>';
                foreach($markers_terms as $marker_term) {
                    if(ISSET($value) && $marker_term->term_id == $value) {
                        $content .= '<option value="'.$marker_term->term_id.'" data-color="'.$marker_term->description.'" selected="selected">'.$marker_term->name.'</option>';
                        $color_selected = $marker_term->description;
                    }
                    else {
                        $content .= '<option value="'.$marker_term->term_id.'" data-color="'.$marker_term->description.'">'.$marker_term->name.'</option>';
                    }
                }
                $content .= '</select>';

                if(ISSET($color_selected))
                    $content .= '<span class="mark-posts-color" style="background:'.$color_selected.'"></span>';
                else
                    $content .= '<span class="mark-posts-color"></span>';

                $content .= '<p>' . sprintf( __('Click <a href="%s">here</a> to manage Marker categories.', 'mark-posts'), esc_url('options-general.php?page=mark-posts') ) . '</p>';

                echo $content;
	}

    /**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( $post_id ) {


		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['mark_posts_inner_meta_box_nonce'] ) )
			return $post_id;

		$nonce = $_POST['mark_posts_inner_meta_box_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'mark_posts_inner_meta_box' ) )
			return $post_id;

		// If this is an autosave, our form has not been submitted,
                //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		// Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;

		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}

		/* OK, its safe for us to save the data now. */

		// Sanitize the user input.
		$mydata = sanitize_text_field( $_POST['mark_posts_term_id'] );
                $myterm = get_term( $mydata, 'marker' );

		// Update the meta field.
		update_post_meta( $post_id, '_mark_posts_term_id', $mydata );
		// Update taxonomy count
		wp_set_object_terms( $post_id, $myterm->name, 'marker' );
                
                /* Save data for Quick Edit Custom Box */
                
                        /* in production code, $slug should be set only once in the plugin,
                        preferably as a class property, rather than in each function that needs it.
                      */
                     /*$slug = 'book';
                     if ( $slug !== $_POST['post_type'] ) {
                         return;
                     }
                     if ( !current_user_can( 'edit_post', $post_id ) ) {
                         return;
                     }
                     $_POST += array("{$slug}_edit_nonce" => '');
                     if ( !wp_verify_nonce( $_POST["{$slug}_edit_nonce"],
                                            plugin_basename( __FILE__ ) ) )
                     {
                         return;
                     }
                 
                     if ( isset( $_REQUEST['book_author'] ) ) {
                         update_post_meta( $post_id, 'author', $_REQUEST['book_author'] );
                     }
                     # checkboxes are submitted if checked, absent if not
                     if ( isset( $_REQUEST['inprint'] ) ) {
                         update_post_meta($post_id, 'inprint', TRUE);
                     } else {
                         update_post_meta($post_id, 'inprint', FALSE);
                     }*/
                
                /* ----- */
                
                

	}


        // create admin columns
        public function mark_posts_column_head($defaults) {

            $defaults['Marker Category'] = __('Marker', 'mark-posts');

            return $defaults;
        }

        // show the column content
        public function mark_posts_column_content($column_name, $post_ID) {

            $value = get_post_meta( $post_ID, '_mark_posts_term_id', true );
            if(ISSET($value)) {
                $term = get_term( $value, 'marker' );
                if($term) {
                    if( ISSET ($term->description) && ISSET ($term->name) ) {
                        //echo '<span class="mark-posts-post-color" data-color="'.$term->description.'" style="display:inline-block;height:13px;width:6px;margin-right:5px;background-color:'.$term->description.'"></span>';
                        //echo $term->name;
                        //echo $term->count;
                        echo '<span class="mark-posts-marker" style="background:'.$term->description.'" data-background="'.$term->description.'">'.$term->name.'</span>';
                    }
                }
            }
            else {
                // no marker set
            }

        }
        
        // Quick Edit Custom Box
        public function display_custom_quickedit_box( $column_name, $post_type ) {
            static $printNonce = TRUE;
            if ( $printNonce ) {
                $printNonce = FALSE;
                wp_nonce_field( plugin_basename( __FILE__ ), 'mark_posts_inner_meta_box_nonce' );
            }
        
            ?>
            <fieldset class="inline-edit-col-right inline-edit-book">
              <div class="inline-edit-col column-marker_category">
                <label class="inline-edit-group">
                <span class="title">Mark Posts</span>
                </label>
                <?php
                // Get Marker terms from DB
                $markers_terms = get_terms( 'marker', 'hide_empty=0' );
                $content = '<select class="mark_posts_term_id" name="mark_posts_term_id">';
                $content .= '<option value="">---</option>';
                foreach($markers_terms as $marker_term) {
                    if(ISSET($value) && $marker_term->term_id == $value) {
                        $content .= '<option value="'.$marker_term->term_id.'" data-color="'.$marker_term->description.'" selected="selected">'.$marker_term->name.'</option>';
                        $color_selected = $marker_term->description;
                    }
                    else {
                        $content .= '<option value="'.$marker_term->term_id.'" data-color="'.$marker_term->description.'">'.$marker_term->name.'</option>';
                    }
                }
                $content .= '</select>';
                // print $content
                echo $content;
                ?>
              </div>
            </fieldset>
            <?php
        }        
        
        // Important Quick Edit JS Parts
        /* load scripts in the footer */
        public function admin_edit_markposts_foot() {
            echo '<script type="text/javascript" src="', plugins_url( 'assets/js/admin-edit.js', __FILE__ ), '"></script>';
        }
        
        /* example of how an existing value can be stored in the table */
        public function custom_markposts_column( $column, $post_id ) {
                // the !! means translate the following item to a boolean value
                if ( !!get_post_meta( $post_id , 'inprint' , true ) ) {
                    $checked = 'checked';
                } else {
                    $checked = '';
                }
                echo "<input type='checkbox' readonly $checked/>";
                break;
        }


}