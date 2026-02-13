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
 * Admin settings for tool_bulkmessaging.
 *
 * @package    tool_bulkmessaging
 * @copyright  2026 Moddaker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Add main page under Users > Accounts.
$ADMIN->add('accounts', new admin_externalpage(
    'toolbulkmessaging',
    get_string('bulkmessaging', 'tool_bulkmessaging'),
    "$CFG->wwwroot/$CFG->admin/tool/bulkmessaging/index.php",
    'tool/bulkmessaging:sendmessage'
));

// Add history page under Users > Accounts.
$ADMIN->add('accounts', new admin_externalpage(
    'toolbulkmessaginghistory',
    get_string('messagehistory', 'tool_bulkmessaging'),
    "$CFG->wwwroot/$CFG->admin/tool/bulkmessaging/history.php",
    'tool/bulkmessaging:sendmessage'
));

// Plugin settings page.
if ($hassiteconfig) {
    $settings = new admin_settingpage('tool_bulkmessaging_settings', get_string('settings', 'tool_bulkmessaging'));

    $settings->add(new admin_setting_configtext(
        'tool_bulkmessaging/batchsize',
        get_string('batchsize', 'tool_bulkmessaging'),
        get_string('batchsize_desc', 'tool_bulkmessaging'),
        50,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'tool_bulkmessaging/maxrecipients',
        get_string('maxrecipients', 'tool_bulkmessaging'),
        get_string('maxrecipients_desc', 'tool_bulkmessaging'),
        0,
        PARAM_INT
    ));

    $ADMIN->add('tools', $settings);
}
