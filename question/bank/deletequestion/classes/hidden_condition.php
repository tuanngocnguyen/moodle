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

namespace qbank_deletequestion;

use core_question\local\bank\condition;

use core_question\local\bank\question_version_status;

/**
 * This class controls whether hidden / deleted questions are hidden in the list.
 *
 * @package    qbank_deletequestion
 * @copyright  2013 Ray Morris
 * @author     2021 Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hidden_condition extends condition {
    /** @var bool Whether to include old "deleted" questions. */
    protected $hide;

    /** @var string SQL fragment to add to the where clause. */
    protected $where;

    /**
     * Constructor to initialize the hidden condition for qbank.
     */
    public function __construct($qbank) {
        $filters = $qbank->get_pagevars('filters');
        if (isset($filters['hidden'])) {
            $this->filter = (object) $filters['hidden'];
            if (isset($this->filter->values[0])) {
                $this->hide = (int) $this->filter->values[0];
            }
        }
        list($this->where, $this->params) = self::build_query_from_filters($filters);
    }

    public function get_condition_key() {
        return 'hidden';
    }

    /**
     * SQL fragment to add to the where clause.
     *
     * @return string
     */
    public function where() {
        return  $this->where;
    }

    /**
     * Get options for filter.
     *
     * @return array
     */
    public function get_filter_options(): array {
        return [
            'name' => 'hidden',
            'title' => get_string('showhidden', 'core_question'),
            'custom' => true,
            'multiple' => true,
            'conditionclass' => get_class($this),
            'filterclass' => 'qbank_deletequestion/datafilter/filtertypes/hidden',
            'values' => [],
            'allowempty' => true,
        ];
    }

    /**
     * Build query from filter value
     *
     * @param array $filters filter objects
     * @return array where sql and params
     */
    public static function build_query_from_filters(array $filters): array {
        if (!isset($filters['hidden'])) {
            $where = "qv.status <> '" . question_version_status::QUESTION_STATUS_HIDDEN . "'";
            return [$where, []];
        }

        $filter = (object) $filters['hidden'];
        $where = "qv.status <> '" . question_version_status::QUESTION_STATUS_HIDDEN . "'";
        $hide = (int) $filter->values[0];
        // Show old question 'Yes' is '1'.
        if ($hide === 1) {
            $where = "qv.status = '" . question_version_status::QUESTION_STATUS_READY .
                "' OR qv.status = '" . question_version_status::QUESTION_STATUS_HIDDEN .
                "' OR qv.status = '" . question_version_status::QUESTION_STATUS_DRAFT . "'";
        }
        return [$where, []];

    }
}
