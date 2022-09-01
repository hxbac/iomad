<?php 
require_once("../../config.php");

$courseid = required_param('courseid', PARAM_INT);

require_login();

$role = $DB->get_record('role', array('shortname' => 'editingteacher'));
$context = context_course::instance($courseid);
$result = get_role_users($role->id, $context);

$teacher = [];

foreach ($result as $item) {
    $teacher = $item;
    break;
}

$response = [];

if (is_enrolled($context, $USER->id, '', true)) {
    $response = [
        'message' => 'success',
        'data' => $teacher
    ];
}

echo json_encode($response);
