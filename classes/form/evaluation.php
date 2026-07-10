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
 * Form for creating or editing a course evaluation.
 *
 * The form keeps the course evaluation model consistent: codes are unique per
 * course, final and extraordinary evaluations are limited to one each, and the
 * configured date range remains valid.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evaluation extends \moodleform {
    /**
     * Defines the evaluation identity, type and date range fields.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;
        $courseid = $customdata->courseid;
        $editing = !empty($customdata->id);

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'id', (int)($customdata->id ?? 0));
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'code', get_string('common_code', 'local_evalfp'), ['size' => 12, 'maxlength' => 10]);
        $mform->setType('code', PARAM_ALPHANUMEXT);
        $mform->addRule('code', get_string('error_required', 'local_evalfp'), 'required', null, 'client');

        $mform->addElement(
            'text',
            'description',
            get_string('form_evaluation_name', 'local_evalfp'),
            ['size' => 64, 'maxlength' => 255]
        );
        $mform->setType('description', PARAM_TEXT);
        $mform->addRule('description', get_string('error_required', 'local_evalfp'), 'required', null, 'client');

        $mform->addElement(
            'select',
            'type',
            get_string('form_evaluation_type', 'local_evalfp'),
            \local_evalfp_get_evaluation_type_options()
        );
        $mform->setType('type', PARAM_INT);
        $mform->setDefault('type', \LOCAL_EVALFP_EVALUATION_TYPE_PARTIAL);
        $mform->addHelpButton('type', 'form_evaluation_type', 'local_evalfp');

        $currentyear = (int)userdate(time(), '%Y');
        $dateopts = [
            'startyear' => $currentyear - 1,
            'stopyear' => $currentyear + 6,
            'step' => 1,
            'timezone' => 99,
        ];
        $mform->addElement('date_selector', 'startdate', get_string('form_evaluation_startdate', 'local_evalfp'), $dateopts);
        $mform->addElement('date_selector', 'enddate', get_string('form_evaluation_enddate', 'local_evalfp'), $dateopts);

        $this->add_action_buttons(
            true,
            $editing ? get_string('common_save_changes', 'local_evalfp') : get_string('common_add', 'local_evalfp')
        );
    }

    /**
     * Validates dates, unique code and unique final/extraordinary evaluation.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Validation errors indexed by form element name.
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        $type = (int)($data['type'] ?? \LOCAL_EVALFP_EVALUATION_TYPE_PARTIAL);
        $allowedtypes = [
            \LOCAL_EVALFP_EVALUATION_TYPE_FINAL,
            \LOCAL_EVALFP_EVALUATION_TYPE_PARTIAL,
            \LOCAL_EVALFP_EVALUATION_TYPE_EXTRAORDINARY,
        ];
        if (!in_array($type, $allowedtypes, true)) {
            $errors['type'] = get_string('error_invalid_evaluation', 'local_evalfp');
        }

        if (in_array($type, [\LOCAL_EVALFP_EVALUATION_TYPE_FINAL, \LOCAL_EVALFP_EVALUATION_TYPE_EXTRAORDINARY], true)) {
            $params = [
                'courseid' => (int)$data['courseid'],
                'type' => $type,
                'id' => (int)($data['id'] ?? 0),
            ];
            $sql = 'courseid = :courseid AND type = :type AND id <> :id';
            if ($DB->record_exists_select('local_evalfp_course_evaluation', $sql, $params)) {
                $errors['type'] = $type === \LOCAL_EVALFP_EVALUATION_TYPE_FINAL
                    ? get_string('error_evaluation_only_one_final', 'local_evalfp')
                    : get_string('error_evaluation_only_one_extraordinary', 'local_evalfp');
            }
        }

        if (!empty($data['startdate']) && !empty($data['enddate'])) {
            $startdate = usergetmidnight((int)$data['startdate']);
            $enddate = usergetmidnight((int)$data['enddate']);
            if ($startdate > $enddate) {
                $errors['enddate'] = get_string('error_evaluation_end_after_start', 'local_evalfp');
            }
        }

        $code = strtoupper(trim($data['code'] ?? ''));
        $params = ['courseid' => $data['courseid'], 'code' => $code];
        $exists = $DB->get_record('local_evalfp_course_evaluation', $params, 'id', IGNORE_MISSING);
        if ($exists && (empty($data['id']) || (int)$exists->id !== (int)$data['id'])) {
            $errors['code'] = get_string('error_code_exists', 'local_evalfp');
        }

        return $errors;
    }
}
