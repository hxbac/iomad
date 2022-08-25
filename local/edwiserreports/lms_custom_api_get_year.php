<?php 

require_once("../../config.php");

$principalrole = $DB->get_record('role', [
    'shortname' => 'hieutruong'
]);
$isPrincipal = ($DB->get_record('role_assignments', [
    'roleid' => $principalrole->id,
    'userid' => $USER->id
]));

if (!isloggedin() && (is_siteadmin() || !$isPrincipal)) {
    $resData = [
        'message' => 'error',
        'data' => []
    ];
    echo json_encode($resData);
    exit;
}

$years = $DB->get_records('course_categories', [
    'parent' => 0
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