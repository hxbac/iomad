<?php

function getCategoriesRenderManager(&$datarendercategories, $categories) {
    global $DB;
    // css transform: translateX($valuecss) để thụt đầu dòng cho phần tử con
    foreach ($categories as $category) {
        $categorieschild = $DB->get_records('course_categories', [
            'parent' => $category->id
        ], 'id DESC');
        $subjectofcategory = $DB->get_record('lms_gadt_subjects', [
            'categoryid' => $category->id
        ]);
        if ($subjectofcategory == null) {
            $category->checked = false;
        } else {
            $category->checked = true;
        }
        $category->urlhandlemanager = new moodle_url('/mod/giaoandientu/handle_manager.php', [
            'categoryid' => $category->id
        ]);
        $splitpath = explode('/', $category->path);
        $category->valuetransform = (count($splitpath) - 2) * 40;
        array_push($datarendercategories, $category);
        getCategoriesRenderManager($datarendercategories, $categorieschild);
    }
}

function getTeachersByCourseid($courseid) {
    global $DB;
    $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
    $context = context_course::instance($courseid);
    $teachers = get_role_users($role->id, $context);
    return $teachers;
}

function getPrincipals($categoryid) {
    global $DB;
    $role = $DB->get_record('role', [
        'shortname' => 'hieutruong'
    ]);
    $context = context_coursecat::instance($categoryid);
    $principals = get_role_users($role->id, $context);
    return $principals;
}

function getParentNameCategory($parentid, &$name = '') {
    global $DB;
    $parentrecord = $DB->get_record('course_categories', [
        'id' => $parentid
    ]);
    if ($parentrecord != null) {
        $name = $parentrecord->name . ' / ' . $name;
        getParentNameCategory($parentrecord->parent, $name);
    }
}

function getManagerByCategoryid($categoryid) {
    global $DB;
    $role = $DB->get_record('role', array('shortname' => 'truongbomon'));
    $context = context_coursecat::instance($categoryid);
    $managers = get_role_users($role->id, $context);
    return $managers;
}

function checkManagerAccess($categoryid) {
    global $DB, $USER;
    require_login();
    $managers = getManagerByCategoryid($categoryid);
    foreach ($managers as $manager) {
        if ($USER->id == $manager->id) {
            return true;
        }
    }

    return false;
}

function checkTeacherAccess($categoryid, $userid = null) {
    global $DB, $USER;
    if ($userid != null) {
        if ($userid != $USER->id) {
            return false;
        }
    }
    require_login();
    $courses = get_courses($categoryid);
    foreach ($courses as $course) {
        $teachers = getTeachersByCourseid($course->id);
        foreach ($teachers as $teacher) {
            if ($USER->id == $teacher->id) {
                return true;
            }
        }
    }

    return false;
}

function checkAccess($categoryid, $userid = null) {
    global $USER;
    if (checkManagerAccess($categoryid)) {
        return true;
    }
    if ($userid != null) {
        if ($userid != $USER->id) {
            return false;
        }
    }
    if (checkTeacherAccess($categoryid)) {
        return true;
    }

    return false;
}

function checkPrincipal($categoryid) {
    global $USER;
    require_login();
    $principals = getPrincipals($categoryid);
    foreach ($principals as $principal) {
        if ($principal->id == $USER->id) {
            return true;
        }
    }
    return false;
}

function sendMessageGadt($usertoid, $action, $message, $messagehtml, $url) {
    global $DB;
    $eventdata = new \core\message\message();
    // $eventdata->courseid         = 2;
    $eventdata->modulename       = 'giaoandientu';
    $eventdata->userto         = $DB->get_record('user', [
        'id' => $usertoid
    ]);
    $eventdata->userfrom = core_user::get_noreply_user();
    $eventdata->subject          = 'Giáo án điện tử >> ' . $action . '.';
    $eventdata->fullmessage      = $message;
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml  = $messagehtml;
    $eventdata->smallmessage     = $action;

    $eventdata->name            = 'giaoandientu_notification';
    $eventdata->component       = 'mod_giaoandientu';
    $eventdata->notification    = 1;
    $eventdata->contexturl      = $url->out(false);
    $eventdata->contexturlname  = 'Xem chi tiết';
    $customdata = [
    ];
    $userpicture = new user_picture($eventdata->userto);
    $userpicture->size = 1; // Use f1 size.
    $userpicture->includetoken = $eventdata->userto->id; // Generate an out-of-session token for the user receiving the message.
    $customdata['notificationiconurl'] = (new moodle_url('/mod/giaoandientu/pix/icon.svg'))->out(false);
    $eventdata->customdata = $customdata;
    message_send($eventdata);
}