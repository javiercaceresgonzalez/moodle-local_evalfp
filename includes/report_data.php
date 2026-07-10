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
 * Report data helpers for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns evaluation criteria with their learning outcome data.
 *
 * @param int $courseid Course ID.
 * @return array<int, stdClass> CE records enriched with RA code and RA ID.
 */
function local_evalfp_get_course_ce_records_with_ra(int $courseid): array {
    global $DB;

    $sql = "
        SELECT cc.id, cr.id AS courseraid, cr.code AS racode, cc.code, cc.description, cc.weight
          FROM {local_evalfp_course_ce} cc
          JOIN {local_evalfp_course_ra} cr ON cr.id = cc.courseraid
         WHERE cr.courseid = :courseid
      ORDER BY cr.code, cc.code
    ";
    return $DB->get_records_sql($sql, ['courseid' => $courseid]);
}

/**
 * Returns report data grouped by learning outcome.
 *
 * @param int $courseid Course ID.
 * @return array<string, mixed> Report data keyed by ras, evidences and byra.
 */
function local_evalfp_get_report_evidences_by_ra(int $courseid): array {
    global $DB;

    $ras = $DB->get_records('local_evalfp_course_ra', ['courseid' => $courseid], 'code ASC');
    $evidences = local_evalfp_get_course_evidences($courseid);
    $ces = local_evalfp_get_course_ce_records_with_ra($courseid);
    $cescourseraids = [];
    foreach ($ces as $cen) {
        $cescourseraids[(int)$cen->id] = (int)$cen->courseraid;
    }

    $byra = [];
    foreach (local_evalfp_get_evidence_ce_links($courseid) as $link) {
        $cenid = (int)$link->courseceid;
        $gradeitemid = (int)$link->gradeitemid;
        if (!isset($cescourseraids[$cenid]) || !isset($evidences[$gradeitemid])) {
            continue;
        }
        $courseraid = $cescourseraids[$cenid];
        $byra[$courseraid][$gradeitemid] = null;
    }

    return [
        'ras' => $ras,
        'evidences' => $evidences,
        'byra' => $byra,
    ];
}

/**
 * Returns report data grouped by assessment criterion.
 *
 * @param int $courseid Course ID.
 * @return array<string, mixed> Report data keyed by ces, evidences and byce.
 */
function local_evalfp_get_report_evidences_by_ce(int $courseid): array {
    $ces = local_evalfp_get_course_ce_records_with_ra($courseid);
    $evidences = local_evalfp_get_course_evidences($courseid);
    $byce = [];

    foreach (local_evalfp_get_evidence_ce_links($courseid) as $link) {
        $gradeitemid = (int)$link->gradeitemid;
        if (isset($evidences[$gradeitemid])) {
            $byce[(int)$link->courseceid][$gradeitemid] = $gradeitemid;
        }
    }

    foreach ($byce as $ceid => $gradeitemids) {
        $orderedgradeitemids = [];
        foreach (array_keys($evidences) as $gradeitemid) {
            if (isset($gradeitemids[$gradeitemid])) {
                $orderedgradeitemids[] = $gradeitemid;
            }
        }
        $byce[$ceid] = $orderedgradeitemids;
    }

    return [
        'ces' => $ces,
        'evidences' => $evidences,
        'byce' => $byce,
    ];
}

/**
 * Returns report data grouped by evaluation period.
 *
 * @param int $courseid Course ID.
 * @return array<string, mixed> Report data keyed by evaluations, evidences and byevaluation.
 */
function local_evalfp_get_report_evidences_by_evaluation(int $courseid): array {
    global $DB;

    $evaluations = $DB->get_records(
        'local_evalfp_course_evaluation',
        ['courseid' => $courseid],
        local_evalfp_get_evaluation_order_sql()
    );
    $evidences = local_evalfp_get_course_evidences($courseid);
    $evaluationsbyevidence = local_evalfp_get_evidence_evaluations($courseid);
    $evaluationsbyid = [];
    $byevaluation = [];

    foreach ($evaluations as $evaluation) {
        $evaluationid = (int)$evaluation->id;
        $evaluationsbyid[$evaluationid] = $evaluation;
        $byevaluation[$evaluationid] = [];
    }

    foreach ($evaluations as $evaluation) {
        $evaluationid = (int)$evaluation->id;
        foreach ($evidences as $gradeitemid => $evidence) {
            if (
                local_evalfp_is_evidence_in_evaluation(
                    (int)$gradeitemid,
                    $evaluationid,
                    $evaluationsbyevidence,
                    $evaluationsbyid
                )
            ) {
                $byevaluation[$evaluationid][(int)$gradeitemid] = $evidence;
            }
        }
    }

    return [
        'evaluations' => $evaluations,
        'byevaluation' => $byevaluation,
    ];
}
