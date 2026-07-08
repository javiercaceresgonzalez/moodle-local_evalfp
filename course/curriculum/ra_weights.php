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
 * Course RA weighting page.
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
$PAGE->set_url(new moodle_url('/local/evalfp/course/curriculum/ra_weights.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(get_string('page_ra_weights_title', 'local_evalfp'));
$PAGE->navigation->override_active_url(new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid]));
$PAGE->navbar->add(
    get_string('pluginname', 'local_evalfp'),
    new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid])
);
$PAGE->navbar->add(get_string('page_ra_weights_title', 'local_evalfp'));
$PAGE->requires->js_call_amd('local_evalfp/ra_weights', 'init');

// Load RA weights.
$ras = local_evalfp_get_ras($courseid);
$errors = [];

// Save submitted weights.
if (optional_param('saveweights', 0, PARAM_BOOL)) {
    require_sesskey();
    $submitted = optional_param_array('weights', [], PARAM_RAW_TRIMMED);
    $parsedweights = local_evalfp_parse_ra_weights($ras, $submitted);
    $weights = $parsedweights['weights'];
    $errors = $parsedweights['errors'];
    $sum = $parsedweights['sum'];

    if (!$errors) {
        local_evalfp_save_ra_weights($ras, $weights);
        \core\notification::success(get_string('success_changes_saved', 'local_evalfp'));
        if (round($sum, 2) !== 100.0) {
            $formattedsum = rtrim(rtrim(sprintf('%.2f', $sum), '0'), '.');
            \core\notification::warning(get_string('warning_ra_weights_sum', 'local_evalfp', $formattedsum));
        }
        redirect(new moodle_url('/local/evalfp/course/curriculum/ra_weights.php', ['courseid' => $courseid]));
    }

    \core\notification::error(get_string('error_ra_weights_not_saved', 'local_evalfp'));
    $ras = local_evalfp_apply_ra_weight_preview($ras, $weights);
}

// Page actions.
$pageactions = html_writer::tag('button', get_string('common_save_changes', 'local_evalfp'), [
    'class' => 'btn btn-primary',
    'type' => 'submit',
    'form' => 'local-evalfp-ra-weights-form',
    'name' => 'saveweights',
    'value' => '1',
]);
local_evalfp_start_course_layout($courseid, 'ra_weights', $pageactions);

// Render the form or its empty state.
if (!$ras) {
    echo $OUTPUT->notification(get_string('page_curriculum_no_ra', 'local_evalfp'), \core\output\notification::NOTIFY_INFO);
    local_evalfp_end_course_layout();
    exit;
}

echo local_evalfp_render_ra_weights_form($courseid, $ras, $errors);
local_evalfp_end_course_layout();
