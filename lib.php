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
 * Library callbacks.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Pluginfile callback for rewritten content URLs in exported HTML.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function local_dixeo_pdfexport_pluginfile(
    $course,
    $cm,
    $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    // Moodle API requires $course and $cm; this callback only uses $context.
    unset($course, $cm);

    $parts = explode('_', $filearea);
    $actualfilearea = array_pop($parts);
    $component = implode('_', $parts);

    array_shift($args);
    $relativepath = implode('/', $args);
    $fullpath = "/{$context->id}/{$component}/{$actualfilearea}/0/{$relativepath}";

    $file = get_file_storage()->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Optional social menu integration.
 *
 * @param moodle_page $page
 * @return array
 */
function local_dixeo_pdfexport_add_button_to_social_menu($page): array {
    if (!str_contains($page->url->get_path(), '/course/view.php')) {
        return [];
    }
    if (empty($page->course->id) || !has_capability('moodle/course:manageactivities', $page->context)) {
        return [];
    }

    $text = get_string('exporttopdf', 'local_dixeo_pdfexport');
    return [[
        'url' => new \moodle_url('/local/dixeo_pdfexport/export_as_pdf.php', ['courseid' => $page->course->id]),
        'icon' => '<i class="icon fa fa-regular fa-file-pdf mr-2"></i>',
        'params' => [
            'target' => '_blank',
            'class' => 'btn btn-secondary course-exporter-button',
            'title' => $text,
            'style' => 'padding: 13px 19px;',
        ],
    ]];
}

/**
 * Dixeo teacher toolbar integration (PDF export).
 *
 * @param \moodle_page $page
 * @return array<int, array<string, mixed>>
 */
function local_dixeo_pdfexport_add_button_to_teacher_toolbar($page): array {
    if (!str_contains($page->url->get_path(), '/course/view.php')) {
        return [];
    }
    if (empty($page->course->id) || !has_capability('moodle/course:manageactivities', $page->context)) {
        return [];
    }

    $text = get_string('exporttopdf', 'local_dixeo_pdfexport');
    $url = (new \moodle_url('/local/dixeo_pdfexport/export_as_pdf.php', ['courseid' => $page->course->id]))->out(false);

    return [[
        'key' => 'pdf',
        'icon' => 'pdf',
        'label' => $text,
        'title' => $text,
        'ismobileoverflow' => true,
        'islink' => true,
        'isdisabled' => false,
        'url' => $url,
        'linktarget' => '_blank',
    ]];
}
