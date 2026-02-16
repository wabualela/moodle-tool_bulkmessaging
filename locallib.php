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
 * Local library functions for tool_bulkmessaging.
 *
 * @package    tool_bulkmessaging
 * @copyright  2026 Moddaker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Render the tab navigation bar for bulk messaging pages.
 *
 * @param string $selected The selected tab ID ('compose' or 'history').
 * @return string HTML output.
 */
function tool_bulkmessaging_render_tabs(string $selected): string {
    global $CFG, $OUTPUT;

    $tabs = [
        new tabobject(
            'compose',
            new moodle_url("/$CFG->admin/tool/bulkmessaging/index.php"),
            $OUTPUT->pix_icon('t/email', '', 'moodle', ['class' => 'iconsmall']) . ' ' .
                get_string('sendmessage', 'tool_bulkmessaging')
        ),
        new tabobject(
            'csvupload',
            new moodle_url("/$CFG->admin/tool/bulkmessaging/csvupload.php"),
            $OUTPUT->pix_icon('i/import', '', 'moodle', ['class' => 'iconsmall']) . ' ' .
                get_string('csvupload', 'tool_bulkmessaging')
        ),
        new tabobject(
            'history',
            new moodle_url("/$CFG->admin/tool/bulkmessaging/history.php"),
            $OUTPUT->pix_icon('t/viewdetails', '', 'moodle', ['class' => 'iconsmall']) . ' ' .
                get_string('messagehistory', 'tool_bulkmessaging')
        ),
    ];

    return $OUTPUT->tabtree($tabs, $selected);
}

/**
 * Render an action icon link for the history table.
 *
 * @param moodle_url $url The action URL.
 * @param string $icon The pix icon identifier (e.g. 't/delete').
 * @param string $alt Alt/title text.
 * @param array $attributes Extra HTML attributes.
 * @return string HTML output.
 */
function tool_bulkmessaging_action_icon(moodle_url $url, string $icon, string $alt, array $attributes = []): string {
    global $OUTPUT;

    return $OUTPUT->action_icon(
        $url,
        new pix_icon($icon, $alt, 'moodle', ['title' => $alt]),
        null,
        $attributes
    );
}
