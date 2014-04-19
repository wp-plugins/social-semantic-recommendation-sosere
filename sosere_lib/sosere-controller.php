<?php
/**
 * File: sosere-controller.php
 * Class: Sosere_Controller
 * Description: Main plugin controller
 *
 * @package sosere 
 * @author Arthur Kaiser <social-semantic-recommendation@sosere.com>
 */
/*
 * avoid to call it directly
 */
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
} // end: if(!function_exists('add_action'))

if ( ! class_exists( 'Sosere_Controller' ) ) {

	class Sosere_Controller
	{
		// class vars
		public $max_view_history = 30; // in days
		public $max_post_age = 1000;

		public $max_results = 3;

		private $taxonomy_selection = array();

		private $category_selection = array();

		private $user_selection = array();

		private $viewed_post_IDs = array();

		private $use_cache = false;

		private $max_cache_time = 1;

		private $recommendation_box_title = 'Read more';

		private $included_post_types = 'post';

		private $browser_locate;

		private $plugin_options_name = 'plugin_sosere';

		private $array_sosere_options;

		private $prefetch_request = false;

		private $show_thumbs_title = false;

		private $title_leng = 50;

		private $show_thumbs = false;

		private $sosere_custom_thumbnail_size = '150x150';

		private $default_thumbnail_img_url = null;

		private $use_custom_css = false;

		private $hide_output = false;

		private $dnt = null;

		/**
		 * PHP 5 Object Constructor
		 *
		 * @since 1.0
		 * @author : Arthur Kaiser <social-semantic-recommendation@sosere.com>
		 */
		function __construct() {
			if ( ! is_admin() ) {
				$this->array_sosere_options = get_option( $this->plugin_options_name );
				
				if ( isset( $this->array_sosere_options['use_cache'] ) && 'on' == $this->array_sosere_options['use_cache'] ) $this->use_cache = true;
				if ( isset( $this->array_sosere_options['max_cache_time'] ) ) $this->max_cache_time = (int) $this->array_sosere_options['max_cache_time'];
				if ( isset( $this->array_sosere_options['recommendation_box_title'] ) ) $this->recommendation_box_title = $this->array_sosere_options['recommendation_box_title'];
				if ( isset( $this->array_sosere_options['show_thumbs'] ) && 1 == $this->array_sosere_options['show_thumbs'] ) $this->show_thumbs_title = true;
				if ( isset( $this->array_sosere_options['show_thumbs'] ) && 2 == $this->array_sosere_options['show_thumbs'] ) $this->show_thumbs = true;
				if ( isset( $this->array_sosere_options['sosere_custom_thumbnail_size'] ) && 0 < strlen( $this->array_sosere_options['sosere_custom_thumbnail_size'] ) ) $this->sosere_custom_thumbnail_size = $this->array_sosere_options['sosere_custom_thumbnail_size'];
				if ( isset( $this->array_sosere_options['default_thumbnail_img_url'] ) ) $this->default_thumbnail_img_url = $this->array_sosere_options['default_thumbnail_img_url'];
				
				if ( isset( $this->array_sosere_options['include_pages'] ) && 'on' == $this->array_sosere_options['include_pages'] ) $this->included_post_types = 'any';
				if ( isset( $this->array_sosere_options['use_custom_css'] ) && 'on' == $this->array_sosere_options['use_custom_css'] ) $this->use_custom_css = true;
				
				if ( isset( $this->array_sosere_options['hide_output'] ) && 'on' == $this->array_sosere_options['hide_output'] ) $this->hide_output = true;
				if ( isset( $this->array_sosere_options['result_count'] ) ) $this->max_results = (int) $this->array_sosere_options['result_count'];
				if ( isset( $this->array_sosere_options['max_view_history'] ) ) $this->max_view_history = (int) $this->array_sosere_options['max_view_history'];
				if ( isset( $this->array_sosere_options['max_post_age'] ) ) $this->max_post_age = (int) $this->array_sosere_options['max_post_age'];
				
				$this->now = time();
				
				global $post;
				$this->post = $post;
				
				// be sure a a session is available
				add_action( 'init', array( $this, 'sosere_start_session' ), 1 );
				add_action( 'wp_logout', array( $this, 'sosere_end_session' ) );
				add_action( 'wp_login', array( $this, 'sosere_end_session' ) );
				
				// get prefetch header
				$this->sosere_get_prefetch_header();
				
				// include frontend css
				add_action( 'wp_enqueue_scripts', array( $this, 'sosere_add_stylesheet' ) );
				
				// session handling
				add_action( 'shutdown', array( $this, 'sosere_handle_session' ), 9999 );
				
				// run
				add_filter( 'the_content', array( $this, 'sosere_run' ) );
			}
			// set browser locate
			$this->browser_locate = explode( ';', $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
			$this->browser_locate = explode( ',', $this->browser_locate[0] );
		} // end constructor
		
		/**
		 * main function
		 *
		 * @since 1.0
		 * @author : Arthur Kaiser <social-semantic-recommendation@sosere.com>
		 */
		public function sosere_run( $content ) {
			if ( is_single() || is_page() ) {
				
				if ( ! isset( $this->array_sosere_options['include_pages'] ) || 'on' !== $this->array_sosere_options['include_pages'] ) {
					if ( is_page() ) {
						return $content;
					}
				}
				
				if ( ! is_object( $this->post ) ) {
					global $post;
					$this->post = $post;
				}
				
				// add current post to network
				$this->add_post_to_db();
				
				// get cached selection if used
				if ( true == $this->use_cache ) {
					$cached = get_post_meta( $this->post->ID, 'soseredbviewedpostscache' );
					$cachetime = get_post_meta( $this->post->ID, 'soseredbviewedpostscachedate' );
					
					// diff in hours
					if ( isset( $cachetime[0] ) ) {
						$cachetime = $cachetime[0];
						$diff = ( $this->now - $cachetime ) / ( 60 * 60 );
					} else {
						$diff = null;
					}
					if ( $cached && 0 < $cachetime && 0 < $diff && $diff < $this->max_cache_time ) {
						if ( false === $this->hide_output ) {
							return $content . $cached[0];
						} else {
							return $content;
						}
					}
				}
				
				$db_selection = array();
				
				// get selections
				// add filter (post age)
				add_filter( 'posts_where', array( $this, 'additional_filter' ) );
				add_filter( 'posts_distinct', array( $this, 'search_distinct' ) );
				
				// get tag id's
				$taxonomy_id_array = wp_get_post_tags( $this->post->ID, array( 'fields' => 'ids' ) );
				
				// get post categories
				$category_array = get_the_category( $this->post->ID );
				
				// get category id's
				$category_id_array = array();
				foreach ( $category_array as $category ) {
					$category_id_array[] = $category->cat_ID;
				}
				$args_array = array( 
						'posts_per_page' 	=> 32 + $this->max_results + ( count( $category_id_array ) + 1 ) + count( $taxonomy_id_array ), 
						'post_type' 		=> explode( ',', $this->included_post_types ), 
						'post_status' 		=> 'publish', 
						'orderby' 			=> 'rand', 
						'suppress_filters'  => false, 
				);
				if ( is_array( $category_id_array ) 
						&& is_array( $taxonomy_id_array ) 
						&& 0 < count( $taxonomy_id_array ) 
						&& 0 < count( $category_id_array ) ) {
					$args_array['tax_query'] = array( 
											'relation' => 'OR', 
													array(  
														'taxonomy' => 'category', 
														'field'    => 'cat_ID',
														'terms' => $category_id_array, 
														), 
													array( 
														'taxonomy' => 'post_tag',
														'field' => 'term_id', 
														'terms' => $taxonomy_id_array, 
														),
					 );
				} elseif ( is_array( $taxonomy_id_array ) && 0 < count( $taxonomy_id_array ) ) {
					$args_array['tag__in'] = $taxonomy_id_array;
				} elseif ( is_array( $category_id_array ) && 0 < count( $category_id_array ) ) {
					$args_array['category__in'] = $category_id_array;
				}
				// fire query
				$posts_arr = get_posts( $args_array );
				
				// add to categories selection
				if ( isset( $posts_arr ) && is_array( $posts_arr ) ) {
					foreach ( $posts_arr as $post_obj ) {
						if ( is_object( $post_obj ) ) {
							$db_selection[] = (int) $post_obj->ID;
						}
					}
				}
				
				remove_filter( 'posts_where', array( $this, 'additional_filter' ) );
				remove_filter( 'posts_distinct', array( $this, 'search_distinct' ) );
				
				// merge selections
				$all_selection = array_merge( $db_selection, $this->user_selection );
				
				// get selected post id's
				$selected_post_IDs = $this->preferential_selection( $all_selection );
				
				// get post content
				$selected_posts_arr = get_posts( array( 'include' => implode( ',', $selected_post_IDs ), 'post_type' => array( $this->included_post_types ), 'posts_per_page' => $this->max_results, 'suppress_filters' => true ) );
				
				$recommendation_string = $this->get_html_output( $selected_posts_arr );
				
				// cache it in db if used
				if ( true == $this->use_cache ) {
					if ( isset( $selected_posts_arr ) ) {
						add_post_meta( $this->post->ID, 'soseredbviewedpostscache', $recommendation_string, true ) or update_post_meta( $this->post->ID, 'soseredbviewedpostscache', $recommendation_string );
						add_post_meta( $this->post->ID, 'soseredbviewedpostscachedate', $this->now, true ) or update_post_meta( $this->post->ID, 'soseredbviewedpostscachedate', $this->now );
					}
				}
				return $content . $recommendation_string;
			} else {
				return $content;
			}
		}

		/**
		 * Add actual seen post to posts network
		 *
		 * @since 1.0
		 * @author : Arthur Kaiser <social-semantic-recommendation@sosere.com>
		 */
		private function add_post_to_db() {
			if ( isset( $_SESSION['sosereviewedposts'] ) && is_array( $_SESSION['sosereviewedposts'] ) ) {
				$this->viewed_post_IDs = $_SESSION['sosereviewedposts'];
			}
			$network_data = @unserialize( get_post_meta( $this->post->ID, 'soseredbviewedposts', true ) );
			if ( false !== $network_data ) {
				foreach ( $network_data as $key => $network_data_set ) {
					if ( $network_data_set['id'] != $this->post->ID ) {
						if ( 0 < $network_data_set['timestamp'] ) {
							$diff = ( $this->now - $network_data_set['timestamp'] ) / ( 60 * 60 * 24 );
							if ( $diff <= $this->max_view_history || ( 0 === $this->max_view_history ) ) {
								$new_network_data[] = array( 'id' => $network_data_set['id'], 'timestamp' => $network_data_set['timestamp'] );
								// add to selection
								$this->user_selection[] = (int) $network_data_set['id'];
							}
						}
					}
				}
			}
			
			if ( ( is_single() || is_page() ) && false === $this->prefetch_request ) {
				// add new post to network but prevent self relations and reload entries
				if ( isset( $this->viewed_post_IDs ) && is_array( $this->viewed_post_IDs ) ) {
					$sp_id = null;
					if ( $this->post->ID != end( $this->viewed_post_IDs ) ) {
						// add to network
						foreach ( $this->viewed_post_IDs as $sp_id ) {
							if ( (int) $this->post->ID !== (int) $sp_id ) {
								$new_network_data[] = array( 'id' => $sp_id, 'timestamp' => $this->now );
							}
						}
					}
					// safe to db
					if ( isset( $new_network_data ) && is_array( $new_network_data ) ) {
						$new_network_data_DB = serialize( $new_network_data );
					}
					
					if ( isset( $new_network_data_DB ) ) {
						add_post_meta( $this->post->ID, 'soseredbviewedposts', $new_network_data_DB, true ) or update_post_meta( $this->post->ID, 'soseredbviewedposts', $new_network_data_DB );
					}
				}
			}
		}

		/**
		 * view
		 *
		 * @since 1.0
		 * @author : Arthur Kaiser <social-semantic-recommendation@sosere.com>
		 */
		private function get_html_output( $selected_posts ) {
			// return empty string if hidden output
			if ( true === $this->hide_output || 0 === count( $selected_posts ) ) return '';
			
			// return output as html string else
			$return_string = '<div class="sosere-recommendation entry-utility"><legend>' . __( $this->recommendation_box_title, 'sosere-rec' ) . '</legend><ul class="sosere-recommendation">';
			
			if ( isset( $selected_posts ) && is_array( $selected_posts ) ) {
				
				foreach ( $selected_posts as $post_obj ) {
					if ( is_object( $post_obj ) ) {
						$url = null;
						$post_thumbnail_id = null;
						$thumb = null;
						if ( true === $this->show_thumbs || true === $this->show_thumbs_title ) {
							// explode custom thumbnail size
							$thumb_size = explode( 'x', $this->sosere_custom_thumbnail_size );
							
							// get thumbs
							$post_thumbnail_id = get_post_thumbnail_id( $post_obj->ID );
							if ( '' != $post_thumbnail_id ) {
								$thumb = wp_get_attachment_image_src( $post_thumbnail_id, 'sosere_thumb' );
							} elseif ( '' != $post_thumbnail_id && false === $thumb ) {
									$thumb = wp_get_attachment_image_src( $post_thumbnail_id, 'thumbnail' );
							} else {
								// get post attachment
								$output = preg_match( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post_obj->post_content, $matches );
								if ( isset( $matches[1] ) ) {
										$thumb = array( $matches[1] );
									}
								}
							if ( isset( $thumb ) && is_array( $thumb ) ) {
								$url = $thumb[0];
							} elseif ( 0 < strlen( $this->default_thumbnail_img_url ) ) {
								$url = $this->default_thumbnail_img_url;
							}
							
							// build response string
							$return_string .= '<li class="sosere-recommendation-thumbs" style="width:' . $thumb_size[0] . 'px;">' . '<a href="' . get_permalink( $post_obj->ID ) . '">';
							isset( $url ) ? $return_string .= '<img src="' . $url . '" alt="' . $post_obj->post_title . '" title="' . $post_obj->post_title . '" style="width:' . $thumb_size[0] . 'px; height: ' . $thumb_size[1] . 'px;"/>' : $return_string .= '<div class="no-thumb" style="width:' . $thumb_size[0] . 'px; height: ' . $thumb_size[1] . 'px;"></div>';
							
							// add title
							if ( true === $this->show_thumbs_title ) {
								if ( 0 < $this->title_leng && mb_strlen( $post_obj->post_title ) > $this->title_leng ) {
									$return_string .= '<p>' . substr( $post_obj->post_title, 0, $this->title_leng ) . '...</p>';
								} else {
									$return_string .= '<p>' . $post_obj->post_title . '</p>';
								}
							}
							// close link, list
							$return_string .= '</a></li>';
						} else {
							$return_string .= '<li><a href="' . get_permalink( $post_obj->ID ) . '">' . $post_obj->post_title . '</a></li>';
						}
					}
				}
			}
			$return_string .= '</ul></div>';
			return $return_string;
		}

		/**
		 * selection
		 *
		 * @since 1.0
		 * @author : Arthur Kaiser <social-semantic-recommendation@sosere.com>
		 * @param s $allSelection array
		 * @return array
		 */
		private function preferential_selection( $all_selection ) {
			
			// exclude current and seen posts from being recommended again
			$all_selection_diff = array_diff($all_selection, $this->viewed_post_IDs, array( $this->post->ID ) );
			
			// calculate array size
			$count = count( $all_selection_diff );
			
			// get count of unique queried posts
			$count_unique = count( array_unique( $all_selection_diff ) );
			
			// calculate ratio between max_results and size (count)
			$ratio = floor( $count_unique/$this->max_results );
			
			// take a slice for selection 
			
			if ( 0 === $ratio ) {
				return array_slice( array_diff( array_unique( $all_selection ), array( $this->post->ID ) ) , 0, $this->max_results );
			} else {
				shuffle( $all_selection_diff );
				$slice_selection = array();
				$i = 0;
				
				while ( (count( array_unique( $slice_selection ) ) < $this->max_results ) && $i < $this->max_results*2 ) {

					shuffle( $all_selection_diff );
					$slice_selection = array_slice( $all_selection_diff, 0, $count/$ratio , true );
					$i++;
				}
				return array_slice( array_unique( $slice_selection ), 0, $this->max_results );
			}
		}
		/*
		 * ######################## Helper section ##########################
		 */
		
		/**
		 * Enqueue plugin style-files
		 *
		 * @since 1.0
		 * @author : Arthur Kaiser <social-semantic-recommendation@sosere.com>
		 */
		public function sosere_add_stylesheet() {
			// custom css
			if ( true === $this->use_custom_css && file_exists( SOSERE_PLUGIN_DIR . 'sosere_css/sosere-recommendation-custom.css' ) ) {
				wp_register_style( 'sosere-recommendation-custom-style', SOSERE_PLUGIN_DIR . 'sosere_css/sosere-recommendation-custom.css' );
				wp_enqueue_style( 'sosere-recommendation-custom-style' );
			} else {
				// base css
				wp_register_style( 'sosere-recommendation-style', SOSERE_PLUGIN_DIR . 'sosere_css/sosere-recommendation.css' );
				wp_enqueue_style( 'sosere-recommendation-style' );
			}
		}

		/**
		 * *
		 * Session handling helper
		 *
		 * @since 1.0
		 * @author : Arthur Kaiser <social-semantic-recommendation@sosere.com>
		 */
		public function sosere_start_session() {
			if ( ! session_id() ) {
				session_start();
			}
		}

		public function sosere_end_session() {
			session_destroy();
		}

		public function sosere_get_prefetch_header() {
			if ( isset( $_SERVER['HTTP_X_MOZ'] ) && false !== strripos( 'prefetch', $_SERVER['HTTP_X_MOZ'] ) ) {
				$this->prefetch_request = true;
			}
			if ( isset( $_SERVER['HTTP_DNT'] ) ) {
				$this->dnt = (int) $_SERVER['HTTP_DNT'];
			}
		}

		/**
		 * *
		 * Tracking Session handling
		 *
		 * @since 1.0
		 * @author : Arthur Kaiser <social-semantic-recommendation@sosere.com>
		 */
		public function sosere_handle_session() {
			if ( ! is_object( $this->post ) ) {
				global $post;
				$this->post = $post;
			}
			
			if ( ( is_single() || is_page() ) && false === $this->prefetch_request && 1 !== $this->dnt ) {
				// get viewed postIDs from
				if ( isset( $_SESSION ) && 0 < count( $_SESSION ) && is_array( $_SESSION['sosereviewedposts'] ) ) {
					$this->viewed_post_IDs = $_SESSION['sosereviewedposts'];
					// do not add if reload
					if ( end( $this->viewed_post_IDs ) != (int) $this->post->ID ) {
						$_SESSION['sosereviewedposts'][] = (int) $this->post->ID;
					}
				} else {
					$_SESSION['sosereviewedposts'] = array( (int) $this->post->ID );
				}
			}
		}

		/**
		 * additional query filter
		 *
		 * @since 1.0
		 * @author : Arthur Kaiser <social-semantic-recommendation@sosere.com>
		 */
		function additional_filter( $where = '' ) {
			// posts in the last 30 days
			if ( $this->max_post_age > 0 ) {
				$where .= " AND post_date >= '" . date( 'Y-m-d', strtotime( '-' . $this->max_post_age . ' days' ) ) . "'";
			}
			return $where;
		}

		/**
		 * disable distinct filter
		 *
		 * @since 1.0
		 */
		function search_distinct() {
			return ''; // filter has no effect
		}
	} // end class sosereController
	
} // end: if exists class

$obj = new Sosere_Controller();