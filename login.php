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

/*
 * This is a very nasty script, it hijacks current browser session for mobile.
 */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../config.php');

$PAGE->set_url('/local/totaramobile/login.php');
$PAGE->set_context(context_system::instance());

echo $OUTPUT->header();

if (!get_config('local_totaramobile', 'enablemobile')) {
    // Not enabled.
    die;
}

$checkloggedin = optional_param('checkloggedin', '', PARAM_RAW);
$username = optional_param('username', '', PARAM_RAW);
$password = optional_param('password', '', PARAM_RAW);

if ($checkloggedin) {
    $loggeid = false;

    if ($user = $DB->get_record('user', array('username' => $checkloggedin, 'mnethostid' => $CFG->mnet_localhost_id))) {
        $loggeid = ($user->id == $USER->id);
    }
    $result = array(
        'success' => true,
        'data' => array(
            'logged_in' => $loggeid,
        ));
    echo json_encode($result);
    die;
}

$user = authenticate_user_login($username, $password, false, $reason);

if (!$user) {
    echo json_encode(array('success' =>false, 'error' => get_string("invalidlogin")));
    die;
} else {
    if (isloggedin()) {
        require_logout();
    }
    complete_user_login($user);
    echo json_encode(array('success' => true));
    die;
}
