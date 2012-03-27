<?php

/* List of handlers */
$handlers = array (
    'user_logout' => array (
        'handlerfile'      => '/local/sg_oauth/lib.php',
        'handlerfunction'  => 'oauth_user_clear_lastchecked',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
);
