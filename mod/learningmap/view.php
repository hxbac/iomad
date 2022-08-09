<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * View a learning map instance
 *
 * @package     mod_learningmap
 * @copyright   2021-2022, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'learningmap');

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/learningmap:view', $context);

$map = $DB->get_record('learningmap', ['id' => $cm->instance], '*', MUST_EXIST);

$PAGE->set_url(new moodle_url('/mod/learningmap/view.php', ['id' => $id]));
$PAGE->set_title(get_string('pluginname', 'mod_learningmap') . ' ' . $map->name);
$PAGE->set_heading($map->name);

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

echo $OUTPUT->header();

echo $OUTPUT->box(learningmap_get_learningmap($cm));

echo $OUTPUT->footer();
