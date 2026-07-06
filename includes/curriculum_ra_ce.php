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
 * Learning outcome and assessment criterion helpers for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns a course learning outcome by id.
 *
 * @param int $courseid
 * @param int $courseraid
 * @param int $strictness
 * @return stdClass|null
 */
function local_evalfp_get_ra(int $courseid, int $courseraid, int $strictness = MUST_EXIST): ?stdClass {
    global $DB;

    $record = $DB->get_record('local_evalfp_course_ra', ['id' => $courseraid, 'courseid' => $courseid], '*', $strictness);
    return $record ?: null;
}

/**
 * Returns a learning outcome by id.
 *
 * @param int $courseraid
 * @param int $strictness
 * @return stdClass|null
 */
function local_evalfp_get_ra_by_id(int $courseraid, int $strictness = MUST_EXIST): ?stdClass {
    global $DB;

    $record = $DB->get_record('local_evalfp_course_ra', ['id' => $courseraid], '*', $strictness);
    return $record ?: null;
}

/**
 * Prepares default data for the learning outcome form.
 *
 * @param int $courseid
 * @param int $courseraid
 * @return stdClass
 */
function local_evalfp_prepare_ra_form_data(int $courseid, int $courseraid = 0): stdClass {
    if ($courseraid) {
        $record = local_evalfp_get_ra($courseid, $courseraid);
        $record->courseraid = $record->id;
        $record->courseid = $courseid;
        return $record;
    }

    return (object)[
        'courseraid' => 0,
        'courseid' => $courseid,
        'code' => '',
        'description' => '',
    ];
}

/**
 * Creates or updates a course learning outcome.
 *
 * @param int $courseid
 * @param stdClass $data
 * @return int
 */
function local_evalfp_save_ra(int $courseid, stdClass $data): int {
    global $DB;

    $now = time();
    $record = (object)[
        'courseid' => $courseid,
        'code' => trim($data->code),
        'description' => trim($data->description),
        'weight' => isset($data->weight) ? (float)$data->weight : 0,
        'timemodified' => $now,
    ];

    if (!empty($data->courseraid)) {
        $record->id = (int)$data->courseraid;
        $DB->update_record('local_evalfp_course_ra', $record);
        return $record->id;
    }

    $record->timecreated = $now;
    return (int)$DB->insert_record('local_evalfp_course_ra', $record);
}

/**
 * Deletes a learning outcome and related CE/evidence links.
 *
 * @param int $courseraid
 * @return void
 */
