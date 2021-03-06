<?php
/**
 * LSX_Activities
 *
 * @package   LSX_Activities
 * @author    LightSpeed
 * @license   GPL-2.0+
 * @link      
 * @copyright 2018 LightSpeedDevelopment
 */
if (!class_exists( 'LSX_Activities' ) ) {
	/**
	 * Main plugin class.
	 *
	 * @package LSX_Activities
	 * @author  LightSpeed
	 */
	class LSX_Activities {
		
		/**
		 * The plugins id
		 */
		public $plugin_slug = 'lsx-activities';

		/**
		 * The post types the plugin registers
		 */
		public $post_types = false;	

		/**
		 * The singular post types the plugin registers
		 */
		public $post_types_singular = false;	

		/**
		 * An array of the post types slugs plugin registers
		 */
		public $post_type_slugs = false;			

		/**
		 * The taxonomies the plugin registers
		 */
		public $taxonomies = false;				

		/**
		 * The taxonomies the plugin registers (plural)
		 */
		public $taxonomies_plural = false;

		/**
		 * Hold the TO options
		 */
		public $options = array();

		/**
		 * Hold the GoogleMaps API key
		 */
		public $api_key = false;

		/**
		 * Constructor
		 */
		public function __construct() {
			//Set the variables
			$this->set_vars();
			$this->lsx_to_search_integration();

			// Make TO last plugin to load
			add_action( 'activated_plugin', array( $this, 'activated_plugin' ) );
			
			add_action('init',array($this,'load_plugin_textdomain'));

			if(false !== $this->post_types){
				add_filter( 'lsx_to_framework_post_types', array( $this, 'post_types_filter') );
				add_filter( 'lsx_to_post_types', array( $this, 'post_types_filter') );
				add_filter( 'lsx_to_post_types_singular', array( $this, 'post_types_singular_filter') );
				add_filter('lsx_to_settings_path',array( $this, 'plugin_path'),10,2);
			}
			if(false !== $this->taxonomies){
				add_filter( 'lsx_to_framework_taxonomies', array( $this, 'taxonomies_filter') );
				add_filter( 'lsx_to_framework_taxonomies_plural', array( $this, 'taxonomies_plural_filter') );
			}	

			require_once(LSX_ACTIVITIES_PATH . '/classes/class-lsx-activities-admin.php');
			require_once(LSX_ACTIVITIES_PATH . '/classes/class-lsx-activities-frontend.php');
			require_once(LSX_ACTIVITIES_PATH . '/includes/template-tags.php');

			// flush_rewrite_rules()
			register_activation_hook( LSX_ACTIVITIES_CORE, array( $this, 'register_activation_hook' ) );
			add_action( 'admin_init', array( $this, 'register_activation_hook_check' ) );
		}

		/**
		 * Include the post type for the search integration
		 */
		public function lsx_to_search_integration(){
			add_filter( 'lsx_to_search_post_types', array( $this, 'post_types_filter') );
			add_filter( 'lsx_to_search_taxonomies', array( $this, 'taxonomies_filter') );
		}
	
		/**
		 * Load the plugin text domain for translation.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'lsx-activities', FALSE, basename( LSX_ACTIVITIES_PATH ) . '/languages');
		}

		/**
		 * Sets the plugins variables
		 */
		public function set_vars() {
			$this->post_types = array(
				'activity'	=>	__('Activities','lsx-activities')
			);
			$this->post_types_singular = array(
				'activity'	=>	__('Activity','lsx-activities')
			);
			$this->post_type_slugs = array_keys($this->post_types);

			$this->options = get_option('_lsx-to_settings',false);
			if((false !== $this->options && isset($this->options['api']['googlemaps_key'])) || defined('GOOGLEMAPS_API_KEY')) {

				if (!defined('GOOGLEMAPS_API_KEY')) {
					$this->api_key = $this->options['api']['googlemaps_key'];
				} else {
					$this->api_key = GOOGLEMAPS_API_KEY;
				}
			}
		}

		/**
		 * Adds our post types to an array via a filter
		 */
		public function plugin_path($path,$post_type){
			if(false !== $this->post_types && array_key_exists($post_type,$this->post_types)){
				$path = LSX_ACTIVITIES_PATH;
			}
			return $path;
		}	

		/**
		 * Adds our post types to an array via a filter
		 */
		public function post_types_slugs_filter($post_types){
			if(is_array($post_types)){
				$post_types = array_merge($post_types,$this->post_type_slugs);
			}else{
				$post_types = $this->post_type_slugs;
			}
			return $post_types;
		}

		/**
		 * Adds our post types to an array via a filter
		 */
		public function post_types_filter($post_types){
			if(is_array($post_types) && is_array($this->post_types)){
				$post_types = array_merge($post_types,$this->post_types);
			}elseif(is_array($this->post_types)){
				$post_types = $this->post_types;
			}
			return $post_types;
		}	

		/**
		 * Adds our post types to an array via a filter
		 */
		public function post_types_singular_filter($post_types_singular){
			if(is_array($post_types_singular) && is_array($this->post_types_singular)){
				$post_types_singular = array_merge($post_types_singular,$this->post_types_singular);
			}elseif(is_array($this->post_types_singular)){
				$post_types_singular = $this->post_types_singular;
			}
			return $post_types_singular;
		}	

		/**
		 * Adds our taxonomies to an array via a filter
		 */
		public function taxonomies_filter($taxonomies){
			if(is_array($taxonomies) && is_array($this->taxonomies)){
				$taxonomies = array_merge($taxonomies,$this->taxonomies);
			}elseif(is_array($this->taxonomies)){
				$taxonomies = $this->taxonomies;
			}
			return $taxonomies;
		}

		/**
		 * Adds our taxonomies_plural to an array via a filter
		 */
		public function taxonomies_plural_filter($taxonomies_plural){
			if(is_array($taxonomies_plural) && is_array($this->taxonomies_plural)){
				$taxonomies_plural = array_merge($taxonomies_plural,$this->taxonomies_plural);
			}elseif(is_array($this->taxonomies_plural)){
				$taxonomies_plural = $this->taxonomies_plural;
			}
			return $taxonomies_plural;
		}
	
		/**
		 * Make TO last plugin to load.
		 */
		public function activated_plugin() {
			if ( $plugins = get_option( 'active_plugins' ) ) {
				$search = preg_grep( '/.*\/tour-operator\.php/', $plugins );
				$key = array_search( $search, $plugins );

				if ( is_array( $search ) && count( $search ) ) {
					foreach ( $search as $key => $path ) {
						array_splice( $plugins, $key, 1 );
						array_push( $plugins, $path );
						update_option( 'active_plugins', $plugins );
					}
				}
			}
		}
	
		/**
		 * On plugin activation
		 */
		public function register_activation_hook() {
			if ( ! is_network_admin() && ! isset( $_GET['activate-multi'] ) ) {
				set_transient( '_tour_operators_activities_flush_rewrite_rules', 1, 30 );
			}
		}
		
		/**
		 * On plugin activation (check)
		 */
		public function register_activation_hook_check() {
			if ( ! get_transient( '_tour_operators_activities_flush_rewrite_rules' ) ) {
				return;
			}

			delete_transient( '_tour_operators_activities_flush_rewrite_rules' );
			flush_rewrite_rules();
		}

	}
	new LSX_Activities();
}