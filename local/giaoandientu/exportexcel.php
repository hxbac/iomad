<?php 
require_once('../../config.php');
require_once($CFG->libdir . '/excellib.class.php');
require_once('./functions.php');

$categoryid = required_param('categoryid', PARAM_INT);
$category = $DB->get_record('course_categories', [
    'id' => $categoryid
]);

$currenttime = new DateTime("now", core_date::get_server_timezone_object());

$filename = 'Thống kê nộp báo cáo tổ '. $category->name;
$sql = "SELECT id, weekname FROM `". $CFG->prefix ."lms_gadt_weeks` WHERE categoryid = ". $categoryid ." AND startdate < ". $currenttime->getTimestamp();
$weeksOfCategory = $DB->get_records_sql($sql);

$sql = "SELECT sr.userid, sr.courseid FROM `". $CFG->prefix ."lms_gadt_storereport` sr JOIN `". $CFG->prefix ."lms_gadt_weeks` w ON w.id = sr.weekid WHERE w.categoryid = ". $categoryid;
$listTeacherOfCategory = $DB->get_records_sql($sql);
$teacherAndCourse = array();
foreach ($listTeacherOfCategory as $record) {
    if (!in_array($record, $teacherAndCourse)) {
        array_push($teacherAndCourse, $record);
    }
}

$i = 1;
$headerexport = ['STT', 'Giáo viên', 'Môn học', 'Tổng số giáo án', 'Giáo án đã nộp', 'Giáo án chưa nộp'];
foreach ($weeksOfCategory as $week) {
    array_push($headerexport, $week->weekname);
}

$dataexport = array();
foreach ($teacherAndCourse as $record) {
    $teacherid = $record->userid;
    $courseid = $record->courseid;
    $item = array();

    // STT 
    array_push($item, $i);

    // Teacher fullname 
    $infoteacher = $DB->get_record('user', [
        'id' => $teacherid
    ]);
    array_push($item, $infoteacher->firstname .' '. $infoteacher->lastname);

    // Course name 
    $coursename = $DB->get_record('course', [
        'id' => $courseid
    ])->fullname;
    array_push($item, $coursename);

    $countTotalReport = 0;
    $countReportAccepted = 0;
    foreach ($weeksOfCategory as $week) {
        $status = 0;
        $sql = "SELECT * FROM `". $CFG->prefix ."lms_gadt_storereport` WHERE weekid = ". $week->id ." AND userid = ". $teacherid ." AND courseid = ". $courseid ." AND status = 1";
        $isAccepted = $DB->record_exists_sql($sql);
        $countTotalReport++;
        if ($isAccepted) {
            $status = 1;
            $countReportAccepted++;
        }
        array_push($item, $status);
    }
    array_splice( $item, 3, 0, [$countTotalReport, $countReportAccepted, $countTotalReport - $countReportAccepted] );
    

    array_push($dataexport, $item);
    $i++;
}

$workbook = new MoodleExcelWorkbook("-");
$myxls = $workbook->add_worksheet($category->name);

$format = $workbook->add_format();
$format->set_align('center');
$format->set_bold(1);
$myxls->write_string(0, 0, 'Thống kê nộp báo cáo giảng dạy', $format);
$myxls->write_string(1, 0, 'Tổ: '. $category->name, $format);
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
$myxls->set_column(2, 2, 22);
$weeknum = count($weeksOfCategory);
for ($i = 0; $i < $weeknum; $i++) {
    $myxls->set_column($i + 3, $i + 3, 10);
}

$myxls->set_column(3, 3, 12, $formatWrapText);
$myxls->set_column(4, 4, 12, $formatWrapText);
$myxls->set_column(5, 5, 12, $formatWrapText);


$format->set_align('left');
$format->set_bold(0);
foreach ($dataexport as $rownum => $data) {
    $rownum += 5;
    foreach ($data as $colnum => $item) {
        if (is_numeric($item)) {
            $format->set_align('center');
        } else {
            $format->set_align('left');
        }
        $myxls->write_string($rownum, $colnum, $item, $format);
    }
    $totalrownum = $rownum;
}

$formatTime = $workbook->add_format(); 
$formatTime->set_italic();
$now = new \DateTime("now", \core_date::get_server_timezone_object());
$myxls->write_string($totalrownum + 2, 6, 'Thời điểm xuất báo cáo: '. date_format($now, 'd-m-Y H:i:s'), $formatTime);

// Sending HTTP headers.
$workbook->send($filename);
// Close the workbook.
$workbook->close();
