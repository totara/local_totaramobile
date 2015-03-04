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

class local_totaramobile_mod_label_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_label_details_parameters() {
        return new external_function_parameters(
            array('cmid' => new external_value(PARAM_INT, 'Course module id'))
        );
    }

    /**
     * Returns the label details
     * @param int $cmid the course module id
     * @return array the attempt status info
     */
    public static function get_label_details($cmid) {
        global $DB, $CFG;

        $params = self::validate_parameters(self::get_label_details_parameters(), array('cmid' => $cmid));

        $cm = get_coursemodule_from_id('label', $params['cmid']);
        $label = $DB->get_record('label', array('id' => $cm->instance));
        $context = \context_module::instance($cm->id);

        self::validate_context($context);

        // TODO: this is all wrong, it needs to use external_format_text()

        if (strpos($label->intro, '@@PLUGINFILE@@') !== false) {
            // Replace the pluginfile placeholder with the correct url
            $pattern = '/(@@PLUGINFILE@@)(.*?)"/';
            $url = $CFG->wwwroot.'/webservice/pluginfile.php/'.$context->id.'/mod_label/intro';
            $token = required_param('wstoken', PARAM_ALPHANUM);
            $replacement = $url."$2?token=".$token.'"';
            $label->intro = preg_replace($pattern, $replacement, $label->intro);

            // However, any values that already had arguments now have two ? in their url.
            // This matches the url up to the first ?
            // Then matches everything up to a second ?
            // And then everything after the second ?
            // All this must appear between double quote marks.
            $pattern = '/"(.*?)\?(.*?)\?(.*?)"/';
            $replacement = "\"$1?$2&$3\"";
            $label->intro = preg_replace($pattern, $replacement, $label->intro);
        }
        return array(
            'id'    => $label->id,
            'cmid'  => $cm->id,
            'name'  => $label->name,
            'intro' => $label->intro
        );
    }

    /**
     * Returns description of method return value
     * @return external_single_structure
     */
    public static function get_label_details_returns() {
        return new external_single_structure(
            array(
                'id'    => new external_value(PARAM_INT,  'label ID'),
                'cmid'  => new external_value(PARAM_INT,  'Course module ID'),
                'name'  => new external_value(PARAM_TEXT, 'label name'),
                'intro' => new external_value(PARAM_RAW,  'label intro')
            )
        );
    }
}
