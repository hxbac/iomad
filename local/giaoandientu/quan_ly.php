<?php 
require_once('../../config.php');
require_once('./functions.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/giaoandientu/quan_ly.php', [
    'categoryid' => $categoryid
]);
$PAGE->set_title('Báo cáo giảng dạy');
$PAGE->set_heading('Quản lý hoạt động danh mục');
echo $OUTPUT->header();

if (!checkPrincipal()) {
    print_error('accessdenied', 'admin');
}

$datarendercategories = [];
$category = $DB->get_records('course_categories', [
    'parent' => 0
]);
// $schoolname = [...$category][0]->name;
getCategoriesRenderManager($datarendercategories, $category);
array_shift($datarendercategories);

echo $OUTPUT->render_from_template('local_giaoandientu/quanly', [
    'categories' => $datarendercategories,
    // 'schoolname' => 'test'
]);

echo $OUTPUT->footer();