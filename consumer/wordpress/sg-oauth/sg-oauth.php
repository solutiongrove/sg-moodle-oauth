<?php
/**
 *  @version 0.1
 *  @author Deds Castillo <dedsoralive@gmail.com>
 *
 *   sg-oauth is a wordpress plugin that connects to an oauth server
 *   in order to login or create user accounts
 *   Copyright (C) 2011  Deds Castillo
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 */

/*
Plugin Name: SG OAuth
Plugin URI: http://solutiongrove.com
Description: This plugin allows users to login or register using credentials from an oauth server
Author: Deds Castillo
Version: 0.2
Author URI: http://solutiongrove.com
Site Wide Only: true
Network: true
Revision Date: July 21, 2011
Requires at least: WP 3.0.5
Tested up to: WP 3.1.4
*/

define( 'SG_OAUTH_VERSION', '0.1' );
define( 'SG_OAUTH_PLUGIN_DIR', WP_PLUGIN_DIR . '/sg-oauth' );
define( 'SG_OAUTH_PLUGIN_URL', plugins_url( $path = '/sg-oauth' ) );

define( 'SG_OAUTH_CONSUMER_KEY',  sg_oauth_get_parameter('oauth_consumer_key'));
define( 'SG_OAUTH_CONSUMER_SECRET',  sg_oauth_get_parameter('oauth_consumer_secret'));
define( 'SG_OAUTH_SERVER_URI',  sg_oauth_get_parameter('server_uri'));


/**
 * include oauth libraries
 *
 */
include_once "lib/OAuth.php";

/**
 * include base class for the plugin
 *
 */
include_once "lib/SGOAuth.php";

include_once 'sg_oauth_templatetags.php';

// on ajax request the register functions from WP are not included
if( !function_exists('wp_create_user') )
	require_once( ABSPATH . WPINC . '/registration.php');


/**
 * Add the admin settings option
 *
 */
function sg_oauth_add_admin_menu() {
  if ( !is_super_admin() ) {
    return false;
  }

  require_once dirname(__FILE__) . '/sg-oauth-admin.php';
  add_options_page('SG OAuth', 'SG OAuth Settings', 8, 'sg-oauth', 'sg_oauth_admin_settings');
}
add_action( 'admin_menu', 'sg_oauth_add_admin_menu' );

/**
 * Add css style if user is not logged in and should see SG OAuth
 *
 */
function sg_oauth_add_css() {
  if( is_user_logged_in() )
    return;

  wp_enqueue_style( 'sg-oauth-css', SG_OAUTH_PLUGIN_URL . '/css/sg-oauth.css' );
}
add_action('wp', 'sg_oauth_add_css');

/**
 * Show css on wp-login.php
 *
 */
function sg_oauth_add_login_css(){
	echo "<link rel='stylesheet' href='" . esc_url( SG_OAUTH_PLUGIN_URL . '/css/sg-oauth.css' ) . "' type='text/css' />";
}
add_action('login_head', 'sg_oauth_add_login_css');

function sg_oauth_get_parameter($name){
  switch ($name){
  case 'oauth_consumer_key':
    $parameter_value = get_site_option('sg_oauth_consumer_key');
    break;
  case 'oauth_consumer_secret':
    $parameter_value = get_site_option('sg_oauth_consumer_secret');
    break;
  case 'server_uri':
    $parameter_value = get_site_option('sg_server_uri');
    break;
  default:
    $parameter_value = '';
  }
  return $parameter_value;
}

