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
 * Learning outcome and assessment criterion import helpers for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Merge imported RA/CE data with current course records. */
const LOCAL_EVALFP_RA_CE_IMPORT_MODE_MERGE = 'merge';

/** Replace current course RA/CE data with imported records. */
const LOCAL_EVALFP_RA_CE_IMPORT_MODE_REPLACE = 'replace';

/**
 * Returns the expected RA/CE import headers.
 *
 * @return array<int, string> Required spreadsheet column names.
 */
function local_evalfp_ra_ce_import_get_expected_headers(): array {
    return ['type', 'ra', 'ce', 'description'];
}

/**
 * Normalises a spreadsheet cell value.
 *
 * @param mixed $value
 * @return string
 */
function local_evalfp_ra_ce_import_normalise_cell($value): string {
    if ($value === null) {
        return '';
    }
    return trim((string)$value);
}

/**
 * Normalises an import row type.
 *
 * @param string $type
 * @return string
 */
function local_evalfp_ra_ce_import_normalise_type(string $type): string {
    return core_text::strtolower(trim($type));
}

/**
 * Normalises a learning outcome code from import data.
 *
 * @param string $raw
 * @return string
 */
function local_evalfp_ra_ce_import_normalise_ra_code(string $raw): string {
    $code = trim($raw);
    $code = preg_replace('/^RA\s*/i', '', $code);
    return trim($code);
}

/**
 * Normalises an assessment criterion code from import data.
 *
 * @param string $raw
 * @param string $racode
 * @return string
 */
function local_evalfp_ra_ce_import_normalise_ce_code(string $raw, string $racode): string {
    $code = trim($raw);
    $code = preg_replace('/^CE\s*/i', '', $code);
    $raclean = preg_quote(preg_replace('/^RA\s*/i', '', $racode), '/');
    if ($raclean !== '') {
        $code = preg_replace('/^' . $raclean . '\s*/i', '', $code);
    }
    return trim($code);
}

/**
 * Checks whether an imported code is valid.
 *
 * @param string $code
 * @return bool
 */
function local_evalfp_ra_ce_import_is_valid_code(string $code): bool {
    return $code !== '' && clean_param($code, PARAM_ALPHANUMEXT) === $code;
}

/**
 * Reads rows from an ODS import file.
 *
 * Only the first worksheet is read. Formatting is ignored because the import
 * contract is defined entirely by the cell values.
 *
 * @param string $filepath Absolute path to the uploaded ODS file.
 * @return array<int, array<int, mixed>> Spreadsheet rows.
 */
function local_evalfp_ra_ce_import_read_ods(string $filepath): array {
    global $CFG;

    require_once($CFG->dirroot . '/lib/phpspreadsheet/vendor/autoload.php');

    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Ods');
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($filepath);
    $worksheet = $spreadsheet->getSheet(0);
    $rows = $worksheet->toArray('', false, false, false);
    $spreadsheet->disconnectWorksheets();

    return $rows;
}

/**
 * Parses raw ODS rows into RA/CE import data.
 *
 * Returned data contains importable items plus validation warnings/errors, but
 * no database changes are performed at this stage.
 *
 * @param array<int, array<int, mixed>> $rows Rows read from the ODS worksheet.
 * @return array{items: array<int, array<string, mixed>>, ras: array<string, array<string, mixed>>,
 *  ces: array<string, array<string, mixed>>, errors: array<int, string>, warnings: array<int, string>}
 */
