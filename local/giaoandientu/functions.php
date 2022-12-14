<?php

function getCategoriesRenderManager(&$datarendercategories, $categories) {
    global $DB;
    foreach ($categories as $category) {
        $item = (object)[];
        $item->selectable = false;
        $isChecked = $DB->record_exists('lms_gadt_subjects', [
            'categoryid' => $category->id
        ]);
        $item->text = '';
        $item->categoryname = $category->name;
        $item->checked = $isChecked ? 'checked' : '';
        $item->categoryid = $category->id;
        $item->nodes = [];
        array_push($datarendercategories, $item);

        $categorieschild = $DB->get_records('course_categories', [
            'parent' => $category->id
        ]);
        getCategoriesRenderManager($item->nodes, $categorieschild);

        if (!$item->nodes) {
            unset($item->nodes);
        }
    }
}

function getDataRenderIndexForPrincipal(&$result, $categories) {
    global $DB, $CFG;

    foreach ($categories as $category) {
        $categoryInfo = $DB->get_record('course_categories', [
            'id' => $category->categoryid ?? $category->id
        ]);
        if (!!$category->categoryid) {
            $fullnamecategory = '';
            getParentNameCategory($categoryInfo->parent, $fullnamecategory);
            $categoryInfo->name = $fullnamecategory . $categoryInfo->name;
        }
        $item = (object)[];
        $url = new moodle_url('/local/giaoandientu/view.php', [
            'categoryid' => $categoryInfo->id
        ]);
        $urlthongke = new moodle_url('/local/giaoandientu/thongke.php', [
            'categoryid' => $categoryInfo->id
        ]);
        
        $item->selectable = false;
        $item->categoryid = $categoryInfo->id;
        $item->text = "<a href='". $url ."'>". $categoryInfo->name ."</a><a href='". $urlthongke ."' style='margin-left: auto;'>Thống kê</a>";
        $item->nodes = [];
        $sql = "SELECT ctg.* FROM `". $CFG->prefix ."lms_gadt_subjects` lmsc JOIN `". $CFG->prefix ."course_categories` ctg ON lmsc.categoryid = ctg.id WHERE ctg.parent = ". $categoryInfo->id;
        
        $childsCategory = $DB->get_records_sql($sql);
        getDataRenderIndexForPrincipal($item->nodes, (array)$childsCategory);

        if (!$item->nodes) {
            unset($item->nodes);
        }
        array_push($result, $item);
    }
}

function getTeachersByCourseid($courseid) {
    global $DB;
    $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
    $context = context_course::instance($courseid);
    $teachers = get_role_users($role->id, $context);
    return $teachers;
}

function getPrincipals() {
    global $DB;
    $role = $DB->get_record('role', [
        'shortname' => 'hieutruong'
    ]);
    $context = context_system::instance();
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

function getAllChildOfCategory($categoryid, &$listChild) {
    global $DB;
    if (!!$categoryid) {
        $childs = $DB->get_records('course_categories', [
            'parent' => $categoryid
        ]);
        foreach ($childs as $child) {
            array_push($listChild, $child->id);
            getAllChildOfCategory($child->id, $listChild);
        }
    }
}

function checkManagerAndPrincipalAccess($categoryid) {
    global $DB, $USER;
    require_login();

    $principalRoleId = $DB->get_field('role', 'id', [
        'shortname' => 'hieutruong'
    ]);
    $isPrincipal = $DB->record_exists('role_assignments', [
        'userid' => $USER->id,
        'roleid' => $principalRoleId,
        'contextid' => 1
    ]);
    if ($isPrincipal) {
        return true;
    }

    $managers = getManagerByCategoryid($categoryid);
    foreach ($managers as $manager) {
        if ($USER->id == $manager->id) {
            return true;
        }
    }

    return false;
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
    global $USER, $DB;

    $principalRoleId = $DB->get_field('role', 'id', [
        'shortname' => 'hieutruong'
    ]);
    $isPrincipal = $DB->record_exists('role_assignments', [
        'userid' => $USER->id,
        'roleid' => $principalRoleId,
        'contextid' => 1
    ]);
    if ($isPrincipal) {
        return true;
    }
    
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

function checkPrincipal() {
    global $USER;
    require_login();
    $principals = getPrincipals();
    foreach ($principals as $principal) {
        if ($principal->id == $USER->id) {
            return true;
        }
    }
    return false;
}

function checkPrincipalAccessChild($categoryid) {
    global $USER;
    require_login();
    
    $school = getSchoolByChildCategoryid($categoryid);
    $principals = getPrincipals($school->id);
    foreach ($principals as $principal) {
        if ($principal->id == $USER->id) {
            return true;
        }
    }
    return false;
}

function getSchoolByChildCategoryid($categoryid) {
    global $DB;

    $category = $DB->get_record('course_categories', [
        'id' => $categoryid
    ]);
    $categoryparent = $DB->get_record('course_categories', [
        'id' => $category->parent
    ]);
    if ($categoryparent->parent != 0) {
        return getSchoolByChildCategoryid($categoryparent->id);
    } else {
        return $categoryparent;
    }
}

function sendMessageGadt($usertoid, $action, $message, $messagehtml, $url) {
    global $DB;
    $eventdata = new \core\message\message();
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
    $eventdata->component       = 'local_giaoandientu';
    $eventdata->notification    = 1;
    $eventdata->contexturl      = $url->out(false);
    $eventdata->contexturlname  = 'Xem chi tiết';
    $customdata = [
    ];
    $userpicture = new user_picture($eventdata->userto);
    $userpicture->size = 1; // Use f1 size.
    $userpicture->includetoken = $eventdata->userto->id; // Generate an out-of-session token for the user receiving the message.
    $customdata['notificationiconurl'] = (new moodle_url('/local/giaoandientu/pix/icon.svg'))->out(false);
    $eventdata->customdata = $customdata;
    message_send($eventdata);
}