<?php

require_once('OAuth.php');

class SGOAuth {
  private $http_status;

  private $last_api_call;

  public static $TO_API_ROOT = SG_OAUTH_SERVER_URI;

  function requestTokenURL() { return self::$TO_API_ROOT.'/local/sg_oauth/request.php'; }
  function authorizeURL() { return self::$TO_API_ROOT.'/local/sg_oauth/authorize.php'; }
  function accessTokenURL() { return self::$TO_API_ROOT.'/local/sg_oauth/access.php'; }
  function restURL() { return self::$TO_API_ROOT.'/local/sg_oauth/rest.php'; }

  function lastStatusCode() { return $this->http_status; }
  function lastAPICall() { return $this->last_api_call; }

  function __construct($oauth_token = NULL, $oauth_token_secret = NULL) {
    $consumer_key = SG_OAUTH_CONSUMER_KEY;
    $consumer_secret = SG_OAUTH_CONSUMER_SECRET;
    $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);

    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuthToken($oauth_token, $oauth_token_secret);
    } else {
      $this->token = NULL;
    }
  }


  function getRequestToken($args=array()) {
    $r = $this->oAuthRequest($this->requestTokenURL(),$args);
    $token = $this->oAuthParseResponse($r);
    if (is_array($token) && array_key_exists('oauth_token',$token)) {
      $this->token = new OAuthToken($token['oauth_token'], $token['oauth_token_secret']);
      return $token;
    } else {
      throw new OAuthException($r);
    }
  }

  function oAuthParseResponse($responseString) {
    $r = array();
    foreach (explode('&', $responseString) as $param) {
      $pair = explode('=', $param, 2);
      if (count($pair) != 2) continue;
      $r[urldecode($pair[0])] = urldecode($pair[1]);
    }
    return $r;
  }

  function getAuthorizeURL($token) {
    if (is_array($token)) $token = $token['oauth_token'];
    return $this->authorizeURL() . '?oauth_token=' . $token;
  }

  function getAccessToken($token = NULL, $pin = NULL) {
    if ($pin)
      $r = $this->oAuthRequest(
        $this->accessTokenURL(),
        array("oauth_verifier" => $pin));
    else
      $r = $this->oAuthRequest($this->accessTokenURL());

    $token = $this->oAuthParseResponse($r);
    if (is_array($token) && array_key_exists('oauth_token',$token)) {
      $this->token = new OAuthToken($token['oauth_token'], $token['oauth_token_secret']);
      return $token;
    } else {
      throw new OAuthException($r);
    }
  }

  function getToken() {
    if ($this->token && $this->token->key != '' && $this->token->secret != '') {
      $token = array('oauth_token' => $this->token->key, 'oauth_token_secret' => $this->token->secret);
      return $token;
    } else {
      return NULL;
    }
  }

  function getUserInfo() {
    $token = $this->getToken();
    if ($token) {
      $args = array('oauth_consumer_key' => $this->consumer->key,
                    'oauth_token' => $token['oauth_token'],
                    'rest_service' => 'user.info');
      $r = $this->oAuthRequest($this->restURL(),$args,"POST");
      return json_decode($r);
    } else {
      return NULL;
    }
  }

  function oAuthRequest($url, $args = array(), $method = NULL) {/*{{{*/
    if (empty($method)) $method = empty($args) ? "GET" : "POST";
    $req = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $args);
    $req->sign_request($this->sha1_method, $this->consumer, $this->token);
    switch ($method) {
    case 'GET': return $this->http($req->to_url());
    case 'POST': return $this->http($req->get_normalized_http_url(), $req->to_postdata());
    }
  }

  function http($url, $post_data = null) {/*{{{*/
    $ch = curl_init();
    if (defined("CURL_CA_BUNDLE_PATH")) curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
	if( defined('WP_PROXY_HOST')){
		curl_setopt($ch, CURLOPT_PROXY, WP_PROXY_HOST);
	}
	if (defined('WP_PROXY_PORT')){
		curl_setopt($ch, CURLOPT_PROXYPORT, WP_PROXY_PORT);
	}
    //////////////////////////////////////////////////
    ///// Set to 1 to verify Twitter's SSL Cert //////
    //////////////////////////////////////////////////
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    if (isset($post_data)) {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    $response = curl_exec($ch);
    $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $this->last_api_call = $url;
    curl_close ($ch);
    return $response;
  }

}

Class SGOAuthUser {

  public $user_id;
  public $user_email;
  public $is_spammer = false;

  function __construct() {
  }

  function create_user($username, $email = '', $password = false, $firstname = false, $lastname = false, $nickname = false, $oauth_userid = '', $mu = false){

    // check if email is alredy in the system
    if ( $email != '' && get_user_by('email', $email) )
      return false;

    if ( $email == '' || $oauth_userid == '')
      return false;

    // check if username is already taken and generate one as needed
    if ( username_exists( sanitize_user($username, true ) ) ) {
      $append_ctr = 0;
      do {
        $append_ctr++;
        $username = $username . '-' . $append_ctr;
      }
      while ( username_exists( sanitize_user($username, true ) ) );
    }

    // generate a new password
    if ( !$password )
      $password = wp_generate_password ();

    $new_user_id = wp_create_user( sanitize_user($username, true), $password, $email);

    // login the user
    if ( $new_user_id ){
      if ($mu) {
        delete_user_option( $new_user_id, 'capabilities' );
        delete_user_option( $new_user_id, 'user_level' );
      }

      $this->user_id = $new_user_id;
      $this->user_email = $email;

      if ($firstname) update_usermeta($new_user_id, 'first_name', $firstname);
      if ($lastname) update_usermeta($new_user_id, 'last_name', $lastname);
      if ($nickname) update_usermeta($new_user_id, 'nickname', $nickname);
      update_usermeta($new_user_id, 'sg_oauth_user_id', $oauth_userid);

      // if(function_exists('wpmu_welcome_user_notification') && $this->user_email != '')
      // wpmu_welcome_user_notification($this->user_id, $password);

      if( $this->login_user() )
        return $new_user_id;

      return false;
    }

    return false;
  }

  function check_user($email = false, $username = false, $custom_value = false){
    if(!$email && $$username && !$custom_value)
      return false;

    if($email)
      $user = get_user_by('email', $email);

    if($username)
      $user = get_user_by('login', $username);

    if( $custom_value && is_array($custom_value) ){
      foreach ($custom_value as $key => $value) {
        if ( !$user = $this->get_user_by_meta_value($key,$value) )
          break;
      }
    }

    if($user){
      $this->user_id = $user->ID;
      $this->user_email = $user->user_email;
      // check if user is spammer
      if ( 1 == $user->spam){
        $this->is_spammer = true;
        // return new WP_Error('invalid_username', __('<strong>ERROR</strong>: Your account has been marked as a spammer.'));
        return false;
      }

      return $this->login_user();
    }

    return false;
  }

  function login_user(){

    if( $this->is_spammer == true )
      // return new WP_Error('invalid_username', __('<strong>ERROR</strong>: Your account has been marked as a spammer.'));
      return false;
    if( !$this->user_email && !$this->user_id )
      return false;

    wp_set_current_user( $this->user_id );
    wp_set_auth_cookie( $this->user_id, true );
    return true;
  }

  function get_user_by_meta_value($key, $value){
    global $wpdb;

    $id = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->usermeta} WHERE meta_key = '{$key}' AND meta_value = '{$value}';") );

    if(!$id[0])
      return false;

    $user = get_user_by('id', $id[0]->user_id);

    if($user)
      return $user;

    return false;

  }
}

?>
