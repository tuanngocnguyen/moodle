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

namespace repository_contentbank;

defined('MOODLE_INTERNAL') || die();

use backup;
use backup_controller;
use backup_file_manager;
use context_course;
use context_module;
use file_storage;
use restore_controller;
use restore_dbops;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Tests that a FILE_REFERENCE alias to a cross-course content bank file is
 * correctly included in the backup and correctly restored.
 *
 * @package    repository_contentbank
 * @copyright  2026 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \backup_file_manager::copy_file_moodle2backup
 * @covers \restore_process_file_aliases_queue
 */
final class backup_alias_test extends \advanced_testcase {
    /**
     * Create an H5P file in Course A's content bank, add a FILE_REFERENCE alias
     * to it inside Course 2's mod_resource, then backup Course 2 with
     * MODE_GENERAL (which physically copies file bytes).
     *
     * Asserts that the backup's files/ directory contains the actual H5P bytes
     */
    public function test_backup_includes_physical_bytes_for_cross_course_contentbank_alias(): void {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->backup_file_logger_level = backup::LOG_NONE;
        // Prevent deleting the temp dir so we can
        // inspect the files/ directory after execute_plan() returns.
        $CFG->keeptempdirectoriesonbackup = true;

        [$course2, $contenthash] = $this->setup_cross_course_alias_scenario();

        // Backup Course 2 with MODE_GENERAL so files are included.
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course2->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id
        );
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // The physical file should be present in the backup's files/ directory.
        $backupbasedir = make_backup_temp_directory($backupid, false);
        $expectedpath = $backupbasedir . '/files/' .
            backup_file_manager::get_backup_content_file_location($contenthash);

        $this->assertFileExists(
            $expectedpath,
            "Physical H5P bytes (contenthash={$contenthash}) must be present in the backup files/ directory. " .
            "Before the fix, is_external_file() caused the copy to be skipped for contentbank aliases."
        );
    }

    /**
     * Create the same cross-course alias scenario, backup Course 2, then
     * restore to a new course. The alias's H5P content must survive the round-trip
     * even though the original source (Course 1's content bank) is not included
     * in the backup.
     *
     * Before the fix, restore logged "referenced file not included" and the
     * activity had no usable H5P file.
     */
    public function test_restore_creates_file_from_cross_course_contentbank_alias(): void {
        global $CFG, $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->backup_file_logger_level = backup::LOG_NONE;

        [$course2, $contenthash, $resourcecmid] = $this->setup_cross_course_alias_scenario();

        // Backup.
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course2->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id
        );
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $bc->destroy();

        // The backup file is now stored in Moodle's file pool. Extract it back to the restore temp dir
        // so restore_controller can find it — this mirrors what the UI restore flow does.
        $fs = get_file_storage();
        $coursecontext = context_course::instance($course2->id);
        $backupfiles = $fs->get_area_files($coursecontext->id, 'backup', 'course', false, 'id ASC', false);
        $this->assertNotEmpty($backupfiles, 'Backup file must be stored in the Moodle file pool.');
        $backupfile = reset($backupfiles);

        // Extract the .mbz to the temp dir so restore_controller can use it.
        $restorepath = $CFG->tempdir . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . $backupid;
        $fp = get_file_packer('application/vnd.moodle.backup');
        $fp->extract_to_pathname($backupfile, $restorepath);

        // Restore to a new course.
        $newcourseid = restore_dbops::create_new_course(
            $course2->fullname,
            $course2->shortname . '_restored',
            $course2->category
        );
        $rc = new restore_controller(
            $backupid,
            $newcourseid,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Find the restored resource module in the new course.
        $restoredresource = $DB->get_record('resource', ['course' => $newcourseid], '*', MUST_EXIST);
        $newcmcontext = context_module::instance(
            get_coursemodule_from_instance('resource', $restoredresource->id, $newcourseid)->id
        );

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $newcmcontext->id,
            'mod_resource',
            'content',
            false,
            'filename',
            false
        );

        $this->assertNotEmpty(
            $files,
            'The restored resource must contain at least one file. ' .
            'Before the fix, the contentbank files were not in the backup, so restore left the area empty.'
        );

        $restoredfile = reset($files);
        $this->assertEquals(
            $contenthash,
            $restoredfile->get_contenthash(),
            'The restored file must have the same contenthash as the original H5P, ' .
            'confirming the files were preserved through backup and restore.'
        );

        // The file must be readable — confirming bytes are actually in the file pool.
        $this->assertTrue(
            $restoredfile->get_filesize() > 0,
            'Restored file must have a non-zero size (actual H5P bytes).'
        );
    }

    /**
     * Build the cross-course alias scenario:
     *
     *   Course 1 - content bank - H5P file (the "source")
     *   Course 2 - mod_resource - FILE_REFERENCE alias - Course 1's H5P
     *
     * This mirrors the user-facing workflow:
     *   1. Upload H5P to Course 1's content bank.
     *   2. In Course 2, add a resource and pick the file via
     *      File picker - Content bank (creates a FILE_REFERENCE alias).
     *
     * @return array  [$courseB, $contenthash, $resourcecmid]
     */
    private function setup_cross_course_alias_scenario(): array {
        global $CFG, $DB, $USER;

        $generator = $this->getDataGenerator();
        $cbgenerator = $generator->get_plugin_generator('core_contentbank');

        // Course 1: upload H5P to the content bank.
        $course1 = $generator->create_course();
        $context1 = context_course::instance($course1->id);
        $h5pfile = $CFG->dirroot . '/h5p/tests/fixtures/filltheblanks.h5p';

        $contents = $cbgenerator->generate_contentbank_data(
            'contenttype_h5p',
            1,
            $USER->id,
            $context1,
            false,
            $h5pfile
        );
        // Retrieve the stored_file for the content bank item so we can read its contenthash.
        $cbcontent = reset($contents);
        $fs = get_file_storage();
        $cbfiles = $fs->get_area_files(
            $context1->id,
            'contentbank',
            'public',
            $cbcontent->id,
            'filename',
            false
        );
        $this->assertNotEmpty($cbfiles, 'Content bank must contain the uploaded H5P file.');
        $originalfile = reset($cbfiles);
        $contenthash = $originalfile->get_contenthash();

        // Get the system-level content bank repository instance.
        // The repository_contentbank is registered at SYSCONTEXTID and has a single instance.
        $sql = "SELECT ri.id
                  FROM {repository} r
                  JOIN {repository_instances} ri ON ri.typeid = r.id
                 WHERE r.type = :type AND ri.contextid = :ctx";
        $repoid = $DB->get_field_sql($sql, ['type' => 'contentbank', 'ctx' => SYSCONTEXTID]);
        $this->assertNotEmpty($repoid, 'The contentbank repository must be installed and have a system instance.');

        // Course 2: create a resource activity.
        $course2 = $generator->create_course();
        $resourcecm = $generator->create_module('resource', ['course' => $course2->id]);
        $cmcontext = context_module::instance($resourcecm->cmid);

        // Build a FILE_REFERENCE alias in Course 2's resource that points to Course 1's H5P.
        // This is what happens when a teacher uses File picker - Content bank in the editor.
        $filerecord = $DB->get_record('files', ['id' => $originalfile->get_id()]);
        $reference = file_storage::pack_reference($filerecord);

        $aliasrecord = [
            'contextid' => $cmcontext->id,
            'component' => 'mod_resource',
            'filearea'  => 'content',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => $originalfile->get_filename(),
        ];
        $fs->create_file_from_reference($aliasrecord, $repoid, $reference);

        return [$course2, $contenthash, $resourcecm->cmid];
    }
}
