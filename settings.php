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

if ($hassiteconfig) {
    $temp = new admin_settingpage('totaramobile', new lang_string('pluginname', 'local_totaramobile'));

    $temp->add(new \local_totaramobile\local\enable_mobile_setting());
    $temp->add(
        new admin_setting_confightmleditor(
            'local_totaramobile/mobilehomepage', new lang_string('mobilehomepage', 'local_totaramobile'),
            new lang_string('mobilehomepage_desc', 'local_totaramobile'), '', PARAM_RAW
        )
    );

    $ADMIN->add('webservicesettings', $temp);
}
