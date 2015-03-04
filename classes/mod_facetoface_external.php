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

class local_totaramobile_mod_facetoface_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_my_bookings_parameters() {
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
     * Get bookings
     *
     * @param array $options It contains an array (list of ids)
     * @return array
     */
    public static function get_my_bookings($options = array()) {
        global $DB, $USER;

        $params = self::validate_parameters(self::get_my_bookings_parameters(), array('options' => $options));
        $options = $params['options'];

        self::validate_context(\context_system::instance());

        $markseenonmobile = false;
        foreach ($options as $option) {
            switch($option['name']) {
                case 'markseenonmobile':
                    $markseenonmobile = $option['value'] == 'true' ? true : false;
                    break;
            }
        }

        // Only show requested(40), approved(50), waitlisted(60) or booked(70) sessions
        $sql = "
            SELECT
                d.id,
                c.id AS courseid,
                c.fullname AS coursename,
                f.name AS facetofacename,
                d.timestart,
                d.timefinish
            FROM {facetoface} f
            JOIN {course} c ON c.id = f.course
            JOIN {facetoface_sessions} s ON s.facetoface = f.id
            JOIN {facetoface_sessions_dates} d ON d.sessionid = s.id
            JOIN {facetoface_signups} su ON su.sessionid = s.id
            JOIN {facetoface_signups_status} sus ON sus.signupid = su.id
            JOIN {user} u ON u.id = su.userid
            WHERE u.id = :userid
            AND s.datetimeknown > 0
            AND sus.superceded = 0
            AND sus.statuscode IN (40, 50, 60, 70)
            AND d.timestart > :now
        ";

        $params = array(
            'userid' => $USER->id,
            'now' => round(time(), -2)
        );

        $bookings = $DB->get_records_sql($sql, $params);

        if ($markseenonmobile) {
            foreach($bookings as $booking) {
                $seen = array(
                    'bookingid' => $booking->id,
                    'userid' => $USER->id
                );
                if (!$DB->record_exists('facetoface_booking_seen_mob', $seen)) {
                    $DB->insert_record('facetoface_booking_seen_mob', (object) $seen);
                }
            }
        }

        return $bookings;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_my_bookings_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'courseid' => new external_value(PARAM_INT, 'course id'),
                    'coursename' => new external_value(PARAM_TEXT, 'course name'),
                    'facetofacename' => new external_value(PARAM_TEXT, 'face to face name'),
                    'timestart' => new external_value(PARAM_TEXT, 'start date'),
                    'timefinish' => new external_value(PARAM_INT, 'start date'),
                ), 'booking'
            )
        );
    }
}
