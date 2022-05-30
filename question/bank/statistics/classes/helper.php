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

use core_question\statistics\questions\all_calculated_for_qubaid_condition;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper for statistics
 *
 * @package    qbank_statistics
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * @var float Threshold to determine 'Needs checking?'
     */
    private const NEED_FOR_REVISION_LOWER_THRESHOLD = 30;

    /**
     * @var float Threshold to determine 'Needs checking?'
     */
    private const NEED_FOR_REVISION_UPPER_THRESHOLD = 50;

    /**
     * Return question usages
     *
     * @param int $questionid id of the question
     * @return array list of question usages
     * @throws \dml_exception
     */
    public static function get_question_usages(int $questionid): array {
        global $DB;

        $usages = $DB->get_records_sql("
             SELECT DISTINCT qu.contextid, qu.component
                        FROM {question_usages} qu
                        JOIN {question_attempts} qatt ON qatt.questionusageid = qu.id
                       WHERE qatt.questionid = :questionid",
            ['questionid' => $questionid]
        );
        return $usages;
    }

    /**
     * Load question stats from a quiz
     *
     * @param \stdClass $usage question usage
     * @return all_calculated_for_qubaid_condition|null question stats
     */
    private static function load_question_stats(\stdClass $usage): ?all_calculated_for_qubaid_condition {
        global $CFG;
        if (strpos($usage->component, 'mod_') !== false) {
            $modulename = substr($usage->component, 4);
            $modulepath = "{$CFG->dirroot}/mod/{$modulename}";
            $modfile = "{$modulepath}/lib.php";
            $extracaps = array();
            if (file_exists($modfile)) {
                include_once($modfile);
                $modfunction = $modulename . '_statistics_calculate_question_stats';
                if (function_exists($modfunction)) {
                    return $modfunction($usage);
                }
            }
        }
        // Otherwise (no question stats data) return null.
        return null;
    }

    /**
     * Load a specified stats item for a question
     *
     * @param \stdClass $usage question usage
     * @param int $questionid question id
     * @param string $item a stats item
     * @return float|int
     */
    public static function load_question_stats_item(\stdClass $usage, int $questionid, string $item): ?float {
        $questionstats = self::load_question_stats($usage);
        if (is_null($questionstats)) {
            return null;
        }
        // Find in main question.
        foreach ($questionstats->questionstats as $stats) {
            if ($stats->questionid == $questionid && isset($stats->$item)) {
                return $stats->$item;
            }
        }
        // If not found, find in sub questions.
        foreach ($questionstats->subquestionstats as $stats) {
            if ($stats->questionid == $questionid && isset($stats->$item)) {
                return $stats->$item;
            }
        }
        return null;
    }

    /**
     * Calculate average for a stats item on a question.
     *
     * @param int $questionid id of the question
     * @param string $item stats item
     * @return float|null
     */
    private static function calculate_average_question_stats_item(int $questionid, string $item): ?float {
        $usages = self::get_question_usages($questionid);

        $sum = 0;
        $count = count($usages);
        foreach ($usages as $usage) {
            $value = self::load_question_stats_item($usage, $questionid, $item);
            if (!is_null($value)) {
                $sum += $value;
            } else {
                // Exclude this value when it is null.
                $count--;
            }
        }

        // Return null if there is no quizzes.
        if (empty($count)) {
            return null;
        }

        // Average value per quiz.
        $average = $sum / $count;
        return $average;
    }

    /**
     * Calculate average facility index
     *
     * @param int $questionid
     * @return float|null
     */
    public static function calculate_average_question_facility(int $questionid): ?float {
        return self::calculate_average_question_stats_item($questionid, 'facility');
    }

    /**
     * Calculate average discriminative efficiency
     *
     * @param int $questionid question id
     * @return float|null
     */
    public static function calculate_average_question_discriminative_efficiency(int $questionid): ?float {
        return self::calculate_average_question_stats_item($questionid, 'discriminativeefficiency');
    }

    /**
     * Calculate average discriminative efficiency
     *
     * @param int $questionid question id
     * @return float|null
     */
    public static function calculate_average_question_discrimination_index(int $questionid): ?float {
        return self::calculate_average_question_stats_item($questionid, 'discriminationindex');
    }

    /**
     * Format a number to a localised percentage with specified decimal points.
     *
     * @param float|null $number The number being formatted
     * @param bool $fraction An indicator for whether the number is a fraction or is already multiplied by 100
     * @param int $decimals Sets the number of decimal points
     * @return string
     * @throws \coding_exception
     */
    public static function format_percentage(?float $number, bool $fraction = true, int $decimals = 2): string {
        if (is_null($number)) {
            return get_string('na', 'qbank_statistics');
        }
        $coefficient = $fraction ? 100 : 1;
        return get_string('percents', 'moodle', format_float($number * $coefficient, $decimals));
    }

    /**
     * Format discrimination index (Needs checking?).
     *
     * @param float|null $value stats value
     * @return array
     */
    public static function format_discrimination_index(?float $value): array {
        if (is_null($value)) {
            $content = get_string('emptyvalue', 'qbank_statistics');
            $classes = '';
        } else if ($value < self::NEED_FOR_REVISION_LOWER_THRESHOLD) {
            $content = get_string('verylikely', 'qbank_statistics');
            $classes = 'alert-danger';
        } else if ($value < self::NEED_FOR_REVISION_UPPER_THRESHOLD) {
            $content = get_string('likely', 'qbank_statistics');
            $classes = 'alert-warning';
        } else {
            $content = get_string('unlikely', 'qbank_statistics');
            $classes = 'alert-success';
        }
        return [$content, $classes];
    }
}
