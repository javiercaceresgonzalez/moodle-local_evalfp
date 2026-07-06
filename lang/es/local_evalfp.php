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
 * Spanish language strings for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['common_activity'] = 'Actividad';
$string['common_add'] = 'Añadir';
$string['common_back'] = 'Volver';
$string['common_ce_abbr'] = 'CE';
$string['common_code'] = 'Código';
$string['common_completion'] = 'Adquisición';
$string['common_date_range'] = 'Fechas';
$string['common_description'] = 'Descripción';
$string['common_end'] = 'Fin';
$string['common_evaluation'] = 'Evaluación';
$string['common_evaluation_period'] = 'Periodo de evaluación';
$string['common_evidence'] = 'Evidencia';
$string['common_grade'] = 'Calificación';
$string['common_import'] = 'Importar';
$string['common_name'] = 'Nombre';
$string['common_ra_abbr'] = 'RA';
$string['common_save_changes'] = 'Guardar cambios';
$string['common_start'] = 'Inicio';
$string['common_type'] = 'Tipo';
$string['common_user'] = 'Usuario';
$string['common_weight'] = 'Peso';
$string['coursemodule_curriculum_select_ce'] = 'Vincular CE';
$string['coursemodule_curriculum_title'] = 'Resultados de aprendizaje y criterios de evaluación';
$string['error_ce_weights_not_saved'] = 'No se han guardado los cambios. Revisa los pesos de CE indicados.';
$string['error_code_exists'] = 'Ya existe un registro con ese código.';
$string['error_evaluation_end_after_start'] = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
$string['error_evaluation_only_one_extraordinary'] = 'Solo puede existir una evaluación extraordinaria por curso.';
$string['error_evaluation_only_one_final'] = 'Solo puede existir una evaluación final ordinaria por curso.';
$string['error_invalid_evaluation'] = 'El periodo de evaluación no existe para este curso.';
$string['error_invalid_record'] = 'Registro no válido';
$string['error_ra_weights_not_saved'] = 'No se han guardado los cambios. Revisa los pesos indicados.';
$string['error_required'] = 'Este campo es obligatorio';
$string['error_weight_invalid'] = 'Introduce un número válido.';
$string['error_weight_range'] = 'El peso debe estar entre 0 y 100.';
$string['evaluation_type_extraordinary'] = 'Extraordinaria';
$string['evaluation_type_final'] = 'Final';
$string['evaluation_type_partial'] = 'Parcial';
$string['form_activity_settings_expanded'] = 'Mostrar la información curricular expandida por defecto';
$string['form_activity_settings_expanded_help'] = 'Si se activa, el bloque aparecerá desplegado al abrir la actividad.';
$string['form_activity_settings_show_curriculum'] = 'Mostrar información curricular en las actividades del curso';
$string['form_activity_settings_show_curriculum_help'] = 'Si se activa, las actividades del curso mostrarán los RA y CE ' .
    'vinculados desde EvalFP.';
$string['form_ce_description'] = 'Criterio de evaluación';
$string['form_ce_weight_for'] = 'Peso de {$a}';
$string['form_evaluation_enddate'] = 'Fecha de fin';
$string['form_evaluation_name'] = 'Nombre del periodo';
$string['form_evaluation_select'] = 'Selecciona un periodo de evaluación';
$string['form_evaluation_startdate'] = 'Fecha de inicio';
$string['form_evaluation_type'] = 'Tipo';
$string['form_evaluation_type_help'] = 'Selecciona cómo se usa este periodo dentro de la estructura de evaluación del curso.';
$string['form_evidence_ce_checkbox_label'] = 'Vincular {$a->evidence} con {$a->ce}';
$string['form_ra_description'] = 'Resultado de aprendizaje';
$string['form_ra_weight_for'] = 'Peso de {$a}';
$string['import_ra_ce_column_ce_help'] = 'Letra o código del CE, por ejemplo a. Déjalo vacío en filas ra.';
$string['import_ra_ce_column_description_help'] = 'Texto descriptivo del RA o del CE.';
$string['import_ra_ce_column_ra_help'] = 'Número o código del RA, por ejemplo 1. El sistema mostrará RA1.';
$string['import_ra_ce_column_type_help'] = 'ra o ce.';
$string['import_ra_ce_confirm'] = 'Confirmar importación';
$string['import_ra_ce_download_template'] = 'Descargar plantilla ODS';
$string['import_ra_ce_error_ce_unknown_ra'] = 'Fila {$a->row}: el CE hace referencia a {$a->ra}, pero ese RA ' .
    'no existe en el curso ni aparece en el fichero.';
$string['import_ra_ce_error_ce_unknown_ra_replace'] = 'Fila {$a->row}: el CE hace referencia a {$a->ra}, ' .
    'pero en modo reemplazar ese RA debe aparecer en el fichero.';
