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
import SortableList from 'core/sortable_list';
import jQuery from 'jquery';

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

/** To track current pinned header */
let currentPinnedHeader;

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
 * Gets the newly reordered columns to display in the question bank view.
 * @returns {Array}
 */
 const getColumnOrder = () => {
    const columns = Array.from(tableHeaders).map(column => column.dataset[dataIdAttribute]);
    return columns.filter((value, index) => columns.indexOf(value) === index);
};

/**
 * Set up move handle
 * @param {String} handleContainer container class that will hold the move icon.
 * @param {Function} callback function to run after a header has been moved.
 */
export const setUpMoveHandle = (handleContainer, callback) => {
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

    new SortableList('tr', {
        moveHandlerSelector: SELECTORS.MOVE_HANDLE,
    });

    jQuery('tr').on(SortableList.EVENTS.DRAGSTART, (event) => {
        event.currentTarget.classList.add('active');
    });

    jQuery('tr').on(SortableList.EVENTS.DROP, (event) => {
        const header = event.target;
        const insertAfter = header.previousElementSibling;

        // Move column.
        const columns = table.querySelectorAll(SELECTORS.tableColumn(header.dataset[dataIdAttribute]));
        columns.forEach(column => {
            const row = column.parentElement;
            if (insertAfter) {
                // Find the column to insert after.
                const insertAfterColumn = row.querySelector(SELECTORS.tableColumn(insertAfter.dataset[dataIdAttribute]));
                insertAfterColumn.after(column);
            } else {
                // Insert as the first child.
                row.insertBefore(column, row.firstChild);
            }

        });
        table.querySelectorAll('tr').forEach(item => item.classList.remove('active'));
        // Update table header array.
        tableHeaders = table.querySelectorAll("th");
        // Persist column order.
        const columnOrder = getColumnOrder();
        callback(columnOrder);
    });
};

/**
 * Pin an element
 * @param {Element} element the node that will be pinned
 * @param {Number} width the width of pinned node.
 * @param {Number} zIndex so that the pinned node will be laid above other element.
 * @param {Number} left distance to the left border of the table.
 */
const pinElement = (element, width, zIndex, left) => {
    element.style.position = "sticky";
    element.style.width = width + "px";
    element.style.zIndex = zIndex;
    element.style.left = left + "px";
    element.style.backgroundColor = "wheat";
};

/**
 * Set up pin handle
 * @param {String} handleContainer container class that will hold the pin icon.
 * @param {Array} currentPinnedColumns pinned columns
 * @param {Function} callback function to run after a header has been pinned.
 */
export const setUpPinHandle = (handleContainer, currentPinnedColumns, callback) => {
    // Add "pin icon" for each header.
    tableHeaders.forEach(header => {
        const context = {
            action: "pin",
            target: header.dataset[dataIdAttribute],
            title: "pin",
            icon: '<i class="fa fa-thumb-tack mr-1" aria-hidden="true"></i>'
        };
        const container = header.querySelector(handleContainer);
        addHandle(context, container);
    });

    // Mouse event on headers.
    table.addEventListener('click', function(e) {
        const pinHandle = e.target.closest(SELECTORS.PIN_HANDLE);
        // Return if it is not ' pin' button.
        if (!pinHandle) {
            return;
        }

        // pin all header to until current clicked header.
        const target = pinHandle.dataset.target;
        const pinHeader = table.querySelector(SELECTORS.tableHeader(target));
        let toBePinned = true;
        if (currentPinnedHeader == pinHeader.dataset[dataIdAttribute]) {
            // Unpinned all header.
            toBePinned = false;
            currentPinnedHeader = '';
        } else {
            // Track current pinned header.
            currentPinnedHeader = pinHeader.dataset[dataIdAttribute];
        }
        // Should be less than zIndex of dropdown.
        let zIndex = 999;
        let left = 0;
        let pinnedHeaders = [];
        tableHeaders.forEach(header => {
            zIndex -= 1;
            const width = header.offsetWidth;
            if (toBePinned) {
                // Pin header.
                pinnedHeaders.push(header.dataset[dataIdAttribute]);
                pinElement(header, width, zIndex, left);

                // Pin columns.
                const columns = table.querySelectorAll(SELECTORS.tableColumn(header.dataset[dataIdAttribute]));
                columns.forEach(column => {
                    pinElement(column, width, zIndex, left);
                });
            } else {
                // Unpin header.
                header.style.position = "";
                header.style.backgroundColor = "";
                // Unpin column.
                const columns = table.querySelectorAll(SELECTORS.tableColumn(header.dataset[dataIdAttribute]));
                columns.forEach(column => {
                    column.style.position = "";
                    column.style.backgroundColor = "";
                });
            }

            // Stop when reaching the clicked header.
            if (header == pinHeader) {
                toBePinned = false;
            }

            // Increase margin.
            left += width;
        });

        // Call back function to process pinned header.
        callback(pinnedHeaders);

    });

    // Existing pinned headers.
    let zIndex = 999;
    let left = 0;
    console.log(currentPinnedColumns);    currentPinnedColumns.forEach(id => {
        const header = table.querySelector(SELECTORS.tableHeader(id));
        if (header) {
            const width = header.offsetWidth;
            pinElement(header, width, zIndex, left);
            // Pin columns.
            const columns = table.querySelectorAll(SELECTORS.tableColumn(header.dataset[dataIdAttribute]));
            columns.forEach(column => {
                pinElement(column, width, zIndex, left);
            });
            left += width;
            zIndex -= 1;
            currentPinnedHeader = id;
        }
    });

};

