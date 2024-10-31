<?php

/**
 * rateit.cool PHP inetrface for api.rateit.cool
 *
 * @author Thomas Gravel <thomas.gravel@rateit.cool>
 */
class RateItCool {

  const VERSION = '0.0.1';
  const TIMEOUT = 5;
  protected static $username, $apikey, $serverapikey, $base_uri = 'https://api.rateit.cool';
  //protected static $username, $apikey, $serverapikey, $base_uri = 'http://localhost:8080';
  protected $request;

  public function __construct($settings) {
    $this->set_username($settings['username']);
    $this->set_serverapikey($settings['serverapikey']);
    $this->set_apikey($settings['apikey']);
  }

	protected function set_request_method($method, $vars) {
      switch (strtoupper($method)) {
        case 'HEAD':
            curl_setopt($this->request, CURLOPT_NOBODY, true);
            break;
        case 'GET':
            curl_setopt($this->request, CURLOPT_HTTPGET, true);
            break;
        case 'POST':
        	curl_setopt($this->request, CURLOPT_POSTFIELDS, $vars);
        	curl_setopt($this->request, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-length: '.strlen($vars)));
            curl_setopt($this->request, CURLOPT_POST, true);
            break;
        default:
            curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, $method);
      }
    }

	protected function set_request_options($url) {
    curl_setopt($this->request, CURLOPT_URL, $url);

    $headers = array(
        'Content-Type:application/json',
        'X-Api-Version: 1.0.0',
        'X-Api-Key: ' . self::$apikey,
        'Origin: localhost',
        'Authorization: Basic '. base64_encode(self::$username . ':' . self::$serverapikey)
    );

    # Set some default CURL options
    curl_setopt($this->request, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($this->request, CURLOPT_RETURNTRANSFER, true);

    //curl_setopt($this->request, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($this->request, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($this->request, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($this->request, CURLOPT_CONNECTTIMEOUT ,self::TIMEOUT);
  }

  function request($method, $url, $vars = array()) {
  	if (!empty($vars)) $vars = self::clean_array($vars);
      $url = self::$base_uri . $url;
      $this->error = '';
      $this->request = curl_init();
      if (is_array($vars)) {
      	if($method == 'POST') {
      		$vars = json_encode($vars);
      	}
      	else {
      		$vars = http_build_query($vars, '', '&');
      	}
      }

      $this->set_request_method($method, $vars);
      $this->set_request_options($url);
      $response = curl_exec($this->request);

      curl_close($this->request);

      return self::process_response($response);
  }

  protected function get($url, $vars = array()) {
    if (!empty($vars)) {
      $url .= (stripos($url, '?') !== false) ? '&' : '?';
      $url .= (is_string($vars)) ? $vars : http_build_query($vars, '', '&');
    }
    return $this->request('GET', $url);
  }

  protected function delete($url, $vars = array()) {
    return $this->request('DELETE', $url, $vars);
  }

  protected function post($url, $vars = array()) {
    return $this->request('POST', $url, $vars);
  }

  protected function put($url, $vars = array()) {
    return $this->request('PUT', $url, $vars);
  }

  protected static function process_response($response) {
    if ($response != NULL) {
      try {
        $response = json_decode($response, true);
      } catch (Exception $e) {
      }
    }
	  return $response;
  }


  public function getReviews($product) {
      return $this->get('/feedback/' . $product['gpntype'] . '/' . $product['gpnvalue'] . '/' . $product['language'], array('limit' => 5));
  }

  public function set_username($username) {
    if ($username != null) {
      self::$username = $username;
    }
  }

  public function set_serverapikey($serverapikey) {
    if ($serverapikey != null) {
      self::$serverapikey = $serverapikey;
    }
  }

  public function set_apikey($apikey) {
    if ($apikey != null) {
      self::$apikey = $apikey;
    }
  }

  protected static function build_request(array $params, array $request_params) {
    $request = array();
    foreach ($params as $key => $value) {
      if (array_key_exists($key, $request_params)) {
        $request[$value] = $request_params[$key];
      }
    }
    return $request;
  }

  protected static function clean_array(array $array){

    foreach( $array as $key => $value ) {
      if( is_array( $value ) ) {
        foreach( $value as $key2 => $value2 ) {
          if( empty( $value2 ) )
            unset( $array[ $key ][ $key2 ] );
        }
      }
      if( empty( $array[ $key ] ) )
        unset( $array[ $key ] );
    }
    return $array;
  }

}

?>
