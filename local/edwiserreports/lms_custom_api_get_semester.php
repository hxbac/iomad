<?php 

require_once("../../config.php");

$yearid = required_param('yearId', PARAM_INT);
$principalrole = $DB->get_record('role', [
    'shortname' => 'hieutruong'
]);
$isPrincipal = ($DB->get_record('role_assignments', [
    'roleid' => $principalrole->id,
    'userid' => $USER->id
]));

if (!isloggedin() && is_siteadmin() || !$isPrincipal) {
    $resData = [
        'message' => 'error',
        'data' => []
    ];
    echo json_encode($resData);
    exit;
}

$semesters = $DB->get_records('course_categories', [
    'parent' => $yearid,
]);
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