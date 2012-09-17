<?php
require_once('../../config.php');
require_once('lib.php');

$token = required_param('oauth_token', PARAM_ALPHANUM);
$callback = optional_param('oauth_callback', '', PARAM_ALPHANUM);

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));

require_login(NULL, false);

$PAGE->set_url('/local/sg_oauth/authorize.php');
$PAGE->set_pagelayout('course');
$PAGE->navbar->add("Authorize Request");
$PAGE->set_title("Authorize Request ");
$PAGE->set_heading("Authorize Request ");

$tokEnt = sg_oauth_lookup_token_entity($token, 'request');

if ($tokEnt) {
    $consumEnt = sg_oauth_lookup_consumer_entity($tokEnt->consumerkey);
    if (isset($_POST['submitbutton'])) {
        if ($_POST['submitbutton'] == get_string('cancel', 'local_sg_oauth')) {
            if ($consumEnt->cancelurl) {
                redirect($consumEnt->cancelurl);
            } elseif ($tokEnt->callbackurl) {
                redirect($tokEnt->callbackurl);
            } else {
                redirect($consumEnt->callbackurl);
            }
        }
        if ($_POST['submitbutton'] == get_string('allow', 'local_sg_oauth')) {
            if ($validated_token = sg_oauth_save_validated_token($tokEnt)) {
                $url = $tokEnt->callbackurl;
                if (empty($url)) {
                    $url = $consumEnt->callbackurl;
                }

                // Pick the correct separator to use
                $separator = "?";
                if (strpos($url, "?") !== false) {
                    $separator = "&";
                }

                // Find the location for the new parameter
                $insertPosition = strlen($url);
                if (strpos($url, "#") !== false) {
                    $insertPosition = strpos($url, "#");
                }

                // Build the new url
                $newUrl = substr_replace($url, $separator . 'oauth_verifier=' . $validated_token->verifier . '&oauth_token=' . $tokEnt->token, $insertPosition, 0);

                redirect($newUrl);
            }
        }
    }
    GLOBAL $USER;
    if ($accEnt = sg_oauth_lookup_access_token_entity($consumEnt->consumerkey, $USER->id)) {
        sg_oauth_refresh_token_lastcheckedon($accEnt);
        $url = $tokEnt->callbackurl;
        if (empty($url)) {
            $url = $consumEnt->callbackurl;
        }

        // Pick the correct separator to use
        $separator = "?";
        if (strpos($url, "?") !== false) {
            $separator = "&";
        }

        // Find the location for the new parameter
        $insertPosition = strlen($url);
        if (strpos($url, "#") !== false) {
            $insertPosition = strpos($url, "#");
        }

        // Build the new url
        $newUrl = substr_replace($url, $separator . 'oauth_token_secret=' . $accEnt->secret . '&oauth_token=' . $accEnt->token, $insertPosition, 0);

        redirect($newUrl);
    }

    if ($tokEnt->userid == 0) {
        $consumEnt = sg_oauth_lookup_consumer_entity($tokEnt->consumerkey);
        echo $OUTPUT->header();
        if ($consumEnt->autoauthorize == 0) {
            ?>
            <h3><?php print_string('authorize', 'local_sg_oauth', $consumEnt->name); ?></h3>
            <div><?php print_string('authorizequestion', 'local_sg_oauth', $consumEnt->name); ?></div>
            <br>
            <div>
                <form id="authorize" name="authorize" method="post">
                    <input id="submitbutton" name="submitbutton" type="submit" value="<?php print_string('allow', 'local_sg_oauth'); ?>" />
                    <input id="submitbutton" name="submitbutton" type="submit" value="<?php print_string('cancel', 'local_sg_oauth'); ?>" />
                </form>
            </div>
            <?php
        } else {
            ?>
            <h3>Authorizing...</h3>
            <div>
                <form id="authorize" name="authorize" method="post">
                    <input id="submitbutton" name="submitbutton" type="hidden" value="<?php print_string('allow', 'local_sg_oauth'); ?>" />
                </form>
                <script type="text/javascript">
                    document.authorize.submit();
                </script>
            </div>
            <?php
        }
    } else {

    }
} else {
    echo $OUTPUT->header();

    echo print_string('invalidtoken', 'local_sg_oauth');
}
echo $OUTPUT->footer();
?>