<?php

global $CONFIG;

if (elgg_is_logged_in()) forward();

$area2 = "";
$title = "Requesting login token";
$area2 .= elgg_view_title($title);

try {

  $oauth = new SGOAuth();
  $args = array();
  $args['oauth_callback'] = $CONFIG->wwwroot.'connect/callback/';
  $token = $oauth->getRequestToken($args);
  sgoauth_save_session($token);

  forward($oauth->getAuthorizeURL($token['oauth_token']));

} catch (OAuthException $e) {

  $area2 .= 'Error encountered when trying to fetch token:<br /><font color="red">'.$e->getMessage().'</font>';

}


$body = elgg_view_layout('two_column_left_sidebar', array('area1' => $area1, 'area2' => $area2));

// Draw it
echo elgg_view_page($title, $body);

?>