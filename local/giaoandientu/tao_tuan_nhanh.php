<?php 
require_once("../../config.php");
require_once('./functions.php');
require_once($CFG->dirroot . '/local/giaoandientu/classes/form_create_auto_week.php');

$categoryid = required_param('categoryid', PARAM_INT);
$action = optional_param('action', null, PARAM_TEXT);

if (!checkManagerAccess($categoryid)) {
    print_error('accessdenied', 'admin');
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/giaoandientu/tao_tuan_nhanh.php', [
    'categoryid' => $categoryid
]);
$PAGE->set_title('Báo cáo giảng dạy');
$PAGE->set_heading('Tạo tuần tự động');
echo $OUTPUT->header();

$returnview = new moodle_url('/local/giaoandientu/view.php', [
    'categoryid' => $categoryid
]);

if ($action === null) {
    $mform = new form_create_auto();
    
    if ($mform->is_cancelled()) {
        redirect($returnview);
    } else if ($fromform = $mform->get_data()) {
        $currenttime = new DateTime("now", core_date::get_server_timezone_object());
        $timecreated = $currenttime->getTimestamp();
        $userid = $USER->id;
    
        $weekname = $fromform->weekname;
        $weekstart = (int)$fromform->weekstart;
        $weeksnumber = (int)$fromform->weeksnumber;
        $startdate = $fromform->startdate;
        $description = $fromform->description;
        $daysexpired = (int)$fromform->daysexpired;
        $distance = (int)$fromform->distance;
        $categoryid = (int)$fromform->categoryid;
    
        if ($weeksnumber > 5) {
            $returnurl = new moodle_url('/local/giaoandientu/tao_tuan_nhanh.php', [
                'categoryid' => $categoryid
            ]);
            print_error('Số tuần tạo trong một lần không được quá 5.', '', $returnurl);
        }

        $listweek = array();
    
        for ($i = 0; $i < $weeksnumber; $i++) {
            $adddays = $i * $distance;
            $datetime = new DateTime();
            $datetime->setTimestamp($startdate);
            $datetime->modify('+'. $adddays .' day');
            $startdatemodify = $datetime->getTimestamp();
            
            $datetime->setTimestamp($startdatemodify);
            $datetime->modify('+'. $daysexpired .' day');
            $enddatemodify = $datetime->getTimestamp();
    
    
            $datainsert = (object) [
                'weekname' => $weekname . ' ' . $weekstart,
                'userid' => $userid,
                'categoryid' => $categoryid,
                'startdate' => $startdatemodify,
                'enddate' => $enddatemodify,
                'timecreated' => $timecreated,
                'description' => $description,
            ];
    
            array_push($listweek, $datainsert);
    
            $weekstart++;
        }
    
        $urlcontinue = new moodle_url('/local/giaoandientu/tao_tuan_nhanh.php');
    
        $jsondatalistweeks = json_encode($listweek);
    
        echo $OUTPUT->render_from_template('local_giaoandientu/previewautocreateweeks', [
            'data' => $listweek,
            'urlcontinue' => $urlcontinue,
            'jsondatalistweeks' => $jsondatalistweeks,
            'categoryid' => $categoryid,
        ]);
    } else {
        $mform->display();
    }
} else if ($action === 'preview') {
    $courses = get_courses($categoryid);
    foreach ($courses as $key => $value) {
        $courses[$key]->shortname = explode('_', $value->shortname)[3];

        $courses[$key]->teachers = (array)[];
        $teachers = getTeachersByCourseid($value->id);
        foreach ($teachers as $teacher) {
            array_push($courses[$key]->teachers, $teacher->id);
        }
    }

    $courses = array_reduce($courses, function ($prevValue, $curValue) {
        foreach ($curValue->teachers as $key => $teacher) {
            foreach ($prevValue as $course) {
                if ($curValue->shortname === $course->shortname && in_array($teacher, $course->teachers)) {
                    unset($curValue->teachers[$key]);
                }
            }
        }
        
        array_push($prevValue, $curValue);
        return $prevValue;
    }, (array)[]);
    


    $jsonlistweeks = required_param('jsonlistweeks', PARAM_RAW);
    $listweeks = array_reverse(json_decode($jsonlistweeks));

    foreach ($listweeks as $insertrecord) {
        $weekid = $DB->insert_record('lms_gadt_weeks', $insertrecord);

        foreach ($courses as $course) {
            foreach ($course->teachers as $teacher) {
                $datainsert = (object) [
                    'weekid' => $weekid,
                    'courseid' => (int)$course->id,
                    'userid' => (int)$teacher,
                    'status' => 0
                ];
                $DB->insert_record('lms_gadt_storereport', $datainsert);
            }
        }
    }

    redirect($returnview, 'Tạo thành công!', null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->footer();