<?php 

require_once("../../config.php");

$semesterId = required_param('semesterId', PARAM_INT);

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

$tbmrole = $DB->get_record('role', [
    'shortname' => 'truongbomon'
]);

$istbm = $DB->record_exists('role_assignments', [
    'userid' => $USER->id,
    'roleid' => $tbmrole->id
]);

$subjectManages = $DB->get_records('course_categories', [
    'parent' => $semesterId,
], 'id DESC');

if ($istbm) {
    $subjectManages = array_filter([...$subjectManages], function ($category) {
        global $DB, $USER, $tbmrole;

        $context = CONTEXT_COURSECAT::instance($category->id);
        return $DB->record_exists('role_assignments', [
            'roleid' => $tbmrole->id,
            'contextid' => $context->id,
            'userid' => $USER->id,
        ]);
    });
}

$data = [];
foreach ($subjectManages as $item) {
    $result = [
        'id' => $item->id,
        'name' => $item->name,
    ];
    array_push($data, $result);
}

$resData = [
    'message' => 'success',
    'data' => $data,
];
echo json_encode($resData);