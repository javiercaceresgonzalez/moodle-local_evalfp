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
 * Formatting helpers for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Formats a nullable percentage value.
 *
 * @param float|null $val
 * @return string
 */
function local_evalfp_format_percent(?float $val): string {
    if ($val === null) {
        return '&ndash;';
    }
    return rtrim(rtrim(sprintf('%.1f', $val), '0'), '.') . '%';
}

/**
 * Formats a nullable percentage value with a stable one-decimal layout.
 *
 * @param float|null $val
 * @return string
 */
function local_evalfp_format_percent_stable(?float $val): string {
    if ($val === null) {
        return '&ndash;';
    }
    $rounded = round($val, 1);
    if ($rounded === 100.0) {
        return '100%';
    }
    return sprintf('%.1f', $rounded) . '%';
}

/**
 * Returns the semantic state for a nullable achievement value.
 *
 * @param float|null $val
 * @return string|null
 */
function local_evalfp_get_grade_state(?float $val): ?string {
    if ($val === null) {
        return null;
    }
    if ($val >= 60) {
        return 'success';
    }
    if ($val >= 40) {
        return 'warning';
    }
    return 'danger';
}

/**
 * Returns the Bootstrap text class for an achievement value.
 *
 * @param float|null $val
 * @return string
 */
function local_evalfp_get_grade_text_class(?float $val): string {
    $state = local_evalfp_get_grade_state($val);
    return $state === null ? 'text-muted' : 'text-' . $state;
}

/**
 * Returns the chart colour for an achievement value.
 *
 * @param float|null $val
 * @return string
 */
function local_evalfp_get_grade_chart_color(?float $val): string {
    $state = local_evalfp_get_grade_state($val);
    $colors = [
        'success' => '#8fd19e',
        'warning' => '#ffda6a',
        'danger' => '#f1aeb5',
    ];
    return $state === null ? '#e9ecef' : $colors[$state];
}

/**
 * Formats an achievement value for display.
 *
 * @param float|null $val
 * @return string
 */
function local_evalfp_format_grade_badge(?float $val): string {
    if ($val === null) {
        return html_writer::span('&ndash;', 'text-muted');
    }
    return html_writer::span(local_evalfp_format_percent($val), local_evalfp_get_grade_text_class($val) . ' font-weight-bold');
}

/**
 * Formats an achievement value as percentage and ten-point grade.
 *
 * @param float|null $val Percentage achievement value.
 * @return string Formatted grade summary.
 */
function local_evalfp_format_grade_summary(?float $val): string {
    if ($val === null) {
        return '&ndash;';
    }
    return local_evalfp_format_percent($val) . ' (' . local_evalfp_format_grade_out_of_ten($val) . ')';
}

/**
 * Formats a nullable achievement value on a ten-point scale.
 *
 * @param float|null $val Percentage achievement value.
 * @return string Formatted ten-point grade or dash.
 */
function local_evalfp_format_grade_out_of_ten(?float $val): string {
    if ($val === null) {
        return '&ndash;';
    }
    $grade = $val / 10;
    return rtrim(rtrim(sprintf('%.2f', $grade), '0'), '.') . '/10';
}

/**
 * Formats a raw grade value and maximum grade.
 *
 * @param mixed $finalgrade Raw final grade value.
 * @param float|null $grademax Maximum grade configured for the grade item.
 * @param float|null $achievement Normalised achievement percentage.
 * @return string Formatted raw grade.
 */
function local_evalfp_format_raw_grade($finalgrade, ?float $grademax, ?float $achievement = null): string {
    $attributes = ['class' => 'badge badge-light border ml-2'];
    if ($achievement !== null) {
        $attributes['title'] = get_string(
            'page_assessment_normalised_achievement',
            'local_evalfp',
            local_evalfp_format_percent($achievement)
        );
    }

    if ($finalgrade === null || $grademax === null || $grademax <= 0) {
        $attributes['title'] = get_string('page_assessment_no_grade', 'local_evalfp');
        return html_writer::span('&ndash;', '', $attributes);
    }
    $final = rtrim(rtrim(sprintf('%.2f', (float)$finalgrade), '0'), '.');
    $max = rtrim(rtrim(sprintf('%.2f', $grademax), '0'), '.');
    return html_writer::span(s($final . '/' . $max), '', $attributes);
}

/**
 * Formats a learning outcome code for display.
 *
 * @param string $racode Raw learning outcome code.
 * @return string Localised learning outcome label.
 */
function local_evalfp_format_ra_label(string $racode): string {
    $raclean = preg_replace('/^(RA|LO)/i', '', $racode);
    return get_string('common_ra_abbr', 'local_evalfp') . $raclean;
}

/**
 * Formats an assessment criterion code for display.
 *
 * @param string $racode Raw learning outcome code.
 * @param string $cecode Raw assessment criterion code.
 * @return string Localised assessment criterion label.
 */
function local_evalfp_format_ce_code(string $racode, string $cecode): string {
    $raclean = preg_replace('/^(RA|LO)/i', '', $racode);
    $ceclean = preg_replace('/^(CE|AC)/i', '', $cecode);
    return get_string('common_ce_abbr', 'local_evalfp') . $raclean . $ceclean;
}

/**
 * Formats an evidence item link.
 *
 * @param stdClass $evidence
 * @param array $attributes
 * @return string
 */
function local_evalfp_format_evidence_link(stdClass $evidence, array $attributes = []): string {
    $attributes = $attributes + ['class' => 'text-truncate d-inline-block align-middle', 'title' => $evidence->label];
    $label = s($evidence->label);
    if (!empty($evidence->url)) {
        return html_writer::link($evidence->url, $label, $attributes);
    }
    return html_writer::span($label, $attributes['class'] ?? '', $attributes);
}
