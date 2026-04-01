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
 * Glossary exporter.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport\local\module_exporter;

use local_dixeo_pdfexport\local\presentation\module_html_renderer;

/**
 * Exports glossary entries in concept order.
 */
class glossary_exporter implements module_exporter_interface {
    /** @var module_html_renderer */
    private module_html_renderer $htmlrenderer;

    /**
     * Constructor.
     *
     * @param module_html_renderer $htmlrenderer Mustache renderer wrapper.
     */
    public function __construct(module_html_renderer $htmlrenderer) {
        $this->htmlrenderer = $htmlrenderer;
    }

    /**
     * Export glossary entries to printable HTML.
     *
     * @param \moodle_database $db Moodle database.
     * @param object $cm Course module.
     * @param object $instance Module instance record.
     * @return string
     */
    public function export_to_html(\moodle_database $db, object $cm, object $instance): string {
        $entries = $db->get_records('glossary_entries', ['glossaryid' => $instance->id], 'concept ASC');
        $context = \context_module::instance($cm->id);

        $templateentries = [];
        foreach ($entries as $entry) {
            $templateentries[] = [
                'concept' => (string)$entry->concept,
                'definitionhtml' => format_text($entry->definition, $entry->definitionformat, ['context' => $context]),
            ];
        }

        return $this->htmlrenderer->render_glossary_entries($templateentries);
    }
}
