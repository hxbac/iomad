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

defined('MOODLE_INTERNAL') || die();

// Default session time limit in seconds.
define('BLOCK_DEDICATION_DEFAULT_SESSION_LIMIT', 60 * 60);
// Ignore sessions with a duration less than defined value in seconds.
define('BLOCK_DEDICATION_IGNORE_SESSION_TIME', 59);
// Default regeneration time in seconds.
define('BLOCK_DEDICATION_DEFAULT_REGEN_TIME', 60 * 15);

/**
 * Generate dedication reports based in passed params.
 */
class block_dedication_manager {

    protected $course;
    protected $mintime;
    protected $maxtime;
    protected $limit;

    public function __construct($course, $mintime, $maxtime, $limit) {
        $this->course = $course;
        $this->mintime = $mintime;
        $this->maxtime = $maxtime;
        $this->limit = $limit;
    }

    public function get_students_dedication($students) {
        global $DB;

        $rows = array();

        $where = 'courseid = :courseid AND userid = :userid AND timecreated >= :mintime AND timecreated <= :maxtime';
        $params = array(
            'courseid' => $this->course->id,
            'userid' => 0,
            'mintime' => $this->mintime,
            'maxtime' => $this->maxtime
        );

        $perioddays = ($this->maxtime - $this->mintime) / DAYSECS;

        foreach ($students as $user) {
            $daysconnected = array();
            $params['userid'] = $user->id;
            $logs = block_dedication_utils::get_events_select($where, $params);

            if ($logs) {
                $previouslog = array_shift($logs);
                $previouslogtime = $previouslog->time;
                $sessionstart = $previouslog->time;
                $dedication = 0;
                $daysconnected[date('Y-m-d', $previouslog->time)] = 1;

                foreach ($logs as $log) {
                    if (($log->time - $previouslogtime) > $this->limit) {
                        $dedication += $previouslogtime - $sessionstart;
                        $sessionstart = $log->time;
                    }
                    $previouslogtime = $log->time;
                    $daysconnected[date('Y-m-d', $log->time)] = 1;
                }
                $dedication += $previouslogtime - $sessionstart;
            } else {
                $dedication = 0;
            }
            $groups = groups_get_user_groups($this->course->id, $user->id);
            $group = !empty($groups) && !empty($groups[0]) ? $groups[0][0] : 0;
            $rows[] = (object) array(
                'user' => $user,
                'groupid' => $group,
                'dedicationtime' => $dedication,
                'connectionratio' => round(count($daysconnected) / $perioddays, 2),
            );
        }

        return $rows;
    }

    public function download_students_dedication($rows) {
        $groups = groups_get_all_groups($this->course->id);

        $headers = array(
            array(
                get_string('sincerow', 'block_dedication'),
                userdate($this->mintime),
                get_string('torow', 'block_dedication'),
                userdate($this->maxtime),
                get_string('perioddiffrow', 'block_dedication'),
                format_time($this->maxtime - $this->mintime),
                $this->course->id,
            ),
            array(
                get_string('firstname'),
                get_string('lastname'),
                get_string('group'),
                get_string('dedicationrow', 'block_dedication') . ' (' . get_string('mins') . ')',
                get_string('dedicationrow', 'block_dedication'),
                get_string('connectionratiorow', 'block_dedication'),
            ),
        );

        foreach ($rows as $index => $row) {
            $rows[$index] = array(
                $row->user->firstname,
                $row->user->lastname,
                isset($groups[$row->groupid]) ? $groups[$row->groupid]->name : '',
                round($row->dedicationtime / MINSECS),
                block_dedication_utils::format_dedication($row->dedicationtime),
                $row->connectionratio,
            );
        }

        $rows = array_merge($headers, $rows);

        return block_dedication_utils::generate_download("{$this->course->shortname}_dedication", $rows);
    }

    public function get_user_dedication($user, $simple = false) {
        $where = 'courseid = :courseid AND userid = :userid AND timecreated >= :mintime AND timecreated <= :maxtime';
        $params = array(
            'courseid' => $this->course->id,
            'userid' => $user->id,
            'mintime' => $this->mintime,
            'maxtime' => $this->maxtime
        );
        $logs = block_dedication_utils::get_events_select($where, $params);

        if ($simple) {
            // Return total dedication time in seconds.
            $total = 0;

            if ($logs) {
                $previouslog = array_shift($logs);
                $previouslogtime = $previouslog->time;
                $sessionstart = $previouslogtime;

                foreach ($logs as $log) {
                    if (($log->time - $previouslogtime) > $this->limit) {
                        $dedication = $previouslogtime - $sessionstart;
                        $total += $dedication;
                        $sessionstart = $log->time;
                    }
                    $previouslogtime = $log->time;
                }
                $dedication = $previouslogtime - $sessionstart;
                $total += $dedication;
            }

            return $total;

        } else {
            // Return user sessions with details.
            $rows = array();

            if ($logs) {
                $previouslog = array_shift($logs);
                $previouslogtime = $previouslog->time;
                $sessionstart = $previouslogtime;
                $ips = array($previouslog->ip => true);

                foreach ($logs as $log) {
                    if (($log->time - $previouslogtime) > $this->limit) {
                        $dedication = $previouslogtime - $sessionstart;

                        // Ignore sessions with a really short duration.
                        if ($dedication > BLOCK_DEDICATION_IGNORE_SESSION_TIME) {
                            $rows[] = (object) array('start_date' => $sessionstart, 'dedicationtime' => $dedication, 'ips' => array_keys($ips));
                            $ips = array();
                        }
                        $sessionstart = $log->time;
                    }
                    $previouslogtime = $log->time;
                    $ips[$log->ip] = true;
                }

                $dedication = $previouslogtime - $sessionstart;

                // Ignore sessions with a really short duration.
                if ($dedication > BLOCK_DEDICATION_IGNORE_SESSION_TIME) {
                    $rows[] = (object) array('start_date' => $sessionstart, 'dedicationtime' => $dedication, 'ips' => array_keys($ips));
                }
            }

            return $rows;
        }
    }

