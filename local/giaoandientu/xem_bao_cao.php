<?php 
require_once('../../config.php');
require_once('./functions.php');

$categoryid = required_param('categoryid', PARAM_INT);

if (!checkTeacherAccess($categoryid)) {
    print_error('accessdenied', 'admin');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/giaoandientu/xem_bao_cao.php', [
    'categoryid' => $categoryid
]);
$PAGE->set_title('Báo cáo giảng dạy');
$PAGE->set_heading('Nộp kế hoạch bài dạy');
echo $OUTPUT->header();

$currenttime = new DateTime("now", core_date::get_server_timezone_object());
$nowtimestamp = $currenttime->getTimestamp();
$sqlraw = 'SELECT * FROM `'. $CFG->prefix .'lms_gadt_weeks` WHERE `categoryid` = ' . $categoryid . ' AND `startdate` <= ' . $nowtimestamp . ' ORDER BY enddate DESC';

$weeksofcategory = $DB->get_records_sql($sqlraw);

$breadcrumbobj = (object) [];
$breadcrumbobj->parentname = '';
$category = $DB->get_record('course_categories', [
    'id' => $categoryid
]);
getParentNameCategory($category->parent, $breadcrumbobj->parentname);
$breadcrumbobj->name = $category->name;
$breadcrumbobj->urlgiaoandientu = new moodle_url('/local/giaoandientu');

$datarender = [];

foreach ($weeksofcategory as $weekofcategory) {
    $storerecords = $DB->get_records('lms_gadt_storereport', [
        'weekid' => $weekofcategory->id,
        'userid' => $USER->id,
    ]);
    foreach ($storerecords as $storerecord) {
        if ($storerecord->status != -1) {
            $renderitem = (object) [];
            $renderitem->weekname = $weekofcategory->weekname;
            $renderitem->urlhistory = new moodle_url('/local/giaoandientu/lich_su.php', [
                'weekid' => $weekofcategory->id,
                'courseid' => $storerecord->courseid,
                'userid' => $USER->id
            ]);
            $renderitem->description = $weekofcategory->description;
            $renderitem->startdate = $weekofcategory->startdate;
            $renderitem->enddate = $weekofcategory->enddate;

            $renderitem->course = explode('_', $DB->get_record('course', [
                'id' => $storerecord->courseid
            ])->shortname)[3] ?? 'Khóa học đã bị xóa';

            if ($storerecord->status != 0) {
                $renderitem->urlsendfile = false;
                if ($storerecord->feedback == null) {
                    $renderitem->messagestatus = 'Chờ duyệt';
                } else {
                    $renderitem->messagestatus = 'Đã duyệt';
                }
            } else {
                $renderitem->messagestatus = '';
                $renderitem->urlsendfile = new moodle_url('/local/giaoandientu/gui_file.php', [
                    'id' => $storerecord->id
                ]);
                $now = (new DateTime("now", core_date::get_server_timezone_object()))->getTimestamp();
                if ($weekofcategory->startdate > $now) {
                    $renderitem->messagestatus = 'Chưa bắt đầu';
                    $renderitem->urlsendfile = false;
                    $renderitem->disablerow = true;
                }
                if ($weekofcategory->enddate < $now) {
                    $renderitem->messagestatus = 'Quá hạn';
                    $renderitem->urlsendfile = false;
                    $renderitem->disablerow = true;
                }
            }
            array_push($datarender, $renderitem);
        }
    }
}

echo $OUTPUT->render_from_template('local_giaoandientu/giaovien', [
    'weeks' => $datarender,
    'breadcrumbobj' => $breadcrumbobj
]);

echo $OUTPUT->footer();