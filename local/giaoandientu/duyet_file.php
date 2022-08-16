<?php
require_once('../../config.php');
require_once('./functions.php');

$id = required_param('id', PARAM_INT);
$status = required_param('status', PARAM_INT);
$feedback = optional_param('feedback', 'Đã duyệt', PARAM_TEXT);

$sqlq = "SELECT * FROM `".$CFG->prefix."lms_gadt_storereport` `sr` JOIN `".$CFG->prefix."lms_gadt_weeks` `w` ON `sr`.weekid = `w`.id  WHERE `sr`.id = " . $id;
$categoryid = $DB->get_record_sql($sqlq)->categoryid;

if (!checkManagerAccess($categoryid)) {
    print_error('accessdenied', 'admin');
}

$storefilerecord = $DB->get_record('lms_gadt_storereport', [
    'id' => $id
]);

$messagestatus = 'Đã duyệt';
$storefilerecord->feedback = $feedback;
if ($status == 1) {
    $messagestatus = "Đã hủy. Bạn phải nộp báo cáo mới.";
    $storefilerecord->status = -1;
    $DB->insert_record('lms_gadt_storereport', (object)[
        'weekid' => $storefilerecord->weekid,
        'userid' => $storefilerecord->userid,
        'courseid' => $storefilerecord->courseid,
        'status' => 0
    ]);
}
$DB->update_record('lms_gadt_storereport', $storefilerecord);

$week = $DB->get_record('lms_gadt_weeks', [
    'id' => $storefilerecord->weekid
]);
$weekname = $week->weekname;
$categoryname = '';
getParentNameCategory($week->categoryid, $categoryname);
$messageurl = new moodle_url('/local/giaoandientu/lich_su.php', [
    'weekid' => $storefilerecord->weekid,
    'courseid' => $storefilerecord->courseid,
    'userid' => $storefilerecord->userid
]);
$message = 'Tổ trưởng đã duyệt báo cáo.';
$messagehtml = "<p>Tổ trưởng ". $USER->firstname ." ". $USER->lastname ." đã duyệt báo cáo của bạn cho ". $weekname ." thuộc danh mục: ". $categoryname .".<br>Trạng thái: ". $messagestatus ." <br>Click <a href='". $messageurl ."' style='text-decoration: underline;'>tại đây</a> để xem chi tiết./</p>";
sendMessageGadt($storefilerecord->userid, 'Duyệt báo cáo', $message, $messagehtml, $messageurl);

$returnurl = new moodle_url('/local/giaoandientu/view.php', [
    'categoryid' => $categoryid
]);
redirect($returnurl);