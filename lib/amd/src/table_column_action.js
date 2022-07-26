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
 * Javascript for action on table columns.
 *
 * @module core/table_column_action
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Nathan Nguyen <nathannguyen@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';

/** The table that we will add action */
let table;

/** Data attribute used to identify each colum */
let dataIdAttribute;

/** Data attribute used to display name of a column */
let dataNameAttribute;

/** Table header nodes */
let tableHeaders;

/** To track mouse event on a table header */
let currentHeader;

/** Current mouse x postion, to track mouse event on a table header */
let currentX;

const SELECTORS = {
    MOVE_HANDLE: '[data-action="move"]',
    RESIZE_HANDLE: '[data-action="resize"]',
    PIN_HANDLE: '[data-action="pin"]',
    tableHeader: identifier => `th[data-${dataIdAttribute}="${identifier}"]`,
    tableColumn: identifier => `td[data-${dataIdAttribute}="${identifier}"]`,
};

/**
 * Add handle
 * @param {Object} context data for each handle.
 * @param {Element} container container cthat will hold a action icon
 */
const addHandle = (context, container) => {
    Templates.renderForPromise('core/action_handle', context)
        .then(({html, js}) => {
            Templates.appendNodeContents(container, html, js);
        })
        .catch(ex => displayException(ex));
};

/**
 * Set up move handle
 * @param {String} handleContainer container class that will hold the move icon.
 */
export const setUpMoveHandle = (handleContainer) => {
    // Add "move icon" for each header.
    tableHeaders.forEach(header => {
        const context = {
            action: "move",
            target: header.dataset[dataIdAttribute],
            title: "move",
            pixicon: "i/dragdrop",
            pixcomponent: "core"
        };
        const container = header.querySelector(handleContainer);
        addHandle(context, container);
    });
};

/**
 * Set up pin handle
 * @param {String} handleContainer container class that will hold the pin icon.
 */
export const setUpPinHandle = (handleContainer) => {
    // Add "move icon" for each header.
    tableHeaders.forEach(header => {
        const context = {
            action: "pin",
            target: header.dataset[dataIdAttribute],
            title: "pin",
            pixicon: "i/down",
            pixcomponent: "core"
        };
        const container = header.querySelector(handleContainer);
        addHandle(context, container);
    });
};

/**
 * Set up resize handle
 * @param {String} handleContainer container class that will hold the move icon.
 */
export const setUpResizeHandle = (handleContainer) => {
    // Add "move icon" for each header.
    tableHeaders.forEach(header => {
        const context = {
            action: "resize",
            target: header.dataset[dataIdAttribute],
            title: "resize",
            pixicon: "i/twoway",
            pixcomponent: "core"
        };
        const container = header.querySelector(handleContainer);
        addHandle(context, container);
    });

    // Mouse event on headers.
    table.addEventListener('mousedown', function(e) {
        const resizeHandle = e.target.closest(SELECTORS.RESIZE_HANDLE);
        // Return if it is not ' resize' button.
        if (!resizeHandle) {
            return;
        }
        currentX = e.pageX;
        // Find the header.
        const target = resizeHandle.dataset.target;
        currentHeader = table.querySelector(SELECTORS.tableHeader(target));
    });

    // Resize column as the mouse move.
    table.addEventListener('mousemove', function(e) {
        if (!currentHeader || currentX == 0) {
            return;
        }

        // Offset.
        const offset = e.pageX - currentX;
        currentX = e.pageX;
        const newWidth = currentHeader.offsetWidth + offset;
        currentHeader.style.width = newWidth + 'px';
    });

    // Reset.
    table.addEventListener('mouseup', function() {
        currentHeader = undefined;
        currentX = 0;
    });
};

