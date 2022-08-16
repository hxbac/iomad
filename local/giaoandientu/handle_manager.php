<?php
require_once('../../config.php');
require_once('./functions.php');

$categoryid = required_param('categoryid', PARAM_INT);

if (!checkPrincipalAccessChild($categoryid)) {
    print_error('accessdenied', 'admin');
}

$school =  getSchoolByChildCategoryid($categoryid);
$returnurl = new moodle_url('/local/giaoandientu/quan_ly.php', [
    'categoryid' => $school->id
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