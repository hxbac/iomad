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

namespace report_rolessitemap;

use moodle_url;

/**
 * This helperclass contains the functions for collecting the data an let them rendered by mustache-templates.
 *
 * @package    report_rolessitemap
 * @copyright  2022 Andreas Schenkel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /**
     * Shows all categories and the assigned users with their roles up to a number of maxcounter users
     *
     * @return void
     * @throws \dml_exception
     */
    public function rendercategoriesandroles(): array {
        global $DB, $OUTPUT;
        $systemcontext = \context_system::instance();
        $roles = role_fix_names(get_all_roles(), $systemcontext, ROLENAME_ORIGINAL);
        $categorieslist = \core_course_category::make_categories_list();
        // Now populate $categorieslistandroles with the information to be rendered using mustach-template.
        $categorieslistandroles = [];
        $supportedroles = $this->get_supported_roles();
        if (empty($supportedroles)) {
            $supportedroles[] = "";
        }
        list($instatement, $params) = $DB->get_in_or_equal($supportedroles);
        foreach ($categorieslist as $categoryid => $categoryname) {
            $context = \context_coursecat::instance($categoryid);
            $contextid = $context->id;
            $sql = "SELECT id, contextid, roleid, userid FROM {role_assignments} " .
                " WHERE contextid = $contextid and roleid $instatement" .
                " ORDER BY contextid, roleid, userid";
            $roleassignments = $DB->get_records_sql($sql, $params);
            $roleassignmentsasarray = [];
            $oldshortname = "";
            $counter = 0;
            $maxcounter = get_config('report_rolessitemap', 'maxcounter');
            foreach ($roleassignments as $roleassignment) {
                $nextrole = false;
                if ($roles[$roleassignment->roleid]->shortname != $oldshortname) {
                    $oldshortname = $roles[$roleassignment->roleid]->shortname;
                    $nextrole = true;
                    $counter = 0;
                }
                if ($counter >= $maxcounter) {
                    continue;
                }
                $counter = $counter + 1;
                $roleassignmenteditingurl = new moodle_url('/admin/roles/assign.php',
                    array('contextid' => $context->id, 'roleid' => $roleassignment->roleid));
                $userprofileurl = new moodle_url('/user/profile.php',
                    array('userid' => $roleassignment->userid));
                $user = \core_user::get_user($roleassignment->userid, 'username, firstname, lastname');
                $roleassignmentsasarray[] = [
                    'nextrole' => $nextrole,
                    'roleid' => $roleassignment->roleid,
                    'roleassignmenteditingurl' => $roleassignmenteditingurl->out(false),
                    'shortname' => $roles[$roleassignment->roleid]->shortname,
                    'localname' => $roles[$roleassignment->roleid]->localname,
                    'userid' => $roleassignment->userid,
                    'userprofileurl' => $userprofileurl->out(false),
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname
                ];
            }
            $url = new moodle_url('/course/index.php', array('categoryid' => $categoryid));
            $categorieslistandroles[] = [
                'categoryid' => $categoryid,
                'categoryname' => $categoryname,
                'url' => $url,
                'roleassignmentsinthiscategory' => $roleassignmentsasarray
            ];
        }
        $data['categorieslistandroles'] = $categorieslistandroles;
        $data['pageheader'] = get_string('pageheader', 'report_rolessitemap');
        $data['maxcounter'] = $maxcounter;
        return $data;
    }

    /**
     * Gets ids of the selected roles that should be supported in this report.
     *
     * @return array of ids of the supported roles
     * @throws \dml_exception
     */
    public function get_supported_roles(): array {
        global $DB;
        $roles = $DB->get_records('role', null, 'id', 'id');
        $configuredsupportedroles = explode(',', get_config('report_rolessitemap', 'supportedroles'));
        $supportedroles = [];
        foreach ($roles as $role) {
            if (in_array($role->id, $configuredsupportedroles)) {
                $supportedroles[] = $role->id;
            }
        }
        return $supportedroles;
    }
}
