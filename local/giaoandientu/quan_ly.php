<?php 
require_once('../../config.php');
require_once('./functions.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/giaoandientu/quan_ly.php');
$PAGE->set_title('Báo cáo giảng dạy');
$PAGE->set_heading('Cấu hình');
$PAGE->requires->css('/local/giaoandientu/assets/bootstrap-treeview.min.css');
$PAGE->requires->css('/local/giaoandientu/assets/fontawesome.min.css');
echo $OUTPUT->header();

if (!checkPrincipal()) {
    print_error('accessdenied', 'admin');
}

$datarendercategories = [];
$categories = $DB->get_records('course_categories', [
    'parent' => 0
], 'id DESC');
getCategoriesRenderManager($datarendercategories, $categories);

$PAGE->requires->js('/local/giaoandientu/assets/bootstrap-treeview.min.js');
echo $OUTPUT->render_from_template('local_giaoandientu/quanly', [
    'datarendercategories' => json_encode($datarendercategories),
]);

echo $OUTPUT->footer();