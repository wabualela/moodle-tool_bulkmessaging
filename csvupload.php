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
 * CSV upload page for bulk messaging recipients.
 *
 * @package    tool_bulkmessaging
 * @copyright  2026 Moddaker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once(__DIR__ . '/locallib.php');

admin_externalpage_setup('toolbulkmessagingcsvupload');

$context = context_system::instance();
require_capability('tool/bulkmessaging:sendmessage', $context);

$baseurl = new moodle_url("/$CFG->admin/tool/bulkmessaging/csvupload.php");
$composeurl = new moodle_url("/$CFG->admin/tool/bulkmessaging/index.php");

$form = new \tool_bulkmessaging\form\csv_upload_form($baseurl);

if ($form->is_cancelled()) {
    redirect($composeurl);
}

if ($formdata = $form->get_data()) {
    $iid = \csv_import_reader::get_new_iid('tool_bulkmessaging');
    $csvreader = new \csv_import_reader($iid, 'tool_bulkmessaging');

    $content = $form->get_file_content('csvfile');
    $readcount = $csvreader->load_csv_content($content, $formdata->encoding, $formdata->delimiter);

    if ($readcount === false) {
        redirect($baseurl, get_string('csvimporterror', 'tool_bulkmessaging'), null,
            \core\output\notification::NOTIFY_ERROR);
    }

    // Find the email column.
    $columns = $csvreader->get_columns();
    $columns = array_map('strtolower', array_map('trim', $columns));
    $emailindex = array_search('email', $columns);

    if ($emailindex === false) {
        $csvreader->close();
        $csvreader->cleanup();
        redirect($baseurl, get_string('emailcolumnrequired', 'tool_bulkmessaging'), null,
            \core\output\notification::NOTIFY_ERROR);
    }

    // Collect all emails from CSV.
    $csvreader->init();
    $emails = [];
    while ($row = $csvreader->next()) {
        $email = trim($row[$emailindex]);
        if (!empty($email)) {
            $emails[] = core_text::strtolower($email);
        }
    }
    $csvreader->close();
    $csvreader->cleanup();

    $totalemails = count($emails);

    if ($totalemails === 0) {
        redirect($baseurl, get_string('csvimporterror', 'tool_bulkmessaging'), null,
            \core\output\notification::NOTIFY_ERROR);
    }

    // Look up users by email in batches.
    $userids = [];
    $foundemails = [];
    $batches = array_chunk(array_unique($emails), 500);

    foreach ($batches as $batch) {
        list($insql, $inparams) = $DB->get_in_or_equal($batch, SQL_PARAMS_NAMED);
        $sql = "SELECT id, " . $DB->sql_compare_text('email') . " AS email
                  FROM {user}
                 WHERE deleted = 0 AND suspended = 0
                   AND " . $DB->sql_compare_text('email') . " $insql";
        $records = $DB->get_records_sql($sql, $inparams);
        foreach ($records as $record) {
            $userids[] = (int)$record->id;
            $foundemails[] = core_text::strtolower($record->email);
        }
    }

    // Store in session.
    $SESSION->bulkmessaging_csvusers = array_unique($userids);

    $foundcount = count($SESSION->bulkmessaging_csvusers);
    $notfound = array_diff(array_unique($emails), $foundemails);

    $summary = new stdClass();
    $summary->found = $foundcount;
    $summary->total = count(array_unique($emails));

    $message = get_string('csvimportsummary', 'tool_bulkmessaging', $summary);
    if (!empty($notfound) && count($notfound) <= 20) {
        $message .= ' ' . get_string('invalidemails', 'tool_bulkmessaging', implode(', ', $notfound));
    }

    redirect($composeurl, $message, null,
        $foundcount > 0 ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_WARNING);
}

// Display the page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('csvupload', 'tool_bulkmessaging'));

echo tool_bulkmessaging_render_tabs('csvupload');

$form->display();

echo $OUTPUT->footer();
