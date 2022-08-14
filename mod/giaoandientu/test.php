<?php
require_once("../../config.php");

$schools = $DB->get_records('course_categories', [
    'parent' => 0
]);
foreach ($schools as $school) {
    $role = $DB->get_record('role', [
        'shortname' => 'hieutruong'
    ]);
    $context = context_coursecat::instance($school->id);
    $principals = get_role_users($role->id, $context);
    $school->principals = [];
    foreach ($principals as $principal) {
        array_push($school->principals, $principal);
    }
}

echo json_encode($schools);
die();
