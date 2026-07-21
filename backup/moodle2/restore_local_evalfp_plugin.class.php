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
 * Restore integration for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/evalfp/backup/moodle2/restore_local_evalfp_stepslib.php');

/**
 * Restores EvalFP data from Moodle course and activity backups.
 */
class restore_local_evalfp_plugin extends restore_local_plugin {
    /** @var array CE evidence links waiting for grade item mappings. */
    protected $pendingevidenceces = [];

    /** @var array Evaluation evidence links waiting for grade item mappings. */
    protected $pendingevidenceevaluations = [];

    /**
     * Defines EvalFP course-level restore paths.
     *
     * @return array Restore paths.
     */
    protected function define_course_plugin_structure(): array {
        return $this->get_course_restore_paths();
    }



    /**
     * Defines EvalFP section-level restore paths.
     *
     * @return array Restore paths.
     */
    protected function define_section_plugin_structure(): array {
        return $this->get_course_restore_paths();
    }

    /**
     * Defines EvalFP module-level restore paths.
     *
     * @return array Restore paths.
     */
    protected function define_module_plugin_structure(): array {
        return $this->get_evidence_restore_paths();
    }

    /**
     * EvalFP stores no encoded HTML links.
     *
     * @return array Decode contents.
     */
    public static function define_decode_contents(): array {
        return [];
    }

    /**
     * Processes restored course settings.
     *
     * @param array $data Backup data.
     */
    public function process_evalfp_setting(array $data): void {
        $oldid = $data['id'];
        $newid = restore_local_evalfp_structure::restore_setting($data, $this->task->get_courseid());

        $this->set_mapping('local_evalfp_course_settings', $oldid, $newid);
    }

    /**
     * Processes a restored RA.
     *
     * @param array $data Backup data.
     */
    public function process_evalfp_ra(array $data): void {
        $oldid = $data['id'];
        $newid = restore_local_evalfp_structure::restore_ra($data, $this->task->get_courseid());

        $this->set_mapping('local_evalfp_course_ra', $oldid, $newid);
    }

    /**
     * Processes a restored CE.
     *
     * @param array $data Backup data.
     */
    public function process_evalfp_ce(array $data): void {
        $oldid = $data['id'];
        $courseraid = $this->get_mappingid('local_evalfp_course_ra', $data['courseraid'], 0);
        if (empty($courseraid)) {
            $this->set_mapping('local_evalfp_course_ce', $oldid, 0);
            return;
        }

        $newid = restore_local_evalfp_structure::restore_ce($data, $courseraid);

        $this->set_mapping('local_evalfp_course_ce', $oldid, $newid);
    }

    /**
     * Processes a restored evaluation period.
     *
     * @param array $data Backup data.
     */
    public function process_evalfp_evaluation(array $data): void {
        $oldid = $data['id'];
        if (!empty($data['startdate'])) {
            $data['startdate'] = $this->apply_date_offset($data['startdate']);
        }
        if (!empty($data['enddate'])) {
            $data['enddate'] = $this->apply_date_offset($data['enddate']);
        }

        $newid = restore_local_evalfp_structure::restore_evaluation($data, $this->task->get_courseid());

        $this->set_mapping('local_evalfp_course_evaluation', $oldid, $newid);
    }

    /**
     * Stores a CE evidence link until grade item mappings are available.
     *
     * @param array $data Backup data.
     */
    public function process_evalfp_evidence_ce(array $data): void {
        $this->pendingevidenceces[] = $data;
    }

    /**
     * Stores an evaluation evidence link until grade item mappings are available.
     *
     * @param array $data Backup data.
     */
    public function process_evalfp_evidence_evaluation(array $data): void {
        $this->pendingevidenceevaluations[] = $data;
    }

    /**
     * Restores pending course evidence links after all grade item mappings have been created.
     */
    protected function after_restore_course(): void {
        $this->restore_pending_evidence_links();
    }

    /**
     * Restores pending module evidence links after activity grade item mappings have been created.
     */
    protected function after_restore_module(): void {
        $this->restore_pending_evidence_links();
    }


    /**
     * Builds course-level EvalFP restore paths.
     *
     * @return array Restore paths.
     */
    protected function get_course_restore_paths(): array {
        return $this->get_evidence_restore_paths([
            new restore_path_element('evalfp_setting', $this->get_pathfor('/evalfp_settings/evalfp_setting')),
            new restore_path_element('evalfp_ra', $this->get_pathfor('/evalfp_ras/evalfp_ra')),
            new restore_path_element('evalfp_ce', $this->get_pathfor('/evalfp_ras/evalfp_ra/evalfp_ces/evalfp_ce')),
            new restore_path_element(
                'evalfp_evaluation',
                $this->get_pathfor('/evalfp_evaluations/evalfp_evaluation')
            ),
        ]);
    }

