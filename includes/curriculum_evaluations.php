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
 * Course evaluation helpers for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Final ordinary evaluation type. */
const LOCAL_EVALFP_EVALUATION_TYPE_FINAL = 0;

/** Partial evaluation type. */
const LOCAL_EVALFP_EVALUATION_TYPE_PARTIAL = 1;

/** Extraordinary evaluation type. */
const LOCAL_EVALFP_EVALUATION_TYPE_EXTRAORDINARY = 2;

/** Full evaluation selector label mode. */
const LOCAL_EVALFP_EVALUATION_OPTION_FULL = 'full';

/** Short evaluation selector label mode. */
const LOCAL_EVALFP_EVALUATION_OPTION_SHORT = 'short';

/**
 * Returns the evaluation type options used by forms and reports.
 *
 * @return array<int, string>
 */
function local_evalfp_get_evaluation_type_options(): array {
    return [
        LOCAL_EVALFP_EVALUATION_TYPE_PARTIAL => get_string('evaluation_type_partial', 'local_evalfp'),
        LOCAL_EVALFP_EVALUATION_TYPE_FINAL => get_string('evaluation_type_final', 'local_evalfp'),
        LOCAL_EVALFP_EVALUATION_TYPE_EXTRAORDINARY => get_string('evaluation_type_extraordinary', 'local_evalfp'),
    ];
}

/**
 * Returns the display label for an evaluation type.
 *
 * @param int $type
 * @return string
 */
function local_evalfp_get_evaluation_type_label(int $type): string {
    $options = local_evalfp_get_evaluation_type_options();
    return $options[$type] ?? $options[LOCAL_EVALFP_EVALUATION_TYPE_PARTIAL];
}

/**
 * Builds the SQL ordering shared by all evaluation selectors.
 *
 * Evaluation periods are primarily ordered by end date. When two periods end
 * on the same day, the latest start date is shown first so final/extra periods
 * stay after the partial periods they close.
 *
 * @param string $prefix Optional table alias.
 * @return string
 */
function local_evalfp_get_evaluation_order_sql(string $prefix = ''): string {
    $field = static function (string $name) use ($prefix): string {
        return $prefix === '' ? $name : $prefix . '.' . $name;
    };

    return $field('enddate') . ' ASC, ' .
        $field('startdate') . ' DESC, ' .
        $field('code') . ' ASC';
}

/**
 * Formats the start and end date range for an evaluation.
 *
 * @param stdClass $evaluation
 * @return string
 */
function local_evalfp_format_evaluation_range(stdClass $evaluation): string {
    $start = !empty($evaluation->startdate) ? userdate($evaluation->startdate, get_string('strftimedate', 'langconfig')) : '-';
    $end = !empty($evaluation->enddate) ? userdate($evaluation->enddate, get_string('strftimedate', 'langconfig')) : '-';

    return html_writer::span($start, 'text-nowrap') .
        html_writer::span(' - ', 'text-muted mx-1') .
        html_writer::span($end, 'text-nowrap');
}

/**
 * Formats a compact plain-text date range for an evaluation.
 *
 * This is used in dense contextual summaries where Moodle's full date string
 * would take too much horizontal space.
 *
 * @param stdClass $evaluation Evaluation record.
 * @return string Compact date range, or empty string when dates are missing.
 */
function local_evalfp_format_evaluation_compact_date_range(stdClass $evaluation): string {
    if (empty($evaluation->startdate) || empty($evaluation->enddate)) {
        return '';
    }

    $start = trim(userdate((int)$evaluation->startdate, '%e %B %Y'));
    $end = trim(userdate((int)$evaluation->enddate, '%e %B %Y'));

    return $start . ' - ' . $end;
}

/**
 * Formats an evaluation option label for selectors.
 *
 * @param stdClass $evaluation
 * @param string $mode Label mode.
 * @return string
 */
function local_evalfp_format_evaluation_option(
    stdClass $evaluation,
    string $mode = LOCAL_EVALFP_EVALUATION_OPTION_FULL
): string {
    $code = format_string($evaluation->code);

    if ($mode === LOCAL_EVALFP_EVALUATION_OPTION_FULL) {
        $description = format_string($evaluation->description ?? '');
        return $description !== '' ? $description . ' (' . $code . ')' : $code;
    }

    $label = $code;
    $type = (int)($evaluation->type ?? LOCAL_EVALFP_EVALUATION_TYPE_PARTIAL);

    if ($type === LOCAL_EVALFP_EVALUATION_TYPE_FINAL) {
        return $label . ' (' . get_string('evaluation_type_final', 'local_evalfp') . ')';
    }

    return $label;
}

