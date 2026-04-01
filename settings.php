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
 * Plugin settings.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_dixeo_pdfexport',
        get_string('pluginname', 'local_dixeo_pdfexport')
    );
    $ADMIN->add('localplugins', $settings);

    $yesno = [
        1 => get_string('yes'),
        0 => get_string('no'),
    ];

    $settings->add(new admin_setting_configselect(
        'local_dixeo_pdfexport/export_course_summary',
        get_string('export_course_summary', 'local_dixeo_pdfexport'),
        get_string('export_course_summary_desc', 'local_dixeo_pdfexport'),
        1,
        $yesno
    ));

    $settings->add(new admin_setting_configselect(
        'local_dixeo_pdfexport/export_empty_sections',
        get_string('export_empty_sections', 'local_dixeo_pdfexport'),
        get_string('export_empty_sections_desc', 'local_dixeo_pdfexport'),
        1,
        $yesno
    ));
}
