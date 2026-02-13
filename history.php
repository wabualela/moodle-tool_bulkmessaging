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
 * Message history page for tool_bulkmessaging.
 *
 * @package    tool_bulkmessaging
 * @copyright  2026 Moddaker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/locallib.php');

admin_externalpage_setup('toolbulkmessaginghistory');

$context = context_system::instance();
require_capability('tool/bulkmessaging:sendmessage', $context);

$baseurl = new moodle_url("/$CFG->admin/tool/bulkmessaging/history.php");
$composeurl = new moodle_url("/$CFG->admin/tool/bulkmessaging/index.php");

// Helper to delete adhoc tasks for a given log ID.
$deletetasks = function(int $logid) use ($DB) {
    $likesql = $DB->sql_like('customdata', ':pattern');
    $DB->delete_records_select('task_adhoc',
        "classname = :classname AND $likesql",
        [
            'classname' => '\\tool_bulkmessaging\\task\\send_bulk_message',
            'pattern' => '%"logid":' . $logid . '%',
        ]
    );
};

// Handle cancel action (queued messages — status 0).
$cancelid = optional_param('cancel', 0, PARAM_INT);
if ($cancelid && confirm_sesskey()) {
    $log = $DB->get_record('tool_bulkmessaging_log', ['id' => $cancelid]);
    if ($log && $log->status == 0) {
        $DB->set_field('tool_bulkmessaging_log', 'status', 4, ['id' => $cancelid]);
        $DB->set_field('tool_bulkmessaging_log', 'timecompleted', time(), ['id' => $cancelid]);
        $deletetasks($cancelid);
        redirect($baseurl, get_string('messagecancelled', 'tool_bulkmessaging'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
    redirect($baseurl);
}

// Handle stop action (processing messages — status 1).
$stopid = optional_param('stop', 0, PARAM_INT);
if ($stopid && confirm_sesskey()) {
    $log = $DB->get_record('tool_bulkmessaging_log', ['id' => $stopid]);
    if ($log && $log->status == 1) {
        $DB->set_field('tool_bulkmessaging_log', 'status', 5, ['id' => $stopid]);
        $DB->set_field('tool_bulkmessaging_log', 'timecompleted', time(), ['id' => $stopid]);
        $deletetasks($stopid);
        redirect($baseurl, get_string('messagestopped', 'tool_bulkmessaging'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
    redirect($baseurl);
}

// Handle start action (re-queue a failed or stopped message — status 3 or 5).
$startid = optional_param('start', 0, PARAM_INT);
if ($startid && confirm_sesskey()) {
    $log = $DB->get_record('tool_bulkmessaging_log', ['id' => $startid]);
    if ($log && in_array((int)$log->status, [3, 5])) {
        // Re-queue: find all active users that were originally targeted.
        // We use the original recipientcount for reference, but re-query all users
        // since we can't recover exact original IDs. Reset counts and re-queue.
        $DB->set_field('tool_bulkmessaging_log', 'status', 0, ['id' => $startid]);
        $DB->set_field('tool_bulkmessaging_log', 'sentcount', 0, ['id' => $startid]);
        $DB->set_field('tool_bulkmessaging_log', 'failedcount', 0, ['id' => $startid]);
        $DB->set_field('tool_bulkmessaging_log', 'timecompleted', null, ['id' => $startid]);

        // Build user query from filter data.
        $usersql = "SELECT u.id FROM {user} u WHERE u.deleted = 0 AND u.suspended = 0 AND u.id > 1";
        $userparams = [];

        // Use all users (we can't reliably reconstruct original filters from JSON).
        $userids = $DB->get_fieldset_sql($usersql, $userparams);
        $recipientcount = count($userids);
        $DB->set_field('tool_bulkmessaging_log', 'recipientcount', $recipientcount, ['id' => $startid]);

        $batchsize = (int) get_config('tool_bulkmessaging', 'batchsize');
        if ($batchsize < 1) {
            $batchsize = 50;
        }
        $batches = array_chunk($userids, $batchsize);

        $transaction = $DB->start_delegated_transaction();
        foreach ($batches as $batch) {
            $task = new \tool_bulkmessaging\task\send_bulk_message();
            $task->set_custom_data([
                'logid' => $startid,
                'userids' => $batch,
                'subject' => $log->subject,
                'messagebody' => $log->messagebody,
                'messageformat' => $log->messageformat,
                'userfromid' => $log->userfrom,
            ]);
            \core\task\manager::queue_adhoc_task($task);
        }
        $transaction->allow_commit();

        redirect($baseurl, get_string('messagestarted', 'tool_bulkmessaging', $recipientcount), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
    redirect($baseurl);
}

// Handle delete action (any finished status: completed, failed, cancelled, stopped).
$deleteid = optional_param('delete', 0, PARAM_INT);
if ($deleteid && confirm_sesskey()) {
    $log = $DB->get_record('tool_bulkmessaging_log', ['id' => $deleteid]);
    if ($log && in_array((int)$log->status, [2, 3, 4, 5])) {
        $deletetasks($deleteid);
        $DB->delete_records('tool_bulkmessaging_log', ['id' => $deleteid]);
        redirect($baseurl, get_string('messagedeleted', 'tool_bulkmessaging'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
    redirect($baseurl);
}

// Handle view action.
$viewid = optional_param('view', 0, PARAM_INT);

$statuslabels = [
    0 => get_string('statusqueued', 'tool_bulkmessaging'),
    1 => get_string('statusprocessing', 'tool_bulkmessaging'),
    2 => get_string('statuscompleted', 'tool_bulkmessaging'),
    3 => get_string('statusfailed', 'tool_bulkmessaging'),
    4 => get_string('statuscancelled', 'tool_bulkmessaging'),
    5 => get_string('statusstopped', 'tool_bulkmessaging'),
];

$statusclasses = [
    0 => 'badge bg-secondary',
    1 => 'badge bg-info',
    2 => 'badge bg-success',
    3 => 'badge bg-danger',
    4 => 'badge bg-dark',
    5 => 'badge bg-warning text-dark',
];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('bulkmessaging', 'tool_bulkmessaging'));

// Tab navigation.
echo tool_bulkmessaging_render_tabs('history');

// If viewing a specific message, show detail.
if ($viewid) {
    $log = $DB->get_record('tool_bulkmessaging_log', ['id' => $viewid]);
    if ($log) {
        $sender = $DB->get_record('user', ['id' => $log->userfrom]);
        $sendername = $sender ? fullname($sender) : '-';

        echo html_writer::start_div('card mb-3');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('h5', s($log->subject), ['class' => 'card-title']);
        echo html_writer::tag('p', get_string('sender', 'tool_bulkmessaging') . ': ' . $sendername);
        echo html_writer::tag('p', get_string('date', 'tool_bulkmessaging') . ': ' . userdate($log->timecreated));
        echo html_writer::tag('p', get_string('recipients', 'tool_bulkmessaging') . ': ' . $log->recipientcount);
        echo html_writer::tag('p',
            get_string('sent', 'tool_bulkmessaging') . ': ' . $log->sentcount . ' / ' .
            get_string('failed', 'tool_bulkmessaging') . ': ' . $log->failedcount
        );

        $statusbadge = html_writer::span(
            $statuslabels[$log->status] ?? $log->status,
            $statusclasses[$log->status] ?? 'badge bg-secondary'
        );
        echo html_writer::tag('p', get_string('status', 'tool_bulkmessaging') . ': ' . $statusbadge);

        if ($log->filterdata) {
            echo html_writer::tag('p', get_string('filtersused', 'tool_bulkmessaging') . ': ' .
                html_writer::tag('code', s($log->filterdata)));
        }

        echo html_writer::tag('h6', get_string('messagebody', 'tool_bulkmessaging'));
        echo html_writer::div(format_text($log->messagebody, $log->messageformat), 'border rounded p-3 bg-light');
        echo html_writer::end_div();
        echo html_writer::end_div();

        echo html_writer::tag('p',
            html_writer::link($baseurl, get_string('back'), ['class' => 'btn btn-secondary'])
        );
        echo $OUTPUT->footer();
        die;
    }
}

// Set up the table.
$table = new flexible_table('tool_bulkmessaging_history');
$table->define_columns(['subject', 'sender', 'recipients', 'progress', 'status', 'timecreated', 'actions']);
$table->define_headers([
    get_string('subject', 'tool_bulkmessaging'),
    get_string('sender', 'tool_bulkmessaging'),
    get_string('recipients', 'tool_bulkmessaging'),
    get_string('progress', 'tool_bulkmessaging'),
    get_string('status', 'tool_bulkmessaging'),
    get_string('date', 'tool_bulkmessaging'),
    get_string('actions'),
]);
$table->define_baseurl($baseurl);
$table->set_attribute('class', 'admintable generaltable');
$table->sortable(true, 'timecreated', SORT_DESC);
$table->no_sorting('progress');
$table->no_sorting('actions');
$table->pagesize(20, $DB->count_records('tool_bulkmessaging_log'));
$table->setup();

// Query log records with pagination.
$sort = $table->get_sql_sort();
if (!$sort) {
    $sort = 'timecreated DESC';
}

$pagestart = $table->get_page_start();
$pagesize = $table->get_page_size();

$logs = $DB->get_records_sql(
    "SELECT * FROM {tool_bulkmessaging_log} ORDER BY $sort",
    [],
    $pagestart,
    $pagesize
);

if (empty($logs) && $pagestart == 0) {
    echo $OUTPUT->notification(get_string('nohistory', 'tool_bulkmessaging'), \core\output\notification::NOTIFY_INFO);
} else {
    // Batch-load all senders to avoid N+1 queries.
    $senderids = array_unique(array_map(function($l) { return $l->userfrom; }, $logs));
    if (!empty($senderids)) {
        list($insql, $inparams) = $DB->get_in_or_equal($senderids);
        $senders = $DB->get_records_select('user', "id $insql", $inparams);
    } else {
        $senders = [];
    }

    foreach ($logs as $log) {
        $sendername = isset($senders[$log->userfrom]) ? fullname($senders[$log->userfrom]) : '-';

        $statusbadge = html_writer::span(
            $statuslabels[$log->status] ?? $log->status,
            $statusclasses[$log->status] ?? 'badge bg-secondary'
        );

        // Progress display.
        $processed = $log->sentcount + $log->failedcount;
        if ($log->recipientcount > 0 && !in_array((int)$log->status, [0])) {
            $pct = round(($processed / $log->recipientcount) * 100);
            $progress = html_writer::div(
                html_writer::div('', 'progress-bar bg-success', [
                    'role' => 'progressbar',
                    'style' => "width: {$pct}%",
                    'aria-valuenow' => $pct,
                    'aria-valuemin' => 0,
                    'aria-valuemax' => 100,
                ]),
                'progress', ['style' => 'height: 20px; min-width: 80px;']
            );
            $progress .= html_writer::tag('small',
                "{$log->sentcount}" . ($log->failedcount > 0 ? " / {$log->failedcount} " .
                get_string('failed', 'tool_bulkmessaging') : '') .
                " ({$pct}%)"
            );
        } else {
            $progress = '-';
        }

        // Action icons.
        $actions = [];

        // View — always available.
        $viewurl = new moodle_url($baseurl, ['view' => $log->id]);
        $actions[] = tool_bulkmessaging_action_icon($viewurl, 't/viewdetails', get_string('view'));

        // Cancel — queued (status 0).
        if ($log->status == 0) {
            $cancelurl = new moodle_url($baseurl, ['cancel' => $log->id, 'sesskey' => sesskey()]);
            $actions[] = tool_bulkmessaging_action_icon($cancelurl, 't/block',
                get_string('cancel'), [
                    'onclick' => "return confirm('" . get_string('confirmcancel', 'tool_bulkmessaging') . "');",
                ]);
        }

        // Stop — processing (status 1).
        if ($log->status == 1) {
            $stopurl = new moodle_url($baseurl, ['stop' => $log->id, 'sesskey' => sesskey()]);
            $actions[] = tool_bulkmessaging_action_icon($stopurl, 't/stop',
                get_string('stop', 'tool_bulkmessaging'), [
                    'onclick' => "return confirm('" . get_string('confirmstop', 'tool_bulkmessaging') . "');",
                ]);
        }

        // Start — failed or stopped (status 3 or 5).
        if (in_array((int)$log->status, [3, 5])) {
            $starturl = new moodle_url($baseurl, ['start' => $log->id, 'sesskey' => sesskey()]);
            $actions[] = tool_bulkmessaging_action_icon($starturl, 't/play',
                get_string('start', 'tool_bulkmessaging'), [
                    'onclick' => "return confirm('" . get_string('confirmstart', 'tool_bulkmessaging') . "');",
                ]);
        }

        // Delete — finished statuses (completed, failed, cancelled, stopped).
        if (in_array((int)$log->status, [2, 3, 4, 5])) {
            $deleteurl = new moodle_url($baseurl, ['delete' => $log->id, 'sesskey' => sesskey()]);
            $actions[] = tool_bulkmessaging_action_icon($deleteurl, 't/delete',
                get_string('delete'), [
                    'onclick' => "return confirm('" . get_string('confirmdelete', 'tool_bulkmessaging') . "');",
                ]);
        }

        $table->add_data([
            s($log->subject),
            $sendername,
            $log->recipientcount,
            $progress,
            $statusbadge,
            userdate($log->timecreated),
            implode(' ', $actions),
        ]);
    }

    $table->finish_output();
}

echo $OUTPUT->footer();
