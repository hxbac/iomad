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
 * Administrative settings
 *
 * @package    report_rolessitemap
 * @copyright  2022 Andreas Schenkel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Add a link in the menu reports.
// Users with capability report/rolessitemap:view' in systemcontext.
// Siteadmin has this capability by default because siteadmin has ALL capabilitys.
$ADMIN->add('reports', new admin_externalpage('reportrolessitemap', get_string('pluginname', 'report_rolessitemap'),
                                              "$CFG->wwwroot/report/rolessitemap/index.php", 'report/rolessitemap:view'));

$settings = new admin_settingpage('report_rolessitemap_settings', new lang_string('pluginname', 'report_rolessitemap'));

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox(
        'report_rolessitemap/isactive',
        get_string('isactive', 'report_rolessitemap'),
        get_string('configisactive', 'report_rolessitemap'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'report_rolessitemap/isactiveforsiteadmin',
        get_string('isactiveforsiteadmin', 'report_rolessitemap'),
        get_string('configisactiveforsiteadmin', 'report_rolessitemap'),
        0
    ));

    global $DB;
    $systemcontext = \context_system::instance();
    $roles = role_fix_names(get_all_roles(), $systemcontext, ROLENAME_ORIGINAL);
    $allroles = [];
    foreach ($roles as $role) {
        $allroles[$role->id] = $role->id . " - " . $role->shortname . " - " . $role->localname;
    }
    $settings->add(new admin_setting_configmultiselect('report_rolessitemap/supportedroles',
        get_string('supportedroles', 'report_rolessitemap'),
        get_string('supportedroles_desc', 'report_rolessitemap'),
        array_keys($allroles), $allroles));

    $options = array(
        1  => '1',
        2  => '2',
        3  => '3',
        4  => '4',
        5  => '5',
        6  => '6',
        7  => '7',
        8  => '8',
        9  => '9',
        10 => '10',
        20 => '20',
        30 => '30',
        40 => '40',
        50 => '50',
        100 => '100',
        150 => '150',
        200 => '200',
        300 => '300',
        400 => '400',
        500 => '500',
        1000 => '1000'
    );
    $settings->add(
        new admin_setting_configselect(
            'report_rolessitemap/maxcounter',
            get_string('maxcounter', 'report_rolessitemap'),
            get_string('maxcounter_desc', 'report_rolessitemap'),
            100,
            $options
        )
    );

}
