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
 * HTML normalization utilities for PDF export.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport\local\content;

/**
 * Normalizes HTML so TCPDF renders consistent output.
 */
class html_normalizer {
    /** @var array<string, string> */
    private array $mathmap = [
        'α' => '\( \alpha \)',
        'β' => '\( \beta \)',
        'γ' => '\( \gamma \)',
        'π' => '\( \pi \)',
        '∑' => '\( \sum \)',
        '∫' => '\( \int \)',
        '∞' => '\( \infty \)',
        '≤' => '\( \leq \)',
        '≥' => '\( \geq \)',
        '≠' => '\( \neq \)',
    ];

    /**
     * Applies all normalizations.
     *
     * @param string $html
     * @return string
     */
    public function normalize(string $html): string {
        $html = $this->replace_math_characters($html);
        $dom = $this->load_dom($html);
        $this->remove_math_tex_scripts($dom);
        $this->fix_relative_units($dom->documentElement, 15, 15);
        $html = $dom->saveHTML();
        // TCPDF fetches <img src> via HTTP; pluginfile URLs return the login page without a session.
        return pluginfile_tcpdf_images::embed_in_html($html);
    }

    /**
     * Replaces selected unicode math symbols by TeX markers.
     *
     * @param string $content
     * @return string
     */
    public function replace_math_characters(string $content): string {
        return str_replace(array_keys($this->mathmap), array_values($this->mathmap), $content);
    }

    /**
     * Removes <script type="math/tex"> tags which otherwise print as raw text.
     *
     * @param \DOMDocument $dom
     */
    private function remove_math_tex_scripts(\DOMDocument $dom): void {
        $scripts = $dom->getElementsByTagName('script');
        for ($i = $scripts->length - 1; $i >= 0; $i--) {
            $script = $scripts->item($i);
            if ($script !== null && $script->getAttribute('type') === 'math/tex') {
                $parent = $script->parentNode;
                if ($parent !== null) {
                    $parent->removeChild($script);
                }
            }
        }
    }

    /**
     * Loads HTML into a UTF-8 DOM document.
     *
     * @param string $html
     * @return \DOMDocument
     */
    private function load_dom(string $html): \DOMDocument {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        return $dom;
    }

    /**
     * Recursively converts rem/em style units to px.
     *
     * @param \DOMNode|null $node
     * @param int $rootsize
     * @param int $fontsize
     */
    private function fix_relative_units(?\DOMNode $node, int $rootsize, int $fontsize): void {
        if ($node === null) {
            return;
        }

        if ($node instanceof \DOMElement) {
            foreach (['style', 'height', 'width'] as $attribute) {
                if (!$node->hasAttribute($attribute)) {
                    continue;
                }
                $value = $node->getAttribute($attribute);
                $value = preg_replace_callback('/(\d*\.?\d+)rem/', static function ($matches) use ($rootsize): string {
                    return ((int)$matches[1] * $rootsize) . 'px';
                }, $value);
                $value = preg_replace_callback('/(\d*\.?\d+)em/', static function ($matches) use ($fontsize): string {
                    return ((int)$matches[1] * $fontsize) . 'px';
                }, $value);
                $node->setAttribute($attribute, $value);
            }
        }

        foreach ($node->childNodes as $childnode) {
            $this->fix_relative_units($childnode, $rootsize, $fontsize);
        }
    }
}
