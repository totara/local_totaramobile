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
 * @author Dominic Orme <dominic.orme@kineo.com>
 * @package local_totaramobile
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * Class local_totaramobile_mod_feedback_external
 *
 * TODO: this looks unfinished and very buggy === not suitable for productions use!
 */
class local_totaramobile_mod_feedback_external extends external_api {
    public static function get_questions_parameters() {
        return new external_function_parameters(
            array(
                'coursemoduleid' => new external_value(
                    PARAM_INT, 'id of the course module id, assumes that it\'s a feedback id', VALUE_REQUIRED
                )
            )
        );
    }

    public static function get_questions($coursemoduleid) {
        global $CFG, $DB, $USER;
        require_once($CFG->libdir.'/filelib.php');

        $params = self::validate_parameters(self::get_questions_parameters(), array('coursemoduleid' => $coursemoduleid));
        $coursemoduleid = $params['coursemoduleid'];

        $userid = $USER->id;

        $cm = get_coursemodule_from_id('feedback', $coursemoduleid);
        $course = $DB->get_record('course', array('id' => $cm->course));
        $feedback = $DB->get_record('feedback', array('id' => $cm->instance));

        $context = \context_module::instance($cm->id);
        $coursecontext = \context_course::instance($course->id);

        self::validate_context($context);
        require_capability('mod/feedback:complete', $context);

        // TODO: add more access control here

        // Get the questions that we want.
        $sql = "SELECT fi.id, fi.name, fi.label, fi.presentation, fi.typ, fi.position, fi.required,
                       fi.feedback, fi.dependitem as dependent_item, fi.dependvalue as dependent_value,
                       fi.options
                  FROM {feedback} f
             LEFT JOIN {feedback_item} fi ON fi.feedback = f.id
                 WHERE f.id = ?
              ORDER BY fi.position ASC";
        $questions = $DB->get_records_sql($sql, array($feedback->id));
        $answers = array();

        $completedid = self::_get_completed_id($userid, $feedback->id);

        if ($completedid !== 0) {
            // If the user has already started answering questions, grab their
            // existing answers too.
            if (self::_already_started($userid, $feedback->id, $completedid)) {
                // TODO: right joins are forbidden!
                $sql = "SELECT fv.id, fv.item, fv.value
                          FROM {feedback_value} fv
                    RIGHT JOIN {feedback_completed} fc ON fc.id = fv.completed
                         WHERE fc.userid = ? AND fc.feedback = ?";
                $answers = $DB->get_records_sql($sql, array($userid, $feedback->id));
            }
        }

        // Insert the correct presentation value for 'info' questions,
        //  & replace the pluginfile placeholder for others
        if ($course->id !== SITEID) {
            $coursecategory = $DB->get_record('course_categories', array('id'=>$course->category));
        } else {
            $coursecategory = false;
        }
        foreach($questions as $key => $question) {
            if ($question->typ == 'info') {
                switch($question->presentation) {
                    case 1:
                        if ($feedback->anonymous == FEEDBACK_ANONYMOUS_YES) {
                            $itemvalue = 0;
                            $itemshowvalue = '-';
                        } else {
                            $itemvalue = time();
                            $itemshowvalue = userdate($itemvalue);
                        }
                        break;
                    case 2:
                        $itemvalue = format_string($course->shortname,
                            true,
                            array('context' => $coursecontext));

                        $itemshowvalue = $itemvalue;
                        break;
                    case 3:
                        if ($coursecategory) {
                            $category_context = context_coursecat::instance($coursecategory->id);
                            $itemvalue = format_string($coursecategory->name,
                                true,
                                array('context' => $category_context));

                            $itemshowvalue = $itemvalue;
                        } else {
                            $itemvalue = '';
                            $itemshowvalue = '';
                        }
                        break;
                }
                $question->presentation = $itemshowvalue;
            } else {
                // TODO: this will not work in mobile app!
                $question->presentation = file_rewrite_pluginfile_urls(
                    $question->presentation,
                    'pluginfile.php',
                    $context->id,
                    'mod_feedback',
                    'item',
                    $question->id);
            }
        }

        // Replace the pluginfile placeholder for the feedback intro
        // TODO: this will not work in mobile app!
        $feedback->intro = file_rewrite_pluginfile_urls(
            $feedback->intro,
            'pluginfile.php',
            $context->id,
            'mod_feedback',
            'intro',
            0);

        $response = array(
            'feedback' => $feedback,
            'questions' => $questions,
            'answers' => $answers
        );

        return $response;
    }

