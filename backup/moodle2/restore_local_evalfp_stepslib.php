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
 * Restore helpers for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Restores EvalFP records using Moodle backup mappings and duplicate-safe writes.
 */
class restore_local_evalfp_structure {
    /**
     * Restores course settings.
     *
     * @param array $data Backup data.
     * @param int $courseid Target course id.
     * @return int Restored record id.
     */
    public static function restore_setting(array $data, int $courseid): int {
        global $DB;

        $record = (object)$data;
        unset($record->id);
        $record->courseid = $courseid;

        $existing = $DB->get_record('local_evalfp_course_settings', ['courseid' => $courseid]);
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_evalfp_course_settings', $record);
            return (int)$existing->id;
        }

        return (int)$DB->insert_record('local_evalfp_course_settings', $record);
    }

    /**
     * Restores or reuses a course RA record.
     *
     * @param array $data Backup data.
     * @param int $courseid Target course id.
     * @return int Restored record id.
     */
    public static function restore_ra(array $data, int $courseid): int {
        global $DB;

        $record = (object)$data;
        unset($record->id);
        $record->courseid = $courseid;

        $existing = $DB->get_record('local_evalfp_course_ra', [
            'courseid' => $courseid,
            'code' => $record->code,
        ]);
        if ($existing) {
            return (int)$existing->id;
        }

        return (int)$DB->insert_record('local_evalfp_course_ra', $record);
    }

    /**
     * Restores or reuses a course CE record.
     *
     * @param array $data Backup data.
     * @param int $courseraid Target course RA id.
     * @return int Restored record id.
     */
    public static function restore_ce(array $data, int $courseraid): int {
        global $DB;

        $record = (object)$data;
        unset($record->id);
        $record->courseraid = $courseraid;

        $existing = $DB->get_record('local_evalfp_course_ce', [
            'courseraid' => $courseraid,
            'code' => $record->code,
        ]);
        if ($existing) {
            return (int)$existing->id;
        }

        return (int)$DB->insert_record('local_evalfp_course_ce', $record);
    }

    /**
     * Restores or reuses a course evaluation record.
     *
     * @param array $data Backup data.
     * @param int $courseid Target course id.
     * @return int Restored record id.
     */
    public static function restore_evaluation(array $data, int $courseid): int {
        global $DB;

        $record = (object)$data;
        unset($record->id);
        $record->courseid = $courseid;

        $existing = $DB->get_record('local_evalfp_course_evaluation', [
            'courseid' => $courseid,
            'code' => $record->code,
        ]);
        if ($existing) {
            return (int)$existing->id;
        }

        return (int)$DB->insert_record('local_evalfp_course_evaluation', $record);
    }

    /**
     * Restores or reuses a CE evidence link.
     *
     * @param array $data Backup data.
     * @param int $gradeitemid Target grade item id.
     * @param int $courseceid Target course CE id.
     * @return int Restored record id.
     */
    public static function restore_evidence_ce(array $data, int $gradeitemid, int $courseceid): int {
        global $DB;

        $record = (object)$data;
        unset($record->id);
        unset($record->racode);
        unset($record->cecode);
        $record->gradeitemid = $gradeitemid;
        $record->courseceid = $courseceid;

        $existing = $DB->get_record('local_evalfp_course_evidence_ce', [
            'gradeitemid' => $gradeitemid,
            'courseceid' => $courseceid,
        ]);
        if ($existing) {
            return (int)$existing->id;
        }

        return (int)$DB->insert_record('local_evalfp_course_evidence_ce', $record);
    }

    /**
     * Restores or updates an evaluation evidence link.
     *
     * @param array $data Backup data.
     * @param int $gradeitemid Target grade item id.
     * @param int $courseevaluationid Target course evaluation id.
     * @return int Restored record id.
     */
    public static function restore_evidence_evaluation(array $data, int $gradeitemid, int $courseevaluationid): int {
        global $DB;

        $record = (object)$data;
        unset($record->id);
        unset($record->evaluationcode);
        $record->gradeitemid = $gradeitemid;
        $record->courseevaluationid = $courseevaluationid;

        $existing = $DB->get_record('local_evalfp_course_evidence_evaluation', ['gradeitemid' => $gradeitemid]);
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_evalfp_course_evidence_evaluation', $record);
            return (int)$existing->id;
        }

        return (int)$DB->insert_record('local_evalfp_course_evidence_evaluation', $record);
    }
}
