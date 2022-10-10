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

namespace mod_quiz\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

use external_function_parameters;
use external_single_structure;
use external_value;
use external_api;
use mod_quiz\question\bank\filter\custom_category_condition;

/**
 * Add random questions to a quiz.
 *
 * @package    mod_quiz
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_random_question extends external_api {

    /**
     * Parameters for the web service function
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters (
            [
                'cmid' => new external_value(PARAM_INT, 'The cmid of the quiz'),
                'categoryid' => new external_value(PARAM_INT, 'Question category'),
                'addonpage' => new external_value(PARAM_INT, 'The page where random questions will be added to'),
                'randomcount' => new external_value(PARAM_INT, 'Number of random questions'),
                'filtercondition' => new external_value(PARAM_TEXT, 'Filter condition'),
                'newcategory' => new external_value(PARAM_TEXT, 'New category', VALUE_DEFAULT, ''),
                'parentcategory' => new external_value(PARAM_TEXT, 'Parent category of new category', VALUE_DEFAULT, 0),
            ]
        );
    }

    /**
     *  Add random questions.
     *
     * @param int $cmid The cmid of the quiz
     * @param int $categoryid Question category
     * @param int $addonpage The page where random questions will be added to
     * @param int $randomcount Number of random questions
     * @param string $filtercondition Filter condition
     * @param string $newcategory add new category
     * @param string $parentcategory parent category of new category
     * @return array result
     */
    public static function execute(int $cmid, int $categoryid, int $addonpage, int $randomcount,
                                   string $filtercondition, string $newcategory = '', string $parentcategory = ''): array {
        [
            'cmid' => $cmid,
            'categoryid' => $categoryid,
            'addonpage' => $addonpage,
            'randomcount' => $randomcount,
            'filtercondition' => $filtercondition,
            'newcategory' => $newcategory,
            'parentcategory' => $parentcategory,
        ] = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'categoryid' => $categoryid,
            'addonpage' => $addonpage,
            'randomcount' => $randomcount,
            'filtercondition' => $filtercondition,
            'newcategory' => $newcategory,
            'parentcategory' => $parentcategory,
        ]);

        // Validate context.
        $thiscontext = \context_module::instance($cmid);
        self::validate_context($thiscontext);
        require_capability('mod/quiz:manage', $thiscontext);

        // Create new category.
        if (!empty($newcategory)) {
            $contexts = new \core_question\local\bank\question_edit_contexts($thiscontext);
            $defaultcategoryobj = question_make_default_categories($contexts->all());
            $defaultcategory = $defaultcategoryobj->id . ',' . $defaultcategoryobj->contextid;
            $qcobject = new \qbank_managecategories\question_category_object(
                null,
                null,
                $contexts->having_one_edit_tab_cap('categories'),
                $defaultcategoryobj->id,
                $defaultcategory,
                null,
                $contexts->having_cap('moodle/question:add'));
            $categoryid = $qcobject->add_category($parentcategory, $newcategory, '', true);
            $filters = [
                'category' => [
                    'jointype' => custom_category_condition::JOINTYPE_DEFAULT,
                    'values' => [$categoryid]
                ]
            ];
            $filtercondition = json_decode($filtercondition);
            $filtercondition->filters = $filters;
            $filtercondition = json_encode($filtercondition);
        }

        // Add random question to the quiz.
        list($quiz, ) = get_module_from_cmid($cmid);
        quiz_add_random_questions($quiz, $addonpage, $categoryid, $randomcount, $filtercondition);
        quiz_delete_previews($quiz);
        quiz_update_sumgrades($quiz);

        return ['message' => get_string('addarandomquestion_success', 'mod_quiz')];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_value
     */
    public static function execute_returns() {
        return new external_single_structure([
            'message' => new external_value(PARAM_TEXT, 'Message', VALUE_OPTIONAL)
        ]);
    }
}
