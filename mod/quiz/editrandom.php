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
 * Page for editing random questions.
 *
 * @package    mod_quiz
 * @copyright  2018 Shamim Rezaie <shamim@moodle.com>
 * @author     2021 Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');

$slotid = required_param('slotid', PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

// Get the quiz slot.
$slot = $DB->get_record('quiz_slots', ['id' => $slotid]);
if (!$slot) {
    new moodle_exception('invalidrandomslot', 'mod_quiz');
}

if (!$quiz = $DB->get_record('quiz', ['id' => $slot->quizid])) {
    new moodle_exception('invalidquizid', 'quiz');
}

$cm = get_coursemodule_from_instance('quiz', $slot->quizid, $quiz->course);

require_login($cm->course, false, $cm);

if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    $returnurl = new moodle_url('/mod/quiz/edit.php', ['cmid' => $cm->id]);
}

$url = new moodle_url('/mod/quiz/editrandom.php', ['slotid' => $slotid]);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->add_body_class('limitedwidth');

$setreference = $DB->get_record('question_set_references',
    ['itemid' => $slot->id, 'component' => 'mod_quiz', 'questionarea' => 'slot']);
$filterconditions = json_decode($setreference->filtercondition);

$params = \core_question\local\bank\helper::convert_object_array($filterconditions);
$params['cmid'] = $cm->id;
$viewclass = 'mod_quiz\\question\\bank\\random_question_view';
$extraparams['view'] = $viewclass;

// Build required parameters.
list($contexts, $thispageurl, $course, $cm, $pagevars, $extraparams) =
    build_required_parameters_for_custom_view($params, $extraparams);

// Custom View.
$questionbank = new $viewclass($contexts, $thispageurl, $course, $cm, $params, $extraparams);

// Output.
$renderer = $PAGE->get_renderer('mod_quiz', 'edit');
$data = new \stdClass();
$data->questionbank = $renderer->question_bank_contents($questionbank, $params);
$data->cmid = $cm->id;
$data->id = $setreference->id;
$data->returnurl = $returnurl;
$updateform = $OUTPUT->render_from_template('mod_quiz/update_filter_condition_form', $data);
$PAGE->requires->js_call_amd('mod_quiz/update_random_question_filter_condition', 'init');

// Display a heading, question editing form.
echo $OUTPUT->header();
$heading = get_string('randomediting', 'mod_quiz');
echo $OUTPUT->heading_with_help($heading, 'randomquestion', 'mod_quiz');
echo $updateform;
echo $OUTPUT->footer();
