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
 * Form for creating or editing a course RA.
 *
 * RA codes are unique within a course and identify the learning outcomes used
 * by the assessment and curriculum reports.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ra extends \moodleform {
    /**
     * Defines the RA code and description fields.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $editing = !empty($this->_customdata['editing']);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'courseraid');
        $mform->setType('courseraid', PARAM_INT);

        $mform->addElement('text', 'code', get_string('common_code', 'local_evalfp'), ['size' => 12, 'maxlength' => 10]);
        $mform->setType('code', PARAM_ALPHANUMEXT);
        $mform->addRule('code', get_string('error_required', 'local_evalfp'), 'required', null, 'client');

        $mform->addElement(
            'textarea',
            'description',
            get_string('form_ra_description', 'local_evalfp'),
            ['rows' => 4, 'cols' => 64]
        );
        $mform->setType('description', PARAM_TEXT);
        $mform->addRule('description', get_string('error_required', 'local_evalfp'), 'required', null, 'client');

        $this->add_action_buttons(
            true,
            $editing ? get_string('common_save_changes', 'local_evalfp') : get_string('common_add', 'local_evalfp')
        );
    }

    /**
     * Validates unique RA code within the course.
     *
     * @param array<string, mixed> $data Submitted data.
     * @param array<string, mixed> $files Submitted files.
     * @return array<string, string> Validation errors indexed by form element name.
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        $params = ['courseid' => $data['courseid'], 'code' => trim($data['code'] ?? '')];
        $exists = $DB->get_record('local_evalfp_course_ra', $params, 'id', IGNORE_MISSING);
        if ($exists && (empty($data['courseraid']) || (int)$exists->id !== (int)$data['courseraid'])) {
            $errors['code'] = get_string('error_code_exists', 'local_evalfp');
        }

        return $errors;
    }
}
