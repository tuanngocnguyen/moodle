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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../tests/behat/behat_question_base.php');

/**
 * Filter helper
 *
 * @package    qbank_viewquestiontext
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_qbank_viewquestiontext extends behat_question_base {

    /**
     * Apply filter
     *
     * @When I choose :not to show question text
     * @When I choose to show question text
     * @param string $not
     */
    public function i_choose_to_show_question_text(bool $not = false) {
        // Type field.
        $this->execute('behat_forms::i_set_the_field_in_container_to', [
            "type",
            "Filter 1",
            "fieldset",
            get_string('showquestiontext', 'question')
        ]);

        // Show text field.
        $this->execute('behat_forms::i_set_the_field_in_container_to', [
            "showtext",
            "Filter 1",
            "fieldset",
            $not ? "No" : "Yes"
        ]);

        $this->execute("behat_general::i_click_on", array(get_string('applyfilters'), 'button'));
    }
}