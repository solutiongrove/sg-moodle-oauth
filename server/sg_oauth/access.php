<?php

require_once('../../config.php');
require_once('lib.php');

try {
  $server = oauth_get_server();
  $req = OAuthRequest::from_request();
  $token = $server->fetch_access_token($req);

  // save the nonce
  $consumerKey = $req->get_parameter('oauth_consumer_key');
  $tokenKey = $req->get_parameter('oauth_token');
  $nonce = $req->get_parameter('oauth_nonce');

  // save our nonce for later checking
  oauth_save_nonce($consumerKey, $nonce, $tokenKey);

  print $token;
} catch (OAuthException $e) {
  print($e->getMessage());
}
