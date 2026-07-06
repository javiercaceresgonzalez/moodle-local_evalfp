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
 * Assessment data and calculation helpers for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Builds evaluation selector data for assessment pages.
 *
 * The returned structure contains the available course evaluations, selector
 * options and the selected evaluation id. Assessment pages deliberately require
 * an explicit evaluation selection, so an empty selection is preserved.
 *
 * @param int $courseid Course ID.
 * @param int $evaluationid Selected evaluation ID, or 0 when none is selected.
 * @return array<string, mixed> Evaluation selector data.
 */
function local_evalfp_get_assessment_evaluation_options(int $courseid, int $evaluationid): array {
    $evaluations = local_evalfp_get_evaluations($courseid);
    $options = local_evalfp_get_evaluation_select_options($evaluations);
    $byid = [];

    foreach ($evaluations as $evaluation) {
        $byid[(int)$evaluation->id] = $evaluation;
    }

    $selectedid = 0;
    $valid = true;
    if ($evaluationid > 0) {
        if (isset($byid[$evaluationid])) {
            $selectedid = $evaluationid;
        } else {
            $valid = false;
        }
    }

    return [
        'evaluations' => $evaluations,
        'options' => $options,
        'byid' => $byid,
        'selectedid' => $selectedid,
        'valid' => $valid,
    ];
}

/**
 * Returns normalised grade percentages by user and evidence item.
 *
 * Raw Moodle grades are converted to percentages using each grade item's
 * maximum grade. Missing grades are intentionally omitted.
 *
 * @param array<int, stdClass> $users Users indexed by user ID.
 * @param array<int, stdClass> $evidences Evidence grade items indexed by grade item ID.
 * @return array<int, array<int, float>> Percentages indexed by user ID and grade item ID.
 */
function local_evalfp_get_assessment_grade_percentages(array $users, array $evidences): array {
    global $DB;

    $gradesbyusergi = [];
    $evidenceids = array_map('intval', array_keys($evidences));
    if (!$evidenceids || !$users) {
        return $gradesbyusergi;
    }

    [$gisql, $giparams] = $DB->get_in_or_equal($evidenceids, SQL_PARAMS_NAMED, 'gi');
    $userids = array_map('intval', array_keys($users));
    [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'usr');
    $sql = "SELECT gg.id, gg.userid, gg.itemid, gg.finalgrade
              FROM {grade_grades} gg
             WHERE gg.itemid {$gisql}
               AND gg.userid {$usersql}";
    $grades = $DB->get_records_sql($sql, array_merge($giparams, $userparams));

    foreach ($grades as $grade) {
        $gradeitemid = (int)$grade->itemid;
        $evidence = $evidences[$gradeitemid] ?? null;
        if ($grade->finalgrade === null || !$evidence || (float)$evidence->grademax <= 0) {
            continue;
        }
        $gradesbyusergi[(int)$grade->userid][$gradeitemid] = (float)$grade->finalgrade / (float)$evidence->grademax * 100.0;
    }

    return $gradesbyusergi;
}

/**
 * Collects the data required to calculate assessment results.
 *
 * This function centralises the gradebook evidence items, RA weights, CE
 * weights and evaluation assignments used by both assessment reports.
 *
 * @param int $courseid Course ID.
 * @param int $selectedevaluationid Selected evaluation ID.
 * @param array<int, stdClass> $evaluationsbyid Course evaluations indexed by ID.
 * @return array<string, mixed> Calculation data keyed by responsibility.
 */
function local_evalfp_get_assessment_calculation_data(int $courseid, int $selectedevaluationid, array $evaluationsbyid): array {
    global $DB;

    $ras = local_evalfp_get_ras($courseid);
    $evidences = local_evalfp_get_course_evidences($courseid);
    $cebyra = [];
    $evidenceidsbyce = [];

    $sql = "SELECT ce.id, ce.courseraid, ce.code, ce.description, ce.weight
              FROM {local_evalfp_course_ce} ce
              JOIN {local_evalfp_course_ra} ra ON ra.id = ce.courseraid
             WHERE ra.courseid = :courseid
          ORDER BY ra.code ASC, ce.code ASC";
    $ces = $DB->get_records_sql($sql, ['courseid' => $courseid]);
    foreach ($ces as $ce) {
        $cebyra[(int)$ce->courseraid][(int)$ce->id] = $ce;
    }

    foreach (local_evalfp_get_evidence_ce_links($courseid) as $link) {
        $gradeitemid = (int)$link->gradeitemid;
        if (isset($evidences[$gradeitemid])) {
            $evidenceidsbyce[(int)$link->courseceid][$gradeitemid] = $gradeitemid;
        }
    }

    return [
        'ras' => $ras,
        'ces' => $ces,
        'cebyra' => $cebyra,
        'evidences' => $evidences,
        'evidenceidsbyce' => $evidenceidsbyce,
        'evaluationsbyevidence' => local_evalfp_get_evidence_evaluations($courseid),
        'evaluationsbyid' => $evaluationsbyid,
        'selectedevaluationid' => $selectedevaluationid,
    ];
}


