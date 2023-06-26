<?php 

require_once("../config.php");
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->dirroot."/group/lib.php");

$courseid = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

//get enrolment instance (manual and student)
$instances = enrol_get_instances($courseid, false);
$enrolment = "";
foreach ($instances as $instance) {
    if ($instance->enrol === 'manual') {
        $enrolment = $instance;
        break;
    }
}

//get enrolment plugin
$manual = enrol_get_plugin('manual');
$context = CONTEXT_COURSE::instance($courseid);
$user = $DB->get_record('user', [
    'id' => $userid
]);
if(is_enrolled($context, $user)) {
    echo 'user enrolled';
} else {
    $manual->enrol_user($enrolment, $user->id, $enrolment->roleid, time());
    // $manual->enrol_user($enrolment, $user->id, $enrolment->roleid, time());
    echo 'enroll user successfully';
}

if (groups_add_member(40, $user)) {
    echo 'user added into group';
} else {
    echo 'error';
}