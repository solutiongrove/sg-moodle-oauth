<?php

function sgoauth_get_parameter($name){
  switch ($name){
  case 'oauth_consumer_key':
    $parameter_value = get_plugin_setting('sgoauth_consumer_key','sgoauth');
    break;
  case 'oauth_consumer_secret':
    $parameter_value = get_plugin_setting('sgoauth_consumer_secret','sgoauth');
    break;
  case 'server_uri':
    $parameter_value = get_plugin_setting('sgoauth_server_uri','sgoauth');
    break;
  case 'peer_name':
    $parameter_value = get_plugin_setting('sgoauth_peer_name','sgoauth');
    break;
  default:
    $parameter_value = '';
  }
  return $parameter_value;
}

function sgoauth_save_session($token,$access_token=false) {
  if ($token['oauth_token'] != '' && $token['oauth_token_secret']) {
    if ($access_token) {
      unset($_SESSION['sgoauth']['oauth_token']);
      unset($_SESSION['sgoauth']['oauth_token_secret']);
      $_SESSION['sgoauth']['oauth_token_access'] = $token['oauth_token'];
      $_SESSION['sgoauth']['oauth_token_secret_access'] = $token['oauth_token_secret'];
    } else {
      unset($_SESSION['sgoauth']['oauth_token_access']);
      unset($_SESSION['sgoauth']['oauth_token_secret_access']);
      $_SESSION['sgoauth']['oauth_token'] = $token['oauth_token'];
      $_SESSION['sgoauth']['oauth_token_secret'] = $token['oauth_token_secret'];
    }
  } else {
    // clear token?
    unset($_SESSION['sgoauth']);
  }
}

function sgoauth_get_session() {
  if (isset($_SESSION['sgoauth'])) {
    return $_SESSION['sgoauth'];
  } else {
    return false;
  }
}

function sgoauth_create_or_login_user($user) {
  $content = '';
  if ( isset($user->id) && isset($user->email) && isset($user->firstname) && isset($user->lastname) && isset($user->username) ) {
    $create_user = new SGOAuthUser();
    if ( $create_user->check_user(false, array('sg_oauth_user_id' => $user->id)) ) {
      $create_user->login_user();
    } elseif ( $create_user->check_user($user->email) ) {
      $content .= "The email address (".$user->email.") from the information the system fetched is already present in this system.  Please login using your username and password instead.<br /><br />";
    } else {
      $create_user->create_user($user->username, $user->email, false, "{$user->firstname} {$user->lastname}", $user->id);
    }

    if ( isloggedin() ) {
      forward();
    } else {
      $content .= "The system encountered a problem while logging you in.";
    }
  } else {
    $content .= "The system encountered an error while fetching your profile.";
  }
  return $content;
}

?>