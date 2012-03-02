<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . "/engine/start.php");

global $CONFIG;

if (isloggedin()) forward();

$area2 = "";
$title = "Requesting login token";
$area2 .= elgg_view_title($title, false);

try {

  $oauth = new SGOAuth();
  $args = array();
  $args['oauth_callback'] = $CONFIG->wwwroot.'pg/connect/callback/';
  $token = $oauth->getRequestToken($args);
  sgoauth_save_session($token);

  forward($oauth->getAuthorizeURL($token['oauth_token']));

} catch (OAuthException $e) {

  $area2 .= 'Error encountered when trying to fetch token:<br /><font color="red">'.$e->getMessage().'</font>';

}


$body = elgg_view_layout('two_column_left_sidebar', $area1, $area2);

// Draw it
page_draw($title,$body);

?>