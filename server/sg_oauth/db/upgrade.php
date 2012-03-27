<?php

function xmldb_local_sg_oauth_upgrade($oldversion) {

  global $CFG, $DB, $OUTPUT;

  $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

  if ($oldversion < 2012022700) {

    /// Define field autoauthorize to be added to oauth_consumers
    $table = new xmldb_table('oauth_consumers');
    $field = new xmldb_field('autoauthorize', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '0', 'consumerkey');

    /// Launch add field autoauthorize
    $dbman->add_field($table, $field);

    /// savepoint reached
    upgrade_plugin_savepoint(true, 2012022700, 'local', 'sg_oauth');
  }

  if ($oldversion < 2012032700) {

    /// Define field lastcheckedon to be added to oauth_tokens
    $table = new xmldb_table('oauth_tokens');
    $field = new xmldb_field('lastcheckedon', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, '0', 'verifier');

    /// Launch add field lastcheckedon
    $dbman->add_field($table, $field);

    /// savepoint reached
    upgrade_plugin_savepoint(true, 2012032700, 'local', 'sg_oauth');
  }

  return true;

}

?>