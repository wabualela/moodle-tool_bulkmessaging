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
 * Language strings for tool_bulkmessaging.
 *
 * @package    tool_bulkmessaging
 * @copyright  2026 Moddaker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Bulk messaging';
$string['bulkmessaging'] = 'Bulk messaging';
$string['bulkmessaging:sendmessage'] = 'Send bulk messages';
$string['sendmessage'] = 'Send bulk message';
$string['messagehistory'] = 'Message history';
$string['subject'] = 'Subject';
$string['messagebody'] = 'Message body';
$string['matchingusers'] = 'Matching users: {$a}';
$string['nousersmatching'] = 'No users match the current filters.';
$string['messagesent'] = 'Bulk message has been queued for delivery to {$a} user(s).';
$string['batchsize'] = 'Batch size';
$string['batchsize_desc'] = 'Number of messages to send per background task batch.';
$string['maxrecipients'] = 'Maximum recipients';
$string['maxrecipients_desc'] = 'Maximum number of recipients allowed per bulk message. Set to 0 for no limit.';
$string['toomanyrecipients'] = 'Too many recipients. The maximum allowed is {$a}. Please narrow your filters.';
$string['settings'] = 'Bulk messaging settings';
$string['tasksendbulkmessage'] = 'Send bulk message batch';
$string['status'] = 'Status';
$string['statusqueued'] = 'Queued';
$string['statusprocessing'] = 'Processing';
$string['statuscompleted'] = 'Completed';
$string['statusfailed'] = 'Failed';
$string['statuscancelled'] = 'Cancelled';
$string['sender'] = 'Sender';
$string['recipients'] = 'Recipients';
$string['sent'] = 'Sent';
$string['failed'] = 'Failed';
$string['date'] = 'Date';
$string['progress'] = 'Progress';
$string['nohistory'] = 'No messages have been sent yet.';
$string['privacy:metadata'] = 'The bulk messaging tool does not store personal user data. It stores admin action logs only.';
$string['messagingdisabled'] = 'Messaging is disabled on this site.';
$string['composemessage'] = 'Compose message';
$string['filterusers'] = 'Filter users';
$string['filtersrequired'] = 'You must apply at least one filter before sending a bulk message.';
$string['confirmsend'] = 'Confirm bulk message';
$string['confirmrecipients'] = 'This message will be sent to {$a} user(s).';
$string['confirmsendmessage'] = 'Are you sure you want to send this message to {$a} user(s)? This action cannot be undone once processing begins.';
$string['confirmcancel'] = 'Are you sure you want to cancel this queued message?';
$string['messagecancelled'] = 'Bulk message has been cancelled and pending tasks removed.';
$string['messageresendnotice'] = 'Message status has been reset. Please create a new bulk message to resend to the same recipients.';
$string['filtersused'] = 'Filters used';
$string['eventbulkmessagesent'] = 'Bulk message sent';
$string['sendtoall'] = 'Send to all users';
$string['sendtoall_desc'] = 'Send this message to all active users on the site, ignoring filters.';
$string['confirmsendallusers'] = 'This message will be sent to ALL {$a} active user(s) on the site.';
$string['alluserscount'] = 'Total active users on site: {$a}. Apply filters below to target specific users, or check "Send to all users" in the form.';
$string['statusstopped'] = 'Stopped';
$string['stop'] = 'Stop';
$string['confirmstop'] = 'Are you sure you want to stop this message? Messages already sent cannot be recalled.';
$string['messagestopped'] = 'Bulk message has been stopped. Messages already sent remain delivered.';
$string['confirmdelete'] = 'Are you sure you want to delete this log entry?';
$string['messagedeleted'] = 'Message log entry has been deleted.';
$string['start'] = 'Start';
$string['confirmstart'] = 'Are you sure you want to re-queue this message for delivery to all active users?';
$string['messagestarted'] = 'Message has been re-queued for delivery to {$a} user(s).';
$string['userlist'] = 'Matching users';
$string['csvupload'] = 'CSV upload';
$string['uploadcsv'] = 'Upload CSV';
$string['csvfile'] = 'CSV file';
$string['csvfile_help'] = 'Upload a CSV file containing an "email" column. Users matching these email addresses will be selected as recipients.';
$string['csvdelimiter'] = 'CSV delimiter';
$string['csvencoding'] = 'File encoding';
$string['csvimporterror'] = 'Error importing CSV file.';
$string['emailcolumnrequired'] = 'The CSV file must contain an "email" column.';
$string['csvimportsummary'] = 'CSV import: {$a->found} user(s) found out of {$a->total} email(s) in the file.';
$string['invalidemails'] = 'The following emails were not found: {$a}';
$string['csvuploadsuccess'] = '{$a} user(s) loaded from CSV. Go to the compose tab to send a message.';
$string['placeholders'] = 'Available placeholders';
$string['placeholders_help'] = 'You can use the following placeholders in the subject and message body. They will be replaced with each recipient\'s details: {fullname}, {firstname}, {lastname}, {email}, {username}';
$string['clearcsv'] = 'Clear CSV selection';
$string['usecsv'] = 'Use CSV recipients';
$string['usecsv_desc'] = 'Send this message to the users loaded from CSV instead of using filters.';
$string['csvusersloaded'] = '{$a} user(s) loaded from CSV are selected as recipients.';
$string['nocsvusers'] = 'No CSV users loaded. Please upload a CSV file first.';
$string['refreshing'] = 'Refreshing...';
