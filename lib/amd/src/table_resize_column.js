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
 * Javascript for resizing columns.
 *
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Nathan Nguyen <nathannguyen@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {exception as displayException} from 'core/notification';
import Templates from 'core/templates';

let currentColumn;
let currentX;
let dataAttribute = "colname";

const SELECTORS = {
    RESIZE_ELEMENT: '[data-action="resize"]',
    tableHeader: columnName => `[data-${dataAttribute}="${columnName}"]`,
};

/**
 * Initialize module
 * @param {HTMLElement} columnTable column table
 */
const addEventListeners = columnTable => {
    columnTable.addEventListener('mousedown', function (e) {
        const resizeElement = e.target.closest(SELECTORS.RESIZE_ELEMENT);
        // Return if it is not ' resize' button.
        if (!resizeElement) {
            return;
        }
        currentX = e.pageX;
        // Find the header.
        const target = resizeElement.dataset.target;
        currentColumn = columnTable.querySelector(SELECTORS.tableHeader(target));
    });

    columnTable.addEventListener('mousemove', function (e) {
        if (!currentColumn || currentX == 0) {
            return;
        }

        // Offset.
        const offset = e.pageX - currentX;
        currentX = e.pageX;
        const newWidth = currentColumn.offsetWidth + offset;
        currentColumn.style.width = newWidth + 'px';
    });

    columnTable.addEventListener('mouseup', function () {
        // Reset.
        currentColumn = undefined;
        currentX = 0;
    });
};


/**
 * Initialize module
 * @param {String} id unique id for columns.
 * @param {String} dataAttr data attribute to identify column.
 * @param {String} handleContainer container class that will hold the resize handle.
 */
export const init = (id, dataAttr, handleContainer) => {
    const columnTable = document.querySelector(`#${id}`);
    dataAttribute = dataAttr;

    // Add Handles to the table header.
    const tableHeaders = columnTable.querySelectorAll("th");
    tableHeaders.forEach(header => {
        const context = {
            action: "resize",
            target: header.dataset[dataAttribute],
            title: "resize",
            pixicon: "i/twoway",
            pixcomponent: "core"
        };
        Templates.renderForPromise('core/action_handle', context)
            .then(({html, js}) => {
                const container = header.querySelector(handleContainer);
                Templates.appendNodeContents(container, html, js);
            })
            .catch(ex => displayException(ex));
    });
    addEventListeners(columnTable);
};
