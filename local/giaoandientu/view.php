<?php
require_once('../../config.php');
require_once('./functions.php');
require_once($CFG->dirroot . '/local/giaoandientu/classes/form_create_week.php');

$categoryid = required_param('categoryid', PARAM_INT);

if (!checkManagerAccess($categoryid)) {
    print_error('accessdenied', 'admin');
}

$weeks = $DB->get_records('lms_gadt_weeks', [
    'categoryid' => $categoryid
], 'id ASC');

$currenttime = new DateTime("now", core_date::get_server_timezone_object());
$nowtimestamp = $currenttime->getTimestamp();
$sqlraw = 'SELECT * FROM `'. $CFG->prefix . 'lms_gadt_weeks` WHERE `categoryid` = '. $categoryid .' AND `startdate` < '. $nowtimestamp .' AND `enddate` > '. $nowtimestamp .' ORDER BY id ASC'; 
$currWeek = $DB->get_record_sql($sqlraw);
$currWeekid = $currWeek->id;
if (!$currWeekid) {
    $weekstemp = [...$weeks];
    $currWeekid = (array_pop($weekstemp))->id;
}
$weekid = optional_param('weekid', $currWeekid ?? 30, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/giaoandientu/view.php', [
    'categoryid' => $categoryid
]);
$PAGE->set_title('Báo cáo giảng dạy');
$PAGE->set_heading('Duyệt kế hoạch bài dạy');
echo $OUTPUT->header();
$urlcreateweek = new moodle_url('/local/giaoandientu/tao_tuan.php', (array) [
    'categoryid' => $categoryid
]);

$urlupdateweek = new moodle_url('/local/giaoandientu/tao_tuan.php', (array) [
    'categoryid' => $categoryid,
    'weekid' => $weekid
]);

$urldeleteweek = new moodle_url('/local/giaoandientu/xoa_tuan.php', (array) [
    'id' => $weekid
]);

$urlautocreateweek = new moodle_url('/local/giaoandientu/tao_tuan_nhanh.php', (array) [
    'categoryid' => $categoryid
]);

$category = $DB->get_record('course_categories', [
    'id' => $categoryid
]);
$breadcrumbobj = (object) [];

$breadcrumbobj->parentname = '';
getParentNameCategory($category->parent, $breadcrumbobj->parentname);
$breadcrumbobj->urlgiaoandientu = new moodle_url('/local/giaoandientu/');
$breadcrumbobj->name = $category->name;

// Lấy danh sách tuần của category chỉ định
// Sửa id của dánh sách tuần thành url để gán vào thẻ a xem theo tuần đó
$listidweek = (array) [];
foreach ($weeks as $week) {
    $week->id = new moodle_url('/local/giaoandientu/view.php', [
        'categoryid' => $categoryid,
        'weekid' => $week->id
    ]);
    array_push($listidweek, (array)$week);
}

$listteachersendfile = $DB->get_records('lms_gadt_storereport', [
    'weekid' => $weekid
]);

$datarenderteachers = [];

foreach ($listteachersendfile as $recordstore) {
    if ($recordstore->status != -1) {
        $giaovien = $DB->get_record('user', [
            'id' => $recordstore->userid
        ]);
        $tengiaovien = $giaovien->firstname . ' ' . $giaovien->lastname;
        
        $datateacher = (object) [
            'name' => $tengiaovien
        ];
        $datateacher->coursename = $DB->get_record('course', [
            'id' => $recordstore->courseid
        ])->fullname;
        $datateacher->urlhistory = new moodle_url('/local/giaoandientu/lich_su.php', [
            'weekid' => $weekid,
            'courseid' => $recordstore->courseid,
            'userid' => $giaovien->id
        ]);
        $datateacher->status = $recordstore->status;
        if ($recordstore->status == 1) {
            $datateacher->urlbrowsefile = new moodle_url('/local/giaoandientu/duyet_file.php', [
                'id' => $recordstore->id
            ]);
            $datateacher->timecreated = $recordstore->timecreated;
            $urldownload = '';
            $fs = get_file_storage();
            $files = $fs->get_area_files($recordstore->contextid, 'local_giaoandientu', 'giaovien', $recordstore->itemid, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
            foreach ($files as $file) {
                $filename = $file->get_filename();
                $urldownload = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                $file->get_filearea(),$file->get_itemid(), $file->get_filepath(), $filename, true);
            }
            $datateacher->urldownloadfile = '<a href="'. $urldownload .'">Tải xuống</a>';
            
            if ($recordstore->feedback == null) {
                $datateacher->feedback = true;
            } else {
                $datateacher->feedback = false;
            }
        } else if ($recordstore->status == 0) {
            $datateacher->timecreated = false;
            $datateacher->urldownloadfile = 'Chưa gửi';
        }
        array_push($datarenderteachers, $datateacher);
    }
}

$weekname = $DB->get_record('lms_gadt_weeks', [
    'id' => $weekid
])->weekname;

echo $OUTPUT->render_from_template('local_giaoandientu/totruong', [
    'urltaotuan' => $urlcreateweek,
    'urlupdateweek' => $urlupdateweek,
    'urldeleteweek' => $urldeleteweek,
    'urlautocreateweek' => $urlautocreateweek,
    'listweek' => $listidweek,
    'datarenderteachers' => $datarenderteachers,
    'weekname' => $weekname,
    'breadcrumbobj' => $breadcrumbobj
]);

echo $OUTPUT->footer();
