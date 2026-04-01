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
 * Module exporter registry.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport\local\module_exporter;

use local_dixeo_pdfexport\local\h5p\h5p_content_reader;
use local_dixeo_pdfexport\local\presentation\module_html_renderer;

/**
 * Provides module exporters by module name.
 */
class module_exporter_registry {
    /** @var module_html_renderer */
    private module_html_renderer $htmlrenderer;
    /** @var array<string, module_exporter_interface> */
    private array $exporters;

    /**
     * Constructor.
     *
     * @param module_html_renderer $htmlrenderer Mustache renderer wrapper.
     */
    public function __construct(module_html_renderer $htmlrenderer) {
        $this->htmlrenderer = $htmlrenderer;
        $this->exporters = [
            'page' => new page_exporter(),
            'label' => new label_exporter(),
            'quiz' => new quiz_exporter($this->htmlrenderer),
            'simplequiz2' => new simplequiz2_exporter($this->htmlrenderer),
            'glossary' => new glossary_exporter($this->htmlrenderer),
            'slideshow' => new slideshow_exporter($this->htmlrenderer),
            'h5pactivity' => new h5pactivity_exporter(new h5p_content_reader(), $this->htmlrenderer),
        ];
    }

    /**
     * Returns exporter for a module, or null when unsupported.
     *
     * @param string $modname
     * @return module_exporter_interface|null
     */
    public function get_exporter(string $modname): ?module_exporter_interface {
        return $this->exporters[$modname] ?? null;
    }
}
