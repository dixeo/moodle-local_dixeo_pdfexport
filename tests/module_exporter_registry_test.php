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
 * Tests for module exporter registry.
 *
 * @package    local_dixeo_pdfexport
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport;

/**
 * Registry test case.
 *
 * @covers \local_dixeo_pdfexport\local\module_exporter\module_exporter_registry
 */
final class module_exporter_registry_test extends \advanced_testcase {
    /**
     * Build module HTML renderer test double.
     *
     * @return \local_dixeo_pdfexport\local\presentation\module_html_renderer
     */
    private function get_test_html_renderer(): \local_dixeo_pdfexport\local\presentation\module_html_renderer {
        $output = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['render_from_template'])
            ->getMock();
        return new \local_dixeo_pdfexport\local\presentation\module_html_renderer($output);
    }

    /**
     * Ensures registry returns exporter for known modules.
     */
    public function test_get_exporter_returns_known_exporters(): void {
        $registry = new \local_dixeo_pdfexport\local\module_exporter\module_exporter_registry($this->get_test_html_renderer());
        $this->assertInstanceOf(
            \local_dixeo_pdfexport\local\module_exporter\module_exporter_interface::class,
            $registry->get_exporter('page')
        );
        $this->assertInstanceOf(
            \local_dixeo_pdfexport\local\module_exporter\module_exporter_interface::class,
            $registry->get_exporter('h5pactivity')
        );
    }

    /**
     * Ensures registry returns null for unsupported modules.
     */
    public function test_get_exporter_returns_null_for_unknown_module(): void {
        $registry = new \local_dixeo_pdfexport\local\module_exporter\module_exporter_registry($this->get_test_html_renderer());
        $this->assertNull($registry->get_exporter('forum'));
    }
}
