<?php
require_once("../../config.php");

$schools = $DB->get_record('course_categories', [
    'parent' => 0
]);
echo gettype($schools);
// echo json_encode($schools);
die();
