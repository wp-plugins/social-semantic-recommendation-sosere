<?php
/**
 * Plugin Name: Social Semantic Recommendation (SOSERE)
 * Plugin URI: http://www.sosere.com
 * Description: SOSERE displays a list or thumbnails of related entries at the bottom of a post based on an unique, self-learning, socialsemantic analysis algorithm. It is efficient and fits perfect to each post individually.
 * Version: 1.11.2
 * Author: Arthur Kaiser <social-semantic-recommendation@sosere.com>
 * Author URI: http://www.arthurkaiser.de
 * License: GPLv2
 * Text Domain: sosere-rec
 * Domain Path: /sosere_languages/
 */

 
 /*  Copyright 2014  Arthur Kaiser (email: social-semantic-recommendation@sosere.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
* Description reference for translations
*/
$dump = __( 'SOSERE displays a list or thumbnails of related entries at the bottom of a post based on an unique, self-learning, socialsemantic analysis algorithm. It is efficient and fits perfect to each post individually.', 'sosere-rec' );

/**
 * Avoid direct calls to this file
 *
 * @since 1.0
 * @author Arthur Kaiser <social-semantic-recommendation@sosere.com>
 *        
 * @package sosere
 */
if( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
} // end: if(!function_exists('add_action'))

if( ! class_exists( 'Social_Semantic_Reommendation_SOSERE' ) ) {

	class Social_Semantic_Recommendation_SOSERE
	{

		/**
		 * php constructor
		 */
		public function __construct() {
			// define constants
			if( ! defined( 'SOSERE_REQUIRED_WP_VERSION' ) ) define( 'SOSERE_REQUIRED_WP_VERSION', '3.2' );
			
			if( ! defined( 'SOSERE_PLUGIN_ROOT_DIR' ) ) define( 'SOSERE_PLUGIN_ROOT_DIR', plugin_dir_path( __FILE__ ) );
			
			if( ! defined( 'SOSERE_PLUGIN_SUBDIR' ) ) define( 
				'SOSERE_PLUGIN_SUBDIR', 
				dirname( plugin_basename( __FILE__ ) ) );
			
			if( ! defined( 'SOSERE_PLUGIN_DIR' ) ) define( 
				'SOSERE_PLUGIN_DIR', 
				plugins_url() . '/' . basename( dirname( __FILE__ ) ) . '/' );
			
			if( ! defined( 'SOSERE_PLUGIN_BASENAME' ) ) define( 'SOSERE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			
			if( ! defined( 'WP_VERSION' ) ) define( 'WP_VERSION', $GLOBALS['wp_version'] );
			
			// check wp version
			if( version_compare( WP_VERSION, SOSERE_REQUIRED_WP_VERSION, '<' ) ) {
				return false;
			}
			
			// load langs
			load_plugin_textdomain( 'sosere-rec', false, SOSERE_PLUGIN_SUBDIR . '/sosere_languages/' );
			
			if( ! is_admin() ) {
				// require libs
				require_once SOSERE_PLUGIN_ROOT_DIR . 'sosere_lib/sosere-controller.php';
			} elseif( is_admin() ) {
				// require libs
				require_once SOSERE_PLUGIN_ROOT_DIR . 'sosere_lib/sosere-admin.php';
			}
		} // end constructor
	} // end class
} // end if !class

$run = new Social_Semantic_Recommendation_SOSERE();
