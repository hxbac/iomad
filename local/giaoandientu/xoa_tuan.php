<?php 
require_once('../../config.php');
require_once('./functions.php');

$id = required_param('id', PARAM_INT);

$week = $DB->get_record('lms_gadt_weeks', [
    'id' => $id
]);

if (!checkManagerAccess($week->categoryid)) {
    print_error('accessdenied', 'admin');
}

$returnurl = new moodle_url('/local/giaoandientu/view.php', [
    'categoryid' => $week->categoryid
]);

$DB->delete_records('lms_gadt_weeks', [
    'id' => $id
]);

$storerecords = $DB->get_records('lms_gadt_storereport', [
    'weekid' => $id
]);

foreach ($storerecords as $item) {
    if ($item->itemid != null) {
        $DB->delete_records('files', [
            'component' => 'local_giaoandientu',
            'itemid' => $item->itemid
        ]);
    }
}

$DB->delete_records('lms_gadt_storereport', [
    'weekid' => $id
]);

redirect($returnurl, 'Xóa thành công', null, \core\output\notification::NOTIFY_SUCCESS);