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
 * Javascript for hiding/unhiding columns.
 *
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Nathan Nguyen <nathannguyen@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';

let dataAttribute = "colname";
let hideHandleContainer = "hide-handle-container";

const SELECTORS = {
    HIDE_ELEMENT: '[data-action="hide"], [data-action="show"]',
    tableHeader: columnName => `th[data-${dataAttribute}="${columnName}"]`,
    tableColumn: columnName => `td[data-${dataAttribute}="${columnName}"]`,
};

/**
 * Initialize module
 * @param {HTMLElement} columnTable column table
 */
const addEventListeners = columnTable => {
    columnTable.addEventListener('click', function (e) {
        // eslint-disable-next-line
        const hideShowElement = e.target.closest(SELECTORS.HIDE_ELEMENT);
        // Return if it is not 'hide' button.
        if (!hideShowElement) {
            return;
        }
        const target = hideShowElement.dataset.target;
        const header = columnTable.querySelector(SELECTORS.tableHeader(target));
        const action = hideShowElement.dataset.action;
        if (action == "hide") {
            // Hide content of the header.
            header.children.forEach(node => {
                node.classList.remove("d-flex");
                node.classList.add("d-none");
            });
            // Remove current width.
            header.style.width = "";
            header.appendChild(hideShowElement);
            // Change action and icon.
            hideShowElement.dataset.action = "show";
            hideShowElement.querySelector(".icon").classList.remove("fa-eye");
            hideShowElement.querySelector(".icon").classList.add("fa-eye-slash");
            // Hide columns.
            const columns = columnTable.querySelectorAll(SELECTORS.tableColumn(target));
            columns.forEach(column => {
                column.style.visibility = "hidden";
            });
        } else {
            const container = header.querySelector(hideHandleContainer);
            container.appendChild(hideShowElement);
            // Show content of the header.
            header.children.forEach(node => {
                node.classList.remove("d-none");
                node.classList.add("d-flex");
            });
            // Change action and icon.
            hideShowElement.dataset.action = "hide";
            hideShowElement.querySelector(".icon").classList.add("fa-eye");
            hideShowElement.querySelector(".icon").classList.remove("fa-eye-slash");
            // Show columns.
            const columns = columnTable.querySelectorAll(SELECTORS.tableColumn(target));
            columns.forEach(column => {
                column.style.visibility = "";
            });
        }
    });
};

/**
 * Initialize module
 * @param {String} id unique id for columns.
 * @param {String} dataAttr data attribute to identify column.
 * @param {String} handleContainer container class that will hold the hide icon.
 */
export const init = (id, dataAttr, handleContainer) => {
    const columnTable = document.querySelector(`#${id}`);
    dataAttribute = dataAttr;
    hideHandleContainer = handleContainer;

    // Add Handles to the table header.
    const tableHeaders = columnTable.querySelectorAll("th");
    tableHeaders.forEach( header => {
        const context = {
            action: "hide",
            target: header.dataset[dataAttribute],
            title: "hide",
            pixicon: "i/hide",
            pixcomponent: "core"
        };
        Templates.renderForPromise('core/action_handle', context)
            .then(({html, js}) => {
                const container = header.querySelector(hideHandleContainer);
                Templates.appendNodeContents(container, html, js);
            })
            .catch(ex => displayException(ex));
    });

    // Add class to each column.
    const rows = columnTable.querySelectorAll("tr");
    rows.forEach(row => {
        const columns = row.querySelectorAll("td");
        for (let i = 0; i < columns.length; i++) {
            columns[i].dataset[dataAttribute] = tableHeaders[i].dataset[dataAttribute];
        }
    });

    addEventListeners(columnTable);
};
