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

class local_totaramobile_totara_message_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_alerts_parameters() {
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
     * Get alerts
     *
     * @param array $options It contains an array (list of ids)
     * @return array
     */
    public static function get_alerts($options=array()) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/totara/message/messagelib.php');

        $params = self::validate_parameters(self::get_alerts_parameters(), array('options' => $options));
        $options = $params['options'];

        self::validate_context(\context_system::instance());

        $limit = true;
        $markseenonmobile = false;
        foreach ($options as $option) {
            switch ($option['name']) {
                case 'markseenonmobile':
                    $markseenonmobile = $option['value'] == 'true' ? true : false;
                    break;
                case 'limit':
                    $limit = $option['value'] == 'true' ? true : 0;
                    break;
            }
        }

        $msgs = tm_messages_get('totara_alert', 'timecreated DESC ', $USER, $limit);
        if ($markseenonmobile) {
            foreach($msgs as $id => $msg) {
                $seen = array(
                    'messageid' => $id,
                    'userid' => $USER->id
                );
                if (!$DB->record_exists('message_seen_on_mobile', $seen)) {
                    $DB->insert_record('message_seen_on_mobile', (object) $seen);
                }
            }
        }
        return $msgs;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_alerts_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id'),
                    'subject' => new external_value(PARAM_TEXT, 'subject'),
                    'fullmessage' => new external_value(PARAM_RAW, 'full message')
                ), 'message'
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function dismiss_alerts_parameters() {
        return new external_function_parameters(
            array(
                'messageids'  => new external_multiple_structure(
                    new external_value(PARAM_INT, 'message id')
                ),
                'options'   => new external_multiple_structure(
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
     * Get bookings
     *
     * @param array $options It contains an array (list of ids)
     *
     * @return array The message ids deleted.
     */
    public static function dismiss_alerts($messageids, $options = array()) {
        global $DB, $USER;

        self::validate_context(\context_system::instance());

        $params = self::validate_parameters(self::dismiss_alerts_parameters(), array('messageids' => $messageids, 'options' => $options));
        $options = $params['options'];
        $messageids = $params['messageids'];

        list($insql, $inparams) = $DB->get_in_or_equal($messageids, SQL_PARAMS_NAMED);
        $inparams['userid'] = $USER->id;
        $DB->delete_records_select("message", "id $insql AND useridto = :userid", $inparams);

        return $messageids;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function dismiss_alerts_returns() {
        return new external_multiple_structure(
            new external_value(PARAM_INT, 'id')
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_tasks_parameters() {
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
     * Get tasks
     *
     * @param array $options It contains an array (list of ids)
     * @return array
     */
    public static function get_tasks($options=array()) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/totara/message/messagelib.php');

        $params = self::validate_parameters(self::get_tasks_parameters(), array('options' => $options));
        $options = $params['options'];

        self::validate_context(\context_system::instance());

        $markseenonmobile = false;
        $limit = true;
        foreach ($options as $option) {
            switch($option['name']) {
                case 'markseenonmobile':
                    $markseenonmobile = $option['value'] == 'true' ? true : false;
                    break;
                case 'limit':
                    $limit = $option['value'] == 'true' ? true : false;
                    break;
            }
        }

        $messages = tm_messages_get('totara_task', 'timecreated DESC ', $USER, $limit);
        foreach ($messages as $msg) {
            $msgmeta = $DB->get_record('message_metadata', array('messageid' => $msg->id));
            $msgacceptdata = totara_message_eventdata($msg->id, 'onaccept', $msgmeta);
            $msgrejectdata = totara_message_eventdata($msg->id, 'onreject', $msgmeta);
            $msginfodata = totara_message_eventdata($msg->id, 'oninfo', $msgmeta);

            // Accept.
            if (!empty($msgacceptdata) && count((array)$msgacceptdata)) {
                $msg->showacceptbtn = true;
                $msg->acceptbtntext = !empty($msgacceptdata->acceptbutton) ?
                    $msgacceptdata->acceptbutton : get_string('onaccept', 'block_totara_tasks');
            } else {
                $msg->showacceptbtn = false;
                $msg->acceptbtntext = null;
            }

            // Reject.
            if (!empty($msgrejectdata) && count((array)$msgrejectdata)) {
                $msg->showrejectbtn = true;
                $msg->rejectbtntext = !empty($msgrejectdata->rejectbutton) ?
                    $msgrejectdata->rejectbutton : get_string('onreject', 'block_totara_tasks');
            } else {
                $msg->showrejectbtn = false;
                $msg->rejectbtntext = null;
            }

            // Info.
            if (!empty($msginfodata) && count((array)$msginfodata)) {
                $msg->showinfobtn = true;
                $msg->infobtntext = !empty($msginfodata->infobutton) ?
                    $msginfodata->infobutton : get_string('oninfo', 'block_totara_tasks');
                $msg->infobtnurl = $msginfodata->data['redirect'];
            } else {
                $msg->showinfobtn = false;
                $msg->infobtntext = null;
                $msg->infobtnurl = null;
            }

            // Mark as seen on mobile
            if ($markseenonmobile) {
                $seen = array(
                    'messageid' => $msg->id,
                    'userid' => $USER->id
                );
                if (!$DB->record_exists('message_seen_on_mobile', $seen)) {
                    $DB->insert_record('message_seen_on_mobile', (object) $seen);
                }
            }
        }

        return $messages;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_tasks_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id'),
                    'subject' => new external_value(PARAM_TEXT, 'subject'),
                    'fullmessage' => new external_value(PARAM_RAW, 'full message'),
                    'showacceptbtn' => new external_value(PARAM_BOOL, 'show accept button'),
                    'acceptbtntext' => new external_value(PARAM_TEXT, 'accept button text'),
                    'showrejectbtn' => new external_value(PARAM_BOOL, 'show reject button'),
                    'rejectbtntext' => new external_value(PARAM_TEXT, 'reject button text'),
                    'showinfobtn' => new external_value(PARAM_BOOL, 'show info button'),
                    'infobtntext' => new external_value(PARAM_TEXT, 'info button text'),
                    'infobtnurl' => new external_value(PARAM_TEXT, 'info button url')
                ), 'message'
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function accept_task_parameters() {
        return new external_function_parameters(
            array(
                'messageid' => new external_value(PARAM_INT, 'message id'),
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
     * Accept task
     *
     * @param array $options It contains an array (list of ids)
     * @return array
     */
    public static function accept_task($messageid, $options=array()) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/totara/message/messagelib.php');

        $params = self::validate_parameters(self::accept_task_parameters(), array('messageid' => $messageid, 'options' => $options));
        $options = $params['options'];
        $messageid = $params['messageid'];

        self::validate_context(\context_system::instance());

        // Message MUST belong to current USER!
        $message = $DB->get_record('message', array('id' => $messageid, 'useridto' => $USER->id), '*', MUST_EXIST);

        tm_message_task_accept($message->id, null);
        return $messageid;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function accept_task_returns() {
        return new external_value(PARAM_INT, 'messageid');
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function reject_task_parameters() {
        return new external_function_parameters(
            array(
                'messageid' => new external_value(PARAM_INT, 'message id'),
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
     * Reject task
     *
     * @param array $options It contains an array (list of ids)
     * @return array
     */
    public static function reject_task($messageid, $options = array()) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/totara/message/messagelib.php');

        $params = self::validate_parameters(self::reject_task_parameters(), array('messageid' => $messageid, 'options' => $options));
        $options = $params['options'];
        $messageid = $params['messageid'];

        self::validate_context(\context_system::instance());

        // Message MUST belong to current USER!
        $message = $DB->get_record('message', array('id' => $messageid, 'useridto' => $USER->id), '*', MUST_EXIST);

        tm_message_task_reject($message->id, null);
        return $messageid;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function reject_task_returns() {
        return new external_value(PARAM_INT, 'messageid');
    }
}
