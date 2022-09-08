<?php
require_once('../../config.php');
require_once('./functions.php');

$weekid = required_param('weekid', PARAM_INT);

$week = $DB->get_record('lms_gadt_weeks', [
    'id' => $weekid
]);

if (!checkManagerAccess($week->categoryid)) {
    print_error('accessdenied', 'admin');
}

$storerecords = $DB->get_records('lms_gadt_storereport', [
    'weekid' => $weekid
]);

foreach ($storerecords as $item) {
    if ($item->itemid != null) {
        $DB->delete_records('files', [
            'component' => 'local_giaoandientu',
            'itemid' => $item->itemid
        ]);
    }
}

$DB->delete_records('lms_gadt_storereport', [
    'weekid' => $weekid
]);

$currenttime = new DateTime("now", core_date::get_server_timezone_object());
$fromform->timecreated = $currenttime->getTimestamp();

$courses = get_courses($week->categoryid);

$categoryname = $DB->get_record('course_categories', [
    'id' => $week->categoryid
])->name;

$messageurl = new moodle_url('/local/giaoandientu/xem_bao_cao.php', [
    'categoryid' => $fromform->categoryid
]);

$message = 'Tổ trưởng đã tạo báo cáo mới.';
$messagehtml = "<p>Tổ trưởng đã tạo tuần mới cho danh mục: " . $categoryname . ". <br>Click <a href='" . $messageurl . "' style='text-decoration: underline;'>tại đây</a> để xem chi tiết./</p>";

foreach ($courses as $key => $value) {
    $courses[$key]->shortname = explode('_', $value->shortname)[3];

    $courses[$key]->teachers = (array)[];
    $teachers = getTeachersByCourseid($value->id);
    foreach ($teachers as $teacher) {
        array_push($courses[$key]->teachers, $teacher->id);
    }
}

$courses = array_reduce($courses, function ($prevValue, $curValue) {
    foreach ($curValue->teachers as $key => $teacher) {
        foreach ($prevValue as $course) {
            if ($curValue->shortname === $course->shortname && in_array($teacher, $course->teachers)) {
                unset($curValue->teachers[$key]);
            }
        }
    }

    array_push($prevValue, $curValue);
    return $prevValue;
}, (array)[]);


foreach ($courses as $course) {
    foreach ($course->teachers as $teacher) {
        $datainsert = (object) [
            'weekid' => $weekid,
            'courseid' => (int)$course->id,
            'userid' => (int)$teacher,
            'status' => 0
        ];
        $DB->insert_record('lms_gadt_storereport', $datainsert);

        sendMessageGadt($teacher, 'Nộp báo cáo mới', $message, $messagehtml, $messageurl);
    }
}

$returnurl = new moodle_url('/local/giaoandientu/view.php', [
    'categoryid' => $week->categoryid,
    'weekid' => $weekid
]);

redirect($returnurl, 'Cập nhật giáo viên thành công!', null, \core\output\notification::NOTIFY_SUCCESS);