/**
 * Calculates learning outcome achievement for each user.
 *
 * A CE is calculated from the average grade of its linked evidence in the
 * selected evaluation period. Each RA is then calculated as the weighted
 * average of its CE with positive weight and available evidence.
 *
 * @param array<int, stdClass> $users Users indexed by user ID.
 * @param array<string, mixed> $calculationdata Calculation data returned by local_evalfp_get_assessment_calculation_data().
 * @return array<int, array<int, float|null>> RA achievement indexed by user ID and RA ID.
 */
function local_evalfp_calculate_assessment_ra_results(array $users, array $calculationdata): array {
    $ras = $calculationdata['ras'];
    $evidences = $calculationdata['evidences'];
    $cebyra = $calculationdata['cebyra'];
    $evidenceidsbyce = $calculationdata['evidenceidsbyce'];
    $evaluationsbyevidence = $calculationdata['evaluationsbyevidence'];
    $evaluationsbyid = $calculationdata['evaluationsbyid'];
    $selectedevaluationid = (int)$calculationdata['selectedevaluationid'];
    $gradesbyusergi = local_evalfp_get_assessment_grade_percentages($users, $evidences);

    $gradepct = static function (int $userid, int $gradeitemid) use ($gradesbyusergi): ?float {
        return $gradesbyusergi[$userid][$gradeitemid] ?? null;
    };

    $evidenceinevaluation = static function (int $gradeitemid) use (
        $selectedevaluationid,
        $evaluationsbyevidence,
        $evaluationsbyid
    ): bool {
        return local_evalfp_is_evidence_in_evaluation(
            $gradeitemid,
            $selectedevaluationid,
            $evaluationsbyevidence,
            $evaluationsbyid
        );
    };

    $raresults = [];
    foreach ($users as $user) {
        $raresults[$user->id] = [];
        foreach ($ras as $ra) {
            $sum = 0.0;
            $totalweight = 0.0;

            foreach ($cebyra[(int)$ra->id] ?? [] as $ce) {
                $ceweight = (float)($ce->weight ?? 0);
                if ($ceweight <= 0) {
                    continue;
                }

                $cesum = 0.0;
                $cecount = 0;
                foreach ($evidenceidsbyce[(int)$ce->id] ?? [] as $gradeitemid) {
                    if (!$evidenceinevaluation((int)$gradeitemid)) {
                        continue;
                    }
                    $pct = $gradepct((int)$user->id, (int)$gradeitemid);
                    $cesum += $pct ?? 0.0;
                    $cecount++;
                }

                if ($cecount === 0) {
                    continue;
                }

                $sum += ($cesum / $cecount) * $ceweight;
                $totalweight += $ceweight;
            }

            $raresults[$user->id][$ra->code] = $totalweight > 0 ? round($sum / $totalweight, 1) : null;
        }
    }

    return $raresults;
}


/**
 * Calculates weighted evaluation totals for each user.
 *
 * Only RA values with data contribute to the total. The configured RA weights
 * are re-normalised over the available RA set for the selected evaluation.
 *
 * @param array<int, stdClass> $users Users indexed by user ID.
 * @param array<int, stdClass> $ras Learning outcomes with their course weights.
 * @param array<int, array<int, float|null>> $raresults RA achievement indexed by user ID and RA ID.
 * @return array<int, float|null> Weighted totals indexed by user ID.
 */
function local_evalfp_calculate_assessment_weighted_totals(array $users, array $ras, array $raresults): array {
    $totals = [];

    foreach ($users as $user) {
        $sum = 0.0;
        $totalweight = 0.0;

        foreach ($ras as $ra) {
            $weight = (float)($ra->weight ?? 0);
            if ($weight <= 0) {
                continue;
            }

            $value = $raresults[$user->id][$ra->code] ?? null;
            if ($value === null) {
                continue;
            }

            $sum += (float)$value * $weight;
            $totalweight += $weight;
        }

        $totals[$user->id] = $totalweight > 0 ? round($sum / $totalweight, 1) : null;
    }

    return $totals;
}


/**
 * Returns active enrolled course users with the learner role archetype.
 *
 * Assessment reports must list learners only. The role archetype keeps the
 * selection aligned with Moodle role configuration while the enrolment check
 * removes suspended or no-longer-active users.
 *
 * @param context_course $context Course context.
 * @return array<int, stdClass> Active users indexed by user ID.
 */