function sg_oauth_save_session($token,$access_token=false) {
  if ($token['oauth_token'] != '' && $token['oauth_token_secret']) {
    if ($access_token) {
      unset($_SESSION['sg_oauth']['oauth_token']);
      unset($_SESSION['sg_oauth']['oauth_token_secret']);
      $_SESSION['sg_oauth']['oauth_token_access'] = $token['oauth_token'];
      $_SESSION['sg_oauth']['oauth_token_secret_access'] = $token['oauth_token_secret'];
    } else {
      unset($_SESSION['sg_oauth']['oauth_token_access']);
      unset($_SESSION['sg_oauth']['oauth_token_secret_access']);
      $_SESSION['sg_oauth']['oauth_token'] = $token['oauth_token'];
      $_SESSION['sg_oauth']['oauth_token_secret'] = $token['oauth_token_secret'];
    }
  } else {
    // clear token?
    unset($_SESSION['sg_oauth']);
  }
}

function sg_oauth_get_session() {
  if (isset($_SESSION['sg_oauth'])) {
    return $_SESSION['sg_oauth'];
  } else {
    return false;
  }
}

function sg_oauth_create_or_login_user($user) {
  $content = '';
  if ( isset($user->id) && isset($user->email) && isset($user->firstname) && isset($user->lastname) && isset($user->username) ) {
    $create_user = new SGOAuthUser();
    if ( $create_user->check_user(false, false, array('sg_oauth_user_id' => $user->id)) ) {
      $create_user->login_user();
    } elseif ( $create_user->check_user($user->email) ) {
      $content .= "The email address (".$user->email.") from the information the system fetched is already present in this system.  Please login using your username and password instead.<br /><br />";
    } elseif ( $create_user->check_user(false, $user->username) ) {
      error_log("MATCHED THE USERNAME");
      $content .= "The username (".$user->username.") from the information the system fetched is already present in this system.  Please login using your username and password instead.<br /><br />";
    } else {
      if (is_multisite()) {
        global $wpdb, $domain, $current_site, $base;
        $create_blog = (get_site_option('sg_oauth_create_site') == 'true');
        if ($create_blog) {
          $domain = $user->username;
          if ( ! is_subdomain_install() ) {
            $subdirectory_reserved_names = apply_filters( 'subdirectory_reserved_names', array( 'page', 'comments', 'blog', 'files', 'feed' ) );
            if ( in_array( $domain, $subdirectory_reserved_names ) )
              $content .= sprintf( __('The following words are reserved for use by WordPress functions and cannot be used as blog names: <code>%s</code>' ), implode( '</code>, <code>', $subdirectory_reserved_names ) );
          }

          if ( is_subdomain_install() ) {
			$newdomain = $domain . '.' . preg_replace( '|^www\.|', '', $current_site->domain );
			$path = $base;
          } else {
			$newdomain = $current_site->domain;
			$path = $base . $domain . '/';
          }
        }

        $new_user_id = $create_user->create_user($user->username, $user->email, false, $user->firstname, $user->lastname, "{$user->firstname} {$user->lastname}", $user->id, $create_blog);

        if ( $new_user_id &&$create_blog && is_user_logged_in() ) {
          # $portfolio_title = "Portfolio for ".$user->username;
          $portfolio_title = "{$user->firstname} {$user->lastname}";
          $wpdb->hide_errors();
          $blog_id = wpmu_create_blog( $newdomain, $path, $portfolio_title, $new_user_id , array( 'public' => 1 ), $current_site->id );
          $wpdb->show_errors();
          if ( is_wp_error( $blog_id ) ) {
            wp_logout();
            $content .= $blog_id->get_error_message();
          } else {
            update_blog_option($blog_id,'blogdescription',"CCC Portfolio Site for {$user->firstname} {$user->lastname}");
          }
        }
      } else {
        $create_user->create_user($user->username, $user->email, false, $user->firstname, $user->lastname, "{$user->firstname} {$user->lastname}", $user->id);
      }
    }

    if ( is_user_logged_in() ) {
      wp_redirect(site_url().'/wp-login.php');
    } else {
      $content .= "The system encountered a problem while logging you in.";
    }
  } else {
    $content .= "The system encountered an error while fetching your profile.";
  }
  return $content;
}

