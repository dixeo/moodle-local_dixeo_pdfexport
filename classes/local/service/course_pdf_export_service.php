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
 * Course PDF export service.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport\local\service;

use local_dixeo_pdfexport\local\module_exporter\module_exporter_interface;
use local_dixeo_pdfexport\local\module_exporter\module_exporter_registry;
use local_dixeo_pdfexport\local\presentation\module_html_renderer;
use local_dixeo_pdfexport\local\pdf\pdf_document;

/**
 * Application service orchestrating course PDF generation.
 */
class course_pdf_export_service {
    /** @var \moodle_database */
    private \moodle_database $db;
    /** @var module_exporter_registry */
    private module_exporter_registry $registry;

    /**
     * Constructor.
     *
     * @param \moodle_database $db Moodle database.
     */
    public function __construct(\moodle_database $db) {
        global $PAGE;
        $this->db = $db;
        $this->registry = new module_exporter_registry(
            new module_html_renderer($PAGE->get_renderer('core'))
        );
    }

    /**
     * Exports a course to PDF and returns generated file path.
     *
     * @param int $courseid
     * @return string
     */
    public function export(int $courseid): string {
        $course = $this->db->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $pdf = new pdf_document($course);
        $pdf->begin_cover($this->build_cover_data($courseid));

        $moduletypemap = $this->get_module_type_map();
        $sections = $this->db->get_records('course_sections', ['course' => $courseid], 'section ASC');
        $sectionnumber = 1;
        foreach ($sections as $section) {
            if ($this->should_skip_course_summary_section($section)) {
                continue;
            }

            $supported = $this->build_supported_modules_list(
                $this->get_section_modules_in_order($courseid, $section),
                $moduletypemap
            );

            if ($this->should_skip_empty_section($supported, $section)) {
                continue;
            }

            $this->write_section_to_pdf($pdf, $section, $sectionnumber, $courseid, $supported);
            $sectionnumber++;
        }

        return $pdf->save($courseid);
    }

    /**
     * Whether to skip section 0 when course summary export is disabled.
     *
     * @param object $section Course section record.
     * @return bool
     */
    private function should_skip_course_summary_section(object $section): bool {
        return (int)$section->section === 0
            && (int)get_config('local_dixeo_pdfexport', 'export_course_summary') === 0;
    }

    /**
     * Build list of course modules that have a registered exporter.
     *
     * @param array $orderedmodules Course module records in order.
     * @param array $moduletypemap Module type id => name map.
     * @return array<int, array{0: object, 1: string, 2: module_exporter_interface}>
     */
    private function build_supported_modules_list(array $orderedmodules, array $moduletypemap): array {
        $supported = [];
        foreach ($orderedmodules as $cm) {
            $modname = $moduletypemap[$cm->module] ?? null;
            if ($modname === null) {
                continue;
            }
            $exporter = $this->registry->get_exporter($modname);
            if ($exporter !== null) {
                $supported[] = [$cm, $modname, $exporter];
            }
        }
        return $supported;
    }

    /**
     * Whether to skip a section that has no exportable modules and no visible heading.
     *
     * @param array $supported Supported module tuples.
     * @param object $section Section record.
     * @return bool
     */
    private function should_skip_empty_section(array $supported, object $section): bool {
        if (!empty($supported)) {
            return false;
        }
        $exportempty = (int)get_config('local_dixeo_pdfexport', 'export_empty_sections') !== 0;

        return empty($section->name) || !$exportempty;
    }

    /**
     * Render one course section into the PDF document.
     *
     * @param pdf_document $pdf
     * @param object $section
     * @param int $sectionnumber
     * @param int $courseid
     * @param array $supported
     * @return void
     */
    private function write_section_to_pdf(
        pdf_document $pdf,
        object $section,
        int $sectionnumber,
        int $courseid,
        array $supported
    ): void {
        $instancesbytype = $this->get_instances_by_type($supported);
        $pdf->begin_section($section, $sectionnumber);
        if (!empty($section->summary)) {
            $summaryformat = property_exists($section, 'summaryformat') ? $section->summaryformat : FORMAT_HTML;
            $summary = format_text(
                $section->summary,
                $summaryformat,
                ['context' => \context_course::instance($courseid)]
            );
            $pdf->add_section_summary($summary);
        }

        foreach ($supported as [$cm, $modname, $exporter]) {
            $instance = $instancesbytype[$modname][$cm->instance] ?? null;
            if ($instance === null) {
                continue;
            }
            $this->write_module_to_pdf($pdf, $cm, $modname, $exporter, $instance);
        }
    }

