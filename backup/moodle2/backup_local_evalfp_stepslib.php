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
 * Backup structure helpers for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines the EvalFP backup structures attached to Moodle backup elements.
 */
class backup_local_evalfp_structure {
    /**
     * Adds course-level EvalFP data to the plugin wrapper.
     *
     * @param backup_nested_element $pluginwrapper Plugin wrapper element.
     */
    public static function add_course_structure(backup_nested_element $pluginwrapper): void {
        $settings = new backup_nested_element('evalfp_settings');
        $setting = new backup_nested_element('evalfp_setting', ['id'], [
            'showactivitycurriculum', 'activitycurriculumexpanded', 'timecreated', 'timemodified',
        ]);

        $ras = new backup_nested_element('evalfp_ras');
        $ra = new backup_nested_element('evalfp_ra', ['id'], [
            'code', 'description', 'weight', 'timecreated', 'timemodified',
        ]);

        $ces = new backup_nested_element('evalfp_ces');
        $ce = new backup_nested_element('evalfp_ce', ['id'], [
            'courseraid', 'code', 'description', 'weight', 'timecreated', 'timemodified',
        ]);

        $evaluations = new backup_nested_element('evalfp_evaluations');
        $evaluation = new backup_nested_element('evalfp_evaluation', ['id'], [
            'code', 'description', 'type', 'startdate', 'enddate', 'timecreated', 'timemodified',
        ]);

        $pluginwrapper->add_child($settings);
        $settings->add_child($setting);

        $pluginwrapper->add_child($ras);
        $ras->add_child($ra);
        $ra->add_child($ces);
        $ces->add_child($ce);

        $pluginwrapper->add_child($evaluations);
        $evaluations->add_child($evaluation);

        $setting->set_source_table('local_evalfp_course_settings', ['courseid' => backup::VAR_COURSEID]);
        $ra->set_source_table('local_evalfp_course_ra', ['courseid' => backup::VAR_COURSEID], 'id ASC');
        $ce->set_source_table('local_evalfp_course_ce', ['courseraid' => backup::VAR_PARENTID], 'id ASC');
        $evaluation->set_source_table(
            'local_evalfp_course_evaluation',
            ['courseid' => backup::VAR_COURSEID],
            'id ASC'
        );

        self::add_course_evidence_structure($pluginwrapper);
    }

    /**
     * Adds activity-level EvalFP evidence links to the plugin wrapper.
     *
     * @param backup_nested_element $pluginwrapper Plugin wrapper element.
     */
    public static function add_module_structure(backup_nested_element $pluginwrapper): void {
        self::add_module_evidence_structure($pluginwrapper);
    }

    /**
     * Adds course evidence link elements and sources.
     *
     * @param backup_nested_element $pluginwrapper Plugin wrapper element.
     */
    protected static function add_course_evidence_structure(backup_nested_element $pluginwrapper): void {
        [$evidencece, $evidenceevaluation] = self::add_evidence_elements($pluginwrapper);

        $evidencece->set_source_sql(
            'SELECT ece.*, ra.code AS racode, ce.code AS cecode
               FROM {local_evalfp_course_evidence_ce} ece
               JOIN {local_evalfp_course_ce} ce ON ce.id = ece.courseceid
               JOIN {local_evalfp_course_ra} ra ON ra.id = ce.courseraid
              WHERE ra.courseid = ?',
            [backup::VAR_COURSEID]
        );
        $evidenceevaluation->set_source_sql(
            'SELECT eev.*, evaluation.code AS evaluationcode
               FROM {local_evalfp_course_evidence_evaluation} eev
               JOIN {local_evalfp_course_evaluation} evaluation ON evaluation.id = eev.courseevaluationid
              WHERE evaluation.courseid = ?',
            [backup::VAR_COURSEID]
        );
    }

    /**
     * Adds module evidence link elements and sources.
     *
     * @param backup_nested_element $pluginwrapper Plugin wrapper element.
     */
    protected static function add_module_evidence_structure(backup_nested_element $pluginwrapper): void {
        [$evidencece, $evidenceevaluation] = self::add_evidence_elements($pluginwrapper);

        $modulegradeitemsql = "
               FROM {grade_items} gi
               JOIN {course_modules} cm ON cm.instance = gi.iteminstance
               JOIN {modules} m ON m.id = cm.module AND m.name = gi.itemmodule
              WHERE gi.itemtype = 'mod'
                AND cm.id = ?";

        $evidencece->set_source_sql(
            'SELECT ece.*, ra.code AS racode, ce.code AS cecode
               FROM {local_evalfp_course_evidence_ce} ece
               JOIN {local_evalfp_course_ce} ce ON ce.id = ece.courseceid
               JOIN {local_evalfp_course_ra} ra ON ra.id = ce.courseraid
               JOIN {grade_items} gi2 ON gi2.id = ece.gradeitemid
              WHERE gi2.id IN (SELECT gi.id' . $modulegradeitemsql . ')',
            [backup::VAR_MODID]
        );
        $evidenceevaluation->set_source_sql(
            'SELECT eev.*, evaluation.code AS evaluationcode
               FROM {local_evalfp_course_evidence_evaluation} eev
               JOIN {local_evalfp_course_evaluation} evaluation ON evaluation.id = eev.courseevaluationid
               JOIN {grade_items} gi2 ON gi2.id = eev.gradeitemid
              WHERE gi2.id IN (SELECT gi.id' . $modulegradeitemsql . ')',
            [backup::VAR_MODID]
        );
    }

    /**
     * Adds evidence link elements to a plugin wrapper.
     *
     * @param backup_nested_element $pluginwrapper Plugin wrapper element.
     * @return array Evidence CE and evidence evaluation elements.
     */
    protected static function add_evidence_elements(backup_nested_element $pluginwrapper): array {
        $evidenceces = new backup_nested_element('evalfp_evidence_ces');
        $evidencece = new backup_nested_element('evalfp_evidence_ce', ['id'], [
            'gradeitemid', 'courseceid', 'racode', 'cecode', 'timecreated', 'timemodified',
        ]);

        $evidenceevaluations = new backup_nested_element('evalfp_evidence_evaluations');
        $evidenceevaluation = new backup_nested_element('evalfp_evidence_evaluation', ['id'], [
            'gradeitemid', 'courseevaluationid', 'evaluationcode', 'timecreated', 'timemodified',
        ]);

        $pluginwrapper->add_child($evidenceces);
        $evidenceces->add_child($evidencece);

        $pluginwrapper->add_child($evidenceevaluations);
        $evidenceevaluations->add_child($evidenceevaluation);

        return [$evidencece, $evidenceevaluation];
    }
}
