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
 * Tests for course PDF export service.
 *
 * @package    local_dixeo_pdfexport
 * @category   test
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport;

/**
 * Service test scaffolding.
 *
 * @covers \local_dixeo_pdfexport\local\service\course_pdf_export_service
 */
final class course_pdf_export_service_test extends \advanced_testcase {
    /**
     * Ensure export generates a PDF for generated page, quiz and h5p modules.
     */
    public function test_export_with_generated_page_quiz_and_h5pactivity(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $generator->create_course_section([
            'course' => $course->id,
            'section' => 1,
            'name' => 'Section 1',
        ]);

        $generator->create_module('page', [
            'course' => $course->id,
            'section' => 1,
            'name' => 'Page activity',
            'content' => '<p>Page content</p>',
        ]);
        $generator->create_module('quiz', [
            'course' => $course->id,
            'section' => 1,
            'name' => 'Quiz activity',
        ]);
        $generator->create_module('h5pactivity', [
            'course' => $course->id,
            'section' => 1,
            'name' => 'H5P activity',
        ]);

        $service = new \local_dixeo_pdfexport\local\service\course_pdf_export_service($DB);
        $filepath = $service->export($course->id);

        $this->assertNotEmpty($filepath);
        $this->assertStringEndsWith('.pdf', $filepath);
        $this->assertFileExists($filepath);
        $this->assertGreaterThan(0, filesize($filepath));
    }
}
