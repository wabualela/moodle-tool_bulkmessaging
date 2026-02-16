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
 * Message compose form for tool_bulkmessaging.
 *
 * @package    tool_bulkmessaging
 * @copyright  2026 Moddaker
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_bulkmessaging\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for composing a bulk message.
 */
class message_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $SESSION;

        $mform = $this->_form;

        $mform->addElement('header', 'composemessage', get_string('composemessage', 'tool_bulkmessaging'));

        $mform->addElement('advcheckbox', 'sendtoall', get_string('sendtoall', 'tool_bulkmessaging'),
            get_string('sendtoall_desc', 'tool_bulkmessaging'));
        $mform->setDefault('sendtoall', 0);

        // CSV recipients checkbox — only visible when CSV users are loaded.
        if (!empty($SESSION->bulkmessaging_csvusers)) {
            $mform->addElement('advcheckbox', 'usecsv', get_string('usecsv', 'tool_bulkmessaging'),
                get_string('usecsv_desc', 'tool_bulkmessaging'));
            $mform->setDefault('usecsv', 1);
            $mform->disabledIf('usecsv', 'sendtoall', 'checked');
            $mform->disabledIf('sendtoall', 'usecsv', 'checked');
        }

        $mform->addElement('text', 'subject', get_string('subject', 'tool_bulkmessaging'), ['size' => 60]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'server');
        $mform->addRule('subject', null, 'maxlength', 255, 'server');

        $mform->addElement('editor', 'messagebody', get_string('messagebody', 'tool_bulkmessaging'),
            ['rows' => 15], [
                'maxfiles' => 0,
                'noclean' => false,
                'context' => \context_system::instance(),
                'trusttext' => false,
                'subdirs' => false,
            ]
        );
        $mform->setType('messagebody', PARAM_RAW);
        $mform->setDefault('messagebody', ['text' => '', 'format' => FORMAT_HTML]);
        $mform->addRule('messagebody', null, 'required', null, 'server');

        $mform->addElement('static', 'placeholdersinfo', get_string('placeholders', 'tool_bulkmessaging'),
            \html_writer::div(get_string('placeholders_help', 'tool_bulkmessaging'), 'alert alert-info'));

        $this->add_action_buttons(true, get_string('sendmessage', 'tool_bulkmessaging'));
    }

    /**
     * Validate form data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty(trim($data['subject']))) {
            $errors['subject'] = get_string('required');
        }

        if (empty($data['messagebody']['text']) || empty(trim(strip_tags($data['messagebody']['text'])))) {
            $errors['messagebody'] = get_string('required');
        }

        return $errors;
    }
}
