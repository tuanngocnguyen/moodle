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
 * Utility class for browsing of curse category files.
 *
 * @package    core_files
 * @copyright  2008 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Represents a course category context in the tree navigated by {@link file_browser}.
 *
 * @package    core_files
 * @copyright  2008 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file_info_context_coursecat extends file_info {
    /** @var stdClass Category object */
    protected $category;

    /**
     * Constructor
     *
     * @param file_browser $browser file browser instance
     * @param stdClass $context context object
     * @param stdClass $category category object
     */
    public function __construct($browser, $context, $category) {
        parent::__construct($browser, $context);
        $this->category = $category;
    }

    /**
     * Return information about this specific context level
     *
     * @param string $component component
     * @param string $filearea file area
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return fileinfo|null
     */
    public function get_file_info($component, $filearea, $itemid, $filepath, $filename) {
        global $DB;

        if (!core_course_category::can_view_category($this->category)) {
            if (empty($component)) {
                // we can not list the category contents, so try parent, or top system
                if ($this->category->parent and $pc = $DB->get_record('course_categories', array('id'=>$this->category->parent))) {
                    $parent = context_coursecat::instance($pc->id, IGNORE_MISSING);
                    return $this->browser->get_file_info($parent);
                } else {
                    return $this->browser->get_file_info();
                }
            }
            return null;
        }

        if (empty($component)) {
            return $this;
        }

        $methodname = "get_area_{$component}_{$filearea}";
        if (method_exists($this, $methodname)) {
            return $this->$methodname($itemid, $filepath, $filename);
        }

        return null;
    }

    /**
     * Return a file from course category description area
     *
     * @param int $itemid item ID
     * @param string $filepath file path
     * @param string $filename file name
     * @return fileinfo|null
     */
    protected function get_area_coursecat_description($itemid, $filepath, $filename) {
        global $CFG;

        if (!$this->category->id) {
            // No coursecat description area for "system".
            return null;
        }
        if (!core_course_category::can_view_category($this->category)) {
            return null;
        }
        if (!has_capability('moodle/category:manage', $this->context)) {
            return null;
        }

        if (is_null($itemid)) {
            return $this;
        }

        $fs = get_file_storage();

        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;
        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file($this->context->id, 'coursecat', 'description', 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($this->context->id, 'coursecat', 'description', 0);
            } else {
                // not found
                return null;
            }
        }

        return new file_info_stored($this->browser, $this->context, $storedfile, $urlbase, get_string('areacategoryintro', 'repository'), false, true, true, false);
    }

    /**
     * Returns localised visible name.
     *
     * @return string
     */
    public function get_visible_name() {
        return format_string($this->category->name, true, array('context'=>$this->context));
    }

    /**
     * Whether or not new files or directories can be added
     *
     * @return bool
     */
    public function is_writable() {
        return false;
    }

    /**
     * Whether or not this is a directory
     *
     * @return bool
     */
    public function is_directory() {
        return true;
    }

    /**
     * Tells if file info can be paging.
     *
     * @return int
     */
    public function supported_paging() {
        return true;
    }

    /**
     * Returns list of children file info that match the extensions.
     * Support paging.
     *
     * @param string|array $extensions file extensions
     * @param int $page current page
     * @param int $perpage number of child item per page
     * @return array
     */
    public function get_non_empty_children_paging($extensions = '*', $page = 0, $perpage = 0) {
        $list = $this->get_children($extensions, $page, $perpage);
        $nonemptylist = array();
        foreach ($list as $fileinfo) {
            if ($fileinfo->is_directory()) {
                if ($fileinfo->count_non_empty_children($extensions)) {
                    $nonemptylist[] = $fileinfo;
                }
            } else if ($extensions === '*') {
                $nonemptylist[] = $fileinfo;
            } else {
                $filename = $fileinfo->get_visible_name();
                $extension = core_text::strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (!empty($extension) && in_array('.' . $extension, $extensions)) {
                    $nonemptylist[] = $fileinfo;
                }
            }
        }
        return $nonemptylist;
    }

    /**
     * Returns list of children.
     *
     * @param string $extensions extension to filter child course file info
     * @param int $page current page
     * @param int $perpage child file info per page
     * @return array of file_info instances
     */
    public function get_children($extensions = null, $page = 0, $perpage = 0) {
        $children = array();
        list($coursecats, $hiddencats) = $this->get_categories();
        // Load all these items at page 0.
        if ($page == 0) {
            if ($child = $this->get_area_coursecat_description(0, '/', '.')) {
                $children[] = $child;
            }

            foreach ($coursecats as $category) {
                $context = context_coursecat::instance($category->id);
                $children[] = new self($this->browser, $context, $category);
            }
        }

        // Only do filtering if there is extension to check.
        if (!empty($extensions)) {
            $courses = $this->get_courses($hiddencats);
            $courses = $this->get_filtered_courses($courses, $extensions, $page, $perpage);
        } else {
            $courses = $this->get_courses($hiddencats, $page, $perpage);
        }

        foreach ($courses as $course) {
            $children[] = $this->get_child_course($course);
        }

        return array_filter($children);
    }

    /**
     * List of courses in this category and in hidden subcategories
     *
     * @param array $hiddencats list of categories that are hidden from current user and returned by {@link get_categories()}
     * @param int $page current page
     * @param int $perpage number of items per page
     * @return array list of courses
     */
    protected function get_courses($hiddencats, $page = 0, $perpage = 0) {
        global $DB, $CFG;
        require_once($CFG->libdir.'/modinfolib.php');

        $params = array('category' => $this->category->id, 'contextlevel' => CONTEXT_COURSE);
        $sql = 'c.category = :category';

        foreach ($hiddencats as $category) {
            $catcontext = context_coursecat::instance($category->id);
            $sql .= ' OR ' . $DB->sql_like('ctx.path', ':path' . $category->id);
            $params['path' . $category->id] = $catcontext->path . '/%';
        }

        // Let's retrieve only minimum number of fields from course table -
        // what is needed to check access or call get_fast_modinfo().
        $coursefields = array_merge(['id', 'visible'], course_modinfo::$cachedfields);
        $fields = 'c.' . join(',c.', $coursefields) . ', ' .
            context_helper::get_preload_record_columns_sql('ctx');
        $startoffset = $perpage * $page;
        return $DB->get_records_sql('SELECT ' . $fields . ' FROM {course} c
                JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                WHERE ('.$sql.') ORDER BY c.sortorder', $params, $startoffset, $perpage);
    }

    /**
     * Finds accessible and non-accessible direct subcategories
     *
     * @return array [$coursecats, $hiddencats] - child categories that are visible to the current user and not visible
     */
    protected function get_categories() {
        global $DB;
        $fields = 'c.*, ' . context_helper::get_preload_record_columns_sql('ctx');
        $coursecats = $DB->get_records_sql('SELECT ' . $fields . ' FROM {course_categories} c
                LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel)
                WHERE c.parent = :parent ORDER BY c.sortorder',
            array('parent' => $this->category->id, 'contextlevel' => CONTEXT_COURSECAT));

        $hiddencats = [];

        foreach ($coursecats as $id => &$category) {
            context_helper::preload_from_record($category);
            if (!core_course_category::can_view_category($category)) {
                $hiddencats[$id] = $coursecats[$id];
                unset($coursecats[$id]);
            }
        }
        return [$coursecats, $hiddencats];
    }

    /**
     * Returns the file info element for a given course or null if course is not accessible
     *
     * @param stdClass $course may contain context fields for preloading
     * @return file_info_context_course|null
     */
    protected function get_child_course($course) {
        context_helper::preload_from_record($course);
        $context = context_course::instance($course->id);
        $child = new file_info_context_course($this->browser, $context, $course);
        return $child->get_file_info(null, null, null, null, null);
    }

    /**
     * Returns the number of children which are either files matching the specified extensions
     * or folders containing at least one such file.
     *
     * @param string|array $extensions, for example '*' or array('.gif','.jpg')
     * @param int $limit stop counting after at least $limit non-empty children are found
     * @return int
     */
    public function count_non_empty_children($extensions = '*', $limit = 1) {
        $cnt = 0;
        if ($child = $this->get_area_coursecat_description(0, '/', '.')) {
            $cnt += $child->count_non_empty_children($extensions) ? 1 : 0;
            if ($cnt >= $limit) {
                return $cnt;
            }
        }

        list($coursecats, $hiddencats) = $this->get_categories();
        foreach ($coursecats as $category) {
            $context = context_coursecat::instance($category->id);
            $child = new file_info_context_coursecat($this->browser, $context, $category);
            $cnt += $child->count_non_empty_children($extensions) ? 1 : 0;
            if ($cnt >= $limit) {
                return $cnt;
            }
        }

        $courses = $this->get_courses($hiddencats);
        // Filter courses by file extension.
        $courses = $this->get_filtered_courses($courses, $extensions, 0, $limit);
        $cnt = count($courses);

        return $cnt;
    }

    /**
     * Returns parent file_info instance
     *
     * @return file_info|null fileinfo instance or null for root directory
     */
    public function get_parent() {
        $parent = $this->context->get_parent_context();
        return $this->browser->get_file_info($parent);
    }

    /**
     * Filter children courses base on file extension
     * Check if any of the courses in the category has files that match the extensions.
     *
     * @param array $courses courses to filter
     * @param array $extensions filter by extension
     * @param int $page current page
     * @param int $perpage number of items per page
     * @return array
     */
    public function get_filtered_courses($courses, $extensions = [], $page = 0, $perpage = 1) {
        global $DB, $SITE;

        // Check if courses are accessible.
        $accessiblecourses = [];
        $enrolledcourses = enrol_get_my_courses(['id']);
        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            // Cannot view hidden course.
            if (!$course->visible and !has_capability('moodle/course:viewhiddencourses', $context)) {
                continue;
            }
            // It is not a enrolled course.
            if (!is_viewing($context) and !array_key_exists($course->id, $enrolledcourses)) {
                continue;
            }
            $accessiblecourses[$course->id] = $course;
        }

        if (empty($accessiblecourses)) {
            return $accessiblecourses;
        }

        // Check course files.
        $sql1 = "SELECT DISTINCT ctx.instanceid AS courseid
                   FROM {files} f
             INNER JOIN {context} ctx
                     ON ctx.id = f.contextid
                  WHERE f.filename <> :emptyfilename1
                    AND ctx.contextlevel = :contextlevel1";
        $params1 = [
            'emptyfilename1' => '.',
            'contextlevel1' => CONTEXT_COURSE,
        ];
        list($incourses1, $incourseparams1) = $DB->get_in_or_equal(array_keys($accessiblecourses), SQL_PARAMS_NAMED);
        $insql1 = " AND ctx.instanceid $incourses1 ";
        list($likesql1, $likeparams1) = parent::build_search_files_sql($extensions, null, 'fn1');
        $coursefilesql = $sql1 . $insql1 . $likesql1;

        // Check module files.
        $sql2 = "SELECT DISTINCT cm.course AS courseid
                   FROM {files} f
             INNER JOIN {context} ctx ON ctx.id = f.contextid
             INNER JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  WHERE f.filename <> :emptyfilename2
                    AND ctx.contextlevel = :contextlevel2";
        $params2 = [
            'emptyfilename2' => '.',
            'contextlevel2' => CONTEXT_MODULE,
        ];
        list($incourses2, $incourseparams2) = $DB->get_in_or_equal(array_keys($accessiblecourses), SQL_PARAMS_NAMED);
        $insql2 = " AND cm.course $incourses2";
        list($likesql2, $likeparams2) = parent::build_search_files_sql($extensions, null, 'fn2');
        $modulefilesql = $sql2 . $insql2 . $likesql2;

        // Get courses with file matching the extension.
        $filtersql = "SELECT DISTINCT filtercourse.courseid
                        FROM (($coursefilesql) UNION ($modulefilesql))
                          AS filtercourse
                    ORDER BY courseid";
        $startoffset = $perpage * $page;
        $filteredcourses = $DB->get_records_sql($filtersql,
            array_merge($params1, $params2, $likeparams1, $likeparams2, $incourseparams1, $incourseparams2),
            $startoffset, $perpage);
        // Return array of course objects.
        return array_intersect_key($accessiblecourses, $filteredcourses);
    }
}
