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
 * Course evidence report grouped by CE.
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
$pagetitle = get_string('report_evidences_by_ce_title', 'local_evalfp');
// Page setup.
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/evalfp/course/report/evidences_by_ce.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($pagetitle);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(
    get_string('pluginname', 'local_evalfp'),
    new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid])
);
$PAGE->navbar->add($pagetitle);

local_evalfp_start_course_layout($courseid, 'report_ce');

// Load report data.
$reportdata = local_evalfp_get_report_evidences_by_ce($courseid);
$ces = $reportdata['ces'];
$evidences = $reportdata['evidences'];
$byce = $reportdata['byce'];

// Render grouped evidence lists.
if (!$ces) {
    echo $OUTPUT->notification(get_string('page_curriculum_no_ce_for_ra', 'local_evalfp'), \core\output\notification::NOTIFY_INFO);
} else {
    foreach ($ces as $cen) {
        $code = local_evalfp_format_ce_code($cen->racode, $cen->code);
        $weightinfo = html_writer::span(
            local_evalfp_format_percent((float)($cen->weight ?? 0)),
            'badge badge-light border ml-2'
        );

        $linked = $byce[(int)$cen->id] ?? [];
        $headerclass = 'list-group-item d-flex align-items-start justify-content-between ' .
            ($linked ? 'bg-light' : 'bg-warning');

        echo html_writer::start_div('list-group mb-3');
        echo html_writer::start_div($headerclass);
        echo html_writer::div(
            html_writer::span($code, 'badge badge-secondary mr-2') .
                html_writer::span(format_string($cen->description), 'font-weight-bold'),
            'pr-3'
        );
        echo html_writer::div($weightinfo, 'text-nowrap');
        echo html_writer::end_div();

        if (!$linked) {
            echo html_writer::div(
                get_string('report_no_evidences_linked_ce', 'local_evalfp'),
                'list-group-item text-muted small'
            );
        } else {
            foreach ($linked as $gradeitemid) {
                $evidence = $evidences[$gradeitemid];
                $evidenceattributes = [
                    'class' => 'text-truncate d-inline-block align-middle',
                    'title' => $evidence->label,
                ];
                $evidencelink = local_evalfp_format_evidence_link($evidence, $evidenceattributes);
                echo html_writer::div(
                    html_writer::div(
                        $evidence->iconhtml . html_writer::div($evidencelink, 'text-truncate ml-2'),
                        'd-flex align-items-center flex-grow-1 overflow-hidden'
                    ),
                    'list-group-item d-flex align-items-center'
                );
            }
        }
        echo html_writer::end_div();
    }
}
local_evalfp_end_course_layout();
