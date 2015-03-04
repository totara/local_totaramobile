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

namespace local_totaramobile\task;

class cron_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('crontask', 'local_totaramobile');
    }

    public function execute() {
        global $DB;

        // Add tokens if necessary.
        \local_totaramobile\local\util::create_all_tokens();

        // Delete old 'seen on mobile' records
        $DB->execute("
        DELETE FROM {message_seen_on_mobile}
        WHERE id IN (
            SELECT msom.id
            FROM {message_seen_on_mobile} msom
            LEFT JOIN {message} m ON (m.id = msom.messageid)
            WHERE m.id IS NULL)");

        // Delete old 'seen on mobile' records
        $DB->execute("
        DELETE FROM {facetoface_booking_seen_mob}
        WHERE id IN (
            SELECT bsom.id
            FROM {facetoface_booking_seen_mob} bsom
            LEFT JOIN {facetoface_sessions_dates} d ON (d.id = bsom.bookingid)
            WHERE d.id IS NULL)");
    }
}
