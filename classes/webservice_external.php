<?php
/*
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package local_totaramobile
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/my/lib.php");
require_once("$CFG->dirroot/totara/message/messagelib.php");

class local_totaramobile_webservice_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_mobile_homepage_parameters() {
        return new external_function_parameters(array());
    }

    /**
     * Return the mobile homepage
     *
     * @return string homepage
     */
    public static function get_mobile_homepage() {
        return get_config('local_totaramobile', 'mobilehomepage');
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function get_mobile_homepage_returns() {
        return new external_value(PARAM_RAW, 'Home page');
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_item_counts_parameters() {
        return new external_function_parameters(
            array(
            )
        );
    }

    /**
     * Return the number of courses, programs, bookings, tasks and alerts, etc
     *
     * @return array counts
     */
    public static function get_item_counts() {
        global $DB, $USER;

        $userid = $USER->id;
        $cce = new local_totaramobile_course_external();
        $courses = array_filter(
            $cce->list_courses(), function($x) {
            return $x->category > 0;
        }
        );
        $pe = new local_totaramobile_totara_program_external();
        $mycourses = count($pe->get_users_courses());
        $myprograms = count($pe->get_users_programs());
        $myrequired = count($pe->get_users_required_programs());
        $mylearning = $mycourses + $myprograms + $myrequired;

        return array(
            'find_courses' => count($courses),
            'find_programs' => $DB->count_records('prog'),
            'my_courses' => $mycourses,
            'my_programs' => $myprograms,
            'required_learning' => $myrequired,
            'my_learning' => $mylearning,
            'bookings' => self::get_num_bookings(),
            'tasks' => self::tm_messages_count('totara_task'),
            'alerts' => self::tm_messages_count('totara_alert'),
            'bookings_new' => self::get_num_bookings(true),
            'tasks_new' => self::tm_messages_count('totara_task', true),
            'alerts_new' => self::tm_messages_count('totara_alert', true),
        );
    }

    /**
     * Returns description of method result value
     *
     * @return external_single_structure
     */
    public static function get_item_counts_returns() {
        return new external_single_structure(
            array(
                'find_courses' => new external_value(PARAM_INT, 'find courses count'),
                'find_programs' => new external_value(PARAM_INT, 'find programs count'),
                'my_courses' => new external_value(PARAM_INT, 'my learning count'),
                'my_programs' => new external_value(PARAM_INT, 'my programs count'),
                'required_learning' => new external_value(PARAM_INT, 'required learning count'),
                'my_learning' => new external_value(PARAM_INT, 'my learning count'),
                'bookings' => new external_value(PARAM_INT, 'bookings count'),
                'tasks' => new external_value(PARAM_INT, 'tasks count'),
                'alerts' => new external_value(PARAM_INT, 'alerts count'),
                'bookings_new' => new external_value(PARAM_INT, 'bookings count'),
                'tasks_new' => new external_value(PARAM_INT, 'tasks count'),
                'alerts_new' => new external_value(PARAM_INT, 'alerts count')
            ), 'counts'
        );
    }

    protected static function get_num_bookings($excludeseenonmobile=false) {
        global $DB, $USER;
        $sql = "
            SELECT COUNT(*)
            FROM {facetoface} f
            JOIN {course} c ON c.id = f.course
            JOIN {facetoface_sessions} s ON s.facetoface = f.id
            JOIN {facetoface_sessions_dates} d ON d.sessionid = s.id
            JOIN {facetoface_signups} su ON su.sessionid = s.id
            JOIN {facetoface_signups_status} sus ON sus.signupid = su.id
            JOIN {user} u ON u.id = su.userid
            LEFT JOIN {facetoface_booking_seen_mob} bsom ON (
                bsom.bookingid = d.id
                AND bsom.userid = ?
            )
            WHERE u.id = ?
            AND s.datetimeknown > 0
            AND sus.superceded = 0
            AND sus.statuscode IN (40, 50, 60, 70)
            AND d.timestart > ?
        ";

        if ($excludeseenonmobile) {
            $sql .= " AND bsom.id IS NULL";
        }

        $now = round(time(), -2);
        $params = array($USER->id, $USER->id, $now);

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * get the current count of messages by type - alert/task
     *
     * @param string $type - message type
     * @return int count of messages
     */
    protected static function tm_messages_count($type, $excludeseenonmobile=false) {
        global $USER, $DB;

        // select only particular type
        $processor = $DB->get_record('message_processors', array('name' => $type));
        if (empty($processor)) {
            return false;
        }

        $where = 'm.useridto = ? AND w.processorid = ?';
        if ($excludeseenonmobile) {
            $where .= ' AND msom.id IS NULL';
        }
        $params = array($USER->id, $processor->id);

        $sql = "SELECT COUNT('x')
                  FROM {message} m
                  JOIN {message_working} w ON (m.id = w.unreadmessageid)
             LEFT JOIN {message_metadata} d ON (d.messageid = m.id)
             LEFT JOIN {message_seen_on_mobile} msom ON (msom.messageid = m.id AND msom.userid = m.useridto)
                 WHERE $where";

        // hunt for messages
        return $DB->count_records_sql($sql, $params);
    }
}
