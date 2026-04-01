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
 * TCPDF customizations.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport\pdf;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/tcpdf/tcpdf.php');

/**
 * Custom TCPDF implementation used by exporter.
 */
class custom_tcpdf extends \TCPDF {
    // phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
    /**
     * Footer with current page number.
     *
     * @return void
     */
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, $this->getAliasNumPage(), 0, 0, 'R');
    }
    // phpcs:enable

    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
    /**
     * Prevent noisy alt text output in generated PDF.
     *
     * @param string $text
     * @param array $altpos
     * @return void
     */
    protected function _printalt($text, $altpos) {
        // Intentionally left blank; suppress unused formal parameters from parent signature.
        unset($text, $altpos);
    }
    // phpcs:enable
}
