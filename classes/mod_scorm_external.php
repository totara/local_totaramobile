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

class local_totaramobile_mod_scorm_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_attempt_status_parameters() {
        return new external_function_parameters(
            array('cmid' => new external_value(PARAM_INT, 'Course module id'))
        );
    }

    /**
     * Returns the attempt status info for the current user
     * @param int $cmid the course module id
     * @return array the attempt status info
     */
    public static function get_attempt_status($cmid) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . "/mod/scorm/locallib.php");

        $params = self::validate_parameters(self::get_attempt_status_parameters(), array('cmid' => $cmid));

        $cm = get_coursemodule_from_id('scorm', $params['cmid']);
        $scorm = $DB->get_record('scorm', array('id' => $cm->instance));
        $course = $DB->get_record('course', array('id' => $cm->course));
        $context = \context_module::instance($cm->id);

        self::validate_context($context);

        $attemptstatus = array(
            'id'    => $scorm->id,
            'cmid'  => $cmid,
            'scorm_name'        => $scorm->name,
            'scorm_intro'       => $scorm->intro,
            'course_short_name' => $course->shortname,
            'course_full_name'  => $course->fullname,
            'compat_phone' => 1, // TODO
            'compat_tablet' => 1, // TODO
        );

        // Num attempts allowed.
        $attemptstatus['num_attempts_allowed'] = $scorm->maxattempt;

        // Num attempts made.
        $attempts = scorm_get_attempt_count($USER->id, $scorm, true);
        if (empty($attempts)) {
            $attemptcount = 0;
        } else {
            $attemptcount = count($attempts);
        }
        $attemptstatus['num_attempts_made'] = $attemptcount;

        // Grading method.
        if ($scorm->maxattempt == 1) {
            switch ($scorm->grademethod) {
                case GRADEHIGHEST:
                    $grademethod = get_string('gradehighest', 'scorm');
                    break;
                case GRADEAVERAGE:
                    $grademethod = get_string('gradeaverage', 'scorm');
                    break;
                case GRADESUM:
                    $grademethod = get_string('gradesum', 'scorm');
                    break;
                case GRADESCOES:
                    $grademethod = get_string('gradescoes', 'scorm');
                    break;
            }
        } else {
            switch ($scorm->whatgrade) {
                case HIGHESTATTEMPT:
                    $grademethod = get_string('highestattempt', 'scorm');
                    break;
                case AVERAGEATTEMPT:
                    $grademethod = get_string('averageattempt', 'scorm');
                    break;
                case FIRSTATTEMPT:
                    $grademethod = get_string('firstattempt', 'scorm');
                    break;
                case LASTATTEMPT:
                    $grademethod = get_string('lastattempt', 'scorm');
                    break;
            }
        }
        $attemptstatus['grading_method'] = $grademethod;

        // Attempt grades.
        $attemptstatus['attempt_grades'] = array();
        if (!empty($attempts)) {
            $i = 1;
            foreach ($attempts as $attempt) {
                $gradereported = scorm_grade_user_attempt($scorm, $USER->id, $attempt->attemptnumber);
                if ($scorm->grademethod !== GRADESCOES && !empty($scorm->maxgrade)) {
                    $gradereported = $gradereported/$scorm->maxgrade;
                    $gradereported = number_format($gradereported*100, 0) .'%';
                }
                $attemptstatus['attempt_grades'][] = $gradereported;
                $i++;
            }
        }

        // Grade reported.
        $calculatedgrade = scorm_grade_user($scorm, $USER->id);
        if ($scorm->grademethod !== GRADESCOES && !empty($scorm->maxgrade)) {
            $calculatedgrade = $calculatedgrade/$scorm->maxgrade;
            $calculatedgrade = number_format($calculatedgrade*100, 0) .'%';
        }
        if (empty($attempts)) {
            $attemptstatus['grade_reported'] = get_string('none');
        } else {
            $attemptstatus['grade_reported'] = $calculatedgrade;
        }

        return $attemptstatus;
    }

    /**
     * Returns description of method return value
     * @return external_single_structure
     */
    public static function get_attempt_status_returns() {
        return new external_single_structure(
            array(
                'id'                    => new external_value(PARAM_INT,    'Scorm ID'),
                'cmid'                  => new external_value(PARAM_INT,    'Course module ID'),
                'scorm_name'            => new external_value(PARAM_TEXT,   'Scorm name'),
                'scorm_intro'           => new external_value(PARAM_RAW,    'Scorm intro'),
                'course_short_name'     => new external_value(PARAM_TEXT,   'Course short name'),
                'course_full_name'      => new external_value(PARAM_TEXT,   'Course full name'),
                'num_attempts_allowed'  => new external_value(PARAM_INT,    'Number of attempts allowed'),
                'num_attempts_made'     => new external_value(PARAM_INT,    'Number of attempts made'),
                'grading_method'        => new external_value(PARAM_TEXT,   'Grading method'),
                'grade_reported'        => new external_value(PARAM_TEXT,   'Grade reported'),
                'attempt_grades'        => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Attempt grade')
                ),
                'compat_phone'          => new external_value(
                    PARAM_INT, 'Scorm compatible with phone. TRUE = 1, FALSE = 0'
                ),
                'compat_tablet'         => new external_value(
                    PARAM_INT, 'Scorm compatible with tablet. TRUE = 1, FALSE = 0'
                )
            )
        );
    }
}
