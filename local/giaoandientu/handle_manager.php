<?php
require_once('../../config.php');
require_once('./functions.php');

$categoryid = required_param('categoryid', PARAM_INT);

if (!checkPrincipalAccessChild($categoryid)) {
    print_error('accessdenied', 'admin');
}

$listChild = array();
getAllChildOfCategory($categoryid, $listChild);

$returnurl = new moodle_url('/local/giaoandientu/quan_ly.php');

$isCategoryAdded = $DB->record_exists('lms_gadt_subjects', [
    'categoryid' => $categoryid
]);

if ($isCategoryAdded) {
    $DB->delete_records('lms_gadt_subjects', [
        'categoryid' => $categoryid
    ]);
    foreach ($listChild as $childCategoryid) {
        $DB->delete_records('lms_gadt_subjects', [
            'categoryid' => $childCategoryid
        ]);
    }
} else {
    $DB->insert_record('lms_gadt_subjects', [
        'categoryid' => $categoryid
    ]);
    foreach ($listChild as $childCategoryid) {
        $isChildExist = $DB->record_exists('lms_gadt_subjects', [
        'categoryid' => $childCategoryid
        ]);
        if (!$isChildExist) {
            $DB->insert_record('lms_gadt_subjects', [
                'categoryid' => $childCategoryid
            ]);
        }
    }
}

redirect($returnurl);