<?php
require_once('../../config.php');
require_once('./functions.php');

$categoryid = required_param('categoryid', PARAM_INT);
if (!checkPrincipal($categoryid)) {
    print_error('accessdenied', 'admin');
    exit;
}

$returnurl = new moodle_url('/mod/giaoandientu/quan_ly.php', [
    'categoryid' => $categoryid
]);

$subjectofcategory = $DB->get_record('lms_gadt_subjects', [
    'categoryid' => $categoryid
]);

if ($subjectofcategory == false) {
    $DB->insert_record('lms_gadt_subjects', [
        'categoryid' => $categoryid
    ]);
} else {
    $DB->delete_records('lms_gadt_subjects', [
        'categoryid' => $categoryid
    ]);
}

redirect($returnurl);