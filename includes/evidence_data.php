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
 * Gradebook evidence data helpers for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns the grade item associated with a course module.
 *
 * @param int $courseid
 * @param int $cmid
 * @return stdClass|null
 */
function local_evalfp_get_gradeitem_for_cmid(int $courseid, int $cmid): ?stdClass {
    global $DB;

    $sql = "
        SELECT gi.*
          FROM {course_modules} cm
          JOIN {modules} m ON m.id = cm.module
          JOIN {grade_items} gi ON gi.courseid = cm.course
                               AND gi.itemtype = 'mod'
                               AND gi.itemmodule = m.name
                               AND gi.iteminstance = cm.instance
         WHERE cm.course = :courseid
           AND cm.id = :cmid
    ";
    $record = $DB->get_record_sql($sql, ['courseid' => $courseid, 'cmid' => $cmid], IGNORE_MISSING);
    return $record ?: null;
}

/**
 * Returns the relevant deadline timestamp for an evidence activity.
 *
 * @param string $modname
 * @param int $instanceid
 * @return int
 */
function local_evalfp_get_evidence_deadline(string $modname, int $instanceid): int {
    global $DB;

    switch ($modname) {
        case 'assign':
            $rec = $DB->get_record('assign', ['id' => $instanceid], 'duedate, cutoffdate', IGNORE_MISSING);
            if ($rec) {
                if (!empty($rec->cutoffdate)) {
                    return (int)$rec->cutoffdate;
                }
                if (!empty($rec->duedate)) {
                    return (int)$rec->duedate;
                }
            }
            break;
        case 'quiz':
            return (int)($DB->get_field('quiz', 'timeclose', ['id' => $instanceid]) ?: 0);
        case 'workshop':
            return (int)($DB->get_field('workshop', 'submissionend', ['id' => $instanceid]) ?: 0);
        case 'forum':
            $rec = $DB->get_record('forum', ['id' => $instanceid], 'duedate, cutoffdate', IGNORE_MISSING);
            if ($rec) {
                if (!empty($rec->duedate)) {
                    return (int)$rec->duedate;
                }
                if (!empty($rec->cutoffdate)) {
                    return (int)$rec->cutoffdate;
                }
            }
            break;
        case 'lesson':
            return (int)($DB->get_field('lesson', 'deadline', ['id' => $instanceid]) ?: 0);
        case 'h5pactivity':
            return (int)($DB->get_field('h5pactivity', 'timeclose', ['id' => $instanceid]) ?: 0);
    }

    return 0;
}

/**
 * Returns gradebook evidence items for a course.
 *
 * @param int $courseid Course ID.
 * @return array<int, stdClass> Evidence grade items indexed by grade item ID.
 */
