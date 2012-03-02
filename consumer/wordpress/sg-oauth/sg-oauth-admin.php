<?php

function sg_oauth_admin_settings() {
  global $wpdb;

  $create_site = get_site_option('sg_oauth_create_site');
  if ($create_site != 'true') $create_site = 'false';

  if ( isset( $_POST['sg-oauth-admin-submit'] ) && check_admin_referer('sg-oauth-admin') ) {

    if( isset($_POST['sg_oauth_consumer_key']) ) update_site_option( 'sg_oauth_consumer_key', $_POST['sg_oauth_consumer_key'] );
    if( isset($_POST['sg_oauth_consumer_secret'])) update_site_option( 'sg_oauth_consumer_secret', $_POST['sg_oauth_consumer_secret'] );
    if( isset($_POST['sg_server_uri'])) update_site_option( 'sg_server_uri', $_POST['sg_server_uri'] );
    if( isset($_POST['sg_server_name'])) update_site_option( 'sg_server_name', $_POST['sg_server_name'] );
    if (is_multisite()) {
      if( isset($_POST['sg_oauth_create_site'])) {
        update_site_option( 'sg_oauth_create_site', 'true' );
      } else {
        update_site_option( 'sg_oauth_create_site', 'false' );
      }
    }
    $create_site = get_site_option('sg_oauth_create_site');

?>
    <div id="message" class="updated fade">
       <p style="line-height: 150%">Saved SG OAuth settings successfully</p>
       </div>
<?php
  }
?>

	<div class="wrap">

       <h2><?php _e( 'SG OAuth Settings' ) ?></h2>

       <?php if ( isset( $_POST['bp-admin'] ) ) : ?>
       <div id="message" class="updated fade">
          <p><?php _e( 'Settings Saved' ) ?></p>
       </div>
       <?php endif; ?>

       <form action="" method="post" id="">

       <?php do_action( 'sg_oauth_admin_screen' ) ?>

          <div class="widefat">
          <div style="padding:10px;">
          <p>
            <label>Consumer Key</label><br /><input type="text" size="20" name="sg_oauth_consumer_key" value="<?php if(get_site_option('sg_oauth_consumer_key')) echo get_site_option('sg_oauth_consumer_key'); ?>" />
          </p>
          <p>
          <label>Secret</label><br /><input type="text" size="20" name="sg_oauth_consumer_secret" value="<?php if(get_site_option('sg_oauth_consumer_secret')) echo get_site_option('sg_oauth_consumer_secret'); ?>" />
          </p>
          <p>
          <label>OAuth Server Base URL</label><br /><input type="text" size="50" name="sg_server_uri" value="<?php if(get_site_option('sg_server_uri')) echo get_site_option('sg_server_uri'); ?>" />
          </p>
          <p>
          <label>OAuth Server Name</label><br /><input type="text" size="50" name="sg_server_name" value="<?php if(get_site_option('sg_server_name')) echo get_site_option('sg_server_name'); ?>" />
          </p>
<?php
          if (is_multisite()) {
?>
          <p>
          <label><input name="sg_oauth_create_site" value="true" type="checkbox" <?php if ( $create_site == 'true' ) echo 'checked="checked" '; ?> /> Automatically create blogs for new users using OAuth?</label>
          </p>
<?php
          } else {
?>
          <p><input type="hidden" name="sg_oauth_create_site" value="<?php echo $create_site; ?>" /></p>
<?php
          }
?>
          </div>
          </div>

          <p class="submit">
          <input class="button-primary" type="submit" name="sg-oauth-admin-submit" id="sg-oauth-admin-submit" value="<?php _e( 'Save Settings' ) ?>"/>
          </p>

          <?php wp_nonce_field( 'sg-oauth-admin' ) ?>

        </form>

	</div>

<?php
}