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
    PINNED_CLASS: 'pinned',
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
    const tableHeaders = table.querySelectorAll("th");
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
    const tableHeaders = table.querySelectorAll("th");
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

    // Implement drag and drop.
    new SortableList('tr', {
        moveHandlerSelector: SELECTORS.MOVE_HANDLE,
    });

    jQuery('tr').on(SortableList.EVENTS.DRAGSTART, (event) => {
        event.currentTarget.classList.add('active');
    });

    jQuery('tr').on(SortableList.EVENTS.DROP, (event) => {
        // Current header.
        const header = event.target;
        // Find the previous sibling of the header, which will be used when moving columns.
        const insertAfter = header.previousElementSibling;

        // Move columns.
        const columns = table.querySelectorAll(SELECTORS.tableColumn(header.dataset[dataIdAttribute]));
        columns.forEach(column => {
            const row = column.parentElement;
            if (insertAfter) {
                // Find the column to insert after.
                const insertAfterColumn = row.querySelector(SELECTORS.tableColumn(insertAfter.dataset[dataIdAttribute]));
                // Insert the column.
                insertAfterColumn.after(column);
            } else {
                // Insert as the first child (first column in the table).
                row.insertBefore(column, row.firstChild);
            }
        });
        // Remove active class.
        table.querySelectorAll('tr').forEach(item => item.classList.remove('active'));

        // Persist column order.
        const columnOrder = getColumnOrder();
        callback(columnOrder);

        // Update pinned headers.
        if (currentPinnedHeader) {
            const stopAtHeader = table.querySelector(SELECTORS.tableHeader(currentPinnedHeader));
            processPinnedHeaders(true, stopAtHeader);
        }
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
    element.classList.add('pinned');
};

/**
 * Unpin an element
 * @param {Element} element the node that will be unpinned
 */
const unpinElement = (element) => {
    element.style.position = "";
    element.style.zIndex = "";
    element.style.backgroundColor = "";
    element.classList.remove('pinned');
};

/**
 * Process pinned elements
 * @param {bool} toBePinned to be pinned or unpinned
 * @param {Element} stopAtHeader stop pinning after the header
 */
const processPinnedHeaders = (toBePinned, stopAtHeader) => {
        // Should be less than zIndex of dropdown - action men.
        let zIndex = 999;
        let left = 0;
        let pinnedHeaders = [];
        const tableHeaders = table.querySelectorAll("th");

        // Unpin all headers.
        tableHeaders.forEach(header => {
            // Unpin header.
            unpinElement(header);
            // Unpin columns.
            const columns = table.querySelectorAll(SELECTORS.tableColumn(header.dataset[dataIdAttribute]));
            columns.forEach(column => {
                unpinElement(column);
            });
        });

        // Pin headers.
        if (toBePinned) {
            const tableHeadersArr = Array.prototype.slice.call(tableHeaders);
            tableHeadersArr.some(header => {
                zIndex -= 1;
                const width = header.offsetWidth;
                // Pin header.
                pinnedHeaders.push(header.dataset[dataIdAttribute]);
                pinElement(header, width, zIndex, left);

                // Pin columns.
                const columns = table.querySelectorAll(SELECTORS.tableColumn(header.dataset[dataIdAttribute]));
                columns.forEach(column => {
                    pinElement(column, width, zIndex, left);
                });

                // Increase margin.
                left += width;

                // End sticky headers.
                return (header == stopAtHeader);
            });

        }

        // Return pinned headers.
        return pinnedHeaders;
};
/**
 * Set up pin handle
 * @param {String} handleContainer container class that will hold the pin icon.
 * @param {Function} callback function to run after a header has been pinned.
 */
export const setUpPinHandle = (handleContainer, callback) => {
    // Add "pin icon" for each header.
    const tableHeaders = table.querySelectorAll("th");
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

        // pin all headers to the clicked header.
        const target = pinHandle.dataset.target;
        const stopAtHeader = table.querySelector(SELECTORS.tableHeader(target));
        let toBePinned = true;
        if (currentPinnedHeader == stopAtHeader.dataset[dataIdAttribute]) {
            // Unpinned all headers.
            toBePinned = false;
            currentPinnedHeader = '';
        } else {
            // Track current pinned header.
            currentPinnedHeader = stopAtHeader.dataset[dataIdAttribute];
        }

        const pinnedHeaders = processPinnedHeaders(toBePinned, stopAtHeader);

        // Call back function to process pinned header.
        callback(pinnedHeaders);
    });
};

/**
 * Set up resize handle
 * @param {String} handleContainer container class that will hold the move icon.
 * @param {Function} callback function to run after a header has been resized.
 */
export const setUpResizeHandle = (handleContainer, callback) => {
    // Add "move icon" for each header.
    const tableHeaders = table.querySelectorAll("th");
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

    // Start mouse event on headers.
    table.addEventListener('mousedown', function(e) {
        const resizeHandle = e.target.closest(SELECTORS.RESIZE_HANDLE);
        // Return if it is not ' resize' button.
        if (!resizeHandle) {
            return;
        }
        // Save current position.
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

    // Set new size when mouse is up.
    table.addEventListener('mouseup', function() {
        if (!currentHeader || currentX == 0) {
            return;
        }

        let columnSizes = [];
        const tableHeaders = table.querySelectorAll("th");
        tableHeaders.forEach(header => {
            // Only get the width set via style attribute (set by pin or move action).
            let size = {
               column: header.dataset[dataIdAttribute],
               width: header.style.width
            };
            columnSizes.push(size);
        });
        callback(JSON.stringify(columnSizes));
        currentHeader = null;
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
    const tableHeaders = table.querySelectorAll("th");
    tableHeaders.forEach(header => {
        let visible = true;
        // Hide column if it is one of current hidden columns.
        if (currentHiddenColumns && currentHiddenColumns.includes(header.dataset[dataIdAttribute])) {
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
 * Current pinned columns
 * @param {Array} currentPinnedColumns pinned columns
 */
export const setUpCurrentPinnedColumns = (currentPinnedColumns) => {
    // Existing pinned headers.
    if (currentPinnedColumns) {
        // Save pinned header.
        currentPinnedHeader = currentPinnedColumns[currentPinnedColumns.length - 1];
        const stopAtHeader = table.querySelector(SELECTORS.tableHeader(currentPinnedHeader));
        processPinnedHeaders(true, stopAtHeader);
    }
};

/**
 * Current columns sizes
 * @param {String} currentColumnSizes column sizes
 */
export const setUpCurrentColumnSizes = (currentColumnSizes) => {
    // Set sizes for each header.
    if (currentColumnSizes) {
        const decodedSizes = JSON.parse(currentColumnSizes);
        decodedSizes.forEach(colSize => {
            if (colSize.width != '') {
                const header = table.querySelector(SELECTORS.tableHeader(colSize.column));
                header.style.width = colSize.width;
            }
        });
    }
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

    // Add class to each column as to identify them later.
    const tableHeaders = table.querySelectorAll("th");
    const rows = table.querySelectorAll("tr");
    rows.forEach(row => {
        const columns = row.querySelectorAll("td");
        for (let i = 0; i < columns.length; i++) {
            columns[i].dataset[dataIdAttribute] = tableHeaders[i].dataset[dataIdAttribute];
        }
    });
};