function local_evalfp_ra_ce_import_parse_rows(array $rows): array {
    $expected = local_evalfp_ra_ce_import_get_expected_headers();
    $errors = [];
    $warnings = [];
    $items = [];
    $ras = [];
    $ces = [];
    $startrow = 0;

    if ($rows) {
        $first = array_map(static function ($value): string {
            return core_text::strtolower(local_evalfp_ra_ce_import_normalise_cell($value));
        }, array_slice($rows[0], 0, 4));
        if ($first === $expected) {
            $startrow = 1;
        }
    }

    for ($i = $startrow; $i < count($rows); $i++) {
        $rownum = $i + 1;
        $row = array_pad($rows[$i], 4, '');
        $type = local_evalfp_ra_ce_import_normalise_type(local_evalfp_ra_ce_import_normalise_cell($row[0]));
        $rawra = local_evalfp_ra_ce_import_normalise_cell($row[1]);
        $rawce = local_evalfp_ra_ce_import_normalise_cell($row[2]);
        $description = clean_param(local_evalfp_ra_ce_import_normalise_cell($row[3]), PARAM_TEXT);

        if ($type === '' && $rawra === '' && $rawce === '' && $description === '') {
            continue;
        }

        if (!in_array($type, ['ra', 'ce'], true)) {
            $errors[] = get_string('import_ra_ce_error_invalid_type', 'local_evalfp', (object)['row' => $rownum]);
            continue;
        }

        $racode = local_evalfp_ra_ce_import_normalise_ra_code($rawra);
        if ($racode === '') {
            $errors[] = get_string('import_ra_ce_error_missing_ra', 'local_evalfp', (object)['row' => $rownum]);
            continue;
        }
        if (!local_evalfp_ra_ce_import_is_valid_code($racode)) {
            $errors[] = get_string(
                'import_ra_ce_error_invalid_code',
                'local_evalfp',
                (object)['row' => $rownum, 'code' => $racode]
            );
            continue;
        }
        if (core_text::strlen($racode) > 10) {
            $errors[] = get_string(
                'import_ra_ce_error_code_too_long',
                'local_evalfp',
                (object)['row' => $rownum, 'code' => $racode]
            );
            continue;
        }
        if ($description === '') {
            $errors[] = get_string(
                'import_ra_ce_error_missing_description',
                'local_evalfp',
                (object)['row' => $rownum]
            );
            continue;
        }

        if ($type === 'ra') {
            if ($rawce !== '') {
                $warnings[] = get_string(
                    'import_ra_ce_warning_ra_ce_ignored',
                    'local_evalfp',
                    (object)['row' => $rownum]
                );
            }
            if (isset($ras[core_text::strtolower($racode)])) {
                $errors[] = get_string(
                    'import_ra_ce_error_duplicate_ra',
                    'local_evalfp',
                    (object)['row' => $rownum, 'code' => local_evalfp_format_ra_code((object)['code' => $racode])]
                );
                continue;
            }
            $ras[core_text::strtolower($racode)] = [
                'code' => $racode,
                'description' => $description,
                'row' => $rownum,
            ];
            $items[] = [
                'type' => 'ra',
                'ra' => $racode,
                'ce' => '',
                'description' => $description,
                'row' => $rownum,
            ];
            continue;
        }

        $cecode = local_evalfp_ra_ce_import_normalise_ce_code($rawce, $racode);
        if ($cecode === '') {
            $errors[] = get_string('import_ra_ce_error_missing_ce', 'local_evalfp', (object)['row' => $rownum]);
            continue;
        }
        if (!local_evalfp_ra_ce_import_is_valid_code($cecode)) {
            $errors[] = get_string(
                'import_ra_ce_error_invalid_code',
                'local_evalfp',
                (object)['row' => $rownum, 'code' => $cecode]
            );
            continue;
        }
        if (core_text::strlen($cecode) > 10) {
            $errors[] = get_string(
                'import_ra_ce_error_code_too_long',
                'local_evalfp',
                (object)['row' => $rownum, 'code' => $cecode]
            );
            continue;
        }

        $rakey = core_text::strtolower($racode);
        $cekey = $rakey . ':' . core_text::strtolower($cecode);
        if (isset($ces[$cekey])) {
            $errors[] = get_string(
                'import_ra_ce_error_duplicate_ce',
                'local_evalfp',
                (object)['row' => $rownum, 'code' => local_evalfp_format_ce_code($racode, $cecode)]
            );
            continue;
        }
        $ces[$cekey] = [
            'racode' => $racode,
            'code' => $cecode,
            'description' => $description,
            'row' => $rownum,
        ];
        $items[] = [
            'type' => 'ce',
            'ra' => $racode,
            'ce' => $cecode,
            'description' => $description,
            'row' => $rownum,
        ];
    }

    if (!$items && !$errors) {
        $errors[] = get_string('import_ra_ce_error_empty_file', 'local_evalfp');
    }

    foreach ($ces as $ce) {
        if (!isset($ras[core_text::strtolower($ce['racode'])])) {
            $warnings[] = get_string('import_ra_ce_warning_ce_without_ra_in_file', 'local_evalfp', (object)[
                'row' => $ce['row'],
                'ra' => local_evalfp_format_ra_code((object)['code' => $ce['racode']]),
            ]);
        }
    }

    return [
        'items' => $items,
        'ras' => $ras,
        'ces' => $ces,
        'errors' => $errors,
        'warnings' => $warnings,
    ];
}

