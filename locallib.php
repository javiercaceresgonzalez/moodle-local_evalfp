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
 * Shared include file for the EvalFP local plugin.
 *
 * Course pages and Moodle callbacks include this file to load the plugin's
 * procedural helper API. Helper files are grouped by responsibility.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Formatting and navigation helpers.
require_once(__DIR__ . '/includes/format.php');
require_once(__DIR__ . '/includes/navigation.php');
require_once(__DIR__ . '/includes/course_settings.php');

// Curriculum configuration helpers.
require_once(__DIR__ . '/includes/curriculum_evaluations.php');
require_once(__DIR__ . '/includes/curriculum_ra_ce.php');
require_once(__DIR__ . '/includes/curriculum_ra_ce_import.php');
require_once(__DIR__ . '/includes/curriculum_evidences.php');
require_once(__DIR__ . '/includes/coursemodule_curriculum.php');

// Evidence, report and assessment helpers.
require_once(__DIR__ . '/includes/evidence_data.php');
require_once(__DIR__ . '/includes/report_data.php');
require_once(__DIR__ . '/includes/assessment.php');
