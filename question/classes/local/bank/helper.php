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

/**
 * Helper class for question bank and its plugins.
 *
 * All the functions which has a potential to be used by different features or
 * plugins, should go here.
 *
 * @package    core_question
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_question\local\bank;

/**
 * Class helper
 *
 * @package    core_question
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Check the status of a plugin and throw exception if not enabled and called manually.
     *
     * Any action plugin having a php script, should call this function for a safer enable/disable implementation.
     *
     * @param string $pluginname
     * @return void
     */
    public static function require_plugin_enabled(string $pluginname): void {
        if (!\core\plugininfo\qbank::is_plugin_enabled($pluginname)) {
            throw new \moodle_exception('The following plugin is either disabled or missing from disk: ' . $pluginname);
        }
    }

    /**
     * Convert multidimentional object to array.
     *
     * @param $obj
     * @return array|mixed
     */
    public static function convert_object_array($obj) {
        // Not an object or array.
        if (!is_object($obj) && !is_array($obj)) {
            return $obj;
        }
        // Parse array.
        foreach ($obj as $key => $value) {
            $arr[$key] = self::convert_object_array($value);
        }
        // Return parsed array.
        return $arr;
    }

    /**
     * Transform query string to array
     *
     * @param string $query query string
     * @return array
     */
    public static function filter_query_to_array(string $query): array {
        if (empty($query)) {
            return [];
        }

        $filters = [];

        // Filters are join by '&'.
        $encodedfilters = explode('&', $query);

        foreach ($encodedfilters as $encodedfilter) {
            // Filter key and data are separate by '=';
            $encodedfilter = explode('=', $encodedfilter);
            $key = $encodedfilter[0];
            $filters[$key] = [];
            $params = explode('&', urldecode($encodedfilter[1]));
            foreach ($params as $param) {
                $param = explode('=', $param);
                $name = $param[0];
                $values = urldecode($param[1]);
                if ($name == 'values') {
                    if (strpos($values, '=') !== false) {
                        // This containes multiple values.
                        $values = explode('&', $values);
                        foreach ($values as $value) {
                            list($index, $avalue) = explode('=', $value);
                            $filters[$key][$name][$index] = $avalue;
                        }
                    } else {
                        $filters[$key][$name] = [$values];
                    }
                } else {
                    // This container only one value.
                    $filters[$key][$name] = $values;
                }
            }
        }
        return $filters;
    }

}
