<?php
require_once('../../config.php');
require_once('./functions.php');
require_once($CFG->dirroot . '/mod/giaoandientu/classes/form_send_file.php');

$id = required_param('id', PARAM_INT);

$sqlq = "SELECT `sr`.status, `w`.categoryid,`sr`.weekid, `w`.weekname, `sr`.userid, `w`.startdate, `w`.enddate FROM `".$CFG->prefix."lms_gadt_storereport` `sr` JOIN `".$CFG->prefix."lms_gadt_weeks` `w` ON `sr`.weekid = `w`.id  WHERE `sr`.id = " . $id;
$result = $DB->get_record_sql($sqlq);
$categoryid = $result->categoryid;
$userid = $result->userid;

if (!checkTeacherAccess($categoryid, $userid)) {
    print_error('accessdenied', 'admin');
}

if ($result->status != 0) {
    print_error('Báo cáo này đã được duyệt hoặc chờ duyệt. Vui lòng chờ tổ trưởng duyệt để nộp bản ghi mới.');
}

$now = (new DateTime("now", core_date::get_server_timezone_object()))->getTimestamp();
if ($result->startdate > $now || $result->enddate < $now) {
    print_error('Báo cáo chưa bắt đầu hoặc hết hạn!');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/mod/giaoandientu/gui_file.php');
$PAGE->set_title('Báo cáo giảng dạy');
$PAGE->set_heading('Nộp báo cáo giảng dạy');
echo $OUTPUT->header();

$mform = new form_send_file();

if ($mform->is_cancelled()) {

} else if ($fromform = $mform->get_data()) {
    $draftitemid = file_get_submitted_draft_itemid('giaovien');

    $itemid = (int)(date("Ymd") . rand(1,999));
    
    if ($draftitemid) {
        file_save_draft_area_files($draftitemid, $context->id, 'mod_giaoandientu', 'giaovien',
        $itemid, array('subdirs' => 0, 'maxbytes' => $maxbytes, 'maxfiles' => 1));
    }

    $currenttime = new DateTime("now", core_date::get_server_timezone_object());

    $storefileitem = $DB->get_record('lms_gadt_storereport', [
        'id' => $fromform->id
    ]);

    $storefileitem->status = 1;
    $storefileitem->timecreated = $currenttime->getTimestamp();
    $storefileitem->contextid = $context->id;
    $storefileitem->itemid = $itemid;

    $DB->update_record('lms_gadt_storereport', $storefileitem);

    // $categoryname = $DB->get_record('course_categories', [
    //     'id' => $categoryid
    // ])->name;
    $categoryname = '';
    getParentNameCategory($categoryid, $categoryname);
    $messageurl = new moodle_url('/mod/giaoandientu/view.php', [
        'categoryid' => $result->categoryid,
        'weekid' => $result->weekid
    ]);
    $message = 'Giáo viên đã nộp báo cáo mới.';
    $messagehtml = "<p>Giáo viên ". $USER->firstname ." ". $USER->lastname ." đã nộp báo cáo cho <b>". $result->weekname ."</b> thuộc danh mục: ". $categoryname .". <br> Click <a href='". $messageurl ."' style='text-decoration: underline;'>tại đây</a> để xem chi tiết./</p>";
    $managers = getManagerByCategoryid($categoryid);
    foreach ($managers as $manager) {
        sendMessageGadt($manager->id, 'Giáo viên nộp báo cáo', $message, $messagehtml, $messageurl);
    }
    
    $returnurl = new moodle_url('/mod/giaoandientu/xem_bao_cao.php', [
        'categoryid' => $result->categoryid
    ]);
    redirect($returnurl);

   
} else {
    $mform->display();
    echo "<div style='padding-left: 34px; margin-top: 34px;'><b>Lưu ý:</b> Sau khi nạp sẽ không thể chỉnh sửa. Chỉ khi tổ trưởng hủy mới có thể gửi bản mới.</div>";
}


echo $OUTPUT->footer();