/**
 * Builds evaluation period options for selectors.
 *
 * Full labels include the evaluation code and dates when they help the user
 * identify the selected period. Short labels keep evidence matrices readable.
 *
 * @param array<int, stdClass> $evaluations Evaluation records indexed by ID.
 * @param string $mode Label mode.
 * @return array<int, string> Select options keyed by evaluation ID.
 */
function local_evalfp_get_evaluation_select_options(
    array $evaluations,
    string $mode = LOCAL_EVALFP_EVALUATION_OPTION_FULL
): array {
    $options = [0 => ''];

    foreach ($evaluations as $evaluation) {
        $options[(int)$evaluation->id] = local_evalfp_format_evaluation_option($evaluation, $mode);
    }

    return $options;
}

/**
 * Returns whether an evidence belongs to the selected evaluation.
 *
 * Final evaluations are calculated from all partial evaluations, so direct
 * evidence assignments to the final evaluation are intentionally ignored.
 *
 * @param int $gradeitemid Grade item ID.
 * @param int $selectedevalid Selected course evaluation ID.
 * @param array<int, int> $evaluationsbyevidence Evidence assignments indexed by grade item ID.
 * @param array<int, stdClass> $evaluationsbyid Course evaluations indexed by ID.
 * @return bool
 */
function local_evalfp_is_evidence_in_evaluation(
    int $gradeitemid,
    int $selectedevalid,
    array $evaluationsbyevidence,
    array $evaluationsbyid
): bool {
    if ($selectedevalid === 0) {
        return true;
    }

    if (empty($evaluationsbyid[$selectedevalid])) {
        return false;
    }

    $assignedevaluationid = (int)($evaluationsbyevidence[$gradeitemid] ?? 0);
    if ($assignedevaluationid <= 0) {
        return false;
    }

    $selectedtype = (int)($evaluationsbyid[$selectedevalid]->type ?? LOCAL_EVALFP_EVALUATION_TYPE_PARTIAL);
    if ($selectedtype === LOCAL_EVALFP_EVALUATION_TYPE_FINAL) {
        if (empty($evaluationsbyid[$assignedevaluationid])) {
            return false;
        }
        return (int)($evaluationsbyid[$assignedevaluationid]->type ??
            LOCAL_EVALFP_EVALUATION_TYPE_PARTIAL) === LOCAL_EVALFP_EVALUATION_TYPE_PARTIAL;
    }

    return $assignedevaluationid === $selectedevalid;
}

/**
 * Returns a course evaluation record.
 *
 * @param int $courseid
 * @param int $evaluationid
 * @param int $strictness
 * @return stdClass|null
 */
function local_evalfp_get_evaluation(int $courseid, int $evaluationid, int $strictness = MUST_EXIST): ?stdClass {
    global $DB;

    $record = $DB->get_record('local_evalfp_course_evaluation', ['id' => $evaluationid, 'courseid' => $courseid], '*', $strictness);
    return $record ?: null;
}

/**
 * Prepares default data for the evaluation form.
 *
 * @param int $courseid
 * @param int $evaluationid
 * @return stdClass
 */
function local_evalfp_prepare_evaluation_form_data(int $courseid, int $evaluationid = 0): stdClass {
    if ($evaluationid) {
        return local_evalfp_get_evaluation($courseid, $evaluationid);
    }

    $start = usergetmidnight(time());
    return (object)[
        'courseid' => $courseid,
        'type' => LOCAL_EVALFP_EVALUATION_TYPE_PARTIAL,
        'startdate' => $start,
        'enddate' => $start + DAYSECS - 1,
    ];
}

/**
 * Creates or updates a course evaluation.
 *
 * @param int $courseid
 * @param stdClass $data
 * @return int
 */
function local_evalfp_save_evaluation(int $courseid, stdClass $data): int {
    global $DB;

    $now = time();
    $type = (int)($data->type ?? LOCAL_EVALFP_EVALUATION_TYPE_PARTIAL);
    $record = (object)[
        'courseid' => $courseid,
        'code' => strtoupper(trim($data->code)),
        'description' => trim($data->description),
        'type' => $type,
        'startdate' => usergetmidnight((int)$data->startdate),
        'enddate' => usergetmidnight((int)$data->enddate) + DAYSECS - 1,
        'timemodified' => $now,
    ];

    if (!empty($data->id)) {
        $record->id = (int)$data->id;
        $DB->update_record('local_evalfp_course_evaluation', $record);
        if ($type === LOCAL_EVALFP_EVALUATION_TYPE_FINAL) {
            $DB->delete_records('local_evalfp_course_evidence_evaluation', ['courseevaluationid' => $record->id]);
        }
        return $record->id;
    }

    $record->timecreated = $now;
    return (int)$DB->insert_record('local_evalfp_course_evaluation', $record);
}

/**
 * Deletes a course evaluation and its evidence assignments.
 *
 * @param int $evaluationid
 * @return void
 */
