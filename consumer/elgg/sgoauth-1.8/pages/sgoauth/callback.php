<?php

global $CONFIG;

$area2 = "";
$title = "Login authorization";
$area2 .= elgg_view_title($title);

// exchange request token with access token
try {

  $oauth_token = $_GET['oauth_token'];
  $oauth_verifier = $_GET['oauth_verifier'];
  $oauth_token_secret = $_GET['oauth_token_secret'];
  $success = false;

  if ($oauth_verifier != '') {
    $session = sgoauth_get_session();
    if ($session && $session['oauth_token'] != '' && $session['oauth_token_secret'] != '') {
      $oauth = new SGOAuth($session['oauth_token'],$session['oauth_token_secret']);
      $token = $oauth->getAccessToken($oauth, $oauth_verifier);
      $success = true;
    } else {
      // no session present
      $area2 .= 'Error verifying login token';
    }
  } elseif ($oauth_token_secret != '') {
    $oauth = new SGOAuth($oauth_token,$oauth_token_secret);
    $token = $oauth->getToken();
    $success = true;
  }

  if ($success) {
    sgoauth_save_session($token,true);
    if ($userinfo = $oauth->getUserInfo()) {
      $area2 .= sgoauth_create_or_login_user($userinfo);
    } else {
      $area2 .= 'Error encountered when trying to fetch user information';
    }
  } else {
    $area2 .= 'Error encountered when trying to fetch token';
  }

} catch (OAuthException $e) {

  $area2 .= 'Error encountered when trying to fetch token:<br /><font color="red">'.$e->getMessage().'</font>';

}

$body = elgg_view_layout('two_column_left_sidebar', array('area1' => $area1, 'area2' => $area2));

// Draw it
echo elgg_view_page($title, $body);

?>