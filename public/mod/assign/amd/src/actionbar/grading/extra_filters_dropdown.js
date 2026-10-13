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

import {getDropdownDialog} from 'core/local/dropdown/dialog';
import {getUserPreference} from 'core_user/repository';
import {get_string as getString} from 'core/str';

/**
 * Module for the extra filters dropdown on the submissions page.
 *
 * @module     mod_assign/actionbar/grading/extra_filters_dropdown
 * @copyright  2024 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @constant {Object} The object containing the relevant selectors. */
const Selectors = {
    extraFiltersDropdown: '.dropdown.extrafilters',
    extraFiltersClose: 'a[data-action="close"]',
    workflowFilterElement: 'select[name="workflowfilter"]',
    markerFilterElement: 'select[name="markingallocationfilter"]',
    suspendedParticipantsFilterCheckbox: 'input[type="checkbox"][name="suspendedparticipantsfilter"]',
    suspendedParticipantsFilterHidden: 'input[type="hidden"][name="suspendedparticipantsfilter"]',
    markFilterElements: '[name^="markfilter_"]',
    markFilterGte: '[name="markfilter_greater_than_or_equal"]',
    markFilterLt: '[name="markfilter_less_than"]',
    markFilterCheckbox: 'input[type="checkbox"][name^="markfilter_"][name$="_enabled"]',
};

/**
 * Register event listeners for the extra filters dropdown.
 *
 * @param {DropdownDialog} extraFiltersDropdown The dropdown dialog instance.
 */
const registerEventListeners = (extraFiltersDropdown) => {
    // Click event listener to the extra filters dropdown element.
    extraFiltersDropdown.getElement().addEventListener('click', e => {
        // The target is the 'Close' button.
        if (e.target.closest(Selectors.extraFiltersClose)) {
            e.preventDefault();
            extraFiltersDropdown.setVisible(false);
        }
    });

    // Change event listener to the extra filters dropdown element.
    extraFiltersDropdown.getElement().addEventListener('change', e => {
        const suspendedParticipantsFilterCheckbox = e.target.closest(Selectors.suspendedParticipantsFilterCheckbox);
        // The target is the 'Suspended participants' filter checkbox.
        if (suspendedParticipantsFilterCheckbox) {
            // The 'Suspended participants' filter uses a hidden input and a checkbox. The hidden input is used to
            // submit '0' as a workaround when the checkbox is unchecked since unchecked checkboxes are not submitted
            // with the form. Therefore, we need to enable or disable the hidden input based on the checkbox state.
            const suspendedParticipantsFilterHidden = suspendedParticipantsFilterCheckbox.parentNode
                .querySelector(Selectors.suspendedParticipantsFilterHidden);
            suspendedParticipantsFilterHidden.disabled = suspendedParticipantsFilterCheckbox.checked;
        }

        if (e.target.closest(Selectors.markFilterElements)) {
            validateMarkFilterRange(extraFiltersDropdown);
        }

        const markFilterCheckbox = e.target.closest(Selectors.markFilterCheckbox);
        if (markFilterCheckbox) {
            const {hidden, input} = getMarkFilterElements(markFilterCheckbox);
            hidden.disabled = markFilterCheckbox.checked;
            input.disabled = !markFilterCheckbox.checked;
            if (markFilterCheckbox.checked) {
                if (input.tagName === 'SELECT' && input.selectedIndex === -1) {
                    input.selectedIndex = 0;
                }
                input.focus();
            }
        }
    });

    // Event listener triggered upon hiding of the dropdown.
    extraFiltersDropdown.getElement().addEventListener('hide.bs.dropdown', () => {
        // Restore the filters to their stored preference values once the dropdown is closed.
        restoreAppliedWorkflowFilter(extraFiltersDropdown);
        restoreAppliedMarkerFilter(extraFiltersDropdown);
        restoreAppliedSuspendedParticipantsFilter(extraFiltersDropdown);
        restoreAppliedMarkFilter(extraFiltersDropdown);
    });
};

/**
 * Restores the currently applied workflow filter to its stored preference value.
 *
 * @param {DropdownDialog} extraFiltersDropdown The dropdown dialog instance.
 */
const restoreAppliedWorkflowFilter = async(extraFiltersDropdown) => {
    const workflowFilterSelect = extraFiltersDropdown.getElement().querySelector(Selectors.workflowFilterElement);
    if (workflowFilterSelect) {
        workflowFilterSelect.value = await getUserPreference('assign_workflowfilter');
    }
};

