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
 * Simplequiz2 exporter.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport\local\module_exporter;

use local_dixeo_pdfexport\local\presentation\module_html_renderer;

/**
 * Exports simplequiz2 question payload to printable HTML.
 */
class simplequiz2_exporter implements module_exporter_interface {
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
     * Export simplequiz2 questions to printable HTML.
     *
     * @param \moodle_database $db Moodle database.
     * @param object $cm Course module.
     * @param object $instance Module instance record.
     * @return string
     */
    public function export_to_html(\moodle_database $db, object $cm, object $instance): string {
        $questions = json_decode($instance->questions, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($questions)) {
            throw new \coding_exception(get_string('invalidsimplequiz2payload', 'local_dixeo_pdfexport'));
        }

        $items = [];
        foreach ($questions as $index => $question) {
            $number = $index + 1;
            $options = [];
            if (!empty($question['answers']) && is_array($question['answers'])) {
                foreach ($question['answers'] as $answer) {
                    $optiontext = trim(strip_tags((string)($answer['text'] ?? '')));
                    if ($optiontext === '') {
                        continue;
                    }
                    $options[] = $optiontext;
                }
            }
            $items[] = [
                'title' => "Question {$number}",
                'text' => (string)($question['text'] ?? ''),
                'options' => $options,
                'hasoptions' => !empty($options),
            ];
        }

        return $this->htmlrenderer->render_question_items($items);
    }
}
