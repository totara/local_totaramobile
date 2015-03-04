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

/**
 * Course external functions
 *
 * @package    core_course
 * @category   external
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_totaramobile_course_external extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function get_course_contents_parameters() {
        return new external_function_parameters(
            array('courseid' => new external_value(PARAM_INT, 'course id'),
                'options' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_ALPHANUM, 'option name'),
                            'value' => new external_value(
                                PARAM_RAW, 'the value of the option, this param is personaly validated in the external function.'
                            )
                        )
                    ),
                    'Options, not used yet, might be used in later version', VALUE_DEFAULT, array()
                )
            )
        );
    }

    /**
     * Get course contents
     *
     * @param int $courseid course id
     * @param array $options These options are not used yet, might be used in later version
     * @return array
     */
    public static function get_course_contents($courseid, $options = array()) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . "/course/lib.php");

        //validate parameter
        $params = self::validate_parameters(self::get_course_contents_parameters(),
            array('courseid' => $courseid, 'options' => $options));

        $forcedescription = false;
        $uncached = false;
        foreach ($options as $option) {
            switch($option['name']) {
                case 'forcedescription':
                    $forcedescription = $option['value'] == 'true' ? true : false;
                    break;
                case 'uncached':
                    $uncached = $option['value'] == 'true' ? true : false;
                    break;
            }
        }

        //retrieve the course
        $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);

        //check course format exist
        if (!file_exists($CFG->dirroot . '/course/format/' . $course->format . '/lib.php')) {
            throw new moodle_exception(
                'cannotgetcoursecontents', 'webservice', '', null, get_string('courseformatnotfound', 'error', '', $course->format)
            );
        } else {
            require_once($CFG->dirroot . '/course/format/' . $course->format . '/lib.php');
        }

        // now security checks
        $context = context_course::instance($course->id, IGNORE_MISSING);
        try {
            self::validate_context($context);
        } catch (Exception $e) {
            $exceptionparam = new stdClass();
            $exceptionparam->message = $e->getMessage();
            $exceptionparam->courseid = $course->id;
            throw new moodle_exception('errorcoursecontextnotvalid', 'webservice', '', $exceptionparam);
        }

        $canupdatecourse = has_capability('moodle/course:update', $context);

        //create return value
        $coursecontents = array();

        if ($canupdatecourse or $course->visible
            or has_capability('moodle/course:viewhiddencourses', $context)) {

            //retrieve sections
            $modinfo = get_fast_modinfo($course, 0, false, $uncached);
            $sections = $modinfo->get_section_info_all();

            //for each sections (first displayed to last displayed)
            $modinfosections = $modinfo->get_sections();
            foreach ($sections as $key => $section) {

                if (!$section->uservisible) {
                    continue;
                }

                // reset $sectioncontents
                $sectionvalues = array();
                $sectionvalues['id'] = $section->id;
                $sectionvalues['name'] = get_section_name($course, $section);
                $sectionvalues['visible'] = $section->visible;
                list($sectionvalues['summary'], $sectionvalues['summaryformat']) =
                    external_format_text($section->summary, $section->summaryformat,
                        $context->id, 'course', 'section', $section->id);
                $sectioncontents = array();

                //for each module of the section
                if (!empty($modinfosections[$section->section])) {
                    foreach ($modinfosections[$section->section] as $cmid) {
                        $cm = $modinfo->cms[$cmid];

                        // stop here if the module is not visible to the user
                        if (!$cm->uservisible) {
                            continue;
                        }

                        $module = array();

                        //common info (for people being able to see the module or availability dates)
                        $module['id'] = $cm->id;
                        $module['name'] = format_string($cm->name, true);
                        $module['instance'] = $cm->instance;
                        $module['modname'] = $cm->modname;
                        $module['modplural'] = $cm->modplural;
                        $module['modicon'] = $cm->get_icon_url()->out(false);
                        $module['indent'] = $cm->indent;
                        $module['completion'] = $cm->completion;

                        $modcontext = context_module::instance($cm->id);

                        if ($forcedescription) {
                            $description = $DB->get_field($cm->modname, 'intro', array(
                                'id' => $cm->instance
                            ));
                            $module['description'] = $description ? $description : '';
                        } else {
                            if (!empty($cm->showdescription) or $cm->modname == 'label') {
                                // We want to use the external format. However from reading get_formatted_content(), $cm->content format is always FORMAT_HTML.
                                list($module['description'], $descriptionformat) = external_format_text($cm->content,
                                    FORMAT_HTML, $modcontext->id, $cm->modname, 'intro', $cm->id);
                            }
                        }

                        //url of the module
                        $url = $cm->url;
                        if ($url) { //labels don't have url
                            $module['url'] = $url->out(false);
                        }

                        $canviewhidden = has_capability(
                            'moodle/course:viewhiddenactivities', context_module::instance($cm->id)
                        );
                        // User that can view hidden module should know about the visibility
                        $module['visible'] = $cm->visible;

                        // Availability date (also send to user who can see hidden module when the showavailabilyt is ON)
                        if ($canupdatecourse or ($CFG->enableavailability && $canviewhidden && $cm->showavailability)) {
                            $module['availablefrom'] = $cm->availablefrom;
                            $module['availableuntil'] = $cm->availableuntil;
                        }

                        $baseurl = 'webservice/pluginfile.php';

                        // Call $modulename_export_contents
                        // (each module callback take care about checking the capabilities)
                        require_once($CFG->dirroot . '/mod/' . $cm->modname . '/lib.php');
                        $getcontentfunction = $cm->modname.'_export_contents';
                        if (function_exists($getcontentfunction)) {
                            if ($contents = $getcontentfunction($cm, $baseurl)) {
                                $module['contents'] = $contents;
                            }
                        }

                        // Assign result to $sectioncontents then add that
                        // ...user's completion status to the module.
                        $num_completions = $DB->count_records('course_modules_completion', array(
                            'userid' => $USER->id,
                            'coursemoduleid' => $cm->id,
                            'completionstate' => 1
                        ));
                        $module['completed'] = $num_completions > 0 ? 1 : 0;

                        // Assign result to $sectioncontents.
                        $sectioncontents[] = $module;

                    }
                }
                $sectionvalues['modules'] = $sectioncontents;

                // Assign result to $coursecontents.
                $coursecontents[] = $sectionvalues;
            }
        }
        return $coursecontents;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function get_course_contents_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'Section ID'),
                    'name' => new external_value(PARAM_TEXT, 'Section name'),
                    'visible' => new external_value(PARAM_INT, 'is the section visible', VALUE_OPTIONAL),
                    'summary' => new external_value(PARAM_RAW, 'Section description'),
                    'summaryformat' => new external_format_value('summary'),
                    'modules' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'id' => new external_value(PARAM_INT, 'activity id'),
                                'url' => new external_value(PARAM_URL, 'activity url', VALUE_OPTIONAL),
                                'name' => new external_value(PARAM_RAW, 'activity module name'),
                                'description' => new external_value(PARAM_RAW, 'activity description', VALUE_OPTIONAL),
                                'visible' => new external_value(PARAM_INT, 'is the module visible', VALUE_OPTIONAL),
                                'modicon' => new external_value(PARAM_URL, 'activity icon url'),
                                'modname' => new external_value(PARAM_PLUGIN, 'activity module type'),
                                'modplural' => new external_value(PARAM_TEXT, 'activity module plural name'),
                                'availability' => new external_value(PARAM_RAW, 'module availability settings', VALUE_OPTIONAL),
                                'indent' => new external_value(PARAM_INT, 'number of identation in the site'),
                                'completion' => new external_value(PARAM_INT, 'completion on/off'),
                                'completed' => new external_value(PARAM_BOOL, 'completed', VALUE_OPTIONAL),
                                'contents' => new external_multiple_structure(
                                    new external_single_structure(
                                        array(
                                            // content info
                                            'type'=> new external_value(PARAM_TEXT, 'a file or a folder or external link'),
                                            'filename'=> new external_value(PARAM_FILE, 'filename'),
                                            'filepath'=> new external_value(PARAM_PATH, 'filepath'),
                                            'filesize'=> new external_value(PARAM_INT, 'filesize'),
                                            'fileurl' => new external_value(PARAM_URL, 'downloadable file url', VALUE_OPTIONAL),
                                            'content' => new external_value(PARAM_RAW, 'Raw content, will be used when type is content', VALUE_OPTIONAL),
                                            'timecreated' => new external_value(PARAM_INT, 'Time created'),
                                            'timemodified' => new external_value(PARAM_INT, 'Time modified'),
                                            'sortorder' => new external_value(PARAM_INT, 'Content sort order'),

                                            // copyright related info
                                            'userid' => new external_value(PARAM_INT, 'User who added this content to moodle'),
                                            'author' => new external_value(PARAM_TEXT, 'Content owner'),
                                            'license' => new external_value(PARAM_TEXT, 'Content license'),
                                        )
                                    ), VALUE_DEFAULT, array()
                                )
                            )
                        ), 'list of module'
                    )
                )
            )
        );
    }

    /**
     * Returns description of method parameters for the list courses method.
     *
     * @return external_function_parameters
     */
    public static function list_courses_parameters() {
        return new external_function_parameters(
            array(
                'options' => new external_single_structure(
                    array(
                        'ids' => new external_multiple_structure(
                            new external_value(PARAM_INT, 'Course id'),
                            'List of course id. If empty return all courses
                            except front page course.',
                            VALUE_OPTIONAL
                        )
                    ),
                    'options - operator OR is used', VALUE_DEFAULT, array()
                )
            )
        );
    }

    /**
     * Gets the list of non-hidden courses for displaying on the mobile
     * application without the various permissions required by get_courses which
     * fail when a normal user tries to access the web service.
     *
     * TODO: this should also verify if user already enrolled (user is incorrectly to asked to enrol over and over again)
     *
     * @param array $options Options as defined by list_courses_parameters
     * @return array
     */
    public static function list_courses($options = array()) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/lib/enrollib.php");

        // Validate parameter.
        $params = self::validate_parameters(
            self::list_courses_parameters(), array('options' => $options)
        );

        // No need to validate context of individual courses here we just list throughout the site.
        self::validate_context(context_system::instance());

        // Respect the category visibility!
        $coursessql = "SELECT c.id, c.fullname, c.shortname, c.category, c.summary, c.summaryformat, c.format, c.startdate, c.sortorder
                         FROM {course} c
                         JOIN {course_categories} cc ON (c.category = cc.id)
                        WHERE c.visible = 1 AND cc.visible = 1";

        // Initial arguments are that we want visible courses.
        $arguments = array(1);

        // If we've been handed a list of ids though, we want to filter the list
        // ... of given ids for visible courses.
        if (array_key_exists('ids', $params['options']) &&
            !empty($params['options']['ids'])
        ) {
            // No guarantee arguments is an array - if it's not, make it one!
            $ids = $params['options']['ids'];
            if (!is_array($ids)) {
                $ids = array($ids);
            }

            // Create the IN statement and append to the SQL currently created.
            list($insql, $inparams) = $DB->get_in_or_equal($ids);
            $coursessql .= " AND c.id ".$insql;

            // The visible argument needs to be the first item in the array.
            $arguments = array_merge($arguments, $inparams);
        }
        $courses = $DB->get_records_sql($coursessql, $arguments);
        $courseinfo = array();
        foreach ($courses as $courseid => $course) {
            $context = context_course::instance($courseid, IGNORE_MISSING);
            $courseformatoptions = course_get_format($course)->get_format_options();

            list($summary, $summaryformat) = external_format_text(
                $course->summary, $course->summaryformat, $context->id,
                'course', 'summary', 0
            );
            $course->summary = $summary;
            $course->summaryformat = $summaryformat;

            if (array_key_exists('numsections', $courseformatoptions)) {
                // For backward-compartibility.
                $course->numsections = $courseformatoptions['numsections'];
            }

            // Get self enrolment status.
            $course->self_enrol_enabled = false;
            $course->self_enrol_key_required = false;
            $enrols = enrol_get_plugins(true);
            $enrolinstances = enrol_get_instances($course->id, true);
            foreach ($enrolinstances as $instance) {
                if (isset($enrols[$instance->enrol]) && $instance->enrol == 'self') {
                    $course->self_enrol_enabled = true;
                    if (isset($instance->password)) {
                        $course->self_enrol_key_required = true;
                    }
                    break;
                }
            }

            $courseinfo[] = $course;
        }

        return $courseinfo;
    }

    /**
     * Returns description of method result value for the list courses method.
     *
     * @return external_description The external description for list courses
     * @since Moodle 2.5
     */
    public static function list_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'course id'),
                    'fullname' => new external_value(PARAM_TEXT, 'full name'),
                    'shortname' => new external_value(PARAM_TEXT, 'course short name'),
                    'category' => new external_value(PARAM_INT, 'category id'),
                    'summary' => new external_value(PARAM_RAW, 'summary'),
                    'summaryformat' => new external_format_value('summary'),
                    'format' => new external_value(
                        PARAM_PLUGIN,
                        'course format: weeks, topics, social, site,..'
                    ),
                    'startdate' => new external_value(
                        PARAM_INT,
                        'timestamp when the course start'
                    ),
                    'numsections' => new external_value(
                        PARAM_INT,
                        '(deprecated, use courseformatoptions) number of weeks/topics',
                        VALUE_OPTIONAL
                    ),
                    'sortorder' => new external_value(
                        PARAM_INT,
                        'sort order into the category',
                        VALUE_OPTIONAL
                    ),
                    'self_enrol_enabled' => new external_value(
                        PARAM_BOOL, 'Self enrol enabled'
                    ),
                    'self_enrol_key_required' => new external_value(
                        PARAM_BOOL, 'Self enrol key required'
                    ),
                ),
                'course'
            )
        );
    }

    /**
     * Set activity completion parameters
     *
     * @return external_function_parameters
     */
    public static function set_activity_completion_parameters() {
        return new external_function_parameters(
            array(
                'cmid'      => new external_value(PARAM_INT,    'Course module ID'),
                'completed' => new external_value(PARAM_BOOL,   'Completion status'),
                'createcoursecomp' => new external_value(PARAM_BOOL, 'Create course comp', VALUE_OPTIONAL)
            )
        );
    }

    /**
     * Set activity completion
     *
     * @param int $cmid
     */
    public static function set_activity_completion($cmid, $completed, $createcoursecomp) {
        global $CFG, $DB, $USER;
        require_once($CFG->libdir.'/completionlib.php');
        require_once($CFG->dirroot.'/totara/program/lib.php');

        $cm = $DB->get_record('course_modules', array('id'=>$cmid));
        $course = $DB->get_record('course', array('id'=>$cm->course));

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $completion = new completion_info($course);
        if ($cm->completion) {
            $completion_param = $completed ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
            $completion->update_state($cm, $completion_param, $USER->id);
            if ($createcoursecomp) {
                prog_can_enter_course($USER, $course);
            }
            return $completed;
        }
        throw new moodle_exception('completionnotenabled', 'completion');
    }

    /**
     * Set activity completion returns
     *
     * @return external_description
     */
    public static function set_activity_completion_returns() {
        return new external_value(PARAM_BOOL, 'Completion status');
    }
}
