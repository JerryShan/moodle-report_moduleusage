<?php
namespace report_moduleusage;
use \html_writer;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

class usage_table extends \table_sql {
    private $total_courses = 0;
    private $visible_courses = 0;
    private $heading = '';
    function __construct($uniqueid) {
        $this->heading = get_string('moduleusage', 'report_moduleusage');
        parent::__construct($uniqueid);
    }
    function set_total_courses($num) {
        if (is_numeric($num)) {
            $this->total_courses = $num;
        }
    }
    function set_visible_courses($num) {
        if (is_numeric($num)) {
            $this->visible_courses = $num;
        }
    }
    function set_heading($heading) {
        $this->heading = $heading;
    }
    function col_name($row) {
        switch($row->name) {
            case 'forumnews':
            case 'forumother':
                $str = get_string($row->name, 'report_moduleusage');
                break;
            default:
                $str = get_string('pluginname', 'mod_'.$row->name);
                break;
        }
        return $str;
    }
    function col_percentage($row) {
        return round($row->visible / $this->visible_courses * 100, 1) . '%';
    }
    function col_totalpercentage($row) {
        return round($row->total / $this->total_courses * 100, 1) . '%';
    }

    function print_headers() {
        $headers = html_writer::tag('th', get_string('module', 'report_moduleusage'));
        $headers .= html_writer::tag('th', get_string('visible', 'report_moduleusage'), array('colspan'=>2));
        $headers .= html_writer::tag('th', get_string('total', 'report_moduleusage'), array('colspan'=>2));
        $row = html_writer::tag('tr', $headers);

        echo html_writer::tag('thead', $row);
    }
}
