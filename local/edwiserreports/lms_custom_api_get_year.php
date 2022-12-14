<?php 

require_once("../../config.php");

$schoolid = required_param('schoolid', PARAM_INT);

$years = $DB->get_records('course_categories', [
    'parent' => $schoolid
], 'id DESC');
$data = [];
foreach ($years as $year) {
    $result = [
        'id' => $year->id,
        'name' => $year->name,
    ];
    array_push($data, $result);
}

$resData = [
    'message' => 'success',
    'data' => $data,
];
echo json_encode($resData);