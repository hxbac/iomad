<?php 

require_once("../../config.php");

$yearid = required_param('yearId', PARAM_INT);

$semesters = $DB->get_records('course_categories', [
    'parent' => $yearid,
], 'id DESC');
$data = [];
foreach ($semesters as $semester) {
    $result = [
        'id' => $semester->id,
        'name' => $semester->name,
    ];
    array_push($data, $result);
}

$resData = [
    'message' => 'success',
    'data' => $data,
];
echo json_encode($resData);