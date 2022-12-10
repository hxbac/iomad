<?php 

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/excellib.class.php');

$courseid = required_param('courseid', PARAM_INT);

require_login($courseid);

$course = get_course($courseid);

$sql = "SELECT c.id, c.shortname FROM ". $CFG->prefix ."competency_coursecomp cc JOIN ". $CFG->prefix ."competency c ON c.id = cc.competencyid WHERE cc.courseid = ". $courseid;
$coursecompetencylist = $DB->get_records_sql($sql);

$context = context_course::instance($courseid);

$headerexport = ['STT', 'Năng lực', 'Hoàn thành', 'Chưa hoàn thành'];



$workbook = new MoodleExcelWorkbook("-");
$myxls = $workbook->add_worksheet($course->fullname);

$format = $workbook->add_format();
$format->set_align('center');
$format->set_bold(1);
$myxls->write_string(0, 0, 'Thống kê báo cáo năng lực', $format);
$myxls->write_string(1, 0, 'Lớp: '. $course->fullname, $format);
$myxls->write_string(2, 0, 'Học sinh: '. $USER->firstname .''. $USER->lastname, $format);
$colnummerge = count($headerexport) - 1;
$myxls->merge_cells(0, 0, 0, $colnummerge);
$myxls->merge_cells(1, 0, 1, $colnummerge);
$myxls->merge_cells(2, 0, 2, $colnummerge);

$format->set_border(1);
// Header 
foreach ($headerexport as $colnum => $header) {
    $myxls->write_string(5, $colnum, $header, $format);
}

$formatWrapText = $workbook->add_format();
$formatWrapText->set_text_wrap();

$myxls->set_column(0, 0, 5);
$myxls->set_column(1, 1, 22, $formatWrapText);
$myxls->set_column(2, 2, 18, $formatWrapText);
$myxls->set_column(3, 3, 18, $formatWrapText);


$format->set_align('center');
$format->set_bold(0);
$stt = 1;
foreach ([...$coursecompetencylist] as $rownum => $competency) {
    $rownum += 6;

    $sql = "SELECT grade FROM `". $CFG->prefix ."competency_usercompcourse` WHERE competencyid = ". $competency->id ." AND userid = ". $USER->id ." AND courseid = ". $courseid;
    $is_completed = $DB->get_field_sql($sql);
    $status = null;
    if ($is_completed == 2) {
        $status = 'X';
    }

    $myxls->write_string($rownum, 0, $stt, $format);
    $myxls->write_string($rownum, 1, $competency->shortname, $format);
    $myxls->write_string($rownum, 2, $status !== null ? 'X' : '', $format);
    $myxls->write_string($rownum, 3, $status === null ? 'X' : '', $format);
    $stt++;
    $totalrownum = $rownum;
}

$formatTime = $workbook->add_format(); 
$formatTime->set_italic();
$now = new \DateTime("now", \core_date::get_server_timezone_object());
$myxls->write_string($totalrownum + 2, 3, 'Thời điểm xuất báo cáo: '. date_format($now, 'd-m-Y H:i:s'), $formatTime);
$myxls->set_column($totalrownum + 2, 3, 14);

// Sending HTTP headers.
$workbook->send('Báo cáo thống kê năng lực lớp '. $course->fullname);
// Close the workbook.
$workbook->close();