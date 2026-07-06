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
 * Course evaluation deletion confirmation page.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/local/evalfp/lib.php');

// Page parameters.
$evaluationid = required_param('evaluationid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
// Course access.
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/evalfp:useincourse', $context);

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/evalfp/course/curriculum/evaluation_delete.php', [
    'courseid' => $courseid,
    'evaluationid' => $evaluationid,
]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('page_evaluation_delete_title', 'local_evalfp'));
$PAGE->set_heading($course->fullname);

// Record lookup.
$evaluation = local_evalfp_get_evaluation($courseid, $evaluationid, IGNORE_MISSING);
if (!$evaluation) {
    throw new moodle_exception('error_invalid_record', 'local_evalfp');
}

// Confirmation handling.
if ($confirm) {
    require_sesskey();
    local_evalfp_delete_evaluation($evaluationid);

    redirect(
        new moodle_url('/local/evalfp/course/curriculum/evaluations.php', ['courseid' => $courseid]),
        get_string('success_evaluation_deleted', 'local_evalfp'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Output.
local_evalfp_start_course_layout($courseid, 'evaluations');

$message = get_string('page_evaluation_delete_confirm', 'local_evalfp', format_string($evaluation->description));
$yesurl = new moodle_url('/local/evalfp/course/curriculum/evaluation_delete.php', [
    'courseid' => $courseid,
    'evaluationid' => $evaluationid,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);
$cancelurl = new moodle_url('/local/evalfp/course/curriculum/evaluations.php', ['courseid' => $courseid]);

echo $OUTPUT->confirm($message, $yesurl, $cancelurl);

local_evalfp_end_course_layout();
