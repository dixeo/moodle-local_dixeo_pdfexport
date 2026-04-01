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
 * PDF document rendering helper.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport\local\pdf;

use local_dixeo_pdfexport\pdf\custom_tcpdf;

/**
 * Wraps TCPDF operations used by the export service.
 */
class pdf_document {
    /** @var int[] RGB color for cover title text. */
    private const COVER_TITLE_COLOR = [0, 0, 145];
    /** @var int[] RGB color for cover separator line. */
    private const COVER_SEPARATOR_COLOR = [87, 87, 87];
    /** @var int[] RGB color for section title text. */
    private const SECTION_TITLE_COLOR = [46, 134, 193];
    /** @var int[] RGB color for module title text. */
    private const MODULE_TITLE_COLOR = [93, 173, 226];

    /** @var custom_tcpdf */
    private custom_tcpdf $tcpdf;
    /** @var string */
    private string $fontfamily = 'helvetica';
    /** @var \stdClass Course record. */
    private \stdClass $course;

    /**
     * Constructor.
     *
     * @param \stdClass $course Course record.
     */
    public function __construct(\stdClass $course) {
        $this->course = $course;
        $this->tcpdf = new custom_tcpdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->tcpdf->SetCreator(PDF_CREATOR);
        $this->tcpdf->SetAuthor($course->fullname);
        $this->tcpdf->SetTitle($course->fullname);
        $this->tcpdf->SetSubject('Course Content Export');
        $this->tcpdf->SetKeywords('Moodle, PDF, Export');
        $this->tcpdf->SetHeaderData('', 0, '', '');
        $this->tcpdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $this->tcpdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
        $this->tcpdf->SetMargins(20, 20, 20);
        $this->tcpdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->tcpdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $this->tcpdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $this->tcpdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->tcpdf->setPrintHeader(false);
        $this->tcpdf->setPrintFooter(false);

        if (strpos((string)$course->lang, 'ar') === 0) {
            $this->tcpdf->setRTL(true);
        }
        $this->tcpdf->SetFont($this->fontfamily, '', 12);
    }

    /**
     * Start cover page and render cover metadata.
     *
     * @param array $coverdata
     */
    public function begin_cover(array $coverdata): void {
        $this->tcpdf->AddPage();
        $this->render_course_header();
        $teachernames = $coverdata['teachernames'] ?? [];
        $exportdate = $coverdata['exportdate'] ?? userdate(time(), get_string('strftimedaydate', 'langconfig'));
        $enrolurl = $coverdata['enrolurl'] ?? null;
        $this->render_authoring_data($teachernames, $exportdate);
        $this->render_enrolment_qr($enrolurl);
        $this->tcpdf->Bookmark($this->course->fullname, 0, 0, '', 'B', [0, 0, 0]);
    }

    /**
     * Start a new section page.
     *
     * @param object $section Course section.
     * @param int $sectionnumber Display section number.
     */
    public function begin_section(object $section, int $sectionnumber): void {
        $this->tcpdf->AddPage();
        $this->tcpdf->setPrintFooter(true);
        $this->tcpdf->SetFont($this->fontfamily, 'B', 22);
        $this->tcpdf->SetTextColor(...self::SECTION_TITLE_COLOR);
        $this->tcpdf->MultiCell(0, 0, $section->name ?? '', 0, 'L', 0, 1);
        $this->tcpdf->SetTextColor(0, 0, 0);
        $this->tcpdf->Ln(5);
        $this->tcpdf->Bookmark($sectionnumber . '. ' . ($section->name ?? ''), 1, 0, '', '', [0, 0, 0]);
    }

    /**
     * Add section summary.
     *
     * @param string $summaryhtml Formatted summary HTML.
     */
    public function add_section_summary(string $summaryhtml): void {
        $this->tcpdf->SetFont($this->fontfamily, '', 12);
        $this->tcpdf->writeHTMLCell(0, 0, '', '', $summaryhtml, 0, 1, false, true, 'L', true);
        $this->tcpdf->Ln(5);
    }

    /**
     * Add module title.
     *
     * @param string $modulename Module display name.
     */
    public function add_module_title(string $modulename): void {
        $this->tcpdf->SetFont($this->fontfamily, 'B', 16);
        $this->tcpdf->SetTextColor(...self::MODULE_TITLE_COLOR);
        $this->tcpdf->MultiCell(0, 0, $modulename, 0, 'L', 0, 1);
        $this->tcpdf->SetTextColor(0, 0, 0);
        $this->tcpdf->Ln(3);
    }

