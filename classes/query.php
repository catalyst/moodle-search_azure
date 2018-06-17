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
 * Azure Search engine.
 *
 * Provides an interface between Moodles Global search functionality
 * and the Azure Search search engine service.
 *
 * Azure Search presents a REST Webservice API that we communicate with
 * via Curl.
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace search_azure;

defined('MOODLE_INTERNAL') || die();

/**
 * azuresearch engine.
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class query  {

    /**
     * @var number of records to return to Global Search.
     */
    private $limit = 0;

    /**
     * @var query string to pass to azure search.
     */
    private $query = array();

    /**
     * Fields that the 'q' string is matched against in a search. Azure expects a comma seperated string
     *
     * @var string search fields
     */
    private $searchfields = 'id, title, content, description1, description2, filetext';

    /**
     * Marker for the start of a highlight.
     */
    const HIGHLIGHT_START = '@@HI_S@@';

    /**
     * Marker for the end of a highlight.
     */
    const HIGHLIGHT_END = '@@HI_E@@';

    /**
     * @var array Fields that can be highlighted.
     */
    protected $highlightfields = array('title', 'content', 'description1', 'description2');

    /**
     * construct basic query structure
     */
    public function __construct() {

        $returnlimit = \core_search\manager::MAX_RESULTS;

        // Basic array to build query from.
        $this->query = array(
                'search' => '',
                'searchFields' => $this->searchfields,
                'top' => $returnlimit
        );
    }

    /**
     * Takes supplied user contexts from Moodle search core
     * and constructs the corresponding part of the
     * search query.
     *
     * @param array $usercontexts
     * @return string
     */
    private function construct_contexts($usercontexts) {
        $contexts = array();
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($usercontexts));

        foreach ($iterator as $key => $value) {
            array_push ($contexts, $value);
        }

        $contexts = array_values(array_unique ($contexts));
        $commaseparated = implode(",", $contexts);
        $filter = "(search.in(contextid, '". $commaseparated ."'))";

        return $filter;
    }

    /**
     * Takes the form submission filter data and given a key value
     * constructs a single match component for the search query.
     *
     * @param string $title
     * @param bool $isand If true add and condition to string.
     * @return string $filter The filter string to apply to search.
     */
    private function construct_title($title, $isand) {
        if ($isand) {
            $filter = ' and';
        } else {
            $filter = '';
        }
        $filter .= " (search.ismatch('". $title ."', 'title'))";

        return $filter;
    }

    /**
     * Takes the form submission filter data and given a key value
     * constructs an array of match components for the search query.
     *
     * @param array $filters The filters to apply
     * @param string $key
     * @param string $match
     * @param bool $isand If true add and condition to string.
     * @return string $filter The filter string to apply to search.
     */
    private function construct_filter($filters, $key, $match, $isand) {
        if ($isand) {
            $filter = ' and';
        } else {
            $filter = '';
        }

        $commaseparated = implode(",", $filters->$key);
        $filter .= " (search.in(". $match .", '". $commaseparated ."'))";

        return $filter;
    }

    /**
     * Takes the form submission filter data and
     * constructs the time range components for the search query.
     *
     * @param array $filters The filters to apply
     * @param bool $isand If true add and condition to string.
     * @return string $filter The filter string to apply to search.
     */
    private function construct_time_range($filters, $isand) {
        $ge = false;

        if ($isand) {
            $filter = ' and (';
        } else {
            $filter = ' (';
        }

        if (isset($filters->timestart) && $filters->timestart != 0) {
            $filter .= 'modified ge ' . $filters->timestart;
            $ge = true;

        }
        if (isset($filters->timeend) && $filters->timeend != 0) {
            if ($ge) {
                $filter .= ' and ';
            }
            $filter .= 'modified lt ' . $filters->timeend;
        }

        $filter .= ')';

        return $filter;
    }

    /**
     * Add highlighting elements to query array.
     *
     * @param array $query query array.
     * @return array $query updated query array with highlighting elements.
     */
    public function set_highlighting($query) {
        $hightlighting = array(
            'highlightPreTag' => self::HIGHLIGHT_START,
            'highlightPostTag' => self::HIGHLIGHT_END,
            'highlight' => implode($this->highlightfields, '-10,') . '-10'
            );

        $query = array_merge($query, $hightlighting);

        return $query;
    }

    /**
     * Construct the azuresearch query
     *
     * @param array $filters
     * @param array|int $usercontexts
     * @return \search_azure\query
     */
    public function get_query($filters, $usercontexts) {
        $query = $this->query;
        $isand = false;

        // Add query text.
        $query['search'] = $filters->q;

        // Add contexts.
        if (gettype($usercontexts) == 'array') {
            $contexts = $this->construct_contexts($usercontexts);
            $query['filter'] = $contexts;
            $isand = true;
        }

        // Add filters.
        if (isset($filters->title) && $filters->title != null) {
            $title = $this->construct_title($filters->title, $isand);
            if (isset($query['filter'])) {
                $query['filter'] = $query['filter'] . $title;
            } else {
                $query['filter'] = $title;
            }
            $isand = true;
        }
        if (isset($filters->areaids) && $filters->areaids != null && !empty($filters->areaids)) {
            $areaids = $this->construct_filter($filters, 'areaids', 'areaid', $isand);
            if (isset($query['filter'])) {
                $query['filter'] = $query['filter'] . $areaids;
            } else {
                $query['filter'] = $areaids;
            }
            $isand = true;
        }
        if (isset($filters->courseids) && $filters->courseids != null && !empty($filters->courseids)) {
            $courseids = $this->construct_filter($filters, 'courseids', 'courseid', $isand);
            if (isset($query['filter'])) {
                $query['filter'] = $query['filter'] . $courseids;
            } else {
                $query['filter'] = $courseids;
            }
            $isand = true;
        }
        if (isset($filters->userids) && $filters->userids != null && !empty($filters->userids)) {
            $userids = $this->construct_filter($filters, 'userids', 'userid', $isand);
            if (isset($query['filter'])) {
                $query['filter'] = $query['filter'] . $userids;
            } else {
                $query['filter'] = $userids;
            }
            $isand = true;
        }
        if (isset($filters->groupids) && $filters->groupids != null && !empty($filters->groupids)) {
            $groupids = $this->construct_filter($filters, 'groupids', 'groupid', $isand);
            if (isset($query['filter'])) {
                $query['filter'] = $query['filter'] . $groupids;
            } else {
                $query['filter'] = $groupids;
            }
            $isand = true;
        }
        if ($filters->timestart != 0  || $filters->timeend != 0) {
            $timerange = $this->construct_time_range($filters, $isand);
            if (isset($query['filter'])) {
                $query['filter'] = $query['filter'] . $timerange;
            } else {
                $query['filter'] = $timerange;
            }
        }

        // Add highlighting.
        $query = $this->set_highlighting($query);

        // Add date based sorting.
        if (!empty($filters->order) && ($filters->order === 'asc' || $filters->order === 'desc')) {
            $query['orderby'] = 'modified ' . $filters->order;
        }

        return $query;
    }

    /**
     * Construct the Azure Search query to get files
     *
     * @param \core_search\document $document A search document.
     * @param int $start The result stating position.
     * @param int $rows The number of rows to return.
     * @return array $query The search query.
     */
    public function get_files_query($document, $start, $rows) {
        $filterstring = "(type eq 2)"
                        ." and (areaid eq '". $document->get('areaid') ."')"
                        ." and (parentid eq '". $document->get('id') ."')";

        $query = array();
        $query['top'] = $rows;
        $query['skip'] = $start;
        $query['filter'] = $filterstring;
        $query['count'] = true;

        return $query;
    }

    /**
     * Construct the Azure Search query to get files
     *
     * @param int $areaid The search area id.
     * @param int $start The result stating position.
     * @param int $rows The number of rows to return.
     * @return array $query The search query.
     */
    public function get_areaid_query($areaid, $start, $rows) {
        $filterstring = "areaid eq '". $areaid ."'";

        $query = array();
        $query['top'] = $rows;
        $query['skip'] = $start;
        $query['filter'] = $filterstring;
        $query['count'] = true;

        return $query;
    }
}