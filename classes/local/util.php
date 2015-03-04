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

class util {
    public static function create_all_tokens() {
        global $CFG, $DB;
        require_once("$CFG->dirroot/lib/externallib.php");

        $service = $DB->get_record('external_services', array('shortname' => 'totara_mobile_app', 'enabled' => 1));
        if (empty($service)) {
            return;
        }
        $syscontext = \context_system::instance();

        $sql = "SELECT u.id
                  FROM {user} u
             LEFT JOIN {external_tokens} t ON (t.userid = u.id AND t.externalserviceid = :serviceid AND t.tokentype = :permanent)
                 WHERE t.id IS NULL AND u.deleted = 0 AND u.mnethostid = :localmnet AND u.id <> :guestid";
        $params = array('permanent' => EXTERNAL_TOKEN_PERMANENT, 'serviceid' => $service->id, 'localmnet' => $CFG->mnet_localhost_id, 'guestid' => guest_user()->id);

        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $user) {
            $newtoken = external_generate_token(EXTERNAL_TOKEN_PERMANENT, $service, $user->id, $syscontext);
            // We need to fake creator, sorry, the whole token user interface and api is idiotic!
            $DB->set_field('external_tokens', 'creatorid', $user->id, array('userid' => $user->id, 'token' => $newtoken));
        }
        $rs->close();
    }
}