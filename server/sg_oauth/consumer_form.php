<?php

require_once("$CFG->libdir/formslib.php");

class OAuthConsumer_form extends moodleform {

    function definition() {
        global $CFG;

        $mform = & $this->_form;

        $mform->addElement('text', 'name', get_string('name', 'local_sg_oauth'), array('size' => '50'));
        $mform->setType('callbackurl', PARAM_ALPHANUMEXT);

        $mform->addElement('text', 'callbackurl', get_string('callbackurl', 'local_sg_oauth'), array('size' => '50'));
        $mform->setType('callbackurl', PARAM_URL);

        $mform->addElement('text', 'cancelurl', get_string('cancelurl', 'local_sg_oauth'), array('size' => '50'));
        $mform->setType('cancelurl', PARAM_URL);

        $mform->addElement('text', 'secret', get_string('secretkey', 'local_sg_oauth'), array('size' => '50'));
        $mform->setType('secret', PARAM_RAW_TRIMMED);

        $mform->addElement('text', 'consumerkey', get_string('consumerkey', 'local_sg_oauth'), array('size' => '50'));
        $mform->setType('consumerkey', PARAM_RAW_TRIMMED);

        $mform->addElement('advcheckbox', 'autoauthorize', get_string('autoauthorize', 'local_sg_oauth'));
        $mform->setDefault('autoauthorize', 0);
        $mform->addHelpButton('autoauthorize', 'autoauthorize', 'local_sg_oauth');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }

}

?>
