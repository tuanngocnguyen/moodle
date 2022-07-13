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
 * Javascript for sorting columns in question bank view.
 *
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Nathan Nguyen <nathannguyen@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

let currentColumn;
let currentX;

const SELECTORS = {
    RESIZE_ELEMENT: '[data-action="resize"]',
    tableHeader: columnName => `.header[data-pluginname="${columnName}"]`,
};

/**
 * Initialize module
 * @param {HTMLElement} columnTable column table
 */
const addEventListeners = columnTable => {
    columnTable.addEventListener('mousedown', function (e) {
        const resizeElement = e.target.closest(SELECTORS.RESIZE_ELEMENT);
        // Return if it is not 'addeditcategory' button.
        if (!resizeElement) {
            return;
        }
        currentX = e.pageX;
        // Find the header.
        const pluginName = resizeElement.dataset.pluginname;
        currentColumn = columnTable.querySelector(SELECTORS.tableHeader(pluginName));

    });

    columnTable.addEventListener('mousemove', function (e) {
        if (!currentColumn) {
            return;
        }

        // Offset.
        const offset = e.pageX - currentX;
        const newWidth = currentColumn.offsetWidth + offset;
        currentColumn.style.width = newWidth + 'px';

        // eslint-disable-next-line
        console.log(newWidth);
    });

    columnTable.addEventListener('mouseup', function () {
        // Reset.
        currentColumn = undefined;

    });
};


/**
 * Initialize module
 * @param {String} id unique id for columns.
 */
export const init = id => {
    const columnTable = document.querySelector(`#${id}`);
    addEventListeners(columnTable);
};
