<?php

require_once('../../config.php'); 
require_once($CFG->libdir . '/excellib.class.php');

$id = required_param('id', PARAM_INT);
$filter = optional_param('filter', 'nofilter', PARAM_TEXT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'assign');

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/assign:viewgrades', $context);

$submissions_of_cm = $DB->get_records('assign_submission', [
    'assignment' => $cm->instance
]);
switch ($filter) {
    case 'requiregrading': {
        $sql = "SELECT asb.* FROM `". $CFG->prefix ."assign_grades` ag JOIN `". $CFG->prefix ."assign_submission` asb ON ag.assignment = asb.assignment AND ag.userid = asb.userid where ag.timemodified < asb.timemodified AND asb.assignment = ". $cm->instance;
        $submissions_of_cm = $DB->get_records_sql($sql);
        break;
    }
    case 'draft': {
        $sql = "SELECT * FROM `". $CFG->prefix ."assign_submission` WHERE timemodified IS NOT NULL AND status = 'draft' AND assignment = ". $cm->instance;
        $submissions_of_cm = $DB->get_records_sql($sql);
        break;
    }
    case 'grantedextension': {
        $sql = "SELECT asb.* FROM `". $CFG->prefix ."assign_user_flags` ag JOIN `". $CFG->prefix ."assign_submission` asb ON ag.assignment = asb.assignment AND ag.userid = asb.userid where ag.extensionduedate > 0 AND asb.assignment = ". $cm->instance;
        $submissions_of_cm = $DB->get_records_sql($sql);
        break;
    }
    case '': {
        
    }
    case 'notsubmitted': {
        
    }
    case 'submitted': {
        $role = $DB->get_record('role', array('shortname' => 'student'));
        $contextcourse = context_course::instance($course->id);
        $students = get_role_users($role->id, $contextcourse);
        $submissions_of_cm = [...$submissions_of_cm];
        foreach ($students as $student) {
            $is_student_in_submission = false;
            foreach ($submissions_of_cm as $student_submission) {
                if ($student->id === $student_submission->userid) {
                    $is_student_in_submission = true;
                    break;
                }
            }
            if (!$is_student_in_submission) {
                $student_submission_new = (object) [
                    'userid' => $student->id
                ];
                array_push($submissions_of_cm, $student_submission_new);
            }
        }
        break;
    }
    default: {
        $returnurl = new moodle_url('/mod/assign/view.php', [
            'id' => $id
        ]);
        print_error('Trường lọc không phù hợp.', 'error', $returnurl);
        die();
    }
}



$workbook = new MoodleExcelWorkbook("-");
$myxls = $workbook->add_worksheet($course->fullname);

$format = $workbook->add_format();
$format->set_align('center');
$format->set_bold(1);
$myxls->write_string(0, 0, 'Thống kê nộp bài tập', $format);
$myxls->write_string(1, 0, 'Lớp: '. $course->fullname, $format);
$myxls->write_string(2, 0, 'Bài: '. $cm->name, $format);

$colnummerge = 5;
$myxls->merge_cells(0, 0, 0, $colnummerge);
$myxls->merge_cells(1, 0, 1, $colnummerge);
$myxls->merge_cells(2, 0, 2, $colnummerge);

$format->set_border(1);

$headerexport = ['STT', 'Họ', 'Tên', 'Tài khoản', 'Đơn vị', 'Đã nộp'];
// Header 
foreach ($headerexport as $colnum => $header) {
    $myxls->write_string(5, $colnum, $header, $format);
}

$formatWrapText = $workbook->add_format();
$formatWrapText->set_text_wrap();

$myxls->set_column(0, 0, 5);
$myxls->set_column(1, 1, 22);
$myxls->set_column(2, 2, 16);
$myxls->set_column(3, 3, 18);
$myxls->set_column(4, 4, 30);
$myxls->set_column(5, 5, 18);

$format_center = $workbook->add_format();
$format_center->set_align('center');
$format_center->set_border(1);

$format->set_align('left');
$format->set_bold(0);
$stt = 1;
$totalrownum = 6;
$rownum = 5;
foreach ($submissions_of_cm as $data) {
    if ($filter === 'notsubmitted' && $data->status === 'submitted') {
        continue;
    }
    if ($filter === 'submitted' && $data->status !== 'submitted') {
        continue;
    }

    $is_teacher = $DB->record_exists('role_assignments', [
        'contextid' => $contextcourse->id,
        'userid' => $data->userid,
        'roleid' => 3
    ]);
    if ($is_teacher) {
        continue;
    }

    $student = $DB->get_record('user', [
        'id' => $data->userid
    ]);
    $address_info_user = $DB->get_field('user_info_data', 'data', [
        'userid' => $student->id,
        'fieldid' => 2
    ]);
    
    $rownum++;
    $myxls->write_string($rownum, 0, $stt, $format_center);
    $myxls->write_string($rownum, 1, $student->firstname, $format);
    $myxls->write_string($rownum, 2, $student->lastname, $format);
    $myxls->write_string($rownum, 3, $student->username, $format);
    $myxls->write_string($rownum, 4, $address_info_user, $format);
    
    $isSubmitted = '';
    if ($data->status === 'submitted') {
        $isSubmitted = 'X';
    }

    $myxls->write_string($rownum, 5, $isSubmitted, $format_center);

    $stt++;
    $totalrownum = $rownum;
}

$formatTime = $workbook->add_format(); 
$formatTime->set_italic();
$now = new \DateTime("now", \core_date::get_server_timezone_object());
$myxls->write_string($totalrownum + 2, 3, 'Thời điểm xuất báo cáo: '. date_format($now, 'd-m-Y H:i:s'), $formatTime);
$myxls->set_column($totalrownum + 2, 3, 14);

// Sending HTTP headers.
$workbook->send('Báo cáo thống kê nộp bài tập lớp '. $course->fullname);
// Close the workbook.
$workbook->close();


