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
 * Assessment render helpers for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Renders the assessment summary table by learning outcome.
 *
 * @param int $courseid
 * @param int $evaluationid
 * @param array $users
 * @param array $ras
 * @param array $raresults
 * @param array $ratotals
 * @return string
 */
function local_evalfp_render_assessment_summary_table(
    int $courseid,
    int $evaluationid,
    array $users,
    array $ras,
    array $raresults,
    array $ratotals = []
): string {
    global $OUTPUT;

    ob_start();
    echo html_writer::start_div('table-responsive d-inline-block w-auto mw-100 border');
    echo html_writer::start_tag('table', ['class' => 'generaltable table-light mb-0 w-auto']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('page_assessment_user_column', 'local_evalfp'), ['scope' => 'col']);
    echo html_writer::tag('th', html_writer::span(get_string('actions'), 'accesshide'), [
        'scope' => 'col',
        'class' => 'text-center',
    ]);
    foreach ($ras as $ra) {
        $label = local_evalfp_format_ra_label($ra->code);
        echo html_writer::tag('th', html_writer::span($label, 'badge badge-primary', [
            'title' => format_string($ra->description),
        ]), [
            'class' => 'text-center align-middle text-nowrap px-4',
            'scope' => 'col',
            'title' => s($ra->description),
        ]);
    }
    echo html_writer::tag('th', get_string('total', 'grades'), [
        'scope' => 'col',
        'class' => 'text-center align-middle text-nowrap px-4 border-left',
    ]);
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($users as $u) {
        echo html_writer::start_tag('tr');
        $userlink = html_writer::link(new moodle_url('/user/view.php', ['id' => $u->id, 'course' => $courseid]), fullname($u));
        $reporturl = new moodle_url('/local/evalfp/course/assessment/user.php', [
            'courseid' => $courseid,
            'userid' => $u->id,
            'evaluationid' => $evaluationid,
        ]);
        $useractions = local_evalfp_render_course_action_menu([
            [
                'url' => $reporturl,
                'icon' => null,
                'text' => get_string('page_assessment_individual_report_action', 'local_evalfp'),
            ],
        ]);
        $usercell = html_writer::div(
            $OUTPUT->user_picture($u, ['size' => 35]) . html_writer::div($userlink, 'ml-2 text-truncate'),
            'd-flex align-items-center overflow-hidden'
        );
        echo html_writer::tag('th', $usercell, ['class' => 'align-middle font-weight-normal', 'scope' => 'row']);
        echo html_writer::tag('td', $useractions, ['class' => 'align-middle text-center']);

        foreach ($ras as $ra) {
            $val = $raresults[$u->id][$ra->code] ?? null;
            echo html_writer::tag(
                'td',
                local_evalfp_format_grade_badge($val),
                ['class' => 'align-middle text-center text-nowrap px-4']
            );
        }
        $total = $ratotals[$u->id] ?? null;
        echo html_writer::tag(
            'td',
            local_evalfp_format_grade_badge($total),
            ['class' => 'align-middle text-center text-nowrap px-4 border-left']
        );
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
    return ob_get_clean();
}

/**
 * Renders the individual assessment report from a prepared view model.
 *
 * @param int $courseid Course ID.
 * @param stdClass $course Course record.
 * @param stdClass $user User record.
 * @param int $userid User ID.
 * @param array<string, mixed> $vm Prepared individual report view model.
 * @return string Rendered individual report HTML.
 */
function local_evalfp_render_individual_assessment_report_from_vm(
    int $courseid,
    stdClass $course,
    stdClass $user,
    int $userid,
    array $vm
): string {
    global $OUTPUT;

    $evaluationid = (int)$vm['evaluationid'];
    $selectedparams = $evaluationid > 0 ? ['evaluationid' => $evaluationid] : [];
    $backurl = new moodle_url('/local/evalfp/course/assessment/index.php', ['courseid' => $courseid] + $selectedparams);

    $baseurluser = new moodle_url('/local/evalfp/course/assessment/user.php', ['courseid' => $courseid] + $selectedparams);
    $selectuser = new single_select($baseurluser, 'userid', $vm['users'], $userid, null);
    $selectuser->set_label(get_string('common_user', 'local_evalfp'), ['class' => 'mr-2']);

    $baseurleval = new moodle_url('/local/evalfp/course/assessment/user.php', ['courseid' => $courseid, 'userid' => $userid]);
    $selecteval = new single_select($baseurleval, 'evaluationid', $vm['evaloptions'], $evaluationid, null);
    $selecteval->set_label(get_string('common_evaluation_period', 'local_evalfp'), ['class' => 'mr-2']);

    $controls = html_writer::tag('div', $OUTPUT->render($selectuser), ['class' => 'navitem align-self-center']) .
        html_writer::tag('div', '', ['class' => 'navitem-divider']) .
        html_writer::tag('div', $OUTPUT->render($selecteval), ['class' => 'navitem align-self-center']);

    $actions = html_writer::link($backurl, get_string('common_back', 'local_evalfp'), ['class' => 'btn btn-outline-secondary']);

    ob_start();
    local_evalfp_start_course_layout($courseid, 'assessment', $actions, $controls);
    $out = ob_get_clean();

    if (!$vm['validevaluation']) {
        $out .= $OUTPUT->notification(
            get_string('error_invalid_evaluation', 'local_evalfp'),
            \core\output\notification::NOTIFY_WARNING
        );
    }
    if (!$vm['evaluations']) {
        $out .= $OUTPUT->notification(
            get_string('page_evaluations_none', 'local_evalfp'),
            \core\output\notification::NOTIFY_WARNING
        );
        ob_start();
        local_evalfp_end_course_layout();
        $out .= ob_get_clean();
        return $out;
    }
    if (!$vm['hasselection']) {
        $out .= $OUTPUT->notification(
            get_string('page_assessment_individual_select_evaluation', 'local_evalfp'),
            \core\output\notification::NOTIFY_INFO
        );
        ob_start();
        local_evalfp_end_course_layout();
        $out .= ob_get_clean();
        return $out;
    }
    if (empty($vm['hasras'])) {
        $out .= $OUTPUT->notification(
            get_string('page_curriculum_no_ra', 'local_evalfp'),
            \core\output\notification::NOTIFY_WARNING
        );
        ob_start();
        local_evalfp_end_course_layout();
        $out .= ob_get_clean();
        return $out;
    }

    $evaluationlabel = $vm['evaluationlabel'];
    $evaluationdetails = html_writer::span(s($evaluationlabel));
    $evaluationbadge = '';
    if (!empty($vm['evaluation'])) {
        $evaluationtype = (int)$vm['evaluation']->type;
        $evaluationdates = local_evalfp_format_evaluation_compact_date_range($vm['evaluation']);
        if ($evaluationdates !== '') {
            $evaluationdetails .= html_writer::span(' | ' . s($evaluationdates), 'ml-1');
        }
        $evaluationbadge = html_writer::span(
            local_evalfp_get_evaluation_type_label($evaluationtype),
            local_evalfp_get_evaluation_type_badge_class($evaluationtype) . ' ml-2'
        );
    }

    $contextinfo = html_writer::div(
        html_writer::tag('h4', fullname($user), ['class' => 'mb-2']) .
            html_writer::div(
                html_writer::span(get_string('course'), 'small text-muted font-weight-bold text-uppercase mr-2') .
                    html_writer::span(format_string($course->fullname)),
                'mb-1'
            ) .
            html_writer::div(
                html_writer::span(
                    get_string('common_evaluation_period', 'local_evalfp'),
                    'small text-muted font-weight-bold text-uppercase mr-2'
                ) .
                    $evaluationdetails .
                    $evaluationbadge
            ) .
            html_writer::div(
                html_writer::span(
                    get_string('page_assessment_individual_total', 'local_evalfp'),
                    'small text-muted font-weight-bold text-uppercase mr-2'
                ) .
                    html_writer::span(
                        local_evalfp_format_grade_summary($vm['total'] ?? null),
                        local_evalfp_get_grade_text_class($vm['total'] ?? null) . ' font-weight-bold'
                    ),
                'mt-1'
            ),
        'ml-3 flex-grow-1'
    );
    $userinfo = html_writer::div(
        $OUTPUT->user_picture($user, ['size' => 35, 'link' => false]) . $contextinfo,
        'd-flex align-items-start'
    );

    $achievementinfo = '';
    if ($vm['ras']) {
        $achievementinfo .= html_writer::start_div('d-flex align-items-center mb-1 mw-50');
        $achievementinfo .= html_writer::span('RA', 'badge badge-primary mr-3 invisible');
        $achievementinfo .= html_writer::start_div('d-flex justify-content-between flex-grow-1 small text-muted px-2');
        foreach ([0, 25, 50, 75, 100] as $tick) {
            $achievementinfo .= html_writer::span($tick . '%');
        }
        $achievementinfo .= html_writer::end_div();
        $achievementinfo .= html_writer::span('100%', 'ml-3 text-nowrap font-weight-bold invisible');
        $achievementinfo .= html_writer::end_div();
        foreach ($vm['ras'] as $ra) {
            $value = $ra['media'];
            $state = local_evalfp_get_grade_state($value);
            $barclass = $state === null ? 'bg-light' : 'bg-' . $state;
            $barwidth = $value === null ? 0 : max(0, min(100, round($value, 1)));
            $label = local_evalfp_format_ra_label($ra['code']);
            $bar = html_writer::div(
                '',
                'progress-bar ' . $barclass,
                [
                    'role' => 'progressbar',
                    'style' => 'width: ' . $barwidth . '%;',
                    'aria-valuenow' => $barwidth,
                    'aria-valuemin' => 0,
                    'aria-valuemax' => 100,
                    'title' => local_evalfp_format_percent_stable($value),
                ]
            );
            $achievementinfo .= html_writer::start_div('d-flex align-items-center mb-2');
            $achievementinfo .= html_writer::span($label, 'badge badge-primary mr-2');
            $achievementinfo .= html_writer::div($bar, 'progress flex-grow-1');
            $achievementinfo .= html_writer::span(
                local_evalfp_format_percent_stable($value),
                local_evalfp_get_grade_text_class($value) . ' mx-2 text-nowrap font-weight-bold'
            );
            $achievementinfo .= html_writer::end_div();
        }
    }

    $out .= html_writer::start_div('border rounded p-3 mb-3');
    $out .= html_writer::start_div('row align-items-start');
    $out .= html_writer::div($userinfo, 'col-lg-6 mb-3 mb-md-0');
    $out .= html_writer::div($achievementinfo, 'col-lg-6');
    $out .= html_writer::end_div();
    $out .= html_writer::end_div();

    foreach ($vm['ras'] as $ra) {
        $out .= html_writer::start_div('card mb-3');
        $out .= html_writer::start_div('card-header');
        $out .= html_writer::span(local_evalfp_format_ra_label($ra['code']), 'badge badge-primary mr-2') .
            html_writer::span(format_string($ra['description']), 'font-weight-bold');
        $out .= html_writer::end_div();

        if ($ra['empty']) {
            $out .= html_writer::div(
                get_string('page_assessment_no_evidences_current_evaluation', 'local_evalfp'),
                'text-muted small p-3'
            );
        } else {
            $out .= html_writer::start_div('list-group list-group-flush');
            foreach ($ra['ces'] as $ce) {
                $weightlabel = local_evalfp_format_percent($ce['weight']);
                $ceheading = html_writer::span(s($ce['code']), 'badge badge-secondary mr-2') .
                    html_writer::span(s($ce['description'])) .
                    html_writer::span(s($weightlabel), 'badge badge-light border ml-2');
                $cegrade = html_writer::span(
                    local_evalfp_format_grade_out_of_ten($ce['media']),
                    local_evalfp_get_grade_text_class($ce['media']) . ' float-right ml-3 text-nowrap'
                );
                $summary = $cegrade . $ceheading;

                if (empty($ce['evidences'])) {
                    $out .= html_writer::div($summary, 'list-group-item clearfix');
                    continue;
                }

                // Render the collapsible details for evidences.
                $out .= html_writer::start_tag('details', ['class' => 'list-group-item py-2']);
                $out .= html_writer::tag('summary', $summary, ['class' => 'font-weight-normal clearfix']);
                $out .= html_writer::start_tag('ul', ['class' => 'list-unstyled my-2 ml-4']);
                foreach ($ce['evidences'] as $evidence) {
                    $evidencecontent = $evidence['iconhtml'] . html_writer::span($evidence['linkhtml'], 'small');
                    $out .= html_writer::tag(
                        'li',
                        html_writer::div($evidencecontent . $evidence['rawgradehtml'], 'd-flex align-items-center'),
                        ['class' => 'my-1']
                    );
                }
                $out .= html_writer::end_tag('ul');
                $out .= html_writer::end_tag('details');
            }

            $out .= html_writer::start_div('list-group-item d-flex justify-content-between font-weight-bold');
            $out .= html_writer::span(
                get_string('page_assessment_ra_total', 'local_evalfp', local_evalfp_format_ra_label($ra['code']))
            );
            $ratotal = local_evalfp_format_grade_summary($ra['media']);
            $out .= html_writer::span($ratotal, local_evalfp_get_grade_text_class($ra['media']) . ' text-nowrap');
            $out .= html_writer::end_div();
            $out .= html_writer::end_div();
        }
        $out .= html_writer::end_div();
    }

    ob_start();
    local_evalfp_end_course_layout();
    $out .= ob_get_clean();
    return $out;
}


/**
 * Builds and renders the individual assessment report.
 *
 * @param int $courseid Course ID.
 * @param context_course $context Course context.
 * @param stdClass $course Course record.
 * @param stdClass $user User record.
 * @param int $userid User ID.
 * @param int $evaluationid Selected evaluation ID.
 * @return string Rendered individual report HTML.
 */
function local_evalfp_render_individual_assessment_report(
    int $courseid,
    context_course $context,
    stdClass $course,
    stdClass $user,
    int $userid,
    int $evaluationid
): string {
    $vm = local_evalfp_build_individual_assessment_report_vm($courseid, $context, $userid, $evaluationid);
    return local_evalfp_render_individual_assessment_report_from_vm($courseid, $course, $user, $userid, $vm);
}
