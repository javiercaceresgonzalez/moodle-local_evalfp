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
 * Dynamic totals for the CE weighting page.
 *
 * Each RA table is calculated independently because CE weights must add up to
 * 100% within their own RA, not across the whole form.
 *
 * @module     local_evalfp/ce_weights
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    const groupSelector = '[data-local-evalfp-ce-weight-group]';
    const inputSelector = '[data-local-evalfp-ce-weight-input]';
    const totalSelector = '[data-local-evalfp-ce-weight-total]';

    /**
     * Parses a decimal percentage entered by the user.
     *
     * @param {string} value Raw input value.
     * @returns {number}
     */
    const parseWeight = value => {
        const normalised = String(value || '').trim().replace(',', '.');
        const parsed = parseFloat(normalised);
        return Number.isFinite(parsed) ? parsed : 0;
    };

    /**
     * Formats a percentage without unnecessary trailing zeroes.
     *
     * @param {number} value Numeric total.
     * @returns {string}
     */
    const formatPercent = value => {
        const rounded = Math.round(value * 100) / 100;
        return rounded.toFixed(2).replace(/\.?0+$/, '') + '%';
    };

    /**
     * Updates the total badge for one RA group.
     *
     * @param {Element} group RA weighting table wrapper.
     */
    const updateGroup = group => {
        const inputs = Array.from(group.querySelectorAll(inputSelector));
        const total = inputs.reduce((sum, input) => sum + parseWeight(input.value), 0);
        const validTotal = Math.abs(total - 100) < 0.0001;
        const totalNode = group.querySelector(totalSelector);

        if (!totalNode) {
            return;
        }

        totalNode.textContent = formatPercent(total);
        totalNode.classList.remove('badge-success', 'badge-warning', 'badge-danger');
        totalNode.classList.add(validTotal ? 'badge-success' : 'badge-warning');
    };

    /**
     * Initialises dynamic CE totals.
     */
    const init = () => {
        document.querySelectorAll(groupSelector).forEach(group => {
            updateGroup(group);
            group.addEventListener('input', event => {
                if (event.target && event.target.matches(inputSelector)) {
                    updateGroup(group);
                }
            });
            group.addEventListener('change', event => {
                if (event.target && event.target.matches(inputSelector)) {
                    updateGroup(group);
                }
            });
        });
    };

    return {
        init: init
    };
});
