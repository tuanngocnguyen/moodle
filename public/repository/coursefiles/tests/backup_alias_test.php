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

namespace repository_coursefiles;

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
 * Tests that a FILE_REFERENCE alias via the legacy coursefiles repository is
 * correctly included in the backup and correctly restored.
 *
 * @package    repository_coursefiles
 * @copyright  2026 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \backup_file_manager::copy_file_moodle2backup
 * @covers \restore_process_file_aliases_queue
 */
final class backup_alias_test extends \advanced_testcase {
    /**
     * Create a file in Course 1's legacy files, add a FILE_REFERENCE alias to it
     * in Course 2's mod_resource using the coursefiles repository, then backup Course 2 with
     * MODE_GENERAL (which physically copies file bytes).
     *
     * Asserts that the backup's files/ directory contains the actual file bytes.
     */
    public function test_backup_includes_physical_bytes_for_cross_course_coursefiles_alias(): void {
        global $CFG, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->backup_file_logger_level = backup::LOG_NONE;
        // Prevent deleting the temp dir so we can inspect the files/ directory after execute_plan() returns.
        $CFG->keeptempdirectoriesonbackup = true;

        [, $course2, $contenthash] = $this->setup_cross_course_alias_scenario();

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
            "Physical file bytes (contenthash={$contenthash}) must be present in the backup files/ directory."
        );
    }

    /**
     * Create the same cross-course alias scenario, backup Course 2, then
     * restore to a new course. The alias content must survive the round-trip.
     */
    public function test_restore_creates_file_from_cross_course_coursefiles_alias(): void {
        global $CFG, $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->backup_file_logger_level = backup::LOG_NONE;

        [$course1, $course2, $contenthash] = $this->setup_cross_course_alias_scenario();

        // Backup Course 2.
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

        // Delete course 1 before restore a backup of course 2.
        delete_course($course1, false);

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

        $restoredfile = $fs->get_file(
            $newcmcontext->id,
            'mod_resource',
            'content',
            0,
            '/',
            'samplelegacyfile.txt'
        );
        $this->assertNotEmpty($restoredfile, 'The restored resource must contain the samplelegacyfile.txt file.');
        $this->assertEquals($contenthash, $restoredfile->get_contenthash());
    }

    /**
     * Create the same cross-course alias scenario, backup Course 2, then
     * restore to a new course on a DIFFERENT site.
     *
     * On cross-site restore (is_samesite is false), the alias cannot point to
     * the original site's reference, so it must be converted into a real,
     * standalone stored file using the physical bytes included in the backup.
     */
    public function test_cross_site_restore_creates_file_from_coursefiles_alias(): void {
        global $CFG, $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->backup_file_logger_level = backup::LOG_NONE;

        [, $course2, $contenthash] = $this->setup_cross_course_alias_scenario();

        // Backup Course 2.
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

        // Extract the .mbz to the temp dir so restore_controller can use it.
        $fs = get_file_storage();
        $coursecontext = context_course::instance($course2->id);
        $backupfiles = $fs->get_area_files($coursecontext->id, 'backup', 'course', false, 'id ASC', false);
        $this->assertNotEmpty($backupfiles, 'Backup file must be stored in the Moodle file pool.');
        $backupfile = reset($backupfiles);

        $restorepath = $CFG->tempdir . DIRECTORY_SEPARATOR . 'backup' . DIRECTORY_SEPARATOR . $backupid;
        $fp = get_file_packer('application/vnd.moodle.backup');
        $fp->extract_to_pathname($backupfile, $restorepath);

        // Modify moodle_backup.xml to simulate a cross-site restore.
        $xmlpath = $restorepath . DIRECTORY_SEPARATOR . 'moodle_backup.xml';
        $this->assertFileExists($xmlpath);
        $xmlcontent = file_get_contents($xmlpath);
        $fakesitehash = md5('https://other-moodle-site.example.com');
        $xmlcontent = preg_replace(
            '/<original_site_identifier_hash>[^<]*<\/original_site_identifier_hash>/',
            '<original_site_identifier_hash>' . $fakesitehash . '</original_site_identifier_hash>',
            $xmlcontent
        );
        file_put_contents($xmlpath, $xmlcontent);

        // Restore to a new course.
        $newcourseid = restore_dbops::create_new_course(
            $course2->fullname,
            $course2->shortname . '_crosssite',
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

        $this->assertFalse($rc->is_samesite(), 'Restore controller must recognize this as a cross-site restore.');
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Find the restored resource module in the new course.
        $restoredresource = $DB->get_record('resource', ['course' => $newcourseid], '*', MUST_EXIST);
        $newcmcontext = context_module::instance(
            get_coursemodule_from_instance('resource', $restoredresource->id, $newcourseid)->id
        );

        $restoredfile = $fs->get_file(
            $newcmcontext->id,
            'mod_resource',
            'content',
            0,
            '/',
            'samplelegacyfile.txt'
        );
        $this->assertNotEmpty(
            $restoredfile,
            'The restored resource must contain the samplelegacyfile.txt file on cross-site restore.'
        );

        $this->assertEquals(
            $contenthash,
            $restoredfile->get_contenthash(),
            'The restored file must have the same contenthash as the original file.'
        );

        $this->assertGreaterThan(
            0,
            $restoredfile->get_filesize(),
            'Restored file must have a non-zero size.'
        );

        $this->assertFalse(
            $restoredfile->is_external_file(),
            'Restored file on cross-site restore must be a real stored file, not an external reference alias.'
        );
    }

    /**
     * Build the cross-course alias scenario using repository_coursefiles:
     *
     *   Course 1 - legacy course files area - stored file
     *   Course 2 - mod_resource - FILE_REFERENCE alias pointing to Course 1's legacy course file
     *
     * @return array [$course1, $course2, $contenthash, $resourcecmid]
     */
    private function setup_cross_course_alias_scenario(): array {
        global $DB;

        $generator = $this->getDataGenerator();

        // Enable coursefiles repository type if not already created.
        $generator->create_repository_type('coursefiles');

        // Course 1: enable legacy files and create a file in legacy files area.
        $course1 = $generator->create_course(['legacyfiles' => 2]);
        $cmcontext1 = context_course::instance($course1->id);

        $fs = get_file_storage();
        $originalfile = $fs->create_file_from_string([
            'contextid' => $cmcontext1->id,
            'component' => 'course',
            'filearea'  => 'legacy',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => 'samplelegacyfile.txt',
        ], 'Sample content for legacy course file');
        $contenthash = $originalfile->get_contenthash();

        // Get instance ID for coursefiles repository.
        $sql = "SELECT ri.id
                  FROM {repository} r
                  JOIN {repository_instances} ri ON ri.typeid = r.id
                 WHERE r.type = :type";
        $repoid = $DB->get_field_sql($sql, ['type' => 'coursefiles']);
        $this->assertNotEmpty($repoid, 'The coursefiles repository must be installed and have an instance.');

        // Course 2: create resource activity.
        $course2 = $generator->create_course();
        $resourcecm2 = $generator->create_module('resource', ['course' => $course2->id]);
        $cmcontext2 = context_module::instance($resourcecm2->cmid);

        // Build FILE_REFERENCE alias in Course 2's resource pointing to Course 1's legacy course file.
        $filerecord = $DB->get_record('files', ['id' => $originalfile->get_id()]);
        $reference = file_storage::pack_reference($filerecord);

        $aliasrecord = [
            'contextid' => $cmcontext2->id,
            'component' => 'mod_resource',
            'filearea'  => 'content',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => $originalfile->get_filename(),
        ];
        $fs->create_file_from_reference($aliasrecord, $repoid, $reference);

        return [$course1, $course2, $contenthash, $resourcecm2->cmid];
    }
}
