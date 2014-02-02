<?php
/**
 * Plugin Name: Social Semantic Recommendation (SOSERE)
 * Plugin URI: http://www.sosere.com
 * Description: Recommendation of related/interesting post on your blog. Based on socialsemantic network analysis for recommendations. It is self-learning and need up to 8 weeks (depend on your blog tariffic) to build the used posts network. See settings for customisation.
 * Version: 1.4.3
 * Author: Arthur Kaiser <social-semantic-recommendation@sosere.com>
 * Author URI: http://www.arthurkaiser.de
 * License: GPL2
 * Text Domain: sosere-rec
 * Domain Path: /languages/
 */
 
 /*  Copyright 2013  Arthur Kaiser (email: social-semantic-recommendation@sosere.com)

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

/**
 * Avoid direct calls to this file
 *
 * @since 1.0
 * @author Arthur Kaiser <social-semantic-recommendation@sosere.com>
 *
 * @package sosere
 */
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
} //end: if(!function_exists('add_action'))

define( 'SOSERE_REQUIRED_WP_VERSION', '3.2' );

if ( ! defined( 'SOSERE_PLUGIN_ROOT_DIR' ) )
	define( 'SOSERE_PLUGIN_ROOT_DIR', plugin_dir_path( __FILE__) );
	
if ( ! defined( 'SOSERE_PLUGIN_SUBDIR' ) )
	define( 'SOSERE_PLUGIN_SUBDIR', dirname( plugin_basename( __FILE__ ) ) ); 

if ( ! defined( 'SOSERE_PLUGIN_DIR' ) )
	define( 'SOSERE_PLUGIN_DIR', plugins_url().'/'.basename( dirname( __FILE__ ) ).'/' );

if ( ! defined( 'SOSERE_ADMIN_READ_CAPABILITY' ) )
	define( 'SOSERE_ADMIN_READ_CAPABILITY', 'edit_posts' );

if ( ! defined( 'SOSERE_ADMIN_READ_WRITE_CAPABILITY' ) )
	define( 'SOSERE_ADMIN_READ_WRITE_CAPABILITY', 'publish_pages' );

	if ( ! defined( 'WP_VERSION' ) )
	define( 'WP_VERSION', $GLOBALS['wp_version'] );

// check wp version
if( version_compare( WP_VERSION, SOSERE_REQUIRED_WP_VERSION, '<' ) ) {
	return false;
}

if ( ! is_admin() ) {
// require libs
require_once SOSERE_PLUGIN_ROOT_DIR . 'sosere_lib/sosere-controller.php';

} elseif ( is_admin() ) {
// require libs
require_once SOSERE_PLUGIN_ROOT_DIR . 'sosere_lib/sosere-admin.php';

// register plugin activation 
register_activation_hook(   __FILE__, 'sosere_setup_on_activation' );

}	

/*
* activation settings
* @since 1.0
* @author: Arthur Kaiser <social-semantic-recommendation@sosere.com>
*/

function sosere_setup_on_activation() {
    if ( ! current_user_can( 'activate_plugins' ) )
        return;
	if ( 0 == count( get_option( 'plugin_sosere' ) ) ) {
		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		check_admin_referer( "activate-plugin_{$plugin}" );
		$sosere_default_options = array(
			"use_cache" => "on",
			"max_cache_time" => "24",
			"recommedation_box_title" => __( "Recommended for you" ),
			"result_count" => "3",
			"max_post_age" => "10000",
			"max_view_history" => "30"
		);
		update_option( 'plugin_sosere', $sosere_default_options );
	}
}




