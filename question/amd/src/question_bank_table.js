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
 * @module     core_question/question_bank_table
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Nathan Nguyen <nathannguyen@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {setUpTable, setUpMoveHandle, setUpPinHandle, setUpResizeHandle, setUpHideShowDropdown} from 'core/table_column_action';
import Notification from 'core/notification';
import Ajax from 'core/ajax';

/**
 * Initialize module
 * @param {Array} currentHiddenColumns current hidden columns
 * @param {Array} currentPinnedColumns current pinned columns
 */
export const init = (currentHiddenColumns, currentPinnedColumns) => {
    setUpTable("categoryquestions", "pluginname", "name");

    setUpHideShowDropdown("#show-hide-dropdown", currentHiddenColumns, (args) => {
        const call = {
            methodname: 'qbank_columnsortorder_set_hidden_columns',
            args: {columns: columns, default: false},
        };
        Ajax.call([call])[0]
            .catch(Notification.exception);
    });

    setUpMoveHandle(".move-handle", (columns) => {
        const call = {
            methodname: 'qbank_columnsortorder_set_columnbank_order',
            args: {columns: columns, default: false},
        };
        Ajax.call([call])[0]
            .catch(Notification.exception);
    });

    setUpPinHandle(".pin-handle", currentPinnedColumns, (args) => {
        const call = {
            methodname: 'qbank_columnsortorder_set_pinned_columns',
            args: {columns: columns, default: false},
        };
        Ajax.call([call])[0]
            .catch(Notification.exception);
    });

    setUpResizeHandle(".resize-handle", (args) => {
        console.log(args);
    });

};
