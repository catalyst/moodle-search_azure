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
    private $searchfields = 'id, title, title, content, description1, description2, filetext';

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
        $commaseparated = implode(",", $usercontexts);
        $filter = "(search.in(contextid, '". $commaseparated ."'))";

        return $filter;
    }

    /**
     * Takes the form submission filter data and given a key value
     * constructs a single match component for the search query.
     *
     * @param string $title
     * @return array
     */
    private function construct_title($title, $isand) {
        //and search.ismatch('\''Forum'\'', '\''title'\'')"
        $titleobj = array('multi_match' => array('query' => $title,
                                                 'fields' => array('title'),
                                                 'type' => "phrase_prefix")
        );

        return $titleobj;
    }

    /**
     * Takes the form submission filter data and given a key value
     * constructs an array of match components for the search query.
     *
     * @param array $filters
     * @param string $key
     * @param string $match
     * @return array
     */
    private function construct_filter($filters, $key, $match, $isand) {
        $arrayobj = array('terms' => array($match => array()));
        $values = $filters->$key;

        foreach ($values as $value) {
            array_push ($arrayobj['terms'][$match], $value);
        }

        return $arrayobj;
    }

    /**
     * Takes the form submission filter data and
     * constructs the time range components for the search query.
     *
     * @param array $filters
     * @return array
     */
    private function construct_time_range($filters) {
        $contextobj = array('range' => array('modified' => array()));

        if (isset($filters->timestart) && $filters->timestart != 0) {
            $contextobj['range']['modified']['gte'] = $filters->timestart;
        }
        if (isset($filters->timeend) && $filters->timeend != 0) {
            $contextobj['range']['modified']['lte'] = $filters->timeend;
        }

        return $contextobj;
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
            $query['query']['bool']['must'][] = $title;
            $isand = true;
        }
        if (isset($filters->areaids) && $filters->areaids != null && !empty($filters->areaids)) {
            $areaids = $this->construct_filter($filters, 'areaids', 'areaid', $isand);
            array_push ($query['query']['bool']['filter']['bool']['must'], $areaids);
            $isand = true;
        }
        if (isset($filters->courseids) && $filters->courseids != null && !empty($filters->courseids)) {
            $courseids = $this->construct_filter($filters, 'courseids', 'courseid', $isand);
            array_push ($query['query']['bool']['filter']['bool']['must'], $courseids);
            $isand = true;
        }
        if ($filters->timestart != 0  || $filters->timeend != 0) {
            $timerange = $this->construct_time_range($filters, $isand);
            array_push ($query['query']['bool']['filter']['bool']['must'], $timerange);
            $isand = true;
        }

        return $query;
    }
}