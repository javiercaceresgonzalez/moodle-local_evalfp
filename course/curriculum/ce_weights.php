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
 * Course assessment criterion weighting page.
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
$PAGE->set_url(new moodle_url('/local/evalfp/course/curriculum/ce_weights.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(get_string('page_ce_weights_title', 'local_evalfp'));
$PAGE->navigation->override_active_url(new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid]));
$PAGE->navbar->add(
    get_string('pluginname', 'local_evalfp'),
    new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid])
);
$PAGE->navbar->add(get_string('page_ce_weights_title', 'local_evalfp'));
$PAGE->requires->js_call_amd('local_evalfp/ce_weights', 'init');

// Load CE weights grouped by learning outcome.
$curriculum = local_evalfp_get_ras_with_ce($courseid);
$ras = $curriculum['results'];
$cesbyra = $curriculum['cebyra'];
$errors = [];

// Save submitted CE weights.
if (optional_param('saveweights', 0, PARAM_BOOL)) {
    require_sesskey();
    $submitted = optional_param_array('weights', [], PARAM_RAW_TRIMMED);
    $parsedweights = local_evalfp_parse_ce_weights($ras, $cesbyra, $submitted);
    $weights = $parsedweights['weights'];
    $errors = $parsedweights['errors'];
    $sums = $parsedweights['sums'];

    if (!$errors) {
        local_evalfp_save_ce_weights($cesbyra, $weights);
        \core\notification::success(get_string('success_changes_saved', 'local_evalfp'));
        foreach ($sums as $sum) {
            if (round($sum, 2) !== 100.0) {
                \core\notification::warning(get_string('warning_ce_weights_sum', 'local_evalfp'));
                break;
            }
        }
        redirect(new moodle_url('/local/evalfp/course/curriculum/ce_weights.php', ['courseid' => $courseid]));
    }

    \core\notification::error(get_string('error_ce_weights_not_saved', 'local_evalfp'));
    $cesbyra = local_evalfp_apply_ce_weight_preview($cesbyra, $weights);
}

// Page actions.
$pageactions = html_writer::tag('button', get_string('common_save_changes', 'local_evalfp'), [
    'class' => 'btn btn-primary',
    'type' => 'submit',
    'form' => 'local-evalfp-ce-weights-form',
    'name' => 'saveweights',
    'value' => '1',
]);
local_evalfp_start_course_layout($courseid, 'ce_weights', $pageactions);

// Render the form or its empty state.
if (!$ras) {
    echo $OUTPUT->notification(get_string('page_curriculum_no_ra', 'local_evalfp'), \core\output\notification::NOTIFY_INFO);
    local_evalfp_end_course_layout();
    exit;
}

echo local_evalfp_render_ce_weights_form($courseid, $ras, $cesbyra, $errors);
local_evalfp_end_course_layout();