    /**
     * Add module intro.
     *
     * @param string $introhtml Formatted intro HTML.
     */
    public function add_module_intro(string $introhtml): void {
        $this->tcpdf->SetFont($this->fontfamily, '', 12);
        $this->tcpdf->writeHTMLCell(0, 0, '', '', $introhtml, 0, 1, false, true, 'L', true);
        $this->tcpdf->Ln(3);
    }

    /**
     * Add module body content.
     *
     * @param string $modulehtml Module HTML.
     */
    public function add_module_content(string $modulehtml): void {
        $this->tcpdf->SetFont($this->fontfamily, '', 12);
        $this->tcpdf->writeHTML($modulehtml, true, false, true, false, '');
        $this->tcpdf->Ln(5);
    }

    /**
     * Save rendered document to temporary file.
     *
     * @param int $courseid Course id.
     * @return string Absolute path to generated file.
     */
    public function save(int $courseid): string {
        $tempdir = make_temp_directory('local_dixeo_pdfexport');
        $filepath = $tempdir . '/course_' . $courseid . '_' . time() . '.pdf';
        $this->tcpdf->Output($filepath, 'F');
        return $filepath;
    }

    /**
     * Render cover heading.
     */
    private function render_course_header(): void {
        global $OUTPUT;
        $logopath = (string)$OUTPUT->get_logo_url();
        $this->tcpdf->Image($logopath, 90, 20, '', 30, 'PNG', '', 'T', false, 300, 'C', false, false, 0, false, false, false);
        $this->tcpdf->SetY(70);
        $this->tcpdf->SetFont($this->fontfamily, 'B', 25);
        $this->tcpdf->SetTextColor(...self::COVER_TITLE_COLOR);
        $this->tcpdf->MultiCell(0, 0, $this->course->fullname ?? '', 0, 'C', 0, 1);
        $this->tcpdf->Ln(5);
        $this->tcpdf->SetDrawColor(...self::COVER_SEPARATOR_COLOR);
        $this->tcpdf->SetLineWidth(0.5);
        $this->tcpdf->Line(20, $this->tcpdf->GetY(), 190, $this->tcpdf->GetY());
        $this->tcpdf->SetTextColor(0, 0, 0);
        $this->tcpdf->Ln(10);
    }

    /**
     * Render authoring metadata on cover.
     *
     * @param array $teachernames Teacher full names.
     * @param string $exportdate Formatted export date.
     */
    private function render_authoring_data(array $teachernames, string $exportdate): void {
        $this->tcpdf->SetFont($this->fontfamily, 'B', 16);
        $this->tcpdf->Ln(20);
        $this->tcpdf->Cell(0, 10, get_string('publishedby', 'local_dixeo_pdfexport'), 0, 1, 'C');
        foreach ($teachernames as $teachername) {
            $this->tcpdf->Cell(0, 10, (string)$teachername, 0, 1, 'C');
            $this->tcpdf->Ln(-2);
        }
        $this->tcpdf->Ln(20);
        $this->tcpdf->SetFont($this->fontfamily, '', 14);
        $this->tcpdf->Cell(0, 10, $exportdate, 0, false, 'C');
        $this->tcpdf->Ln(40);
    }

    /**
     * Render optional enrolment QR code.
     *
     * @param string|null $enrolurl Enrolment URL when available.
     */
    private function render_enrolment_qr(?string $enrolurl): void {
        if (empty($enrolurl)) {
            return;
        }
        $this->tcpdf->SetFont($this->fontfamily, '', 12);
        $this->tcpdf->Cell(0, 10, get_string('scantoenrol', 'local_dixeo_pdfexport'), 0, false, 'C');
        $newyposition = $this->tcpdf->GetY() + 10;
        $wasrtl = strpos((string)$this->course->lang, 'ar') === 0;
        if ($wasrtl) {
            $this->tcpdf->setRTL(false);
        }
        $this->tcpdf->write2DBarcode($enrolurl, 'QRCODE', '93', $newyposition, 25, 25, null, 'C');
        if ($wasrtl) {
            $this->tcpdf->setRTL(true);
        }
    }
}
