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
 * Course activity settings page.
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

// Course access.
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/evalfp:useincourse', $context);

// Page setup.
$pageurl = new moodle_url('/local/evalfp/course/settings/activity.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(get_string('page_activity_settings_title', 'local_evalfp'));
$PAGE->navigation->override_active_url(new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid]));
$PAGE->navbar->add(
    get_string('pluginname', 'local_evalfp'),
    new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid])
);
$PAGE->navbar->add(get_string('page_activity_settings_title', 'local_evalfp'));

// Form setup.
$mform = new \local_evalfp\form\activity_settings($pageurl, ['courseid' => $courseid]);
$listurl = new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid]);

// Form handling.
if ($mform->is_cancelled()) {
    redirect($listurl);
}

if ($data = $mform->get_data()) {
    local_evalfp_save_course_settings($courseid, $data);
    \core\notification::success(get_string('success_changes_saved', 'local_evalfp'));
    redirect($pageurl);
}

$mform->set_data(local_evalfp_get_course_settings($courseid));

// Output.
local_evalfp_start_course_layout($courseid, 'activity_settings');
$mform->display();
local_evalfp_end_course_layout();
