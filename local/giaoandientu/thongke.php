<?php
require_once('../../config.php');
require_once('./functions.php');

$categoryid = required_param('categoryid', PARAM_INT);

if (!checkManagerAccess($categoryid)) {
    print_error('accessdenied', 'admin');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/giaoandientu/thongke.php', [
    'categoryid' => $categoryid
]);
$PAGE->set_title('Báo cáo giảng dạy');
$PAGE->set_heading('Thống kê báo cáo giảng dạy');
echo $OUTPUT->header();

$breadcrumbobj = (object) [];
$breadcrumbobj->parentname = '';
$category = $DB->get_record('course_categories', [
    'id' => $categoryid
]);
getParentNameCategory($category->parent, $breadcrumbobj->parentname);
$breadcrumbobj->name = $category->name;
$breadcrumbobj->urlgiaoandientu = new moodle_url('/local/giaoandientu');

$sql = "SELECT `w`.id, `w`.weekname, COUNT(*) AS `solanchuagui`  FROM `" . $CFG->prefix . "lms_gadt_storereport` `sr` JOIN `" . $CFG->prefix . "lms_gadt_weeks` `w` ON `w`.id = `sr`.weekid WHERE status = 0 AND `w`.categoryid = ". $categoryid ." GROUP BY `w`.id, `w`.weekname";
$weeks = $DB->get_records_sql($sql);

foreach ($weeks as $week) {
    $sql = "SELECT courseid, count(*) AS `solanchuagui` FROM `" . $CFG->prefix . "lms_gadt_storereport` WHERE status = 0 AND weekid = ". $week->id ." GROUP BY courseid";
    $courses = $DB->get_records_sql($sql);
    foreach ($courses as $course) {
        $course->coursename = explode('_', $DB->get_record('course', [
            'id' => $course->courseid
        ])->shortname)[3] ?? 'Khóa học đã bị xóa';
    }
    $week->data = [...$courses];
}

$urlExportExcel = new moodle_url('/local/giaoandientu/exportexcel.php', [
    'categoryid' => $categoryid
]);

echo $OUTPUT->render_from_template('local_giaoandientu/thongke', [
    'weeks' => [...$weeks],
    'breadcrumbobj' => $breadcrumbobj,
    'urlexportexcel' => $urlExportExcel,
]);

echo $OUTPUT->footer();