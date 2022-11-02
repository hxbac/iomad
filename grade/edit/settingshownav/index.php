<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A page for editing show/hidden item of grade navigation for teacher
 *
 * @package   core_grades
 * @copyright 2007 Petr Skoda
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once '../../../config.php';
require_once './form.php';

require_login();
if (!is_siteadmin()) {
    print_error('accessdenied', 'admin');
    exit;
}

$PAGE->set_url('/grade/edit/settingshownav/index.php');

$configValue = (object)[];
if ($configNavGrade = $DB->get_record('config', [
    'name' => 'lms_config_grade_nav_for_teacher'
])) {
    $configValue = json_decode($configNavGrade->value);
} else {
    $configValue->overview = '1';
    $configValue->singleview = '1';
    $configValue->user = '1';
    $configValue->setup = '1';
    $configValue->coursesettings = '1';
    $configValue->grader = '1';
    $configValue->view = '1';
    $configValue->edit = '1';
    $configValue->csv = '1';
    $configValue->direct = '1';
    $configValue->xls = '1';
}

$mform = new form_show_hide_item_gradenav(null, [
    'configValue' => $configValue
]);

echo $OUTPUT->header();

//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
    //In this case you process validated data. $mform->get_data() returns data posted in form.
    unset($fromform->submitbutton);

    if ($configNavGrade) {
        $configNavGrade->value = json_encode($fromform);
        $DB->update_record('config', $configNavGrade);
    } else {
        $objInsert = (object) [
            'name' => 'lms_config_grade_nav_for_teacher',
            'value' => json_encode($fromform)
        ];
        $DB->insert_record('config', $objInsert);
    }

    redirect('/grade/edit/settingshownav/index.php', 'Thay đổi cài đặt thành công', null, \core\output\notification::NOTIFY_SUCCESS);
} else {

    //displays the form
    $mform->display();
}

echo $OUTPUT->footer();
