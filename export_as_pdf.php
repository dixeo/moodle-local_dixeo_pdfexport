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
 * Export entrypoint.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);

require_login($courseid);
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/dixeo_pdfexport/export_as_pdf.php', ['courseid' => $courseid]));

try {
    $service = new \local_dixeo_pdfexport\local\service\course_pdf_export_service($DB);
    $filepath = $service->export($courseid);
    register_shutdown_function(static function () use ($filepath): void {
        if (is_string($filepath) && is_readable($filepath)) {
            unlink($filepath);
        }
    });
    send_file(
        $filepath,
        time() . '_course_' . $courseid . '.pdf',
        null,
        0,
        false,
        false,
        '',
        false,
        ['nocache' => true]
    );
} catch (\Throwable $exception) {
    echo $OUTPUT->header();
    $message = get_string('export_failed', 'local_dixeo_pdfexport', $exception->getMessage());
    echo $OUTPUT->notification($message, 'error');
    echo $OUTPUT->footer();
    exit;
}