function sg_oauth_display_page($content) {
  require(dirname(dirname(dirname(dirname(__FILE__)))).'/wp-blog-header.php');
  get_header();
  echo '<div class="widecolumn">';
  echo '<div>';
  echo $content;
  echo '</div>';
  echo '<div>';
  echo '<br /><a href="'.site_url().'">Home</a> | <a href="'.site_url().'/wp-login.php">Login</a>';
  echo '</div>';
  echo '</div>';
  get_footer();
}

function sg_oauth_init(){
  session_start();

  $page_content = "";

  if( isset($_GET['action']) && $_GET['action'] == 'sg_oauth' ){
    if( isset($_GET['service']) ){

      // login start
      if( $_GET['service'] == 'login' ){

        if( is_user_logged_in() )
          wp_logout();

        try {

          $oauth = new SGOAuth();
          $args = array();
          $args['oauth_callback'] = site_url().'/wp-load.php?'.http_build_query(array('action'=>'sg_oauth',
                                                                                      'service'=>'logincallback'),
                                                                                null,
                                                                                '&');
          $token = $oauth->getRequestToken($args);
          sg_oauth_save_session($token);

          wp_redirect($oauth->getAuthorizeURL($token['oauth_token']));

        } catch (OAuthException $e) {

          $page_content .= 'Error encountered when trying to fetch token:<br /><font color="red">'.$e->getMessage().'</font>';

        }
      }
      // login end

      // logincallback start
      if( $_GET['service'] == 'logincallback' ){

        // exchange request token with access token
        try {

          $oauth_token = $_GET['oauth_token'];
          $oauth_verifier = $_GET['oauth_verifier'];
          $oauth_token_secret = $_GET['oauth_token_secret'];
          $success = false;

          if ($oauth_verifier != '') {
            $session = sg_oauth_get_session();
            if ($session && $session['oauth_token'] != '' && $session['oauth_token_secret'] != '') {
              $oauth = new SGOAuth($session['oauth_token'],$session['oauth_token_secret']);
              $token = $oauth->getAccessToken($oauth, $oauth_verifier);
              $success = true;
            } else {
              // no session present
              $page_content .= 'Error verifying login token';
            }
          } elseif ($oauth_token_secret != '') {
            $oauth = new SGOAuth($oauth_token,$oauth_token_secret);
            $token = $oauth->getToken();
            $success = true;
          }

          if ($success) {
            sg_oauth_save_session($token,true);
            if ($userinfo = $oauth->getUserInfo()) {
              $page_content .= sg_oauth_create_or_login_user($userinfo);
            } else {
              $page_content .= 'Error encountered when trying to fetch user information';
            }
          } else {
            $page_content .= 'Error encountered when trying to fetch token';
          }

        } catch (OAuthException $e) {

          $page_content .= 'Error encountered when trying to fetch token:<br /><font color="red">'.$e->getMessage().'</font>';

        }

      }
      // logincallback end

      // show non-sso users start
      if( $_GET['service'] == 'view_non_sso' ){

        if( !is_super_admin() )
          return;

        global $wpdb;

        $sort= "user_registered";

        //Build the custom database query to fetch all user IDs
        $all_users_id = $wpdb->get_col( $wpdb->prepare(
                                                       "SELECT $wpdb->users.ID FROM $wpdb->users ORDER BY %s ASC"
                                                       , $sort ));
        foreach ($all_users_id as $i_user_id) {
          $user = get_userdata($i_user_id);
          $sg_oauth_user_id = get_usermeta($i_user_id,'sg_oauth_user_id',true);
          if ($sg_oauth_user_id == '') {
            $page_content .= $user->ID.' | '.$user->user_login.' | '.$user->user_email.' | '.$sg_oauth_user_id.'<br />';
          }
        }
      }
      // show non-sso users end

      if (!empty($page_content)) {
        sg_oauth_display_page($page_content);
      }

    }
  }
}
add_action('init', 'sg_oauth_init');

?>
