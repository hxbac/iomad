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

require_once($CFG->dirroot.'/grade/export/lib.php');

class grade_export_xls extends grade_export {

    public $plugin = 'xls';

    /**
     * Constructor should set up all the private variables ready to be pulled
     * @param object $course
     * @param int $groupid id of selected group, 0 means all
     * @param stdClass $formdata The validated data from the grade export form.
     */
    public function __construct($course, $groupid, $formdata) {
        parent::__construct($course, $groupid, $formdata);

        // Overrides.
        $this->usercustomfields = true;
    }

    /**
     * To be implemented by child classes
     */
    public function print_grades() {
        global $CFG;
        require_once($CFG->dirroot.'/lib/excellib.class.php');

        $export_tracking = $this->track_exports();

        $strgrades = get_string('grades');

        // If this file was requested from a form, then mark download as complete (before sending headers).
        \core_form\util::form_download_complete();

        // Calculate file name
        $shortname = format_string($this->course->shortname, true, array('context' => context_course::instance($this->course->id)));
        $downloadfilename = clean_filename("$shortname $strgrades.xls");
        // Creating a workbook
        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers
        $workbook->send($downloadfilename);
        // Adding the worksheet
        $myxls = $workbook->add_worksheet($strgrades);

        // Print names of all the fields
        $profilefields = grade_helper::get_user_profile_fields($this->course->id, $this->usercustomfields);
        foreach ($profilefields as $id => $field) {
          	//thanhlv, thanpt-start
            //$myxls->write_string(0, $id, $field->fullname);          
          	$name_column = $field->fullname;
            $myxls->write_string(0, $id, $name_column, style_Text($workbook, 1, 1));
          	array_push($withLength_arr,mb_strlen($name_column)); // add column in arr
          	//thanhlv, thanpt-end
        }
        $pos = count($profilefields);
        if (!$this->onlyactive) {
            $myxls->write_string(0, $pos++, get_string("suspended"));
        }
        foreach ($this->columns as $grade_item) {
            foreach ($this->displaytype as $gradedisplayname => $gradedisplayconst) {
              	//thanhlv, thanpt-start
                //$myxls->write_string(0, $pos++, $this->format_column_name($grade_item, false, $gradedisplayname));
              	$name_column = $this->format_column_name($grade_item, false, $gradedisplayname); // Than 
                $myxls->write_string(0, $pos++, short_Text($name_column),style_Text($workbook,1,1,true)); // than edit
              	//$myxls->write_string(0, $pos++, str_replace( '(Thực)', '', $this->format_column_name($grade_item, false, $gradedisplayname)));
              	//thanhlv, thanpt-end
            }
            // Add a column_feedback column
            if ($this->export_feedback) {
                $myxls->write_string(0, $pos++, $this->format_column_name($grade_item, true));
            }
        }
      	//thanhlv-start
        // Last downloaded column header.
        //$myxls->write_string(0, $pos++, get_string('timeexported', 'gradeexport_xls'));
		//thanhlv-end
        // Print all the lines of data.
        $i = 0;
        $geub = new grade_export_update_buffer();
        $gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
        $gui->require_active_enrolment($this->onlyactive);
        $gui->allow_user_custom_fields($this->usercustomfields);
        $gui->init();
        while ($userdata = $gui->next_user()) {
          	//thanhlv, thanpt-start
          	$count_cloumn = 0; // Column number;
          	//thanhlv, thanpt-end
            $i++;
            $user = $userdata->user;

            foreach ($profilefields as $id => $field) {
                $fieldvalue = grade_helper::get_user_field_value($user, $field);
              	//thanhlv, thanpt-start
                //$myxls->write_string($i, $id, $fieldvalue);
              	$myxls->write_string($i, $id, $fieldvalue, style_Text($workbook, 0, 1));//set style front three first column
                //get max length column text
              	if($withLength_arr[$count_cloumn] < mb_strlen($fieldvalue)){
                      $withLength_arr[$count_cloumn] = mb_strlen($fieldvalue);    
                }
                $count_cloumn++;
              	//thanhlv, thanpt-end
            }
            $j = count($profilefields);
            if (!$this->onlyactive) {
                $issuspended = ($user->suspendedenrolment) ? get_string('yes') : '';
                $myxls->write_string($i, $j++, $issuspended);
            }
            foreach ($userdata->grades as $itemid => $grade) {
                if ($export_tracking) {
                    $status = $geub->track($grade);
                }
                foreach ($this->displaytype as $gradedisplayconst) {
                    $gradestr = $this->format_grade($grade, $gradedisplayconst);
                    if (is_numeric($gradestr)) {
                      	//thanhlv-start
                        //$myxls->write_number($i, $j++, $gradestr);
                      	$myxls->write_number($i, $j++, $gradestr, style_Text($workbook, 0, 1));
                      	//thanhlv-end
                    } else {
                      	//thanhlv-start
                        //$myxls->write_string($i, $j++, $gradestr);
                      	$myxls->write_string($i, $j++, $gradestr, style_Text($workbook, 0, 1));
                      	//thanhlv-end
                    }
                }
                // writing feedback if requested
                if ($this->export_feedback) {
                    $myxls->write_string($i, $j++, $this->format_feedback($userdata->feedbacks[$itemid], $grade));
                }
            }
          	//thanhlv-start
            // Time exported.
            //$myxls->write_string($i, $j++, time());
          	//thanhlv-end
        }
      	//thanhlv-start
      	for($i = 0 ; $i<count($withLength_arr) ; $i++){
            $myxls->set_column($i,$i,$withLength_arr[$i]+1);
        }	
      	//thanhlv-end
        $gui->close();
        $geub->close();

    /// Close the workbook
        $workbook->close();

        exit;
    }
}

//thanhlv, thanpt-start
function style_Text($workbook,$bold = 0,$border = 0,$wrap = false){
    $format = $workbook->add_format();
    $format->set_bold($bold);
    $format->set_border($border);
    if($wrap) $format->set_text_wrap();  
    return $format;
}

function short_Text($text){
    $text = str_replace("(Thực)","",$text);
    $text = str_replace("[Deletion in progress]","",$text);
    $str_ex = explode(":",$text);
    return count($str_ex) > 1 ? str_replace($str_ex[0].':',"",$text) : $text;
}
//thanhlv, thanpt-end

