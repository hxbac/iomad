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
            $this->content->text = "<a href='".$urlmanager."'><svg style='height: 16px; width: 16px; transform: translateY(-2px); margin-right: 6px;' xmlns='http://www.w3.org/2000/svg' id='Outline' viewBox='0 0 24 24' width='512' height='512'><path d='M12,8a4,4,0,1,0,4,4A4,4,0,0,0,12,8Zm0,6a2,2,0,1,1,2-2A2,2,0,0,1,12,14Z'/><path d='M21.294,13.9l-.444-.256a9.1,9.1,0,0,0,0-3.29l.444-.256a3,3,0,1,0-3-5.2l-.445.257A8.977,8.977,0,0,0,15,3.513V3A3,3,0,0,0,9,3v.513A8.977,8.977,0,0,0,6.152,5.159L5.705,4.9a3,3,0,0,0-3,5.2l.444.256a9.1,9.1,0,0,0,0,3.29l-.444.256a3,3,0,1,0,3,5.2l.445-.257A8.977,8.977,0,0,0,9,20.487V21a3,3,0,0,0,6,0v-.513a8.977,8.977,0,0,0,2.848-1.646l.447.258a3,3,0,0,0,3-5.2Zm-2.548-3.776a7.048,7.048,0,0,1,0,3.75,1,1,0,0,0,.464,1.133l1.084.626a1,1,0,0,1-1,1.733l-1.086-.628a1,1,0,0,0-1.215.165,6.984,6.984,0,0,1-3.243,1.875,1,1,0,0,0-.751.969V21a1,1,0,0,1-2,0V19.748a1,1,0,0,0-.751-.969A6.984,6.984,0,0,1,7.006,16.9a1,1,0,0,0-1.215-.165l-1.084.627a1,1,0,1,1-1-1.732l1.084-.626a1,1,0,0,0,.464-1.133,7.048,7.048,0,0,1,0-3.75A1,1,0,0,0,4.79,8.992L3.706,8.366a1,1,0,0,1,1-1.733l1.086.628A1,1,0,0,0,7.006,7.1a6.984,6.984,0,0,1,3.243-1.875A1,1,0,0,0,11,4.252V3a1,1,0,0,1,2,0V4.252a1,1,0,0,0,.751.969A6.984,6.984,0,0,1,16.994,7.1a1,1,0,0,0,1.215.165l1.084-.627a1,1,0,1,1,1,1.732l-1.084.626A1,1,0,0,0,18.746,10.125Z'/></svg>Cấu hình</a>";
        }

        $now = (new DateTime("now", core_date::get_server_timezone_object()))->getTimestamp();
        $sqlq = "SELECT COUNT(*) as `solanconlai` FROM `".$CFG->prefix."lms_gadt_storereport` sr LEFT JOIN `".$CFG->prefix."lms_gadt_weeks` w ON w.id = sr.weekid WHERE sr.`status` = 0 AND sr.`userid` = " . $USER->id ." AND w.startdate < ". $now ." AND w.enddate > ". $now;
        $storerecordofuser = $DB->get_record_sql($sqlq);
        if ($storerecordofuser->solanconlai == 0 ) {
            $this->content->text .= "<p class='text-center'>Hiện không có nhiệm vụ nào!</p>";
        } else {
            $this->content->text .= "<p class='text-center'>Có " . $storerecordofuser->solanconlai . " nhiệm vụ chưa hoàn thành!</p>";
        }
        $urlgadt = new moodle_url('/local/giaoandientu');
        $this->content->text .= "<a href='" . $urlgadt . "'><svg style='height: 16px; width: 16px; transform: translateY(-2px); margin-right: 6px;' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='512' height='512'><g id='_01_align_center' data-name='01 align center'><path d='M5,19H9.414L23.057,5.357a3.125,3.125,0,0,0,0-4.414,3.194,3.194,0,0,0-4.414,0L5,14.586Zm2-3.586L20.057,2.357a1.148,1.148,0,0,1,1.586,0,1.123,1.123,0,0,1,0,1.586L8.586,17H7Z'/><path d='M23.621,7.622,22,9.243V16H16v6H2V3A1,1,0,0,1,3,2H14.758L16.379.379A5.013,5.013,0,0,1,16.84,0H3A3,3,0,0,0,0,3V24H18.414L24,18.414V7.161A5.15,5.15,0,0,1,23.621,7.622ZM18,21.586V18h3.586Z'/></g></svg>Đi tới trang Quản lý kế hoạch bài dạy</a>";
        

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
