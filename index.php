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

admin_externalpage_setup('report_moduleusage', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('moduleusage', 'report_moduleusage'));

echo html_writer::start_tag('div', array('class' => 'report_moduleusage_main'));
report_moduleusage_output_table($category);
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', array('class' => 'report_moduleusage_sub'));
if ($subcats = $DB->get_records('course_categories', array('parent'=>$category, 'visible'=>1))) {
    foreach ($subcats as $cat) {
        report_moduleusage_output_table($cat->id);
    }
}
echo html_writer::end_tag('div');


echo $OUTPUT->footer();