export const setUpHideShowDropdown = (dropdownContainer) => {
    const container = document.querySelector(dropdownContainer);

    let context = {
        columns: [],
        title: "Drop down menu",
        text: "Show/Hide Column",
        pixicon: "i/hide",
        pixcomponent: "core"
    };
    tableHeaders.forEach(header => {
        const column = {
            id: header.dataset[dataIdAttribute],
            name: header.dataset[dataNameAttribute],
            checked: true
        };
        context.columns.push(column);

    });

    Templates.renderForPromise('core/checkbox_dropdown', context)
        .then(({html, js}) => {
            Templates.appendNodeContents(container, html, js);
        })
        .then(()=> {
            const checkboxes = container.querySelectorAll("input[type=checkbox]");
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('click', function (e) {
                    const element = e.target;
                    const target = element.value;
                    const header = table.querySelector(SELECTORS.tableHeader(target));
                    if (element.checked == true){
                        // Show header.
                        header.style.display = "";
                        // Show column.
                        const columns = table.querySelectorAll(SELECTORS.tableColumn(target));
                        columns.forEach(column => {
                            column.style.display = "";
                        });
                    } else {
                        // Hide header.
                        header.style.display = "none";
                        // Hide column.
                        const columns = table.querySelectorAll(SELECTORS.tableColumn(target));
                        columns.forEach(column => {
                            column.style.display = "none";
                        });
                    }
                });
            });
        })
        .catch(ex => displayException(ex));
};

// /**
//  * Initialize module
//  * @param {HTMLElement} columnTable column table
//  */
// const addEventListeners = columnTable => {
//     columnTable.addEventListener('click', function (e) {
//         // eslint-disable-next-line
//         const hideShowElement = e.target.closest(SELECTORS.HIDE_ELEMENT);
//         // Return if it is not 'hide' button.
//         if (!hideShowElement) {
//             return;
//         }
//         const target = hideShowElement.dataset.target;
//         const header = columnTable.querySelector(SELECTORS.tableHeader(target));
//         // Hide header.
//         header.style.display = "none";
//         // Hide column.
//         const columns = columnTable.querySelectorAll(SELECTORS.tableColumn(target));
//         columns.forEach(column => {
//             column.style.display = "none";
//         });
//         // const action = hideShowElement.dataset.action;
//         // if (action == "hide") {
//             // // Hide content of the header.
//             // header.children.forEach(node => {
//             //     node.classList.remove("d-flex");
//             //     node.classList.add("d-none");
//             // });
//             // // Remove current width.
//             // header.style.width = "";
//             // header.appendChild(hideShowElement);
//             // // Change action and icon.
//             // hideShowElement.dataset.action = "show";
//             // hideShowElement.querySelector(".icon").classList.remove("fa-eye");
//             // hideShowElement.querySelector(".icon").classList.add("fa-eye-slash");
//             // // Hide columns.
//             // const columns = columnTable.querySelectorAll(SELECTORS.tableColumn(target));
//             // columns.forEach(column => {
//             //     column.style.visibility = "hidden";
//             // });
//         // } else {
//             // const container = header.querySelector(hideHandleContainer);
//             // container.appendChild(hideShowElement);
//             // // Show content of the header.
//             // header.children.forEach(node => {
//             //     node.classList.remove("d-none");
//             //     node.classList.add("d-flex");
//             // });
//             // // Change action and icon.
//             // hideShowElement.dataset.action = "hide";
//             // hideShowElement.querySelector(".icon").classList.add("fa-eye");
//             // hideShowElement.querySelector(".icon").classList.remove("fa-eye-slash");
//             // // Show columns.
//             // const columns = columnTable.querySelectorAll(SELECTORS.tableColumn(target));
//             // columns.forEach(column => {
//             //     column.style.visibility = "";
//             // });
//         // }
//     });
// };

/**
 * Initialize module
 * @param {String} id unique id for columns.
 * @param {String} dataIdAttr data attribute to identify column.
 * @param {String} dataNameAttr data attribute that container column name.
 */
export const setUpTable = (id, dataIdAttr, dataNameAttr) => {
    table = document.querySelector(`#${id}`);
    dataIdAttribute = dataIdAttr;
    dataNameAttribute = dataNameAttr;
    tableHeaders = table.querySelectorAll("th");

    // Add class to each column as to identify them later.
    const rows = table.querySelectorAll("tr");
    rows.forEach(row => {
        const columns = row.querySelectorAll("td");
        for (let i = 0; i < columns.length; i++) {
            columns[i].dataset[dataIdAttribute] = tableHeaders[i].dataset[dataIdAttribute];
        }
    });
};
