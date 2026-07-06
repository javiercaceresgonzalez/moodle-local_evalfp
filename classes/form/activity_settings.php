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
 * Form for course activity display settings.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_settings extends \moodleform {
    /**
     * Defines activity display settings fields.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $courseid = (int)$this->_customdata['courseid'];

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement(
            'advcheckbox',
            'showactivitycurriculum',
            get_string('form_activity_settings_show_curriculum', 'local_evalfp'),
            null,
            [],
            [0, 1]
        );
        $mform->setDefault('showactivitycurriculum', 0);
        $mform->addHelpButton(
            'showactivitycurriculum',
            'form_activity_settings_show_curriculum',
            'local_evalfp'
        );

        $mform->addElement(
            'advcheckbox',
            'activitycurriculumexpanded',
            get_string('form_activity_settings_expanded', 'local_evalfp'),
            null,
            [],
            [0, 1]
        );
        $mform->setDefault('activitycurriculumexpanded', 0);
        $mform->addHelpButton(
            'activitycurriculumexpanded',
            'form_activity_settings_expanded',
            'local_evalfp'
        );
        $mform->disabledIf('activitycurriculumexpanded', 'showactivitycurriculum', 'notchecked');

        $this->add_action_buttons(true, get_string('common_save_changes', 'local_evalfp'));
    }
}
