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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * English language strings for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['common_activity'] = 'Activity';
$string['common_add'] = 'Add';
$string['common_back'] = 'Back';
$string['common_ce_abbr'] = 'AC';
$string['common_code'] = 'Code';
$string['common_completion'] = 'Achievement';
$string['common_date_range'] = 'Dates';
$string['common_description'] = 'Description';
$string['common_end'] = 'End';
$string['common_evaluation'] = 'Evaluation';
$string['common_evaluation_period'] = 'Evaluation period';
$string['common_evidence'] = 'Evidence';
$string['common_grade'] = 'Grade';
$string['common_import'] = 'Import';
$string['common_name'] = 'Name';
$string['common_ra_abbr'] = 'LO';
$string['common_save_changes'] = 'Save changes';
$string['common_start'] = 'Start';
$string['common_type'] = 'Type';
$string['common_user'] = 'User';
$string['common_weight'] = 'Weight';
$string['coursemodule_curriculum_select_ce'] = 'Link AC';
$string['coursemodule_curriculum_title'] = 'Learning outcomes and assessment criteria';
$string['error_ce_weights_not_saved'] = 'Changes were not saved. Check the entered AC weights.';
$string['error_code_exists'] = 'A record with that code already exists.';
$string['error_evaluation_end_after_start'] = 'The end date cannot be before the start date.';
$string['error_evaluation_only_one_extraordinary'] = 'Only one extraordinary evaluation period can exist per course.';
$string['error_evaluation_only_one_final'] = 'Only one final evaluation period can exist per course.';
$string['error_invalid_evaluation'] = 'The evaluation period does not exist for this course.';
$string['error_invalid_record'] = 'Invalid record.';
$string['error_ra_weights_not_saved'] = 'Changes were not saved. Check the entered weights.';
$string['error_required'] = 'This field is required.';
$string['error_weight_invalid'] = 'Enter a valid number.';
$string['error_weight_range'] = 'The weight must be between 0 and 100.';
$string['evaluation_type_extraordinary'] = 'Extraordinary';
$string['evaluation_type_final'] = 'Final';
$string['evaluation_type_partial'] = 'Partial';
$string['form_activity_settings_expanded'] = 'Show curriculum information expanded by default';
$string['form_activity_settings_expanded_help'] = 'When enabled, the curriculum information block will be expanded ' .
    'when the activity is opened.';
$string['form_activity_settings_show_curriculum'] = 'Show curriculum information in course activities';
$string['form_activity_settings_show_curriculum_help'] = 'When enabled, course activities will display the LO and AC ' .
    'linked in EvalFP.';
$string['form_ce_description'] = 'Assessment criterion';
$string['form_ce_weight_for'] = '{$a} weight';
$string['form_evaluation_enddate'] = 'End date';
$string['form_evaluation_name'] = 'Period name';
$string['form_evaluation_select'] = 'Select an evaluation period';
$string['form_evaluation_startdate'] = 'Start date';
$string['form_evaluation_type'] = 'Type';
$string['form_evaluation_type_help'] = 'Select how this evaluation period is used in the course assessment structure.';
$string['form_evidence_ce_checkbox_label'] = 'Link {$a->evidence} to {$a->ce}';
$string['form_ra_description'] = 'Learning outcome';
$string['form_ra_weight_for'] = '{$a} weight';
$string['import_ra_ce_column_ce_help'] = 'AC letter or code, for example a. Leave it empty in ra rows.';
$string['import_ra_ce_column_description_help'] = 'Description text for the LO or AC.';
$string['import_ra_ce_column_ra_help'] = 'LO number or code, for example 1. The system will display LO1.';
$string['import_ra_ce_column_type_help'] = 'ra or ce.';
$string['import_ra_ce_confirm'] = 'Confirm import';
$string['import_ra_ce_download_template'] = 'Download ODS template';
$string['import_ra_ce_error_ce_unknown_ra'] = 'Row {$a->row}: the AC references {$a->ra}, but that LO does not ' .
    'exist in the course and is not present in the file.';
$string['import_ra_ce_error_ce_unknown_ra_replace'] = 'Row {$a->row}: the AC references {$a->ra}, but in replace mode ' .
    'that LO must be present in the file.';
