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

namespace local_evalfp\form;

/**
 * Form for importing RA and CE definitions from an ODS file.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ra_ce_import extends \moodleform {
    /**
     * Defines the ODS upload fields and import mode selector.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('filepicker', 'importfile', get_string('file'), null, [
            'accepted_types' => ['.ods'],
            'maxfiles' => 1,
        ]);
        $mform->addRule('importfile', get_string('error_required', 'local_evalfp'), 'required', null, 'client');

        $choices = [
            '' => get_string('choosedots'),
            'merge' => get_string('import_ra_ce_mode_merge', 'local_evalfp'),
            'replace' => get_string('import_ra_ce_mode_replace', 'local_evalfp'),
        ];
        $mform->addElement('select', 'mode', get_string('import_ra_ce_mode', 'local_evalfp'), $choices);
        $mform->setType('mode', PARAM_ALPHA);
        $mform->addRule('mode', get_string('error_required', 'local_evalfp'), 'required', null, 'client');
        $mform->addHelpButton('mode', 'import_ra_ce_mode', 'local_evalfp');

        $this->add_action_buttons(true, get_string('continue'));
    }
}
