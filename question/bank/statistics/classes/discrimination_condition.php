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

namespace qbank_statistics;

use core_question\local\bank\condition;

/**
 * Question bank search class to allow searching/filtering by discrimination index on a question.
 *
 * @package    qbank_statistics
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discrimination_condition extends condition {
    /** @var string SQL fragment to add to the where clause. */
    protected $where;

    public function __construct($qbank) {
        $this->filters = $qbank->get_pagevars('filters');
        // Build where and params.
        list($this->where, $this->params) = self::build_query_from_filters($this->filters);
    }

    public function where() {
        return $this->where;
    }

    public function get_condition_key() {
        return 'discrimination';
    }

    /**
     * Return parameters to be bound to the above WHERE clause fragment.
     * @return array parameter name => value.
     */
    public function params() {
        return [];
    }

    /**
     * Display GUI for selecting criteria for this condition. Displayed when Show More is open.
     *
     * Compare display_options(), which displays always, whether Show More is open or not.
     * @return bool|string HTML form fragment
     * @deprecated since Moodle 4.0 MDL-72321 - please do not use this function any more.
     * @todo Final deprecation on Moodle 4.1 MDL-72572
     */
    public function display_options_adv() {
        debugging('Function display_options_adv() is deprecated, please use filtering objects', DEBUG_DEVELOPER);
        return false;
    }

    /**
     * Display GUI for selecting criteria for this condition. Displayed always, whether Show More is open or not.
     *
     * Compare display_options_adv(), which displays when Show More is open.
     * @return bool|string HTML form fragment
     * @deprecated since Moodle 4.0 MDL-72321 - please do not use this function any more.
     * @todo Final deprecation on Moodle 4.1 MDL-72572
     */
    public function display_options() {
        debugging('Function display_options() is deprecated, please use filtering objects', DEBUG_DEVELOPER);
        return false;
    }

    /**
     * Get options for filter.
     *
     * @return array
     */
    public function get_filter_options(): array {
        return [
            'name' => 'discrimination',
            'title' => 'Discrimination index',
            'custom' => true,
            'multiple' => true,
            'conditionclass' => get_class($this),
            'filterclass' => 'qbank_statistics/datafilter/filtertypes/discrimination',
            'values' => [],
            'allowempty' => true,
        ];
    }

    /**
     * Get the list of available joins for the filter.
     *
     * @return array
     */
    public function get_join_list(): array {
        return [
            self::JOINTYPE_NONE => get_string('none'),
            self::JOINTYPE_ANY => get_string('any'),
            self::JOINTYPE_ALL => get_string('all'),
        ];
    }

    /**
     * Build query from filter value
     *
     * @param array $filters filter objects
     * @return array where sql and params
     */
    public static function build_query_from_filters(array $filters): array {
        if (isset($filters['discrimination'])) {
            $filter = (object) $filters['discrimination'];
            $where = 'q.id IN (SELECT qs.questionid
                               FROM {question_statistics} qs
                          LEFT JOIN {question} q ON qs.questionid = q.id
                           GROUP BY qs.questionid
                              HAVING AVG(qs.discriminationindex)';
            if ($filter->rangetype === self::RANGETYPE_AFTER) {
                $where .= ' > ' . $filter->values[0] . ')';
            }
            if ($filter->rangetype === self::RANGETYPE_BEFORE) {
                $where .= ' < ' . $filter->values[0] . ')';
            }
            if ($filter->rangetype === self::RANGETYPE_BETWEEN) {
                $where .= ' > '
                    . $filter->values[0]
                    . ' AND AVG(qs.discriminationindex) <'
                    . $filter->values[1]
                    . ')';
            }
            return [$where, []];
        }
        return ['', []];
    }
}
