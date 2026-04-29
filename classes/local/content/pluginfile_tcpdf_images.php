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
 * Embeds Moodle pluginfile images for TCPDF HTML rendering.
 *
 * @package    local_dixeo_pdfexport
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dixeo_pdfexport\local\content;

/**
 * Replaces pluginfile image URLs with TCPDF inline format (@ + base64), matching core dataformat_pdf.
 *
 * TCPDF otherwise HTTP-fetches src URLs and receives the login page (no session), which breaks Image().
 */
class pluginfile_tcpdf_images {
    /**
     * Rewrite <img src="…pluginfile…"> to inline image data TCPDF accepts.
     *
     * @param string $html
     * @return string
     */
    public static function embed_in_html(string $html): string {
        if (stripos($html, 'pluginfile.php') === false && stripos($html, 'tokenpluginfile.php') === false) {
            return $html;
        }

        return preg_replace_callback(
            '/<img\b[^>]*\bsrc\s*=\s*(["\'])([^"\']*)\1[^>]*\/?>/i',
            static function (array $matches): string {
                $quote = $matches[1];
                $src = $matches[2];
                $inline = self::src_to_tcpdf_inline($src);
                if ($inline === null) {
                    return $matches[0];
                }
                return str_replace($quote . $src . $quote, $quote . $inline . $quote, $matches[0]);
            },
            $html
        );
    }

    /**
     * Converts an image src into TCPDF inline image data.
     *
     * @param string $src Raw img src attribute value.
     * @return string|null Inline src or null to leave unchanged.
     */
    private static function src_to_tcpdf_inline(string $src): ?string {
        $src = trim($src);
        if ($src === '') {
            return null;
        }
        // Already in TCPDF “@” stream format (must stay valid base64 after the @).
        if ($src[0] === '@') {
            return null;
        }
        // Data URLs are handled by TCPDF; leave them (DOM may have kept them intact).
        if (strncasecmp($src, 'data:', 5) === 0) {
            return null;
        }

        $fullpath = self::pluginfile_src_to_storage_fullpath($src);
        if ($fullpath === null) {
            return null;
        }

        $file = get_file_storage()->get_file_by_hash(sha1($fullpath));
        if ($file === false) {
            return null;
        }

        return '@' . base64_encode($file->get_content());
    }

    /**
     * Build the storage full path used by {@see \file_storage::get_file_by_hash()}.
     *
     * Mirrors {@see \core\dataformat\base::replace_pluginfile_images()} with extra URL forms.
     *
     * @param string $src
     * @return string|null e.g. /10/mod_slideshow/content/3/image.png
     */
    private static function pluginfile_src_to_storage_fullpath(string $src): ?string {
        // Token URLs contain the substring "pluginfile.php", so check token URLs first.
        if (stripos($src, 'tokenpluginfile.php') !== false) {
            $tokenpattern =
                '/tokenpluginfile\.php\/[^\/]+\/(?<context>\d+)\/(?<component>[^\/]+)\/'
                . '(?<filearea>[^\/]+)\/(?:(?<itemid>\d+)\/)?(?<path>[^?#]+)/i';
            $tokenmatched = preg_match($tokenpattern, $src, $args);
            if ($tokenmatched) {
                return self::build_fullpath_from_regex_groups($args);
            }
        }

        // Slash-argument form: .../pluginfile.php/ctx/comp/area/(itemid/)path.
        if (stripos($src, 'pluginfile.php') !== false) {
            $pos = stripos($src, 'pluginfile.php');
            $from = substr($src, $pos);
            $slashpattern =
                '/^pluginfile\.php\/(?<context>\d+)\/(?<component>[^\/]+)\/(?<filearea>[^\/]+)\/'
                . '(?:(?<itemid>\d+)\/)?(?<path>[^?#]+)/iu';
            $slashmatched = preg_match($slashpattern, $from, $args);
            if ($slashmatched) {
                return self::build_fullpath_from_regex_groups($args);
            }
        }

        // Query form: pluginfile.php?file=/ctx/comp/area/itemid/path.
        $decoded = html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $query = parse_url($decoded, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            parse_str($query, $params);
            if (!empty($params['file']) && is_string($params['file'])) {
                $file = urldecode($params['file']);
                $querypattern =
                    '/^\/(?<context>\d+)\/(?<component>[^\/]+)\/(?<filearea>[^\/]+)\/'
                    . '(?:(?<itemid>\d+)\/)?(?<path>.+)$/u';
                $querymatched = preg_match($querypattern, $file, $args);
                if ($querymatched) {
                    return self::build_fullpath_from_regex_groups($args);
                }
            }
        }

        return null;
    }

    /**
     * Builds Moodle file storage fullpath from regex groups.
     *
     * @param array $args Named capture groups from preg_match.
     * @return string
     */
    private static function build_fullpath_from_regex_groups(array $args): string {
        $context = $args['context'];
        $component = clean_param($args['component'], PARAM_COMPONENT);
        $filearea = clean_param($args['filearea'], PARAM_AREA);
        $itemid = !empty($args['itemid']) ? $args['itemid'] : '0';
        $path = clean_param(urldecode($args['path']), PARAM_PATH);

        return '/' . $context . '/' . $component . '/' . $filearea . '/' . $itemid . '/' . $path;
    }
}
