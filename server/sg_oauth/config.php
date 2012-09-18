<?php

global $CFG, $PAGE, $DB, $OUTPUT;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

admin_externalpage_setup('sg_oauth_config');

$PAGE->set_heading(get_string('configheader', 'local_sg_oauth'));

// Execute desired action
$action = optional_param('action', false, PARAM_ALPHA);
if ($action == 'delete') {
    $id = required_param('id', PARAM_INT);
    $confirm = optional_param('confirm', false, PARAM_BOOL);
    if ($confirm) {
        $DB->delete_records('oauth_consumers', array('id' => $id));
        redirect(new moodle_url('/local/sg_oauth/config.php'), get_string('consumerdeleted', 'local_sg_oauth'));
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('consumerdeleteheader'));
        $continueurl = new moodle_url('/local/sg_oauth/config.php', array('action' => 'delete', 'id' => $id, 'confirm' => '1'));
        $cancelurl = new moodle_url('/local/sg_oauth/config.php');
        echo $OUTPUT->confirm(get_string('consumerdeletequestion', 'local_sg_oauth'), $continueurl, $cancelurl);
        echo $OUTPUT->footer();
    }
} else if ($action == 'edit') {
    $id = optional_param('id', 0, PARAM_INT);
    require_once('consumer_form.php');
    $mform = new OAuthConsumer_form(new moodle_url('/local/sg_oauth/config.php', array('action' => 'edit', 'id' => $id)));
    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/local/sg_oauth/config.php'));
    } else if ($data = $mform->get_data()) {
        if ($data->id) {
            $data->timemodified = time();
            $DB->update_record('oauth_consumers', $data);
        } else {
            $data->timecreated = time();
            $data->timemodified = $data->timecreated;
            $DB->insert_record('oauth_consumers', $data);
        }
        redirect(new moodle_url('/local/sg_oauth/config.php'));
    } else {
        if ($id) {
            $consumer = $DB->get_record('oauth_consumers', array('id' => $id), '*', MUST_EXIST);
            $mform->set_data($consumer);
        }
        // Output page with OAuth consumer form
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('consumeredit', 'local_sg_oauth'));
        $mform->display();
        echo $OUTPUT->footer();
    }
} else {
    $table = new html_table();
    $table->head = array(
        get_string('name', 'local_sg_oauth'),
        get_string('callbackurl', 'local_sg_oauth'),
        get_string('cancelurl', 'local_sg_oauth'),
        get_string('secretkey', 'local_sg_oauth'),
        get_string('consumerkey', 'local_sg_oauth'),
        get_string('autoauthorize', 'local_sg_oauth') . $OUTPUT->help_icon('autoauthorize', 'local_sg_oauth'),
        get_string('edit'),
        get_string('delete'));
    $table->align = array('center', 'center', 'center', 'center', 'center', 'center');
    //$table->width = '90%';
    $table->data = array();

    // Fetch data for the table
    $consumers = $DB->get_records('oauth_consumers');
    if ($consumers) {
        foreach ($consumers as $consumer) {
            // Build row with consumer data
            $row = array();
            array_push($row, $consumer->name);
            array_push($row, html_writer::link($consumer->callbackurl, $consumer->callbackurl));
            array_push($row, html_writer::link($consumer->cancelurl, $consumer->cancelurl));
            array_push($row, $consumer->secret);
            array_push($row, $consumer->consumerkey);

            $authorizetxt = $consumer->autoauthorize ? get_string('enabled', 'local_sg_oauth') : get_string('disabled', 'local_sg_oauth');
            array_push($row, $authorizetxt);

            $deleteurl = new moodle_url('/local/sg_oauth/config.php', array('action' => 'edit', 'id' => $consumer->id));
            array_push($row, html_writer::link($deleteurl, get_string('edit')));

            $deleteurl = new moodle_url('/local/sg_oauth/config.php', array('action' => 'delete', 'id' => $consumer->id));
            array_push($row, html_writer::link($deleteurl, get_string('delete')));

            // Put row into table data
            array_push($table->data, $row);
        }
    }
    // Output page with consumers table
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('consumersheader', 'local_sg_oauth'));
    echo $OUTPUT->box_start('generalbox');
    echo html_writer::table($table);
    echo $OUTPUT->single_button(new moodle_url('/local/sg_oauth/config.php', array('action' => 'edit')), get_string('addconsumer', 'local_sg_oauth'));
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
}
?>
