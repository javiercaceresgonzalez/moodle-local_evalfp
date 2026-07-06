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
 * EvalFP course index page.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->dirroot . '/local/evalfp/lib.php');

// Page parameters.
$courseid = required_param('courseid', PARAM_INT);
// Course access.
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/evalfp:useincourse', $context);

$PAGE->set_url(new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid]));
// Page setup.
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('pluginname', 'local_evalfp'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navigation->override_active_url(new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid]));

// Output.
echo $OUTPUT->header();
echo html_writer::start_tag('main', ['class' => 'container-fluid mt-4']);
echo html_writer::tag('h2', get_string('pluginname', 'local_evalfp'), ['class' => 'mb-4']);

// Render grouped plugin links.
foreach (local_evalfp_get_course_menu_items($courseid) as $group) {
    // Add a horizontal rule between groups, except after the last group.
    if ($group !== end($group)) {
        echo html_writer::empty_tag('hr');
    }
    // Render the group heading and items.
    echo html_writer::start_div('row');
    echo html_writer::tag('h4', $group['heading'], ['class' => 'col col-sm-3']);
    echo html_writer::start_div('col');
    // Render the items as an unstyled list.
    echo html_writer::start_tag('ul', ['class' => 'list-unstyled']);
    foreach ($group['items'] as $item) {
        echo html_writer::start_tag('li');
        echo html_writer::link($item['url'], $item['label']);
        echo html_writer::end_tag('li');
    }
    echo html_writer::end_tag('ul');
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_tag('main');
echo $OUTPUT->footer();
