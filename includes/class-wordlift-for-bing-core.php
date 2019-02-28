<?php
/**
 * The file that defines the core plugin functionality class
 *
 * @link       https://wordlift.io
 * @since      1.0.0
 *
 * @package    Wordlift_For_Bing
 * @subpackage Wordlift_For_Bing/includes
 */

class Wordlift_For_Bing_Core {

	public function __construct() {

		$this->api_key = trim(get_option('wl-bing-api-key'));
		$this->is_post_enabled = get_option('wl-bing-post-enabled');
		$this->site_url = get_site_url();
		if(get_post_status() === 'publish'){
			$this->url = get_permalink();
		}
		$this->submit_url = get_permalink();
		$this->blog_public = get_option('blog_public');
		$this->load_dependencies();

	}

	/**
	 * Load the required dependencies for this class
	 *
	 * - Bing_Webmaster_API. Dependency free, drop-in PHP wrapper class around Bing Webmaster API
	 */
	private function load_dependencies() {

		require_once WP_PLUGIN_DIR . '/wordlift-for-bing/includes/BingWebmasterAPI.class.php';

	}

	/**
	 * Validate constructor data to prepare for get info calls
	 *
	 * @return WP_Error
	 */
	private function setup_for_get_info() {

		if( empty($this->api_key) ){
			return new WP_Error( "no-key" );
		}

		if( empty($this->url) ){
			return new WP_Error( "no-url" );
		}

		$this->bing_webmaster_api_get_info = new Bing_Webmaster_API($this->api_key);

	}

	/**
	 * Validate constructor data to prepare for submit call
	 *
	 * @return WP_Error
	 */
	private function setup_for_submit() {

		if( empty($this->api_key) ){
			return new WP_Error( "no-key" );
		}

		if( empty($this->submit_url) ){
			return new WP_Error( "no-url" );
		}

		if( $this->is_post_enabled != 1 ){
			return new WP_Error( "not-enabled" );
		}

		if( $this->blog_public != 1 ){
			return new WP_Error( "not-public" );
		}

		/*
		 * To test posts, pass this webhook test URL as second param to constructor
		 * https://webhook.site/fda4f103-ac60-403f-b4a2-3335d8d286f1/
		 *
		 * ... and monitor requests here: https://webhook.site/#/fda4f103-ac60-403f-b4a2-3335d8d286f1/
		 */
		$this->bing_webmaster_api_submit = new Bing_Webmaster_API($this->api_key);

	}

	/**
	 * Call the GetUrlTrafficInfo method of Bing_Webmaster_API and handle exceptions
	 *
	 * @return WP_Error|object
	 */
	private function get_url_traffic_info() {

		try {
			$response = $this->bing_webmaster_api_get_info
				->get('GetUrlTrafficInfo')
				->params(array(
					'siteUrl' => $this->site_url,
					'url'     => $this->url
				))
				->perform();
		} catch(Exception $e){
			return new WP_Error( "bing-api-request", $e->getMessage() );
		}

		if(isset($response->ErrorCode)) {
			return new WP_Error( "bing-api-response", $response->Message );
		}

		return $response;

	}

	/**
	 * Call the GetUrlInfo method of Bing_Webmaster_API and handle exceptions
	 *
	 * @return WP_Error|object
	 */
	private function get_url_info() {

		try {
			$response = $this->bing_webmaster_api_get_info
				->get('GetUrlInfo')
				->params(array(
					'siteUrl' => $this->site_url,
					'url'     => $this->url
				))
				->perform();
		} catch(Exception $e){
			return new WP_Error( "bing-api-request", $e->getMessage() );
		}

		if(isset($response->ErrorCode)) {
			return new WP_Error( "bing-api-response", "$response->Message  ($response->ErrorCode)" );
		}

		return $response;

	}

