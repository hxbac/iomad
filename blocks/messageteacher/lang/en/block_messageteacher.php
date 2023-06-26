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
 * Defines the lang strings for the Message My Teacher block
 *
 * @package    block_messageteacher
 * @author     Mark Johnson <mark@barrenfrozenwasteland.com>
 * @copyright  2010-2012 Tauntons College, UK. 2012 onwards Mark Johnson.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['appendurl'] = 'Append Referring URL';
$string['appendurldesc'] = 'When enabled, all messages send using the block will automatically contain the URL of the page from which the message was sent.';
$string['enablegroups'] = 'Enable Group Support';
$string['groupsdesc'] = 'When enabled, students will only see teachers in the same group as them';
$string['includecoursecat'] = 'Bao gồm giáo viên hỗ trợ từ danh mục';
$string['includecoursecatdesc'] = 'When enabled, users with the teacher roles in the course\'s parent category will be displayed, as well as those assigned in the course itself';
$string['messageteacher:addinstance'] = 'Add a new Message My Teacher block';
$string['messageprovider:message'] = 'A message sent using the Message My Teacher block';
$string['messagefrom'] = 'Tin nhắn từ {$a}';
$string['messageheader'] = 'Nhập tin nhắn của bạn cho {$a}';
$string['messagefailed'] = 'Gửi tin nhắn không thành công';
$string['messagesent'] = 'Tin nhắn đã được gửi!';
$string['messagetext'] = 'Tin nhắn văn bản';
$string['nogroupmembership'] = 'You\'re not a member of any group';
$string['nogroupteachers'] = 'Teacher not yet assigned to your group';
$string['norecipient'] = 'No recipient could be determined for userid {$a}';
$string['pluginname'] = 'Tin nhắn cho giáo viên';
$string['pluginnameplural'] = 'Message My Teachers';
$string['send'] = 'Gửi';
$string['sentfrom'] = 'Tin nhắn này được gửi từ {$a}';
$string['showuserpictures'] = 'Show User Pictures';
$string['showuserpicturesdesc'] = 'If enabled, teachers\' pictures will show alongside their name';
$string['teachersinclude']         = 'Teachers include:';
$string['rolesdesc']         = 'Select all the roles which are teachers or people whom students may wish to ask for help';