/**
 * Restores the currently applied marker filter to its stored preference value.
 *
 * @param {DropdownDialog} extraFiltersDropdown The dropdown dialog instance.
 */
const restoreAppliedMarkerFilter = async(extraFiltersDropdown) => {
    const markerFilterSelect = extraFiltersDropdown.getElement().querySelector(Selectors.markerFilterElement);
    if (markerFilterSelect) {
        markerFilterSelect.value = await getUserPreference('assign_markerfilter');
    }
};

/**
 * Restores the currently suspended participants filter to its stored preference value.
 *
 * @param {DropdownDialog} extraFiltersDropdown The dropdown dialog instance.
 */
const restoreAppliedSuspendedParticipantsFilter = async(extraFiltersDropdown) => {
    const suspendedParticipantsFilterCheckbox = extraFiltersDropdown.getElement()
        .querySelector(Selectors.suspendedParticipantsFilterCheckbox);
    if (suspendedParticipantsFilterCheckbox) {
        const suspendedParticipantsFilterHidden = suspendedParticipantsFilterCheckbox.parentNode
            .querySelector(Selectors.suspendedParticipantsFilterHidden);
        const showOnlyActiveParticipants = await getUserPreference('grade_report_showonlyactiveenrol');
        suspendedParticipantsFilterCheckbox.checked = !showOnlyActiveParticipants;
        suspendedParticipantsFilterHidden.disabled = !showOnlyActiveParticipants;
    }
};

/**
 * Get the hidden and value elements that accompany a mark filter checkbox.
 *
 * @param {HTMLInputElement} checkbox The mark filter enabled checkbox.
 * @returns {{hidden: HTMLInputElement, input: HTMLElement}}
 */
const getMarkFilterElements = (checkbox) => ({
    hidden: checkbox.parentNode.querySelector(`input[type="hidden"][name="${checkbox.name}"]`),
    input: checkbox.parentNode.querySelector(`[name="${checkbox.name.replace(/_enabled$/, '')}"]`),
});

/**
 * Ensure the 'less than' mark is greater than the 'greater than or equal' mark when both filters are enabled.
 *
 * @param {DropdownDialog} extraFiltersDropdown The dropdown dialog instance.
 */
const validateMarkFilterRange = async(extraFiltersDropdown) => {
    const element = extraFiltersDropdown.getElement();
    const gte = element.querySelector(Selectors.markFilterGte);
    const lt = element.querySelector(Selectors.markFilterLt);
    if (!gte || !lt) {
        return;
    }
    let message = '';
    if (!gte.disabled && !lt.disabled && gte.value !== '' && lt.value !== '' &&
            Number(lt.value) <= Number(gte.value)) {
        message = await getString('markfilterrangeinvalid', 'mod_assign');
    }
    lt.setCustomValidity(message);
    lt.reportValidity();
};

/**
 * Restores the currently applied mark filters to their stored preference values.
 *
 * @param {DropdownDialog} extraFiltersDropdown The dropdown dialog instance.
 */
const restoreAppliedMarkFilter = async(extraFiltersDropdown) => {
    const checkboxes = extraFiltersDropdown.getElement().querySelectorAll(Selectors.markFilterCheckbox);

    await Promise.all(Array.from(checkboxes).map(async(checkbox) => {
        const {hidden, input} = getMarkFilterElements(checkbox);
        const name = checkbox.name.replace(/_enabled$/, '');
        const [enabledValue, value] = await Promise.all([
            getUserPreference(`assign_${name}_enabled`),
            getUserPreference(`assign_${name}`),
        ]);
        let enabled = enabledValue === '1' || enabledValue === 1 || enabledValue === true;

        if (input.tagName === 'SELECT') {
            const option = Array.from(input.options).find((opt) => opt.value === String(value));
            if (option) {
                input.value = option.value;
            } else {
                // The stored value is no longer a valid option, so the filter cannot be applied.
                enabled = false;
                input.selectedIndex = 0;
            }
        } else {
            if (value === null || value === '' || isNaN(Number(value))) {
                enabled = false;
            }
            input.value = value ?? '';
        }

        checkbox.checked = enabled;
        hidden.disabled = enabled;
        input.disabled = !enabled;
    }));

    const lt = extraFiltersDropdown.getElement().querySelector(Selectors.markFilterLt);
    lt?.setCustomValidity('');
};

/**
 * Initialize module.
 */
export const init = () => {
    const extraFiltersDropdown = getDropdownDialog(Selectors.extraFiltersDropdown);
    if (extraFiltersDropdown) {
        registerEventListeners(extraFiltersDropdown);
    }
};
