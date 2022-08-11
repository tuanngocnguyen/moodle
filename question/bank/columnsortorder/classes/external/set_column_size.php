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
 * External qbank_columnsortorder_set_column_size API
 *
 * @package    qbank_columnsortorder
 * @category   external
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     2022, Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_column_size extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sizes' => new external_value(PARAM_TEXT, 'Size for each column', VALUE_REQUIRED),
            'preference' => new external_value(PARAM_TEXT, 'User preference', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Returns description of method result value.
     *
     */
    public static function execute_returns(): void {
    }

    /**
     * Column sizes.
     *
     * @param string $sizes json string representing pinned columns.
     * @param string $preference name of user preference.
     */
    public static function execute(string $sizes, string $preference = ''): void {
        [
            'sizes' => $sizes,
            'preference' => $preference,
        ]
            = self::validate_parameters(self::execute_parameters(),
        [
            'sizes' => $sizes,
            'preference' => $preference,
        ]);
        $context = context_system::instance();
        self::validate_context($context);
        // TODO: Discuss required caps at siteadmin / course page.
        require_capability('moodle/category:manage', $context);

        column_manager::set_column_size($sizes, $preference);
    }
}
