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
 * @package    local_wsmiro
 * @copyright  2020 - 2024 Université de Perpignan (https://www.univ-perp.fr)
 * @author     Samuel Calegari <samuel.calegari@univ-perp.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . "/externallib.php");

class local_wsmiro_external extends external_api {

    public static function get_overview_parameters() {
        return new external_function_parameters(
            array('uid' => new external_value(PARAM_INT, 'User ID"',VALUE_REQUIRED))
        );
    }

    public static function get_logs_parameters() {
        return new external_function_parameters(
                array(  'uid' => new external_value(PARAM_INT, 'User ID"',VALUE_REQUIRED),
                        'start' => new external_value(PARAM_INT, 'Staring Time (seconds until 1970)"',VALUE_REQUIRED),
                        'end' => new external_value(PARAM_INT, 'Staring Time (seconds until 1970)"',VALUE_REQUIRED)
                )
        );
    }

    public static function get_course_logs_parameters() {
        return new external_function_parameters(
            array(  'cid' => new external_value(PARAM_INT, 'Course ID"',VALUE_REQUIRED),
                'start' => new external_value(PARAM_INT, 'Staring Time (seconds until 1970)"',VALUE_REQUIRED),
                'end' => new external_value(PARAM_INT, 'Staring Time (seconds until 1970)"',VALUE_REQUIRED)
            )
        );
    }

    public static function get_user_parameters() {
        return new external_function_parameters(
            array('uid' => new external_value(PARAM_INT, 'User ID"',VALUE_REQUIRED))
        );
    }

    public static function get_course_students_parameters() {
        return new external_function_parameters(
            array('cid' => new external_value(PARAM_INT, 'Course ID"',VALUE_REQUIRED))
        );
    }

    public static function get_zoom_logs_parameters() {
        return new external_function_parameters(
            array(  'uid' => new external_value(PARAM_INT, 'User ID"',VALUE_REQUIRED),
                'start' => new external_value(PARAM_INT, 'Staring Time (seconds until 1970)"',VALUE_REQUIRED),
                'end' => new external_value(PARAM_INT, 'Staring Time (seconds until 1970)"',VALUE_REQUIRED)
            )
        );
    }

    public static function get_quiz_attempts_logs_parameters() {
        return new external_function_parameters(
            array(  'uid' => new external_value(PARAM_INT, 'User ID"',VALUE_REQUIRED),
                'start' => new external_value(PARAM_INT, 'Staring Time (seconds until 1970)"',VALUE_REQUIRED),
                'end' => new external_value(PARAM_INT, 'Staring Time (seconds until 1970)"',VALUE_REQUIRED)
            )
        );
    }

    public static function get_overview($uid) {
        global $USER, $DB;

        $params = self::validate_parameters(self::get_overview_parameters(), array('uid' => $uid));

        $context = context_user::instance($USER->id);
        self::validate_context($context);

        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        $tmp = array();

        $query  = 'SELECT max(timecreated) as lastconnection FROM mdl_logstore_standard_log WHERE eventname LIKE "%user_loggedin" and userid = '.$params['uid'];
        $result = $DB->get_records_sql($query);
        if($record = array_shift($result)) {
            $tmp['lastaccess'] = $record->lastconnection;
        } else {
			$tmp['lastaccess'] = 0;
		}

        $result = $DB->get_records('user_lastaccess',array('userid' => $params['uid']),'timeaccess DESC','*',0,1);
        if($record = array_shift($result)) {
            $courses_names = self::get_courses_names();
            $tmp['lastcourse'] = $courses_names[$record->courseid];
            $tmp['lastcourseaccess'] = $record->timeaccess;
        } else {
			$tmp['lastcourse'] = "";
			$tmp['lastcourseaccess'] = 0;
		}

        $result = $DB->get_records('logstore_standard_log',array('userid' => $params['uid']),'timecreated DESC','*',0,5);
        $tmp2 = array();
        foreach($result as $record){
            array_push($tmp2, array(
                    'eventname' => $record->eventname,
                    'courseid' => $record->courseid,
                    'course' => $courses_names[$record->courseid],
                    'date' => $record->timecreated,
                    'origin' => $record->origin,
                    'ip' => $record->ip)
            );
        }
        $tmp['lastlogs'] = $tmp2;

		return $tmp;
    }

