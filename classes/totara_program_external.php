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
require_once("$CFG->dirroot/totara/program/program.class.php");

class local_totaramobile_totara_program_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_programs_parameters() {
        return new external_function_parameters(
            array(
                'options' => new external_single_structure(
                    array('ids' => new external_multiple_structure(
                            new external_value(PARAM_INT, 'Program id')
                            , 'List of program id. If empty return all programs
                                except front page program.',
                            VALUE_OPTIONAL)
                    ), 'options - operator OR is used', VALUE_DEFAULT, array())
            )
        );
    }

    /**
     * Get programs
     *
     * @param array $options It contains an array (list of ids)
     * @return array
     */
    public static function get_programs($options = array()) {
        global $DB, $USER;

        // Validate parameter.
        $params = self::validate_parameters(self::get_programs_parameters(), array('options' => $options));

        self::validate_context(\context_system::instance());

        // Retrieve programs.
        if (!array_key_exists('ids', $params['options'])  or empty($params['options']['ids'])) {
            $programs = $DB->get_records('prog');
        } else {
            $programs = $DB->get_records_list('prog', 'id', $params['options']['ids']);
        }

        // Create return value
        $programsinfo = array();
        foreach ($programs as $program) {
            $programinfo = array();
            $programinfo['id'] = $program->id;
            $programinfo['fullname'] = $program->fullname;
            $programinfo['shortname'] = $program->shortname;
            $programinfo['categoryid'] = $program->category;
            $programinfo['summary'] = $program->summary;
            $programinfo['categorysortorder'] = $program->sortorder;
            $programinfo['idnumber'] = $program->idnumber;
            $programinfo['visible'] = $program->visible;
            $programinfo['timecreated'] = $program->timecreated;
            $programinfo['timemodified'] = $program->timemodified;

            // Completion status
            $comp = $DB->get_record('prog_completion', array(
                'userid' => $USER->id,
                'programid' => $program->id,
                'coursesetid' => 0
            ));
            $programinfo['completed'] = false;
            if ($comp) {
                $programinfo['completed'] = $comp->status == '1';
            }

            $programsinfo[] = $programinfo;
        }

        return $programsinfo;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_programs_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'program id'),
                    'fullname' => new external_value(PARAM_TEXT, 'full name'),
                    'shortname' => new external_value(PARAM_TEXT, 'program short name'),
                    'categoryid' => new external_value(PARAM_INT, 'category id'),
                    'summary' => new external_value(PARAM_RAW, 'summary'),
                    'completed' => new external_value(PARAM_BOOL, 'completed'),
                    'categorysortorder' => new external_value(PARAM_INT,
                        'sort order into the category', VALUE_OPTIONAL),
                    'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL),
                    'visible' => new external_value(PARAM_INT,
                        '1: available to student, 0:not available', VALUE_OPTIONAL),
                    'timecreated' => new external_value(PARAM_INT,
                        'timestamp when the program have been created', VALUE_OPTIONAL),
                    'timemodified' => new external_value(PARAM_INT,
                        'timestamp when the program have been modified', VALUE_OPTIONAL),
                ), 'program'
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_users_programs_parameters() {
        return new external_function_parameters(
            array(
                'options' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_ALPHANUM, 'option name'),
                            'value' => new external_value(PARAM_RAW, 'the value of the option, this param is personaly validated in the external function.'
                        )
                    )
                ), 'Options, not used yet, might be used in later version', VALUE_DEFAULT, array())
            )
        );
    }

    /**
     * Get users programs
     *
     * @param array $options It contains an array (list of ids)
     * @return array
     */
    public static function get_users_programs($options = array()) {
        global $CFG, $DB, $USER;

        $params = self::validate_parameters(self::get_users_programs_parameters(), array('options' => $options));

        self::validate_context(\context_system::instance());

        $sql = "
            SELECT prog.*
            FROM {prog} prog
            JOIN {dp_plan_program_assign} ass ON (ass.programid=prog.id)
            JOIN {dp_plan} plan ON (plan.id=ass.planid)
            WHERE plan.userid=?
        ";
        $programs = $DB->get_records_sql($sql, array($USER->id));
        $programsinfo = array();
        foreach ($programs as $program) {
            $programinfo = array();
            $programinfo['id'] = $program->id;
            $programinfo['fullname'] = $program->fullname;
            $programinfo['shortname'] = $program->shortname;
            $programinfo['categoryid'] = $program->category;
            $programinfo['summary'] = $program->summary;
            $programinfo['categorysortorder'] = $program->sortorder;
            $programinfo['idnumber'] = $program->idnumber;
            $programinfo['visible'] = $program->visible;
            $programinfo['timecreated'] = $program->timecreated;
            $programinfo['timemodified'] = $program->timemodified;
            $programsinfo[] = $programinfo;
        }

        return $programsinfo;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_users_programs_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'program id'),
                    'fullname' => new external_value(PARAM_TEXT, 'full name'),
                    'shortname' => new external_value(PARAM_TEXT, 'program short name'),
                    'categoryid' => new external_value(PARAM_INT, 'category id'),
                    'summary' => new external_value(PARAM_RAW, 'summary'),
                    'categorysortorder' => new external_value(PARAM_INT,
                        'sort order into the category', VALUE_OPTIONAL),
                    'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL),
                    'visible' => new external_value(PARAM_INT,
                        '1: available to student, 0:not available', VALUE_OPTIONAL),
                    'timecreated' => new external_value(PARAM_INT,
                        'timestamp when the program have been created', VALUE_OPTIONAL),
                    'timemodified' => new external_value(PARAM_INT,
                        'timestamp when the program have been modified', VALUE_OPTIONAL),
                ), 'program'
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_users_courses_parameters() {
        return new external_function_parameters(
            array(
                'options' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_ALPHANUM, 'option name'),
                            'value' => new external_value(
                                PARAM_RAW, 'the value of the option, this param is personally validated in the external function.'
                            )
                        )
                    ), 'Options, not used yet, might be used in later version', VALUE_DEFAULT, array()
                )
            )
        );
    }

    /**
     * Get users courses
     *
     * @param array $options It contains an array (list of ids)
     * @return array
     */
    public static function get_users_courses($options = array()) {
        global $CFG, $DB, $USER;
        require_once("{$CFG->libdir}/completionlib.php");

        $params = self::validate_parameters(self::get_users_courses_parameters(), array('options' => $options));

        self::validate_context(\context_system::instance());

        $enrolledcourses = enrol_get_users_courses($USER->id, true, 'id, shortname, fullname, idnumber, visible');

        /*
         * NOW GRAB A LIST OF LEARNING PLAN COURSES
         * (This bit obvs isn't in the Moodle WS)
         * TODO: Exclude unapproved items, draft plans or inactive plans.
         */
        $sql = "
            SELECT course.*
            FROM {course} course
            JOIN {dp_plan_course_assign} ass ON (ass.courseid=course.id)
            JOIN {dp_plan} p ON (p.id=ass.planid)
            WHERE p.userid=?
        ";
        $plancourses = $DB->get_records_sql($sql, array($USER->id));

        /*
         * FINALLY MERGE THE LISTS AND ADD COMPLETION COUNTS
         */
        $courses = $enrolledcourses + $plancourses;
        foreach($courses as $course) {
            $info = new completion_info($course);
            $completions = $info->get_completions($USER->id);
            $completionstotal = count($completions);
            $completionscompleted = 0;
            foreach($completions as $completion) {
                if ($completion->is_complete()) {
                    $completionscompleted++;
                }
            }
            $course->started = $completionscompleted > 0 ? 1 : 0;
            $course->completed = $completionstotal > 0 && $completionscompleted >= $completionstotal ? 1 : 0;
            $course->completions_total = $completionstotal;
            $course->completions_completed = $completionscompleted;
        }
        return $courses;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_users_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'                    => new external_value(PARAM_INT, 'id of course'),
                    'shortname'             => new external_value(PARAM_RAW, 'short name of course'),
                    'fullname'              => new external_value(PARAM_RAW, 'long name of course'),
                    'idnumber'              => new external_value(PARAM_RAW, 'id number of course'),
                    'started'               => new external_value(PARAM_BOOL, 'started'),
                    'completed'             => new external_value(PARAM_BOOL, 'started'),
                    'completions_total'     => new external_value(PARAM_INT, 'started'),
                    'completions_completed' => new external_value(PARAM_INT, 'started'),
                    'visible'               => new external_value(PARAM_INT, '1 means visible, 0 means hidden course'),
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_users_required_programs_parameters() {
        return new external_function_parameters(
            array(
                'options' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_ALPHANUM, 'option name'),
                            'value' => new external_value(
                                PARAM_RAW, 'the value of the option, this param is personally validated in the external function.'
                            )
                        )
                    ), 'Options, not used yet, might be used in later version', VALUE_DEFAULT, array()
                )
            )
        );
    }

    /**
     * Get users programs
     *
     * @param array $options It contains an array (list of ids)
     * @return array
     */
    public static function get_users_required_programs($options = array()) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/totara/program/lib.php');

        $params = self::validate_parameters(self::get_users_required_programs_parameters(), array('options' => $options));

        self::validate_context(\context_system::instance());

        $programs = prog_get_all_programs($USER->id, '', '', '', false, false, true);
        $programsinfo = array();
        foreach ($programs as $program) {
            $programinfo = array();
            $programinfo['id'] = $program->id;
            $programinfo['fullname'] = $program->fullname;
            $programinfo['shortname'] = $program->shortname;
            $programinfo['categoryid'] = $program->category;
            $programinfo['summary'] = $program->summary;
            $programinfo['categorysortorder'] = $program->sortorder;
            $programinfo['idnumber'] = $program->idnumber;
            $programinfo['visible'] = $program->visible;
            $programinfo['timecreated'] = $program->timecreated;
            $programinfo['timemodified'] = $program->timemodified;
            $programsinfo[] = $programinfo;
        }
        return $programsinfo;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_users_required_programs_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'program id'),
                    'fullname' => new external_value(PARAM_TEXT, 'full name'),
                    'shortname' => new external_value(PARAM_TEXT, 'program short name'),
                    'categoryid' => new external_value(PARAM_INT, 'category id'),
                    'summary' => new external_value(PARAM_RAW, 'summary'),
                    'categorysortorder' => new external_value(PARAM_INT,
                        'sort order into the category', VALUE_OPTIONAL),
                    'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL),
                    'visible' => new external_value(PARAM_INT,
                        '1: available to student, 0:not available', VALUE_OPTIONAL),
                    'timecreated' => new external_value(PARAM_INT,
                        'timestamp when the program have been created', VALUE_OPTIONAL),
                    'timemodified' => new external_value(PARAM_INT,
                        'timestamp when the program have been modified', VALUE_OPTIONAL),
                ), 'program'
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_program_parameters() {
        return new external_function_parameters(
            array(
                'programid' => new external_value(PARAM_INT, 'program id'),
                'options' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_ALPHANUM, 'option name'),
                            'value' => new external_value(PARAM_RAW, 'the value of the option, this param is personaly validated in the external function.'
                        )
                    )
                ), 'Options, not used yet, might be used in later version', VALUE_DEFAULT, array())
            )
        );
    }

    /**
     * Get programs
     *
     * @param array $options It contains an array (list of ids)
     * @return array
     */
    public static function get_program($programid, $options=array()) {
        global $DB, $CFG, $USER;

        $params = self::validate_parameters(self::get_program_parameters(), array('programid' => $programid, 'options' => $options));
        $programid = $params['programid'];
        $options = $params['options'];

        self::validate_context(\context_system::instance());

        $program = new program($programid);
        $prog_completion = $DB->get_record('prog_completion', array(
            'programid'     => $programid,
            'userid'        => $USER->id,
            'coursesetid'   => 0
        ));
        if ($prog_completion) {
            $enrolled = true;
            $startdate = ($prog_completion->timestarted != 0)
                ?  userdate($prog_completion->timestarted, get_string('strftimedate', 'langconfig'), $CFG->timezone, false)
                : get_string('nostartdate', 'totara_program');

            $duedate = (empty($prog_completion->timedue) || $prog_completion->timedue == COMPLETION_TIME_NOT_SET)
                ? get_string('duedatenotset', 'totara_program')
                : userdate($prog_completion->timedue, get_string('strftimedate', 'langconfig'), $CFG->timezone, false);
        } else {
            $enrolled = false;
            $startdate = get_string('nostartdate', 'totara_program');
            $duedate = get_string('duedatenotset', 'totara_program');
        }
        $progress = 0;
        if ($enrolled) {
            if ($prog_completion->status == STATUS_PROGRAM_COMPLETE) {
                $progress = 100;
            } else {
                $progress = $program->get_progress($USER->id);
            }
        }

        // Course sets.
        $return_sets = array();
        $sets_sql = 'SELECT * FROM {prog_courseset} WHERE programid=? ORDER BY sortorder';
        $sets = $DB->get_records_sql($sets_sql, array($programid));
        foreach ($sets as $set) {

            // Course set completion status
            $completion_status = $DB->get_field(
                'prog_completion',
                'status',
                array(
                    'coursesetid' => $set->id,
                    'programid' => $programid,
                    'userid' => $USER->id
                )
            );
            $course_set_is_complete = $completion_status == STATUS_COURSESET_COMPLETE;

            $comp_type_str = $set->completiontype == COMPLETIONTYPE_ALL ? 'completeallcourses' : 'completeanycourse';
            $return_courses = array();
            $courses_sql = '
                SELECT c.*
                FROM {course} c
                INNER JOIN {prog_courseset_course} csc ON csc.courseid = c.id
                WHERE csc.coursesetid = ?
            ';
            $courses = $DB->get_records_sql($courses_sql, array($set->id));
            foreach ($courses as $course) {
                //error_log(COMPLETION_STATUS_NOTYETSTARTED);
                if (!$status = $DB->get_field('course_completions', 'status', array('userid'=>$USER->id, 'course'=>$course->id))) {
                    $status = COMPLETION_STATUS_NOTYETSTARTED;
                }
                array_push($return_courses, array(
                    'id'        => $course->id,
                    'shortname' => $course->shortname,
                    'fullname'  => $course->fullname,
                    'status'    => $status
                ));
            }
            array_push($return_sets, array(
                'id'                => $set->id,
                'label'             => $set->label,
                'completiontype'    => get_string($comp_type_str, 'totara_program'),
                'courses'           => $return_courses,
                'iscomplete'        => $course_set_is_complete
            ));
        }

        $program->endnote = file_rewrite_pluginfile_urls(
            $program->endnote,
            'pluginfile.php',
            \context_program::instance($program->id)->id,
            'totara_program',
            'endnote',
            0);

        return array(
            'id'        => $program->id,
            'shortname' => $program->shortname,
            'fullname'  => $program->fullname,
            'summary'   => $program->summary,
            'endnote'   => $program->endnote,
            'startdate' => $startdate,
            'duedate'   => $duedate,
            'enrolled'  => $enrolled,
            'progress'  => $progress,
            'sets'      => $return_sets
        );
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_program_returns() {
        return new external_single_structure(array(
            'id'        => new external_value(PARAM_INT, 'Program ID'),
            'shortname' => new external_value(PARAM_TEXT, 'short name'),
            'fullname'  => new external_value(PARAM_TEXT, 'full name'),
            'summary'   => new external_value(PARAM_RAW, 'summary'),
            'endnote'   => new external_value(PARAM_RAW, 'endnote'),
            'startdate' => new external_value(PARAM_TEXT, 'start date'),
            'duedate'   => new external_value(PARAM_TEXT, 'due date'),
            'enrolled'  => new external_value(PARAM_BOOL, 'enrolled'),
            'progress'  => new external_value(PARAM_INT, 'progress'),
            'sets' => new external_multiple_structure(new external_single_structure(array(
                'id'                => new external_value(PARAM_INT, 'Set ID'),
                'label'             => new external_value(PARAM_TEXT, 'label'),
                'completiontype'    => new external_value(PARAM_TEXT, 'completion type'),
                'iscomplete'        => new external_value(PARAM_BOOL, 'is complete'),
                'courses'           => new external_multiple_structure(new external_single_structure(array(
                    'id'        => new external_value(PARAM_INT, 'Course ID'),
                    'shortname' => new external_value(PARAM_TEXT, 'short name'),
                    'fullname'  => new external_value(PARAM_TEXT, 'full name'),
                    'status'    => new external_value(PARAM_TEXT, 'status')
                )))
            )))
        ));
    }
}
