<?php
/**
 * Dependency free, drop-in PHP wrapper class around Bing Webmaster API
 *
 * Method names directly passed. Here's a list of IWebmasterApi Members:
 * https://docs.microsoft.com/en-us/previous-versions/bing/webmaster-api/jj572365(v%3dtechnet.10)
 *
 * Action and data array depends on method.
 *
 * Usage Example:
 *
 * $bing_webmaster_api = new Bing_Webmaster_API('apikey');
 *
 * $response = $bing_webmaster_api
 *  ->get('GetUrlInfo')
 *  ->params(
 *      array(
 *          'siteUrl' => 'http://example.com/',
 *          'url'     => 'http://example.com/path'
 *      )
 *  )
 *  ->perform();
 *
 */

class Bing_Webmaster_API {

	private $api_base;

	protected $api_key;

	public
		$api_action,
		$api_method,
		$api_params,
		$api_url;

	/**
	 * Bing_Webmaster_API constructor.
	 *
	 * @param string $api_key
	 * @param string $api_base
	 */
	public function __construct($api_key, $api_base = 'https://ssl.bing.com/webmaster/api.svc/json/') {

		$this->api_key = $api_key;
		$this->api_base = $api_base;

	}

	/**
	 * Set a get method from the list at https://docs.microsoft.com/en-us/previous-versions/bing/webmaster-api/jj572365(v%3dtechnet.10)
	 *
	 * @param string $method
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function get($method) {

		$this->api_action = 'GET';
		$this->set_method($method);
		return $this;

	}

	/**
	 * Set a post method from the list at https://docs.microsoft.com/en-us/previous-versions/bing/webmaster-api/jj572365(v%3dtechnet.10)
	 *
	 * @param string $method
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function post($method) {

		$this->api_action = 'POST';
		$this->set_method($method);
		return $this;

	}

	/**
	 * Function to sanitize and validate the passed method
	 *
	 * @param string $method
	 *
	 * @throws Exception
	 */
	private function set_method($method) {

		$method = trim($method);

		if(empty($method)){
			throw new Exception('BingWebmasterAPI exception: Invalid method.');
		}

		$this->api_method = $method;
		$this->api_url = $this->api_base . $this->api_method . '?apiKey=' . $this->api_key;
	}

	/**
	 * Set params respective to the selected method
	 *
	 * @param array $params
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function params($params = array()) {

		if($params && !is_array($params)){
			throw new Exception('BingWebmasterAPI exception: Invalid params.');
		}

		$this->api_params = $params;
		return $this;

	}

	/**
	 * Performs the chained action
	 *
	 * @param array $curlopt
	 *
	 * @return array|bool|mixed|object|string
	 * @throws Exception
	 */
	public function perform($curlopt = array(
		CURLOPT_TIMEOUT => 30
	)) {

		$default_curlopt = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json; charset=utf-8'
			)
		);

		$curlopt = array(CURLOPT_URL => $this->api_url) + $curlopt + $default_curlopt;

		if($this->api_action === 'GET' && !empty($this->api_params)){

			$curlopt[CURLOPT_URL] .= '&' . http_build_query($this->api_params);


		} elseif ($this->api_action === 'POST' && !empty($this->api_params)){

			$curlopt[CURLOPT_POSTFIELDS] = json_encode($this->api_params);

		}

		$ch = curl_init();
		curl_setopt_array($ch, $curlopt);
		$response = curl_exec($ch);

		if ($response === false) {
			throw new Exception('BingWebmasterAPI exception: '.curl_error($ch));
		}

		curl_close($ch);

		$parsed_response = json_decode($response);
		if (json_last_error() === JSON_ERROR_NONE) {
			return $parsed_response;
		}

		return $response;

	}

}