$string['import_ra_ce_error_code_too_long'] = 'Fila {$a->row}: el código {$a->code} supera la longitud máxima permitida.';
$string['import_ra_ce_error_duplicate_ce'] = 'Fila {$a->row}: el CE {$a->code} está duplicado en el fichero.';
$string['import_ra_ce_error_duplicate_ra'] = 'Fila {$a->row}: el RA {$a->code} está duplicado en el fichero.';
$string['import_ra_ce_error_empty_file'] = 'El fichero no contiene filas importables.';
$string['import_ra_ce_error_file_not_found'] = 'No se ha podido recuperar el fichero subido.';
$string['import_ra_ce_error_invalid_code'] = 'Fila {$a->row}: el código {$a->code} contiene caracteres no válidos.';
$string['import_ra_ce_error_invalid_type'] = 'Fila {$a->row}: la columna type debe ser ra o ce.';
$string['import_ra_ce_error_missing_ce'] = 'Fila {$a->row}: falta el código del CE.';
$string['import_ra_ce_error_missing_description'] = 'Fila {$a->row}: falta la descripción.';
$string['import_ra_ce_error_missing_ra'] = 'Fila {$a->row}: falta el código del RA.';
$string['import_ra_ce_error_unreadable'] = 'No se ha podido leer el fichero ODS: {$a}';
$string['import_ra_ce_format_help'] = 'La hoja debe contener cuatro columnas: type, ra, ce y description. ' .
    'Usa una fila ra para cada resultado de aprendizaje y filas ce para sus criterios.';
$string['import_ra_ce_format_title'] = 'Formato de la plantilla';
$string['import_ra_ce_intro'] = 'Descarga la plantilla, completa los RA y CE, y sube el fichero ' .
    'para revisar los datos antes de aplicarlos al curso.';
$string['import_ra_ce_mode'] = 'Modo de importación';
$string['import_ra_ce_mode_help'] = 'Fusionar actualiza o crea RA/CE según su código. Reemplazar elimina primero ' .
    'los RA y CE actuales del curso y después importa el fichero.';
$string['import_ra_ce_mode_merge'] = 'Fusionar con los datos actuales';
$string['import_ra_ce_mode_replace'] = 'Reemplazar todos los RA y CE actuales';
$string['import_ra_ce_review_help'] = 'Revisa los datos detectados. La importación no se aplicará hasta que confirmes.';
$string['import_ra_ce_selected_mode_merge'] = 'Modo seleccionado: se fusionarán los datos del fichero con los RA y CE actuales.';
$string['import_ra_ce_selected_mode_replace'] = 'Modo seleccionado: se eliminarán los RA y CE actuales del curso ' .
    'antes de importar el fichero.';
$string['import_ra_ce_success'] = 'Importación completada. RA creados: {$a->rascreated}. ' .
    'RA actualizados: {$a->rasupdated}. CE creados: {$a->cescreated}. ' .
    'CE actualizados: {$a->cesupdated}. RA eliminados: {$a->rasdeleted}.';
$string['import_ra_ce_template_example_ce1a'] = 'Descripción del criterio de evaluación 1a';
$string['import_ra_ce_template_example_ce1b'] = 'Descripción del criterio de evaluación 1b';
$string['import_ra_ce_template_example_ce2a'] = 'Descripción del criterio de evaluación 2a';
$string['import_ra_ce_template_example_ra1'] = 'Descripción del resultado de aprendizaje 1';
$string['import_ra_ce_template_example_ra2'] = 'Descripción del resultado de aprendizaje 2';
$string['import_ra_ce_template_filename'] = 'plantilla_ra_ce.ods';
$string['import_ra_ce_title'] = 'Importar RA y CE';
$string['import_ra_ce_warning_ce_without_ra_in_file'] = 'Fila {$a->row}: el CE se vinculará a {$a->ra} existente ' .
    'en el curso si está disponible.';
$string['import_ra_ce_warning_ra_ce_ignored'] = 'Fila {$a->row}: se ignorará la columna ce porque la fila es de tipo ra.';
$string['nav_assessment'] = 'Evaluación';
$string['nav_assessment_by_ra'] = 'Evaluación por resultados de aprendizaje';
$string['nav_curriculum'] = 'Configuración curricular';
$string['nav_curriculum_ra_ce'] = 'Resultados de aprendizaje y criterios de evaluación';
$string['nav_report_evidences_by_ce'] = 'Evidencias por criterio de evaluación';
$string['nav_report_evidences_by_evaluation'] = 'Evidencias por periodo de evaluación';
$string['nav_report_evidences_by_ra'] = 'Evidencias por resultado de aprendizaje';
$string['nav_reports'] = 'Informes';
$string['nav_settings'] = 'Ajustes';
$string['page_activity_settings_title'] = 'Ajustes de actividad';
$string['page_assessment_chart_title'] = 'Adquisición por RA';
$string['page_assessment_individual_report_action'] = 'Informe individual';
$string['page_assessment_individual_select_evaluation'] = 'Selecciona un periodo de evaluación para ver el informe individual.';
$string['page_assessment_individual_title'] = 'Informe individual por resultados de aprendizaje';
$string['page_assessment_individual_total'] = 'Calificación';
$string['page_assessment_no_evidences_current_evaluation'] = 'No hay evidencias vinculadas a este resultado ' .
    'de aprendizaje en el periodo de evaluación seleccionado.';
