<?php 
require_once("../../config.php");
require_once("./functions.php");

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/giaoandientu/index.php');
$PAGE->set_title('Báo cáo giảng dạy');
$PAGE->set_heading('Quản lý kế hoạch giảng dạy');
echo $OUTPUT->header();

$principalRoleId = $DB->get_field('role', 'id', [
    'shortname' => 'hieutruong'
]);
$isPrincipal = $DB->record_exists('role_assignments', [
    'userid' => $USER->id,
    'roleid' => $principalRoleId,
    'contextid' => 1
]);

$categoriesActive = $DB->get_records('lms_gadt_subjects', [], 'categoryid DESC');

$categoriesActive = array_filter([...$categoriesActive], function ($category) {
    global $DB;
    $categoryinfo = $DB->get_record('course_categories', [
        'id' => $category->categoryid
    ]);

    if (!$categoryinfo->id) {
        $DB->delete_records('lms_gadt_subjects', [
            'id' => $category->id
        ]);
        return false;
    }
    return true;
});

$checkmanagerrecord = false;
if ($isPrincipal) {
    foreach ($categoriesActive as $categoryrecord) {
        $checkmanagerrecord = true;
        $categoryrecord->parentname = '';
        $categoryinfo = $DB->get_record('course_categories', [
            'id' => $categoryrecord->categoryid
        ]);
        $categoryrecord->name = $categoryinfo->name;
        $categoryrecord->url = new moodle_url('/local/giaoandientu/view.php', [
            'categoryid' => $categoryrecord->categoryid
        ]);
        $categoryrecord->urlthongke = new moodle_url('/local/giaoandientu/thongke.php', [
            'categoryid' => $categoryrecord->categoryid
        ]);
        
        $categoryrecord-> solanconlai = false;
    
        getParentNameCategory($categoryinfo->parent, $categoryrecord->parentname);
    }
    echo $OUTPUT->render_from_template('local_giaoandientu/index', [
        'categorymanager' => [...$categoriesActive ?? []],
        'checkmanagerrecord' => $checkmanagerrecord,
        'rendercountreportformanager' => false
    ]);
    
    echo $OUTPUT->footer();
} else {

    $categoriesSendFilter = array_filter([...$categoriesActive], function ($category) {
        global $USER;
        $courses = get_courses($category->categoryid);
        foreach ($courses as $course) {
            $teachers = getTeachersByCourseid($course->id);
            foreach($teachers as $teacher) {
                if ($teacher->id == $USER->id) {
                    return true;
                }
            }
        }
        return false;
    });

    $checksendrecord = false;
    foreach ($categoriesSendFilter as $categoryrecord) {
        $checksendrecord = true;
        $categoryrecord->parentname = '';
        $categoryinfo = $DB->get_record('course_categories', [
            'id' => $categoryrecord->categoryid
        ]);
        $categoryrecord->name = $categoryinfo->name;
        $categoryrecord->url = new moodle_url('/local/giaoandientu/xem_bao_cao.php', [
            'categoryid' => $categoryrecord->categoryid
        ]);
        $now = (new DateTime("now", core_date::get_server_timezone_object()))->getTimestamp();
        $sqlq = "SELECT COUNT(*) as `solanconlai` FROM `".$CFG->prefix."lms_gadt_storereport` `sr` JOIN `".$CFG->prefix."lms_gadt_weeks` `w` ON `sr`.weekid = `w`.id  WHERE `status` = 0 AND `sr`.`userid` = " . $USER->id . " AND `categoryid` = " . $categoryrecord->categoryid ." AND w.startdate < ". $now ." AND w.enddate > ". $now;
        $storerecordofuser = $DB->get_record_sql($sqlq);
        $categoryrecord-> solanconlai = $storerecordofuser->solanconlai;

        getParentNameCategory($categoryinfo->parent, $categoryrecord->parentname);
    }

    $categoriesManagerFilter = array_filter([...$categoriesActive], function ($category) {
        global $USER;
        $managers = getManagerByCategoryid($category->categoryid);
        foreach($managers as $manager) {
            if ($manager->id == $USER->id) {
                return true;
            }
        }
        return false;
    });

    $categoriesManagerFilter = json_decode(json_encode($categoriesManagerFilter));

    $checkmanagerrecord = false;
    foreach ($categoriesManagerFilter as $categoryrecord) {
        $checkmanagerrecord = true;
        $categoryrecord->parentname = '';
        $categoryinfo = $DB->get_record('course_categories', [
            'id' => $categoryrecord->categoryid
        ]);
        $categoryrecord->name = $categoryinfo->name;
        $categoryrecord->url = new moodle_url('/local/giaoandientu/view.php', [
            'categoryid' => $categoryrecord->categoryid
        ]);
        $categoryrecord->urlthongke = new moodle_url('/local/giaoandientu/thongke.php', [
            'categoryid' => $categoryrecord->categoryid
        ]);
        $sqlq = "SELECT COUNT(*) as `solanconlai` FROM `".$CFG->prefix."lms_gadt_storereport` `sr` JOIN `".$CFG->prefix."lms_gadt_weeks` `w` ON `sr`.weekid = `w`.id  WHERE `status` = 1 AND `feedback` is null AND `categoryid` = " . $categoryrecord->categoryid;
        $storerecordofuser = $DB->get_record_sql($sqlq);
        $categoryrecord-> solanconlai = $storerecordofuser->solanconlai;

        getParentNameCategory($categoryinfo->parent, $categoryrecord->parentname);
    }

    echo $OUTPUT->render_from_template('local_giaoandientu/index', [
        'categorysend' => [...$categoriesSendFilter ?? []],
        'checksendrecord' => $checksendrecord,
        'categorymanager' => [...$categoriesManagerFilter ?? []],
        'checkmanagerrecord' => $checkmanagerrecord,
        'rendercountreportformanager' => true
    ]);

    echo $OUTPUT->footer();
}