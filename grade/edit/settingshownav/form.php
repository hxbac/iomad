<?php 
//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class form_show_hide_item_gradenav extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
        $mform = $this->_form; // Don't forget the underscore! 
        $configValue = $this->_customdata['configValue'];
        $choices = array();
        $choices['0'] = 'Ẩn';
        $choices['1'] = 'Hiện';

        $mform->addElement('header', 'viewGrade', 'Xem');
        $mform->addElement('select', 'overview', 'Báo cáo tổng quan', $choices);
        $mform->setDefault('overview', (int)$configValue->overview);
        $mform->addElement('select', 'singleview', 'Xem đơn giản', $choices);
        $mform->setDefault('singleview', (int)$configValue->singleview);
        $mform->addElement('select', 'user', 'Báo cáo', $choices);
        $mform->setDefault('user', (int)$configValue->user);

        $mform->addElement('header', 'gradeCategory', 'Các chuyên mục và mục');
        $mform->addElement('select', 'settings', 'Ẩn thẻ cha', $choices);
        $mform->setDefault('settings', (int)$configValue->settings);
        $mform->addElement('select', 'setup', 'Sổ điểm', $choices);
        $mform->setDefault('setup', (int)$configValue->setup);
        $mform->addElement('select', 'coursesettings', 'Thiết lập điểm khóa học', $choices);
        $mform->setDefault('coursesettings', (int)$configValue->coursesettings);
        $mform->addElement('select', 'grader', 'Tùy chỉnh: Báo cáo chấm điểm', $choices);
        $mform->setDefault('grader', (int)$configValue->grader);

        $mform->addElement('header', 'letterHeader', 'Chữ');
        $mform->addElement('select', 'letter', 'Ẩn thẻ cha', $choices);
        $mform->setDefault('letter', (int)$configValue->letter);
        $mform->addElement('select', 'view', 'Xem', $choices);
        $mform->setDefault('view', (int)$configValue->view);
        $mform->addElement('select', 'edit', 'Chỉnh sửa', $choices);
        $mform->setDefault('edit', (int)$configValue->edit);

        $mform->addElement('header', 'importHeader', 'Nhập dữ liệu');
        $mform->addElement('select', 'import', 'Ẩn thẻ cha', $choices);
        $mform->setDefault('import', (int)$configValue->import);
        $mform->addElement('select', 'csv', 'CSV file', $choices);
        $mform->setDefault('csv', (int)$configValue->csv);
        $mform->addElement('select', 'direct', 'Dán từ bảng tính', $choices);
        $mform->setDefault('direct', (int)$configValue->direct);

        $mform->addElement('header', 'exportHeader', 'Xuất');
        $mform->addElement('select', 'export', 'Ẩn thẻ cha', $choices);
        $mform->setDefault('export', (int)$configValue->export);
        $mform->addElement('select', 'xls', 'Bảng tính Excel', $choices);
        $mform->setDefault('xls', (int)$configValue->xls);


        $this->add_action_buttons();
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}