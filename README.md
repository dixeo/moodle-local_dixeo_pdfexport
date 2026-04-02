# Dixeo PDF Export

## Description

Generate a **printable PDF** of a course’s supported activities—sections, summaries, and module content—in one download, with a cover page, bookmarks, and optional **enrolment QR** when Sharecourse is available.

## About

Dixeo PDF Export (`local_dixeo_pdfexport`) is a **local plugin** that walks the course structure and builds a single PDF using Moodle’s bundled **TCPDF** library. **Editing teachers** (and anyone else holding the required capability) open **Export to PDF** from the course; the plugin renders each supported activity into HTML, normalises it for PDF output, and streams the file.

**What gets exported**

- **Sections** in order, with optional **section summaries** (HTML).
- **Activities** that have a built-in exporter: **Page**, **Label**, **Quiz**, **Glossary**, **H5P activity**, plus the Dixeo modules **[SimpleQuiz](https://github.com/dixeo/moodle-mod_simplequiz2)** (`mod_simplequiz2`) and **[Slideshow](https://github.com/dixeo/moodle-mod_slideshow)** (`mod_slideshow`) when those plugins are installed.
- A **cover page** with the course full name, **editing teacher** names and export date.

**Where the action appears**

- **Course secondary navigation** (via Moodle’s Hook API): an **Export to PDF** node for users with permission.

**Site settings** (under **Site administration → Plugins → Local plugins → Dixeo PDF Export**)

- **Export course summary** — include section 0 (course summary) and its modules.
- **Export empty sections** — include section titles even when they contain no exportable activities.

## Requirements

- Moodle **4.3** or newer (see `version.php` for `$plugin->requires`, currently `2023100900`), aligned with the minimum for **mod_simplequiz2**.

## Installation

Install via **Site administration → Plugins → Install plugins** using a ZIP of the `dixeo_pdfexport` folder, or deploy the folder under `local/dixeo_pdfexport` and complete the upgrade prompt.

## Source code

- **Repository:** https://github.com/dixeo/moodle-local_dixeo_pdfexport  
- **Bug tracker:** https://github.com/dixeo/moodle-local_dixeo_pdfexport/issues  

## External services and subscriptions

None. PDF generation runs on your server.

## Capabilities

| Capability | Purpose |
|------------|---------|
| `moodle/course:manageactivities` | Required to run the export (`export_as_pdf.php` and navigation visibility). Typically granted to editing teacher and manager roles in the course context. |

Adjust permissions under **Site administration → Users → Permissions** in the course context as needed.

## Privacy

The plugin implements the Moodle **Privacy API** as a **null provider** (`classes/privacy/provider.php`): it does **not** keep user data in plugin-owned database tables or file areas. Exports are built in a **temporary file** under Moodle’s temp directory, sent to the browser, then **removed** after the response. A PDF may still contain **names** (editing teachers on the cover) and **course titles** taken from existing course data at export time—same kind of information as viewing the course as a privileged user.

## Limitations

- **Activity coverage:** Only the module types listed above are exported; other activities are omitted from the PDF.
- **HTML and media:** Complex HTML, embedded players, and some filters may not reproduce exactly in TCPDF compared to the browser.
