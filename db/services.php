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

$functions = array(
    'tm_core_course_get_contents' => array(
        'classname'   => 'local_totaramobile_course_external',
        'methodname'  => 'get_course_contents',
        'description' => 'Get course contents',
        'type'        => 'read',
        'capabilities'=> 'moodle/course:update,moodle/course:viewhiddencourses',
    ),

    'tm_core_list_courses' => array(
        'classname'    => 'local_totaramobile_course_external',
        'methodname'   => 'list_courses',
        'description'  => 'Get a list of courses',
        'type'         => 'read',
        'capabilities' => ''
    ),

    'tm_core_course_set_activity_completion' => array(
        'classname'   => 'local_totaramobile_course_external',
        'methodname'  => 'set_activity_completion',
        'description' => 'Set activity completion',
        'type'        => 'write'
    ),

    'tm_core_enrol_get_users_course_completions' => array(
        'classname'   => 'local_totaramobile_enrol_external',
        'methodname'  => 'get_users_course_completions',
        'description' => 'Get the list of courses where a user is enrolled in and the completion status',
        'type'        => 'read',
        'capabilities'=> '',
    ),

    'tm_enrol_self_enrol' => array(
        'classname'   => 'local_totaramobile_enrol_self_external',
        'methodname'  => 'enrol',
        'description' => 'Self enrol',
        'capabilities'=> 'enrol/self:enrol',
        'type'        => 'write',
    ),

    'tm_mod_feedback_get_questions' => array(
        'classname' => 'local_totaramobile_mod_feedback_external',
        'methodname' => 'get_questions',
        'description' => 'Gets all the questions for a feedback',
        'type' => 'read'
    ),

    'tm_mod_feedback_send_answers' => array(
        'classname' => 'local_totaramobile_mod_feedback_external',
        'methodname' => 'send_answers',
        'description' => 'Sends back all the answers for a specific feedback',
        'type' => 'write'
    ),

    'tm_mod_label_get_label_details' => array(
        'classname'     => 'local_totaramobile_mod_label_external',
        'methodname'    => 'get_label_details',
        'description'   => 'Returns the label details',
        'type'          => 'read'
    ),

    'tm_mod_page_get_page_details' => array(
        'classname'     => 'local_totaramobile_mod_page_external',
        'methodname'    => 'get_page_details',
        'description'   => 'Returns the page details',
        'type'          => 'read'
    ),

    'tm_mod_scorm_get_attempt_status' => array(
        'classname'     => 'local_totaramobile_mod_scorm_external',
        'methodname'    => 'get_attempt_status',
        'description'   => 'Returns the attempt status info for the current user',
        'type'          => 'read'
    ),

    'tm_mod_url_get_url_details' => array(
        'classname'     => 'local_totaramobile_mod_url_external',
        'methodname'    => 'get_url_details',
        'description'   => 'Returns the url details',
        'type'          => 'read'
    ),

    'tm_mod_facetoface_get_my_bookings' => array(
        'classname'   => 'local_totaramobile_mod_facetoface_external',
        'methodname'  => 'get_my_bookings',
        'description' => 'Return user bookings',
        'type'        => 'read'
    ),

    'tm_totara_message_get_alerts' => array(
        'classname'   => 'local_totaramobile_totara_message_external',
        'methodname'  => 'get_alerts',
        'description' => 'Return user alerts',
        'type'        => 'read'
    ),

    'tm_totara_message_dismiss_alerts' => array(
        'classname'   => 'local_totaramobile_totara_message_external',
        'methodname'  => 'dismiss_alerts',
        'description' => 'Dismiss user alerts',
        'type'        => 'write'
    ),

    'tm_totara_message_get_tasks' => array(
        'classname'   => 'local_totaramobile_totara_message_external',
        'methodname'  => 'get_tasks',
        'description' => 'Return user tasks',
        'type'        => 'read'
    ),

    'tm_totara_message_accept_task' => array(
        'classname'   => 'local_totaramobile_totara_message_external',
        'methodname'  => 'accept_task',
        'description' => 'Accept task',
        'type'        => 'write'
    ),

    'tm_totara_message_reject_task' => array(
        'classname'   => 'local_totaramobile_totara_message_external',
        'methodname'  => 'reject_task',
        'description' => 'Reject task',
        'type'        => 'write'
    ),

    'tm_totara_program_get_programs' => array(
        'classname'   => 'local_totaramobile_totara_program_external',
        'methodname'  => 'get_programs',
        'description' => 'Return program hierarchy',
        'type'        => 'read'
    ),

    'tm_totara_program_get_users_programs' => array(
        'classname'   => 'local_totaramobile_totara_program_external',
        'methodname'  => 'get_users_programs',
        'description' => 'Return program hierarchy',
        'type'        => 'read'
    ),

    'tm_totara_program_get_users_courses' => array(
        'classname'   => 'local_totaramobile_totara_program_external',
        'methodname'  => 'get_users_courses',
        'description' => 'Return users courses including those from learning plans',
        'type'        => 'read'
    ),

    'tm_totara_program_get_users_required_programs' => array(
        'classname'   => 'local_totaramobile_totara_program_external',
        'methodname'  => 'get_users_required_programs',
        'description' => 'Return program hierarchy',
        'type'        => 'read'
    ),

    'tm_totara_program_get_program' => array(
        'classname'   => 'local_totaramobile_totara_program_external',
        'methodname'  => 'get_program',
        'description' => 'Return program details',
        'type'        => 'read'
    ),


    'tm_webservice_get_mobile_homepage' => array(
        'classname'   => 'local_totaramobile_webservice_external',
        'methodname'  => 'get_mobile_homepage',
        'description' => 'Return the mobile homepage',
        'type'        => 'read',
    ),

    'tm_webservice_get_item_counts' => array(
        'classname'   => 'local_totaramobile_webservice_external',
        'methodname'  => 'get_item_counts',
        'description' => 'Return item counts',
        'type'        => 'read',
    ),
);

