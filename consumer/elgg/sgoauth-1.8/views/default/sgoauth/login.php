<?php

global $CONFIG;

$login_url = $CONFIG->wwwroot.'connect/login';

$is_configured = (sgoauth_get_parameter('oauth_consumer_key') != '' &&
                  sgoauth_get_parameter('oauth_consumer_secret') != '' &&
                  sgoauth_get_parameter('server_uri') != '');

if ($is_configured) {

$peer_name = sgoauth_get_parameter('peer_name');
if (empty($peer_name))
  $peer_name = sgoauth_get_parameter('server_uri');

?>

<p>Sign in through one of these peers:
<ul>
   <li><a href="<?php echo $login_url; ?>"><?php echo $peer_name; ?></a></li>
</ul>
</p>

<?php
}
?>