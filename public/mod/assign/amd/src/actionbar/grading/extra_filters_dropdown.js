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
import {getString} from 'core/str';
import {prefetchStrings} from 'core/prefetch';
import Notification from 'core/notification';

/**
 * Module for the extra filters dropdown on the submissions page.
 *
 * @module     mod_assign/actionbar/grading/extra_filters_dropdown
 * @copyright  2024 Mihail Geshoski <mihail@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

prefetchStrings('mod_assign', ['markfilterrangeinvalid']);

let rangeInvalidMessage = '';
getString('markfilterrangeinvalid', 'mod_assign').then((message) => {
    rangeInvalidMessage = message;
    return message;
}).catch(Notification.exception);

/** @constant {Object} The object containing the relevant selectors. */
const Selectors = {
    extraFiltersDropdown: '.dropdown.extrafilters',
    extraFiltersClose: 'a[data-action="close"]',
    suspendedParticipantsFilterCheckbox: 'input[type="checkbox"][name="suspendedparticipantsfilter"]',
    suspendedParticipantsFilterHidden: 'input[type="hidden"][name="suspendedparticipantsfilter"]',
    markFilterElements: '[name^="markfilter_"]',
    markFilterGte: '[name="markfilter_greater_than_or_equal"]',
    markFilterLte: '[name="markfilter_less_than_or_equal"]',
    markFilterCheckbox: 'input[type="checkbox"][name^="markfilter_"][name$="_enabled"]',
};

/**
 * Register event listeners for the extra filters dropdown.
 *
 * @param {DropdownDialog} extraFiltersDropdown The dropdown dialog instance.
 */
const registerEventListeners = (extraFiltersDropdown) => {
    const dropdownElement = extraFiltersDropdown.getElement();

    // Click event listener to the extra filters dropdown element.
    dropdownElement.addEventListener('click', e => {
        // The target is the 'Close' button.
        if (e.target.closest(Selectors.extraFiltersClose)) {
            e.preventDefault();
            extraFiltersDropdown.setVisible(false);
        }
    });

    // Change event listener to the extra filters dropdown element.
    dropdownElement.addEventListener('change', e => {
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

        if (e.target.closest(Selectors.markFilterElements)) {
            validateMarkFilterRange(extraFiltersDropdown);
        }
    });

    // Input event listener to clear or update validity as the user types.
    dropdownElement.addEventListener('input', e => {
        if (e.target.closest(Selectors.markFilterElements)) {
            validateMarkFilterRange(extraFiltersDropdown, false);
        }
    });

    let isSubmitting = false;

    // Submit event listener to the extra filters dropdown form.
    const form = dropdownElement.querySelector('form');
    if (form) {
        form.addEventListener('submit', e => {
            if (!validateMarkFilterRange(extraFiltersDropdown)) {
                e.preventDefault();
                return;
            }
            isSubmitting = true;
        });
    }

    // Reset submission state when the dropdown is shown.
    dropdownElement.addEventListener('show.bs.dropdown', () => {
        isSubmitting = false;
    });

    // Event listener triggered upon hiding of the dropdown.
    dropdownElement.addEventListener('hide.bs.dropdown', () => {
        if (isSubmitting) {
            return;
        }
        // Restore the filters to their applied values once the dropdown is closed.
        restoreAppliedFilters(extraFiltersDropdown);
    });
};

/**
 * Restores the filters in the dropdown to their applied values for the current page.
 *
 * @param {DropdownDialog} extraFiltersDropdown The dropdown dialog instance.
 */
const restoreAppliedFilters = (extraFiltersDropdown) => {
    const dropdownElement = extraFiltersDropdown.getElement();
    const form = dropdownElement.querySelector('form');
    if (!form) {
        return;
    }

    form.reset();

    const suspendedParticipantsFilterCheckbox = dropdownElement
        .querySelector(Selectors.suspendedParticipantsFilterCheckbox);
    if (suspendedParticipantsFilterCheckbox) {
        const suspendedParticipantsFilterHidden = suspendedParticipantsFilterCheckbox.parentNode
            .querySelector(Selectors.suspendedParticipantsFilterHidden);
        if (suspendedParticipantsFilterHidden) {
            suspendedParticipantsFilterHidden.disabled = suspendedParticipantsFilterCheckbox.checked;
        }
    }

    const markFilterCheckboxes = dropdownElement.querySelectorAll(Selectors.markFilterCheckbox);
    markFilterCheckboxes.forEach((checkbox) => {
        const {hidden, input} = getMarkFilterElements(checkbox);
        if (hidden) {
            hidden.disabled = checkbox.checked;
        }
        if (input) {
            input.disabled = !checkbox.checked;
        }
    });

    const lte = dropdownElement.querySelector(Selectors.markFilterLte);
    lte?.setCustomValidity('');
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
 * Ensure the 'less than or equal' mark is not less than the 'greater than or equal' mark when both filters are enabled.
 *
 * @param {DropdownDialog} extraFiltersDropdown The dropdown dialog instance.
 * @param {boolean} [report=true] Whether to report the validity to the user.
 * @returns {boolean} True if valid, false otherwise.
 */
const validateMarkFilterRange = (extraFiltersDropdown, report = true) => {
    const element = extraFiltersDropdown.getElement();
    const gte = element.querySelector(Selectors.markFilterGte);
    const lte = element.querySelector(Selectors.markFilterLte);
    if (!gte || !lte) {
        return true;
    }
    const isInvalid = !gte.disabled && !lte.disabled && gte.value !== '' && lte.value !== '' &&
        Number(lte.value) < Number(gte.value);
    // A non-empty message is needed to mark the field invalid even if the string has not loaded yet.
    const message = isInvalid ? (rangeInvalidMessage || ' ') : '';
    lte.setCustomValidity(message);
    if (isInvalid) {
        if (report) {
            lte.reportValidity();
        }
        return false;
    }
    return true;
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