/**
 * Validates parsed RA/CE import data against the course.
 *
 * @param int $courseid Course ID.
 * @param array<string, mixed> $parsed Parsed import data.
 * @param string $mode Import mode.
 * @return array<string, mixed> Parsed import data with course-level validation messages.
 */
function local_evalfp_ra_ce_import_validate_against_course(int $courseid, array $parsed, string $mode): array {
    global $DB;

    $errors = $parsed['errors'];
    $warnings = $parsed['warnings'];

    if (!in_array($mode, [LOCAL_EVALFP_RA_CE_IMPORT_MODE_MERGE, LOCAL_EVALFP_RA_CE_IMPORT_MODE_REPLACE], true)) {
        $errors[] = get_string('error_invalid_record', 'local_evalfp');
    }

    if ($mode === LOCAL_EVALFP_RA_CE_IMPORT_MODE_MERGE) {
        $existing = $DB->get_records('local_evalfp_course_ra', ['courseid' => $courseid], '', 'id, code');
        $existingbycode = [];
        foreach ($existing as $ra) {
            $existingbycode[core_text::strtolower($ra->code)] = $ra;
        }
        foreach ($parsed['ces'] as $ce) {
            $rakey = core_text::strtolower($ce['racode']);
            if (!isset($parsed['ras'][$rakey]) && !isset($existingbycode[$rakey])) {
                $errors[] = get_string('import_ra_ce_error_ce_unknown_ra', 'local_evalfp', (object)[
                    'row' => $ce['row'],
                    'ra' => local_evalfp_format_ra_code((object)['code' => $ce['racode']]),
                ]);
            }
        }
    } else {
        foreach ($parsed['ces'] as $ce) {
            if (!isset($parsed['ras'][core_text::strtolower($ce['racode'])])) {
                $errors[] = get_string('import_ra_ce_error_ce_unknown_ra_replace', 'local_evalfp', (object)[
                    'row' => $ce['row'],
                    'ra' => local_evalfp_format_ra_code((object)['code' => $ce['racode']]),
                ]);
            }
        }
    }

    $parsed['errors'] = array_values(array_unique($errors));
    $parsed['warnings'] = array_values(array_unique($warnings));
    $parsed['mode'] = $mode;

    return $parsed;
}

/**
 * Reads, parses and validates an ODS import file.
 *
 * This is the entry point used by the import form before showing the preview
 * screen. It does not write to the database.
 *
 * @param int $courseid Course ID.
 * @param string $filepath Absolute path to the uploaded ODS file.
 * @param string $mode Import mode.
 * @return array<string, mixed> Parsed data with validation errors and warnings.
 */
function local_evalfp_ra_ce_import_parse_ods_file(int $courseid, string $filepath, string $mode): array {
    $rows = local_evalfp_ra_ce_import_read_ods($filepath);
    $parsed = local_evalfp_ra_ce_import_parse_rows($rows);
    return local_evalfp_ra_ce_import_validate_against_course($courseid, $parsed, $mode);
}

/**
 * Applies a validated RA/CE import to the course.
 *
 * Replace mode removes existing RA and CE records for the course before
 * inserting the reviewed data. Merge mode updates by code or creates records.
 *
 * @param int $courseid Course ID.
 * @param array<string, mixed> $parsed Validated import data.
 * @param string $mode Import mode.
 * @return array<string, int> Import counters.
 */
