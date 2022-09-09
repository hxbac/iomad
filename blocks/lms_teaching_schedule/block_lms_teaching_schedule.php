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

        $this->content->text = '<div style="width: 25%; margin: 0 auto 14px auto;"><svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
        viewBox="0 0 434.951 434.951" style="enable-background:new 0 0 434.951 434.951;" xml:space="preserve">
   <g>
       <path style="fill:none;" d="M75.148,85.73v15.355v225.281c0,8.467,6.889,15.355,15.355,15.355h269.297V85.73h-46.477H75.148z"/>
       <path style="fill:none;" d="M336.725,61.73c-1.653-4.234-2.57-8.836-2.57-13.648V37.648c0-4.813,0.917-9.414,2.57-13.648H90.504
           c-8.467,0-15.355,6.889-15.355,15.355V61.73h238.176H336.725z"/>
       <path style="fill:#6C757D;" d="M124.955,401.59c0,0,25.626-15.426,25.646-15.434c3.666-2.084,8.321-2.35,12.179,0l25.338,15.434
           v-35.867h-63.162V401.59z"/>
       <path style="fill:#6C757D;" d="M371.803,61.73c-7.526,0-13.648-6.123-13.648-13.648V37.648c0-7.525,6.122-13.648,13.648-13.648
           c6.627,0,12-5.373,12-12c0-6.627-5.373-12-12-12c-0.001,0-0.001,0-0.001,0h-0.001H90.504C68.803,0,51.148,17.654,51.148,39.355
           v287.012c0,21.699,17.654,39.355,39.356,39.355h10.451v57.229c0,4.338,2.342,8.338,6.123,10.461
           c1.827,1.027,3.854,1.539,5.877,1.539c2.165,0,4.328-0.586,6.243-1.752l37.31-22.76l37.366,22.76
           c1.915,1.166,4.078,1.752,6.243,1.752c2.023,0,4.05-0.512,5.877-1.539c3.782-2.123,6.122-6.123,6.122-10.461v-57.229h159.684
           c6.628,0,12-5.373,12-12V73.75c0-0.008,0.002-0.014,0.002-0.02C383.803,67.104,378.43,61.73,371.803,61.73z M75.148,39.355
           C75.148,30.889,82.037,24,90.504,24h246.221c-1.653,4.234-2.57,8.836-2.57,13.648v10.434c0,4.813,0.917,9.414,2.57,13.648h-23.4
           H75.148V39.355z M188.117,401.59l-25.338-15.434c-3.857-2.35-8.513-2.084-12.179,0c-0.02,0.008-25.646,15.434-25.646,15.434
           v-35.867h63.162V401.59z M359.801,341.723H90.504c-8.467,0-15.355-6.889-15.355-15.355V101.086V85.73h238.176h46.477V341.723z"/>
   </g>
   <g>
   </g>
   <g>
   </g>
   <g>
   </g>
   <g>
   </g>
   <g>
   </g>
   <g>
   </g>
   <g>
   </g>
   <g>
   </g>
   <g>
   </g>
   <g>
   </g>
   <g>
   </g>
   <g>
   </g>
   <g>
   </g>
   <g>
   </g>
   <g>
   </g>
   </svg></div>';
        
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
