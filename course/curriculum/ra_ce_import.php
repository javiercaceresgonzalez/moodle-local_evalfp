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
 * Course RA and CE import workflow.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
require_once($CFG->dirroot . '/local/evalfp/lib.php');
require_once($CFG->libdir . '/formslib.php');

// Page parameters.
$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$token = optional_param('token', '', PARAM_ALPHANUMEXT);

// Course access.
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/evalfp:useincourse', $context);

// Template download endpoint.
if ($action === 'template') {
    local_evalfp_ra_ce_import_send_template();
}

// Page URLs.
$url = new moodle_url('/local/evalfp/course/curriculum/ra_ce_import.php', ['courseid' => $courseid]);
$listurl = new moodle_url('/local/evalfp/course/curriculum/index.php', ['courseid' => $courseid]);

// Page setup.
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(get_string('import_ra_ce_title', 'local_evalfp'));
$PAGE->navigation->override_active_url(new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid]));
$PAGE->navbar->add(
    get_string('pluginname', 'local_evalfp'),
    new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid])
);
$PAGE->navbar->add(
    get_string('page_curriculum_ra_ce_title', 'local_evalfp'),
    $listurl
);
$PAGE->navbar->add(
    get_string('import_ra_ce_title', 'local_evalfp')
);

// Form setup.
$mform = new \local_evalfp\form\ra_ce_import(null, ['courseid' => $courseid]);
$mform->set_data(['courseid' => $courseid]);

// Form handling.
if ($mform->is_cancelled()) {
    redirect($listurl);
}

// Apply a confirmed preview.
if ($action === 'confirm') {
    require_sesskey();
    $preview = local_evalfp_ra_ce_import_get_preview($token);
    if (!$preview || (int)$preview['courseid'] !== $courseid) {
        throw new moodle_exception('error_invalid_record', 'local_evalfp');
    }
    if (!empty($preview['parsed']['errors'])) {
        throw new moodle_exception('error_invalid_record', 'local_evalfp');
    }

    $stats = local_evalfp_ra_ce_import_apply($courseid, $preview['parsed'], $preview['mode']);
    local_evalfp_ra_ce_import_clear_preview($token);

    \core\notification::success(get_string('import_ra_ce_success', 'local_evalfp', (object)$stats));
    redirect($listurl);
}

// Page actions.
$pageactions = html_writer::link(
    new moodle_url('/local/evalfp/course/curriculum/ra_ce_import.php', ['courseid' => $courseid, 'action' => 'template']),
    get_string('import_ra_ce_download_template', 'local_evalfp'),
    ['class' => 'btn btn-outline-secondary']
);
local_evalfp_start_course_layout($courseid, 'results', $pageactions);

// Build the import preview before writing any data.
if ($data = $mform->get_data()) {
    $filepath = $mform->save_temp_file('importfile');
    if (!$filepath) {
        echo $OUTPUT->notification(
            get_string('import_ra_ce_error_file_not_found', 'local_evalfp'),
            \core\output\notification::NOTIFY_ERROR
        );
        $mform->display();
    } else {
        try {
            $mode = $data->mode ?? LOCAL_EVALFP_RA_CE_IMPORT_MODE_MERGE;
            $parsed = local_evalfp_ra_ce_import_parse_ods_file($courseid, $filepath, $mode);
            @unlink($filepath);
            $token = local_evalfp_ra_ce_import_store_preview($courseid, $parsed, $mode);

            echo html_writer::div(get_string('import_ra_ce_review_help', 'local_evalfp'), 'text-muted mb-3');
            $modeclass = $mode === LOCAL_EVALFP_RA_CE_IMPORT_MODE_REPLACE ? 'alert alert-warning' : 'alert alert-info';
            echo html_writer::div(get_string('import_ra_ce_selected_mode_' . $mode, 'local_evalfp'), $modeclass);

            foreach ($parsed['warnings'] as $warning) {
                echo $OUTPUT->notification($warning, \core\output\notification::NOTIFY_WARNING);
            }
            foreach ($parsed['errors'] as $error) {
                echo $OUTPUT->notification($error, \core\output\notification::NOTIFY_ERROR);
            }

            echo local_evalfp_ra_ce_import_render_preview($parsed);

            echo html_writer::start_div('d-flex justify-content-end');
            echo html_writer::link($url, get_string('back'), ['class' => 'btn btn-secondary mr-2']);
            if (!$parsed['errors']) {
                echo html_writer::start_tag('form', ['method' => 'post', 'action' => new moodle_url(
                    '/local/evalfp/course/curriculum/ra_ce_import.php',
                    ['courseid' => $courseid, 'action' => 'confirm']
                )]);
                echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
                echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'token', 'value' => $token]);
                echo html_writer::tag(
                    'button',
                    get_string('import_ra_ce_confirm', 'local_evalfp'),
                    ['type' => 'submit', 'class' => 'btn btn-primary']
                );
                echo html_writer::end_tag('form');
            }
            echo html_writer::end_div();
        } catch (Throwable $e) {
            if (!empty($filepath) && file_exists($filepath)) {
                @unlink($filepath);
            }
            echo $OUTPUT->notification(
                get_string('import_ra_ce_error_unreadable', 'local_evalfp', $e->getMessage()),
                \core\output\notification::NOTIFY_ERROR
            );
            $mform->display();
        }
    }
} else {
    // Initial upload screen.
    echo html_writer::div(get_string('import_ra_ce_intro', 'local_evalfp'), 'text-muted mb-3');
    echo html_writer::start_div('card mb-3');
    echo html_writer::div(get_string('import_ra_ce_format_title', 'local_evalfp'), 'card-header font-weight-bold');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', get_string('import_ra_ce_format_help', 'local_evalfp'), ['class' => 'mb-2']);
    echo html_writer::start_tag('ul', ['class' => 'mb-0']);
    echo html_writer::tag('li', html_writer::tag(
        'code',
        'type'
    ) . ': ' . get_string('import_ra_ce_column_type_help', 'local_evalfp'));
    echo html_writer::tag('li', html_writer::tag(
        'code',
        'ra'
    ) . ': ' . get_string('import_ra_ce_column_ra_help', 'local_evalfp'));
    echo html_writer::tag('li', html_writer::tag(
        'code',
        'ce'
    ) . ': ' . get_string('import_ra_ce_column_ce_help', 'local_evalfp'));
    echo html_writer::tag('li', html_writer::tag(
        'code',
        'description'
    ) . ': ' . get_string('import_ra_ce_column_description_help', 'local_evalfp'));
    echo html_writer::end_tag('ul');
    echo html_writer::end_div();
    echo html_writer::end_div();
    $mform->display();
}

local_evalfp_end_course_layout();
