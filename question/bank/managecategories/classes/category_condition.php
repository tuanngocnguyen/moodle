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

namespace qbank_managecategories;

use core_question\local\bank\condition;

/**
 *  This class controls from which category questions are listed.
 *
 * @copyright 2013 Ray Morris
 * @author    2021 Safat Shahin <safatshahin@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category_condition extends condition {
    /** @var \stdClass The course record. */
    protected $course;

    /** @var \stdClass The category record. */
    protected $category;

    /** @var array of contexts. */
    protected $contexts;

    /** @var string SQL fragment to add to the where clause. */
    protected $where;

    /** @var array query param used in where. */
    protected $params;

    /** @var string categoryID,contextID as used with question_bank_view->display(). */
    protected $cat;

    /** @var int The maximum displayed length of the category info. */
    public $maxinfolength;

    /**
     * Constructor to initialize the category filter condition.
     */
    public function __construct($qbank) {
        $this->cat = $qbank->get_pagevars('cat');
        $this->contexts = $qbank->contexts->having_one_edit_tab_cap($qbank->get_pagevars('tabname'));
        $this->course = $qbank->course;
        $filters = $qbank->get_pagevars('filters');

        if (!$this->category = self::get_current_category($this->cat)) {
            return;
        }

        // Build where and params.
        list($this->where, $this->params) = self::build_query_from_filters($filters);
    }

    /**
     * Return default category
     *
     * @return \stdClass default category
     */
    public function get_default_category(): \stdClass {
        if (empty($this->category)) {
            return question_get_default_category(\context_course::instance($this->course->id)->id);
        }

        return $this->category;
    }

    public function get_condition_key() {
        return 'category';
    }

    /**
     * Returns course id.
     *
     * @return string Course id.
     */
    public function get_course_id() {
        return $this->course->id;
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
     * Return parameters to be bound to the above WHERE clause fragment.
     * @return array parameter name => value.
     */
    public function params() {
        return $this->params;
    }

    /**
     * Called by question_bank_view to display the GUI for selecting a category
     * @deprecated since Moodle 4.0 MDL-72321 - please do not use this function any more.
     * @todo Final deprecation on Moodle 4.1 MDL-72572
     */
    public function display_options() {
        debugging('Function display_options() is deprecated, please use filtering objects', DEBUG_DEVELOPER);
        global $PAGE;
        $displaydata = [];
        $catmenu = helper::question_category_options($this->contexts, true, 0,
                true, -1, false);
        $displaydata['categoryselect'] = \html_writer::select($catmenu, 'category', $this->cat, [],
                array('class' => 'searchoptions custom-select', 'id' => 'id_selectacategory'));
        $displaydata['categorydesc'] = '';
        if ($this->category) {
            $displaydata['categorydesc'] = $this->print_category_info($this->category);
        }
        return $PAGE->get_renderer('qbank_managecategories')->render_category_condition($displaydata);
    }

    /**
     * Displays the recursion checkbox GUI.
     * question_bank_view places this within the section that is hidden by default
     * @deprecated since Moodle 4.0 MDL-72321 - please do not use this function any more.
     * @todo Final deprecation on Moodle 4.1 MDL-72572
     */
    public function display_options_adv() {
        debugging('Function display_options_adv() is deprecated, please use filtering objects', DEBUG_DEVELOPER);
        global $PAGE;
        $displaydata = [];
        if ($this->recurse) {
            $displaydata['checked'] = 'checked';
        }
        return $PAGE->get_renderer('qbank_managecategories')->render_category_condition_advanced($displaydata);
    }

    /**
     * Display the drop down to select the category.
     *
     * @param array $contexts of contexts that can be accessed from here.
     * @param \moodle_url $pageurl the URL of this page.
     * @param string $current 'categoryID,contextID'.
     * @deprecated since Moodle 4.0
     * @todo Final deprecation on Moodle 4.4 MDL-72438
     */
    protected function display_category_form($contexts, $pageurl, $current) {
        debugging('Function display_category_form() is deprecated,
         please use the core_question renderer instead.', DEBUG_DEVELOPER);
        echo \html_writer::start_div('choosecategory');
        $catmenu = question_category_options($contexts, true, 0, true, -1, false);
        echo \html_writer::label(get_string('selectacategory', 'question'), 'id_selectacategory', true, ["class" => "mr-1"]);
        echo \html_writer::select($catmenu, 'category', $current, [],
                array('class' => 'searchoptions custom-select', 'id' => 'id_selectacategory'));
        echo \html_writer::end_div() . "\n";
    }

    /**
     * Print the text if category id not available.
     */
    public static function print_choose_category_message(): void {
        echo \html_writer::start_tag('p', ['style' => "\"text-align:center;\""]);
        echo \html_writer::tag('b', get_string('selectcategoryabove', 'question'));
        echo \html_writer::end_tag('p');
    }

    /**
     * Look up the category record based on category ID and context
     * @param string $categoryandcontext categoryID,contextID as used with question_bank_view->display()
     * @return \stdClass The category record
     */
    public static function get_current_category($categoryandcontext) {
        global $DB, $OUTPUT;
        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        if (!$categoryid) {
            self::print_choose_category_message();
            return false;
        }

        if (!$category = $DB->get_record('question_categories', ['id' => $categoryid, 'contextid' => $contextid])) {
            echo $OUTPUT->box_start('generalbox questionbank');
            echo $OUTPUT->notification('Category not found!');
            echo $OUTPUT->box_end();
            return false;
        }

        return $category;
    }

    /**
     * Print the category description
     * @param \stdClass $category the category information form the database.
     */
    protected function print_category_info($category): string {
        $formatoptions = new \stdClass();
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        if (isset($this->maxinfolength)) {
            return shorten_text(format_text($category->info, $category->infoformat, $formatoptions, $this->course->id),
                    $this->maxinfolength);
        }

        return format_text($category->info, $category->infoformat, $formatoptions, $this->course->id);
    }

    /**
     * Build query from filter value
     *
     * @param array $filters filter objects
     * @return array where sql and params
     */
    public static function build_query_from_filters(array $filters): array {
        global $DB;

        // Category filter
        if (isset($filters['category'])) {
            $filter = (object) $filters['category'];
        } else {
            return ['', []];
        }

        $recursive = false;
        if (isset($filters['subcategories'])) {
            $subcatfilter = (object) $filters['subcategories'];
            $recursive = (int) $subcatfilter->values[0];
        }

        // Sub categories.
        if ($recursive) {
            $categories = $filter->values;
            $categoriesandsubcategories = [];
            foreach ($categories as $categoryid) {
                $categoriesandsubcategories += question_categorylist($categoryid);
            }
        } else {
            $categoriesandsubcategories = $filter->values;
        }

        $filterverb = $filter->jointype ?? self::JOINTYPE_DEFAULT;
        $equal = !($filterverb === self::JOINTYPE_NONE);
        list($insql, $params) = $DB->get_in_or_equal($categoriesandsubcategories, SQL_PARAMS_NAMED, 'cat', $equal);
        $where = 'qbe.questioncategoryid ' . $insql;
        return [$where, $params];
    }

    public function get_name() {
        return 'category';
    }

    public function get_title() {
        return get_string('category', 'core_question');
    }

    public function get_filter_class() {
        return null;
    }

    public function allow_custom() {
        return false;
    }

    public function allow_multiple() {
        return false;
    }

    public function allow_empty() {
        return false;
    }

    public function get_initial_values() {
        $catmenu = helper::question_category_options($this->contexts, true, 0, true, -1, false);
        $values = [];
        foreach ($catmenu as $menu) {
            foreach ($menu as $catlist) {
                foreach ($catlist as $key => $value) {
                    $values[] = (object) [
                        // Remove contextid from value.
                        'value' => strpos($key, ',') === false ? $key : substr($key, 0, strpos($key, ',')),
                        'title' => html_entity_decode($value),
                        'selected' => ($key === $this->cat),
                    ];
                }
            }
        }
        return $values;
    }
}
