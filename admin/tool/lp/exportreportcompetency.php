<?php 

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/excellib.class.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);

$sql = "SELECT c.id, c.shortname FROM ". $CFG->prefix ."competency_coursecomp cc JOIN ". $CFG->prefix ."competency c ON c.id = cc.competencyid WHERE cc.courseid = ". $courseid;
$coursecompetencylist = $DB->get_records_sql($sql);

$context = context_course::instance($courseid);
$studentroleid = $DB->get_field('role', 'id',[
    'shortname' => 'student'
]);
$students_of_course = get_role_users($studentroleid, $context);

$headerexport = ['STT', 'Học sinh', 'Tổng số năng lực', 'Số năng lực hoàn thành'];
foreach ($coursecompetencylist as $conpetency) {
    array_push($headerexport, $conpetency->shortname);
}

$stt = 1;
$dataexport = array();
foreach ($students_of_course as $student) {
    $item = array();

    // STT 
    array_push($item, $stt);
    $stt++;

    // Fullname student 
    array_push($item, $student->firstname .' '. $student->lastname);

    $total_competency = 0;
    $total_competency_completed = 0;
    foreach ($coursecompetencylist as $competency) {
        $status = '';
        $sql = "SELECT grade FROM `". $CFG->prefix ."competency_usercompcourse` WHERE competencyid = ". $competency->id ." AND userid = ". $student->id ." AND courseid = ". $courseid;
        $is_completed = $DB->get_field_sql($sql);
        $total_competency++;
        if ($is_completed == 2) {
            $status = 'Đạt';
            $total_competency_completed++;
        }
        array_push($item, $status);
    }
    array_splice( $item, 2, 0, [$total_competency, $total_competency_completed] );

    array_push($dataexport, $item);
}

$workbook = new MoodleExcelWorkbook("-");
$myxls = $workbook->add_worksheet($course->fullname);

$format = $workbook->add_format();
$format->set_align('center');
$format->set_bold(1);
$myxls->write_string(0, 0, 'Thống kê báo cáo năng lực', $format);
$myxls->write_string(1, 0, 'Lớp: '. $course->fullname, $format);
$colnummerge = count($headerexport) - 1;
$myxls->merge_cells(0, 0, 0, $colnummerge);
$myxls->merge_cells(1, 0, 1, $colnummerge);

$format->set_border(1);
// Header 
foreach ($headerexport as $colnum => $header) {
    $myxls->write_string(4, $colnum, $header, $format);
}

$formatWrapText = $workbook->add_format();
$formatWrapText->set_text_wrap();

$myxls->set_column(0, 0, 5);
$myxls->set_column(1, 1, 22);
$myxls->set_column(2, 2, 18, $formatWrapText);
$myxls->set_column(3, 3, 18, $formatWrapText);
$weeknum = count($weeksOfCategory);
for ($i = 0; $i < $weeknum; $i++) {
    $myxls->set_column($i + 3, $i + 3, 10);
}


$format->set_align('left');
$format->set_bold(0);
foreach ($dataexport as $rownum => $data) {
    $rownum += 5;
    foreach ($data as $colnum => $item) {
        if ($colnum > 3) {
            $myxls->set_column($colnum, $colnum, 14, $formatWrapText);
            $format->set_align('center');
        } else {
            $format->set_align('left');
            if (is_numeric($item)) {
                $format->set_align('center');
            }
        }

        $myxls->write_string($rownum, $colnum, $item, $format);
    }
    $totalrownum = $rownum;
}

$formatTime = $workbook->add_format(); 
$formatTime->set_italic();
$now = new \DateTime("now", \core_date::get_server_timezone_object());
$myxls->write_string($totalrownum + 2, 6, 'Thời điểm xuất báo cáo: '. date_format($now, 'd-m-Y H:i:s'), $formatTime);
$myxls->set_column($totalrownum + 2, 6, 14);

// Sending HTTP headers.
$workbook->send('Báo cáo thống kê năng lực lớp '. $course->fullname);
// Close the workbook.
$workbook->close();