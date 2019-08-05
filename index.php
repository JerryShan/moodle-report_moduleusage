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
 * Module usage report
 *
 * @package    report
 * @subpackage moduleusage
 * @copyright  2016 Paul Nicholls
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once('./locallib.php');

$category = optional_param('category', 0, PARAM_INT);

// CSV format
$format = optional_param('format','',PARAM_ALPHA);
$excel = $format == 'excelcsv';
$csv = $format == 'csv' || $excel;

function csv_quote($value) {
    global $excel;
    if ($excel) {
        return core_text::convert('"'.str_replace('"',"'",$value).'"','UTF-8','UTF-16LE');
    } else {
        return '"'.str_replace('"',"'",$value).'"';
    }
}

if ($csv) {
	header('Content-Disposition: attachment; filename=report_moduleusage-'.$category.'.csv');

    // Unicode byte-order mark for Excel
    if ($excel) {
        header('Content-Type: text/csv; charset=UTF-16LE');
        print chr(0xFF).chr(0xFE);
        $sep="\t".chr(0);
        $line="\n".chr(0);
    } else {
        header('Content-Type: text/csv; charset=UTF-8');
        $sep=",";
        $line="\n";
	}

	report_moduleusage_output_table($category, $csv, $sep, $line);
} else {
    admin_externalpage_setup('report_moduleusage', '', null, '', array('pagelayout'=>'report'));
    echo $OUTPUT->header();

    echo $OUTPUT->heading(get_string('moduleusage', 'report_moduleusage'));

    echo html_writer::start_tag('div', array('class' => 'report_moduleusage_main'));
    report_moduleusage_output_table($category);
    echo html_writer::end_tag('div');

    echo html_writer::start_tag('div', array('class' => 'report_moduleusage_sub'));
}

if ($subcats = $DB->get_records('course_categories', array('parent'=>$category, 'visible'=>1))) {
    foreach ($subcats as $cat) {
        report_moduleusage_output_table($cat->id, $csv, $sep, $line);
    }
}

if ($csv) {
    exit;
} else {
    echo html_writer::end_tag('div');
}

echo html_writer::start_tag('div', array('class' => 'report_moduleusage_main'));

print '<ul class="moduleusage-actions">
        <li><a href="index.php?category='.$category.'&amp;format=csv">'.get_string('csvdownload','completion').'</a></li>
        <li><a href="index.php?category='.$category.'&amp;format=excelcsv">'.get_string('excelcsvdownload','completion').'</a></li>
        </ul>';

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