function local_evalfp_delete_ra(int $courseraid): void {
    global $DB;

    $ces = $DB->get_records('local_evalfp_course_ce', ['courseraid' => $courseraid], '', 'id');
    if ($ces) {
        $cenids = array_keys($ces);
        [$insql, $params] = $DB->get_in_or_equal($cenids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_evalfp_course_evidence_ce', "courseceid $insql", $params);
    }

    $DB->delete_records('local_evalfp_course_ce', ['courseraid' => $courseraid]);
    $DB->delete_records('local_evalfp_course_ra', ['id' => $courseraid]);
}

/**
 * Formats a learning outcome code for display.
 *
 * @param stdClass $ra
 * @return string
 */
function local_evalfp_format_ra_code(stdClass $ra): string {
    return local_evalfp_format_ra_label($ra->code);
}

/**
 * Returns an assessment criterion by id.
 *
 * @param int $courseraid
 * @param int $courseceid
 * @param int $strictness
 * @return stdClass|null
 */
function local_evalfp_get_ce(int $courseraid, int $courseceid, int $strictness = MUST_EXIST): ?stdClass {
    global $DB;

    $record = $DB->get_record(
        'local_evalfp_course_ce',
        ['id' => $courseceid, 'courseraid' => $courseraid],
        '*',
        $strictness
    );
    return $record ?: null;
}

/**
 * Prepares default data for the assessment criterion form.
 *
 * @param int $courseraid
 * @param int $courseceid
 * @return stdClass
 */
function local_evalfp_prepare_ce_form_data(int $courseraid, int $courseceid = 0): stdClass {
    if ($courseceid) {
        $record = local_evalfp_get_ce($courseraid, $courseceid);
        $record->courseceid = $record->id;
        $record->courseraid = $courseraid;
        return $record;
    }

    return (object)[
        'courseceid' => 0,
        'courseraid' => $courseraid,
        'code' => '',
        'description' => '',
        'weight' => 0,
    ];
}

/**
 * Creates or updates an assessment criterion.
 *
 * @param int $courseraid
 * @param stdClass $data
 * @return int
 */
function local_evalfp_save_ce(int $courseraid, stdClass $data): int {
    global $DB;

    $now = time();
    $record = (object)[
        'courseraid' => $courseraid,
        'code' => trim($data->code),
        'description' => trim($data->description),
        'timemodified' => $now,
    ];

    if (property_exists($data, 'weight')) {
        $record->weight = (float)$data->weight;
    }

    if (!empty($data->courseceid)) {
        $record->id = (int)$data->courseceid;
        $DB->update_record('local_evalfp_course_ce', $record);
        return $record->id;
    }

    if (!property_exists($record, 'weight')) {
        $record->weight = 0;
    }
    $record->timecreated = $now;
    return (int)$DB->insert_record('local_evalfp_course_ce', $record);
}

/**
 * Deletes an assessment criterion and related evidence links.
 *
 * @param int $courseceid
 * @return void
 */
function local_evalfp_delete_ce(int $courseceid): void {
    global $DB;

    $DB->delete_records('local_evalfp_course_evidence_ce', ['courseceid' => $courseceid]);
    $DB->delete_records('local_evalfp_course_ce', ['id' => $courseceid]);
}


/**
 * Returns learning outcomes and their evaluation criteria.
 *
 * @param int $courseid Course ID.
 * @return array{results: array<int, stdClass>, cebyra: array<int, array<int, stdClass>>}
 *  RA records and CE records grouped by RA ID.
 */
function local_evalfp_get_ras_with_ce(int $courseid): array {
    global $DB;

    $ras = $DB->get_records('local_evalfp_course_ra', ['courseid' => $courseid], 'code ASC');
    $cesbyra = [];

    if ($ras) {
        $raids = array_keys($ras);
        [$rasql, $raparams] = $DB->get_in_or_equal($raids, SQL_PARAMS_NAMED, 'ra');
        $ces = $DB->get_records_select(
            'local_evalfp_course_ce',
            "courseraid $rasql",
            $raparams,
            'courseraid ASC, code ASC'
        );

        foreach ($ces as $ce) {
            $cesbyra[(int)$ce->courseraid][] = $ce;
        }
    }

    return [
        'results' => $ras,
        'cebyra' => $cesbyra,
    ];
}

/**
 * Returns course learning outcomes.
 *
 * @param int $courseid Course ID.
 * @return array<int, stdClass> RA records indexed by RA ID.
 */
function local_evalfp_get_ras(int $courseid): array {
    global $DB;

    return $DB->get_records('local_evalfp_course_ra', ['courseid' => $courseid], 'code ASC');
}

/**
 * Parses submitted learning outcome weights.
 *
 * @param array<int, stdClass> $ras RA records indexed by RA ID.
 * @param array<string, mixed> $submitted Submitted form data.
 * @return array{weights: array<int, float>, errors: array<int, string>} Parsed weights and validation errors.
 */
function local_evalfp_parse_ra_weights(array $ras, array $submitted): array {
    $errors = [];
    $weights = [];
    $sum = 0.0;

    foreach ($ras as $ra) {
        $raw = trim((string)($submitted[$ra->id] ?? '0'));
        $raw = str_replace(',', '.', $raw);
        if ($raw === '') {
            $raw = '0';
        }
        if (!is_numeric($raw)) {
            $errors[$ra->id] = get_string('error_weight_invalid', 'local_evalfp');
            continue;
        }
        $weight = (float)$raw;
        if ($weight < 0 || $weight > 100) {
            $errors[$ra->id] = get_string('error_weight_range', 'local_evalfp');
            continue;
        }
        $weights[$ra->id] = $weight;
        $sum += $weight;
    }

    return [
        'weights' => $weights,
        'errors' => $errors,
        'sum' => $sum,
    ];
}

/**
 * Saves learning outcome weights.
 *
 * @param array $ras
 * @param array $weights
 * @return void
 */
function local_evalfp_save_ra_weights(array $ras, array $weights): void {
    global $DB;

    $now = time();
    foreach ($ras as $ra) {
        $ra->weight = $weights[$ra->id] ?? 0;
        $ra->timemodified = $now;
        $DB->update_record('local_evalfp_course_ra', $ra);
    }
}

/**
 * Applies submitted weights to learning outcome records for preview.
 *
 * @param array<int, stdClass> $ras RA records indexed by RA ID.
 * @param array<int, float> $weights Parsed weights indexed by RA ID.
 * @return array<int, stdClass> RA records with preview weights applied.
 */
function local_evalfp_apply_ra_weight_preview(array $ras, array $weights): array {
    foreach ($ras as $ra) {
        if (isset($weights[$ra->id])) {
            $ra->weight = $weights[$ra->id];
        }
    }

    return $ras;
}


/**
 * Parses submitted assessment criterion weights grouped by learning outcome.
 *
 * @param array<int, stdClass> $ras RA records indexed by RA ID.
 * @param array<int, array<int, stdClass>> $cesbyra CE records grouped by RA ID.
 * @param array<string, mixed> $submitted Submitted form data.
 * @return array{weights: array<int, float>, errors: array<int, string>, sums: array<int, float>}
 *  Parsed weights, validation errors indexed by CE ID, and totals indexed by RA ID.
 */
function local_evalfp_parse_ce_weights(array $ras, array $cesbyra, array $submitted): array {
    $errors = [];
    $weights = [];
    $sums = [];

    foreach ($ras as $ra) {
        $raid = (int)$ra->id;
        $sums[$raid] = 0.0;
        foreach ($cesbyra[$raid] ?? [] as $ce) {
            $raw = trim((string)($submitted[$ce->id] ?? '0'));
            $raw = str_replace(',', '.', $raw);
            if ($raw === '') {
                $raw = '0';
            }
            if (!is_numeric($raw)) {
                $errors[$ce->id] = get_string('error_weight_invalid', 'local_evalfp');
                continue;
            }
            $weight = (float)$raw;
            if ($weight < 0 || $weight > 100) {
                $errors[$ce->id] = get_string('error_weight_range', 'local_evalfp');
                continue;
            }
            $weights[$ce->id] = $weight;
            $sums[$raid] += $weight;
        }
    }

    return [
        'weights' => $weights,
        'errors' => $errors,
        'sums' => $sums,
    ];
}

/**
 * Saves assessment criterion weights.
 *
 * @param array<int, array<int, stdClass>> $cesbyra CE records grouped by RA ID.
 * @param array<int, float> $weights Parsed weights indexed by CE ID.
 * @return void
 */
function local_evalfp_save_ce_weights(array $cesbyra, array $weights): void {
    global $DB;

    $now = time();
    foreach ($cesbyra as $ces) {
        foreach ($ces as $ce) {
            $ce->weight = $weights[$ce->id] ?? 0;
            $ce->timemodified = $now;
            $DB->update_record('local_evalfp_course_ce', $ce);
        }
    }
}

/**
 * Applies submitted CE weights to records for preview.
 *
 * @param array<int, array<int, stdClass>> $cesbyra CE records grouped by RA ID.
 * @param array<int, float> $weights Parsed weights indexed by CE ID.
 * @return array<int, array<int, stdClass>> CE records with preview weights applied.
 */
function local_evalfp_apply_ce_weight_preview(array $cesbyra, array $weights): array {
    foreach ($cesbyra as $raid => $ces) {
        foreach ($ces as $index => $ce) {
            if (isset($weights[$ce->id])) {
                $cesbyra[$raid][$index]->weight = $weights[$ce->id];
            }
        }
    }

    return $cesbyra;
}

/**
 * Renders the assessment criterion weights form.
 *
 * @param int $courseid Course ID.
 * @param array<int, stdClass> $ras RA records indexed by RA ID.
 * @param array<int, array<int, stdClass>> $cesbyra CE records grouped by RA ID.
 * @param array<int, string> $errors Validation errors indexed by CE ID.
 * @return string Rendered form HTML.
 */
function local_evalfp_render_ce_weights_form(int $courseid, array $ras, array $cesbyra, array $errors = []): string {
    ob_start();
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/local/evalfp/course/curriculum/ce_weights.php', ['courseid' => $courseid]),
        'id' => 'local-evalfp-ce-weights-form',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    foreach ($ras as $ra) {
        $raid = (int)$ra->id;
        $ces = $cesbyra[$raid] ?? [];
        $displaycode = local_evalfp_format_ra_code($ra);
        $raheading = html_writer::span(format_string($displaycode), 'badge badge-primary mr-2') .
            html_writer::span(format_string($ra->description), 'font-weight-bold');

        echo html_writer::tag('p', $raheading, ['class' => 'mb-3']);

        if (!$ces) {
            echo html_writer::div(get_string('page_curriculum_no_ce_for_ra', 'local_evalfp'), 'text-muted small mb-4');
            continue;
        }

        echo html_writer::start_div('table-responsive border mb-4');
        echo html_writer::start_tag('table', ['class' => 'generaltable table-light mb-0']);
        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', get_string('form_ce_description', 'local_evalfp'), ['scope' => 'col']);
        echo html_writer::tag('th', get_string('common_weight', 'local_evalfp'), [
            'scope' => 'col',
            'class' => 'text-right text-nowrap',
            'style' => 'width: 10rem;',
        ]);
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');

        $total = 0.0;
        foreach ($ces as $ce) {
            $displayce = local_evalfp_format_ce_code($ra->code, $ce->code);
            $weight = (float)($ce->weight ?? 0);
            $total += $weight;
            $value = rtrim(rtrim(sprintf('%.2f', $weight), '0'), '.');
            $inputid = 'local-evalfp-ce-weight-' . (int)$ce->id;
            $input = html_writer::start_div('input-group input-group-sm justify-content-end flex-nowrap');
            $input .= html_writer::empty_tag('input', [
                'type' => 'text',
                'class' => 'form-control text-right w-auto' . (isset($errors[$ce->id]) ? ' is-invalid' : ''),
                'id' => $inputid,
                'name' => 'weights[' . (int)$ce->id . ']',
                'value' => $value,
                'size' => 5,
                'inputmode' => 'decimal',
                'aria-label' => get_string('form_ce_weight_for', 'local_evalfp', $displayce),
                'data-local-evalfp-weight-input' => '1',
            ]);
            $input .= html_writer::div(html_writer::span('%', 'input-group-text'), 'input-group-append');
            $input .= html_writer::end_div();
            if (isset($errors[$ce->id])) {
                $input .= html_writer::div($errors[$ce->id], 'invalid-feedback d-block text-right');
            }

            echo html_writer::start_tag('tr');
            echo html_writer::tag(
                'td',
                html_writer::span(format_string($displayce), 'badge badge-secondary mr-2') .
                    html_writer::span(format_string($ce->description)),
                ['class' => 'align-middle']
            );
            echo html_writer::tag('td', $input, ['class' => 'align-middle text-right text-nowrap']);
            echo html_writer::end_tag('tr');
        }
        echo html_writer::end_tag('tbody');

        $totalclass = round($total, 2) === 100.0 ? 'badge badge-success' : 'badge badge-warning';
        echo html_writer::start_tag('tfoot');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', get_string('total', 'grades'), ['class' => 'text-right align-middle']);
        echo html_writer::tag('th', html_writer::span(local_evalfp_format_percent($total), $totalclass, [
            'data-local-evalfp-weight-total' => '1',
        ]), ['class' => 'text-right align-middle']);
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('tfoot');
        echo html_writer::end_tag('table');
        echo html_writer::end_div();
    }

    echo html_writer::end_tag('form');

    return ob_get_clean();
}


/**
 * Renders the learning outcome and assessment criterion list.
 *
 * @param int $courseid
 * @param array $results
 * @param array $cesbyra
 * @return string
 */
function local_evalfp_render_ra_ce_list(int $courseid, array $results, array $cesbyra): string {
    ob_start();

    foreach ($results as $ra) {
        $displaycode = local_evalfp_format_ra_code($ra);
        $ces = $cesbyra[$ra->id] ?? [];
        $raactions = local_evalfp_render_course_action_menu([
            [
                'url' => new moodle_url(
                    '/local/evalfp/course/curriculum/ra_edit.php',
                    ['courseid' => $courseid, 'courseraid' => $ra->id]
                ),
                'icon' => new pix_icon('t/edit', get_string('edit')),
                'text' => get_string('edit'),
            ],
            [
                'url' => new moodle_url(
                    '/local/evalfp/course/curriculum/ra_delete.php',
                    ['courseid' => $courseid, 'courseraid' => $ra->id, 'sesskey' => sesskey()]
                ),
                'icon' => new pix_icon('t/delete', get_string('delete')),
                'text' => get_string('delete'),
                'danger' => true,
            ],
            [
                'separator' => true,
            ],
            [
                'url' => new moodle_url(
                    '/local/evalfp/course/curriculum/ce_edit.php',
                    ['courseraid' => $ra->id]
                ),
                'icon' => new pix_icon('t/add', get_string('page_curriculum_add_ce', 'local_evalfp')),
                'text' => get_string('page_curriculum_add_ce', 'local_evalfp'),
            ],
        ]);

        echo html_writer::start_div('list-group mb-3');
        echo html_writer::start_div(
            'list-group-item bg-light d-flex align-items-center justify-content-between py-2'
        );
        echo html_writer::div(
            html_writer::span(format_string($displaycode), 'badge badge-primary mr-2') .
                html_writer::span(format_string($ra->description), 'font-weight-bold'),
            'pr-3'
        );
        echo html_writer::div($raactions, 'text-center text-nowrap');
        echo html_writer::end_div();

        if (!$ces) {
            echo html_writer::div(
                get_string('page_curriculum_no_ce_for_ra', 'local_evalfp'),
                'list-group-item text-muted small'
            );
            echo html_writer::end_div();
            continue;
        }

        foreach ($ces as $ce) {
            $displayce = local_evalfp_format_ce_code($ra->code, $ce->code);
            $ceactions = local_evalfp_render_course_action_menu([
                [
                    'url' => new moodle_url(
                        '/local/evalfp/course/curriculum/ce_edit.php',
                        ['courseraid' => $ra->id, 'courseceid' => $ce->id]
                    ),
                    'icon' => new pix_icon('t/edit', get_string('edit')),
                    'text' => get_string('edit'),
                ],
                [
                    'url' => new moodle_url(
                        '/local/evalfp/course/curriculum/ce_delete.php',
                        ['courseraid' => $ra->id, 'courseceid' => $ce->id, 'sesskey' => sesskey()]
                    ),
                    'icon' => new pix_icon('t/delete', get_string('delete')),
                    'text' => get_string('delete'),
                    'danger' => true,
                ],
            ]);

            $cecontent = html_writer::div(
                html_writer::span(format_string($displayce), 'badge badge-secondary mr-2 text-nowrap') .
                    html_writer::span(format_string($ce->description)),
                'd-flex align-items-center flex-grow-1 pr-3'
            );

            echo html_writer::div(
                $cecontent . html_writer::div($ceactions, 'text-center text-nowrap'),
                'list-group-item d-flex align-items-center justify-content-between py-2'
            );
        }

        echo html_writer::end_div();
    }

    return ob_get_clean();
}


/**
 * Renders the learning outcome weights form.
 *
 * @param int $courseid
 * @param array $ras
 * @param array $errors
 * @return string
 */
function local_evalfp_render_ra_weights_form(int $courseid, array $ras, array $errors = []): string {
    ob_start();
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/local/evalfp/course/curriculum/ra_weights.php', ['courseid' => $courseid]),
        'id' => 'local-evalfp-ra-weights-form',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    echo html_writer::start_div('table-responsive border');
    echo html_writer::start_tag('table', ['class' => 'generaltable table-light mb-0']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('form_ra_description', 'local_evalfp'), ['scope' => 'col']);
    echo html_writer::tag('th', get_string(
        'common_weight',
        'local_evalfp'
    ), ['scope' => 'col', 'class' => 'text-right text-nowrap', 'style' => 'width: 10rem;']);
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    $total = 0.0;
    foreach ($ras as $ra) {
        $displaycode = local_evalfp_format_ra_code($ra);
        $weight = (float)($ra->weight ?? 0);
        $total += $weight;
        $value = rtrim(rtrim(sprintf('%.2f', $weight), '0'), '.');
        $inputid = 'local-evalfp-ra-weight-' . (int)$ra->id;
        $input = html_writer::start_div('input-group input-group-sm justify-content-end flex-nowrap');
        $input .= html_writer::empty_tag('input', [
            'type' => 'text',
            'class' => 'form-control text-right w-auto' . (isset($errors[$ra->id]) ? ' is-invalid' : ''),
            'id' => $inputid,
            'name' => 'weights[' . (int)$ra->id . ']',
            'value' => $value,
            'size' => 5,
            'inputmode' => 'decimal',
            'aria-label' => get_string('form_ra_weight_for', 'local_evalfp', $displaycode),
            'data-local-evalfp-weight-input' => '1',
        ]);
        $input .= html_writer::div(html_writer::span('%', 'input-group-text'), 'input-group-append');
        $input .= html_writer::end_div();
        if (isset($errors[$ra->id])) {
            $input .= html_writer::div($errors[$ra->id], 'invalid-feedback d-block text-right');
        }

        echo html_writer::start_tag('tr');
        echo html_writer::tag(
            'td',
            html_writer::span(format_string($displaycode), 'badge badge-primary mr-2') .
                html_writer::span(format_string($ra->description)),
            ['class' => 'align-middle']
        );
        echo html_writer::tag('td', $input, ['class' => 'align-middle text-right text-nowrap']);
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');

    $totalclass = round($total, 2) === 100.0 ? 'badge badge-success' : 'badge badge-warning';
    echo html_writer::start_tag('tfoot');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('total', 'grades'), ['class' => 'text-right align-middle']);
    echo html_writer::tag('th', html_writer::span(rtrim(rtrim(sprintf('%.2f', $total), '0'), '.') . '%', $totalclass, [
        'data-local-evalfp-weight-total' => '1',
    ]), ['class' => 'text-right align-middle']);
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('tfoot');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();

    echo html_writer::end_tag('form');

    return ob_get_clean();
}
