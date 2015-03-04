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

class local_totaramobile_enrol_external extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_users_course_completions_parameters() {
        return new external_function_parameters(
            array()
        );
    }

    /**
     * Get list of courses user is enrolled in (only active enrolments are returned)
     * and the course completion status.
     * Please note the current user must be able to access the course,
     * otherwise the course is not included.
     *
     * @return array of courses
     */
    public static function get_users_course_completions() {
        global $CFG, $USER;
        require_once("{$CFG->libdir}/completionlib.php");

        // Do basic automatic PARAM checks on incoming data, using params description
        // If any problems are found then exceptions are thrown with helpful error messages
        $params = self::validate_parameters(
            self::get_users_course_completions_parameters(), array()
        );

        $courses = enrol_get_users_courses($USER->id, true, 'id, shortname, fullname, idnumber, visible');
        $result = array();

        foreach ($courses as $course) {
            $context = context_course::instance($course->id, IGNORE_MISSING);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                // Current user can not access this course!
                continue;
            }

            // Add completion counts.
            $info = new completion_info($course);
            $completions = $info->get_completions($USER->id);
            $completions_total = count($completions);
            $completions_completed = 0;
            foreach ($completions as $completion) {
                if ($completion->is_complete()) {
                    $completions_completed++;
                }
            }
            $started = $completions_completed > 0 ? 1 : 0;
            $completed = (($completions_total > 0) && ($completions_completed >= $completions_total)) ? 1 : 0;

            $result[] = array(
                'id' => $course->id,
                'shortname' => $course->shortname,
                'fullname' => $course->fullname,
                'idnumber' => $course->idnumber,
                'visible' => $course->visible,
                'completions_total' => $completions_total,
                'completions_completed' => $completions_completed,
                'started' => $started,
                'completed' => $completed
            );
        }

        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_users_course_completions_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'        => new external_value(PARAM_INT, 'id of course'),
                    'shortname' => new external_value(PARAM_RAW, 'short name of course'),
                    'fullname'  => new external_value(PARAM_RAW, 'long name of course'),
                    'idnumber'  => new external_value(PARAM_RAW, 'id number of course'),
                    'visible'   => new external_value(
                        PARAM_INT, '1 means visible, 0 means hidden course'
                    ),
                    'completions_total' => new external_value(
                        PARAM_INT, 'number of completable items'
                    ),
                    'completions_completed' => new external_value(
                        PARAM_INT, 'number of completed items'
                    ),
                    'started' => new external_value(
                        PARAM_INT, '1 means course has been started, 0 means it has not'
                    ),
                    'completed' => new external_value(
                        PARAM_INT, '1 means course has been completed, 0 means it has not'
                    ),
                )
            )
        );
    }
}