$string['import_ra_ce_error_code_too_long'] = 'Row {$a->row}: code {$a->code} exceeds the maximum allowed length.';
$string['import_ra_ce_error_duplicate_ce'] = 'Row {$a->row}: AC {$a->code} is duplicated in the file.';
$string['import_ra_ce_error_duplicate_ra'] = 'Row {$a->row}: LO {$a->code} is duplicated in the file.';
$string['import_ra_ce_error_empty_file'] = 'The file does not contain any importable rows.';
$string['import_ra_ce_error_file_not_found'] = 'The uploaded file could not be retrieved.';
$string['import_ra_ce_error_invalid_code'] = 'Row {$a->row}: code {$a->code} contains invalid characters.';
$string['import_ra_ce_error_invalid_type'] = 'Row {$a->row}: the type column must be ra or ce.';
$string['import_ra_ce_error_missing_ce'] = 'Row {$a->row}: the AC code is missing.';
$string['import_ra_ce_error_missing_description'] = 'Row {$a->row}: the description is missing.';
$string['import_ra_ce_error_missing_ra'] = 'Row {$a->row}: the LO code is missing.';
$string['import_ra_ce_error_unreadable'] = 'The ODS file could not be read: {$a}';
$string['import_ra_ce_format_help'] = 'The sheet must contain four columns: type, ra, ce and description. ' .
    'Use one ra row for each LO and ce rows for its AC.';
$string['import_ra_ce_format_title'] = 'Template format';
$string['import_ra_ce_intro'] = 'Download the template, fill in the LO and AC rows, and upload the file ' .
    'to review the data before applying it to the course.';
$string['import_ra_ce_mode'] = 'Import mode';
$string['import_ra_ce_mode_help'] = 'Merge updates or creates LO and AC records by code. Replace deletes ' .
    'the current course LO and AC records before importing the file.';
$string['import_ra_ce_mode_merge'] = 'Merge with current data';
$string['import_ra_ce_mode_replace'] = 'Replace all current LO and AC records';
$string['import_ra_ce_review_help'] = 'Review the detected data. The import will not be applied until you confirm it.';
$string['import_ra_ce_selected_mode_merge'] = 'Selected mode: file data will be merged with the current LO and AC records.';
$string['import_ra_ce_selected_mode_replace'] = 'Selected mode: current course LO and AC records will be deleted ' .
    'before importing the file.';
$string['import_ra_ce_success'] = 'Import completed. LO created: {$a->rascreated}. LO updated: {$a->rasupdated}. ' .
    'AC created: {$a->cescreated}. AC updated: {$a->cesupdated}. LO deleted: {$a->rasdeleted}.';
$string['import_ra_ce_template_example_ce1a'] = 'Assessment criterion 1a description';
$string['import_ra_ce_template_example_ce1b'] = 'Assessment criterion 1b description';
$string['import_ra_ce_template_example_ce2a'] = 'Assessment criterion 2a description';
$string['import_ra_ce_template_example_ra1'] = 'LO 1 description';
$string['import_ra_ce_template_example_ra2'] = 'LO 2 description';
$string['import_ra_ce_template_filename'] = 'ra_ce_template.ods';
$string['import_ra_ce_title'] = 'Import LO and AC';
$string['import_ra_ce_warning_ce_without_ra_in_file'] = 'Row {$a->row}: the AC will be linked to existing {$a->ra} ' .
    'in the course if available.';
$string['import_ra_ce_warning_ra_ce_ignored'] = 'Row {$a->row}: the ce column will be ignored because the row type is ra.';
$string['nav_assessment'] = 'Assessment';
$string['nav_assessment_by_ra'] = 'Assessment by learning outcomes';
$string['nav_curriculum'] = 'Curriculum configuration';
$string['nav_curriculum_ra_ce'] = 'Learning outcomes and assessment criteria';
$string['nav_report_evidences_by_ce'] = 'Evidence by assessment criterion';
$string['nav_report_evidences_by_evaluation'] = 'Evidence by evaluation period';
$string['nav_report_evidences_by_ra'] = 'Evidence by learning outcome';
$string['nav_reports'] = 'Reports';
$string['nav_settings'] = 'Settings';
$string['page_activity_settings_title'] = 'Activity settings';
$string['page_assessment_chart_title'] = 'Achievement by LO';
$string['page_assessment_individual_report_action'] = 'Individual report';
$string['page_assessment_individual_select_evaluation'] = 'Select an evaluation period to view the individual report.';
$string['page_assessment_individual_title'] = 'Individual report by learning outcomes';
$string['page_assessment_individual_total'] = 'Grade';
$string['page_assessment_no_evidences_current_evaluation'] = 'No evidence is linked to this learning outcome ' .
    'in the selected evaluation period.';
