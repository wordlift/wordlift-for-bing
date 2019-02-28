<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wordlift.io
 * @since      1.0.0
 *
 * @package    Wordlift_For_Bing
 * @subpackage Wordlift_For_Bing/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wordlift_For_Bing
 * @subpackage Wordlift_For_Bing/admin
 * @author     WordLift <hello@wordlift.io>
 */
class Wordlift_For_Bing_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->load_dependencies();

	}

	private function load_dependencies() {

		require_once WP_PLUGIN_DIR . '/wordlift-for-bing/includes/class-wordlift-for-bing-core.php';

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wordlift_For_Bing_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wordlift_For_Bing_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wordlift-for-bing-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wordlift_For_Bing_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wordlift_For_Bing_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wordlift-for-bing-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Add the meta box to UI
	 *
	 * @since    1.0.0
	 */
	public function add_meta_boxes_post( $post ) {

		add_meta_box(
			'wordlift-for-bing-meta-box',
			__( 'WordLift for Bing' ),
			array( $this, 'render_meta_box' ),
			'post',
			'side',
			'default'
		);

	}

	/**
	 * Settings API handler.
	 * Adds setting section, fields and registers settings
	 *
	 * @since    1.0.0
	 */
	public function settings_api_init() {

		add_settings_section(
			'wordlift-for-bing-settings-section',
			__( 'Bing Webmaster Integration'),
			function(){_e( 'You need a Bing Webmaster API Key to connect to Bing Webmaster Tools. <a href="https://docs.microsoft.com/en-us/previous-versions/bing/webmaster-api/hh969388(v%3dtechnet.10)" target="_blank">Get API key</a>.');},
			'writing'
		);

		add_settings_field(
			'wl-bing-api-key',
			'Webmaster API Key',
			array( $this, 'settings_input_text_field' ),
			'writing',
			'wordlift-for-bing-settings-section',
			array(
				'wl-bing-api-key'
			)
		);

		add_settings_field(
			'wl-bing-post-enabled',
			'Submit on post',
			array( $this, 'settings_input_checkbox_field' ),
			'writing',
			'wordlift-for-bing-settings-section',
			array(
				'wl-bing-post-enabled'
			)
		);

		register_setting('writing','wl-bing-api-key');
		register_setting('writing','wl-bing-post-enabled');

	}

	/**
	 * Submit URL to Bing on post
	 *
	 * @param $post_id
	 *
	 * @since    1.0.0
	 */
	public function publishing_post($post_id) {

		$wordlift_bing_core = new Wordlift_For_Bing_Core();
		$wordlift_bing_core->submit_url();

	}

	/**
	 * Render callback for Meta box UI
	 *
	 * @param $post
	 *
	 * @since    1.0.0
	 */
	public function render_meta_box( $post ) {

		$wordlift_bing_core = new Wordlift_For_Bing_Core();
		$response_of_before_submit = $wordlift_bing_core->before_submit();

		if($response_of_before_submit){
			if($response_of_before_submit['status'] == true){
				echo '<h4 style="color: green">Will submit to Bing on publish</h4>';
			} else {
				echo '<h4 style="color: darkred">Will not submit to Bing on publish</h4>';
			}
			echo '<ul>';
			foreach($response_of_before_submit['reasons'] as $reason){
				echo '<li>'.$reason.'</li>';
			}
			echo '</ul>';
		} else {
			$response_of_get_info = $wordlift_bing_core->get_response();
			if ( is_wp_error( $response_of_get_info ) ) {
				echo $response_of_get_info->get_error_message();
			} else {
				echo <<<HTML
<h4>URL Traffic Info</h4>			
<table style="font-size: 12px">
	<tr>
		<td style="font-weight: bold">DiscoveryDate</td>
		<td>{$response_of_get_info->DiscoveryDate}</td>
	</tr>
	<tr>
		<td style="font-weight: bold">LastCrawledDate</td>
		<td>{$response_of_get_info->LastCrawledDate}</td>
	</tr>	
	<tr>
		<td style="font-weight: bold">DocumentSize</td>
		<td>{$response_of_get_info->DocumentSize}</td>
	</tr>
	<tr>
		<td style="font-weight: bold">HttpStatus</td>
		<td>{$response_of_get_info->HttpStatus}</td>
	</tr>	
	<tr>
		<td style="font-weight: bold">TotalChildUrlCount</td>
		<td>{$response_of_get_info->TotalChildUrlCount}</td>
	</tr>	
	<tr>
		<td style="font-weight: bold">AnchorCount</td>
		<td>{$response_of_get_info->AnchorCount}</td>
	</tr>		
	<tr>
		<td style="font-weight: bold">Clicks</td>
		<td>{$response_of_get_info->Clicks}</td>
	</tr>	
	<tr>
		<td style="font-weight: bold">Impressions</td>
		<td>{$response_of_get_info->Impressions}</td>
	</tr>		
</table>
HTML;
			}
		}
	}

	/**
	 * Setting fields
	 *
	 * @param $args
	 *
	 * @since    1.0.0
	 */
	public function settings_input_text_field( $args ){
		$option = get_option($args[0]);

echo <<<HTML
<input type="text" id="$args[0]" name="$args[0]" value="$option" class="regular-text ltr" />
HTML;

	}

	public function settings_input_checkbox_field( $args ){
		$checked = checked(1, get_option($args[0]), false );

echo <<<HTML
<label for="$args[0]">
	<input type="checkbox" id="$args[0]" name="$args[0]" value="1" $checked /> 
	Enable submit to Bing on post
</label>
HTML;

	}
}
