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

namespace repository_user;

defined('MOODLE_INTERNAL') || die();

use backup;
use backup_controller;
use backup_file_manager;
use context_module;
use file_storage;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

/**
 * Tests that a FILE_REFERENCE alias to a user (private) file is NOT
 * physically included in the backup.
 *
 * @package    repository_user
 * @copyright  2026 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \backup_file_manager::copy_file_moodle2backup
 */
final class backup_alias_test extends \advanced_testcase {
    /**
     * Create a file in user's private files, add a FILE_REFERENCE alias to it
     * in a course module, then backup the course with MODE_GENERAL.
     *
     * Asserts that the backup's files/ directory does NOT contain the physical bytes
     * for user files aliases.
     */
    public function test_backup_does_not_include_physical_bytes_for_user_alias(): void {
        global $CFG, $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->backup_file_logger_level = backup::LOG_NONE;
        // Prevent deleting the temp dir so we can
        // inspect the files/ directory after execute_plan() returns.
        $CFG->keeptempdirectoriesonbackup = true;

        // Create a file in user's private files.
        $usercontext = \context_user::instance($USER->id);
        $fs = get_file_storage();
        $userfile = $fs->create_file_from_string([
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea'  => 'private',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => 'userfile.txt',
        ], 'Private user file content');
        $contenthash = $userfile->get_contenthash();

        // Get the system-level user repository instance.
        $sql = "SELECT ri.id
                  FROM {repository} r
                  JOIN {repository_instances} ri ON ri.typeid = r.id
                 WHERE r.type = :type AND ri.contextid = :ctx";
        $repoid = $DB->get_field_sql($sql, ['type' => 'user', 'ctx' => SYSCONTEXTID]);
        $this->assertNotEmpty($repoid, 'The user repository must be installed and have a system instance.');

        // Create a course and resource activity.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $resourcecm = $generator->create_module('resource', ['course' => $course->id]);
        $cmcontext = context_module::instance($resourcecm->cmid);

        // Build a FILE_REFERENCE alias in course resource pointing to the user private file.
        $filerecord = $DB->get_record('files', ['id' => $userfile->get_id()]);
        $reference = file_storage::pack_reference($filerecord);

        $aliasrecord = [
            'contextid' => $cmcontext->id,
            'component' => 'mod_resource',
            'filearea'  => 'content',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => $userfile->get_filename(),
        ];
        $fs->create_file_from_reference($aliasrecord, $repoid, $reference);

        // Backup course with MODE_GENERAL.
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id
        );
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // The physical file should NOT be present in the backup's files/ directory.
        $backupbasedir = make_backup_temp_directory($backupid, false);
        $expectedpath = $backupbasedir . '/files/' .
            backup_file_manager::get_backup_content_file_location($contenthash);

        $this->assertFileDoesNotExist(
            $expectedpath,
            "Physical bytes (contenthash={$contenthash}) for user repository alias
             must NOT be present in the backup files/ directory."
        );
    }
}
