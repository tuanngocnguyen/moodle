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

        $newgroup->courseid = $id;

        // If idnumber is used to store user id number,
        if (isset($newgroup->idnumber)) {
           $useridnumber = $newgroup->idnumber;
           unset($newgroup->idnumber);
        }

        // If member is used to store user id number,
        if (isset($newgroup->member)) {
            $username = $newgroup->member;
            unset($newgroup->member);
        }

        // If courseid is set.
        if (!empty($newgroup->courseid)) {
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
                        if ($existing = groups_get_group_by_idnumber($newgroup->courseid, $newgroup->groupidnumber)) {
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
                    echo $OUTPUT->notification(get_string('groupalreadyexists', 'group', $groupname), 'notifysuccess');
                    $adduser = true;
                    $addgroupings = true;
                } else if ($groupid = groups_create_group($newgroup)) {
                    echo $OUTPUT->notification(get_string('groupaddedsuccesfully', 'group', $groupname), 'notifysuccess');
                    $adduser = true;
                    $addgroupings = true;
                } else {
                    echo $OUTPUT->notification(get_string('groupnotaddederror', 'error', $groupname));
                    $adduser = false;
                    $addgroupings = false;
                }

                // If there is userdata, add them to the group.
                if ($adduser && (isset($username) || isset($useridnumber))) {
                    if (!empty($username) && !empty($useridnumber)) {
                        $newmember = $DB->get_records('user', array('idnumber' => $useridnumber, 'username' => $username, "deleted" => 0));
                        if (empty($newmember)) {
                            echo $OUTPUT->notification(get_string('usernameidmismatch', 'group', array('name' => $username, 'id' => $useridnumber)));
                        }
                    } else if (!empty($username)) {
                        $newmember = $DB->get_records('user', array('username' => $username, "deleted" => 0));
                        if (empty($newmember)) {
                            echo $OUTPUT->notification(get_string('usernotfoundskip', 'group', $username));
                        }
                    } else if (!empty($useridnumber)) {
                        $newmember = $DB->get_records('user', array('idnumber' => $useridnumber, "deleted" => 0));
                        if (empty($newmember)) {
                            echo $OUTPUT->notification(get_string('usernotfoundskip', 'group', $useridnumber));
                        }
                    } else {
                        $newmember = [];
                    }

                    if (count($newmember) == 1) {
                        $newmember = reset($newmember);
                        $gid = groups_get_group_by_name($newgroup->courseid, $groupname);

                        if (!has_capability('moodle/course:managegroups', $context)) {
                            echo $OUTPUT->notification(get_string('nopermission'));
                        } else if (groups_is_member($gid, $newmember->id)) {
                            echo $OUTPUT->notification(get_string('groupmembershipexists', 'group',
                                array('user' => $newmember->username, 'group' => $groupname)));
                        } else if (!groups_add_member($gid, $newmember->id)) {
                            if (!is_enrolled(context_course::instance($newgroup->courseid), $newmember->id)) {
                                echo $OUTPUT->notification(get_string('notenrolledincourse', 'group', $newmember->username));
                            } else {
                                echo $OUTPUT->notification(get_string('groupmembershipfailed', 'group',
                                    array('user' => $newmember->username, 'group' => $groupname)));
                            }
                        } else {
                            echo $OUTPUT->notification(get_string('groupmembershipadded', 'group',
                                array('user' => $newmember->username, 'group' => $groupname)), 'notifysuccess');
                        }
                    } else if (count($newmember) > 1) {
                        $arraykeys = array_keys($newmember);
                        $notetext = "";
                        foreach ($newmember as $member) {
                            $notetext .= "'" . $member->username . "' ";
                        }
                        echo $OUTPUT->notification(get_string('multipleusersfound', 'group',
                            array('id' => $newmember[$arraykeys[0]]->idnumber, 'names' => $notetext)));
                    }
                }

                // Add group to grouping.
                if ($addgroupings && isset($newgroup->groupingname) && strlen($newgroup->groupingname)) {
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
