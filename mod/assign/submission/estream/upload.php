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
 * Planet eStream Assignment Submission Plugin Upload code
 *
 * @package        assignsubmission_estream
 * @copyright        Planet Enterprises Ltd
 * @license        http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/submission/estream/locallib.php');
global $PAGE, $USER;
require_login();
$PAGE->set_context(context_user::instance($USER->id));
profile_load_data($USER);
$params = array();
    //$params['usercontextid'] = $usercontextid;
if (isset($USER->profile_field_planetestreamusername) && !empty($USER->profile_field_planetestreamusername)) {
    $delta = atto_planetestream_obfuscate($USER->profile_field_planetestreamusername);
	} else {
		//here
	if (get_config('assignsubmission_estream', 'usemail') == true) {
		$delta = atto_planetestream_obfuscate($USER->email);
	} else {
		$delta = atto_planetestream_obfuscate($USER->username);
	}	
	}
	$userip = atto_planetestream_obfuscate(getremoteaddr());
	$baseurl = rtrim(get_config('assignsubmission_estream', 'url') , '/');
	$params['estream_url'] = $baseurl;
	$checksum = atto_planetestream_getchecksum();
	$authticket = atto_planetestream_getauthticket($baseurl, $checksum, $delta, $userip, $params);
	if ($authticket == '') {
       $params['disabled'] = true; //may not need
    }
	
$PAGE->set_url($CFG->wwwroot . '/mod/assign/submission/estream/upload.php');
$itemtitle = optional_param('itemtitle', '', PARAM_TEXT);
$itemdesc = optional_param('itemdesc', '', PARAM_TEXT);
$itemcid = optional_param('itemcid', '', PARAM_TEXT);
$itemcdid = optional_param('itemcdid', '', PARAM_TEXT);
$itemaid = optional_param('itemaid', '', PARAM_TEXT);
$itemuid = optional_param('itemuid', '', PARAM_TEXT);
$cdid = optional_param('cdid', '', PARAM_TEXT);
$embedcode = optional_param('ec', '', PARAM_TEXT);
$error = optional_param('error', '', PARAM_TEXT);
$configerror = optional_param('configerror', '', PARAM_TEXT);
function atto_planetestream_getchecksum() {
    $decchecksum = (float)(date('d') + date('m')) + (date('m') * date('d')) + (date('Y') * date('d'));
    $decchecksum += $decchecksum * (date('d') * 2.27409) * .689274;
    return md5(floor($decchecksum));
}

function atto_planetestream_getauthticket($url, $checksum, $delta, $userip, &$params) {
    $return = '';
	
	//$return = $url . "~~~" . $checksum . "~~~~" . $delta . "~~~~" . $userip;
    try {
        $url .= '/VLE/Moodle/Auth/?source=1&assign=1&checksum=' . $checksum . '&delta=' . $delta . '&u=' . $userip;
        if (!$curl = curl_init($url)) {
           return '';
		   //return $return;
        }
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 4);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        if (strpos($response, '{"ticket":') === 0) {
            $jobj = json_decode($response);
            $return = $jobj->ticket;
            $params['estream_height'] = $jobj->height;
            $params['estream_width'] = $jobj->width;
        }
    } catch (Exception $e) {
        // ... non-fatal ...
    }
    return $return;
}

function atto_planetestream_obfuscate($strx) {
    $strbase64chars = '0123456789aAbBcCDdEeFfgGHhiIJjKklLmMNnoOpPQqRrsSTtuUvVwWXxyYZz/+=';
    $strbase64string = base64_encode($strx);
    if ($strbase64string == '') {
        return '';
    }
    $strobfuscated = '';
    for ($i = 0; $i < strlen ($strbase64string); $i ++) {
        $intpos = strpos($strbase64chars, substr($strbase64string, $i, 1));
        if ($intpos == - 1) {
            return '';
        }
        $intpos += strlen($strbase64string ) + $i;
        $intpos = $intpos % strlen($strbase64chars);
        $strobfuscated .= substr($strbase64chars, $intpos, 1);
    }
    return urlencode($strobfuscated);
}
if (empty($itemtitle)) {
    if (empty($error) && empty($configerror) && !empty($cdid)) {
?>
<html>
    <head>
        <script type="text/javascript">
            function page_load() {
                parent.parent.document.getElementById('hdn_cdid').value = "<?php echo $cdid; ?>";
                parent.parent.document.getElementById('hdn_embedcode').value = "<?php echo $embedcode; ?>";
            }
        </script>
        <style type="text/css">
            * {
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            }
        </style>
    </head>
    <body onload="page_load()" >
            <h3><?php echo get_string('uploadok', 'assignsubmission_estream'); ?></h3>
    </body>
</html>
<?php
    } else {
?>
    <html>
        <head>
            <style type="text/css">
                * {
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                }

            </style>
        </head>
        <body>
            <h3><?php
        if (empty($configerror)) {
            echo get_string('uploadfailed', 'assignsubmission_estream') . '<br />' . $error;
        } else {
            echo $configerror;
        } ?>    </h3>
        </body>
    </html>
<?php
    }
} else {
    $thissubmission = new assign_submission_estream(new assign(null, null, null) , null);
    if (empty($thissubmission)) {
        echo "Sorry, the submission could not be initiated.";
        die();
    }
    //$baseurl = rtrim(get_config('assignsubmission_estream', 'url') , '/');
  
    if (empty($baseurl)) {
?>
    <html>
        <head>
            <style type="text/css">
                * {
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                }
            </style>
        </head>
        <body>
            <h3><?php echo get_string('notyetconfigured', 'assignsubmission_estream') ?></h3>
        </body>
     </html>
<?php
    } else {
?>
        <script type="text/javascript">

                // parent.parent.document.getElementById("id_submitbutton").disabled = true;

        </script>
        <div style="text-align: center;">
                <iframe width="90%" height="700px" allow="camera;microphone;display-capture;" frameborder="0" src="<?php echo $baseurl; ?>/VLE/Moodle/Default.aspx?sourceid=11&inlinemode=moodle&delta=<?php echo $delta?>&checksum=<?php echo $checksum; ?>&itemcdid=<?php echo $itemcdid; ?>&assign=<?php echo ((string)$PAGE->pagetype == 'mod-assign-editsubmission' ? "true" : "false"); ?>&assignmoodle=<?php echo ((string)$PAGE->pagetype == 'mod-assign-submission-estream-upload' ? "true" : "false"); ?>&ticket=<?php echo urlencode($authticket); ?>&murl=
				<?php 
                echo $CFG->wwwroot.'/mod/assign/submission/estream/upload.php'
                .'&amp;title='.urlencode($itemtitle).'&amp;desc='.urlencode($itemdesc)
                .'&amp;cid='.urlencode($itemcid).'&amp;aid='.urlencode($itemaid)				
                .'&amp;uid='.urlencode($itemuid).'&amp;cdid='				
                .$itemcdid;?>"></iframe>
        </div>
<?php
    }
}