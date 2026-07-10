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
 * Curriculum evidence matrix helpers for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Builds data for the curriculum evidence matrix.
 *
 * @param int $courseid Course ID.
 * @return array<string, mixed> Matrix data keyed by RA groups, evidences, evaluations and existing links.
 */
function local_evalfp_get_curriculum_evidence_matrix_data(int $courseid): array {
    global $DB;

    $sql = "
        SELECT c.id, r.id AS raid, r.code AS racode, r.description AS radescription,
               c.code AS cecode, c.description AS cedescription
          FROM {local_evalfp_course_ce} c
          JOIN {local_evalfp_course_ra} r ON r.id = c.courseraid
         WHERE r.courseid = :courseid
      ORDER BY r.code ASC, c.code ASC
    ";
    $ces = $DB->get_records_sql($sql, ['courseid' => $courseid]);
    $ras = $DB->get_records('local_evalfp_course_ra', ['courseid' => $courseid], 'code ASC');
    $periods = $DB->get_records_select(
        'local_evalfp_course_evaluation',
        'courseid = :courseid AND type > 0',
        ['courseid' => $courseid],
        local_evalfp_get_evaluation_order_sql()
    );
    $evidences = local_evalfp_get_course_evidences($courseid);

    $ragroups = [];
    foreach ($ras as $ra) {
        $ragroups[(int)$ra->id] = [
            'id' => (int)$ra->id,
            'code' => $ra->code,
            'description' => $ra->description,
            'ces' => [],
        ];
    }
    foreach ($ces as $ce) {
        $raid = (int)$ce->raid;
        if (isset($ragroups[$raid])) {
            $ragroups[$raid]['ces'][] = $ce;
        }
    }

    $existingcelinks = [];
    foreach (local_evalfp_get_evidence_ce_links($courseid) as $celink) {
        $existingcelinks[(int)$celink->gradeitemid . '-' . (int)$celink->courseceid] = $celink;
    }

    return [
        'ces' => $ces,
        'ragroups' => $ragroups,
        'evaluations' => $periods,
        'evidences' => $evidences,
        'existingcelinks' => $existingcelinks,
        'existingevaluations' => local_evalfp_get_evidence_evaluations($courseid),
    ];
}

/**
 * Saves the curriculum evidence matrix.
 *
 * @param int $courseid Course ID.
 * @param array $evidencematrix Matrix data returned by local_evalfp_get_curriculum_evidence_matrix_data().
 * @return void
 */
