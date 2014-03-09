<?php
/**
 * File: sosere-admin.php
 * Class: Sosere_Admin
 * Description: plugin admin page
 * @author: Arthur Kaiser <social-semantic-recommendation@sosere.com>
 */
 /*
 * avoid to call it directly
 */
 if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
} //end: if(!function_exists('add_action'))

 if ( ! class_exists('Sosere_Admin') ) {
	class Sosere_Admin
	{

		/**
		 * Holds the values to be used in the fields callbacks
		 */
		private $options;

		/**
		 * php constructor
		 */
		public function __construct()
		{
			$this->options = get_option( 'plugin_sosere' );
			
			add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
			add_action( 'admin_init', array( $this, 'page_init' ) );
			add_action( 'admin_head', array( $this, 'admin_include_register') );
			
			// register plugin activation 
			register_activation_hook( __FILE__, array( $this, 'sosere_setup_on_activation' ) );
			add_action( 'activated_plugin', array( $this, 'sosere_activated' ) );
			add_action( 'admin_notices', array( $this, 'sosere_msg_on_reactivation') );
			// hook update procedure
			add_filter( 'upgrader_pre_install', array( $this, 'sosere_secure_custom_css_on_update' ) );
			add_filter( 'upgrader_post_install', array( $this, 'sosere_restore_custom_css_on_update' ) );
			add_filter( 'upgrader_post_install', array( $this, 'sosere_msg_on_update' ) );
			
			
			// extended description
			add_filter( 'plugin_row_meta', array( $this, 'sosere_extend_description' ), 10, 2 );
					
		}
		
		//function admin_include lang
		/*
		* add custom css & js for settings page
		*/
		function admin_include_register()
		{	
			// style
			wp_register_style( 'sosere-recommendation-admin-style',  SOSERE_PLUGIN_DIR.'sosere_css/sosere-recommendation-admin.css' );
			wp_enqueue_style( 'sosere-recommendation-admin-style' );
			
			// Include in admin cusom header for media lib
			// required wp 3.5
			if( version_compare( WP_VERSION, '3.5', '>=' ) ) {
				// register admin js
				wp_register_script( 'sosere-recommendation-admin-js',  SOSERE_PLUGIN_DIR.'sosere_js/sosere-recommendation-admin.js' );
				wp_enqueue_script( 'sosere-recommendation-admin-js' );
				// media lib
				wp_enqueue_media();
				wp_enqueue_script( 'custom-header' );	
			}
		}

		/**
		 * Add options page
		 */
		public function add_plugin_page()
		{
			// This page will be under "Settings"
			add_options_page(
				__( 'Settings Admin', 'sosere-rec' ), 
				'SOSERE', 
				'manage_options', 
				'sosere-settings', 
				array( $this, 'create_admin_page' )
			);
		}

		/**
		 * Options page callback
		 */
		public function create_admin_page()
		{
			
			?>
			<div class="wrap">
				<h2>SOSERE</h2> 
				<form method="post" action="options.php">
				<?php
					// This prints out all hidden setting fields
					settings_fields( 'sosere_option_group' );   
					do_settings_sections( 'sosere-settings' );
					submit_button(); 
				?>
				</form>
			</div>
			<?php
		}

		/**
		 * Register and add settings
		 */
		public function page_init()
		{
			$this->options = get_option( 'plugin_sosere' );
			if ( false == $this->options ) {
				// preset options
				$sosere_default_options = array(
					"use_cache" 				=> "on",
					"max_cache_time" 			=> "24",
					"recommedation_box_title"   => __( "Recommended for you", 'sosere-rec' ),
					"result_count" 				=> "3",
					"max_post_age" 				=> "0",
					"max_view_history" 			=> "30"
				);
				$this->options = $sosere_default_options;
				update_option ( 'plugin_sosere', $this->options );
			}
			
			register_setting(
				'sosere_option_group', // Option group
				'plugin_sosere' // Option name
			);

			add_settings_section(
				'sosere-setting-base', // ID
				__( 'Basic Settings', 'sosere-rec' ), // Title
				array( $this, 'print_section_basic_info' ), // Callback
				'sosere-settings' // Page
			);  

			add_settings_field(
				'sosere_use_cache', // ID
				__( 'Use SOSERE output cache', 'sosere-rec' ), // Title 
				array( $this, 'sosere_use_cache_callback' ), // Callback
				'sosere-settings', // Page
				'sosere-setting-base' // Section           
			);

			add_settings_field(
				'sosere_max_cache_time', // ID
				__( 'Max Cache Time (hours)', 'sosere-rec' ), // Title 
				array( $this, 'sosere_max_cache_time_callback' ), // Callback
				'sosere-settings', // Page
				'sosere-setting-base' // Section           
			);

			add_settings_field(
				'recommedation_box_title', 
				__( 'Recommendations box title',  'sosere-rec' ),
				array( $this, 'sosere_title_callback' ), 
				'sosere-settings', 
				'sosere-setting-base' 
			); 
			
			add_settings_field(
				'resultcount', 
				__( 'Count of recommended posts per page/post', 'sosere-rec' ),
				array( $this, 'sosere_resultcount_callback' ), 
				'sosere-settings', 
				'sosere-setting-base' 
			); 
			
			add_settings_section(
				'sosere-setting-view', // ID
				__( 'View Settings', 'sosere-rec' ), // Title
				array( $this, 'print_section_view_info' ), // Callback
				'sosere-settings' // Page
			);
			
			add_settings_field(
				'sosere_show_thumbs', // ID
				__( 'Show recommendations with thumbs', 'sosere-rec' ), // Title 
				array( $this, 'sosere_show_thumbs_callback' ), // Callback
				'sosere-settings', // Page
				'sosere-setting-view' // Section           
			);
			
			// sosere_default_thumb_callback(
			add_settings_field(
				'defaul_thumb', 
				__( 'Default thumbnail', 'sosere-rec' ), 
				array( $this, 'sosere_default_thumb_callback' ), 
				'sosere-settings', 
				'sosere-setting-view' 
			); 
			
			add_settings_field(
				'sosere_use_custom_css', // ID
				__( 'Use custom css', 'sosere-rec' ), // Title 
				array( $this, 'sosere_use_custom_css_callback' ), // Callback
				'sosere-settings', // Page
				'sosere-setting-view' // Section           
			);
			
			add_settings_field(
				'sosere_hide_output', // ID
				__( 'Hide recommendations', 'sosere-rec' ), // Title 
				array( $this, 'sosere_hide_output_callback' ), // Callback
				'sosere-settings', // Page
				'sosere-setting-view' // Section           
			);
			
			add_settings_field(
				'sosere_include_pages', // ID
				__( 'Include pages and custom types', 'sosere-rec' ), // Title 
				array( $this, 'sosere_include_pages_callback' ), // Callback
				'sosere-settings', // Page
				'sosere-setting-view' // Section           
			);
				
			
			add_settings_section(
				'sosere-setting-selection', // ID
				__( 'Selection Settings', 'sosere-rec' ), // Title
				array( $this, 'print_section_selection_info' ), // Callback
				'sosere-settings' // Page
			); 
			
			add_settings_field(
				'sosere_maxpostage', 
				__( 'Consider posts/pages not older than (days)', 'sosere-rec' ), 
				array( $this, 'sosere_maxpostage_callback' ), 
				'sosere-settings', 
				'sosere-setting-selection' 
			); 
			//sosere_maxviewhistory_callback
			add_settings_field(
				'sosere_maxviewhistory', 
				__( 'Consider posts/pages viewed for (days)', 'sosere-rec' ), 
				array( $this, 'sosere_maxviewhistory_callback' ), 
				'sosere-settings', 
				'sosere-setting-selection' 
			);
		}

		/** 
		 * Print the Section text
		 */
		public function print_section_basic_info()
		{
			_e( 'SOSERE Basic settings:', 'sosere-rec' );
		}
		
		/** 
		 * Print the View text
		 */
		public function print_section_view_info()
		{
			_e( 'Select view settings:', 'sosere-rec' );
		}
		/** 
		 * Print the Section text
		 */
		public function print_section_selection_info()
		{
			_e( 'Select recommendation settings:', 'sosere-rec' );
		} 

		/** 
		 * Get the settings option array and print one of its values
		 */
		public function sosere_use_cache_callback()
		{
			if( isset( $this->options['use_cache'] ) && 'on' == $this->options['use_cache'] ) $checkbox_use_cache = 'checked="checked"'; else $checkbox_use_cache = '';
			printf( 
				'<input type="checkbox" id="use_cache" name="plugin_sosere[use_cache]" %s />',
				$checkbox_use_cache
			);
			print( '<span class="admininfo">' . __( "Caching increases your blog performance by storing output in database for a period of time, while SSOSERE doesn't have to generate it each time. It has no effect on other caching plugins but leave it unchecked if you are using another caching plugin.", 'sosere-rec' ) . '</span>' );
		}
		
		public function sosere_max_cache_time_callback()
		{
			printf(
				'<input type="text" id="max_cache_time" name="plugin_sosere[max_cache_time]" value="%s" size="3"/>',
				isset( $this->options['max_cache_time'] ) ? esc_attr( $this->options['max_cache_time'] ) : ''
			);
			print( '<span class="admininfo">' . __( 'Define here how long SOSERE output should be cached before it would be regenerated.', 'sosere-rec' ) . '</span>' );
		}
		
		public function sosere_show_thumbs_callback()
		{
			$img_baseurl = SOSERE_PLUGIN_DIR.'sosere_img/';
			$optionsstring = '';
			$option_number = 0;

			$optionsstring .= '<option ';
			if ( isset( $this->options['show_thumbs'] ) && 0 == $this->options['show_thumbs'] ) $optionsstring .= ' selected '; 
			$optionsstring .= 'value="0"> ' . __( 'Title list', 'sosere-rec' ) . ' </option>';
			$optionsstring .= '<option ';
			if ( isset( $this->options['show_thumbs'] ) && 1 == $this->options['show_thumbs'] ) $optionsstring .= ' selected '; 
			$optionsstring .='value="1"> ' . __( 'Thumbnail and title', 'sosere-rec' ) . ' </option>';
			$optionsstring .= '<option ';
			if ( isset( $this->options['show_thumbs'] ) && 2 == $this->options['show_thumbs'] ) $optionsstring .= ' selected '; 
			$optionsstring .='value="2"> ' . __( 'Thumbnail only', 'sosere-rec' ) . ' </option>';
			printf(
				'<select name="plugin_sosere[show_thumbs]" onchange=\'document.getElementById("admin-viewstyle-img").src="'
				. $img_baseurl
				. 'admin-viewstyle" + this.options[this.selectedIndex].value + ".png";\'>' . $optionsstring . '</select>'	
			);
			
			if ( isset( $this->options['show_thumbs'] ) ) $option_number = $this->options['show_thumbs'];
			printf( '<p class="show_thumbs"><img id="admin-viewstyle-img" src="%s" /> </p>',
				 SOSERE_PLUGIN_DIR.'sosere_img/admin-viewstyle'.$option_number.'.png'
			);
			
		}
		
		public function sosere_default_thumb_callback()
		{	
			if( version_compare( WP_VERSION, '3.5', '>=' ) ) {
				printf( '<img id="default_thumbnail_img" src="%s" /><input type="hidden" id="default_thumbnail_img_url" name="plugin_sosere[default_thumbnail_img_url]" value="%s" />',
					isset( $this->options['default_thumbnail_img_url'] ) ? esc_attr( $this->options['default_thumbnail_img_url'] ) : '',
					isset( $this->options['default_thumbnail_img_url'] ) ? esc_attr( $this->options['default_thumbnail_img_url'] ) : ''
				);
				printf( '<input type="hidden" id="default_thumbnail_img_id" name="plugin_sosere[default_thumbnail_img_id]" value="%s" />',
					isset( $this->options['default_thumbnail_img_id'] ) ? esc_attr( $this->options['default_thumbnail_img_id'] ) : ''
				);
				
				printf( '<img id="default_thumb_button" src="%s"></img>',
					SOSERE_PLUGIN_DIR.'sosere_img/admin-icon-edit.png'
				);
				print( '<span class="admininfo"> ' . __( 'Choose a default thumbnail image. It will be shown if you use thumbnails for recommendations and a recommended article has no thumbnail.', 'sosere-rec' ) . '</span>' );		
			} else {
				print( '<span class="admininfo"> ' . __( 'This option requires Wordpress 3.5 or newer. Update Wordpress if you want to use it.', 'sosere-rec' ) . '</span>' );
			}
		}
		
		public function sosere_use_custom_css_callback()
		{
			if ( isset( $this->options['use_custom_css'] ) && 'on' == $this->options['use_custom_css'] ) $checkbox_use_custom_css = 'checked="checked"'; else $checkbox_use_custom_css = '';
			printf(
				'<input type="checkbox" id="use_custom_css" name="plugin_sosere[use_custom_css]" %s />',
				$checkbox_use_custom_css
			);
			print( '<span class="admininfo">' . __( 'Check this box if you want to use custom css definitions in sosere-recommendation-custom.css', 'sosere-rec' ) . '</span>' );
		}
		
		public function sosere_hide_output_callback()
		{
			if( isset( $this->options['hide_output'] ) && 'on' == $this->options['hide_output'] ) $checkbox_hide_output = 'checked="checked"'; else $checkbox_hide_output = '';
			printf(
				'<input type="checkbox" id="hide_output" name="plugin_sosere[hide_output]" %s />',
				$checkbox_hide_output
			);
			print( '<span class="admininfo">' . __( 'SOSERE uses categories, tags and user behaviour data for recommendations. It takes up to 6 weeks to collect enough data for a usable network. You can activate the plugin, let it learn and hide the output. Useful also for A/B testing.', 'sosere-rec' ) . '</span>' );
		}
		
		public function sosere_include_pages_callback()
		{
			if ( isset( $this->options['include_pages'] ) && 'on' == $this->options['include_pages'] ) $checkbox_include_pages = 'checked="checked"'; else $checkbox_include_pages = '';
			printf(
				'<input type="checkbox" id="include_pages" name="plugin_sosere[include_pages]" %s />',
				$checkbox_include_pages
			);
			print( '<span class="admininfo">' . __( 'By default SOSERE shows recommendations on posts but not on (custom) pages. Check this box if you like to show recommendations on pages and custom page types too.', 'sosere-rec' ) . '</span>' );
		}

		/** 
		 * Get the settings option array and print its values
		 */
		public function sosere_title_callback()
		{
			printf(
				'<input type="text" id="recommedation_box_title" name="plugin_sosere[recommedation_box_title]" value="%s" />',
				isset( $this->options['recommedation_box_title'] ) ? esc_attr( $this->options['recommedation_box_title'] ) : ''
			);
			print( '<span class="admininfo">' . __( 'Type in a recommendation box title like "Recommended for you:" or "You could also be interested in:".', 'sosere-rec' ) . '</span>' );
		}
		
		public function sosere_resultcount_callback() {
			$optionsstring = '';
			for ( $i = 1; $i <= 6; $i++ ) {		
				$optionsstring .= '<option ';
				if ( isset( $this->options['result_count'] ) && $i == $this->options['result_count'] ) {
					$optionsstring .= 'selected';
				} 
				$optionsstring .= '>'.$i.'</option>';
			}
			printf(
				'<select name="plugin_sosere[result_count]" size="1">' . $optionsstring . '</select>'
			);
			print( '<span class="admininfo">' . __( 'Choose the maximum count of recommended posts.', 'sosere-rec' ) . '</span>' );

		}
		
		public function sosere_maxpostage_callback()
		{
			printf(
				'<input type="text" id="max_post_age" name="plugin_sosere[max_post_age]" value="%s" />',
				isset( $this->options['max_post_age'] ) ? esc_attr( $this->options['max_post_age'] ) : ''
			);
			print( '<span class="admininfo">' . __( 'How old may recommended posts be? Put 0 in for no age limit. Default is 1000 days.', 'sosere-rec' ) . '</span>' );

		}
		
		public function sosere_maxviewhistory_callback()
		{
			printf(
				'<input type="text" id="max_view_history" name="plugin_sosere[max_view_history]" value="%s" />',
				isset( $this->options['max_view_history'] ) ? esc_attr( $this->options['max_view_history'] ) : ''
			);
			print( '<span class="admininfo">' . __( 'User behaviour is the basis of recommendations. How old may considered user actions be? Default is 30 days.', 'sosere-rec' ) . '</span>' );
		}

		/*
		* first activation settings
		* @since 1.0
		* @author: Arthur Kaiser <social-semantic-recommendation@sosere.com>
		*/
		public function sosere_setup_on_activation() {
			if ( current_user_can( 'activate_plugins' ) ) {
				
				$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
				check_admin_referer( "activate-plugin_{$plugin}" );
				// activation flag 
				update_option ( 'plugin_sosere_activated', array( 'plugin_sosere_activated' => true ) );
				return;
			} else {
				return;
			}
		}
		
		/*
		* sosere_activated
		* set activation flag
		* @since 1.4.4
		* @author: Arthur Kaiser <social-semantic-recommendation@sosere.com>
		*/
		public function sosere_activated( $file ) {
			if ( current_user_can( 'activate_plugins' ) && $file == substr( SOSERE_PLUGIN_BASENAME, -mb_strlen( $file ) ) ) {
				update_option ( 'plugin_sosere_activated', array( 'plugin_sosere_activated' => true ) );
			}
			return;
			
		}
		/*
		* activation message
		* @since 1.4.4
		* @author: Arthur Kaiser <social-semantic-recommendation@sosere.com>
		*/
		public function sosere_msg_on_reactivation() {
			global $hook_suffix;
			
			if ( $hook_suffix === 'plugins.php' && !isset($_POST['submit']) ) {
				$activated = get_option( 'plugin_sosere_activated' );
				if ( isset( $activated['plugin_sosere_activated'] ) && $activated['plugin_sosere_activated'] === true ){
					$activation_msg = '<div class="updated">';
					$activation_msg .= '<p>';
					$activation_msg .= __( 'Thank you for activating SOSERE. It is free software. <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=S72VJQJHV4J8G">Buy us some coffee</a> and support continuous improvement of <a href="http://www.sosere.com">SOSERE</a>.','sosere-rec' );
					$activation_msg .= '</p>';
					$activation_msg .= '</div><!-- /.updated -->';
					echo $activation_msg;
					delete_option( 'plugin_sosere_activated' );
				}
			}
			return;
		}
		
		/*
		* secure custom css file before update
		* @since 1.6
		* @author: Arthur Kaiser <social-semantic-recommendation@sosere.com>
		*/
		public function sosere_secure_custom_css_on_update() {
			if( isset( $_REQUEST["plugin"] ) && 0 === stripos( $_REQUEST["plugin"], 'social-semantic-recommendation-sosere' ) && $_REQUEST["action"] == 'upgrade-plugin' ) {
				$dest = realpath(SOSERE_PLUGIN_ROOT_DIR.'../');    
				if ( file_exists( SOSERE_PLUGIN_ROOT_DIR.'sosere_css/sosere-recommendation-custom.css' ) ) {
					copy( SOSERE_PLUGIN_ROOT_DIR.'sosere_css/sosere-recommendation-custom.css', $dest.'/sosere-recommendation-custom.css' );
				}
			}
		}
		
		/*
		* restore custom css file after update
		* @since 1.6
		* @author: Arthur Kaiser <social-semantic-recommendation@sosere.com>
		*/
		public function sosere_restore_custom_css_on_update() {
			if( isset( $_REQUEST["plugin"] ) && 0 === stripos( $_REQUEST["plugin"], 'social-semantic-recommendation-sosere' ) && $_REQUEST["action"] == 'upgrade-plugin' ) {
				$src = realpath( SOSERE_PLUGIN_ROOT_DIR .'../' );  
				if ( file_exists( $src.'/sosere-recommendation-custom.css' ) ) {
					copy( $src.'/sosere-recommendation-custom.css', SOSERE_PLUGIN_ROOT_DIR.'sosere_css/sosere-recommendation-custom.css' );
				}
			}
		}
		
		/*
		* update message
		* @since 1.4.4
		* @author: Arthur Kaiser <social-semantic-recommendation@sosere.com>
		*/
		public function sosere_msg_on_update( $value=null, $hook_extra=null, $result=null ) {
			if( isset( $_GET["plugin"] ) && 0 === stripos( $_GET["plugin"], 'social-semantic-recommendation-sosere' ) && $_GET["action"] == 'upgrade-plugin' ) {
				$activation_msg = '<div class="updated">';
				$activation_msg .= '<p>';
				$activation_msg .= __( 'Thank you for updating SOSERE. It is free software. <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=S72VJQJHV4J8G">Buy us some coffee</a> and support continuous improvement of <a href="http://www.sosere.com">SOSERE</a>.','sosere-rec' );
				$activation_msg .= '</p>';
				$activation_msg .= '</div><!-- /.updated -->';
				update_option ( 'plugin_sosere_activated', array( 'plugin_sosere_activated' => true ) );
				echo $activation_msg;
			} 
			if ( $result ) { 
				return $result; 
			} else if( $value ) {
				return $value;
			} else if( $hook_extra ) {
				return $hook_extra;
			} else {
				return;
			}
		}
		
		public function sosere_extend_description( $links, $file=null ) {
			if ( $file == substr( SOSERE_PLUGIN_BASENAME, -mb_strlen( $file ) ) ) {
	           $links[] = '<a href="options-general.php?page=sosere-settings">' . __('Settings', 'sosere-rec' ) . '</a>';
	           $links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=S72VJQJHV4J8G">' . __('Donate', 'sosere-rec') . '</a>';
	        }
	        return $links;
	    }

	}// end: class Sosere_Admin
} // end: if !class

 new Sosere_Admin();