function local_evalfp_delete_evaluation(int $evaluationid): void {
    global $DB;

    $DB->delete_records('local_evalfp_course_evidence_evaluation', ['courseevaluationid' => $evaluationid]);
    $DB->delete_records('local_evalfp_course_evaluation', ['id' => $evaluationid]);
}

/**
 * Returns course evaluations, optionally restricted to direct evidence assignment.
 *
 * @param int $courseid Course ID.
 * @param bool $directassignableonly Whether to exclude final/automatic evaluations from direct evidence assignment.
 * @return array<int, stdClass> Course evaluation records indexed by evaluation ID.
 */
function local_evalfp_get_evaluations(int $courseid, bool $directassignableonly = false): array {
    global $DB;

    $conditions = ['courseid = :courseid'];
    $params = ['courseid' => $courseid];

    if ($directassignableonly) {
        $conditions[] = 'type > 0';
    }

    return $DB->get_records_select(
        'local_evalfp_course_evaluation',
        implode(' AND ', $conditions),
        $params,
        local_evalfp_get_evaluation_order_sql()
    );
}

/**
 * Returns the Bootstrap badge class for an evaluation type.
 *
 * @param int $type
 * @return string
 */
function local_evalfp_get_evaluation_type_badge_class(int $type): string {
    if ($type === LOCAL_EVALFP_EVALUATION_TYPE_FINAL) {
        return 'badge badge-primary';
    }
    if ($type === LOCAL_EVALFP_EVALUATION_TYPE_EXTRAORDINARY) {
        return 'badge badge-warning';
    }

    return 'badge badge-secondary';
}

/**
 * Renders the course evaluation list.
 *
 * @param int $courseid Course ID.
 * @param array<int, stdClass> $evaluations Course evaluations.
 * @return string
 */
function local_evalfp_render_evaluations_table(int $courseid, array $evaluations): string {
    global $OUTPUT;

    ob_start();
    echo html_writer::start_div('table-responsive border');
    echo html_writer::start_tag('table', ['class' => 'generaltable table-light mb-0']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('common_code', 'local_evalfp'), ['scope' => 'col']);
    echo html_writer::tag('th', get_string('common_name', 'local_evalfp'), ['scope' => 'col']);
    echo html_writer::tag('th', get_string('form_evaluation_type', 'local_evalfp'), ['scope' => 'col']);
    echo html_writer::tag('th', get_string('common_date_range', 'local_evalfp'), ['scope' => 'col']);
    echo html_writer::tag('th', html_writer::span(get_string('actions'), 'accesshide'), [
        'scope' => 'col',
        'class' => 'text-center',
        'style' => 'width: 3rem;',
    ]);
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($evaluations as $item) {
        $type = (int)($item->type ?? LOCAL_EVALFP_EVALUATION_TYPE_PARTIAL);
        $dates = local_evalfp_format_evaluation_range($item);
        $typebadge = html_writer::span(
            local_evalfp_get_evaluation_type_label($type),
            local_evalfp_get_evaluation_type_badge_class($type)
        );

        $menu = new action_menu();
        $icon = html_writer::tag('i', '', [
            'class' => 'icon fa fa-ellipsis-v fa-fw m-0',
            'aria-hidden' => 'true',
        ]);
        $extraclasses = 'btn btn-icon d-flex align-items-center justify-content-center dropdown-toggle icon-no-margin no-caret';
        $menu->set_menu_trigger($icon, $extraclasses);
        $menu->set_menu_left();
        $menu->set_boundary('window');

        $editurl = new moodle_url('/local/evalfp/course/curriculum/evaluation_edit.php', [
            'courseid' => $courseid,
            'evaluationid' => $item->id,
        ]);
        $editicon = new pix_icon('t/edit', get_string('edit'));
        $menu->add(new action_menu_link_secondary($editurl, $editicon, get_string('edit')));

        $deleteurl = new moodle_url('/local/evalfp/course/curriculum/evaluation_delete.php', [
            'courseid' => $courseid,
            'evaluationid' => $item->id,
            'sesskey' => sesskey(),
        ]);
        $deleteicon = new pix_icon('t/delete', get_string('delete'));
        $menu->add(new action_menu_link_secondary($deleteurl, $deleteicon, get_string('delete'), ['class' => 'text-danger']));

        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', format_string($item->code), ['class' => 'align-middle text-nowrap']);
        echo html_writer::tag('td', format_string($item->description), ['class' => 'align-middle']);
        echo html_writer::tag('td', $typebadge, ['class' => 'align-middle text-nowrap']);
        echo html_writer::tag('td', html_writer::div($dates, 'text-nowrap'), ['class' => 'align-middle']);
        echo html_writer::tag('td', $OUTPUT->render($menu), ['class' => 'align-middle text-center']);
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();

    return ob_get_clean();
}
