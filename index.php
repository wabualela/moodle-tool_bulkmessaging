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
 * Main page for bulk messaging: filter users, compose and send.
 *
 * @package    tool_bulkmessaging
 * @copyright  2026 Moddaker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/user/filters/lib.php');
require_once(__DIR__ . '/locallib.php');

admin_externalpage_setup('toolbulkmessaging');

$context = context_system::instance();
require_capability('tool/bulkmessaging:sendmessage', $context);

if (empty($CFG->messaging)) {
    throw new \moodle_exception('messagingdisabled', 'tool_bulkmessaging');
}

$confirm = optional_param('confirm', 0, PARAM_BOOL);
$sendtoall = optional_param('sendtoall', 0, PARAM_BOOL);

$baseurl = new moodle_url("/$CFG->admin/tool/bulkmessaging/index.php");
$historyurl = new moodle_url("/$CFG->admin/tool/bulkmessaging/history.php");

// Set up user filtering.
$ufiltering = new user_filtering(null, $baseurl);

// Get filtered user SQL.
list($filtersql, $filterparams) = $ufiltering->get_sql_filter();

// Build user query — if sendtoall, ignore filters.
$usersql = "SELECT u.id FROM {user} u WHERE u.deleted = 0 AND u.suspended = 0 AND u.id > 1";
$userparams = [];
$hasactivefilters = !empty($filtersql);

if (!$sendtoall && $hasactivefilters) {
    $usersql .= " AND $filtersql";
    $userparams = $filterparams;
}

$usercount = $DB->count_records_sql("SELECT COUNT(*) FROM ({$usersql}) subq", $userparams);

// Max recipients check.
$maxrecipients = (int) get_config('tool_bulkmessaging', 'maxrecipients');

// Set up the message form.
$msgform = new \tool_bulkmessaging\form\message_form($baseurl);

if ($msgform->is_cancelled()) {
    redirect($baseurl);
}

