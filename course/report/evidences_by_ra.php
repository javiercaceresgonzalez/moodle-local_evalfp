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
 * Course evidence report grouped by RA.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/local/evalfp/lib.php');

// Page parameters.
$courseid = required_param('courseid', PARAM_INT);
// Course access.
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/evalfp:useincourse', $context);
$pagetitle = get_string('report_evidences_by_ra_title', 'local_evalfp');

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/evalfp/course/report/evidences_by_ra.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(
    get_string('pluginname', 'local_evalfp'),
    new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid])
);
$PAGE->navbar->add($pagetitle);

local_evalfp_start_course_layout($courseid, 'report_ra');

// Load report data.
$reportdata = local_evalfp_get_report_evidences_by_ra($courseid);
$ras = $reportdata['ras'];
$evidences = $reportdata['evidences'];
$byra = $reportdata['byra'];

// Render grouped evidence lists.
if (!$ras) {
    echo $OUTPUT->notification(get_string('page_curriculum_no_ra', 'local_evalfp'), \core\output\notification::NOTIFY_INFO);
} else {
    foreach ($ras as $ra) {
        $displaycode = local_evalfp_format_ra_label($ra->code);
        $weightinfo = !empty($ra->weight)
            ? html_writer::span(
                rtrim(rtrim(sprintf('%.2f', (float)$ra->weight), '0'), '.') . '%',
                'badge badge-light border ml-2'
            )
            : '';

        echo html_writer::start_div('list-group mb-3');
        echo html_writer::start_div(
            'list-group-item bg-light d-flex align-items-start justify-content-between'
        );
        echo html_writer::div(
            html_writer::span(format_string($displaycode), 'badge badge-primary mr-2') .
                html_writer::span(format_string($ra->description), 'font-weight-bold'),
            'pr-3'
        );
        echo html_writer::div($weightinfo, 'text-nowrap');
        echo html_writer::end_div();

        $links = $byra[(int)$ra->id] ?? [];
        if (!$links) {
            echo html_writer::div(
                get_string('report_no_evidences_linked_ra', 'local_evalfp'),
                'list-group-item text-muted small'
            );
        } else {
            foreach ($evidences as $gradeitemid => $evidence) {
                if (!array_key_exists($gradeitemid, $links)) {
                    continue;
                }

                $weight = $links[$gradeitemid];
                $weightbadge = ($weight !== null && $weight > 0)
                    ? html_writer::span(
                        rtrim(rtrim(sprintf('%.2f', $weight), '0'), '.') . '%',
                        'badge badge-light border'
                    )
                    : '';

                $evidenceattributes = [
                    'class' => 'text-truncate d-inline-block align-middle',
                    'title' => $evidence->label,
                ];
                $evidencelink = local_evalfp_format_evidence_link($evidence, $evidenceattributes);
                $evidencecontent = html_writer::div(
                    $evidence->iconhtml . html_writer::div($evidencelink, 'text-truncate ml-2'),
                    'd-flex align-items-center flex-grow-1 overflow-hidden'
                );

                echo html_writer::div(
                    $evidencecontent . html_writer::div($weightbadge, 'text-nowrap ml-3'),
                    'list-group-item d-flex align-items-center justify-content-between'
                );
            }
        }
        echo html_writer::end_div();
    }
}
local_evalfp_end_course_layout();
