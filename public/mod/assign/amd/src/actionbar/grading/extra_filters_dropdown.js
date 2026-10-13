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
    markFilterGreaterThanOrEqualCheckbox: 'input[type="checkbox"][name="markfilter_greater_than_or_equal_enabled"]',
    markFilterGreaterThanOrEqualHidden: 'input[type="hidden"][name="markfilter_greater_than_or_equal_enabled"]',
    markFilterGreaterThanOrEqualInput: '[name="markfilter_greater_than_or_equal"]',
    markFilterLessThanCheckbox: 'input[type="checkbox"][name="markfilter_less_than_enabled"]',
    markFilterLessThanHidden: 'input[type="hidden"][name="markfilter_less_than_enabled"]',
    markFilterLessThanInput: '[name="markfilter_less_than"]'
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

        const markFilterGreaterThanOrEqualCheckbox = e.target.closest(Selectors.markFilterGreaterThanOrEqualCheckbox);
        if (markFilterGreaterThanOrEqualCheckbox) {
            const markFilterGreaterThanOrEqualHidden = markFilterGreaterThanOrEqualCheckbox.parentNode
                .querySelector(Selectors.markFilterGreaterThanOrEqualHidden);
            markFilterGreaterThanOrEqualHidden.disabled = markFilterGreaterThanOrEqualCheckbox.checked;
            const markFilterGreaterThanOrEqualInput = markFilterGreaterThanOrEqualCheckbox.parentNode
                .querySelector(Selectors.markFilterGreaterThanOrEqualInput);
            markFilterGreaterThanOrEqualInput.disabled = !markFilterGreaterThanOrEqualCheckbox.checked;
            if (markFilterGreaterThanOrEqualCheckbox.checked) {
                if (markFilterGreaterThanOrEqualInput.tagName === 'SELECT'
                        && markFilterGreaterThanOrEqualInput.selectedIndex === -1) {
                    markFilterGreaterThanOrEqualInput.selectedIndex = 0;
                }
                markFilterGreaterThanOrEqualInput.focus();
            }
        }

        const markFilterLessThanCheckbox = e.target.closest(Selectors.markFilterLessThanCheckbox);
        if (markFilterLessThanCheckbox) {
            const markFilterLessThanHidden = markFilterLessThanCheckbox.parentNode
                .querySelector(Selectors.markFilterLessThanHidden);
            markFilterLessThanHidden.disabled = markFilterLessThanCheckbox.checked;
            const markFilterLessThanInput = markFilterLessThanCheckbox.parentNode
                .querySelector(Selectors.markFilterLessThanInput);
            markFilterLessThanInput.disabled = !markFilterLessThanCheckbox.checked;
            if (markFilterLessThanCheckbox.checked) {
                if (markFilterLessThanInput.tagName === 'SELECT' && markFilterLessThanInput.selectedIndex === -1) {
                    markFilterLessThanInput.selectedIndex = 0;
                }
                markFilterLessThanInput.focus();
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
 * Restores the currently applied mark filter to its stored preference value.
 *
 * @param {DropdownDialog} extraFiltersDropdown The dropdown dialog instance.
 */
const restoreAppliedMarkFilter = async(extraFiltersDropdown) => {
    const markFilterGreaterThanOrEqualCheckbox = extraFiltersDropdown.getElement()
        .querySelector(Selectors.markFilterGreaterThanOrEqualCheckbox);
    const markFilterLessThanCheckbox = extraFiltersDropdown.getElement()
        .querySelector(Selectors.markFilterLessThanCheckbox);

    if (!markFilterGreaterThanOrEqualCheckbox && !markFilterLessThanCheckbox) {
        return;
    }

    const [
        greaterThanOrEqualEnabled,
        greaterThanOrEqualValue,
        lessThanEnabled,
        lessThanValue
    ] = await Promise.all([
        getUserPreference('assign_markfilter_greater_than_or_equal_enabled'),
        getUserPreference('assign_markfilter_greater_than_or_equal'),
        getUserPreference('assign_markfilter_less_than_enabled'),
        getUserPreference('assign_markfilter_less_than')
    ]);

    if (markFilterGreaterThanOrEqualCheckbox) {
        const markFilterGreaterThanOrEqualHidden = markFilterGreaterThanOrEqualCheckbox.parentNode
            .querySelector(Selectors.markFilterGreaterThanOrEqualHidden);
        const markFilterGreaterThanOrEqualInput = markFilterGreaterThanOrEqualCheckbox.parentNode
            .querySelector(Selectors.markFilterGreaterThanOrEqualInput);
        const isGreaterThanOrEqualEnabled = greaterThanOrEqualEnabled === '1'
            || greaterThanOrEqualEnabled === 1
            || greaterThanOrEqualEnabled === true;
        markFilterGreaterThanOrEqualCheckbox.checked = isGreaterThanOrEqualEnabled;
        markFilterGreaterThanOrEqualHidden.disabled = isGreaterThanOrEqualEnabled;
        markFilterGreaterThanOrEqualInput.disabled = !isGreaterThanOrEqualEnabled;
        if (markFilterGreaterThanOrEqualInput.tagName === 'SELECT') {
            if (greaterThanOrEqualValue
                    && markFilterGreaterThanOrEqualInput.querySelector(`option[value="${greaterThanOrEqualValue}"]`)) {
                markFilterGreaterThanOrEqualInput.value = greaterThanOrEqualValue;
            } else if (markFilterGreaterThanOrEqualInput.options.length > 0) {
                markFilterGreaterThanOrEqualInput.selectedIndex = 0;
            }
        } else {
            markFilterGreaterThanOrEqualInput.value = greaterThanOrEqualValue ?? '';
        }
    }

    if (markFilterLessThanCheckbox) {
        const markFilterLessThanHidden = markFilterLessThanCheckbox.parentNode
            .querySelector(Selectors.markFilterLessThanHidden);
        const markFilterLessThanInput = markFilterLessThanCheckbox.parentNode
            .querySelector(Selectors.markFilterLessThanInput);
        const isLessThanEnabled = lessThanEnabled === '1' || lessThanEnabled === 1 || lessThanEnabled === true;
        markFilterLessThanCheckbox.checked = isLessThanEnabled;
        markFilterLessThanHidden.disabled = isLessThanEnabled;
        markFilterLessThanInput.disabled = !isLessThanEnabled;
        if (markFilterLessThanInput.tagName === 'SELECT') {
            if (lessThanValue && markFilterLessThanInput.querySelector(`option[value="${lessThanValue}"]`)) {
                markFilterLessThanInput.value = lessThanValue;
            } else if (markFilterLessThanInput.options.length > 0) {
                markFilterLessThanInput.selectedIndex = 0;
            }
        } else {
            markFilterLessThanInput.value = lessThanValue ?? '';
        }
    }
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