    /**
     * Downloads user dedication with passed data.
     * @param $user
     * @return MoodleExcelWorkbook
     */
    public function download_user_dedication($user) {
        $headers = array(
            array(
                get_string('sincerow', 'block_dedication'),
                userdate($this->mintime),
                get_string('torow', 'block_dedication'),
                userdate($this->maxtime),
                get_string('perioddiffrow', 'block_dedication'),
                format_time($this->maxtime - $this->mintime),
            ),
            array(''),
            array(
                get_string('firstname'),
                get_string('lastname'),
                get_string('sessionstart', 'block_dedication'),
                get_string('dedicationrow', 'block_dedication') . ' ' . get_string('secs'),
                get_string('sessionduration', 'block_dedication'),
                'IP',
            )
        );

        $rows = $this->get_user_dedication($user);
        foreach ($rows as $index => $row) {
            $rows[$index] = array(
                $user->firstname,
                $user->lastname,
                userdate($row->start_date),
                $row->dedicationtime,
                block_dedication_utils::format_dedication($row->dedicationtime),
                implode(', ', $row->ips),
            );
        }

        $rows = array_merge($headers, $rows);

        return block_dedication_utils::generate_download("{$this->course->shortname}_dedication", $rows);
    }

}

/**
 * Utils functions used by block dedication.
 */
class block_dedication_utils {

    public static $logstores = array('logstore_standard', 'logstore_legacy');

    /**
     * Return formatted events from logstores.
     * @param string $selectwhere
     * @param array $params
     * @return array
     */
    public static function get_events_select($selectwhere, array $params) {
        $return = array();

        static $allreaders = null;

        if (is_null($allreaders)) {
            $allreaders = get_log_manager()->get_readers();
        }

        $processedreaders = 0;

        foreach (self::$logstores as $name) {
            if (isset($allreaders[$name])) {
                $reader = $allreaders[$name];
                $events = $reader->get_events_select($selectwhere, $params, 'timecreated ASC', 0, 0);
                foreach ($events as $event) {
                    // Note: see \core\event\base to view base class of event.
                    $obj = new stdClass();
                    $obj->time = $event->timecreated;
                    $obj->ip = $event->get_logextra()['ip'];
                    $return[] = $obj;
                }
                if (!empty($events)) {
                    $processedreaders++;
                }
            }
        }

        // Sort mixed array by time ascending again only when more of a reader has added events to return array.
        if ($processedreaders > 1) {
            usort($return, function($a, $b) {
                return $a->time > $b->time;
            });
        }

        return $return;
    }

    /**
     * Formats time based in Moodle function format_time($totalsecs).
     * @param int $totalsecs
     * @return string
     */
    public static function format_dedication($totalsecs) {
        $totalsecs = abs($totalsecs);

        $str = new stdClass();
        $str->hour = get_string('hour');
        $str->hours = get_string('hours');
        $str->min = get_string('min');
        $str->mins = get_string('mins');
        $str->sec = get_string('sec');
        $str->secs = get_string('secs');

        $hours = floor($totalsecs / HOURSECS);
        $remainder = $totalsecs - ($hours * HOURSECS);
        $mins = floor($remainder / MINSECS);
        $secs = round($remainder - ($mins * MINSECS), 2);

        $ss = ($secs == 1) ? $str->sec : $str->secs;
        $sm = ($mins == 1) ? $str->min : $str->mins;
        $sh = ($hours == 1) ? $str->hour : $str->hours;

        $ohours = '';
        $omins = '';
        $osecs = '';

        if ($hours) {
            $ohours = $hours . ' ' . $sh;
        }
        if ($mins) {
            $omins = $mins . ' ' . $sm;
        }
        if ($secs) {
            $osecs = $secs . ' ' . $ss;
        }

        if ($hours) {
            return trim($ohours . ' ' . $omins);
        }
        if ($mins) {
            return trim($omins . ' ' . $osecs);
        }
        if ($secs) {
            return $osecs;
        }
        return get_string('none');
    }

