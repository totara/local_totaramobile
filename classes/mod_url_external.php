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

class local_totaramobile_mod_url_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_url_details_parameters() {
        return new external_function_parameters(
            array('cmid' => new external_value(PARAM_INT, 'Course module id'))
        );
    }

    /**
     * Returns the url details
     * @param int $cmid the course module id
     * @return array the attempt status info
     */
    public static function get_url_details($cmid) {
        global $DB;

        $params = self::validate_parameters(self::get_url_details_parameters(), array('cmid' => $cmid));

        $cm = get_coursemodule_from_id('url', $params['cmid']);
        $url = $DB->get_record('url', array('id' => $cm->instance));
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('mod/url:view', $context);

        return array(
            'id'           => $url->id,
            'cmid'         => $cm->id,
            'name'         => $url->name,
            'intro'        => $url->intro,
            'external_url' => $url->externalurl
        );
    }

    /**
     * Returns description of method return value
     * @return external_single_structure
     */
    public static function get_url_details_returns() {
        return new external_single_structure(
            array(
                'id'           => new external_value(PARAM_INT,  'URL ID'),
                'cmid'         => new external_value(PARAM_INT,  'Course module ID'),
                'name'         => new external_value(PARAM_TEXT, 'URL name'),
                'intro'        => new external_value(PARAM_RAW,  'URL intro'),
                'external_url' => new external_value(PARAM_TEXT, 'External URL')
            )
        );
    }
}
