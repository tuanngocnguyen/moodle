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
 * Question bank settings page class.
 *
 * @package    qbank_columnsortorder
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Nathan Nguyen <nathannguyen@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$returnurl = required_param('returnurl', PARAM_LOCALURL);

require_sesskey();
require_login();

// Reset user preference.
unset_user_preference('qbank_columnsortorder_enabledcol');
unset_user_preference('qbank_columnsortorder_pinnedcols');
unset_user_preference('qbank_columnsortorder_hiddencols');
unset_user_preference('qbank_columnsortorder_colsize');

redirect($returnurl);