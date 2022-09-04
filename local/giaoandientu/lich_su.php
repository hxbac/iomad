<?php
require_once('../../config.php');
require_once('./functions.php');

$weekid = required_param('weekid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$categoryid = $DB->get_record('lms_gadt_weeks', [
    'id' => $weekid
])->categoryid;

if (!checkAccess($categoryid, $userid)) {
    print_error('accessdenied', 'admin');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/giaoandientu/lich_su.php', [
    'weekid' => $weekid,
    'courseid' => $courseid,
    'userid' => $userid
]);
$PAGE->set_title('Báo cáo giảng dạy');
$PAGE->set_heading('Lịch sử nộp báo cáo');
echo $OUTPUT->header();

$storerecords = $DB->get_records('lms_gadt_storereport', [
    'weekid' => $weekid,
    'courseid' => $courseid,
    'userid' => $userid
], 'id DESC');

$course = $DB->get_record('course', [
    'id' => $courseid
]);
$coursename = $course->fullname ?? 'Khóa học đã bị xóa';
$weekrecord = $DB->get_record('lms_gadt_weeks', [
    'id' => $weekid
]);
$weekname = $weekrecord->weekname;

$teacher = $DB->get_record('user', [
    'id' => $userid
]);

$breadcrumbobj = (object) [];
$breadcrumbobj->parentname = '';
getParentNameCategory($weekrecord->parent, $breadcrumbobj->parentname);
$breadcrumbobj->categorycurr = $DB->get_record('course_categories', [
    'id' => $weekrecord->categoryid
])->name;
$breadcrumbobj->name = $teacher->firstname . ' ' . $teacher->lastname;
$breadcrumbobj->urlgiaoandientu = new moodle_url('/local/giaoandientu/');
$breadcrumbobj->course = $coursename;

foreach ($storerecords as $item) {
    $fs = get_file_storage();
    $files = $fs->get_area_files($item->contextid, 'local_giaoandientu', 'giaovien', $item->itemid, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
    foreach ($files as $file) {
        $filename = $file->get_filename();
        $item->urldownload = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $filename,
            true
        );
    }
    $item->message = "Đã duyệt";

    if ($item->status == -1) {
        $item->message = "Đã hủy";
    } else {
        if ($item->feedback == null) {
            if ($item->status == 0) {
                $item->message = "Chưa gửi";
            } else if ($item->status == 1) {
                $item->message = "Chờ duyệt";
            }
            $item->urldownload = false;
        }
    }
}

echo $OUTPUT->render_from_template('local_giaoandientu/lichsu', [
    'storerecords' => [...$storerecords],
    'breadcrumbobj' => $breadcrumbobj,
    'weekname' => $weekname,
    'cousename' => $coursename
]);

echo $OUTPUT->footer();