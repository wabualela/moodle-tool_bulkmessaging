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
 * Event for bulk message sent.
 *
 * @package    tool_bulkmessaging
 * @copyright  2026 Moddaker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_bulkmessaging\event;

/**
 * Event triggered when a bulk message is queued for sending.
 */
class bulk_message_sent extends \core\event\base {

    /**
     * Initialise the event.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'tool_bulkmessaging_log';
    }

    /**
     * Get event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventbulkmessagesent', 'tool_bulkmessaging');
    }

    /**
     * Get event description.
     *
     * @return string
     */
    public function get_description() {
        $recipientcount = $this->other['recipientcount'] ?? 0;
        $subject = $this->other['subject'] ?? '';
        return "The user with id '{$this->userid}' queued a bulk message '{$subject}' " .
               "to {$recipientcount} recipient(s).";
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/tool/bulkmessaging/history.php', [
            'view' => $this->other['logid'] ?? 0,
        ]);
    }
}