    public static function get_questions_returns() {
        return new external_single_structure(
            array(
                'feedback' => new external_single_structure(
                    array(
                        'id' => new external_value(
                            PARAM_INT, 'Id of the feedback retrieved'
                        ),
                        'name' => new external_value(
                            PARAM_TEXT, 'Name of the feedback'
                        ),
                        'intro' => new external_value(
                            PARAM_RAW, 'Introduction to the feedback'
                        ),
                        'page_after_submit' => new external_value(
                            PARAM_RAW, 'Message to show after submission'
                        ),
                        'course' => new external_value(
                            PARAM_INT, 'Id of course this feedback relates to'
                        )
                    )
                ),
                'questions' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Question id'),
                            'name' => new external_value(PARAM_TEXT, 'Question name'),
                            'label' => new external_value(PARAM_TEXT, 'Question label'),
                            'presentation' => new external_value(PARAM_RAW, 'Question presentation'),
                            'typ' => new external_value(PARAM_TEXT, 'Question type'),
                            'position' => new external_value(
                                PARAM_INT, 'Position of question within feedback'
                            ),
                            'required' => new external_value(
                                PARAM_INT, 'Whether the answering of this question is required before submission is allowed'
                            ),
                            'feedback' => new external_value(
                                PARAM_INT, 'Feedback id this question relates to.'
                            ),
                            'dependent_item' => new external_value(
                                PARAM_INT, 'Can only be answered after dependent item with specified id. 0 means no dependency.'
                            ),
                            'dependent_value' => new external_value(
                                PARAM_TEXT, 'Can only be answered after dependent item with specified id in dependent_item has this value.'
                            ),
                            'options' => new external_value(
                                PARAM_TEXT, 'Any other options for this question type.'
                            )
                        )
                    )
                ),
                // 'Questions and Current answers for this feedback'
                'answers' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'answer id'),
                            'item' => new external_value(PARAM_INT, 'related question'),
                            'value' => new external_value(PARAM_TEXT, 'current answer')
                        )
                    )
                )
            )
        );
    }

    public static function send_answers_parameters() {
        return new external_function_parameters(
            array(
                'answers' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'questionid' => new external_value(PARAM_INT, 'ID of the question'),
                            'answer' => new external_value(PARAM_RAW, 'answer', VALUE_REQUIRED)
                        )
                    ), 'answers to questions'
                )
            )
        );
    }

    /**
     * Stores the answers for a specific feedback into the database. If the
     * question has already been answered, then the answer is updated rather
     * than a new answer value being stored in the database.
     *
     * @param array $answers An array of arrays, where each sub array is the
     *                       details of an answer with the following keys:
     *                       questionid, answer.
     * @return array
     */
    public static function send_answers($answers) {
        global $DB, $USER;

        $params = self::validate_parameters(self::send_answers_parameters(), array('answers' => $answers));
        $answers = $params['answers'];

        $userid = $USER->id;
        $response = array();

        $first = reset($answers);
        $fi = $DB->get_record('feedback_item', array('id' => $first['questionid']), '*', MUST_EXIST);
        $feedback = $DB->get_record('feedback', array('id' => $fi->feedback), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('feedback', $feedback->id);
        $course = $DB->get_record('course', array('id' => $cm->course));

        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('mod/feedback:complete', $context);

        // TODO: add more access control here

        $completedid = self::_get_completed_id($userid, $feedback->id);

        if ($completedid !== 0) {
            foreach ($answers as $answer) {
                // TODO: verify the answer is from the same feedback!
                $questionid = $answer['questionid'];
                $answer = $answer['answer'];
                $answerobj = new stdClass();
                $answerobj->course_id = $course->id;
                $answerobj->item = $questionid;
                $answerobj->completed = $completedid;
                $answerobj->tmp_completed = 0;
                $answerobj->value = $answer;

                // Does this answer already exist?
                $sql = "SELECT fv.id
                          FROM {feedback_value} fv
                         WHERE fv.item = ? AND course_id = ? AND completed = ?";

                $found = $DB->get_record_sql($sql, array($questionid, $course->id, $completedid));
                if (!$found) {
                    // No results or something went wrong.
                    // Who cares - attempt to insert the record.
                    $insertid = $DB->insert_record('feedback_value', $answerobj, true, false);
                    $response[] = array(
                        'id' => $insertid,
                        'questionId' => $questionid,
                        'value' => $answer,
                        'success' => true,
                        'error' => '',
                        'answer' => $answerobj->value
                    );
                } else {
                    $answerobj->id = $found->id;
                    $error = "";
                    $success = true;
                    try {
                        $DB->update_record('feedback_value', $answerobj, false);
                    } catch (dml_exception $e) {
                        $error = $e->getMessage();
                        $success = false;
                    }
                    $response[] = array(
                        'id' => $found->id,
                        'questionId' => $questionid,
                        'success' => $success,
                        'error' => $error,
                        'answer' => $answer
                    );
                }
            }

            // Insert into the feedback tracking table.
            self::_start_feedback($userid, $feedback->id, $completedid);
        }

        return array('answers' => $response);
    }

    public static function send_answers_returns() {
        return new external_single_structure(
            array(
                'answers' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Answer id'),
                            'questionId' => new external_value(PARAM_INT, 'Question id'), // TODO: no capital letters in param names!
                            'success' => new external_value(
                                PARAM_INT, 'Success saving to db'
                            ),
                            'error' => new external_value(
                                PARAM_TEXT, 'Any errors from saving this answer'
                            ),
                            'answer' => new external_value(
                                PARAM_RAW, 'Raw answer text, however it was submitted'
                            )
                        )
                    ),
                    'The answers that have been successfully saved ( by id ), and the error in the event there was one.'
                ),
            )
        );
    }

    /**
     * Whether the specified user has got saved answers for the specified
     * feedback as defined by the feedback_tracking table.
     *
     * @param int $userid     The user id to check
     * @param int $feedbackid The feedback id to check for
     *
     * @return bool TRUE if feedback has already started, FALSE otherwise.
     */
    private static function _already_started($userid, $feedbackid, $completedid) {
        global $DB;

        return $DB->record_exists_select(
            'feedback_tracking', 'userid = ? AND feedback = ? AND completed = ?',
            array($userid, $feedbackid, $completedid)
        );
    }

    /**
     * Inserts sufficient information into the feedback_tracking table
     * to specify a feedback as 'started'.
     *
     * @param int $userid     The user that is doing the feedback
     * @param int $feedbackid The feedback that is to be started.
     *
     * @return bool TRUE if feedback is newly started, FALSE if there is already
     *              a record for this user/feedback.
     */
    private static function _start_feedback($userid, $feedbackid, $completedid) {
        global $DB;

        // Response will be false if the feedback tracking already exists.
        $response = !(self::_already_started($userid, $feedbackid, $completedid));

        if ($response) {
            $feedbacktrackingobj = new stdClass();
            $feedbacktrackingobj->userid = $userid;
            $feedbacktrackingobj->feedback = $feedbackid;
            $feedbacktrackingobj->completed = $completedid;
            $DB->insert_record('feedback_tracking', $feedbacktrackingobj, false, false);
        }

        return $response;
    }

    /**
     * Gets or sets the completed feedback id from the feedback_completed
     * table.
     * Used to map user ids to the question answers that are theirs.
     * Anonymous flag set to 2 (not anonymous)
     *
     * @param int $userid     The user id to query for
     * @param int $feedbackid The feedback we're checking for
     *
     * @return int $completedid The table row id to be used with the
     *                          feedback_values table
     */
    private static function _get_completed_id($userid, $feedbackid) {
        global $DB;

        $sql = "SELECT fc.id
                  FROM {feedback_completed} fc
                 WHERE fc.userid = ? AND fc.feedback = ?";

        $results = $DB->get_records_sql($sql, array($userid, $feedbackid));
        if (count($results) > 0) {
            $keys = array_keys($results);
            $completedid = $keys[0];
        } else {
            // Insert into the completed table
            $completed = new stdClass();
            $completed->feedback = $feedbackid;
            $completed->userid = $userid;
            $completed->timemodified = time();
            $completed->anonymous_response = 2;
            $completedid = $DB->insert_record('feedback_completed', $completed, true, false);
        }

        return $completedid;
    }
}

