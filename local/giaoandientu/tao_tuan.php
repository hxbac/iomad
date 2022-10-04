<?php 
require_once("../../config.php");
require_once("./functions.php");
require_once($CFG->dirroot . '/local/giaoandientu/classes/form_create_week.php');

$categoryid = required_param('categoryid', PARAM_INT);
$weekid = optional_param('weekid', -1, PARAM_INT);

if (!checkManagerAccess($categoryid)) {
    print_error('accessdenied', 'admin');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/giaoandientu/tao_tuan.php');
$PAGE->set_title('Báo cáo giảng dạy');
$PAGE->set_heading('Tạo tuần báo cáo');
echo $OUTPUT->header();

if ($weekid === -1) {
    $returnurl = new moodle_url('/local/giaoandientu/view.php', [
        'categoryid' => $categoryid
    ]);
    
    $mform = new form_create_week(null, array(
        'createweek' => true
    ));
    
    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($fromform = $mform->get_data()) {
        $sql = "SELECT weekname FROM `". $CFG->prefix ."lms_gadt_weeks` WHERE categoryid = ". $fromform->categoryid ." AND weekname = '". $fromform->weekname. "'";
        $checkWeekNameExists = $DB->record_exists_sql($sql);

        if ($checkWeekNameExists) {
            $returnurl = new moodle_url('/local/giaoandientu/tao_tuan.php', [
                'categoryid' => $fromform->categoryid
            ]);
            print_error('Tên tuần đã tồn tại. Vui lòng nhập tên khác.', 'local_giaoandientu', $returnurl);
            exit;
        }

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
    
        foreach ($courses as $key => $value) {
            $courses[$key]->shortname = explode('_', $value->shortname)[3];

            $courses[$key]->teachers = (array)[];
            $teachers = getTeachersByCourseid($value->id);
            foreach ($teachers as $teacher) {
                array_push($courses[$key]->teachers, $teacher->id);
            }
        }

        if ($fromform->merge_teacher == '1') {
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
        }

        foreach ($courses as $course) {
            foreach ($course->teachers as $teacher) {
                $datainsert = (object) [
                    'weekid' => $weekid,
                    'courseid' => (int)$course->id,
                    'userid' => (int)$teacher,
                    'status' => 0
                ];
                $DB->insert_record('lms_gadt_storereport', $datainsert);
    
                sendMessageGadt($teacher, 'Nộp KHBD '. $fromform->weekname, $message, $messagehtml, $messageurl);
            }
        }
    
        redirect($returnurl, 'Thêm tuần thành công!', null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        $category = $DB->get_record('course_categories', [
            'id' => $categoryid
        ]);
        $parentname = '';
        getParentNameCategory($category->parent, $parentname);
        echo "<div style='padding-left: 34px;'>Bạn đang tạo tuần cho danh mục " . $parentname . "<b><a href=" . $returnurl . ">" . $category->name . "</a></b>. Giáo viên thuộc danh mục này sẽ hiển thị tuần để nộp báo cáo!</div><br><br>";
        $mform->display();
    }
} else {
    $week = $DB->get_record('lms_gadt_weeks', [
        'id' => $weekid
    ]);

    $returnurl = new moodle_url('/local/giaoandientu/view.php', [
        'categoryid' => $categoryid,
        'weekid' => $weekid
    ]);
    
    $mform = new form_create_week(null, array(
        'week' => $week
    ));
    
    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($fromform = $mform->get_data()) {
        $week->weekname = $fromform->weekname;
        $week->startdate = $fromform->startdate;
        $week->enddate = $fromform->enddate;
        $week->description = $fromform->description;
        $week->userid = $USER->id;

        $DB->update_record('lms_gadt_weeks', $week);
        redirect($returnurl, 'Sửa tuần thành công!', null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        $mform->display();
    }
}


echo $OUTPUT->footer();