    /**
     * Builds evidence restore paths for course and module connection points.
     *
     * @param array $paths Initial restore paths.
     * @return array Restore paths.
     */
    protected function get_evidence_restore_paths(array $paths = []): array {
        $paths[] = new restore_path_element(
            'evalfp_evidence_ce',
            $this->get_pathfor('/evalfp_evidence_ces/evalfp_evidence_ce')
        );
        $paths[] = new restore_path_element(
            'evalfp_evidence_evaluation',
            $this->get_pathfor('/evalfp_evidence_evaluations/evalfp_evidence_evaluation')
        );

        return $paths;
    }

    /**
     * Restores all pending evidence links.
     */
    protected function restore_pending_evidence_links(): void {
        foreach ($this->pendingevidenceces as $data) {
            $this->restore_pending_evidence_ce($data);
        }

        foreach ($this->pendingevidenceevaluations as $data) {
            $this->restore_pending_evidence_evaluation($data);
        }
    }

    /**
     * Restores one pending CE evidence link.
     *
     * @param array $data Backup data.
     */
    protected function restore_pending_evidence_ce(array $data): void {
        $oldid = $data['id'];
        $gradeitemid = $this->get_mappingid('grade_item', $data['gradeitemid'], 0);
        $courseceid = $this->get_course_ce_mappingid($data);
        if (empty($gradeitemid) || empty($courseceid)) {
            $this->set_mapping('local_evalfp_course_evidence_ce', $oldid, 0);
            return;
        }

        $newid = restore_local_evalfp_structure::restore_evidence_ce($data, $gradeitemid, $courseceid);

        $this->set_mapping('local_evalfp_course_evidence_ce', $oldid, $newid);
    }

    /**
     * Restores one pending evaluation evidence link.
     *
     * @param array $data Backup data.
     */
    protected function restore_pending_evidence_evaluation(array $data): void {
        $oldid = $data['id'];
        $gradeitemid = $this->get_mappingid('grade_item', $data['gradeitemid'], 0);
        $courseevaluationid = $this->get_course_evaluation_mappingid($data);
        if (empty($gradeitemid) || empty($courseevaluationid)) {
            $this->set_mapping('local_evalfp_course_evidence_evaluation', $oldid, 0);
            return;
        }

        $newid = restore_local_evalfp_structure::restore_evidence_evaluation($data, $gradeitemid, $courseevaluationid);

        $this->set_mapping('local_evalfp_course_evidence_evaluation', $oldid, $newid);
    }

    /**
     * Gets the target CE id, falling back to matching RA and CE codes in the target course.
     *
     * @param array $data Backup data.
     * @return int Target CE id, or 0 if not available.
     */
    protected function get_course_ce_mappingid(array $data): int {
        global $DB;

        $oldid = (int)$data['courseceid'];
        $mappedid = $this->get_mappingid('local_evalfp_course_ce', $oldid, 0);
        if (!empty($mappedid)) {
            return (int)$mappedid;
        }

        $sql = 'SELECT ce.id
                  FROM {local_evalfp_course_ce} ce
                  JOIN {local_evalfp_course_ra} ra ON ra.id = ce.courseraid
                 WHERE ce.id = :ceid AND ra.courseid = :courseid';
        $params = [
            'ceid' => $oldid,
            'courseid' => $this->task->get_courseid(),
        ];
        if ($DB->record_exists_sql($sql, $params)) {
            return $oldid;
        }

        if (empty($data['racode']) || empty($data['cecode'])) {
            return 0;
        }

        $sql = 'SELECT ce.id
                  FROM {local_evalfp_course_ce} ce
                  JOIN {local_evalfp_course_ra} ra ON ra.id = ce.courseraid
                 WHERE ra.courseid = :courseid
                   AND ra.code = :racode
                   AND ce.code = :cecode';
        $params = [
            'courseid' => $this->task->get_courseid(),
            'racode' => $data['racode'],
            'cecode' => $data['cecode'],
        ];

        return (int)($DB->get_field_sql($sql, $params) ?: 0);
    }

    /**
     * Gets the target evaluation id, falling back to matching evaluation code in the target course.
     *
     * @param array $data Backup data.
     * @return int Target evaluation id, or 0 if not available.
     */
    protected function get_course_evaluation_mappingid(array $data): int {
        global $DB;

        $oldid = (int)$data['courseevaluationid'];
        $mappedid = $this->get_mappingid('local_evalfp_course_evaluation', $oldid, 0);
        if (!empty($mappedid)) {
            return (int)$mappedid;
        }

        $exists = $DB->record_exists('local_evalfp_course_evaluation', [
            'id' => $oldid,
            'courseid' => $this->task->get_courseid(),
        ]);
        if ($exists) {
            return $oldid;
        }

        if (empty($data['evaluationcode'])) {
            return 0;
        }

        return (int)($DB->get_field('local_evalfp_course_evaluation', 'id', [
            'courseid' => $this->task->get_courseid(),
            'code' => $data['evaluationcode'],
        ]) ?: 0);
    }
}