$string['page_assessment_no_grade'] = 'No grade';
$string['page_assessment_no_users'] = 'No enrolled or visible users match the current filters.';
$string['page_assessment_normalised_achievement'] = 'Normalised grade: {$a}';
$string['page_assessment_ra_total'] = '{$a} total';
$string['page_assessment_select_evaluation'] = 'Select an evaluation period to view the results.';
$string['page_assessment_title'] = 'Assessment by learning outcomes';
$string['page_assessment_user_column'] = 'First name / Last name';
$string['page_ce_add_title'] = 'New assessment criterion';
$string['page_ce_delete_confirm'] = 'Are you sure you want to delete the assessment criterion "{$a}"? ' .
    'Its evidence links will also be deleted.';
$string['page_ce_delete_title'] = 'Delete assessment criterion';
$string['page_ce_edit_title'] = 'Edit assessment criterion';
$string['page_ce_weights_title'] = 'AC weighting';
$string['page_curriculum_add_ce'] = 'Add AC';
$string['page_curriculum_add_ra'] = 'Add LO';
$string['page_curriculum_no_ce'] = 'No assessment criteria have been defined for this course.';
$string['page_curriculum_no_ce_for_ra'] = 'No assessment criteria have been defined for this LO.';
$string['page_curriculum_no_ra'] = 'No learning outcomes have been defined for this course.';
$string['page_curriculum_ra_ce_title'] = 'Learning outcomes and assessment criteria';
$string['page_evaluation_add_title'] = 'New evaluation period';
$string['page_evaluation_delete_confirm'] = 'Are you sure you want to delete the evaluation period "{$a}"? ' .
    'This action cannot be undone.';
$string['page_evaluation_delete_title'] = 'Delete evaluation period';
$string['page_evaluation_edit_title'] = 'Edit evaluation period';
$string['page_evaluations_none'] = 'No evaluation periods have been defined for this course.';
$string['page_evaluations_title'] = 'Evaluation periods';
$string['page_evidences_no_grade_items'] = 'There is no evaluation evidence in this course gradebook.';
$string['page_evidences_title'] = 'Evaluation evidence';
$string['page_ra_add_title'] = 'New learning outcome';
$string['page_ra_delete_confirm'] = 'Are you sure you want to delete the learning outcome "{$a}"? ' .
    'Its assessment criteria and evidence links will also be deleted.';
$string['page_ra_delete_title'] = 'Delete learning outcome';
$string['page_ra_edit_title'] = 'Edit learning outcome';
$string['page_ra_weights_title'] = 'LO weighting';
$string['pluginname'] = 'EvalFP';
$string['privacy:metadata'] = 'The EvalFP plugin does not store any personal data.';
$string['report_evidences_by_ce_title'] = 'Evidence by assessment criterion';
$string['report_evidences_by_evaluation_title'] = 'Evidence by evaluation period';
$string['report_evidences_by_ra_title'] = 'Evidence by learning outcome';
$string['report_no_evidences_linked_ce'] = 'No evidence is linked to this assessment criterion.';
$string['report_no_evidences_linked_evaluation'] = 'No evidence is linked to this evaluation period.';
$string['report_no_evidences_linked_ra'] = 'No evidence is linked to this learning outcome.';
$string['success_ce_deleted'] = 'Assessment criterion deleted.';
$string['success_changes_saved'] = 'Changes saved.';
$string['success_evaluation_deleted'] = 'Evaluation period deleted.';
$string['success_ra_deleted'] = 'Learning outcome deleted.';
$string['success_record_created'] = 'Record created.';
$string['warning_ce_weights_sum'] = 'Each LO should normally have an AC weight total of 100%.';
$string['warning_ra_weights_sum'] = 'The weight total is {$a}%. It should normally be 100%.';
