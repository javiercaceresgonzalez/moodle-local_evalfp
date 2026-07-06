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
 * Course RA and CE management page.
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
$PAGE->set_url(new moodle_url('/local/evalfp/course/curriculum/index.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(get_string('page_curriculum_ra_ce_title', 'local_evalfp'));
$PAGE->navigation->override_active_url(new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid]));
$PAGE->navbar->add(
    get_string('pluginname', 'local_evalfp'),
    new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid])
);
$PAGE->navbar->add(get_string('page_curriculum_ra_ce_title', 'local_evalfp'));

// Load RA and CE records.
$outcomesdata = local_evalfp_get_ras_with_ce($courseid);
$results = $outcomesdata['results'];
$cesbyra = $outcomesdata['cebyra'];

// Page actions.
$pageactions = html_writer::div(
    html_writer::link(
        new moodle_url('/local/evalfp/course/curriculum/ra_ce_import.php', ['courseid' => $courseid]),
        get_string('common_import', 'local_evalfp'),
        ['class' => 'btn btn-outline-secondary mr-2']
    ) . html_writer::link(
        new moodle_url('/local/evalfp/course/curriculum/ra_edit.php', ['courseid' => $courseid]),
        get_string('page_curriculum_add_ra', 'local_evalfp'),
        ['class' => 'btn btn-primary']
    ),
    'd-flex'
);
local_evalfp_start_course_layout($courseid, 'results', $pageactions);

// Render the curriculum list or its empty state.
if (!$results) {
    echo $OUTPUT->notification(get_string('page_curriculum_no_ra', 'local_evalfp'), \core\output\notification::NOTIFY_INFO);
    local_evalfp_end_course_layout();
    exit;
}

echo local_evalfp_render_ra_ce_list($courseid, $results, $cesbyra);
local_evalfp_end_course_layout();