	/**
	 * Run validations and do get info calls
	 *
	 * @return json string|WP_Error
	 */
	public function get_response() {

		$transient_slug = 'wordlift_for_bing_core_response_'.get_the_ID();

		$wordlift_for_bing_core_response = get_transient( $transient_slug );

		if ( false !== $wordlift_for_bing_core_response ) {
			return Wordlift_For_Bing_Core::sanitize_response($wordlift_for_bing_core_response);
		}

		$setup = $this->setup_for_get_info();

		if ( is_wp_error( $setup ) ) {
			if( $setup->get_error_code() === "no-key" ){
				return new WP_Error( "wordlift-for-bing-core", __('Please save your <a href="'.menu_page_url('writing', false).'">Bing Webmaster API Key in Writing Settings page</a> to use WordLift for Bing.') );
			}
			if( $setup->get_error_code() === "no-url" ){
				return new WP_Error( "wordlift-for-bing-core", __('Waiting to publish post to start showing URL Traffic Info.') );
			}
		}

		$get_url_info = $this->get_url_info();

		if ( is_wp_error( $get_url_info ) ) {
			if( $get_url_info->get_error_code() === "bing-api-request" ){
				return new WP_Error( "wordlift-for-bing-core", __('Error in Bing Webmaster API request. ').$get_url_info->get_error_message() );
			}
			if( $get_url_info->get_error_code() === "bing-api-response" ){
				return new WP_Error( "wordlift-for-bing-core", __('Error in Bing Webmaster API response.').'<p>'.$get_url_info->get_error_message().'</p>'.__('<a href="https://docs.microsoft.com/en-us/previous-versions/bing/webmaster-api/hh969357(v%3dtechnet.10)" target="_blank">Check Bing Webmaster API error codes</a>') );
			}
		}

		$get_url_traffic_info = $this->get_url_traffic_info();

		if ( is_wp_error( $get_url_traffic_info ) ) {
			if( $get_url_traffic_info->get_error_code() === "bing-api-request" ){
				return new WP_Error( "wordlift-for-bing-core", __('Error in Bing Webmaster API request. ').$get_url_traffic_info->get_error_message() );
			}
			if( $get_url_traffic_info->get_error_code() === "bing-api-response" ){
				return new WP_Error( "wordlift-for-bing-core", __('Error in Bing Webmaster API response. ').'<p>'.$get_url_info->get_error_message().'</p>'.__(' <a href="https://docs.microsoft.com/en-us/previous-versions/bing/webmaster-api/hh969357(v%3dtechnet.10)" target="_blank">Check Bing Webmaster API error codes</a>') );
			}
		}

		$response = (object) array_merge((array) $get_url_info->d, (array) $get_url_traffic_info->d);

		set_transient( $transient_slug, $response, 4 * HOUR_IN_SECONDS );
		return Wordlift_For_Bing_Core::sanitize_response($response);

	}

	/**
	 * Run validations before submit. Output is used in UI.
	 *
	 * @return mixed
	 */
	public function before_submit(){

		$post_status = get_post_status();

		if($post_status != 'pending' && $post_status != 'draft' && $post_status != 'auto-draft' && $post_status != 'future'){
			return;
		}

		$yes = array();
		$no = array();

		if( empty($this->api_key) ){
			$no[] = __('Bing Webmaster API Key not saved in Writing Settings page.');
		} else {
			$yes[] = __('Bing Webmaster API Key available.');
		}

		if(get_option( 'wl-bing-post-enabled' ) != 1){
			$no[] = __('Post to Bing disabled globally in Writing Settings page.');
		} else {
			$yes[] = __('Post to Bing enabled globally.');
		}

		if(get_option( 'blog_public' ) != 1){
			$no[] = __('Search engines discouraged from indexing this site (Reading Settings page).');
		} else {
			$yes[] = __('Search engines are encouraged to index this site.');
		}

		if(count($no) > 0){
			return array(
				'status' => false,
				'reasons' => $no
			);
		} else {
			return array(
				'status' => true,
				'reasons' => $yes
			);
		}

	}

	/**
	 * Run validations and do submit url call
	 *
	 * @return WP_Error
	 */
	public function submit_url(){

		$setup = $this->setup_for_submit();

		if ( !is_wp_error( $setup ) ) {
			try {
				$response = $this->bing_webmaster_api_submit
					->post('SubmitUrl')
					->params(array(
						'siteUrl' => $this->site_url,
						'url'     => $this->submit_url
					))
					->perform();
			} catch(Exception $e){
				return new WP_Error( "bing-api-request", $e->getMessage() );
			}
		}

	}

	/**
	 * Static function to sanitize response.
	 * Only date sanitizations as of now.
	 *
	 * @param object $response
	 *
	 * @return object
	 */
	static function sanitize_response($response){
		foreach ($response as $key => $value) {
			if(substr( $value, 0, 6 ) === "/Date("){
				$response->$key = date(get_option( 'date_format' ), Wordlift_For_Bing_Core::parse_date($value));
			}
		}
		return $response;
	}

	/**
	 * Static date sanitizer helper
	 *
	 * @param string $date_string
	 *
	 * @return string
	 */
	static function parse_date($date_string){
		$date_string = str_replace(array('/Date(',')/'),'', $date_string);
		return substr($date_string, 0, 10);
	}

}