    public static function get_logs($uid,$start,$end) {
        global $USER, $DB;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::get_logs_parameters(), array('uid' => $uid, 'start' => $start, 'end' => $end));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        $courses_names =  self::get_courses_names();

        $select = 'userid = ' . $params['uid'] . ' AND realuserid is NULL AND timecreated >= ' . $params['start'] . ' AND timecreated <= ' . $params['end'];
        $result = $DB->get_records_select('logstore_standard_log', $select,null, 'timecreated ASC');
        $tmp = array();
        foreach($result as $record){
            array_push($tmp, array(
                'eventname' => $record->eventname,
                'courseid' => $record->courseid,
                'course' => $courses_names[$record->courseid],
                'date' => $record->timecreated,
                'origin' => $record->origin,
                'ip' => $record->ip)
            );
        }

        return $tmp;
    }

    public static function get_course_logs($cid,$start,$end) {
        global $USER, $DB;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::get_course_logs_parameters(), array('cid' => $cid, 'start' => $start, 'end' => $end));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        $select = 'courseid = ' . $params['cid'] . ' AND realuserid is NULL AND timecreated >= ' . $params['start'] . ' AND timecreated <= ' . $params['end'];
        $result = $DB->get_records_select('logstore_standard_log', $select,null, 'timecreated ASC');
        $tmp = array();
        foreach($result as $record){
            array_push($tmp, array(
                    'eventname' => $record->eventname,
                    'userid' => $record->userid,
                    'date' => $record->timecreated)
            );
        }

        return $tmp;
    }

    public static function get_user($uid) {
        global $USER;
        global $DB;

        $params = self::validate_parameters(self::get_user_parameters(), array('uid' => $uid));

        $context = context_user::instance($USER->id);
        self::validate_context($context);

        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        $result = $DB->get_record('user',array('id' => $params['uid']));
        $tmp = array();
        $tmp['uid'] = $result->id;
        $tmp['lastname'] = $result->lastname;
        $tmp['firstname'] = $result->firstname;

        return $tmp;
    }

    public static function get_course_students($cid) {
        global $USER, $DB;

        $params = self::validate_parameters(self::get_course_students_parameters(), array('cid' => $cid));

        $context = context_user::instance($USER->id);
        self::validate_context($context);

        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        $coursecontext = context_course::instance($cid);
        $users = get_enrolled_users($coursecontext, 'moodle/course:isincompletionreports');

        $tmp = array();

        foreach ($users  as $user ) {
            global $DB;
            $lastaccesscourse = $DB->get_field('user_lastaccess', 'timeaccess', array('courseid' => $cid, 'userid' => $user->id));
            if (!$lastaccesscourse) $lastaccesscourse = 0;
            array_push($tmp,
                array('uid'=>$user->id,
                    'firstname'=>$user->firstname,
                    'lastname'=>$user->lastname,
                    'email'=>$user->email,
                    'avatar' => strval(new moodle_url('/user/pix.php/'.$user->id.'/f2.jpg')),
                    'lastaccess' => $user->lastaccess,
                    'lastaccesscourse' => $lastaccesscourse
                )
            );
        }

        return $tmp;
    }

    public static function get_zoom_logs($uid,$start,$end) {
        global $USER, $DB;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::get_zoom_logs_parameters(), array('uid' => $uid, 'start' => $start, 'end' => $end));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        $select = 'userid = ' . $params['uid'] . ' AND join_time >= ' . $params['start'] . ' AND join_time <= ' . $params['end'];
        $result = $DB->get_records_select('zoom_meeting_participants', $select,null, 'join_time ASC');
        $tmp = array();
        foreach($result as $record){
            array_push($tmp, array(
                    'date' => $record->join_time,
                    'duration' => $record->duration)
            );
        }

        return $tmp;
    }

    public static function get_quiz_attempts_logs($uid,$start,$end) {
        global $USER, $DB;

        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::get_quiz_attempts_logs_parameters(), array('uid' => $uid, 'start' => $start, 'end' => $end));

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        $select = 'userid = ' . $params['uid'] . ' AND timestart >= ' . $params['start'] . ' AND timestart <= ' . $params['end'] . ' AND state= \'finished\'';
        $result = $DB->get_records_select('quiz_attempts', $select,null, 'timestart ASC');
        $tmp = array();
        foreach($result as $record){
            array_push($tmp, array(
                    'timestart' => $record->timestart,
                    'timefinish' => $record->timefinish,)
            );
        }

        return $tmp;
    }

    public static function get_overview_returns() {

        return new external_single_structure(
            array(
                'lastaccess' => new external_value(PARAM_INT, 'Last access'),
                'lastcourse' => new external_value(PARAM_TEXT, 'Last course viewed'),
                'lastcourseaccess' => new external_value(PARAM_INT, 'Last course viewed time'),
                'lastlogs' => new external_multiple_structure(self::log_structure())
            )
        );
    }

    public static function get_logs_returns() {

        return new external_multiple_structure(self::log_structure());
    }

    public static function log_structure() {

        return new external_single_structure(
            array(
                'eventname' => new external_value(PARAM_TEXT, 'Event name'),
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'course' => new external_value(PARAM_TEXT, 'Course name'),
                'date' => new external_value(PARAM_INT, 'Datetime'),
                'origin' => new external_value(PARAM_TEXT, 'Origin'),
                'ip' => new external_value(PARAM_TEXT, 'IP Address')
            )
        );
    }

    public static function get_course_logs_returns() {

        return new external_multiple_structure(self::course_log_structure());
    }

    public static function course_log_structure() {

        return new external_single_structure(
            array(
                'eventname' => new external_value(PARAM_TEXT, 'Event name'),
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'date' => new external_value(PARAM_INT, 'Datetime')
            )
        );
    }

    public static function get_course_students_returns() {

        return new external_multiple_structure(self::course_students_structure());
    }

    public static function course_students_structure() {

        return new external_single_structure(
            array(
                'uid' => new external_value(PARAM_INT, 'User ID'),
                'firstname' => new external_value(PARAM_TEXT, 'Firstname'),
                'lastname' => new external_value(PARAM_TEXT, 'Lastname'),
                'email' => new external_value(PARAM_TEXT, 'Email'),
                'avatar' => new external_value(PARAM_TEXT, 'Avatar'),
                'lastaccess' => new external_value(PARAM_INT, 'Lms Last Access'),
                'lastaccesscourse' => new external_value(PARAM_INT, 'Course Last Access'),
            )
        );
    }

    public static function get_zoom_logs_returns() {

        return new external_multiple_structure(self::zoom_log_structure());
    }

    public static function zoom_log_structure() {

        return new external_single_structure(
            array(
                'date' => new external_value(PARAM_INT, 'Datetime'),
                'duration' => new external_value(PARAM_INT, 'Duration')
            )
        );
    }

    public static function get_quiz_attempts_logs_returns() {

        return new external_multiple_structure(self::quiz_attempts_log_structure());
    }
    public static function quiz_attempts_log_structure() {

        return new external_single_structure(
            array(
                'timestart' => new external_value(PARAM_INT, 'Time Start'),
                'timefinish' => new external_value(PARAM_INT, 'Time Finish')
            )
        );
    }

    public static function get_user_returns() {

        return new external_single_structure(
            array(
                'uid' => new external_value(PARAM_INT, 'User Id'),
                'lastname' => new external_value(PARAM_TEXT, 'User Lastname'),
                'firstname' => new external_value(PARAM_TEXT, 'User FirstName')
            )
        );
    }

    public static function get_courses_names() {

        global $DB;
        $result = $DB->get_records('course');
        $courses_names =  array();
        foreach($result as $record){
            $courses_names[$record->id] = format_string($record->fullname);
        }

        return $courses_names;
    }
}

