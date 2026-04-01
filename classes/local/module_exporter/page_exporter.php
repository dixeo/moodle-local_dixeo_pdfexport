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
 * Page exporter.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport\local\module_exporter;

use local_dixeo_pdfexport\local\content\html_normalizer;

/**
 * Exports mod_page content.
 */
class page_exporter implements module_exporter_interface {
    /**
     * Export page content to printable HTML.
     *
     * @param \moodle_database $db Moodle database.
     * @param object $cm Course module.
     * @param object $instance Module instance record.
     * @return string
     */
    public function export_to_html(\moodle_database $db, object $cm, object $instance): string {
        if (empty($instance->content)) {
            return '';
        }

        $context = \context_module::instance($cm->id);
        $content = file_rewrite_pluginfile_urls(
            $instance->content,
            'pluginfile.php',
            $context->id,
            'local_dixeo_pdfexport',
            'mod_page_content',
            $instance->revision
        );
        $content = format_text($content, $instance->contentformat, [
            'context' => $context,
            'trusted' => true,
            'filter' => true,
            'noclean' => true,
        ]);

        return '<div>' . (new html_normalizer())->normalize($content) . '</div>';
    }
}
