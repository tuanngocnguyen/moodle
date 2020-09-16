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
 * Bulk grouping creation registration script from a comma separated file
 *
 * @copyright  2020 Catalyst IT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core_group
 */

require_once('../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once('import_form.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

$PAGE->set_url('/group/importgrouping.php', array('id' => $id));

require_login($course);
$context = context_course::instance($id);
require_capability('moodle/course:managegroups', $context);

$strimportgroupings = get_string('importgroupings', 'core_group');

$PAGE->navbar->add($strimportgroupings);
navigation_node::override_active_url(new moodle_url('/group/index.php', array('id' => $course->id)));
$PAGE->set_title("$course->shortname: $strimportgroupings");
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('standard');

$returnurl = new moodle_url('/group/groupings.php', array('id' => $id));

$mform = new groupings_import_form(null, array('id' => $id));
// If a file has been uploaded, then process it.
if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($records = $mform->get_data()) {
    echo $OUTPUT->header();
    foreach ($records as $record) {
        // New grouping.
        $newgrouping = new stdClass();
        $newgrouping->courseid = $id;
        foreach ($record as $name => $value) {
            if (isset($required[$name]) and !$value) {
                print_error('missingfield', 'error', $PAGE->url, $name);
            } else if ($name == "grouping") {
                $newgrouping->name = $value;
            } else {
                $newgrouping->{$name} = $value;
            }
        }

        // Create new grouping.
        if ($groupingid = groups_get_grouping_by_name($newgrouping->courseid, $newgrouping->name)) {
            echo $OUTPUT->notification(get_string('groupingalreadyexists',
                'group', $newgrouping->name), 'notifysuccess');
            $addgroup = true;
        } else if ($groupingid = groups_create_grouping($newgrouping)) {
            echo $OUTPUT->notification(get_string('groupingaddedsuccesfully', 'group', $newgrouping->name), 'notifysuccess');
            $addgroup = true;
        } else {
            echo $OUTPUT->notification(get_string('groupingnotaddederror', 'error', $newgrouping->name));
            $addgroup = false;
        }
        if (!isset($newgrouping->groupname) || empty($newgrouping->groupname)) {
            $addgroup = false;
        }

        // If there is group data, add it to the grouping.
        if ($addgroup && isset($newgrouping->groupname)) {
            // Check if our group already exists, if not, create it.
            $groupid = groups_get_group_by_name($newgrouping->courseid, $newgrouping->groupname);
            if (empty($groupid)) {
                // Create group.
                $newgroup = new stdClass();
                $newgroup->courseid = $newgrouping->courseid;
                $newgroup->name = $newgrouping->groupname;
                $groupid = groups_create_group($newgroup);
                // Assign group to grouping.
                if (!groups_assign_grouping($groupingid, $groupid)) {
                    echo $OUTPUT->notification(get_string('groupingmembershipfailed',
                        'group', array('group' => $newgrouping->groupname, 'grouping' => $newgrouping->name) ));
                } else {
                    echo $OUTPUT->notification(get_string('groupingmembershipadded',
                        'group', array('user' => $newgroup->name, 'group' => $newgrouping->name)), 'notifysuccess' );
                }
            } else if (groups_assign_grouping($groupingid, $groupid)) {
                echo $OUTPUT->notification(get_string('groupingmembershipadded',
                    'group', array('user' => $newgrouping->groupname, 'group' => $newgrouping->name)), 'notifysuccess' );
            } else {
                echo $OUTPUT->notification(get_string('groupingmembershipexists',
                    'group', array('group' => $newgrouping->groupname, 'grouping' => $newgrouping->name) ));
            }
        }
        unset($newgrouping);
    }

    echo $OUTPUT->single_button($returnurl, get_string('continue'), 'get');
    echo $OUTPUT->footer();
    die;
}

echo $OUTPUT->header();
echo $OUTPUT->heading_with_help($strimportgroupings, 'importgroupings', 'core_group');
$mform ->display();
echo $OUTPUT->footer();
