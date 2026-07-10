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
 * Standard Moodle callbacks for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/locallib.php');

/**
 * Extends the course navigation with the EvalFP entry point.
 *
 * The node is shown only in real courses and only to users allowed to use the
 * plugin in the course context.
 *
 * @param navigation_node $navigation Course navigation node.
 * @param stdClass $course Course record.
 * @param context_course $context Course context.
 * @return void
 */
function local_evalfp_extend_navigation_course($navigation, $course, $context) {
    if ((int)$course->id === SITEID) {
        return;
    }

    if (!has_capability('local/evalfp:useincourse', $context)) {
        return;
    }

    $url = new moodle_url('/local/evalfp/course/index.php', ['courseid' => $course->id]);

    $navigation->add(
        get_string('pluginname', 'local_evalfp'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_evalfp',
        new pix_icon('i/settings', '')
    );
}

/**
 * Adds EvalFP curriculum controls to the standard course-module form.
 *
 * @param moodleform_mod $formwrapper Module form wrapper.
 * @param MoodleQuickForm $mform Module quick form.
 * @return void
 */
function local_evalfp_coursemodule_standard_elements($formwrapper, MoodleQuickForm $mform) {
    global $COURSE;

    unset($formwrapper);

    local_evalfp_add_coursemodule_curriculum_elements((int)$COURSE->id, $mform);
}

/**
 * Saves EvalFP curriculum links after a course module is edited.
 *
 * @param stdClass $data Submitted course-module data.
 * @param stdClass $course Course record.
 * @return stdClass Submitted data, unchanged for Moodle's post-action chain.
 */
function local_evalfp_coursemodule_edit_post_actions($data, $course) {
    if (empty($data->coursemodule)) {
        return $data;
    }

    local_evalfp_save_coursemodule_evaluation((int)$course->id, (int)$data->coursemodule);
    local_evalfp_save_coursemodule_curriculum_links((int)$course->id, (int)$data->coursemodule);

    return $data;
}