$services = array(
    'Totara mobile web service'  => array(
        'functions' => array (

            // Standard mobile.

            //'moodle_enrol_get_users_courses',
            'moodle_enrol_get_enrolled_users',
            'moodle_user_get_users_by_id',
            'moodle_webservice_get_siteinfo',
            'moodle_notes_create_notes',
            'moodle_user_get_course_participants_by_id',
            'moodle_user_get_users_by_courseid',
            'moodle_message_send_instantmessages',
            //'core_course_get_contents',
            'core_get_component_strings',
            'core_user_add_user_device',
            'core_calendar_get_calendar_events',
            'core_enrol_get_users_courses',
            'core_enrol_get_enrolled_users',
            'core_user_get_users_by_id',
            'core_webservice_get_site_info',
            'core_notes_create_notes',
            'core_user_get_course_user_profiles',
            'core_enrol_get_enrolled_users',
            'core_message_send_instant_messages',
            'mod_assign_get_grades',
            'mod_assign_get_assignments',
            'mod_assign_get_submissions',
            'mod_assign_get_user_flags',
            'mod_assign_set_user_flags',
            'mod_assign_get_user_mappings',
            'mod_assign_revert_submissions_to_draft',
            'mod_assign_lock_submissions',
            'mod_assign_unlock_submissions',
            'mod_assign_save_submission',
            'mod_assign_submit_for_grading',
            'mod_assign_save_grade',
            'mod_assign_save_user_extensions',
            'mod_assign_reveal_identities',
            'message_airnotifier_is_system_configured',
            'message_airnotifier_are_notification_preferences_configured',
            'core_grades_update_grades',
            'mod_forum_get_forums_by_courses',
            'mod_forum_get_forum_discussions',
            'mod_forum_get_forum_discussion_posts',

            // Some extras.
            'core_course_get_categories',
            'core_course_get_courses',
            'user_course_get_courses',

            // Totara extras - the "tm_" prefix prevents collisions with future Totara/Moodle changes.
            'tm_core_course_get_contents',
            'tm_core_list_courses',
            'tm_core_course_set_activity_completion',
            'tm_core_enrol_get_users_course_completions',
            'tm_enrol_self_enrol',
            'tm_mod_feedback_get_questions',
            'tm_mod_feedback_send_answers',
            'tm_mod_label_get_label_details',
            'tm_mod_page_get_page_details',
            'tm_mod_scorm_get_attempt_status',
            'tm_mod_url_get_url_details',
            'tm_mod_facetoface_get_my_bookings',
            'tm_totara_message_get_alerts',
            'tm_totara_message_dismiss_alerts',
            'tm_totara_message_get_tasks',
            'tm_totara_message_accept_task',
            'tm_totara_message_reject_task',
            'tm_totara_program_get_programs',
            'tm_totara_program_get_users_programs',
            'tm_totara_program_get_users_courses',
            'tm_totara_program_get_users_required_programs',
            'tm_totara_program_get_program',
            'tm_webservice_get_mobile_homepage',
            'tm_webservice_get_item_counts',
        ),

        'enabled' => 0,
        'restrictedusers' => 0,
        'shortname' => 'totara_mobile_app',
        'downloadfiles' => 1,
        'uploadfiles' => 1
    ),
);
