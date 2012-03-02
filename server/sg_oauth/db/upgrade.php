<?php

function xmldb_local_sg_oauth_upgrade($oldversion) {

  global $CFG, $DB, $OUTPUT;

  $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

  if ($oldversion < 2012022700) {

    /// Define field messagetrust to be added to forum_posts
    $table = new xmldb_table('oauth_consumers');
    $field = new xmldb_field('autoauthorize', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '0', 'consumerkey');

    /// Launch add field messagetrust
    $dbman->add_field($table, $field);

    /// forum savepoint reached
    upgrade_plugin_savepoint(true, 2012022700, 'local', 'sg_oauth');
  }

  return true;

}

?>