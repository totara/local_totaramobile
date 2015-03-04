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

class local_totaramobile_mod_page_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_page_details_parameters() {
        return new external_function_parameters(
            array('cmid' => new external_value(PARAM_INT, 'Course module id'))
        );
    }

    /**
     * Returns the page details
     * @param int $cmid the course module id
     * @return array the attempt status info
     */
    public static function get_page_details($cmid) {
        global $DB;

        $params = self::validate_parameters(self::get_page_details_parameters(), array('cmid' => $cmid));

        $cm = get_coursemodule_from_id('page', $params['cmid']);
        $page = $DB->get_record('page', array('id' => $cm->instance));
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('mod/page:view', $context);

        // TODO: where is text formatting and image urls?

        $display_options = unserialize($page->displayoptions);
        $print_intro = false;
        if (array_key_exists('printintro', $display_options)) {
            $print_intro = $display_options['printintro'];
        }
        return array(
            'id'               => $page->id,
            'cmid'             => $cm->id,
            'name'             => $page->name,
            'intro'            => $page->intro,
            'content'          => $page->content,
            'print_intro'      => $print_intro,
            'complete_on_view' => $cm->completionview
        );
    }

    /**
     * Returns description of method return value
     * @return external_single_structure
     */
    public static function get_page_details_returns() {
        return new external_single_structure(
            array(
                'id'               => new external_value(PARAM_INT,  'Page ID'),
                'cmid'             => new external_value(PARAM_INT,  'Course module ID'),
                'name'             => new external_value(PARAM_TEXT, 'Page name'),
                'intro'            => new external_value(PARAM_RAW,  'Page intro'),
                'content'          => new external_value(PARAM_RAW,  'Page content'),
                'print_intro'      => new external_value(PARAM_BOOL, 'Print intro'),
                'complete_on_view' => new external_value(PARAM_BOOL, 'Complete on view')
            )
        );
    }
}
