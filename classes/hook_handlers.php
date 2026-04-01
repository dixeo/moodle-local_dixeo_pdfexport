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
 * Hook handlers.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport;

use core\hook\navigation\secondary_extend;

/**
 * Navigation hook handlers.
 */
class hook_handlers {
    /**
     * Adds the export link in course secondary navigation.
     *
     * @param secondary_extend $hook Hook payload.
     */
    public static function secondary_extend(secondary_extend $hook): void {
        global $PAGE;

        if (empty($PAGE->course->id) || $PAGE->course->id <= 1) {
            return;
        }

        $context = \context_course::instance($PAGE->course->id);
        if (!has_capability('moodle/course:manageactivities', $context)) {
            return;
        }

        $secondary = $hook->get_secondaryview();
        $url = new \moodle_url('/local/dixeo_pdfexport/export_as_pdf.php', ['courseid' => $PAGE->course->id]);
        $node = \navigation_node::create(
            get_string('exporttopdf', 'local_dixeo_pdfexport'),
            $url,
            \navigation_node::TYPE_SETTING,
            null,
            'dixeo_pdfexport'
        );
        $secondary->add_node($node);
    }
}
