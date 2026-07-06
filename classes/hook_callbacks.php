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

namespace local_evalfp;

/**
 * Hook callbacks for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_callbacks {
    /**
     * Starts the activity curriculum placer early in the page body.
     *
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook Output hook.
     * @return void
     */
    public static function before_standard_top_of_body_html_generation(
        \core\hook\output\before_standard_top_of_body_html_generation $hook
    ): void {
        global $CFG;

        require_once($CFG->dirroot . '/local/evalfp/lib.php');

        $html = local_evalfp_render_coursemodule_curriculum_bootstrap_html();
        if ($html !== '') {
            $hook->add_html($html);
        }
    }

    /**
     * Adds the course-module curriculum block after the main region HTML.
     *
     * The block is generated only on module view pages and is injected through
     * the output hook so no Moodle core files need to be modified.
     *
     * @param \core\hook\output\after_standard_main_region_html_generation $hook Output hook.
     * @return void
     */
    public static function after_standard_main_region_html_generation(
        \core\hook\output\after_standard_main_region_html_generation $hook
    ): void {
        global $CFG;

        require_once($CFG->dirroot . '/local/evalfp/lib.php');

        $html = local_evalfp_render_coursemodule_curriculum_footer_html();
        if ($html !== '') {
            $hook->add_html($html);
        }
    }
}
