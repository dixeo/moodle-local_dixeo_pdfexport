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
 * Quiz exporter.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport\local\module_exporter;

use local_dixeo_pdfexport\local\presentation\module_html_renderer;

/**
 * Exports question bank representation for mod_quiz.
 */
class quiz_exporter implements module_exporter_interface {
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
     * Export quiz questions and options to printable HTML.
     *
     * @param \moodle_database $db Moodle database.
     * @param object $cm Course module.
     * @param object $instance Module instance record.
     * @return string
     */
    public function export_to_html(\moodle_database $db, object $cm, object $instance): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');

        $quizcontext = \context_module::instance($cm->id);
        $questions = \mod_quiz\question\bank\qbank_helper::get_question_structure(
            (int) $instance->id,
            $quizcontext
        );

        $items = [];
        $number = 1;
        foreach ($questions as $question) {
            if (!is_numeric($question->questionid)) {
                continue;
            }
            $questiontext = (string)$question->questiontext;
            if ($question->qtype === 'gapselect') {
                $questiontext = preg_replace('/\[\[\d+\]\]/', '________', $questiontext);
            }

            $answers = $db->get_records('question_answers', ['question' => $question->questionid]);
            $options = [];
            if (!empty($answers)) {
                foreach ($answers as $answer) {
                    $optiontext = trim(strip_tags((string)$answer->answer));
                    if ($optiontext === '') {
                        continue;
                    }
                    $options[] = $optiontext;
                }
            }
            $items[] = [
                'title' => "Question {$number}",
                'text' => $questiontext,
                'options' => $options,
                'hasoptions' => !empty($options),
            ];
            $number++;
        }
        return $this->htmlrenderer->render_question_items($items);
    }
}
