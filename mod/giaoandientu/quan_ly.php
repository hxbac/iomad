<?php 
require_once('../../config.php');
require_once('./functions.php');

$categoryid = required_param('categoryid', PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/mod/giaoandientu/quan_ly.php', [
    'categoryid' => $categoryid
]);
$PAGE->set_title('Báo cáo giảng dạy');
$PAGE->set_heading('Quản lý hoạt động danh mục');
echo $OUTPUT->header();

if (!checkPrincipal($categoryid)) {
    print_error('accessdenied', 'admin');
}

$datarendercategories = [];
$category = $DB->get_records('course_categories', [
    'id' => $categoryid
]);
$schoolname = [...$category][0]->name;
getCategoriesRenderManager($datarendercategories, $category);
array_shift($datarendercategories);

echo $OUTPUT->render_from_template('mod_giaoandientu/quanly', [
    'categories' => $datarendercategories,
    'schoolname' => $schoolname
]);

echo $OUTPUT->footer();