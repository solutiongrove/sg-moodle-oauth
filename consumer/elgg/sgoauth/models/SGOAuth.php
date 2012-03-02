<?php

define( 'SG_OAUTH_CONSUMER_KEY',  sgoauth_get_parameter('oauth_consumer_key'));
define( 'SG_OAUTH_CONSUMER_SECRET',  sgoauth_get_parameter('oauth_consumer_secret'));
define( 'SG_OAUTH_SERVER_URI',  sgoauth_get_parameter('server_uri'));

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
  public $is_banned = false;

  function __construct() {
  }

  function create_user($username, $email = '', $password = false, $nickname = '', $oauth_userid = ''){

    // check if email is alredy in the system
    if ( $email != '' && get_user_by_email($email) )
      return false;

    if ( $email == '' || $oauth_userid == '')
      return false;

    // check if username is already taken and generate one as needed
    if ( get_user_by_username($username) ) {
      $append_ctr = 0;
      do {
        $append_ctr++;
        $username = $username . '-' . $append_ctr;
      }
      while ( get_user_by_username($username) );
    }

    if ( !$password )
      $password = generate_random_cleartext_password();

    if ($nickname == '')
      $nickname = $username;

    $newuser = new ElggUser();
    $newuser->email = $email;
    $newuser->access_id = ACCESS_PUBLIC;
    $newuser->subtype = 'sgoauth';
    $newuser->username = $username;
    $newuser->name = $nickname;
	$newuser->salt = generate_random_cleartext_password(); // Note salt generated before password!
	$newuser->password = generate_user_password($newuser, $password);
	$newuser->owner_guid = 0; // Users aren't owned by anyone, even if they are admin created.
	$newuser->container_guid = 0; // Users aren't contained by anyone, even if they are admin created.

    // login the user
    if ($newuser->save()) {

      $this->user_id = $newuser->getGUID();
      $this->user_email = $email;

      set_user_validation_status($newuser->getGUID(), TRUE, 'sgoauth');

      $newuser->sg_oauth_user_id = $oauth_userid;
      $newuser->save();

      // Turn on email notifications by default
      set_user_notification_setting($newuser->getGUID(), 'email', true);

      if( $this->login_user() )
        return true;

      return false;
    }

    return false;
  }

  function check_user($email = false, $custom_value = false){
    if(!$email && !$custom_value)
      return false;

    if($email)
      $user = get_user_by_email($email);

    if( $custom_value && is_array($custom_value) ){
      $metadata_name_value_pairs = array();
      foreach ($custom_value as $key => $value) {
        $metadata_name_value_pairs[] = array('name'=>$key,'operand'=>'=','value'=>$value);
      }
      if ($users = elgg_get_entities_from_metadata(array('metadata_name_value_pairs'=>$metadata_name_value_pairs,
                                                         'types'=>'user',
                                                         'subtypes'=>'sgoauth',
                                                         'limit'=>1))) {
        $user = $users[0];
      } else {
        $user = false;
      }
    }

    if($user){
      $this->user_id = $user->guid;
      $this->user_email = $user->email;
      // check if user is banned
      if (isset($user->banned) && $user->banned == 'yes') {
        $this->is_banned = true;
        return false;
      }

      return true;
    }

    return false;
  }

  function login_user(){

    if( $this->is_banned == true )
      return false;
    if( !$this->user_email && !$this->user_id )
      return false;

    $user = get_entity($this->user_id);
    login($user);

    return true;
  }

}

?>
