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
 * Bulk group creation registration script from a comma separated file
 *
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package core_group
 */

require_once('../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
include_once('import_form.php');

$id = required_param('id', PARAM_INT);    // Course id

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

$PAGE->set_url('/group/import.php', array('id'=>$id));

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/course:managegroups', $context);

$strimportgroups = get_string('importgroups', 'core_group');

$PAGE->navbar->add($strimportgroups);
navigation_node::override_active_url(new moodle_url('/group/index.php', array('id' => $course->id)));
$PAGE->set_title("$course->shortname: $strimportgroups");
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('admin');

$returnurl = new moodle_url('/group/index.php', array('id'=>$id));

$importform = new groups_import_form(null, ['id' => $id]);

// If a file has been uploaded, then process it
if ($importform->is_cancelled()) {
    redirect($returnurl);

} else if ($records = $importform->get_data()) {
    echo $OUTPUT->header();

    foreach ($records as $record) {
        // New group.
        $newgroup = new stdClass();
        foreach ($record as $name => $value) {
            // Check for required values.
            if (isset($required[$name]) and !$value) {
                print_error('missingfield', 'error', $PAGE->url, $name);
            } else if ($name == "groupname") {
                $newgroup->name = $value;
            } else {
                // Normal entry.
                $newgroup->{$name} = $value;
            }
        }

        // Set course id.
        if (isset($newgroup->idnumber) && strlen($newgroup->idnumber)) {
            // If idnumber is set, we use that.
            // Unset invalid courseid.
            if (!$mycourse = $DB->get_record('course', array('idnumber' => $newgroup->idnumber))) {
                echo $OUTPUT->notification(get_string('unknowncourseidnumber', 'error', $newgroup->idnumber));
                // Unset so doesn't get written to database.
                unset($newgroup->courseid);
            } else {
                $newgroup->courseid = $mycourse->id;
            }
        } else if (isset($newgroup->coursename) && strlen($newgroup->coursename)) {
            // Else use course short name to look up.
            // Unset invalid coursename (if no id).
            if (!$mycourse = $DB->get_record('course', array('shortname' => $newgroup->coursename))) {
                echo $OUTPUT->notification(get_string('unknowncourse', 'error', $newgroup->coursename));
                // Unset so 0 doesn't get written to database.
                unset($newgroup->courseid);
            } else {
                $newgroup->courseid = $mycourse->id;
            }
        } else {
            // Else use current id.
            $newgroup->courseid = $id;
        }
        unset($newgroup->idnumber);
        unset($newgroup->coursename);

        // If courseid is set.
        if (isset($newgroup->courseid)) {
            $groupname = $newgroup->name;
            $newgrpcoursecontext = context_course::instance($newgroup->courseid);

            // Users cannot upload groups in courses they cannot update.
            if (!has_capability('moodle/course:managegroups', $newgrpcoursecontext) ||
                (!is_enrolled($newgrpcoursecontext) && !has_capability('moodle/course:view', $newgrpcoursecontext))) {
                echo $OUTPUT->notification(get_string('nopermissionforcreation', 'group', $groupname));
            } else {
                if (isset($newgroup->groupidnumber)) {
                    /* The CSV field for the group idnumber is groupidnumber rather than
                     * idnumber as that field is already in use for the course idnumber.
                     */
                    $newgroup->groupidnumber = trim($newgroup->groupidnumber);
                    if (has_capability('moodle/course:changeidnumber', $newgrpcoursecontext)) {
                        $newgroup->idnumber = $newgroup->groupidnumber;
                        if ($existing = groups_get_group_by_idnumber($newgroup->courseid, $newgroup->idnumber)) {
                            // The idnumbers must be unique to a course but we shouldn't ignore group creation for duplicates.
                            $existing->name = s($existing->name);
                            $existing->idnumber = s($existing->idnumber);
                            $existing->problemgroup = $groupname;
                            echo $OUTPUT->notification(get_string('groupexistforcoursewithidnumber', 'error', $existing));
                            unset($newgroup->idnumber);
                        }
                    }
                    // Always drop the groupidnumber key. It's not a valid database field.
                    unset($newgroup->groupidnumber);
                }
                if ($groupid = groups_get_group_by_name($newgroup->courseid, $groupname)) {
                    echo $OUTPUT->notification("$groupname :".get_string('groupexistforcourse', 'error', $groupname));
                } else if ($groupid = groups_create_group($newgroup)) {
                    echo $OUTPUT->notification(get_string('groupaddedsuccesfully', 'group', $groupname), 'notifysuccess');
                } else {
                    echo $OUTPUT->notification(get_string('groupnotaddederror', 'error', $groupname));
                    continue;
                }

                // Add group to grouping.
                if (isset($newgroup->groupingname) && strlen($newgroup->groupingname)) {
                    $groupingname = $newgroup->groupingname;
                    if (!$groupingid = groups_get_grouping_by_name($newgroup->courseid, $groupingname)) {
                        $data = new stdClass();
                        $data->courseid = $newgroup->courseid;
                        $data->name = $groupingname;
                        if ($groupingid = groups_create_grouping($data)) {
                            echo $OUTPUT->notification(get_string('groupingaddedsuccesfully', 'group', $groupingname),
                                'notifysuccess');
                        } else {
                            echo $OUTPUT->notification(get_string('groupingnotaddederror', 'error', $groupingname));
                            continue;
                        }
                    }

                    // If we have reached here we definitely have a groupingid.
                    $a = array('groupname' => $groupname, 'groupingname' => $groupingname);
                    try {
                        groups_assign_grouping($groupingid, $groupid);
                        echo $OUTPUT->notification(get_string('groupaddedtogroupingsuccesfully', 'group', $a), 'notifysuccess');
                    } catch (Exception $e) {
                        echo $OUTPUT->notification(get_string('groupnotaddedtogroupingerror', 'error', $a));
                    }
                }
            }
        }
        unset ($newgroup);
    }

    echo $OUTPUT->single_button($returnurl, get_string('continue'), 'get');
    echo $OUTPUT->footer();
    die;
}

// Print the form.
echo $OUTPUT->header();
echo $OUTPUT->heading_with_help($strimportgroups, 'importgroups', 'core_group');
$importform->display();
echo $OUTPUT->footer();
