<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details
 *
 * @package    local_external_users
 * @author     Stephan Lorbek
 * @copyright  2022 Stephan Lorbek
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('modsettings', new admin_externalpage('external_users',
    get_string('pluginname', 'local_external_users') . " Dashboard",
    new moodle_url('/local/external_users/views/manage.php'),
    'local/external_users:manage'));

if ($hassiteconfig) {
    $settings = new admin_settingpage('External Users', '');
    $ADMIN->add('localplugins', new admin_category('local_external_users',
        get_string('pluginname', 'local_external_users')));
    $ADMIN->add('local_external_users', $settings);

    if ($ADMIN->fulltree) {
        $settings->add(
            new admin_setting_confightmleditor("local_external_users/onboardingdescription",
                "Onboarding Description", "", ""));
        $settings->add(
            new admin_setting_configtext("local_external_users/signupmailsubject",
                "Signup Mail Subject", "", ""));
        $settings->add(
            new admin_setting_confightmleditor("local_external_users/signupmailmessage",
                "Signup Mail Body", "", ""));
        $settings->add(
            new admin_setting_configtext("local_external_users/mailrejectionsubject",
                "Mail Rejection Subject", "", ""));
        $settings->add(
            new admin_setting_confightmleditor("local_external_users/mailrejectionmessage",
                "Mail Rejection Body", "", ""));
        $settings->add(
            new admin_setting_configtext("local_external_users/discounturl",
                "Discount Info URL", "", ""));
    }
}
