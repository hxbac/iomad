<?php 

require_once("../../config.php");

$subjectManageId = required_param('subjectManageId', PARAM_INT);

// $principalrole = $DB->get_record('role', [
//     'shortname' => 'hieutruong'
// ]);
// $isPrincipal = ($DB->get_record('role_assignments', [
//     'roleid' => $principalrole->id,
//     'userid' => $USER->id
// ]));

// if (!isloggedin() && (is_siteadmin() || !$isPrincipal)) {
//     $resData = [
//         'message' => 'error',
//         'data' => []
//     ];
//     echo json_encode($resData);
//     exit;
// }
$courses = get_courses($subjectManageId);

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