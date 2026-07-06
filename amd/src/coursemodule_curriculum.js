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
 * Activity page placement for EvalFP curriculum information.
 *
 * @module     local_evalfp/coursemodule_curriculum
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    const sourceId = 'local-evalfp-coursemodule-curriculum-source';
    const blockSelector = '[data-local-evalfp-coursemodule-curriculum]';

    /**
     * Returns whether the current page is the main activity view.
     *
     * @returns {boolean}
     */
    const isMainActivityView = () => {
        const params = new URLSearchParams(window.location.search);
        const internalParams = ['action', 'mode', 'rownum', 'userid'];

        return !internalParams.some(name => params.has(name));
    };

    /**
     * Returns the best insertion point inside Moodle activity pages.
     *
     * @returns {Element|null}
     */
    const getInsertionTarget = () => {
        return document.querySelector('.activity-description') ||
            document.querySelector('.activity-information') ||
            document.querySelector('#intro') ||
            document.querySelector('[role="main"]');
    };

    /**
     * Moves the delayed curriculum block into the activity content.
     */
    const init = () => {
        const source = document.getElementById(sourceId);
        if (!source) {
            return;
        }

        if (!isMainActivityView()) {
            source.remove();
            return;
        }

        const block = source.querySelector(blockSelector);
        if (!block) {
            source.remove();
            return;
        }

        const target = getInsertionTarget();
        if (!target) {
            source.classList.remove('d-none');
            return;
        }

        const movedBlock = block.cloneNode(true);
        if (target.id === 'intro' || target.classList.contains('activity-description')) {
            target.insertAdjacentElement('afterend', movedBlock);
        } else {
            target.appendChild(movedBlock);
        }
        source.remove();
    };

    /**
     * Keeps the edit-form hook stable. The current form no longer needs extra JS.
     */
    const watch = () => {};

    return {
        init: init,
        watch: watch
    };
});
