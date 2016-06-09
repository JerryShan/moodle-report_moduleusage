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
 * @package    report_moduleusage
 * @copyright  2016 Paul Nicholls
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function report_moduleusage_output_table($category) {
    global $DB, $PAGE;

    $config = get_config('report_moduleusage');

    $catwhere = "(cat.id = ? OR " . $DB->sql_like('cat.path', '?') . ")";

    $enrolsql = '';
    $enrolparams = array();
    if (!empty($config->enrolmethods)) {
        list($enrolsql, $enrolparams) = $DB->get_in_or_equal(explode(',', $config->enrolmethods));
        $enrolsql = 'AND e.enrol ' . $enrolsql;
    }

    if (!empty($category)) {
        // Category-level report.
        $cat = $DB->get_record('course_categories', array('id' => $category), '*', MUST_EXIST);
        $path = $cat->path;
        $heading = $cat->name;
        $linkheading = true;
        $params = array($category, "$path/%");
        
        $sql = 'SELECT COUNT(*) FROM {course} c INNER JOIN {course_categories} cat ON c.category=cat.id WHERE ' . $catwhere;
        $allcourses = $DB->count_records_sql($sql, $params);

        $params = array_merge($enrolparams, $params);
        $visiblecourses = $DB->count_records_sql("SELECT COUNT(DISTINCT c.id)
                FROM ({user_enrolments} ue INNER JOIN {enrol} e ON ue.enrolid=e.id $enrolsql)
                    INNER JOIN {course} c ON c.id=e.courseid
                    INNER JOIN {course_categories} cat ON c.category = cat.id
                WHERE c.visible=1 AND cat.visible=1 AND $catwhere", $params);
    } else {
        // Site-level report.
        $path = '/';
        $heading = get_string('sitewideusage', 'report_moduleusage');
        $linkheading = false;

        $allcourses = $DB->count_records('course');
        $visiblecourses = $DB->count_records_sql("SELECT COUNT(DISTINCT c.id)
                FROM ({user_enrolments} ue INNER JOIN {enrol} e ON ue.enrolid=e.id $enrolsql)
                    INNER JOIN {course} c ON c.id=e.courseid
                    INNER JOIN {course_categories} cat ON c.category = cat.id
                WHERE c.visible=1 AND cat.visible=1", $enrolparams);
    }

    list($main, $mainparams) = report_moduleusage_build_query($category, $path, false, false);
    list($forums, $forumparams) = report_moduleusage_build_query($category, $path, true, false);
    list($news, $newsparams) = report_moduleusage_build_query($category, $path, true, true);

    $fields = '*';
    $from = "(($main) UNION ($forums) UNION ($news)) combined"; // 'combined' is the derived table alias.
    $tblwhere = '1=1';
    $sqlparams = array_merge($mainparams, $forumparams, $newsparams);

    echo html_writer::start_tag('div', array('class' => 'report_moduleusage_category'));
    if ($linkheading) {
        $url = new moodle_url('/report/moduleusage/index.php', array('category'=>$category));
        $heading = html_writer::link($url, $heading);
    }
    echo html_writer::tag('h3', $heading);

    $counts = new \stdClass();
    $counts->visible = $visiblecourses;
    $counts->total = $allcourses;
    echo html_writer::span(get_string('coursesvisible', 'report_moduleusage', $counts), 'moduleusage_course_count');

    $table = new \report_moduleusage\usage_table('report_moduleusage_'.$category);
    $table->define_baseurl($PAGE->url);
    $table->set_sql($fields, $from, $tblwhere, $sqlparams);
    $table->set_count_sql('SELECT COUNT(*) FROM {modules}');
    $table->define_columns(array('name', 'visible', 'percentage', 'total', 'totalpercentage'));
    $table->define_headers(array('Module', 'Visible', '%', 'Total', 'Total %'));
    $table->set_total_courses($allcourses);
    $table->set_visible_courses($visiblecourses);
    $table->sortable(false, 'name');

    $table->out(100, false); // 100 per page, no initials bar.
    echo html_writer::end_tag('div');
}

/**
 * Return the SQL and parameters for a query to extract module usage information.
 *
 * @param int $category Category ID.
 * @param string $path Category path.
 * @param bool $forums True if we're interested in forums, false for all other modules.
 * @param bool $news True if we're interested in news forums, false for all other types.
 * @return string
 */
function report_moduleusage_build_query($category, $path, $forums=false, $news=false) {
    global $DB;
    $config = get_config('report_moduleusage');

    $params = array();
    $catwhere = '';
    $pathsql = '';

    $enrolsql = '';
    if (!empty($config->enrolmethods)) {
        list($enrolsql, $params) = $DB->get_in_or_equal(explode(',', $config->enrolmethods));
        $enrolsql = 'AND e.enrol ' . $enrolsql;
    }

    $name = "mods.name";
    $forumsql = '';
    $iforumsql = '';
    $forumwhere = "WHERE mods.visible = 1 AND mods.name <> 'forum'";
    $modjoin = "{modules} mods LEFT JOIN";
    $modjoinon = "ON mods.id=counts.id";
    if ($forums) {
        $modjoin = '';
        $modjoinon = '';
        $forumwhere = '';
        $forumsql = " INNER JOIN {forum} f ON m.name='forum' AND cm.instance = f.id AND f.type ";
        $iforumsql = " INNER JOIN {modules} im ON icm.module=im.id INNER JOIN {forum} ifrm ON im.name='forum' AND icm.instance = ifrm.id AND ifrm.type ";
        if ($news) {
            $name = "'forumnews'";
            $forumsql .= " = ";
            $iforumsql .= " = ";
        } else {
            $name = "'forumother'";
            $forumsql .= " <> ";
            $iforumsql .= " <> ";
        }
        $forumsql .= "'news'";
        $iforumsql .= "'news'";
        $forumsql .= " INNER JOIN {forum_discussions} fd ON f.id=fd.forum";
        $iforumsql .= " INNER JOIN {forum_discussions} ifd ON ifrm.id=ifd.forum";
    }
    $ipathlike = $DB->sql_like('icat.path', '?');
    $pathlike = $DB->sql_like('cat.path', '?');

    $totalsql = "SELECT module AS id, COUNT(DISTINCT icm.course) AS total FROM {course_modules} icm $iforumsql GROUP BY module";
    if (!empty($category)) {
        $pathsql = "(icat.id = ? OR " . $DB->sql_like('icat.path', '?') . ")";
        $totalsql = "SELECT icm.module AS id, COUNT(DISTINCT icm.course) AS total
                FROM {course_modules} icm
                    INNER JOIN {course} ic ON ic.id=icm.course
                    INNER JOIN {course_categories} icat ON ic.category=icat.id
                    $iforumsql
                WHERE $pathsql
                GROUP BY icm.module";
        $catwhere = "(cat.id = ? OR " . $DB->sql_like('cat.path', '?') . ") AND ";
        $params = array_merge($params, array($category, "$path/%", $category, "$path/%"));
    }
    $sql = "SELECT $name AS name, COALESCE(counts.visible, 0) AS visible, COALESCE(counts.total, 0) AS total
            FROM $modjoin (
                SELECT m.id, m.name, COUNT(DISTINCT cm.course) AS visible, t.total
                FROM {modules} m INNER JOIN (({course_modules} cm INNER JOIN (
                        SELECT c.id, c.visible, c.category 
                        FROM ({user_enrolments} ue INNER JOIN {enrol} e ON ue.enrolid=e.id $enrolsql) INNER JOIN {course} c ON c.id=e.courseid
                        GROUP BY c.id
                        HAVING COUNT(e.id) > 0) ce 
                    ON cm.course=ce.id)
                    INNER JOIN {course_categories} cat ON ce.category = cat.id) ON m.id=cm.module
                    INNER JOIN ($totalsql) t ON m.id=t.id
                    $forumsql
                WHERE $catwhere cm.visible=1 AND cat.visible=1 AND ce.visible=1
                GROUP BY m.id) counts 
            $modjoinon
            $forumwhere";

    return array($sql, $params);
}