// Handle confirmed send.
if ($confirm && confirm_sesskey()) {
    $subject = required_param('subject', PARAM_TEXT);
    $messagebody = required_param('messagebody', PARAM_RAW);
    $messageformat = required_param('messageformat', PARAM_INT);

    // Re-check sendtoall: rebuild query without filters if sending to all.
    if ($sendtoall) {
        $usersql = "SELECT u.id FROM {user} u WHERE u.deleted = 0 AND u.suspended = 0 AND u.id > 1";
        $userparams = [];
        $usercount = $DB->count_records_sql("SELECT COUNT(*) FROM ({$usersql}) subq", $userparams);
    }

    if ($usercount == 0) {
        redirect($baseurl, get_string('nousersmatching', 'tool_bulkmessaging'), null,
            \core\output\notification::NOTIFY_ERROR);
    }

    // Serialize filter data for the log.
    $filterdata = '';
    if ($sendtoall) {
        $filterdata = json_encode(['sendtoall' => true]);
    } else if (!empty($SESSION->user_filtering)) {
        $simplifiedfilters = [];
        foreach ($SESSION->user_filtering as $fname => $datas) {
            $simplifiedfilters[$fname] = count($datas) . ' filter(s)';
        }
        $filterdata = json_encode($simplifiedfilters);
    }

    // Use a transaction for log insert + task queueing.
    $transaction = $DB->start_delegated_transaction();

    $logrecord = new stdClass();
    $logrecord->subject = $subject;
    $logrecord->messagebody = $messagebody;
    $logrecord->messageformat = $messageformat;
    $logrecord->userfrom = $USER->id;
    $logrecord->recipientcount = $usercount;
    $logrecord->sentcount = 0;
    $logrecord->failedcount = 0;
    $logrecord->status = 0; // Queued.
    $logrecord->timecreated = time();
    $logrecord->filterdata = $filterdata;

    $logid = $DB->insert_record('tool_bulkmessaging_log', $logrecord);

    // Get all matching user IDs.
    $userids = $DB->get_fieldset_sql($usersql, $userparams);

    // Chunk into batches.
    $batchsize = (int) get_config('tool_bulkmessaging', 'batchsize');
    if ($batchsize < 1) {
        $batchsize = 50;
    }
    $batches = array_chunk($userids, $batchsize);

    foreach ($batches as $batch) {
        $task = new \tool_bulkmessaging\task\send_bulk_message();
        $task->set_custom_data([
            'logid' => $logid,
            'userids' => $batch,
            'subject' => $subject,
            'messagebody' => $messagebody,
            'messageformat' => $messageformat,
            'userfromid' => $USER->id,
        ]);
        \core\task\manager::queue_adhoc_task($task);
    }

    $transaction->allow_commit();

    // Fire event.
    $event = \tool_bulkmessaging\event\bulk_message_sent::create([
        'context' => $context,
        'other' => [
            'logid' => $logid,
            'recipientcount' => $usercount,
            'subject' => $subject,
        ],
    ]);
    $event->trigger();

    redirect($historyurl, get_string('messagesent', 'tool_bulkmessaging', $usercount), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// Handle form submission — show confirmation page.
if ($formdata = $msgform->get_data()) {
    $sendtoall = !empty($formdata->sendtoall);

    // If send to all, rebuild the count without filters.
    if ($sendtoall) {
        $usersql = "SELECT u.id FROM {user} u WHERE u.deleted = 0 AND u.suspended = 0 AND u.id > 1";
        $userparams = [];
        $usercount = $DB->count_records_sql("SELECT COUNT(*) FROM ({$usersql}) subq", $userparams);
    }

    if ($usercount == 0) {
        redirect($baseurl, get_string('nousersmatching', 'tool_bulkmessaging'), null,
            \core\output\notification::NOTIFY_ERROR);
    }

    if (!$sendtoall && !$hasactivefilters) {
        redirect($baseurl, get_string('filtersrequired', 'tool_bulkmessaging'), null,
            \core\output\notification::NOTIFY_ERROR);
    }

    if ($maxrecipients > 0 && $usercount > $maxrecipients) {
        redirect($baseurl, get_string('toomanyrecipients', 'tool_bulkmessaging', $maxrecipients), null,
            \core\output\notification::NOTIFY_ERROR);
    }

    $subject = $formdata->subject;
    $messagebody = $formdata->messagebody['text'];
    $messageformat = $formdata->messagebody['format'];

    // Confirmation page.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('confirmsend', 'tool_bulkmessaging'));

    if ($sendtoall) {
        echo $OUTPUT->notification(
            get_string('confirmsendallusers', 'tool_bulkmessaging', $usercount),
            \core\output\notification::NOTIFY_WARNING
        );
    } else {
        echo $OUTPUT->notification(
            get_string('confirmrecipients', 'tool_bulkmessaging', $usercount),
            \core\output\notification::NOTIFY_WARNING
        );
    }

    echo html_writer::tag('h4', get_string('subject', 'tool_bulkmessaging'));
    echo html_writer::tag('p', s($subject), ['class' => 'fw-bold']);

    echo html_writer::tag('h4', get_string('messagebody', 'tool_bulkmessaging'));
    echo html_writer::div(format_text($messagebody, $messageformat), 'border rounded p-3 mb-3 bg-light');

    $confirmurl = new moodle_url($baseurl, [
        'confirm' => 1,
        'sesskey' => sesskey(),
        'subject' => $subject,
        'messagebody' => $messagebody,
        'messageformat' => $messageformat,
        'sendtoall' => $sendtoall ? 1 : 0,
    ]);
    $confirmbtn = new single_button($confirmurl, get_string('confirm'), 'post', single_button::BUTTON_DANGER);
    $cancelbtn = new single_button($baseurl, get_string('cancel'), 'get');

    echo $OUTPUT->confirm(
        get_string('confirmsendmessage', 'tool_bulkmessaging', $usercount),
        $confirmbtn,
        $cancelbtn
    );

    echo $OUTPUT->footer();
    die;
}

// Display the main page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('bulkmessaging', 'tool_bulkmessaging'));

// Tab navigation.
echo tool_bulkmessaging_render_tabs('compose');

// Render filter forms.
echo html_writer::start_div('user-filters mb-3');
echo $OUTPUT->heading(get_string('filterusers', 'tool_bulkmessaging'), 3);
$ufiltering->display_add();
$ufiltering->display_active();
echo html_writer::end_div();

// Display matching user count (based on filters, not send-to-all).
if ($hasactivefilters) {
    if ($usercount > 0) {
        $countmsg = get_string('matchingusers', 'tool_bulkmessaging', $usercount);
        if ($maxrecipients > 0 && $usercount > $maxrecipients) {
            $countmsg .= ' ' . get_string('toomanyrecipients', 'tool_bulkmessaging', $maxrecipients);
            echo $OUTPUT->notification($countmsg, \core\output\notification::NOTIFY_ERROR);
        } else {
            echo $OUTPUT->notification($countmsg, \core\output\notification::NOTIFY_INFO);
        }
    } else {
        echo $OUTPUT->notification(get_string('nousersmatching', 'tool_bulkmessaging'),
            \core\output\notification::NOTIFY_WARNING);
    }
} else {
    // Show total user count when no filters are active (for send-to-all context).
    $alluserscount = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {user} WHERE deleted = 0 AND suspended = 0 AND id > 1"
    );
    echo $OUTPUT->notification(
        get_string('alluserscount', 'tool_bulkmessaging', $alluserscount),
        \core\output\notification::NOTIFY_INFO
    );
}

// Always display compose form — send-to-all checkbox allows bypassing filters.
$msgform->display();

echo $OUTPUT->footer();
