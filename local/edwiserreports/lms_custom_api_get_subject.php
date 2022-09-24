<?php 

require_once("../../config.php");

$subjectManageId = required_param('subjectManageId', PARAM_INT);

$sql = "SELECT id FROM `". $CFG->prefix ."course_categories` WHERE path LIKE '%". $subjectManageId ."%'";
$childCategories = $DB->get_records_sql($sql);

$condition = '';
foreach ($childCategories as $category) {
    $condition .= $category->id . ',';
}

$condition = rtrim($condition, ",");
$sql = 'SELECT id, fullname FROM `'. $CFG->prefix .'course` WHERE category IN ('. $condition .')';
$courses = $DB->get_records_sql($sql);

$data = [];
foreach ($courses as $course) {
    $result = [
        'id' => $course->id,
        'name' => $course->fullname,
    ];
    array_push($data, $result);
}

$resData = [
    'message' => 'success',
    'data' => $data,
];
echo json_encode($resData);