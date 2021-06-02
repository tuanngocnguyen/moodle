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
 * Adhoc task to optimise component files deletion
 *
 * @package core
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\task;
defined('MOODLE_INTERNAL') || die();

/**
 * Adhoc task to optimise component files deletion
 *
 * @package core
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_component_file_task extends adhoc_task {
    /** @var int MAX_WORKERS maximum number of workers */
    const MAX_WORKERS = 10;

    /**
     * {@inheritdoc}
     */
    public function execute() {
        global $DB, $CFG;
        $component = isset($this->get_custom_data()->component) ? $this->get_custom_data()->component : '';
        $minid = isset($this->get_custom_data()->minid) ? $this->get_custom_data()->minid : '';
        $maxid = isset($this->get_custom_data()->maxid) ? $this->get_custom_data()->maxid : '';

        // Return if there is no component info.
        if (empty($component)) {
            return;
        }

        // There is only component data. We split files into smaller number for faster deletion.
        if (empty($minid) && empty($maxid)) {
            mtrace("Create adhoc tasks for deleting files belong to $component");
            $filerecords = $DB->get_records('files', ['component' => $component], 'id');

            if (!isset($CFG->componentfiledeletionworker) || clean_param($CFG->componentfiledeletionworker, PARAM_INT) == 0) {
                $maxworker = self::MAX_WORKERS;
            } else {
                $maxworker = $CFG->componentfiledeletionworker;
            }

            $length = ceil(count($filerecords) / $maxworker);
            $workers = array_chunk($filerecords, $length, true);

            $filedeletion = new \core\task\delete_component_file_task();
            // Divide into smaller adhoc tasks with min and max ids.
            foreach ($workers as $worker) {
                $minid = reset($worker)->id;
                $maxid = end($worker)->id;;
                $filedeletion->set_custom_data(['component' => $component, 'minid' => $minid, 'maxid' => $maxid]);
                \core\task\manager::queue_adhoc_task($filedeletion, true);
            }
            mtrace("Finished creating adhoc tasks for deleting files belong to $component");
        } else if (!empty($minid) && !empty($maxid)) {
            // Delete component files of ids within the range $minid and $maxid.
            mtrace("Deleting files belong to $component with ids from $minid to $maxid");
            $where = 'component = :component AND id >= :minid AND id <= :maxid';
            $params = ['component' => $component, 'minid' => $minid, 'maxid' => $maxid];
            $filerecords = $DB->get_recordset_select('files', $where, $params);
            $fs = get_file_storage();
            foreach ($filerecords as $filerecord) {
                $fs->get_file_instance($filerecord)->delete();
            }
            $filerecords->close();
            mtrace("Finish deleting files belong to $component with ids from $minid to $maxid");
        } else {
            mtrace("Nothing to do with $component");
        }
    }
}
