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
 * Report settings
 *
 * @package    report
 * @subpackage moduleusage
 * @copyright  2016 Paul Nicholls
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('report_moduleusage', get_string('moduleusage', 'report_moduleusage'), "$CFG->wwwroot/report/moduleusage/index.php", 'report/moduleusage:view'));

if ($hassiteconfig) {
    $enrolplugins = core_component::get_plugin_list('enrol');
    foreach ($enrolplugins as $enrolplugin => $path) {
        $enrolplugins[$enrolplugin] = get_string('pluginname', 'enrol_' . $enrolplugin);
    }
    $settings->add(new admin_setting_configmultiselect('report_moduleusage/enrolmethods',
                                                        get_string('enrolmethods', 'report_moduleusage'),
                                                        get_string('enrolmethods_desc', 'report_moduleusage'),
                                                        array(),
                                                        $enrolplugins));
}