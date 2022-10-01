<?php 

require_once("../../config.php");

$subjectManageId = required_param('subjectManageId', PARAM_INT);
$isGetAll = optional_param('action', '', PARAM_TEXT);

$courses = null;
if ($isGetAll === 'getall') {
    $sql = "SELECT id FROM `". $CFG->prefix ."course_categories` WHERE path LIKE '%/". $subjectManageId ."/%'";
    $childCategories = $DB->get_records_sql($sql);
    
    $condition = '';
    foreach ($childCategories as $category) {
        $condition .= $category->id . ',';
    }

    if ($condition === '') {
        echo json_encode([
            'message' => 'success',
            'data' => [],
        ]);
        die();
    }
    
    $condition = rtrim($condition, ",");
    $sql = 'SELECT id, fullname FROM `'. $CFG->prefix .'course` WHERE category IN ('. $condition .')';
    $courses = $DB->get_records_sql($sql);
} else {
    $courses = get_courses($subjectManageId);
}


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