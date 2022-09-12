<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file is used to call any registered externallib function in Moodle.
 *
 * It will process more than one request and return more than one response if required.
 * It is recommended to add webservice functions and re-use this script instead of
 * writing any new custom ajax scripts.
 *
 * @since Moodle 2.9
 * @package core
 * @copyright 2015 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
// Services can declare 'readonlysession' in their config located in db/services.php, if not present will default to false.
define('READ_ONLY_SESSION', true);

if (!empty($_GET['nosessionupdate'])) {
    define('NO_SESSION_UPDATE', true);
}

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/externallib.php');

define('PREFERRED_RENDERER_TARGET', RENDERER_TARGET_GENERAL);

$arguments = '';
$cacherequest = false;
if (defined('ALLOW_GET_PARAMETERS')) {
    $arguments = optional_param('args', '', PARAM_RAW);
    $cachekey = optional_param('cachekey', '', PARAM_INT);
    if ($cachekey && $cachekey > 0 && $cachekey <= time()) {
        $cacherequest = true;
    }
}

// Either we are not allowing GET parameters or we didn't use GET because
// we did not pass a cache key or the URL was too long.
if (empty($arguments)) {
    $arguments = file_get_contents('php://input');
}

$requests = json_decode($arguments, true);

if ($requests === null) {
    $lasterror = json_last_error_msg();
    throw new coding_exception('Invalid json in request: ' . $lasterror);
}
$responses = array();

// Defines the external settings required for Ajax processing.
$settings = external_settings::get_instance();
$settings->set_file('pluginfile.php');
$settings->set_fileurl(true);
$settings->set_filter(true);
$settings->set_raw(false);

$haserror = false;
foreach ($requests as $request) {
    $response = array();
    $methodname = clean_param($request['methodname'], PARAM_ALPHANUMEXT);
    $index = clean_param($request['index'], PARAM_INT);
    $args = $request['args'];

    $response = external_api::call_external_function($methodname, $args, true);
    $responses[$index] = $response;
    if ($response['error']) {
        // Do not process the remaining requests.
        $haserror = true;
        break;
    }
}

if ($cacherequest && !$haserror) {
    // 90 days only - based on Moodle point release cadence being every 3 months.
    $lifetime = 60 * 60 * 24 * 90;

    header('Expires: '. gmdate('D, d M Y H:i:s', time() + $lifetime) .' GMT');
    header('Pragma: ');
    header('Cache-Control: public, max-age=' . $lifetime . ', immutable');
    header('Accept-Ranges: none');
}

function getTeacherOfCourse($courseId) {
    global $DB;
    
    $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
    $context = context_course::instance($courseId);
    $teachers = get_role_users($role->id, $context);
    foreach ($teachers as $teacher) {
        return $teacher;
    }
}

function isTeacherOfCourse($courseid) {
    global $DB, $USER;

    $roleteacher = $DB->get_record('role', [
        'shortname' => 'editingteacher'
    ]);
    
    $context = CONTEXT_COURSE::instance((int)$courseid);
    
    return $DB->record_exists('role_assignments', [
        'roleid' => $roleteacher->id,
        'contextid' => $context->id,
        'userid' => $USER->id
    ]);
}

$lmsTypeRequest = optional_param('info', '', PARAM_TEXT);

switch ($lmsTypeRequest) {
    case 'core_course_get_enrolled_courses_by_timeline_classification':
        foreach ($responses as $key => $value) {
            foreach ($responses[$key]['data']['courses'] as $key3 => $value3) {
                $courseid = $responses[$key]['data']['courses'][$key3]['id'];

                if (isTeacherOfCourse($courseid)) {
                    $responses[$key]['data']['courses'][$key3]['hasprogress'] = true;
                    $responses[$key]['data']['courses'][$key3]['progress'] = 100;
                } else {
                    $teacher = getTeacherOfCourse($courseid);
                    if (isloggedin() && !isguestuser() && $teacher->picture > 0) {
                        $usercontext = context_user::instance($teacher->id, IGNORE_MISSING);
                        $courseimage = moodle_url::make_pluginfile_url($usercontext->id, 'user', 'icon', null, '/', "f3")
                        . '?rev=' . $teacher->picture;
    
                        $responses[$key]['data']['courses'][$key3]['courseimage'] = $courseimage;
                    }
                }

            }
        }

        break;
    case 'core_course_get_recent_courses':
        foreach ($responses as $key => $value) {
            foreach ($responses[$key]['data'] as $key2 => $value2) {
                $courseid = $responses[$key]['data'][$key2]['id'];
                
                if (isTeacherOfCourse($courseid)) {
                    $responses[$key]['data'][$key2]['hasprogress'] = true;
                    $responses[$key]['data'][$key2]['progress'] = 100;
                } else {
                    $teacher = getTeacherOfCourse($courseid);
                    if (isloggedin() && !isguestuser() && $teacher->picture > 0) {
                        $usercontext = context_user::instance($teacher->id, IGNORE_MISSING);
                        $courseimage = moodle_url::make_pluginfile_url($usercontext->id, 'user', 'icon', null, '/', "f3")
                        . '?rev=' . $teacher->picture;
    
                        $responses[$key]['data'][$key2]['courseimage'] = $courseimage;
                    }
                }
            }
        }
        break;
}

echo json_encode($responses);