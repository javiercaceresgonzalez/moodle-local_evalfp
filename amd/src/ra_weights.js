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
 * Dynamic total for the RA weighting page.
 *
 * @module     local_evalfp/ra_weights
 * @copyright  2026 Javier Caceres Gonzalez <javiercaceresgonzalez@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    const inputSelector = '[data-local-evalfp-ra-weight-input]';
    const totalSelector = '[data-local-evalfp-ra-weight-total]';

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
     * Updates a total badge.
     *
     * @param {Element} totalNode Total badge node.
     * @param {number} total Current total.
     */
    const updateBadge = (totalNode, total) => {
        const validTotal = Math.abs(total - 100) < 0.0001;

        totalNode.textContent = formatPercent(total);
        totalNode.classList.remove('badge-success', 'badge-warning', 'badge-danger');
        totalNode.classList.add(validTotal ? 'badge-success' : 'badge-warning');
    };

    /**
     * Updates the form total.
     *
     * @param {HTMLFormElement|Element} form Form or container element.
     */
    const updateForm = form => {
        const inputs = Array.from(form.querySelectorAll(inputSelector));
        const total = inputs.reduce((sum, input) => sum + parseWeight(input.value), 0);

        form.querySelectorAll(totalSelector).forEach(totalNode => updateBadge(totalNode, total));
    };

    /**
     * Initialises dynamic RA totals.
     */
    const init = () => {
        const forms = Array.from(document.querySelectorAll('form')).filter(form => form.querySelector(inputSelector));

        forms.forEach(form => {
            updateForm(form);
            form.addEventListener('input', event => {
                if (event.target && event.target.matches(inputSelector)) {
                    updateForm(form);
                }
            });
            form.addEventListener('change', event => {
                if (event.target && event.target.matches(inputSelector)) {
                    updateForm(form);
                }
            });
        });
    };

    return {
        init: init
    };
});
