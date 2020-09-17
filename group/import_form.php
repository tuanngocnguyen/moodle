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
 * A form for group import.
 *
 * @package    core_group
 * @copyright  2010 Toyomoyo (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir . '/csvlib.class.php');

/**
 * Groups import form class
 *
 * @package    core_group
 * @copyright  2010 Toyomoyo (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class groups_import_form extends moodleform {

    /** @var string $type import type */
    protected $type = "importgroups";

    /** @var array $requiredfields required fields
     *  Allowed alternative headers if specify: "groupname" => ['/^groupname$/i', '/^group$/i', '/^group name$/i']
     */
    protected $requiredfields = ["groupname" => ['/^group$/i', '/^group name$/i']];

    /** @var array $optionalfields optional fields */
    protected $optionalfields = [
        "coursename"      => '',
        "idnumber"        => ['/^id number$/i', '/^studentid$/i', '/^student id$/i', '/^user id$/i', '/^userid$/i'],
        "groupidnumber"   => '',
        "description"     => ['/^desc$/i'],
        "enrolmentkey"    => ['/^enrolment key$/i', '/^enrolkey$/i', '/^enrol key$/i'],
        "groupingname"    => '',
        "enablemessaging" => '',
        "picture"         => '',
        "hidepicture"     => '',
        "member"          => ['/^user/i', '/^username$/i', '/^login$/i', '/^login name$/i'],
    ];

    /**
     * Form definition
     */
    function definition() {
        $mform =& $this->_form;
        $data  = $this->_customdata;

        //fill in the data depending on page params
        //later using set_data
        $mform->addElement('header', 'general', get_string('general'));

        $filepickeroptions = array();
        $filepickeroptions['filetypes'] = '*';
        $filepickeroptions['maxbytes'] = get_max_upload_file_size();
        $mform->addElement('filepicker', 'userfile', get_string('import'), null, $filepickeroptions);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'group'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'group'), $choices);
        $mform->setDefault('encoding', 'UTF-8');
        $this->add_action_buttons(true, get_string($this->type, 'core_group'));

        $this->set_data($data);
    }

    /**
     * Get data from csv file
     *
     * @return array|object|null list of group details
     */
    public function get_data() {
        global $CFG, $PAGE;

        $data = parent::get_data();
        $text = $this->get_file_content('userfile');
        if (empty($data) || empty($text)) {
            return null;
        }
        require_once($CFG->libdir . '/csvlib.class.php');
        $text = preg_replace('!\r\n?!', "\n", $text);
        $delimiter = $data->delimiter_name;
        $encoding = $data->encoding;

        $importid = csv_import_reader::get_new_iid($this->type);
        $csvimport = new csv_import_reader($importid, $this->type);
        $readcount = $csvimport->load_csv_content($text, $encoding, $delimiter);
        $rawlines = explode("\n", $text);

        if ($readcount === false) {
            print_error('csvfileerror', 'error', $PAGE->url, $csvimport->get_error());
        } else if ($readcount == 0) {
            print_error('csvemptyfile', 'error', $PAGE->url, $csvimport->get_error());
        } else if ($readcount == 1) {
            print_error('csvnodata', 'error', $PAGE->url);
        }
        $csvimport->init();
        unset($text);

        // Headers.
        $header = explode($csvimport::get_delimiter($delimiter), array_shift($rawlines));
        // Check for valid field names.
        $requiredfields = $this->requiredfields;
        $optionalfields = $this->optionalfields;
        $allvalidfields = array_merge($requiredfields, $optionalfields);
        foreach ($header as $i => $h) {
            $header[$i] = trim($h, ' "\'');
            foreach ($allvalidfields as $field => $pattern) {
                // Check if there is alternative header pattern.
                if (is_array($pattern) && !empty($pattern)) {
                    // Accept alternative headers.
                    $header[$i] = preg_replace($pattern, $field, $header[$i]);
                }
            }
        }

        // Check for invalid fields.
        $invalidfields = array_diff($header, array_keys($allvalidfields));
        if (!empty($invalidfields)) {
            print_error('invalidfieldname', 'error', $PAGE->url, implode(',', $invalidfields));
        }

        // Check for missing required fields.
        $missingrequiredfields = array_diff(array_keys($requiredfields), $header);
        if (!empty($missingrequiredfields)) {
            print_error('fieldrequired', 'error', $PAGE->url, implode(',', $missingrequiredfields));
        }

        $records = [];
        while ($line = $csvimport->next()) {
            $record = [];
            $record['lang'] = current_language();
            foreach ($line as $key => $value) {
                $record[$header[$key]] = trim($value);
            }
            $records[] = $record;
        }
        $csvimport->close();
        return $records;
    }
}

/**
 * Groupings import form class
 *
 * @package    core_group
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class groupings_import_form extends groups_import_form {
    /** @var string $type import type */
    protected $type = "importgroupings";

    /** @var array $requiredfields required fields */
    protected $requiredfields = ["grouping" => 1];

    /** @var array $optionalfields optional fields, allow alternative headers for groupname */
    protected $optionalfields = ["groupname" => ['/^group$/i', '/^group name$/i']];
}
