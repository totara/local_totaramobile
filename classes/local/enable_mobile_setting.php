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

namespace local_totaramobile\local;

class enable_mobile_setting extends \admin_setting_configcheckbox {
    public function __construct() {
        parent::__construct('local_totaramobile/enablemobile',
            new \lang_string('enablemobile', 'local_totaramobile'),
            new \lang_string('enablemobile_desc', 'local_totaramobile'),
            0);
    }

    private function is_protocol_cap_allowed() {
        global $DB, $CFG;

        if (empty($CFG->defaultuserroleid)) {
            return false;
        }

        $params = array();
        $params['permission'] = CAP_ALLOW;
        $params['roleid'] = $CFG->defaultuserroleid;
        $params['capability'] = 'webservice/rest:use';

        return $DB->record_exists('role_capabilities', $params);
    }

    private function enable_mobile() {
        global $CFG;
        require_once($CFG->dirroot . '/webservice/lib.php');

        if (empty($CFG->defaultuserroleid)) {
            return get_string('errornodefaultuserroleid', 'local_totaramobile');
        }

        set_config('enablewebservices', 1);
        $systemcontext = \context_system::instance();
        assign_capability('webservice/rest:use', CAP_ALLOW, $CFG->defaultuserroleid, $systemcontext->id, true);

        $protocols = empty($CFG->webserviceprotocols) ? array() : explode(',', $CFG->webserviceprotocols);
        $protocols[] = 'rest';
        $protocols = array_unique($protocols);
        set_config('webserviceprotocols', implode(',', $protocols));

        $webservicemanager = new \webservice();
        $mobileservice = $webservicemanager->get_external_service_by_shortname('totara_mobile_app');
        $mobileservice->enabled = 1;
        $webservicemanager->update_external_service($mobileservice);

        util::create_all_tokens();

        set_config('enablemobile', 1, 'local_totaramobile');

        return '';
    }

    private function disable_mobile() {
        global $CFG;
        require_once($CFG->dirroot . '/webservice/lib.php');

        set_config('enablemobile', 0, 'local_totaramobile');

        $webservicemanager = new \webservice();
        $mobileservice = $webservicemanager->get_external_service_by_shortname('totara_mobile_app');
        $mobileservice->enabled = 0;
        $webservicemanager->update_external_service($mobileservice);

        return '';
    }

    public function get_setting() {
        global $CFG;

        if (empty($CFG->enablewebservices)) {
            return '0';
        }

        require_once($CFG->dirroot . '/webservice/lib.php');
        $webservicemanager = new \webservice();
        $mobileservice = $webservicemanager->get_external_service_by_shortname('totara_mobile_app');
        if (!$mobileservice->enabled or !$this->is_protocol_cap_allowed()) {
            return '0';
        }

        if (get_config('local_totaramobile', 'enablemobile')) {
            return '1';
        } else {
            return '0';
        }
    }

    public function write_setting($data) {
        if ((string)$data === $this->yes) { // Convert to strings before comparison.
            $result = $this->enable_mobile();
        } else {
            $result = $this->disable_mobile();
        }

        return $result;
    }
}
