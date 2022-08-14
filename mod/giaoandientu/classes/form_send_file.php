<?php 
//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class form_send_file extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
        $id = required_param('id', PARAM_INT);
        $mform = $this->_form; // Don't forget the underscore! 
        // $mform->addElement('filepicker', 'coursefile', 'coursefile');
        $mform->addElement('filemanager', 'giaovien', 'Tải file', null, array('maxbytes' => $CFG->maxbytes, 'maxfiles' => 1, 'accepted_types' => array('*')));
        
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);              // Set type of element.
        $mform->setDefault('id', $id);

        $this->add_action_buttons();
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}