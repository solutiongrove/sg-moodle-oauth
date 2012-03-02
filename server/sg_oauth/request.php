<?php

require_once('../../config.php');
require_once('lib.php');

try {

	$server = oauth_get_server();
	$req = OAuthRequest::from_request();
	$token = $server->fetch_request_token($req);

	$consumEnt = oauth_lookup_consumer_entity($req->get_parameter('oauth_consumer_key'));

	// save the nonce
	$consumerKey = $req->get_parameter('oauth_consumer_key');
	$nonce = $req->get_parameter('oauth_nonce');

	// save our nonce for later checking
	oauth_save_nonce($consumerKey, $nonce);

	print $token;
} catch (OAuthException $e) {
	print($e->getMessage());
}
