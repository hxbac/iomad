<?php 
require_once("../../config.php");

$db_controller = new local_edwiserreports\db_controller();

$courses = get_courses();
foreach ($courses as $course) {
    $params = (object)[

    ];
    $params->isdeleting = false;
    $params->courseid = $course->id;

    $db_controller->update_course_progress_table($params);
}

$returnurl = new \moodle_url('/local/edwiserreports/index.php');
redirect($returnurl, 'Cập nhật tổng số hoạt động thành công', null, \core\output\notification::NOTIFY_SUCCESS);