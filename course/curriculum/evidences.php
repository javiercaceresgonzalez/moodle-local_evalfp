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
 * Course curriculum evidence linking matrix.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/local/evalfp/lib.php');

// Page parameters.
$courseid = required_param('courseid', PARAM_INT);
// Course access.
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/evalfp:useincourse', $context);

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/evalfp/course/curriculum/evidences.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('page_evidences_title', 'local_evalfp'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navigation->override_active_url(new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid]));
$PAGE->navbar->add(
    get_string('pluginname', 'local_evalfp'),
    new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid])
);
$PAGE->navbar->add(get_string('page_evidences_title', 'local_evalfp'));

// Load matrix data.
$evidencematrix = local_evalfp_get_curriculum_evidence_matrix_data($courseid);
$ces = $evidencematrix['ces'];
$evidences = $evidencematrix['evidences'];

// Save submitted matrix changes.
if (optional_param('savecurriculumevidences', 0, PARAM_BOOL)) {
    require_sesskey();
    local_evalfp_save_curriculum_evidence_matrix($evidencematrix);
    \core\notification::success(get_string('success_changes_saved', 'local_evalfp'));
    redirect(new moodle_url('/local/evalfp/course/curriculum/evidences.php', ['courseid' => $courseid]));
}

// Page actions.
$pageactions = html_writer::tag('button', get_string('common_save_changes', 'local_evalfp'), [
    'class' => 'btn btn-primary',
    'type' => 'submit',
    'form' => 'local-evalfp-curriculum-evidences-form',
    'name' => 'savecurriculumevidences',
    'value' => '1',
]);
local_evalfp_start_course_layout($courseid, 'evidences', $pageactions);

// Empty-state guards.
if (!$ces) {
    echo $OUTPUT->notification(get_string('page_curriculum_no_ce', 'local_evalfp'), \core\output\notification::NOTIFY_INFO);
    local_evalfp_end_course_layout();
    exit;
}

if (!$evidences) {
    echo $OUTPUT->notification(get_string('page_evidences_no_grade_items', 'local_evalfp'), \core\output\notification::NOTIFY_INFO);
    local_evalfp_end_course_layout();
    exit;
}

// Render matrix.
echo local_evalfp_render_curriculum_evidence_matrix($courseid, $evidencematrix);

local_evalfp_end_course_layout();
