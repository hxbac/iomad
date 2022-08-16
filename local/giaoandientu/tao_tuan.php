<?php 
require_once("../../config.php");
require_once("./functions.php");
require_once($CFG->dirroot . '/local/giaoandientu/classes/form_create_week.php');

$categoryid = required_param('categoryid', PARAM_INT);

if (!checkManagerAccess($categoryid)) {
    print_error('accessdenied', 'admin');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/giaoandientu/tao_tuan.php');
$PAGE->set_title('Báo cáo giảng dạy');
$PAGE->set_heading('Tạo tuần báo cáo');
echo $OUTPUT->header();


$returnurl = new moodle_url('/local/giaoandientu/view.php', [
    'categoryid' => $categoryid
]);

$mform = new form_create_week();

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($fromform = $mform->get_data()) {
    $currenttime = new DateTime("now", core_date::get_server_timezone_object());
    $fromform->timecreated = $currenttime->getTimestamp();
    
    $courses = get_courses($fromform->categoryid);
    $weekid = $DB->insert_record('lms_gadt_weeks', $fromform);

    $categoryname = $DB->get_record('course_categories', [
        'id' => $categoryid
    ])->name;
    $messageurl = new moodle_url('/local/giaoandientu/xem_bao_cao.php', [
        'categoryid' => $fromform->categoryid
    ]);
    $message = 'Tổ trưởng đã tạo báo cáo mới.';
    $messagehtml = "<p>Tổ trưởng đã tạo tuần mới cho danh mục: ". $categoryname .". <br>Click <a href='". $messageurl ."' style='text-decoration: underline;'>tại đây</a> để xem chi tiết./</p>";

    foreach ($courses as $course) {
        $teachers = getTeachersByCourseid($course->id);
        foreach ($teachers as $teacher) {
            $datainsert = (object) [
                'weekid' => $weekid,
                'courseid' => $course->id,
                'userid' => $teacher->id,
                'status' => 0
            ];
            $DB->insert_record('lms_gadt_storereport', $datainsert);

            sendMessageGadt($teacher->id, 'Nộp báo cáo mới', $message, $messagehtml, $messageurl);
        }
    }

    redirect($returnurl, 'Thêm tuần thành công. Trang sẽ chuyển hướng tới xem báo cáo!', 5);
} else {
    $category = $DB->get_record('course_categories', [
        'id' => $categoryid
    ]);
    $parentname = '';
    getParentNameCategory($category->parent, $parentname);
    echo "<div style='padding-left: 34px;'>Bạn đang tạo tuần cho danh mục " . $parentname . "<b><a href=" . $returnurl . ">" . $category->name . "</a></b>. Giáo viên thuộc danh mục này sẽ hiển thị tuần để nộp báo cáo!</div><br><br>";
    $mform->display();
}
echo $OUTPUT->footer();