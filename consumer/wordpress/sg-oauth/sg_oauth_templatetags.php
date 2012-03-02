<?php


/**
 * Show the activated oauth logins
 *
 */
function sg_oauth_show_login(){
  $sg_oauth_server_name = trim(get_site_option('sg_server_name'));
  $sg_oauth_server_uri = trim(get_site_option('sg_server_uri'));
  $sg_oauth_consumer_key = trim(get_site_option('sg_oauth_consumer_key'));
  $sg_oauth_consumer_secret = trim(get_site_option('sg_oauth_consumer_secret'));
  if ($sg_oauth_server_name == '' ||
      $sg_oauth_server_uri == '' ||
      $sg_oauth_consumer_key == '' ||
      $sg_oauth_consumer_secret == '') {
    return false;
  }
  echo '<p class="sg_oauth_logins">';
  echo '<label>or login using<br />';
  echo '<a href="'.site_url().'/wp-load.php?action=sg_oauth&service=login">'.$sg_oauth_server_name.'</a>';
  echo '</label>';
  echo '</p>';
}
add_action('login_form', 'sg_oauth_show_login');
?>
