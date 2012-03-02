<?php

require_once('lib/sg-oauth.php');
require_once('models/OAuth.php');
require_once('models/SGOAuth.php');

function sgoauth_init() {
  extend_view('account/forms/login','sgoauth/login');
  register_page_handler('connect','sgoauth_page_handler');
}

function sgoauth_page_handler($page) {
  global $CONFIG;

  switch($page[0]) {
  case 'login':
  case 'callback':
    include($CONFIG->pluginspath . 'sgoauth/pages/' . $page[0] . '.php');
  }
}

register_elgg_event_handler('init','system','sgoauth_init');


?>