function local_evalfp_save_curriculum_evidence_matrix(int $courseid, array $evidencematrix): void {
    global $DB;

    require_sesskey();
    $now = time();

    $ces = $evidencematrix['ces'];
    $evaluations = $evidencematrix['evaluations'];
    $evidences = $evidencematrix['evidences'];
    $existingcelinks = $evidencematrix['existingcelinks'];
    $existingevaluations = $evidencematrix['existingevaluations'];

    $validceids = array_flip(array_map('intval', array_keys($ces)));
    $validgradeitemids = array_flip(array_map('intval', array_keys($evidences)));
    $validevaluationids = array_flip(array_map('intval', array_keys($evaluations)));

    $submitted = optional_param_array('evalfp_links', [], PARAM_ALPHANUMEXT);
    $newcelinks = [];
    foreach ($submitted as $pair) {
        if (!preg_match('/^(\d+)-(\d+)$/', $pair, $matches)) {
            continue;
        }
        $gradeitemid = (int)$matches[1];
        $ceid = (int)$matches[2];
        if (!isset($validgradeitemids[$gradeitemid]) || !isset($validceids[$ceid])) {
            continue;
        }
        $newcelinks[$gradeitemid . '-' . $ceid] = [$gradeitemid, $ceid];
    }

    foreach (array_diff_key($existingcelinks, $newcelinks) as $celink) {
        $DB->delete_records('local_evalfp_course_evidence_ce', ['id' => $celink->id]);
    }
    foreach (array_diff_key($newcelinks, $existingcelinks) as [$gradeitemid, $ceid]) {
        $DB->insert_record('local_evalfp_course_evidence_ce', (object)[
            'gradeitemid' => $gradeitemid,
            'courseceid' => $ceid,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    foreach ($evidences as $evidence) {
        $gradeitemid = (int)$evidence->id;
        $evaluationid = optional_param('evalfp_evaluation_' . $gradeitemid, 0, PARAM_INT);
        $currentevaluationid = (int)($existingevaluations[$gradeitemid] ?? 0);

        if ($evaluationid > 0 && isset($validevaluationids[$evaluationid])) {
            if ($currentevaluationid > 0) {
                if ($currentevaluationid !== $evaluationid) {
                    $record = $DB->get_record(
                        'local_evalfp_course_evidence_evaluation',
                        ['gradeitemid' => $gradeitemid],
                        '*',
                        IGNORE_MISSING
                    );
                    if ($record) {
                        $record->courseevaluationid = $evaluationid;
                        $record->timemodified = $now;
                        $DB->update_record('local_evalfp_course_evidence_evaluation', $record);
                    }
                }
            } else {
                $DB->insert_record('local_evalfp_course_evidence_evaluation', (object)[
                    'gradeitemid' => $gradeitemid,
                    'courseevaluationid' => $evaluationid,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ]);
            }
        } else if ($currentevaluationid > 0) {
            $DB->delete_records('local_evalfp_course_evidence_evaluation', ['gradeitemid' => $gradeitemid]);
        }
    }
}

/**
 * Renders the curriculum evidence matrix.
 *
 * @param int $courseid Course ID.
 * @param array $evidencematrix Matrix data returned by local_evalfp_get_curriculum_evidence_matrix_data().
 * @return string Rendered matrix HTML.
 */
function local_evalfp_render_curriculum_evidence_matrix(int $courseid, array $evidencematrix): string {
    $ragroups = $evidencematrix['ragroups'];
    $evaluations = $evidencematrix['evaluations'];
    $evidences = $evidencematrix['evidences'];
    $existingcelinks = $evidencematrix['existingcelinks'];
    $existingevaluations = $evidencematrix['existingevaluations'];

    ob_start();

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/local/evalfp/course/curriculum/evidences.php', ['courseid' => $courseid]),
        'id' => 'local-evalfp-curriculum-evidences-form',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    echo html_writer::start_div('local-evalfp-matrix-wrap');
    echo html_writer::start_tag('table', ['class' => 'generaltable mb-0 local-evalfp-matrix border']);

    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    $evidenceheaderclasses = [
        'align-middle',
        'border-right',
        'bg-light',
        'local-evalfp-matrix-sticky-left',
        'local-evalfp-matrix-sticky-top-1',
        'local-evalfp-matrix-col-evidence',
        'local-evalfp-matrix-z-top-left',
    ];
    echo html_writer::tag('th', get_string('common_evidence', 'local_evalfp'), [
        'class' => implode(' ', $evidenceheaderclasses),
    ]);

    $evaluationheaderclasses = [
        'align-middle',
        'border-right',
        'text-center',
        'bg-light',
        'local-evalfp-matrix-sticky-top-1',
        'local-evalfp-matrix-col-evaluation',
        'local-evalfp-matrix-z-top',
    ];
    echo html_writer::tag('th', get_string('common_evaluation', 'local_evalfp'), [
        'class' => implode(' ', $evaluationheaderclasses),
        'title' => get_string('common_evaluation_period', 'local_evalfp'),
    ]);

    foreach ($ragroups as $ra) {
        foreach ($ra['ces'] as $index => $ce) {
            $classes = [
                'text-center',
                'bg-light',
                'local-evalfp-matrix-sticky-top-1',
                'local-evalfp-matrix-col-control',
                'local-evalfp-matrix-z-top',
            ];
            if ($index === 0) {
                $classes[] = 'border-left';
            }
            echo html_writer::tag(
                'th',
                html_writer::span(local_evalfp_format_ce_code($ra['code'], $ce->cecode), 'badge badge-secondary', [
                    'title' => format_string($ce->cedescription),
                ]),
                [
                    'class' => implode(' ', $classes),
                    'title' => format_string($ra['description']) . ': ' . format_string($ce->cedescription),
                ]
            );
        }
    }
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($evidences as $evidence) {
        $gradeitemid = (int)$evidence->id;
        $iscategory = $evidence->itemtype === 'category';
        $rowclasses = ['local-evalfp-matrix-row'];
        if ($iscategory) {
            $rowclasses[] = 'local-evalfp-matrix-row-category';
            $rowclasses[] = 'bg-light';
        } else if ($evidence->itemtype === 'manual') {
            $rowclasses[] = 'local-evalfp-matrix-row-manual';
        }
        echo html_writer::start_tag('tr', ['class' => implode(' ', $rowclasses)]);

        $evidenceattributes = [
            'class' => 'text-truncate d-inline-block align-middle local-evalfp-matrix-evidence-link',
            'title' => $evidence->label,
        ];
        $evidencelink = local_evalfp_format_evidence_link($evidence, $evidenceattributes);
        $indentlevel = min(6, max(0, (int)($evidence->indentlevel ?? 0)));
        $indent = html_writer::span('', 'd-inline-block flex-shrink-0 local-evalfp-matrix-indent-' . $indentlevel, [
            'aria-hidden' => 'true',
        ]);
        $evidencecontent = $indent . $evidence->iconhtml . html_writer::div($evidencelink, 'min-w-0');
        $evidencecell = html_writer::div($evidencecontent, 'd-flex align-items-center');
        $evidencecellclasses = [
            'border-right',
            $iscategory ? 'bg-light' : 'bg-white',
            'local-evalfp-matrix-sticky-left',
            'local-evalfp-matrix-col-evidence',
            'local-evalfp-matrix-z-left',
        ];
        $evidencecellclasses[] = $iscategory ? 'font-weight-bold' : 'font-weight-normal';

        echo html_writer::tag('th', $evidencecell, [
            'scope' => 'row',
            'class' => implode(' ', $evidencecellclasses),
        ]);

        $evaluationoptions = local_evalfp_get_evaluation_select_options(
            $evaluations,
            LOCAL_EVALFP_EVALUATION_OPTION_SHORT
        );
        $evaluationselect = html_writer::select(
            $evaluationoptions,
            'evalfp_evaluation_' . $gradeitemid,
            (int)($existingevaluations[$gradeitemid] ?? 0),
            false,
            [
                'class' => 'custom-select custom-select-sm',
                'aria-label' => get_string('common_evaluation_period', 'local_evalfp'),
            ]
        );
        $evaluationcellclasses = ['align-middle', 'text-center', 'border-right'];
        if ($iscategory) {
            $evaluationcellclasses[] = 'bg-light';
        }
        echo html_writer::tag('td', $evaluationselect, [
            'class' => implode(' ', $evaluationcellclasses),
        ]);

        foreach ($ragroups as $ra) {
            foreach ($ra['ces'] as $index => $ce) {
                $ceid = (int)$ce->id;
                $pair = $gradeitemid . '-' . $ceid;
                $checkboxid = 'evalfp-link-' . $gradeitemid . '-' . $ceid;
                $cellclasses = ['text-center', 'align-middle'];
                if ($iscategory) {
                    $cellclasses[] = 'bg-light';
                }
                if ($index === 0) {
                    $cellclasses[] = 'border-left';
                }
                $attributes = [
                    'type' => 'checkbox',
                    'class' => 'form-check-input position-static m-0',
                    'name' => 'evalfp_links[]',
                    'value' => $pair,
                    'id' => $checkboxid,
                    'aria-label' => get_string('form_evidence_ce_checkbox_label', 'local_evalfp', (object)[
                        'evidence' => $evidence->label,
                        'ce' => local_evalfp_format_ce_code($ra['code'], $ce->cecode),
                    ]),
                ];
                if (isset($existingcelinks[$pair])) {
                    $attributes['checked'] = 'checked';
                }
                echo html_writer::tag('td', html_writer::empty_tag('input', $attributes), [
                    'class' => implode(' ', $cellclasses),
                ]);
            }
        }

        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');

    echo html_writer::end_tag('table');
    echo html_writer::end_div();

    echo html_writer::end_tag('form');

    return ob_get_clean();
}
