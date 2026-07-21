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
 * Backup integration for the EvalFP local plugin.
 *
 * @package    local_evalfp
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/evalfp/backup/moodle2/backup_local_evalfp_stepslib.php');

/**
 * Adds EvalFP data to Moodle course backups.
 */
class backup_local_evalfp_plugin extends backup_local_plugin {
    /**
     * Defines EvalFP data attached to the course element.
     *
     * @return backup_plugin_element Plugin structure.
     */
    protected function define_course_plugin_structure(): backup_plugin_element {
        $plugin = $this->get_plugin_element(null);
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($pluginwrapper);

        backup_local_evalfp_structure::add_course_structure($pluginwrapper);

        return $plugin;
    }


    /**
     * Defines EvalFP data attached to the general section backup.
     *
     * @return backup_plugin_element Plugin structure.
     */
    protected function define_section_plugin_structure(): backup_plugin_element {
        $plugin = $this->get_plugin_element(null, '../../number', 0);
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($pluginwrapper);

        backup_local_evalfp_structure::add_course_structure($pluginwrapper);

        return $plugin;
    }

    /**
     * Defines EvalFP evidence links attached to an activity module backup.
     *
     * @return backup_plugin_element Plugin structure.
     */
    protected function define_module_plugin_structure(): backup_plugin_element {
        $plugin = $this->get_plugin_element(null);
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($pluginwrapper);

        backup_local_evalfp_structure::add_module_structure($pluginwrapper);

        return $plugin;
    }
}
