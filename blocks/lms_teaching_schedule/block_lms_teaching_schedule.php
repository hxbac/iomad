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
        $this->title = "Kế hoạch bài dạy";
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

        if (!$this->canViewBlock()) {
            return null;
        }


        if ($this->checkPrincipal()) {
            $urlmanager = new moodle_url('/local/giaoandientu/quan_ly.php');
            $this->content->text = "<a href='".$urlmanager."'>Cấu hình</a>";
        }

        $this->content->text = "<div><i class='fa-thin fa-book-user'></i></div>";
        
        $sqlq = "SELECT COUNT(*) as `solanconlai` FROM `".$CFG->prefix."lms_gadt_storereport` WHERE `status` = 0 AND `userid` = " . $USER->id;
        $storerecordofuser = $DB->get_record_sql($sqlq);
        if ($storerecordofuser->solanconlai == 0 ) {
            $this->content->text .= "<p class='text-center'>Hiện không có nhiệm vụ nào!</p>";
        } else {
            $this->content->text .= "<p class='text-center'>Có " . $storerecordofuser->solanconlai . " nhiệm vụ chưa hoàn thành!</p>";
        }
        $urlgadt = new moodle_url('/local/giaoandientu');
        $this->content->text .= "<a href='" . $urlgadt . "'>Đi tới trang Quản lý kế hoạch bài dạy</a>";
        

        // $this->content->text = $OUTPUT->render_from_template('block_lms_teaching_schedule/giaovien', []);
        return $this->content;
    }

    function canViewBlock() {
        global $DB, $USER;

        if (is_siteadmin()) {
            return true;
        }

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'hieutruong']);
        $isprincipal = $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]);
        
        if ($isprincipal) {
            return true;
        }

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'truongbomon']);
        $isTBM = $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]);
        
        if ($isTBM) {
            return true;
        }

        $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        $isteacheranywhere = $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]);
        
        if ($isteacheranywhere) {
            return true;
        }

        return false;
    }

    function checkPrincipal() {
        global $DB, $USER;
        $role = $DB->get_record('role', [
            'shortname' => 'hieutruong'
        ]);
        $context = context_system::instance();
        $principals = get_role_users($role->id, $context);

        foreach ($principals as $principal) {
            if ($principal->id === $USER->id) {
                return true;
            }
        }

        return false;
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
