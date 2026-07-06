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
 * Individual course assessment report by learning outcome.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/local/evalfp/lib.php');

// Page parameters.
$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$evaluationid = optional_param('evaluationid', 0, PARAM_INT);

// Course access.
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('moodle/grade:viewall', $context);
$user = local_evalfp_get_assessment_report_user($context, $userid);

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/evalfp/course/assessment/user.php', [
    'courseid' => $courseid,
    'userid' => $userid,
    'evaluationid' => $evaluationid,
]));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('page_assessment_individual_title', 'local_evalfp'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(get_string('participants'));
$PAGE->navbar->add(fullname($user));

// Build and render the individual report.
echo local_evalfp_render_individual_assessment_report($courseid, $context, $course, $user, $userid, $evaluationid);
