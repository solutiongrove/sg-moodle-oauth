<?php

require_once('lib/sg-oauth.php');
require_once('models/OAuth.php');
require_once('models/SGOAuth.php');

function sgoauth_init() {
  elgg_extend_view('forms/login','sgoauth/login');
  elgg_register_page_handler('connect','sgoauth_page_handler');
}

function sgoauth_page_handler($page) {
  global $CONFIG;

  $base_dir = elgg_get_plugins_path() . 'sgoauth/pages/sgoauth/';

  switch($page[0]) {
    case 'login':
    case 'callback':
      include($base_dir . $page[0] . '.php');
  }
}

elgg_register_event_handler('init','system','sgoauth_init');


?>