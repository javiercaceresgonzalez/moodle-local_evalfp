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
 * Course module curriculum helpers for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns curriculum information linked to a course module.
 *
 * @param int $courseid Course ID.
 * @param int $cmid Course module ID.
 * @return array<int, array<string, mixed>> Linked curriculum data grouped by RA.
 */
function local_evalfp_get_coursemodule_curriculum(int $courseid, int $cmid): array {
    global $DB;

    $gradeitem = local_evalfp_get_gradeitem_for_cmid($courseid, $cmid);
    if (!$gradeitem) {
        return [];
    }

    $sql = "
        SELECT ce.id, ra.id AS raid, ra.code AS racode, ra.description AS radescription,
               ce.code AS cecode, ce.description AS cedescription
          FROM {local_evalfp_course_evidence_ce} ec
          JOIN {local_evalfp_course_ce} ce ON ce.id = ec.courseceid
          JOIN {local_evalfp_course_ra} ra ON ra.id = ce.courseraid
         WHERE ec.gradeitemid = :gradeitemid
           AND ra.courseid = :courseid
      ORDER BY ra.code, ce.code
    ";
    $records = $DB->get_records_sql($sql, [
        'gradeitemid' => $gradeitem->id,
        'courseid' => $courseid,
    ]);

    $groups = [];
    foreach ($records as $record) {
        $raid = (int)$record->raid;
        if (!isset($groups[$raid])) {
            $groups[$raid] = [
                'code' => $record->racode,
                'description' => $record->radescription,
                'ces' => [],
            ];
        }
        $groups[$raid]['ces'][] = $record;
    }

    return $groups;
}

/**
 * Renders the read-only curriculum information block for a course module.
 *
 * @param int $courseid Course ID.
 * @param int $cmid Course module ID.
 * @return string
 */
function local_evalfp_render_coursemodule_curriculum_block(int $courseid, int $cmid, bool $expanded = false): string {
    $groups = local_evalfp_get_coursemodule_curriculum($courseid, $cmid);
    if (!$groups) {
        return '';
    }

    $content = html_writer::tag('summary', get_string('coursemodule_curriculum_title', 'local_evalfp'), [
        'class' => 'font-weight-bold pb-2 mb-2',
    ]);
    $content .= html_writer::start_div('pb-2');

    foreach ($groups as $ra) {
        $ces = '';
        foreach ($ra['ces'] as $ce) {
            $ces .= html_writer::span(local_evalfp_format_ce_code($ra['code'], $ce->cecode), 'badge badge-secondary mr-2 mb-1', [
                'title' => format_string($ce->cedescription),
            ]);
        }

        $content .= html_writer::start_div('mb-3');
        $content .= html_writer::div(
            html_writer::span(local_evalfp_format_ra_label($ra['code']), 'badge badge-primary mr-2') .
                html_writer::span(format_string($ra['description']), 'font-weight-normal'),
            'mb-1'
        );
        $content .= html_writer::div($ces, 'pl-4');
        $content .= html_writer::end_div();
    }

    $content .= html_writer::end_div();

    $attributes = [
        'class' => 'my-2 pt-2',
        'data-local-evalfp-coursemodule-curriculum' => '1',
    ];
    if ($expanded) {
        $attributes['open'] = 'open';
    }

    return html_writer::tag('details', $content, $attributes);
}

/**
 * Returns evaluation period options for a course module selector.
 *
 * @param int $courseid Course ID.
 * @return array<int, string> Select options keyed by course evaluation ID.
 */
function local_evalfp_get_coursemodule_evaluation_options(int $courseid): array {
    global $DB;

    $evaluations = $DB->get_records_select(
        'local_evalfp_course_evaluation',
        'courseid = :courseid AND type > 0',
        ['courseid' => $courseid],
        local_evalfp_get_evaluation_order_sql()
    );

    return local_evalfp_get_evaluation_select_options($evaluations);
}

/**
 * Returns the evaluation period assigned to a course module evidence.
 *
 * @param int $courseid Course ID.
 * @param int $cmid Course module ID.
 * @return int Course evaluation ID, or 0 when none is assigned.
 */
function local_evalfp_get_coursemodule_evaluation_id(int $courseid, int $cmid): int {
    global $DB;

    $gradeitem = local_evalfp_get_gradeitem_for_cmid($courseid, $cmid);
    if (!$gradeitem) {
        return 0;
    }

    return (int)$DB->get_field(
        'local_evalfp_course_evidence_evaluation',
        'courseevaluationid',
        ['gradeitemid' => $gradeitem->id]
    );
}

/**
 * Saves the evaluation period assigned to a course module evidence.
 *
 * @param int $courseid Course ID.
 * @param int $cmid Course module ID.
 * @return void
 */
