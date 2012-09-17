<?php

require_once('../../config.php');
require_once('lib.php');

try {

    $server = sg_oauth_get_server();
    $req = OAuthRequest::from_request();
    $api_result = $server->verify_request($req);

    $retval = sg_oauth_execute_rest($api_result[0], $api_result[1], $req->get_parameter('rest_service'));
    print json_encode($retval);
} catch (OAuthException $e) {
    print($e->getMessage());
}