    /**
     * Render one activity instance into the PDF document.
     *
     * @param pdf_document $pdf
     * @param object $cm
     * @param string $modname
     * @param module_exporter_interface $exporter
     * @param object $instance
     * @return void
     */
    private function write_module_to_pdf(
        pdf_document $pdf,
        object $cm,
        string $modname,
        module_exporter_interface $exporter,
        object $instance
    ): void {
        if ($modname === 'slideshow') {
            $pdf->add_module_content('<div style="height:8px;line-height:8px;">&nbsp;</div>');
        }
        if ($modname !== 'label') {
            $pdf->add_module_title((string)($instance->name ?? ''));
        }
        if ($modname !== 'slideshow' && !empty($instance->intro)) {
            $introformat = property_exists($instance, 'introformat') ? $instance->introformat : FORMAT_HTML;
            $intro = format_text(
                $instance->intro,
                $introformat,
                ['context' => \context_module::instance($cm->id)]
            );
            $pdf->add_module_intro($intro);
        }
        $pdf->add_module_content($exporter->export_to_html($this->db, $cm, $instance));
    }

    /**
     * Returns modules of a section in configured sequence order.
     *
     * @param int $courseid
     * @param object $section
     * @return array
     */
    private function get_section_modules_in_order(int $courseid, object $section): array {
        $modules = $this->db->get_records('course_modules', [
            'course' => $courseid,
            'section' => $section->id,
            'deletioninprogress' => 0,
        ]);

        if (empty($section->sequence)) {
            return array_values($modules);
        }

        $ordered = [];
        foreach (explode(',', $section->sequence) as $cmid) {
            if ($cmid === '' || !isset($modules[$cmid])) {
                continue;
            }
            $ordered[] = $modules[$cmid];
        }
        return $ordered;
    }

    /**
     * Get modules map id => name once per export.
     *
     * @return array<int, string>
     */
    private function get_module_type_map(): array {
        return $this->db->get_records_menu('modules', null, '', 'id, name');
    }

    /**
     * Batch fetch module instances grouped by module type.
     *
     * @param array $supported List of [cm, modname, exporter] tuples.
     * @return array
     */
    private function get_instances_by_type(array $supported): array {
        $idsbytype = [];
        foreach ($supported as [$cm, $modname]) {
            if (!isset($idsbytype[$modname])) {
                $idsbytype[$modname] = [];
            }
            $idsbytype[$modname][] = $cm->instance;
        }

        $instancesbytype = [];
        foreach ($idsbytype as $modname => $ids) {
            $instancesbytype[$modname] = $this->db->get_records_list($modname, 'id', array_unique($ids));
        }

        return $instancesbytype;
    }

    /**
     * Build all cover metadata in service layer.
     *
     * @param int $courseid
     * @return array
     */
    private function build_cover_data(int $courseid): array {
        return [
            'teachernames' => $this->get_teacher_names($courseid),
            'exportdate' => userdate(time(), get_string('strftimedaydate', 'langconfig')),
            'enrolurl' => $this->get_sharecourse_enrol_url($courseid),
        ];
    }

    /**
     * Resolve teacher names for cover page.
     *
     * @param int $courseid
     * @return array
     */
    private function get_teacher_names(int $courseid): array {
        $context = \context_course::instance($courseid);
        $role = $this->db->get_record('role', ['shortname' => 'editingteacher']);
        if (!$role) {
            return [];
        }
        $teachers = get_role_users($role->id, $context);
        return array_map(static fn($teacher) => fullname($teacher), $teachers);
    }

    /**
     * Resolve sharecourse enrol URL when available.
     *
     * @param int $courseid
     * @return string|null
     */
    private function get_sharecourse_enrol_url(int $courseid): ?string {
        if (!class_exists('\local_sharecourse\sharecourse_helper')) {
            return null;
        }
        if (class_exists(\local_dixeo\service\plugin_installation_service::class)) {
            if (!\local_dixeo\service\plugin_installation_service::is_component_installed('local_sharecourse')) {
                return null;
            }
        } else if (\core_plugin_manager::instance()->get_plugin_info('local_sharecourse') === null) {
            return null;
        }
        $helper = new \local_sharecourse\sharecourse_helper($this->db);
        return $helper->get_sharecourse_url($courseid)->out();
    }
}
