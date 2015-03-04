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

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * Class local_totaramobile_enrol_self_external
 *
 * TODO: this is borked in several ways:
 *  1/ it does nto work with multiple guest methods in one course
 *  2/ it does not check if user is already enrolled
 */
class local_totaramobile_enrol_self_external extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function enrol_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'The course to enrol the user role in'),
                'enrolmentkey' => new external_value(PARAM_RAW, 'Enrolment key', VALUE_OPTIONAL),
            )
        );
    }

    /**
     * Enrolment of users.
     *
     * @param int $courseid Course ID
     * @param string $enrolmentkey
     *
     * @return array
     */
    public static function enrol($courseid, $enrolmentkey = null) {
        global $DB, $CFG, $USER;
        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(
            self::enrol_parameters(),
            array('courseid' => $courseid, 'enrolmentkey' => $enrolmentkey)
        );

        $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);
        $context = context_course::instance($course->id);

        $catcontext = context_coursecat::instance($course->category, MUST_EXIST);
        self::validate_context($catcontext);

        if (!$course->visible) {
            require_capability('moodle/course:viewhiddencourses', $context);
        }

        // Retrieve the self enrolment plugin.
        $enrol = enrol_get_plugin('self');
        if (!$enrol) {
            return array('enrolled' => false);
        }

        $instance = null;
        // Check self enrolment plugin instance is enabled/exist.
        $enrolinstances = enrol_get_instances($courseid, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol === "self") {
                $instance = $courseenrolinstance;
                break;
            }
        }
        if (empty($instance)) {
            return array('enrolled' => false);
        }

        // Check the enrolment key and process the enrolment.
        if (!$instance->password or $instance->password === $params['enrolmentkey']) {
            // Rollback all enrolment if an error occurs.
            $transaction = $DB->start_delegated_transaction();
            $enrol->enrol_user($instance, $USER->id, $instance->roleid, 0, 0, ENROL_USER_ACTIVE);
            $transaction->allow_commit();
            return array('enrolled' => true);
        }

        return array('enrolled' => false);
    }

    /**
     * Returns description of method result value.
     *
     * @return null
     */
    public static function enrol_returns() {
        return new external_single_structure(
            array(
                'enrolled' => new external_value(PARAM_BOOL, 'Enrolled')
            )
        );
    }
}