function local_evalfp_get_assessment_users(context_course $context): array {
    $learnerroles = get_archetype_roles('student');
    if (!$learnerroles) {
        return [];
    }

    $roleids = array_map('intval', array_keys($learnerroles));
    $fields = 'ra.id, u.id AS userid, u.firstname, u.lastname, u.alternatename, ' .
        'u.middlename, u.lastnamephonetic, u.firstnamephonetic, u.email, u.picture, u.imagealt';
    $records = get_role_users($roleids, $context, false, $fields, 'u.lastname, u.firstname');

    $users = [];
    foreach ($records as $record) {
        $user = clone($record);
        $user->id = (int)$record->userid;
        unset($user->userid);

        if (!is_enrolled($context, $user, '', true)) {
            continue;
        }

        $users[$user->id] = $user;
    }

    uasort($users, static function (stdClass $a, stdClass $b): int {
        $lastnamecmp = strnatcasecmp((string)$a->lastname, (string)$b->lastname);
        if ($lastnamecmp !== 0) {
            return $lastnamecmp;
        }

        $firstnamecmp = strnatcasecmp((string)$a->firstname, (string)$b->firstname);
        if ($firstnamecmp !== 0) {
            return $firstnamecmp;
        }

        return (int)$a->id <=> (int)$b->id;
    });

    return $users;
}

/**
 * Returns an enrolled user for the individual assessment report.
 *
 * @param context_course $context Course context.
 * @param int $userid User ID.
 * @return stdClass
 * @throws moodle_exception If the user is not enrolled in the course.
 */
function local_evalfp_get_assessment_report_user(context_course $context, int $userid): stdClass {
    $users = local_evalfp_get_assessment_users($context);
    if (!isset($users[$userid])) {
        throw new moodle_exception('invaliduser');
    }

    return $users[$userid];
}

/**
 * Builds the view model for the individual assessment report.
 *
 * The view model contains the selected user, selected evaluation, chart data,
 * RA cards and the weighted evaluation total used by the renderer.
 *
 * @param int $courseid Course ID.
 * @param context_course $context Course context.
 * @param int $userid User ID.
 * @param int $evaluationid Selected evaluation ID.
 * @return array<string, mixed> Individual report view model.
 */
