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
 * External function to get bulk message progress.
 *
 * @package    tool_bulkmessaging
 * @copyright  2026 Moddaker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_bulkmessaging\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function for getting progress of a bulk message.
 */
class get_message_progress extends external_api {

    /**
     * Define parameters for external function.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'logid' => new external_value(PARAM_INT, 'The bulk messaging log ID'),
        ]);
    }

    /**
     * Get the progress of a bulk message.
     *
     * @param int $logid The log record ID.
     * @return array
     */
    public static function execute(int $logid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['logid' => $logid]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('tool/bulkmessaging:sendmessage', $context);

        $log = $DB->get_record('tool_bulkmessaging_log', ['id' => $params['logid']], '*', MUST_EXIST);

        $statuslabels = [
            0 => get_string('statusqueued', 'tool_bulkmessaging'),
            1 => get_string('statusprocessing', 'tool_bulkmessaging'),
            2 => get_string('statuscompleted', 'tool_bulkmessaging'),
            3 => get_string('statusfailed', 'tool_bulkmessaging'),
            4 => get_string('statuscancelled', 'tool_bulkmessaging'),
            5 => get_string('statusstopped', 'tool_bulkmessaging'),
        ];

        $statusclasses = [
            0 => 'badge bg-secondary text-dark',
            1 => 'badge bg-info text-dark',
            2 => 'badge bg-success text-dark',
            3 => 'badge bg-danger text-dark',
            4 => 'badge bg-dark',
            5 => 'badge bg-warning text-dark',
        ];

        $processed = $log->sentcount + $log->failedcount;
        $percentage = ($log->recipientcount > 0) ? round(($processed / $log->recipientcount) * 100) : 0;

        return [
            'status' => (int) $log->status,
            'statuslabel' => $statuslabels[(int) $log->status] ?? (string) $log->status,
            'statusclass' => $statusclasses[(int) $log->status] ?? 'badge bg-secondary',
            'sentcount' => (int) $log->sentcount,
            'failedcount' => (int) $log->failedcount,
            'recipientcount' => (int) $log->recipientcount,
            'percentage' => $percentage,
        ];
    }

    /**
     * Define return values.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_INT, 'Status code'),
            'statuslabel' => new external_value(PARAM_TEXT, 'Status label text'),
            'statusclass' => new external_value(PARAM_TEXT, 'CSS class for status badge'),
            'sentcount' => new external_value(PARAM_INT, 'Number of messages sent'),
            'failedcount' => new external_value(PARAM_INT, 'Number of messages failed'),
            'recipientcount' => new external_value(PARAM_INT, 'Total recipient count'),
            'percentage' => new external_value(PARAM_INT, 'Completion percentage'),
        ]);
    }
}
