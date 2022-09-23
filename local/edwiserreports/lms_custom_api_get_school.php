<?php 

require_once("../../config.php");

$schools = $DB->get_records('course_categories', [
    'parent' => 0
], 'id DESC');
$data = [];
foreach ($schools as $school) {
    if ($school->id == 1) {
        continue;
    }
    $result = [
        'id' => $school->id,
        'name' => $school->name,
    ];
    array_push($data, $result);
}

$resData = [
    'message' => 'success',
    'data' => $data,
];
echo json_encode($resData);