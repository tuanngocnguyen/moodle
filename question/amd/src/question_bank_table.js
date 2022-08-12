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

import * as ColumnAction from 'core/table_column_action';
import Notification from 'core/notification';
import Ajax from 'core/ajax';

/**
 * Initialize module
 * @param {Array} currentHiddenColumns current hidden columns
 * @param {Array} currentPinnedColumns current pinned columns
 * @param {String} currentColumnSizes current pinned columns
 * @param {Bool} isEditing whether it is in editing mode
 */
export const init = (currentHiddenColumns, currentPinnedColumns, currentColumnSizes, isEditing) => {
    ColumnAction.setUpTable("categoryquestions", "pluginname", "name");

    ColumnAction.setUpCurrentPinnedColumns(currentPinnedColumns);
    ColumnAction.setUpCurrentColumnSizes(currentColumnSizes);

    if (isEditing) {
        ColumnAction.setUpHideShowDropdown("#show-hide-dropdown", currentHiddenColumns, (columns) => {
            const call = {
                methodname: 'qbank_columnsortorder_set_hidden_columns',
                args: {columns: columns, preference: 'qbank_view'},
            };
            Ajax.call([call])[0]
                .catch(Notification.exception);
        });

        ColumnAction.setUpMoveHandle(".move-handle", (columns) => {
            const call = {
                methodname: 'qbank_columnsortorder_set_columnbank_order',
                args: {columns: columns, preference: 'qbank_view'},
            };
            Ajax.call([call])[0]
                .catch(Notification.exception);
        });

        ColumnAction.setUpPinHandle(".pin-handle", (columns) => {
            const call = {
                methodname: 'qbank_columnsortorder_set_pinned_columns',
                args: {columns: columns, preference: 'qbank_view'},
            };
            Ajax.call([call])[0]
                .catch(Notification.exception);
        });

        ColumnAction.setUpResizeHandle(".resize-handle", (sizes) => {
            const call = {
                methodname: 'qbank_columnsortorder_set_column_size',
                args: {sizes: sizes, preference: 'qbank_view'},
            };
            Ajax.call([call])[0]
                .catch(Notification.exception);
        });
    }

};