function local_evalfp_ra_ce_import_apply(int $courseid, array $parsed, string $mode): array {
    global $DB;

    $transaction = $DB->start_delegated_transaction();
    $now = time();
    $stats = [
        'rascreated' => 0,
        'rasupdated' => 0,
        'cescreated' => 0,
        'cesupdated' => 0,
        'rasdeleted' => 0,
    ];

    if ($mode === LOCAL_EVALFP_RA_CE_IMPORT_MODE_REPLACE) {
        $existingras = $DB->get_records('local_evalfp_course_ra', ['courseid' => $courseid], '', 'id');
        foreach ($existingras as $ra) {
            local_evalfp_delete_ra((int)$ra->id);
            $stats['rasdeleted']++;
        }
    }

    $existingras = $DB->get_records('local_evalfp_course_ra', ['courseid' => $courseid], '', '*');
    $rasbycode = [];
    foreach ($existingras as $ra) {
        $rasbycode[core_text::strtolower($ra->code)] = $ra;
    }

    foreach ($parsed['items'] as $item) {
        if ($item['type'] !== 'ra') {
            continue;
        }
        $key = core_text::strtolower($item['ra']);
        if (isset($rasbycode[$key])) {
            $ra = $rasbycode[$key];
            $ra->description = $item['description'];
            $ra->timemodified = $now;
            $DB->update_record('local_evalfp_course_ra', $ra);
            $stats['rasupdated']++;
            $rasbycode[$key] = $ra;
        } else {
            $ra = (object)[
                'courseid' => $courseid,
                'code' => $item['ra'],
                'description' => $item['description'],
                'weight' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $ra->id = $DB->insert_record('local_evalfp_course_ra', $ra);
            $rasbycode[$key] = $ra;
            $stats['rascreated']++;
        }
    }

    $cesbyra = [];
    if ($rasbycode) {
        $raids = array_map(static fn($ra): int => (int)$ra->id, $rasbycode);
        [$insql, $params] = $DB->get_in_or_equal($raids, SQL_PARAMS_NAMED, 'ra');
        $existingces = $DB->get_records_select('local_evalfp_course_ce', "courseraid $insql", $params, '', '*');
        foreach ($existingces as $ce) {
            $cesbyra[(int)$ce->courseraid][core_text::strtolower($ce->code)] = $ce;
        }
    }

    foreach ($parsed['items'] as $item) {
        if ($item['type'] !== 'ce') {
            continue;
        }
        $rakey = core_text::strtolower($item['ra']);
        if (empty($rasbycode[$rakey])) {
            continue;
        }
        $ra = $rasbycode[$rakey];
        $cekey = core_text::strtolower($item['ce']);
        if (!empty($cesbyra[(int)$ra->id][$cekey])) {
            $ce = $cesbyra[(int)$ra->id][$cekey];
            $ce->description = $item['description'];
            $ce->timemodified = $now;
            $DB->update_record('local_evalfp_course_ce', $ce);
            $stats['cesupdated']++;
        } else {
            $ce = (object)[
                'courseraid' => (int)$ra->id,
                'code' => $item['ce'],
                'description' => $item['description'],
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $ce->id = $DB->insert_record('local_evalfp_course_ce', $ce);
            $cesbyra[(int)$ra->id][$cekey] = $ce;
            $stats['cescreated']++;
        }
    }

    $transaction->allow_commit();
    return $stats;
}

/**
 * Stores RA/CE import preview data in the user session.
 *
 * @param int $courseid Course ID.
 * @param array<string, mixed> $parsed Parsed and validated import data.
 * @param string $mode Import mode.
 * @return string Preview token.
 */
function local_evalfp_ra_ce_import_store_preview(int $courseid, array $parsed, string $mode): string {
    global $SESSION;

    $token = random_string(32);
    if (empty($SESSION->local_evalfp_ra_ce_import) || !is_array($SESSION->local_evalfp_ra_ce_import)) {
        $SESSION->local_evalfp_ra_ce_import = [];
    }
    $SESSION->local_evalfp_ra_ce_import[$token] = [
        'courseid' => $courseid,
        'mode' => $mode,
        'parsed' => $parsed,
        'timecreated' => time(),
    ];

    return $token;
}

/**
 * Returns stored RA/CE import preview data.
 *
 * @param string $token Preview token.
 * @return array<string, mixed>|null Stored preview data, or null when the token is invalid.
 */
function local_evalfp_ra_ce_import_get_preview(string $token): ?array {
    global $SESSION;

    if (empty($SESSION->local_evalfp_ra_ce_import[$token])) {
        return null;
    }
    return $SESSION->local_evalfp_ra_ce_import[$token];
}

/**
 * Clears stored RA/CE import preview data.
 *
 * @param string $token Preview token.
 * @return void
 */
function local_evalfp_ra_ce_import_clear_preview(string $token): void {
    global $SESSION;

    if (!empty($SESSION->local_evalfp_ra_ce_import[$token])) {
        unset($SESSION->local_evalfp_ra_ce_import[$token]);
    }
}

/**
 * Renders RA/CE import preview results.
 *
 * @param array<string, mixed> $parsed Parsed import data.
 * @return string Rendered preview HTML.
 */
function local_evalfp_ra_ce_import_render_preview(array $parsed): string {
    ob_start();

    echo html_writer::start_div('table-responsive border rounded mb-3');
    echo html_writer::start_tag('table', ['class' => 'generaltable table table-hover mb-0']);
    echo html_writer::start_tag('thead', ['class' => 'thead-light']);
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('common_type', 'local_evalfp'), ['scope' => 'col']);
    echo html_writer::tag('th', get_string('common_ra_abbr', 'local_evalfp'), ['scope' => 'col']);
    echo html_writer::tag('th', get_string('common_ce_abbr', 'local_evalfp'), ['scope' => 'col']);
    echo html_writer::tag('th', get_string('common_description', 'local_evalfp'), ['scope' => 'col']);
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    foreach ($parsed['items'] as $item) {
        $typebadge = $item['type'] === 'ra' ? 'badge badge-primary' : 'badge badge-secondary';
        $racode = local_evalfp_format_ra_code((object)['code' => $item['ra']]);
        $cecode = $item['type'] === 'ce' ? local_evalfp_format_ce_code($item['ra'], $item['ce']) : '';
        echo html_writer::start_tag('tr');
        echo html_writer::tag(
            'td',
            html_writer::span(core_text::strtoupper($item['type']), $typebadge),
            ['class' => 'align-middle text-nowrap']
        );
        echo html_writer::tag('td', s($racode), ['class' => 'align-middle text-nowrap']);
        echo html_writer::tag('td', s($cecode), ['class' => 'align-middle text-nowrap']);
        echo html_writer::tag('td', format_string($item['description']), ['class' => 'align-middle']);
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();

    return ob_get_clean();
}

/**
 * Sends the RA/CE import template as an ODS file.
 *
 * @return void
 */
function local_evalfp_ra_ce_import_send_template(): void {
    global $CFG;

    require_once($CFG->libdir . '/odslib.class.php');

    $workbook = new MoodleODSWorkbook('-');
    $workbook->send(clean_filename(get_string('import_ra_ce_template_filename', 'local_evalfp')));
    $sheet = $workbook->add_worksheet('curriculum');

    $headers = local_evalfp_ra_ce_import_get_expected_headers();
    foreach ($headers as $col => $header) {
        $sheet->write_string(0, $col, $header, ['bold' => 1]);
    }

    $examples = [
        ['ra', '1', '', get_string('import_ra_ce_template_example_ra1', 'local_evalfp')],
        ['ce', '1', 'a', get_string('import_ra_ce_template_example_ce1a', 'local_evalfp')],
        ['ce', '1', 'b', get_string('import_ra_ce_template_example_ce1b', 'local_evalfp')],
        ['ra', '2', '', get_string('import_ra_ce_template_example_ra2', 'local_evalfp')],
        ['ce', '2', 'a', get_string('import_ra_ce_template_example_ce2a', 'local_evalfp')],
    ];

    foreach ($examples as $row => $values) {
        foreach ($values as $col => $value) {
            $sheet->write_string($row + 1, $col, $value);
        }
    }

    $sheet->set_column(0, 0, 12);
    $sheet->set_column(1, 1, 10);
    $sheet->set_column(2, 2, 10);
    $sheet->set_column(3, 3, 90);

    $workbook->close();
    exit;
}
