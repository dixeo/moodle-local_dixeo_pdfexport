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
 * Reads H5P content through Moodle core APIs.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport\local\h5p;

/**
 * Resolves H5P payload for a course module.
 */
class h5p_content_reader {
    /**
     * Gets decoded H5P metadata object for a course module.
     *
     * @param object $cm Course module record.
     * @return object
     * @throws \coding_exception
     */
    public function get_h5p_content(object $cm): object {
        $contextid = \context_module::instance($cm->id)->id;
        $files = get_file_storage()->get_area_files(
            $contextid,
            'mod_h5pactivity',
            'package',
            0,
            'id',
            false
        );
        $file = reset($files);
        if (!$file) {
            throw new \coding_exception(get_string('missingh5ppackage', 'local_dixeo_pdfexport'));
        }

        $url = \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            false
        );

        [, $h5p] = \core_h5p\api::get_content_from_pluginfile_url($url, true, true);
        if (!is_object($h5p)) {
            throw new \coding_exception(get_string('missingh5ppackage', 'local_dixeo_pdfexport'));
        }
        return $h5p;
    }
}
