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
 * Course RA edit page.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/local/evalfp/lib.php');
require_once($CFG->libdir . '/formslib.php');

// Page parameters.
$courseid = required_param('courseid', PARAM_INT);
$courseraid = optional_param('courseraid', 0, PARAM_INT);
// Course access.
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/evalfp:useincourse', $context);

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/evalfp/course/curriculum/ra_edit.php', [
    'courseid' => $courseid,
    'courseraid' => $courseraid,
]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(
    $courseraid ? get_string('page_ra_edit_title', 'local_evalfp') : get_string('page_ra_add_title', 'local_evalfp')
);
$PAGE->navigation->override_active_url(new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid]));
$PAGE->navbar->add(
    get_string('pluginname', 'local_evalfp'),
    new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid])
);
$PAGE->navbar->add(
    get_string('page_curriculum_ra_ce_title', 'local_evalfp'),
    new moodle_url('/local/evalfp/course/curriculum/index.php', ['courseid' => $courseid])
);
$PAGE->navbar->add($courseraid ? get_string('edit') : get_string('common_add', 'local_evalfp'));

// Form setup.
$listurl = new moodle_url('/local/evalfp/course/curriculum/index.php', ['courseid' => $courseid]);

$ra = local_evalfp_prepare_ra_form_data($courseid, $courseraid);
$formurl = new moodle_url('/local/evalfp/course/curriculum/ra_edit.php', [
    'courseid' => $courseid,
    'courseraid' => $courseraid,
]);

$mform = new \local_evalfp\form\ra($formurl, ['editing' => !empty($courseraid)]);
$mform->set_data($ra);

// Form handling.
if ($mform->is_cancelled()) {
    redirect($listurl);
} else if ($data = $mform->get_data()) {
    local_evalfp_save_ra($courseid, $data);

    \core\notification::success(get_string('success_changes_saved', 'local_evalfp'));
    redirect($listurl);
}

// Output.
local_evalfp_start_course_layout($courseid, 'results');
echo $OUTPUT->heading(
    $courseraid ? get_string('page_ra_edit_title', 'local_evalfp') : get_string('page_ra_add_title', 'local_evalfp')
);
$mform->display();
local_evalfp_end_course_layout();
