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
 * Course navigation helpers for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns the EvalFP course menu structure.
 *
 * Menu entries are grouped in the same order used by the tertiary navigation:
 * assessment, curriculum configuration and reports.
 *
 * @param int $courseid Course ID.
 * @return array<string, mixed> Grouped menu definition.
 */
function local_evalfp_get_course_menu_items(int $courseid): array {
    return [
        [
            'heading' => get_string('nav_assessment', 'local_evalfp'),
            'items' => [
                [
                    'key' => 'assessment',
                    'label' => get_string('nav_assessment_by_ra', 'local_evalfp'),
                    'url' => new moodle_url('/local/evalfp/course/assessment/index.php', ['courseid' => $courseid]),
                ],
            ],
        ],
        [
            'heading' => get_string('nav_curriculum', 'local_evalfp'),
            'items' => [
                [
                    'key' => 'results',
                    'label' => get_string('nav_curriculum_ra_ce', 'local_evalfp'),
                    'url' => new moodle_url('/local/evalfp/course/curriculum/index.php', ['courseid' => $courseid]),
                ],
                [
                    'key' => 'ra_weights',
                    'label' => get_string('page_ra_weights_title', 'local_evalfp'),
                    'url' => new moodle_url('/local/evalfp/course/curriculum/ra_weights.php', ['courseid' => $courseid]),
                ],
                [
                    'key' => 'ce_weights',
                    'label' => get_string('page_ce_weights_title', 'local_evalfp'),
                    'url' => new moodle_url('/local/evalfp/course/curriculum/ce_weights.php', ['courseid' => $courseid]),
                ],
                [
                    'key' => 'evaluations',
                    'label' => get_string('page_evaluations_title', 'local_evalfp'),
                    'url' => new moodle_url('/local/evalfp/course/curriculum/evaluations.php', ['courseid' => $courseid]),
                ],
                [
                    'key' => 'evidences',
                    'label' => get_string('page_evidences_title', 'local_evalfp'),
                    'url' => new moodle_url('/local/evalfp/course/curriculum/evidences.php', ['courseid' => $courseid]),
                ],
            ],
        ],
        [
            'heading' => get_string('nav_reports', 'local_evalfp'),
            'items' => [
                [
                    'key' => 'report_ra',
                    'label' => get_string('nav_report_evidences_by_ra', 'local_evalfp'),
                    'url' => new moodle_url('/local/evalfp/course/report/evidences_by_ra.php', ['courseid' => $courseid]),
                ],
                [
                    'key' => 'report_ce',
                    'label' => get_string('nav_report_evidences_by_ce', 'local_evalfp'),
                    'url' => new moodle_url('/local/evalfp/course/report/evidences_by_ce.php', ['courseid' => $courseid]),
                ],
                [
                    'key' => 'report_evaluation',
                    'label' => get_string('nav_report_evidences_by_evaluation', 'local_evalfp'),
                    'url' => new moodle_url('/local/evalfp/course/report/evidences_by_evaluation.php', ['courseid' => $courseid]),
                ],
            ],
        ],
        [
            'heading' => get_string('nav_settings', 'local_evalfp'),
            'items' => [
                [
                    'key' => 'activity_settings',
                    'label' => get_string('page_activity_settings_title', 'local_evalfp'),
                    'url' => new moodle_url('/local/evalfp/course/settings/activity.php', ['courseid' => $courseid]),
                ],
            ],
        ],
    ];
}

/**
 * Returns the active EvalFP course menu item.
 *
 * If no active key is supplied, the current page URL is matched against the
 * configured course menu so the dropdown reflects the page being viewed.
 *
 * @param int $courseid Course ID.
 * @param string $active Explicit active menu key, or empty to infer it from the URL.
 * @return array<string, mixed> Active menu item definition.
 */
function local_evalfp_get_active_course_menu_item(int $courseid, string $active = ''): array {
    if ($active === 'home' || $active === '') {
        return [
            'key' => 'home',
            'label' => get_string('pluginname', 'local_evalfp'),
            'url' => new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid]),
        ];
    }

    $fallback = null;

    foreach (local_evalfp_get_course_menu_items($courseid) as $group) {
        foreach ($group['items'] as $item) {
            $fallback = $fallback ?? $item;
            if ($item['key'] === $active) {
                return $item;
            }
        }
    }

    return $fallback ?? [
        'key' => 'home',
        'label' => get_string('pluginname', 'local_evalfp'),
        'url' => new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid]),
    ];
}

