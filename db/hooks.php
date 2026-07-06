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
 * Hook callback definitions for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    // Starts the activity curriculum placer before Moodle renders the main page body.
    [
        'hook' => \core\hook\output\before_standard_top_of_body_html_generation::class,
        'callback' => [
            \local_evalfp\hook_callbacks::class,
            'before_standard_top_of_body_html_generation',
        ],
    ],

    // Adds read-only curriculum information to supported course module pages.
    [
        'hook' => \core\hook\output\after_standard_main_region_html_generation::class,
        'callback' => [
            \local_evalfp\hook_callbacks::class,
            'after_standard_main_region_html_generation',
        ],
    ],
];
