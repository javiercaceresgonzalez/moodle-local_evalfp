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
 * Course settings helpers for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns EvalFP settings for a course, including defaults when not configured.
 *
 * @param int $courseid Course ID.
 * @return stdClass Course settings record or default settings object.
 */
function local_evalfp_get_course_settings(int $courseid): stdClass {
    global $DB;

    $settings = $DB->get_record('local_evalfp_course_settings', ['courseid' => $courseid], '*', IGNORE_MISSING);
    if ($settings) {
        return $settings;
    }

    return (object)[
        'id' => 0,
        'courseid' => $courseid,
        'showactivitycurriculum' => 0,
        'activitycurriculumexpanded' => 0,
    ];
}

/**
 * Saves EvalFP settings for a course.
 *
 * @param int $courseid Course ID.
 * @param stdClass $data Submitted settings data.
 * @return int Settings record ID.
 */
function local_evalfp_save_course_settings(int $courseid, stdClass $data): int {
    global $DB;

    $now = time();
    $record = local_evalfp_get_course_settings($courseid);
    $record->courseid = $courseid;
    $record->showactivitycurriculum = empty($data->showactivitycurriculum) ? 0 : 1;
    $record->activitycurriculumexpanded = empty($data->activitycurriculumexpanded) ? 0 : 1;
    $record->timemodified = $now;

    if (!empty($record->id)) {
        $DB->update_record('local_evalfp_course_settings', $record);
        return (int)$record->id;
    }

    $record->timecreated = $now;
    return (int)$DB->insert_record('local_evalfp_course_settings', $record);
}