    /**
     * @param string[] $ips
     * @return string
     */
    public static function format_ips($ips) {
        return implode(', ', array_map('block_dedication_utils::link_ip', $ips));
    }

    /**
     * Generates a linkable ip.
     * @param string $ip
     * @return string
     */
    public static function link_ip($ip) {
        return html_writer::link("http://en.utrace.de/?query=$ip", $ip, array('target' => '_blank'));
    }

    /**
     * Return table styles based on current theme.
     * @return array
     */
    public static function get_table_styles() {
        global $PAGE;

        // Twitter Bootstrap styling.
        $is_bootstrap_theme = ($PAGE->theme->name === 'boost') || count(array_intersect(array('boost', 'bootstrapbase'), $PAGE->theme->parents)) > 0;
        if ($is_bootstrap_theme) {
            $styles = array(
                'table_class' => 'table table-bordered table-hover table-sm table-condensed table-dedication',
                'header_style' => 'background-color: #333; color: #fff;'
            );
        } else {
            $styles = array(
                'table_class' => 'table-dedication',
                'header_style' => ''
            );
        }

        return $styles;
    }

    /**
     * Generates generic Excel file for download.
     * @param string $downloadname
     * @param array $rows
     * @return MoodleExcelWorkbook
     * @throws coding_exception
     */
    public static function generate_download($downloadname, $rows) {
        global $CFG, $DB;

        require_once($CFG->libdir . '/excellib.class.php');

        $workbook = new MoodleExcelWorkbook(clean_filename($downloadname));

        $myxls = $workbook->add_worksheet(get_string('pluginname', 'block_dedication'));

        $rowcount = 0;
        $lmsAction = optional_param('action', '', PARAM_TEXT);
        if ($lmsAction === 'all') {
            $infoCourse = array_shift($rows);
            $courseid = array_pop($infoCourse);
            $header = array_shift($rows);
            array_splice($header, 3, 1);
            array_unshift($header, 'STT');
    
            $course = $DB->get_record('course', [
                'id' => $courseid
            ]);
            $pathCategory = $DB->get_field('course_categories', 'path',[
                'id' => $course->category
            ]);
            $pathCategoryArr = explode('/', $pathCategory);
            $yearName = $DB->get_field('course_categories', 'name', [
                'id' => $pathCategoryArr[2] ?? ''
            ]) ?? '';
            $semesterName = $DB->get_field('course_categories', 'name', [
                'id' => $pathCategoryArr[3] ?? ''
            ]) ?? '';

            $teacherroleid = $DB->get_field('role', 'id',[
                'shortname' => 'editingteacher'
            ]);
            $contextCourse = \context_course::instance($courseid);
            $teacherid = $DB->get_field('role_assignments', 'userid', [
                'roleid' => $teacherroleid,
                'contextid' => $contextCourse->id
            ]);
            $teacher = $DB->get_record('user', [
                'id' => $teacherid
            ]);

            $formatCenterBold = $workbook->add_format();
            $formatCenterBold->set_align('center');
            $formatCenterBold->set_bold(1);

            $myxls->write_string(0, 0, 'THỐNG KÊ THỜI GIAN HỌC', $formatCenterBold);
            $myxls->merge_cells(0, 0, 0, 6);

            $myxls->write_string(2, 1, 'Năm học:');
            $myxls->write_string(2, 2, $yearName);

            $myxls->write_string(3, 1, 'Học kì:');
            $myxls->write_string(3, 2, $semesterName);

            $myxls->write_string(4, 1, 'Môn:');
            $myxls->write_string(4, 2, $course->fullname);

            $myxls->write_string(5, 1, 'Giáo viên:');
            $myxls->write_string(5, 2, $teacher->firstname .' '. $teacher->lastname);

            $myxls->write_string(6, 1, 'Từ: '. $infoCourse[1]);
            $myxls->write_string(7, 1, 'Đến: '. $infoCourse[3]);
            $myxls->write_string(8, 1, 'Môn học đã bắt đầu được: '. $infoCourse[5]);

            $formatCenterBold->set_border(1);
            foreach ($header as $colnum => $val) {
                $myxls->write_string(10, $colnum, $val, $formatCenterBold);
            }

            $rowcount = 11;

            // Set width column 
            $myxls->set_column(0, 0, 5);
            $myxls->set_column(1, 1, 25);
            $myxls->set_column(2, 2, 13);
            $myxls->set_column(3, 3, 13);
            $myxls->set_column(4, 4, 16);
            $myxls->set_column(5, 5, 16);
        }

        $i = 1;
        $format = $workbook->add_format();
        $format->set_border(1);
        foreach ($rows as $row) {
            if ($lmsAction === 'all') {
                array_splice($row, 3, 1);
                array_unshift($row, $i);
                $i++;
            }
            foreach ($row as $index => $content) {
                if (is_numeric($content)) {
                    $format->set_align('center');
                } else {
                    $format->set_align('left');
                }
                $myxls->write($rowcount, $index, $content, $format);
            }
            $rowcount++;
        }

        $workbook->close();

        return $workbook;
    }
}