/**
 * Set up resize handle
 * @param {String} handleContainer container class that will hold the move icon.
 * @param {Array} currentColumnSize sizes of current columns
 * @param {Function} callback function to run after a header has been resized.
 */
export const setUpResizeHandle = (handleContainer, currentColumnSize, callback) => {
    // Existing size.
    const decodedSizes = JSON.parse(currentColumnSize);
    decodedSizes.forEach(colSize => {
        if (colSize.width != '') {
            const header = table.querySelector(SELECTORS.tableHeader(colSize.column));
            header.style.width = colSize.width;
            console.log(header);
        }
    });

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
        if (!currentHeader || currentX == 0) {
            return;
        }

        let columnSize = [];
        tableHeaders.forEach(header => {
            // Only get the width set via style attribute (set by pin or move action).
            let size = {
               column: header.dataset[dataIdAttribute],
               width: header.style.width
            };
            columnSize.push(size);
        });
        callback(JSON.stringify(columnSize));
        currentHeader = undefined;
        currentX = 0;
    });
};

/**
 * Set up hide/show dropdown
 * @param {String} dropdownContainer container class that will hold the hide/show dropdwon.
 * @param {Array} currentHiddenColumns current hidden columns
 * @param {Function} callback function to run after a header has been hidden/shown.
 */
export const setUpHideShowDropdown = (dropdownContainer, currentHiddenColumns, callback) => {
    const container = document.querySelector(dropdownContainer);

    let context = {
        columns: [],
        title: "Drop down menu",
        text: "Show/Hide Column",
        pixicon: "i/hide",
        pixcomponent: "core"
    };
    tableHeaders.forEach(header => {
        let visible = true;
        // Hide column if it is one of current hidden columns.
        if (currentHiddenColumns.includes(header.dataset[dataIdAttribute])) {
            visible = false;
            // Hide header.
            header.style.display = "none";
            // Hide column.
            const columns = table.querySelectorAll(SELECTORS.tableColumn(header.dataset[dataIdAttribute]));
            columns.forEach(column => {
                column.style.display = "none";
            });
        }
        // Data for checkbox.
        const column = {
            id: header.dataset[dataIdAttribute],
            name: header.dataset[dataNameAttribute],
            checked: visible
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
                    if (element.checked == true) {
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

                    let hiddenColumns = [];
                    checkboxes.forEach(checkbox => {
                        if (checkbox.checked == false) {
                            hiddenColumns.push(checkbox.value);
                        }
                    });
                    callback(hiddenColumns);

                });
            });
        })
        .catch(ex => displayException(ex));
};

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
