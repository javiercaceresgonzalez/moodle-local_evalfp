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
 * Course CE edit page.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/local/evalfp/lib.php');
require_once($CFG->libdir . '/formslib.php');

// Page parameters.
$courseraid = required_param('courseraid', PARAM_INT);
$courseceid = optional_param('courseceid', 0, PARAM_INT);

$ra = local_evalfp_get_ra_by_id($courseraid);
$courseid = $ra->courseid;
// Course access.
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/evalfp:useincourse', $context);

$displayra = local_evalfp_format_ra_code($ra);

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/evalfp/course/curriculum/ce_edit.php', [
    'courseraid' => $courseraid,
    'courseceid' => $courseceid,
]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title($courseceid ? get_string('page_ce_edit_title', 'local_evalfp') : get_string('page_ce_add_title', 'local_evalfp'));
$PAGE->navigation->override_active_url(new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid]));
$PAGE->navbar->add(
    get_string('pluginname', 'local_evalfp'),
    new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid])
);
$PAGE->navbar->add(
    get_string('page_curriculum_ra_ce_title', 'local_evalfp'),
    new moodle_url('/local/evalfp/course/curriculum/index.php', ['courseid' => $courseid])
);
$PAGE->navbar->add($displayra, new moodle_url('/local/evalfp/course/curriculum/index.php', ['courseid' => $courseid]));
$PAGE->navbar->add($courseceid ? get_string('edit') : get_string('common_add', 'local_evalfp'));

// Form setup.
$listurl = new moodle_url('/local/evalfp/course/curriculum/index.php', ['courseid' => $courseid]);

$ce = local_evalfp_prepare_ce_form_data($courseraid, $courseceid);
$formurl = new moodle_url('/local/evalfp/course/curriculum/ce_edit.php', [
    'courseraid' => $courseraid,
    'courseceid' => $courseceid,
]);

$mform = new \local_evalfp\form\ce($formurl, ['editing' => !empty($courseceid), 'ra' => $ra]);
$mform->set_data($ce);

// Form handling.
if ($mform->is_cancelled()) {
    redirect($listurl);
} else if ($data = $mform->get_data()) {
    local_evalfp_save_ce($courseraid, $data);

    \core\notification::success(get_string('success_changes_saved', 'local_evalfp'));
    redirect($listurl);
}

// Output.
local_evalfp_start_course_layout($courseid, 'results');
echo $OUTPUT->heading($courseceid ? get_string(
    'page_ce_edit_title',
    'local_evalfp'
) : get_string('page_ce_add_title', 'local_evalfp'));
$mform->display();
local_evalfp_end_course_layout();
