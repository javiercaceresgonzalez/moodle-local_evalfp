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
 * Course CE deletion confirmation page.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/local/evalfp/lib.php');

// Page parameters.
$courseraid = required_param('courseraid', PARAM_INT);
$courseceid = required_param('courseceid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

// Record lookup.
$ra = local_evalfp_get_ra_by_id($courseraid);
$courseid = $ra->courseid;
// Course access.
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/evalfp:useincourse', $context);

$ce = local_evalfp_get_ce($courseraid, $courseceid);
$listurl = new moodle_url('/local/evalfp/course/curriculum/index.php', ['courseid' => $courseid]);

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/evalfp/course/curriculum/ce_delete.php', [
    'courseraid' => $courseraid,
    'courseceid' => $courseceid,
]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(get_string('page_ce_delete_title', 'local_evalfp'));

// Confirmation handling.
if ($confirm) {
    require_sesskey();
    local_evalfp_delete_ce($courseceid);

    redirect($listurl, get_string('success_ce_deleted', 'local_evalfp'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Output.
local_evalfp_start_course_layout($courseid, 'results');

$displayname = trim(format_string($ce->code) . ' - ' . format_string($ce->description));
$message = get_string('page_ce_delete_confirm', 'local_evalfp', $displayname);
$yesurl = new moodle_url('/local/evalfp/course/curriculum/ce_delete.php', [
    'courseraid' => $courseraid,
    'courseceid' => $courseceid,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);

echo $OUTPUT->confirm($message, $yesurl, $listurl);

local_evalfp_end_course_layout();