function local_evalfp_get_course_evidences(int $courseid): array {
    global $DB, $OUTPUT;

    $gradeitems = $DB->get_records_sql("
        SELECT gi.id, gi.courseid, gi.categoryid, gi.itemtype, gi.itemmodule, gi.iteminstance, gi.itemname,
               gi.grademax, gi.gradetype, gi.hidden, gi.sortorder
          FROM {grade_items} gi
         WHERE gi.courseid = :courseid
           AND gi.itemtype <> 'course'
           AND gi.gradetype <> 0
      ORDER BY gi.sortorder ASC, gi.id ASC
    ", ['courseid' => $courseid]);

    $modinfo = get_fast_modinfo($courseid);
    $categories = $DB->get_records('grade_categories', ['courseid' => $courseid], '', 'id, fullname, depth, parent');
    $mindepth = null;
    foreach ($gradeitems as $gradeitem) {
        $candidate = null;
        if ($gradeitem->itemtype === 'category' && isset($categories[$gradeitem->iteminstance])) {
            $candidate = (int)$categories[$gradeitem->iteminstance]->depth;
        } else if (!empty($gradeitem->categoryid) && isset($categories[$gradeitem->categoryid])) {
            $candidate = (int)$categories[$gradeitem->categoryid]->depth + 1;
        }
        if ($candidate !== null) {
            $mindepth = $mindepth === null ? $candidate : min($mindepth, $candidate);
        }
    }
    if ($mindepth === null) {
        $mindepth = 1;
    }

    $evidences = [];
    $categorygradeitembycat = [];

    foreach ($gradeitems as $gi) {
        $label = trim((string)$gi->itemname);
        $url = null;
        $iconhtml = '';
        $cmid = 0;
        $deadline = 0;
        $typelabel = $gi->itemtype;
        $indentlevel = 0;
        if ($gi->itemtype === 'category' && isset($categories[$gi->iteminstance])) {
            $indentlevel = max(0, (int)$categories[$gi->iteminstance]->depth - $mindepth);
        } else if (!empty($gi->categoryid) && isset($categories[$gi->categoryid])) {
            $indentlevel = max(0, (int)$categories[$gi->categoryid]->depth + 1 - $mindepth);
        }

        if ($gi->itemtype === 'mod' && !empty($gi->itemmodule) && !empty($gi->iteminstance)) {
            $cm = $modinfo->instances[$gi->itemmodule][$gi->iteminstance] ?? null;
            if ($cm && empty($cm->deletioninprogress) && $cm->uservisible) {
                $label = format_string($cm->name, true, ['context' => $cm->context]);
                $url = $cm->url;
                $cmid = (int)$cm->id;
                $deadline = local_evalfp_get_evidence_deadline($gi->itemmodule, (int)$gi->iteminstance);
                $iconhtml = html_writer::img($cm->get_icon_url(), s(get_string('modulename', $gi->itemmodule)), [
                    'class' => 'icon itemicon',
                ]);
                $typelabel = get_string('common_activity', 'local_evalfp');
            } else {
                continue;
            }
        } else if ($gi->itemtype === 'category') {
            if (isset($categories[$gi->iteminstance])) {
                $label = format_string($categories[$gi->iteminstance]->fullname);
            }
            $iconhtml = $OUTPUT->pix_icon('i/folder', get_string('category', 'grades'), 'moodle', [
                'class' => 'icon itemicon',
            ]);
            $typelabel = get_string('category');
        } else if ($gi->itemtype === 'manual') {
            $iconhtml = $OUTPUT->pix_icon('i/manual_item', get_string('manualitem', 'grades'), 'moodle', [
                'class' => 'icon itemicon',
            ]);
            $typelabel = get_string('manualitem', 'grades');
        }

        if ($label === '') {
            $label = get_string('gradeitem', 'grades') . ' ' . $gi->id;
        }

        $gi->label = $label;
        $gi->url = $url;
        $gi->iconhtml = html_writer::div($iconhtml, 'me-1 d-flex align-items-center flex-shrink-0');
        $gi->cmid = $cmid;
        $gi->deadline = $deadline;
        $gi->typelabel = $typelabel;
        $gi->indentlevel = $indentlevel;
        $evidences[(int)$gi->id] = $gi;
        if ($gi->itemtype === 'category') {
            $categorygradeitembycat[(int)$gi->iteminstance] = (int)$gi->id;
        }
    }

    require_once($GLOBALS['CFG']->libdir . '/gradelib.php');
    require_once($GLOBALS['CFG']->dirroot . '/grade/lib.php');
    $gtree = new grade_tree($courseid, false, false, null, true);
    $ordered = [];

    $appendtree = function (
        array $element,
        int $depth = 0
    ) use (
        &$appendtree,
        &$ordered,
        &$evidences,
        $categorygradeitembycat
    ): void {
        $type = $element['type'] ?? '';
        $object = $element['object'] ?? null;

        if ($type === 'category' && $object) {
            $catid = (int)$object->id;
            if ($depth > 0 && isset($categorygradeitembycat[$catid])) {
                $gradeitemid = $categorygradeitembycat[$catid];
                if (isset($evidences[$gradeitemid])) {
                    $evidences[$gradeitemid]->indentlevel = max(0, $depth - 1);
                    $ordered[$gradeitemid] = $evidences[$gradeitemid];
                }
            }
            foreach ($element['children'] ?? [] as $child) {
                if (($child['type'] ?? '') === 'categoryitem' || ($child['type'] ?? '') === 'courseitem') {
                    continue;
                }
                $appendtree($child, $depth + 1);
            }
            return;
        }

        if ($type === 'item' && $object) {
            $gradeitemid = (int)$object->id;
            if (isset($evidences[$gradeitemid])) {
                $evidences[$gradeitemid]->indentlevel = max(0, $depth - 1);
                $ordered[$gradeitemid] = $evidences[$gradeitemid];
            }
        }
    };

    $appendtree($gtree->top_element);

    foreach ($evidences as $gradeitemid => $evidence) {
        if (!isset($ordered[$gradeitemid])) {
            $ordered[$gradeitemid] = $evidence;
        }
    }

    return $ordered;
}

/**
 * Returns evaluation assignments by evidence item.
 *
 * @param int $courseid Course ID.
 * @return array<int, int> Evaluation IDs indexed by grade item ID.
 */
function local_evalfp_get_evidence_evaluations(int $courseid): array {
    global $DB;

    $sql = "
        SELECT ep.gradeitemid, ep.courseevaluationid
          FROM {local_evalfp_course_evidence_evaluation} ep
          JOIN {local_evalfp_course_evaluation} ev ON ev.id = ep.courseevaluationid
         WHERE ev.courseid = :courseid
    ";
    return $DB->get_records_sql_menu($sql, ['courseid' => $courseid]);
}

/**
 * Returns CE links by evidence item.
 *
 * @param int $courseid Course ID.
 * @return array<int, stdClass> Evidence-to-CE link records indexed by link ID.
 */
function local_evalfp_get_evidence_ce_links(int $courseid): array {
    global $DB;

    $sql = "
        SELECT ec.id, ec.gradeitemid, ec.courseceid
          FROM {local_evalfp_course_evidence_ce} ec
          JOIN {local_evalfp_course_ce} ce ON ce.id = ec.courseceid
          JOIN {local_evalfp_course_ra} ra ON ra.id = ce.courseraid
         WHERE ra.courseid = :courseid
    ";
    return $DB->get_records_sql($sql, ['courseid' => $courseid]);
}
