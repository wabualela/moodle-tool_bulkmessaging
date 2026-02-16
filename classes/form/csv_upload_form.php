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
 * CSV upload form for tool_bulkmessaging.
 *
 * @package    tool_bulkmessaging
 * @copyright  2026 Moddaker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_bulkmessaging\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/csvlib.class.php');

/**
 * Form for uploading a CSV file with recipient email addresses.
 */
class csv_upload_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('filepicker', 'csvfile', get_string('csvfile', 'tool_bulkmessaging'), null,
            ['accepted_types' => ['.csv', '.txt']]);
        $mform->addHelpButton('csvfile', 'csvfile', 'tool_bulkmessaging');
        $mform->addRule('csvfile', null, 'required');

        $delimiters = \csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter', get_string('csvdelimiter', 'tool_bulkmessaging'), $delimiters);
        $mform->setDefault('delimiter', 'comma');

        $encodings = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('csvencoding', 'tool_bulkmessaging'), $encodings);
        $mform->setDefault('encoding', 'UTF-8');

        $this->add_action_buttons(true, get_string('uploadcsv', 'tool_bulkmessaging'));
    }
}
