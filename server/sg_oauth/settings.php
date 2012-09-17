<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs this condition or there is error on login page
    $ADMIN->add('root', new admin_externalpage('sg_oauth_config', get_string('pluginname', 'local_sg_oauth'), $CFG->wwwroot . '/local/sg_oauth/config.php'));
}
?>
