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
 * Adhoc task to send a batch of bulk messages.
 *
 * @package    tool_bulkmessaging
 * @copyright  2026 Moddaker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_bulkmessaging\task;

use core\task\adhoc_task;

/**
 * Sends a batch of notifications for a bulk message.
 */
class send_bulk_message extends adhoc_task {

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('tasksendbulkmessage', 'tool_bulkmessaging');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        $logid = $data->logid;
        $userids = $data->userids;
        $subject = $data->subject;
        $messagebody = $data->messagebody;
        $messageformat = $data->messageformat;

        // Check that the log record still exists and isn't cancelled/stopped (status 4 or 5).
        $log = $DB->get_record('tool_bulkmessaging_log', ['id' => $logid]);
        if (!$log || in_array((int)$log->status, [4, 5])) {
            mtrace("Bulk message log {$logid} has been cancelled/stopped or deleted, skipping batch.");
            return;
        }

        // Mark as processing.
        $DB->set_field('tool_bulkmessaging_log', 'status', 1, ['id' => $logid]);

        $userfrom = \core_user::get_noreply_user();
        $sentcount = 0;
        $failedcount = 0;

        foreach ($userids as $userid) {
            try {
                $userto = $DB->get_record('user', ['id' => $userid, 'deleted' => 0, 'suspended' => 0]);
                if (!$userto) {
                    $failedcount++;
                    continue;
                }

                $message = new \core\message\message();
                $message->component = 'tool_bulkmessaging';
                $message->name = 'bulknotification';
                $message->userfrom = $userfrom;
                $message->userto = $userto;
                $message->subject = $subject;
                $message->fullmessage = html_to_text($messagebody);
                $message->fullmessageformat = FORMAT_HTML;
                $message->fullmessagehtml = $messagebody;
                $message->smallmessage = shorten_text(html_to_text($messagebody), 100);
                $message->notification = 1;

                message_send($message);
                $sentcount++;
            } catch (\Exception $e) {
                $failedcount++;
                mtrace("Failed to send message to user {$userid}: " . $e->getMessage());
            }
        }

        // Update counts and check completion in a transaction.
        $transaction = $DB->start_delegated_transaction();

        $sql = "UPDATE {tool_bulkmessaging_log}
                   SET sentcount = sentcount + :sent,
                       failedcount = failedcount + :failed
                 WHERE id = :logid";
        $DB->execute($sql, ['sent' => $sentcount, 'failed' => $failedcount, 'logid' => $logid]);

        // Check if any other tasks remain for this log entry.
        $likesql = $DB->sql_like('customdata', ':pattern');
        $remaining = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {task_adhoc}
              WHERE classname = :classname AND id != :taskid AND $likesql",
            [
                'classname' => '\\' . self::class,
                'taskid' => $this->get_id(),
                'pattern' => '%"logid":' . (int)$logid . '%',
            ]
        );

        if ($remaining == 0) {
            // This is the last batch — mark completed.
            $DB->set_field('tool_bulkmessaging_log', 'status', 2, ['id' => $logid]);
            $DB->set_field('tool_bulkmessaging_log', 'timecompleted', time(), ['id' => $logid]);
        }

        $transaction->allow_commit();
    }
}