$string['page_assessment_no_grade'] = 'Sin calificación';
$string['page_assessment_no_users'] = 'No hay usuarios matriculados o visibles con los filtros actuales.';
$string['page_assessment_normalised_achievement'] = 'Calificación normalizada: {$a}';
$string['page_assessment_ra_total'] = 'Total {$a}';
$string['page_assessment_select_evaluation'] = 'Selecciona un periodo de evaluación para consultar los resultados.';
$string['page_assessment_title'] = 'Evaluación por resultados de aprendizaje';
$string['page_assessment_user_column'] = 'Nombre / apellido(s)';
$string['page_ce_add_title'] = 'Nuevo criterio de evaluación';
$string['page_ce_delete_confirm'] = '¿Está seguro de que desea eliminar el criterio de evaluación "{$a}"? ' .
    'También se eliminarán sus vínculos con actividades.';
$string['page_ce_delete_title'] = 'Eliminar criterio de evaluación';
$string['page_ce_edit_title'] = 'Editar criterio de evaluación';
$string['page_ce_weights_title'] = 'Ponderación de CE';
$string['page_curriculum_add_ce'] = 'Añadir CE';
$string['page_curriculum_add_ra'] = 'Añadir RA';
$string['page_curriculum_no_ce'] = 'No hay criterios de evaluación definidos en este curso.';
$string['page_curriculum_no_ce_for_ra'] = 'No se han definido criterios de evaluación para este resultado.';
$string['page_curriculum_no_ra'] = 'No hay resultados de aprendizaje definidos en este curso.';
$string['page_curriculum_ra_ce_title'] = 'Resultados de aprendizaje y criterios de evaluación';
$string['page_evaluation_add_title'] = 'Nuevo periodo de evaluación';
$string['page_evaluation_delete_confirm'] = '¿Está seguro de que desea eliminar el periodo de evaluación "{$a}"? ' .
    'Esta acción no se puede deshacer.';
$string['page_evaluation_delete_title'] = 'Eliminar periodo de evaluación';
$string['page_evaluation_edit_title'] = 'Editar periodo de evaluación';
$string['page_evaluations_none'] = 'No hay periodos de evaluación definidos en este curso.';
$string['page_evaluations_title'] = 'Periodos de evaluación';
$string['page_evidences_no_grade_items'] = 'No hay evidencias de evaluación en el libro de calificaciones de este curso.';
$string['page_evidences_title'] = 'Evidencias de evaluación';
$string['page_ra_add_title'] = 'Nuevo resultado de aprendizaje';
$string['page_ra_delete_confirm'] = '¿Está seguro de que desea eliminar el resultado de aprendizaje "{$a}"? ' .
    'También se eliminarán sus criterios y vínculos con actividades.';
$string['page_ra_delete_title'] = 'Eliminar resultado de aprendizaje';
$string['page_ra_edit_title'] = 'Editar resultado de aprendizaje';
$string['page_ra_weights_title'] = 'Ponderación de RA';
$string['pluginname'] = 'EvalFP';
$string['privacy:metadata'] = 'El plugin EvalFP no almacena datos personales de usuarios.';
$string['report_evidences_by_ce_title'] = 'Evidencias por criterio de evaluación';
$string['report_evidences_by_evaluation_title'] = 'Evidencias por periodo de evaluación';
$string['report_evidences_by_ra_title'] = 'Evidencias por resultado de aprendizaje';
$string['report_no_evidences_linked_ce'] = 'No hay evidencias vinculadas a este criterio de evaluación.';
$string['report_no_evidences_linked_evaluation'] = 'No hay evidencias vinculadas a este periodo de evaluación.';
$string['report_no_evidences_linked_ra'] = 'No hay evidencias vinculadas a este resultado de aprendizaje.';
$string['success_ce_deleted'] = 'Criterio de evaluación eliminado correctamente';
$string['success_changes_saved'] = 'Cambios guardados correctamente';
$string['success_evaluation_deleted'] = 'Periodo de evaluación eliminado correctamente';
$string['success_ra_deleted'] = 'Resultado de aprendizaje eliminado correctamente';
$string['success_record_created'] = 'Registro creado correctamente';
$string['warning_ce_weights_sum'] = 'Conviene que la suma de pesos de CE de cada RA sea 100%.';
$string['warning_ra_weights_sum'] = 'La suma de los pesos es {$a}%. Conviene que sea 100%.';