function local_evalfp_save_coursemodule_evaluation(int $courseid, int $cmid): void {
    global $DB;

    $gradeitem = local_evalfp_get_gradeitem_for_cmid($courseid, $cmid);
    if (!$gradeitem) {
        return;
    }

    $evaluationid = optional_param('evalfp_evaluation_period', 0, PARAM_INT);
    $current = $DB->get_record(
        'local_evalfp_course_evidence_evaluation',
        ['gradeitemid' => $gradeitem->id],
        '*',
        IGNORE_MISSING
    );

    $valid = false;
    if ($evaluationid > 0) {
        $valid = $DB->record_exists_select(
            'local_evalfp_course_evaluation',
            'id = :id AND courseid = :courseid AND type > 0',
            ['id' => $evaluationid, 'courseid' => $courseid]
        );
    }

    if ($evaluationid > 0 && $valid) {
        $now = time();
        if ($current) {
            if ((int)$current->courseevaluationid !== $evaluationid) {
                $current->courseevaluationid = $evaluationid;
                $current->timemodified = $now;
                $DB->update_record('local_evalfp_course_evidence_evaluation', $current);
            }
            return;
        }

        $record = (object)[
            'gradeitemid' => (int)$gradeitem->id,
            'courseevaluationid' => $evaluationid,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $DB->insert_record('local_evalfp_course_evidence_evaluation', $record);
        return;
    }

    if ($current) {
        $DB->delete_records('local_evalfp_course_evidence_evaluation', ['gradeitemid' => $gradeitem->id]);
    }
}

/**
 * Adds EvalFP curriculum fields to the course module edit form.
 *
 * @param int $courseid Course ID.
 * @param MoodleQuickForm $mform Module quick form.
 * @return void
 */
function local_evalfp_add_coursemodule_curriculum_elements(int $courseid, MoodleQuickForm $mform): void {
    global $DB;

    $mform->addElement('header', 'curriculum_header', get_string('pluginname', 'local_evalfp'));

    $cmid = optional_param('update', 0, PARAM_INT);
    $gradeitem = $cmid ? local_evalfp_get_gradeitem_for_cmid($courseid, $cmid) : null;
    $selectedevaluationid = $cmid ? local_evalfp_get_coursemodule_evaluation_id($courseid, $cmid) : 0;
    $evaluationoptions = local_evalfp_get_coursemodule_evaluation_options($courseid);
    if (!array_key_exists($selectedevaluationid, $evaluationoptions)) {
        $selectedevaluationid = 0;
    }

    if (count($evaluationoptions) > 1) {
        $mform->addElement(
            'select',
            'evalfp_evaluation_period',
            get_string('common_evaluation_period', 'local_evalfp'),
            $evaluationoptions
        );
        $mform->setType('evalfp_evaluation_period', PARAM_INT);
        $mform->setDefault('evalfp_evaluation_period', $selectedevaluationid);
    } else {
        $mform->addElement('html', html_writer::div(
            get_string('page_evaluations_none', 'local_evalfp'),
            'alert alert-warning mb-3'
        ));
    }

    $sql = "
        SELECT c.id, r.id AS raid, r.code AS racode, r.description AS radescription,
               c.code AS cecode, c.description AS cedescription
          FROM {local_evalfp_course_ce} c
          JOIN {local_evalfp_course_ra} r ON c.courseraid = r.id
         WHERE r.courseid = :courseid
      ORDER BY r.code, c.code
    ";
    $records = $DB->get_records_sql($sql, ['courseid' => $courseid]);

    $selectedces = [];
    if ($gradeitem) {
        $selected = $DB->get_records('local_evalfp_course_evidence_ce', ['gradeitemid' => $gradeitem->id], '', 'courseceid');
        $selectedces = array_map('intval', array_keys($selected));
    }

    if (!$records) {
        $mform->addElement('html', html_writer::div(
            get_string('page_curriculum_no_ce', 'local_evalfp'),
            'alert alert-warning mb-3'
        ));
        return;
    }

    $groups = [];
    foreach ($records as $ce) {
        $raid = (int)$ce->raid;
        if (!isset($groups[$raid])) {
            $groups[$raid] = [
                'code' => $ce->racode,
                'description' => $ce->radescription,
                'ces' => [],
            ];
        }
        $groups[$raid]['ces'][] = $ce;
    }

    $html = html_writer::start_div('mt-3');

    foreach ($groups as $ra) {
        $html .= html_writer::start_div('form-group row py-3 border-top mb-0');

        $rahtml = html_writer::span(local_evalfp_format_ra_label($ra['code']), 'badge badge-primary mr-2') .
            html_writer::span(format_string($ra['description']));
        $html .= html_writer::div($rahtml, 'col-md-3 col-form-label pt-0');

        $html .= html_writer::start_div('col-md-9');
        foreach ($ra['ces'] as $ce) {
            $ceid = (int)$ce->id;
            $inputid = 'local-evalfp-ce-' . $ceid;
            $checked = in_array($ceid, $selectedces, true);

            $html .= html_writer::start_div('form-check py-1');
            $attributes = [
                'type' => 'checkbox',
                'class' => 'form-check-input',
                'name' => 'evalfp_ce[]',
                'value' => $ceid,
                'id' => $inputid,
            ];
            if ($checked) {
                $attributes['checked'] = 'checked';
            }
            $html .= html_writer::empty_tag('input', $attributes);
            $html .= html_writer::tag(
                'label',
                html_writer::span(local_evalfp_format_ce_code($ra['code'], $ce->cecode), 'badge badge-secondary mr-2') .
                    html_writer::span(format_string($ce->cedescription)),
                ['class' => 'form-check-label', 'for' => $inputid]
            );
            $html .= html_writer::end_div();
        }

        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
    }

    $html .= html_writer::end_div();

    $mform->addElement('html', $html);
}

/**
 * Saves EvalFP curriculum links from a course module edit form.
 *
 * @param int $courseid Course ID.
 * @param int $cmid Course module ID.
 * @return void
 */
function local_evalfp_save_coursemodule_curriculum_links(int $courseid, int $cmid): void {
    global $DB;

    $cmid = (int)$cmid;
    $newselected = optional_param_array('evalfp_ce', [], PARAM_INT);
    $newselected = array_values(array_unique(array_filter(array_map('intval', $newselected))));
    $gradeitem = local_evalfp_get_gradeitem_for_cmid($courseid, (int)$cmid);

    if ($gradeitem) {
        $existing = $DB->get_records_menu(
            'local_evalfp_course_evidence_ce',
            ['gradeitemid' => $gradeitem->id],
            '',
            'courseceid, id'
        );
        $existingids = array_keys($existing);
        $toinsert = array_diff($newselected, $existingids);
        $todelete = array_diff($existingids, $newselected);

        if (!empty($todelete)) {
            [$sqlin, $cesparams] = $DB->get_in_or_equal($todelete, SQL_PARAMS_QM);
            $params = array_merge([$gradeitem->id], $cesparams);
            $DB->delete_records_select('local_evalfp_course_evidence_ce', "gradeitemid = ? AND courseceid $sqlin", $params);
        }

        foreach ($toinsert as $ceid) {
            $record = new stdClass();
            $record->gradeitemid = $gradeitem->id;
            $record->courseceid = $ceid;
            $record->timecreated = time();
            $record->timemodified = time();
            $DB->insert_record('local_evalfp_course_evidence_ce', $record);
        }
    }
}

/**
 * Returns the current course-module curriculum display context.
 *
 * @return array{courseid:int, cmid:int, expanded:bool}|null
 */
function local_evalfp_get_current_coursemodule_curriculum_context(): ?array {
    global $PAGE;

    try {
        $cm = $PAGE->cm;
        $course = $PAGE->course;
    } catch (Exception $exception) {
        return null;
    }

    if (empty($cm->id) || empty($course->id)) {
        return null;
    }

    $path = $PAGE->url->get_path();
    if (!preg_match('#(?:^|/)mod/[^/]+/view\.php$#', $path)) {
        return null;
    }

    // Only the main activity view should display curriculum information.
    // Internal module views such as assignment grading reuse view.php with
    // action parameters, but they are not user-facing activity content.
    if (!empty($PAGE->url->param('action'))) {
        return null;
    }

    if (isset($cm->uservisible) && !$cm->uservisible) {
        return null;
    }

    $settings = local_evalfp_get_course_settings((int)$course->id);
    if (empty($settings->showactivitycurriculum)) {
        return null;
    }

    return [
        'courseid' => (int)$course->id,
        'cmid' => (int)$cm->id,
        'expanded' => !empty($settings->activitycurriculumexpanded),
    ];
}

/**
 * Returns the early AMD bootstrap used to place the activity curriculum block.
 *
 * @return string
 */
function local_evalfp_render_coursemodule_curriculum_bootstrap_html(): string {
    if (local_evalfp_get_current_coursemodule_curriculum_context() === null) {
        return '';
    }

    return html_writer::script(
        "require(['local_evalfp/coursemodule_curriculum'], function(module) { module.watch(); });"
    );
}

/**
 * Returns delayed course module curriculum HTML for the current page.
 *
 * @return string
 */
function local_evalfp_render_coursemodule_curriculum_footer_html(): string {
    $context = local_evalfp_get_current_coursemodule_curriculum_context();
    if ($context === null) {
        return '';
    }

    $block = local_evalfp_render_coursemodule_curriculum_block(
        $context['courseid'],
        $context['cmid'],
        $context['expanded']
    );
    if ($block === '') {
        return '';
    }

    return html_writer::div($block, 'd-none', ['id' => 'local-evalfp-coursemodule-curriculum-source']) .
        html_writer::script("require(['local_evalfp/coursemodule_curriculum'], function(module) { module.init(); });");
}
