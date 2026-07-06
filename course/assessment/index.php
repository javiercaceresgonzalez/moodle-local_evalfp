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
 * Course assessment summary by learning outcome.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/local/evalfp/lib.php');

// Page parameters.
$courseid = required_param('courseid', PARAM_INT);
$evaluationid = optional_param('evaluationid', 0, PARAM_INT);

// Course access.
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('moodle/grade:viewall', $context);

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/evalfp/course/assessment/index.php', [
    'courseid' => $courseid,
    'evaluationid' => $evaluationid,
]));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('page_assessment_title', 'local_evalfp'));
$PAGE->set_heading(format_string($course->fullname));

// Build selector state and tertiary navigation controls.
$evaluationselector = local_evalfp_get_assessment_evaluation_options($courseid, $evaluationid);
$options = $evaluationselector['options'];
$evaluationsbyid = $evaluationselector['byid'];
$selectedevaluationid = $evaluationselector['selectedid'];
$validevaluation = $evaluationselector['valid'];

$controls = '';
if ($evaluationselector['evaluations']) {
    $baseurl = new moodle_url('/local/evalfp/course/assessment/index.php', ['courseid' => $courseid]);
    $select = new single_select($baseurl, 'evaluationid', $options, $selectedevaluationid, null);
    $select->set_label(get_string('common_evaluation_period', 'local_evalfp'), ['class' => 'mr-2']);
    $controls = html_writer::tag('div', $OUTPUT->render($select), ['class' => 'navitem align-self-center']);
}
local_evalfp_start_course_layout($courseid, 'assessment', '', $controls);

// Empty-state guards. Each guard exits early so the page never renders partial or misleading data.
if (!$evaluationselector['evaluations']) {
    echo $OUTPUT->notification(get_string(
        'page_evaluations_none',
        'local_evalfp'
    ), \core\output\notification::NOTIFY_WARNING);
    local_evalfp_end_course_layout();
    exit;
}

if (!$validevaluation) {
    echo $OUTPUT->notification(get_string(
        'error_invalid_evaluation',
        'local_evalfp'
    ), \core\output\notification::NOTIFY_WARNING);
    local_evalfp_end_course_layout();
    exit;
}

if ($selectedevaluationid <= 0) {
    echo $OUTPUT->notification(get_string(
        'page_assessment_select_evaluation',
        'local_evalfp'
    ), \core\output\notification::NOTIFY_INFO);
    local_evalfp_end_course_layout();
    exit;
}

// Get users only after there is a valid selected evaluation.
$users = local_evalfp_get_assessment_users($context);
if (!$users) {
    echo $OUTPUT->notification(get_string(
        'page_assessment_no_users',
        'local_evalfp'
    ), \core\output\notification::NOTIFY_WARNING);
    local_evalfp_end_course_layout();
    exit;
}

// Calculate and render the assessment summary.
$calculationdata = local_evalfp_get_assessment_calculation_data($courseid, $selectedevaluationid, $evaluationsbyid);
$ras = $calculationdata['ras'];

if (!$ras) {
    echo $OUTPUT->notification(get_string(
        'page_curriculum_no_ra',
        'local_evalfp'
    ), \core\output\notification::NOTIFY_WARNING);
    local_evalfp_end_course_layout();
    exit;
}

if (!$calculationdata['ces']) {
    echo $OUTPUT->notification(get_string(
        'page_curriculum_no_ce',
        'local_evalfp'
    ), \core\output\notification::NOTIFY_WARNING);
    local_evalfp_end_course_layout();
    exit;
}

$raresults = local_evalfp_calculate_assessment_ra_results($users, $calculationdata);
$ratotals = local_evalfp_calculate_assessment_weighted_totals($users, $ras, $raresults);

echo local_evalfp_render_assessment_summary_table(
    $courseid,
    $selectedevaluationid,
    $users,
    $ras,
    $raresults,
    $ratotals
);

local_evalfp_end_course_layout();
