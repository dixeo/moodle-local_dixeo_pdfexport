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
 * Mustache rendering helper for module HTML fragments.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport\local\presentation;

/**
 * Centralizes Mustache rendering for module exporters.
 */
class module_html_renderer {
    /** @var object */
    private object $output;

    /**
     * Constructor.
     *
     * @param object $output Renderer used for Mustache rendering.
     */
    public function __construct(object $output) {
        if (!method_exists($output, 'render_from_template')) {
            throw new \coding_exception('Renderer must provide render_from_template().');
        }
        $this->output = $output;
    }

    /**
     * Render question-like content (question + options list).
     *
     * @param array $items
     * @return string
     */
    public function render_question_items(array $items): string {
        $context = [
            'items' => $items,
            'nooptionslabel' => get_string('nooptionsavailable', 'local_dixeo_pdfexport'),
        ];
        return $this->output->render_from_template('local_dixeo_pdfexport/module_question_items', $context);
    }

    /**
     * Render glossary entries.
     *
     * @param array $entries
     * @return string
     */
    public function render_glossary_entries(array $entries): string {
        return $this->output->render_from_template('local_dixeo_pdfexport/module_glossary_entries', ['entries' => $entries]);
    }

    /**
     * Render slideshow slides.
     *
     * @param array $slides
     * @return string
     */
    public function render_slideshow(array $slides): string {
        $html = $this->output->render_from_template('local_dixeo_pdfexport/module_slideshow', [
            'slides' => $slides,
        ]);
        // PDF/slideshow layout rules (kept in PHP: <style> in .mustache fails Mustache HTML validation).
        $style = '<style>.slideshow .pexels-credits{display:block !important;clear:both !important;}' .
            '.slideshow .slide p{display:block !important;clear:both !important;}</style>';
        return $style . $html;
    }
}
