<p>
  <?php
    echo elgg_echo('Peer Name');
    echo "<br /><input type='text' size='60' name='params[sgoauth_peer_name]' value='".$vars['entity']->sgoauth_peer_name."' />";
    echo "<br />";
    echo "<br />";

    echo elgg_echo('Consumer Key');
    echo "<br /><input type='text' size='30' name='params[sgoauth_consumer_key]' value='".$vars['entity']->sgoauth_consumer_key."' />";
    echo "<br />";
    echo "<br />";

    echo elgg_echo('Consumer Secret');
    echo "<br /><input type='text' size='30' name='params[sgoauth_consumer_secret]' value='".$vars['entity']->sgoauth_consumer_secret."' />";
    echo "<br />";
    echo "<br />";

    echo elgg_echo('Server Base URL');
    echo "<br /><input type='text' size='80' name='params[sgoauth_server_uri]' value='".$vars['entity']->sgoauth_server_uri."' />";
    echo "<br />";
    echo "<br />";

  ?>
</p>
