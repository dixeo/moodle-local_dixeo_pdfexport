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
 * H5P activity exporter.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport\local\module_exporter;

use local_dixeo_pdfexport\local\h5p\h5p_content_reader;
use local_dixeo_pdfexport\local\presentation\module_html_renderer;

/**
 * Exports selected H5P library types to printable HTML.
 */
class h5pactivity_exporter implements module_exporter_interface {
    /** @var h5p_content_reader */
    private h5p_content_reader $h5preader;
    /** @var module_html_renderer */
    private module_html_renderer $htmlrenderer;

    /**
     * Constructor.
     *
     * @param h5p_content_reader $h5preader H5P content reader.
     * @param module_html_renderer $htmlrenderer Mustache renderer wrapper.
     */
    public function __construct(h5p_content_reader $h5preader, module_html_renderer $htmlrenderer) {
        $this->h5preader = $h5preader;
        $this->htmlrenderer = $htmlrenderer;
    }

    /**
     * Export H5P questions/cards/clues into printable HTML.
     *
     * @param \moodle_database $db Moodle database.
     * @param object $cm Course module.
     * @param object $instance Module instance record.
     * @return string
     */
    public function export_to_html(\moodle_database $db, object $cm, object $instance): string {
        try {
            $h5p = $this->h5preader->get_h5p_content($cm);
        } catch (\coding_exception $exception) {
            // Gracefully skip invalid/incomplete H5P activities in export output.
            return '';
        }
        $content = json_decode($h5p->jsoncontent, true);
        if (!is_array($content)) {
            return '';
        }

        $library = $db->get_field('h5p_libraries', 'machinename', ['id' => $h5p->mainlibraryid]);
        $parsed = $this->extract_h5p_questions($library, $content);
        if ($parsed === null) {
            return $this->htmlrenderer->render_question_items([]);
        }
        [$questionheader, $questions] = $parsed;

        return $this->build_question_items_html($questionheader, $questions);
    }

    /**
     * Map H5P library to question blocks for rendering.
     *
     * @param string|false $library Machine name from h5p_libraries.
     * @param array $content Decoded jsoncontent.
     * @return array{0: string, 1: array<int, object>}|null Null when library is not supported.
     */
    private function extract_h5p_questions($library, array $content): ?array {
        if ($library === false || $library === '') {
            return null;
        }
        switch ($library) {
            case 'H5P.QuestionSet':
                return $this->extract_questionset($content);
            case 'H5P.Flashcards':
                return $this->extract_flashcards($content);
            case 'H5P.Crossword':
                return $this->extract_crossword($content);
            case 'H5P.FindTheWords':
                return $this->extract_find_the_words($content);
            default:
                return null;
        }
    }

    /**
     * Question set library payload to internal question list.
     *
     * @param array $content
     * @return array{0: string, 1: array<int, object>}
     */
    private function extract_questionset(array $content): array {
        $questionheader = 'Question';
        $questions = [];
        foreach (($content['questions'] ?? []) as $question) {
            $qtype = strtolower($question['params']['qtype'] ?? '');
            $questiontext = '';
            $options = [];
            if ($qtype === 'multichoice') {
                $questiontext = $question['params']['question'] ?? '';
                foreach (($question['params']['answers'] ?? []) as $answer) {
                    $options[] = $answer['text'] ?? '';
                }
            } else if ($qtype === 'dragtext') {
                $questiontext = $question['params']['taskDescription'] ?? '';
                $distractors = $question['params']['distractors'] ?? '';
                $distractors = explode(' ', str_replace('*', '', (string)$distractors));
                preg_match_all('/\*(.*?)\*/', (string)($question['params']['textField'] ?? ''), $matches);
                $answers = array_merge($matches[1], $distractors);
                shuffle($answers);
                $option = (string)($question['params']['textField'] ?? '');
                foreach ($matches[0] as $match) {
                    $option = str_replace($match, '________', $option);
                }
                $options[] = $option . ' (Options: ' . implode(', ', $answers) . ')';
            }
            $questions[] = (object)['questiontext' => $questiontext, 'options' => $options];
        }
        return [$questionheader, $questions];
    }

    /**
     * Flashcards library payload to internal question list.
     *
     * @param array $content
     * @return array{0: string, 1: array<int, object>}
     */
    private function extract_flashcards(array $content): array {
        $questionheader = 'Card';
        $questions = [];
        foreach (($content['cards'] ?? []) as $card) {
            $questions[] = (object)[
                'questiontext' => ($card['text'] ?? '') . ' (' . ($card['tip'] ?? '') . ')',
                'options' => [],
            ];
        }
        return [$questionheader, $questions];
    }

    /**
     * Crossword library payload to internal question list.
     *
     * @param array $content
     * @return array{0: string, 1: array<int, object>}
     */
    private function extract_crossword(array $content): array {
        $questionheader = 'Crossword clues';
        $answers = [];
        foreach (($content['words'] ?? []) as $word) {
            $answer = $word['clue'] ?? '';
            if (!empty($word['extraClue']['params']['text'])) {
                $answer .= ' (' . $word['extraClue']['params']['text'] . ')';
            }
            $answers[] = $answer;
        }
        return [$questionheader, [(object)['questiontext' => '', 'options' => $answers]]];
    }

    /**
     * Find the words library payload to internal question list.
     *
     * @param array $content
     * @return array{0: string, 1: array<int, object>}
     */
    private function extract_find_the_words(array $content): array {
        $questionheader = 'Find the words list';
        $words = strtoupper(trim((string)($content['wordList'] ?? '')));
        return [$questionheader, [(object)['questiontext' => '', 'options' => explode(',', $words)]]];
    }

    /**
     * Build Mustache context and render question items.
     *
     * @param string $questionheader
     * @param array $questions List of objects with questiontext and options.
     * @return string
     */
    private function build_question_items_html(string $questionheader, array $questions): string {
        $items = [];
        foreach ($questions as $index => $question) {
            $number = $index + 1;
            $questiontext = (string)$question->questiontext;
            $label = $questionheader . (count($questions) > 1 ? " {$number}" : '');
            $options = array_map(static fn($option) => (string)$option, $question->options ?? []);
            $items[] = [
                'title' => $label,
                'text' => $questiontext,
                'options' => $options,
                'hasoptions' => !empty($options),
            ];
        }
        return $this->htmlrenderer->render_question_items($items);
    }
}
