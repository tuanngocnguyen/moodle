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

namespace core_question\local\bank;

use renderer_base;
use stdClass;
use core\output\datafilter;

/**
 * Class for rendering filters on the base view.
 *
 * @package    core_question
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Tomo Tsuyuki <tomotsuyuki@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbank_filter extends datafilter {

    /** @var array $searchconditions Searchconditions for the filter. */
    protected $searchconditions = array();

    /** @var int $perpage number of records per page. */
    protected $perpage = 0;

    /**
     * Set searchcondition.
     *
     * @param array $searchconditions
     * @param array $additionalparams
     * @return self
     */
    public function set_searchconditions(array $searchconditions, array $additionalparams): self {
        $this->searchconditions = $searchconditions;
        $this->additionalparams = $additionalparams;
        return $this;
    }

    /**
     * Get data for all filter types.
     *
     * @return array
     */
    protected function get_filtertypes(): array {

        $filtertypes = [];

        foreach ($this->searchconditions as $searchcondition) {
            $filtertypes[] = $this->get_filter_object(
                $searchcondition->get_name(),
                $searchcondition->get_title(),
                $searchcondition->allow_custom(),
                $searchcondition->allow_multiple(),
                $searchcondition->get_filter_class(),
                $searchcondition->get_initial_values(),
                $searchcondition->allow_empty(),
                $searchcondition->get_condition_class()
            );
        }

        return $filtertypes;
    }

    /**
     * Export the renderer data in a mustache template friendly format.
     *
     * @param renderer_base $output Unused.
     * @return stdClass Data in a format compatible with a mustache template.
     */
    public function export_for_template(renderer_base $output): stdClass {
        $defaultcategory = question_get_default_category($this->context->id);
        $courseid = $this->context->instanceid;
        if ($courseid === 0) {
            $courseid = $this->searchconditions['category']->get_course_id();
        }
        return (object) [
            'tableregionid' => $this->tableregionid,
            'courseid' => $courseid,
            'filtertypes' => $this->get_filtertypes(),
            'selected' => 'category',
            'rownumber' => 1,
            'defaultcategoryid' => $defaultcategory->id,
            'perpage' => $this->additionalparams['perpage'] ?? 0,
        ];
    }
}