function local_evalfp_build_individual_assessment_report_vm(
    int $courseid,
    context_course $context,
    int $userid,
    int $evaluationid
): array {
    global $DB;

    $evaluations = $DB->get_records(
        'local_evalfp_course_evaluation',
        ['courseid' => $courseid],
        local_evalfp_get_evaluation_order_sql(),
        'id, code, description, type, startdate, enddate'
    );
    $evaluationsbyid = [];
    $evaloptions = local_evalfp_get_evaluation_select_options($evaluations);
    foreach ($evaluations as $evaluation) {
        $evaluationsbyid[(int)$evaluation->id] = $evaluation;
    }

    $selectedevaluationid = 0;
    $validevaluation = true;
    if ($evaluationid > 0) {
        if (isset($evaluationsbyid[$evaluationid])) {
            $selectedevaluationid = $evaluationid;
        } else {
            $validevaluation = false;
        }
    }

    $users = local_evalfp_get_assessment_users($context);
    $useroptions = [];
    foreach ($users as $user) {
        $useroptions[$user->id] = fullname($user);
    }

    $vm = [
        'evaloptions' => $evaloptions,
        'evaluations' => $evaluations,
        'users' => $useroptions,
        'ras' => [],
        'evaluationid' => $selectedevaluationid,
        'evaluation' => $selectedevaluationid > 0 ? $evaluationsbyid[$selectedevaluationid] : null,
        'evaluationlabel' => $selectedevaluationid > 0 ? $evaloptions[$selectedevaluationid] : '',
        'hasselection' => $selectedevaluationid > 0,
        'validevaluation' => $validevaluation,
        'hasras' => true,
    ];

    if ($selectedevaluationid <= 0) {
        return $vm;
    }

    $ras = $DB->get_records('local_evalfp_course_ra', ['courseid' => $courseid], 'code ASC');
    if (!$ras) {
        $vm['hasras'] = false;
        return $vm;
    }

    $sql = "SELECT ce.id, ce.courseraid, ce.code, ce.description, ce.weight
              FROM {local_evalfp_course_ce} ce
              JOIN {local_evalfp_course_ra} ra ON ra.id = ce.courseraid
             WHERE ra.courseid = :courseid
          ORDER BY ra.code ASC, ce.code ASC";
    $ces = $DB->get_records_sql($sql, ['courseid' => $courseid]);
    $cebyra = [];
    $cedata = [];
    foreach ($ces as $ce) {
        $cebyra[(int)$ce->courseraid][(int)$ce->id] = $ce;
        $cedata[(int)$ce->id] = $ce;
    }

    $evidences = local_evalfp_get_course_evidences($courseid);
    $evaluationsbyevidence = local_evalfp_get_evidence_evaluations($courseid);
    $evidenceinevaluation = static function (int $gradeitemid) use (
        $selectedevaluationid,
        $evaluationsbyevidence,
        $evaluationsbyid
    ): bool {
        return local_evalfp_is_evidence_in_evaluation(
            $gradeitemid,
            $selectedevaluationid,
            $evaluationsbyevidence,
            $evaluationsbyid
        );
    };

    $evidenceidsbyce = [];
    foreach (local_evalfp_get_evidence_ce_links($courseid) as $link) {
        $gradeitemid = (int)$link->gradeitemid;
        $ceid = (int)$link->courseceid;
        if (!isset($evidences[$gradeitemid], $cedata[$ceid])) {
            continue;
        }
        $evidenceidsbyce[$ceid][$gradeitemid] = $gradeitemid;
    }

    $gradesbygi = [];
    $evidenceids = array_map('intval', array_keys($evidences));
    if ($evidenceids) {
        [$in, $params] = $DB->get_in_or_equal($evidenceids, SQL_PARAMS_NAMED, 'gi');
        $params['userid'] = $userid;
        $sql = "SELECT itemid, finalgrade
                  FROM {grade_grades}
                 WHERE userid = :userid
                   AND itemid {$in}";
        $grades = $DB->get_records_sql($sql, $params);
        foreach ($grades as $grade) {
            $gradesbygi[(int)$grade->itemid] = $grade->finalgrade;
        }
    }

    $gradepct = static function (int $gradeitemid) use ($gradesbygi, $evidences): ?float {
        $final = $gradesbygi[$gradeitemid] ?? null;
        $evidence = $evidences[$gradeitemid] ?? null;
        if ($final === null || !$evidence || (float)$evidence->grademax <= 0) {
            return null;
        }
        return (float)$final / (float)$evidence->grademax * 100.0;
    };

    $raresults = [$userid => []];

    foreach ($ras as $ra) {
        $raid = (int)$ra->id;
        $raentry = [
            'id' => $raid,
            'code' => s($ra->code),
            'description' => s($ra->description),
            'ces' => [],
            'empty' => true,
            'media' => null,
        ];

        $rasum = 0.0;
        $ratotalweight = 0.0;
        foreach ($cebyra[$raid] ?? [] as $ce) {
            $ceweight = (float)($ce->weight ?? 0);
            if ($ceweight <= 0) {
                continue;
            }

            $ceevidences = [];
            $cesum = 0.0;
            $cecount = 0;
            foreach ($evidences as $gradeitemid => $evidence) {
                $gradeitemid = (int)$gradeitemid;
                if (empty($evidenceidsbyce[(int)$ce->id][$gradeitemid]) || !$evidenceinevaluation($gradeitemid)) {
                    continue;
                }

                $pct = $gradepct($gradeitemid);
                $rawgrade = $gradesbygi[$gradeitemid] ?? null;
                $grademax = !empty($evidence->grademax) ? (float)$evidence->grademax : null;
                $ceevidences[] = [
                    'iconhtml' => $evidence->iconhtml,
                    'linkhtml' => local_evalfp_format_evidence_link($evidence),
                    'rawgradehtml' => local_evalfp_format_raw_grade($rawgrade, $grademax, $pct),
                ];

                $cesum += $pct ?? 0.0;
                $cecount++;
            }

            $cemedia = $ceevidences ? round($cesum / $cecount, 1) : null;
            $raentry['ces'][] = [
                'id' => (int)$ce->id,
                'code' => local_evalfp_format_ce_code($ra->code, $ce->code),
                'description' => format_string($ce->description),
                'weight' => $ceweight,
                'media' => $cemedia,
                'evidences' => $ceevidences,
            ];

            if ($cemedia !== null) {
                $rasum += $cemedia * $ceweight;
                $ratotalweight += $ceweight;
            }
        }

        if ($ratotalweight > 0) {
            $raentry['media'] = round($rasum / $ratotalweight, 1);
        }

        $raentry['empty'] = empty($raentry['ces']);
        $raresults[$userid][$ra->code] = $raentry['media'];
        $vm['ras'][] = $raentry;
    }

    $ratotals = local_evalfp_calculate_assessment_weighted_totals([$userid => (object)['id' => $userid]], $ras, $raresults);
    $vm['total'] = $ratotals[$userid] ?? null;

    return $vm;
}
