<?php 
//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class form_create_auto extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
        $mform = $this->_form; // Don't forget the underscore! 
        
        $categoryid = required_param('categoryid', PARAM_INT);

        $mform->addElement('text', 'weekname', 'Nhập tên tuần'); // Add elements to your form.
        $mform->setType('weekname', PARAM_NOTAGS);                   // Set type of element.
        $mform->setDefault('weekname', "Tuần số");

        $mform->addElement('text', 'weekstart', 'Bắt đầu từ tuần'); // Add elements to your form.
        $mform->setType('weekstart', PARAM_INT);                   // Set type of element.
        $mform->setDefault('weekstart', "1");

        $mform->addElement('text', 'weeksnumber', 'Số tuần sẽ tạo'); // Add elements to your form.
        $mform->setType('weeksnumber', PARAM_INT);                   // Set type of element.

        $mform->addElement('html', '<div style="margin-left: 36px; margin-top: 10px; margin-bottom: 14px; ">VD: Tên tuần: "Tuần số". Bắt đầu từ tuần: 3. Số tuần sẽ tạo: 2. Kết quả sẽ được: <br>Tuần số 3<br>Tuần số 4</div>');
        
        $mform->addElement('date_time_selector', 'startdate', 'Ngày bắt đầu');
        
        $mform->addElement('text', 'daysexpired', 'Số ngày từ ngày bắt đầu tới hạn'); // Add elements to your form.
        $mform->setType('daysexpired', PARAM_INT);                   // Set type of element.
        $mform->setDefault('daysexpired', "7");

        $mform->addElement('text', 'distance', 'Khoảng cách giữa mỗi tuần (ngày)'); // Add elements to your form.
        $mform->setType('distance', PARAM_INT);                   // Set type of element.
        $mform->setDefault('distance', "7");

        $mform->addElement('text', 'description', 'Yêu cầu'); // Add elements to your form.
        $mform->setType('description', PARAM_NOTAGS); 
        
        $mform->addElement('hidden', 'categoryid');
        $mform->setType('categoryid', PARAM_INT);                  // Set type of element.
        $mform->setDefault('categoryid', $categoryid);

        $this->add_action_buttons();
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}