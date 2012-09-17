<?php

/*
 * Add endpoint services to this file as new functions using this template:
 *
  function sg_oauth_service_$name($consumer, $tokEnt) {
  // Get userid of Moodle using this service
  $tokEnt->userid;
  // Use userid to do something, for example get student courses
  return "Return value of the service";
  }
 *
 * Set $name to the name of the service.
 */

/**
 * Service to return general Moodle user information.
 * @global type $DB
 * @param type $consumer
 * @param type $tokEnt
 * @return type
 */
function sg_oauth_service_user_info($consumer, $tokEnt) {
    global $DB;

    return $DB->get_record('user', array('id' => $tokEnt->userid), 'id,username,firstname,lastname,email');    
}

/**
 * Service to return teachers of user in Moodle courses
 * @global type $DB
 * @param type $consumer
 * @param type $tokEnt
 * @return type
 */
function sg_oauth_service_user_myteachers($consumer, $tokEnt) {
    global $DB;

    $userid = $tokEnt->userid;
    $teacher_role_ids = array();

    if ($teacher_roles = $DB->get_records_sql("SELECT r.* FROM {role} r where shortname = 'editingteacher' or shortname = 'teacher'")) {
        foreach ($teacher_roles as $teacher_role) {
            $teacher_role_ids[] = $teacher_role->id;
        }
    }

    $teachers = array();
    $seen_teacher_ids = array();
    if ($courses = enrol_get_users_courses($userid, true)) {
        foreach ($courses as $course) {
            $context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);
            $contextid = $context->id;
            $has_role = false;
            foreach ($teacher_role_ids as $roleid) {
                if (user_has_role_assignment($userid, $roleid, $contextid)) {
                    $has_role = TRUE;
                    break;
                }
            }
            if (!$has_role) {
                if ($users = get_role_users($teacher_role_ids, $context, false, 'u.id,u.username,u.firstname,u.lastname,u.email,r.name as rolename,r.shortname as roleshortname')) {
                    foreach ($users as $user) {
                        if (!in_array($user->id, $seen_teacher_ids)) {
                            $teachers[] = $user;
                            $seen_teacher_ids[] = $user->id;
                        }
                    }
                }
            }
        }
    }
    return $teachers;
}

/**
 * Service to return students from user Moodle courses
 * @global type $DB
 * @param type $consumer
 * @param type $tokEnt
 * @return type
 */
function sg_oauth_service_user_mystudents($consumer, $tokEnt) {
    global $DB;

    $userid = $tokEnt->userid;
    $teacher_role_ids = array();

    if ($teacher_roles = $DB->get_records_sql("SELECT r.* FROM {role} r where shortname = 'editingteacher' or shortname = 'teacher'")) {
        foreach ($teacher_roles as $teacher_role) {
            $teacher_role_ids[] = $teacher_role->id;
        }
    }

    if ($student_roles = $DB->get_records_sql("SELECT r.* FROM {role} r where shortname = 'student'")) {
        foreach ($student_roles as $student_role) {
            $student_role_ids[] = $student_role->id;
        }
    }

    $students = array();
    $seen_student_ids = array();
    if ($courses = enrol_get_users_courses($userid, true)) {
        foreach ($courses as $course) {
            $context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);
            $contextid = $context->id;
            $has_role = false;
            foreach ($teacher_role_ids as $roleid) {
                if (user_has_role_assignment($userid, $roleid, $contextid)) {
                    $has_role = TRUE;
                    break;
                }
            }
            if ($has_role) {
                if ($users = get_role_users($student_role_ids, $context, false, 'u.id,u.username,u.firstname,u.lastname,u.email,r.name as rolename,r.shortname as roleshortname')) {
                    foreach ($users as $user) {
                        if (!in_array($user->id, $seen_student_ids)) {
                            $students[] = $user;
                            $seen_student_ids[] = $user->id;
                        }
                    }
                }
            }
        }
    }
    return $students;
}

/**
 * Service to verify that user token is still active and refresh it
 * @global type $DB
 * @param type $consumer
 * @param type $tokEnt
 * @return string
 */
function sg_oauth_service_user_verifylastchecked($consumer, $tokEnt) {
    global $DB;

    // verifies that the token is still active
    // and refresh if it is
    $valid_from = time() - (HOURSECS * 2);
    if ($tokEnt->lastcheckedon == 0 || $tokEnt->lastcheckedon < $valid_from) {
        $return_value = array("status" => "fail");
    } else {
        sg_oauth_refresh_token_lastcheckedon($tokEnt);
        $return_value = array("status" => "pass");
    }
    return $return_value;
}

?>
