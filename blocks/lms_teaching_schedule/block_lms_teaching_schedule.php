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
 * Block definition class for the block_lms_teaching_schedule plugin.
 *
 * @package   block_lms_teaching_schedule
 * @copyright Year, You Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// defined('MOODLE_INTERNAL') || die();
// require(__DIR__.'/../../../config.php');
class block_lms_teaching_schedule extends block_base
{

    /**
     * Initialises the block.
     *
     * @return void
     */
    public function init()
    {
        $this->title = "Giáo án điện tử";
    }

    /**
     * Gets the block contents.
     *
     * @return string The block HTML.
     */
    public function get_content()
    {
        // require_once($CFG->libdir . '/pagelib.php');
        global $OUTPUT, $PAGE, $CFG, $USER, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        $schoolsOfPrincipal = block_lms_teaching_schedule::getSchoolsOfPrincipal();
        $this->content->text = "<div>Quản lý hoạt động danh mục</div>";
        foreach ($schoolsOfPrincipal as $school) {
            $urlmanager = new moodle_url('/local/giaoandientu/quan_ly.php', [
                'categoryid' => $school->id
            ]);
            $this->content->text = "<a href='".$urlmanager."'>". $school->name ."</a>";
        }
        
        $sqlq = "SELECT COUNT(*) as `solanconlai` FROM `".$CFG->prefix."lms_gadt_storereport` WHERE `status` = 0 AND `userid` = " . $USER->id;
        $storerecordofuser = $DB->get_record_sql($sqlq);
        if ($storerecordofuser->solanconlai == 0 ) {
            $this->content->text .= "<p class='text-center'>Hiện không có nhiệm vụ nào!</p>";
        } else {
            $this->content->text .= "<p class='text-center'>Có " . $storerecordofuser->solanconlai . " nhiệm vụ chưa hoàn thành!</p>";
        }
        $urlgadt = new moodle_url('/local/giaoandientu');
        $this->content->text .= "<a href='" . $urlgadt . "'>Đi tới trang quản lý giáo án</a>";
        

        // $this->content->text = $OUTPUT->render_from_template('block_lms_teaching_schedule/giaovien', []);
        $PAGE->requires->js(new moodle_url($CFG->wwwroot . 'blocks/lms_teaching_schedule/tree.js'));
        return $this->content;
    }

    public function getSchoolsOfPrincipal()
    {
        global $OUTPUT, $PAGE, $CFG, $USER, $DB;

        $result = [];
        $schools = block_lms_teaching_schedule::getAllSchools();
        foreach ($schools as $school) {
            foreach ($school->principals as $principal) {
                if ($principal->id == $USER->id) {
                    array_push($result, $school);
                }
            }
        }
        return $result;
    }

    public function getAllSchools() {
        global $DB;
        
        $schools = $DB->get_records('course_categories', [
            'parent' => 0
        ]);
        foreach ($schools as $school) {
            $principals = block_lms_teaching_schedule::getPrincipals($school->id);
            $school->principals = [];
            foreach ($principals as $principal) {
                array_push($school->principals, $principal);
            }
        }

        return $schools;
    }

    function getPrincipals($categoryid) {
        global $DB;
        $role = $DB->get_record('role', [
            'shortname' => 'hieutruong'
        ]);
        $context = context_coursecat::instance($categoryid);
        $principals = get_role_users($role->id, $context);
        return $principals;
    }

    /**
     * Defines in which pages this block can be added.
     *
     * @return array of the pages where the block can be added.
     */
    public function applicable_formats()
    {
        return [
            'admin' => false,
            'site-index' => true,
            'course-view' => true,
            'mod' => true,
            'my' => true,
        ];
    }
}
