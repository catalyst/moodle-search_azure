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
 * azure search engine query unit tests.
 *
 * @package    search_azure
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Azure Search engine.
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_azure_query_testcase extends advanced_testcase {

    /**
     * Test basic query construction.
     */
    public function test_get_query() {

        $filters = new \stdClass();
        $filters->q = '*';
        $filters->timestart = 0;
        $filters->timeend = 0;

        $expected = '{
            "search": "*",
            "searchFields": "id, title, content, description1, description2, filetext",
            "top": 100
            }';

        $query = new \search_azure\query();
        $result = $query->get_query($filters, true);

        // Check the results.
        $this->assertJsonStringEqualsJsonString($expected, json_encode($result));
    }

    /**
     * Test context query construction.
     */
    public function test_get_query_context() {

        $filters = new \stdClass();
        $filters->q = '*';
        $filters->timestart = 0;
        $filters->timeend = 0;

        $contexts = array(1,2,3);

        $expected = array(
            "search" => "*",
            "searchFields" => "id, title, content, description1, description2, filetext",
            "filter" => "(search.in(contextid, '1,2,3'))",
            "top"=> 100
        );

        $query = new \search_azure\query();
        $result = $query->get_query($filters, $contexts);

        // Check the results.
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($result));
    }

    /**
     * Test title query construction.
     */
    public function test_get_query_title() {

        $filters = new \stdClass();
        $filters->q = '*';
        $filters->title = 'forum';
        $filters->timestart = 0;
        $filters->timeend = 0;

        $contexts = array(1,2,3);

        $expected = array(
                "search" => "*",
                "searchFields" => "id, title, content, description1, description2, filetext",
                "filter" => "(search.in(contextid, '1,2,3')) and (search.ismatch('forum', 'title'))",
                "top"=> 100
        );

        $query = new \search_azure\query();
        $result = $query->get_query($filters, $contexts);

        // Check the results.
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($result));
    }

    /**
     * Test areaid query construction.
     */
    public function test_get_query_areaid() {

        $filters = new \stdClass();
        $filters->q = '*';
        $filters->title = 'forum';
        $filters->areaids = array('mod_assign-activity', 'mod_forum-activity');
        $filters->timestart = 0;
        $filters->timeend = 0;

        $contexts = array(1,2,3);

        $expected = array(
                "search" => "*",
                "searchFields" => "id, title, content, description1, description2, filetext",
                "filter" => "(search.in(contextid, '1,2,3')) and (search.ismatch('forum', 'title'))"
                            ." and (search.in(areaid, 'mod_assign-activity,mod_forum-activity'))",
                "top"=> 100
        );

        $query = new \search_azure\query();
        $result = $query->get_query($filters, $contexts);

        // Check the results.
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($result));
    }

    /**
     * Test courseid query construction.
     */
    public function test_get_query_courseid() {

        $filters = new \stdClass();
        $filters->q = '*';
        $filters->title = 'forum';
        $filters->areaids = array('mod_assign-activity', 'mod_forum-activity');
        $filters->courseids = array(1,2,3,4);
        $filters->timestart = 0;
        $filters->timeend = 0;

        $contexts = array(1,2,3);

        $expected = array(
                "search" => "*",
                "searchFields" => "id, title, content, description1, description2, filetext",
                "filter" => "(search.in(contextid, '1,2,3')) and (search.ismatch('forum', 'title'))"
                ." and (search.in(areaid, 'mod_assign-activity,mod_forum-activity'))"
                ." and (search.in(courseid, '1,2,3,4'))",
                "top"=> 100
        );

        $query = new \search_azure\query();
        $result = $query->get_query($filters, $contexts);

        // Check the results.
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($result));
    }

    /**
     * Test times query construction.
     */
    public function test_get_query_times() {

        $filters = new \stdClass();
        $filters->q = '*';
        $filters->title = 'forum';
        $filters->areaids = array('mod_assign-activity', 'mod_forum-activity');
        $filters->courseids = array(1,2,3,4);
        $filters->timestart = 1504505792;
        $filters->timeend = 1504505795;

        $contexts = array(1,2,3);

        $expected = array(
                "search" => "*",
                "searchFields" => "id, title, content, description1, description2, filetext",
                "filter" => "(search.in(contextid, '1,2,3')) and (search.ismatch('forum', 'title'))"
                ." and (search.in(areaid, 'mod_assign-activity,mod_forum-activity'))"
                ." and (search.in(courseid, '1,2,3,4'))"
                ." and (modified ge 1504505792 and modified lt 1504505795)",
                "top"=> 100
        );

        $query = new \search_azure\query();
        $result = $query->get_query($filters, $contexts);

        // Check the results.
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($result));
    }

    /**
     * Test times query construction.
     */
    public function test_get_files_query() {

        $document = new \stdClass();
        $document->id = 'mod_assign-activity-12"';
        $document->areaid = array('mod_assign-activity');

        $start = 0;
        $rows = 500;

        $expected = array(
                "search" => "*",
                "searchFields" => "id, title, content, description1, description2, filetext",
                "filter" => "(search.in(contextid, '1,2,3')) and (search.ismatch('forum', 'title'))"
                ." and (search.in(areaid, 'mod_assign-activity,mod_forum-activity'))"
                ." and (search.in(courseid, '1,2,3,4'))"
                ." and (modified ge 1504505792 and modified lt 1504505795)",
                "top"=> 100
        );

        $query = new \search_azure\query();
        $result = $query->get_file_query($document, $start, $rows);

        // Check the results.
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($result));
    }
}
