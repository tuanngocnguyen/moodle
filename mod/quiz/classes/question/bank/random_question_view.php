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
 * Defines the custom question bank view used on the Edit quiz page.
 *
 * @package   mod_quiz
 * @category  question
 * @copyright 1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_quiz\question\bank;

/**
 * Subclass to customise the view of the question bank for the quiz editing screen.
 *
 * @author     2022 Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class random_question_view extends custom_view {
    /**
     * Init required columns.
     *
     * @return void
     */
    protected function init_required_columns(): void {
        // override core columns.
        $this->corequestionbankcolumns = [
            'question_type_column',
            'question_name_text_column',
            'preview_action_column'
        ];
    }

    /**
     * Prints the table of questions in a category with interactions
     */
    protected function display_question_list(): void {
        echo \html_writer::start_tag('div',
            ['class' => 'categoryquestionscontainer', 'id' => 'questionscontainer']);
        echo \html_writer::end_tag('div');
    }
}