/**
 * Renders the EvalFP tertiary navigation.
 *
 * The navigation mirrors Moodle gradebook pages: a page selector on the left,
 * optional filters in the middle and page actions on the right.
 *
 * @param int $courseid Course ID.
 * @param string $active Active menu key.
 * @param string $actions HTML action controls displayed on the right.
 * @param string $controls HTML filter controls displayed after the menu selector.
 * @return string Rendered tertiary navigation HTML.
 */
function local_evalfp_render_course_tertiary_navigation(
    int $courseid,
    string $active = '',
    string $actions = '',
    string $controls = ''
): string {
    global $OUTPUT;

    $menu = [
        [
            get_string('pluginname', 'local_evalfp') => [
                (new moodle_url('/local/evalfp/course/index.php', ['courseid' => $courseid]))->out(false) =>
                    get_string('pluginname', 'local_evalfp'),
            ],
        ],
    ];
    foreach (local_evalfp_get_course_menu_items($courseid) as $group) {
        $options = [];
        foreach ($group['items'] as $item) {
            $options[$item['url']->out(false)] = $item['label'];
        }
        $menu[][$group['heading']] = $options;
    }

    $selected = local_evalfp_get_active_course_menu_item($courseid, $active);
    $selectmenu = new \core\output\select_menu('local_evalfp_navigation', $menu, $selected['url']->out(false));
    $selectmenu->set_label(get_string('pluginname', 'local_evalfp'), ['class' => 'sr-only']);

    $selector = $OUTPUT->render_from_template('core/tertiary_navigation_selector', $selectmenu->export_for_template($OUTPUT));

    $pluginlabel = html_writer::span(get_string('pluginname', 'local_evalfp'), 'text-muted font-weight-normal');
    $content = html_writer::tag('div', $pluginlabel, ['class' => 'navitem align-self-center']);
    $content .= html_writer::tag('div', '', ['class' => 'navitem-divider']);
    $content .= html_writer::tag('div', $selector, ['class' => 'navitem']);
    if ($controls !== '') {
        $content .= html_writer::tag('div', '', ['class' => 'navitem-divider']);
        $content .= $controls;
    }
    if ($actions !== '') {
        $content .= html_writer::tag('div', $actions, ['class' => 'navitem ml-auto align-self-center']);
    }

    return html_writer::tag(
        'div',
        html_writer::tag('div', $content, ['class' => 'row']),
        ['class' => 'container-fluid tertiary-navigation full-width-bottom-border']
    );
}

/**
 * Renders a Moodle action menu.
 *
 * @param array $actions Action definitions with label, URL, icon and optional attributes.
 * @return string Rendered action menu HTML.
 */
function local_evalfp_render_course_action_menu(array $actions): string {
    global $OUTPUT;

    $menu = new action_menu();
    $icon = html_writer::tag('i', '', [
        'class' => 'icon fa fa-ellipsis-v fa-fw m-0',
        'aria-hidden' => 'true',
    ]);
    $extraclasses = 'btn btn-icon d-flex align-items-center justify-content-center dropdown-toggle icon-no-margin no-caret';
    $menu->set_menu_trigger($icon, $extraclasses);
    $menu->set_menu_left();
    $menu->set_boundary('window');

    foreach ($actions as $action) {
        if (!empty($action['separator'])) {
            $separator = new action_menu_filler();
            $separator->primary = false;
            $menu->add($separator);
            continue;
        }
        $attrs = $action['danger'] ?? false ? ['class' => 'text-danger'] : [];
        $menu->add(new action_menu_link_secondary($action['url'], $action['icon'], $action['text'], $attrs));
    }

    return $OUTPUT->render($menu);
}

/**
 * Starts the standard EvalFP course page layout.
 *
 * This emits the Moodle page header, EvalFP tertiary navigation and the main
 * fluid content container used by all course-level plugin pages.
 *
 * @param int $courseid Course ID.
 * @param string $active Active menu key.
 * @param string $actions HTML action controls displayed on the right.
 * @param string $controls HTML filter controls displayed after the menu selector.
 * @return void
 */
function local_evalfp_start_course_layout(int $courseid, string $active = '', string $actions = '', string $controls = ''): void {
    global $OUTPUT;

    echo $OUTPUT->header();
    echo local_evalfp_render_course_tertiary_navigation($courseid, $active, $actions, $controls);
    echo html_writer::start_tag('main', ['class' => 'mt-4']);
}

/**
 * Ends the standard EvalFP course page layout.
 *
 * @return void
 */
function local_evalfp_end_course_layout(): void {
    global $OUTPUT;

    echo html_writer::end_tag('main');
    echo $OUTPUT->footer();
}
