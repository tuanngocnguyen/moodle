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
 * Strings for component 'antivirus', language 'en'
 *
 * @package   core_antivirus
 * @copyright 2015 Ruslan Kabalin, Lancaster University.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actantivirushdr'] = 'Available antivirus plugins';
$string['antiviruses'] = 'Antivirus plugins';
$string['antiviruscommonsettings'] = 'Common antivirus settings';
$string['antivirussettings'] = 'Manage antivirus plugins';
$string['configantivirusplugins'] = 'Please choose the antivirus plugins you wish to use and arrange them in order of being applied.';
$string['datastream'] = 'Data';
$string['datainfecteddesc'] = 'There is a virus infected data';
$string['datainfectedname'] = 'Data infected';
$string['emailsubject'] = '{$a} :: Antivirus notification';
$string['fileinfectedname'] = 'File infected';
$string['incidencedetails'] = 'Infected file detected:
File name: {$a->filename}
File size: {$a->filesize}
File content hash: {$a->contenthash}
File content type: {$a->contenttype}
Uploaded by: {$a->author}
IP: {$a->ipaddress}
REFERER: {$a->referer}
Date: {$a->date}
{$a->notice}';
$string['notifyemail'] = 'Antivirus alert email';
$string['notifyemail_help'] = 'If set, then only the specified email will be notified when a virus is detected.
If blank, then all site admins will be notified by email when a virus is detected.';
$string['privacy:metadata'] = 'The Antivirus system does not store any personal data.';
$string['unknown'] = 'Unknown';
$string['virusfound'] = '{$a->item} has been scanned by a virus checker and found to be infected!';
