<?php
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

namespace qbank_columnsortorder\external;

use context_system;
use Exception;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_value;
use qbank_columnsortorder\column_manager;

/**
 * External qbank_columnsortorder_set_hidden_columns API
 *
 * @package    qbank_columnsortorder
 * @category   external
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     2022, Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_hidden_columns extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'columns' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Plugin name for the hidden column', VALUE_REQUIRED)
            ),
            'default' => new external_value(PARAM_BOOL, 'Is this user preference or site default', VALUE_DEFAULT, true),
        ]);
    }

    /**
     * Returns description of method result value.
     *
     */
    public static function execute_returns(): void {
    }

    /**
     * Sticky Columns.
     *
     * @param array $columns json string representing pinned columns.
     * @param bool $default true if it is site default.
     */
    public static function execute(array $columns, bool $default): void {
        [
            'columns' => $columns,
            'default' => $default,
        ]
            = self::validate_parameters(self::execute_parameters(),
        [
            'columns' => $columns,
            'default' => $default,
        ]);
        $context = context_system::instance();
        self::validate_context($context);
        // TODO: Discuss required caps at siteadmin / course page.
        require_capability('moodle/category:manage', $context);

        column_manager::set_hidden_columns($columns, $default);
    }
}
