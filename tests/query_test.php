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
require_once($CFG->dirroot . '/search/tests/fixtures/testable_core_search.php');
require_once($CFG->dirroot . '/search/tests/fixtures/mock_search_area.php');
require_once($CFG->dirroot . '/search/engine/azure/tests/fixtures/testable_engine.php');

/**
 * Azure Search engine.
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_azure_query_testcase extends advanced_testcase {
    /**
     * @var \core_search::manager
     */
    protected $search = null;

    /**
     * @var Instace of core_search_generator.
     */
    protected $generator = null;

    /**
     * @var Instace of testable_engine.
     */
    protected $engine = null;

    public function setUp() {
        $this->resetAfterTest();
        set_config('enableglobalsearch', true);

        $this->generator = self::getDataGenerator()->get_plugin_generator('core_search');
        $this->generator->setup();

        $this->engine = new \search_azure\testable_engine();
        $this->search = testable_core_search::instance($this->engine);
        $areaid = \core_search\manager::generate_areaid('core_mocksearch', 'mock_search_area');
        $this->search->add_search_area($areaid, new core_mocksearch\search\mock_search_area());

        $this->setAdminUser();
    }

    public function tearDown() {
        // For unit tests before PHP 7, teardown is called even on skip. So only do our teardown if we did setup.
        if ($this->generator) {
            // Moodle DML freaks out if we don't teardown the temp table after each run.
            $this->generator->teardown();
            $this->generator = null;
        }
    }

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
     * Test get files query construction.
     */
    public function test_get_files_query() {
        $this->resetAfterTest();
        set_config('enableglobalsearch', true);

        $rec = new \stdClass();
        $rec->id = 'mod_assign-activity-12"';
        $rec->areaid = array('mod_assign-activity');

        $start = 0;
        $rows = 500;

        $area = new core_mocksearch\search\mock_search_area();
        $record = $this->generator->create_record($rec);
        $doc = $area->get_document($record);

        $expected = array(
                "filter" => "(type eq 2))"
                ." and (areaid eq 'core_mocksearch-mock_search_area')"
                ." and (parentid eq 'core_mocksearch-mock_search_area-1)",
                "top" => 500,
                "skip" => 0,
                "count" => true
        );

        $query = new \search_azure\query();
        $result = $query->get_files_query($doc, $start, $rows);

        // Check the results.
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($result));
    }

    /**
     * Test get records by area id query construction.
     */
    public function test_get_areaid_query() {
        $this->resetAfterTest();
        set_config('enableglobalsearch', true);

        $rec = new \stdClass();
        $rec->id = 'mod_assign-activity-12"';
        $rec->areaid = array('mod_assign-activity');

        $start = 0;
        $rows = 500;

        $area = new core_mocksearch\search\mock_search_area();
        $record = $this->generator->create_record($rec);
        $doc = $area->get_document($record);

        $expected = array(
            "filter" => "areaid eq 'core_mocksearch-mock_search_area'",
            "top" => 500,
            "skip" => 0,
            "count" => true
        );

        $query = new \search_azure\query();
        $result = $query->get_files_query($doc, $start, $rows);

        // Check the results.
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($result));
    }
}
