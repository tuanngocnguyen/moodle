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

declare(strict_types=1);

namespace core_reportbuilder\local\filters;

use core_reportbuilder\local\helpers\database;

/**
 * Text (with option) report filter
 *
 * @package     core_reportbuilder
 * @copyright   2022 Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_with_options extends text {
    /**
     * Return the options for the filter as an array, to be used to populate the select input field
     *
     * @return array
     */
    protected function get_select_options(): array {
        return (array) $this->filter->get_options();
    }

    /**
     * Adds controls specific to this filter in the form.
     *
     * Operator selector use the "$this->name . '_operator'" naming convention and the fields to enter custom values should
     * use "$this->name . '_value'" or _value1/_value2/... in case there is more than one field for their naming.
     *
     * @param \MoodleQuickForm $mform
     */
    public function setup_form(\MoodleQuickForm $mform): void {
        $elements = [];
        $elements['operator'] = $mform->createElement('select', $this->name . '_operator',
            get_string('filterfieldoperator', 'core_reportbuilder', $this->get_header()), $this->get_operators());

        $operator = $this->name . '_operator';

        // Text selector.
        $options = $this->get_select_options();
        $element = (count($options) == count($options, COUNT_RECURSIVE) ? 'select' : 'selectgroups');
        $elements['selector'] = $mform->createElement($element, $this->name . '_selector',
            get_string('filterfieldvalue', 'core_reportbuilder', $this->get_header()), $options);

        // Text value
        $elements['value'] = $mform->createElement('text', $this->name . '_value',
            get_string('filterfieldvalue', 'core_reportbuilder', $this->get_header()));

        $mform->addElement('group', $this->name . '_group', '', $elements, '', false);

        // Hide text value.
        $mform->setType($this->name . '_value', PARAM_RAW);
        $mform->hideIf($this->name . '_value', $operator, 'eq', self::ANY_VALUE);
        $mform->hideIf($this->name . '_value', $operator, 'eq', self::IS_EMPTY);
        $mform->hideIf($this->name . '_value', $operator, 'eq', self::IS_NOT_EMPTY);
        $mform->hideIf($this->name . '_value', $operator, 'eq', self::IS_EQUAL_TO);
        $mform->hideIf($this->name . '_value', $operator, 'eq', self::IS_NOT_EQUAL_TO);

        // Only show selector for equal to and not equal to.
        $mform->setType($this->name . '_selector', PARAM_RAW);
        $mform->hideIf($this->name . '_selector', $operator, 'eq', self::ANY_VALUE);
        $mform->hideIf($this->name . '_selector', $operator, 'eq', self::IS_EMPTY);
        $mform->hideIf($this->name . '_selector', $operator, 'eq', self::IS_NOT_EMPTY);
        $mform->hideIf($this->name . '_selector', $operator, 'eq', self::CONTAINS);
        $mform->hideIf($this->name . '_selector', $operator, 'eq', self::DOES_NOT_CONTAIN);
        $mform->hideIf($this->name . '_selector', $operator, 'eq', self::STARTS_WITH);
        $mform->hideIf($this->name . '_selector', $operator, 'eq', self::ENDS_WITH);
    }

    /**
     * Return filter SQL
     *
     * @param array|null $values
     * @return array array of two elements - SQL query and named parameters
     */
    public function get_sql_filter(?array $values) : array {
        $operator = (int) ($values["{$this->name}_operator"] ?? self::ANY_VALUE);
        if ($operator == self::IS_EQUAL_TO || $operator == self::IS_NOT_EQUAL_TO) {
            // Use the value of selector.
            $values["{$this->name}_value"] = $values["{$this->name}_selector"] ?? '';
        }

        return parent::get_sql_filter($values);
    }
}
