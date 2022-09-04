<?php 
//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class form_create_week extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG, $USER;

        $categoryid = required_param('categoryid', PARAM_INT);
       
        $week = $this->_customdata['week'];

        $mform = $this->_form; // Don't forget the underscore! 

        $mform->addElement('text', 'weekname', 'Nhập tên tuần'); // Add elements to your form.
        $mform->setType('weekname', PARAM_NOTAGS);                   // Set type of element.
        $mform->setDefault('weekname', $week->weekname ?? '');
        $mform->addElement('date_time_selector', 'startdate', 'Bắt đầu');
        $mform->setDefault('startdate', $week->startdate ?? '');
        $mform->addElement('date_time_selector', 'enddate', 'Kết thúc');
        $mform->setDefault('enddate', $week->enddate ?? '');
        $mform->addElement('text', 'description', 'Yêu cầu'); // Add elements to your form.
        $mform->setType('description', PARAM_NOTAGS); 
        $mform->setDefault('description', $week->description ?? '');
        
        $mform->addElement('hidden', 'categoryid');
        $mform->setType('categoryid', PARAM_INT);                  // Set type of element.
        $mform->setDefault('categoryid', $categoryid);
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);                  // Set type of element.
        $mform->setDefault('userid', $USER->id);
        if ($week->id) {
            $mform->addElement('hidden', 'weekid');
            $mform->setType('weekid', PARAM_INT);                  // Set type of element.
            $mform->setDefault('weekid', $week->id);
        }

        $this->add_action_buttons();
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}