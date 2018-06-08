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
 * Azure Search engine unit tests.
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

use \GuzzleHttp\Handler\MockHandler;
use \GuzzleHttp\HandlerStack;
use \GuzzleHttp\Psr7\Response;
use \GuzzleHttp\Middleware;

/**
 * Azure Search engine.
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_azure_engine_testcase extends advanced_testcase {
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

        // Allow setting of test server info via Env Var or define
        // to cater for mulitiple test setups.
        $searchurl = getenv('TEST_SEARCH_AZURE_URL');
        $apikey = getenv('TEST_SEARCH_AZUREC_APIKEY');
        $index = getenv('TEST_SEARCH_AZURE_INDEX');

        if (!$searchurl && defined('TEST_SEARCH_AZURE_SEARCHURL')) {
            $searchurl = TEST_SEARCH_AZURE_SEARCHURL;
        }
        if (!$apikey &&defined('TEST_SEARCH_AZURE_APIKEY')) {
            $apikey = TEST_SEARCH_AZURE_APIKEY;
        }
        if (!$index && defined('TEST_SEARCH_AZURE_INDEX')) {
            $index = TEST_SEARCH_AZURE_INDEX;
        }

        if (!$searchurl || !$apikey || !$index) {
            $this->skiptest = true;
        } else {
            $this->skiptest = false;
        }

        set_config('searchurl', $searchurl, 'search_elastic');
        set_config('apikey', $apikey, 'search_elastic');
        set_config('index', $index, 'search_elastic');

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
     * Simple data provider to allow tests to be run with file indexing on and off.
     */
    public function file_indexing_provider() {
        return array(
                'file-indexing-off' => array(0)
        );
    }

    /**
     * Test basic URL construction.
     */
    public function test_get_url() {
        set_config('searchurl', 'https://moodle.search.windows.net', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        $expected = 'https://moodle.search.windows.net/indexes/moodle?api-version=2016-09-01';

        // Reflection magic as we are directly testing a private method.
        $method = new ReflectionMethod('\search_azure\engine', 'get_url');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \search_azure\engine);

        // Check the results.
        $this->assertEquals($expected, $proxy);
    }

    /**
     * Test path URL construction.
     */
    public function test_get_url_path() {
        set_config('searchurl', 'https://moodle.search.windows.net', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        $expected = 'https://moodle.search.windows.net/indexes/moodle/docs/index?api-version=2016-09-01';

        // Reflection magic as we are directly testing a private method.
        $method = new ReflectionMethod('\search_azure\engine', 'get_url');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \search_azure\engine, '/docs/index');

        // Check the results.
        $this->assertEquals($expected, $proxy);
    }

    /**
     * Test check if index exists.
     */
    public function test_check_index() {
        set_config('searchurl', 'https://moodle.search.windows.test', 'search_azure');
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        // Create a mock stack and queue a response.
        $container = [];
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'])
        ]);

        $stack = HandlerStack::create($mock);

        // Reflection magic as we are directly testing a private method.
        $method = new ReflectionMethod('\search_azure\engine', 'check_index');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \search_azure\engine, $stack);

        // Check the results.
        $this->assertEquals(true, $proxy);
    }

    /**
     * Test check if index doesn't exist.
     */
    public function test_check_index_false() {
        set_config('searchurl', 'https://moodle.search.windows.test', 'search_azure');
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        // Create a mock stack and queue a response.
        $container = [];
        $mock = new MockHandler([
            new Response(404, ['Content-Type' => 'application/json'])
        ]);

        $stack = HandlerStack::create($mock);

        // Reflection magic as we are directly testing a private method.
        $method = new ReflectionMethod('\search_azure\engine', 'check_index');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \search_azure\engine, $stack);

        // Check the results.
        $this->assertEquals(false, $proxy);
    }

    /**
     * Test mapping construction.
     */
    public function test_get_mapping() {
        set_config('index', 'moodle', 'search_azure');

        $expected = '{
            "name": "moodle",
            "fields": [
                {"name": "id", "type": "Edm.String", "retrievable":true, "searchable": true, "key":true, "filterable": false},
                {"name": "parentid", "type": "Edm.String", "retrievable":true, "searchable": false, "filterable": true},
                {"name": "itemid", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": false},
                {"name": "title", "type": "Edm.String", "retrievable":true, "searchable": true, "filterable": false},
                {"name": "content", "type": "Edm.String", "retrievable":true, "searchable": true, "filterable": false},
                {"name": "contextid", "type": "Edm.String", "retrievable":true, "searchable": false, "filterable": true},
                {"name": "areaid", "type": "Edm.String", "retrievable":true, "searchable": false, "filterable": true},
                {"name": "type", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": true},
                {"name": "courseid", "type": "Edm.String", "retrievable":true, "searchable": false, "filterable": true},
                {"name": "owneruserid", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": false},
                {"name": "modified", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": true},
                {"name": "userid", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": false},
                {"name": "groupid", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": false},
                {"name": "description1", "type": "Edm.String", "retrievable":true, "searchable": true, "filterable": false},
                {"name": "description2", "type": "Edm.String", "retrievable":true, "searchable": true, "filterable": false},
                {"name": "filetext", "type": "Edm.String", "retrievable":false, "searchable": true, "filterable": false},
                {"name": "filecontenthash", "type": "Edm.String", "retrievable":true, "searchable": false, "filterable": true}

            ]
        }';

        // Reflection magic as we are directly testing a private method.
        $method = new ReflectionMethod('\search_azure\engine', 'get_mapping');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = json_encode($method->invoke(new \search_azure\engine));

        // Check the results.
        $this->assertJsonStringEqualsJsonString($expected, $proxy);
    }

    /**
     * Test Azure Search index creation.
     */
    public function test_create_index() {
        set_config('searchurl', 'https://moodle.search.windows.test', 'search_azure');
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        // Create a mock stack and queue a response.
        $container = [];
        $mock = new MockHandler([
            new Response(201, ['Content-Type' => 'application/json'])
        ]);

        $stack = HandlerStack::create($mock);

        // Reflection magic as we are directly testing a private method.
        $method = new ReflectionMethod('\search_azure\engine', 'create_index');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \search_azure\engine, $stack);

        // Check the results.
        $this->assertTrue(true);
    }

    /**
     * Test check if Azure search server is ready.
     */
    public function test_is_server_ready() {
        set_config('searchurl', 'https://moodle.search.windows.test', 'search_azure');
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        // Create a mock stack and queue a response.
        $container = [];
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'])
        ]);

        $stack = HandlerStack::create($mock);

        // Reflection magic as we are directly testing a private method.
        $method = new ReflectionMethod('\search_azure\engine', 'is_server_ready');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \search_azure\engine, $stack);

        // Check the results.
        $this->assertEquals(true, $proxy);
    }

    /**
     * Test check if Azure search server is ready.
     */
    public function test_is_server_ready_false() {
        set_config('searchurl', 'https://moodle.search.windows.test', 'search_azure');
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        // Create a mock stack and queue a response.
        $container = [];
        $mock = new MockHandler([
            new Response(404, ['Content-Type' => 'application/json'])
        ]);

        $stack = HandlerStack::create($mock);

        // Reflection magic as we are directly testing a private method.
        $method = new ReflectionMethod('\search_azure\engine', 'is_server_ready');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \search_azure\engine, $stack);

        $expected = 'Azure Search endpoint unreachable';

        // Check the results.
        $this->assertEquals($expected, $proxy);
    }

    /**
     * Test the add document method makes correctly formed request.
     */
    public function test_add_document() {
        global $CFG;
        set_config('searchurl', 'https://moodle.search.windows.test', 'search_azure');
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        // Create a mock stack and queue a response.
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'])
        ]);

        $stack = HandlerStack::create($mock);
        // Add the history middleware to the handler stack.
        $stack->push($history);

        // Construct the search object and add it to the engine.
        $rec = new \stdClass();
        $rec->content = "Test content to add to engine";
        $rec->timemodified = '1519536013';
        $rec->contextid = '2';
        $area = new core_mocksearch\search\mock_search_area();
        $record = $this->generator->create_record($rec);
        $doc = $area->get_document($record);

        $engine = new \search_azure\engine();
        $result = $engine->add_document($doc, false, $stack);
        $request = $container[0]['request'];
        $requestcontents = $request->getBody()->getContents();

        if ($CFG->version > 2016120509.00) {
            $expect = '{"value": [
                        {"areaid":"core_mocksearch-mock_search_area",
                        "id":"core_mocksearch-mock_search_area-1",
                        "itemid":1,
                        "title":"A basic title",
                        "content":"Test content to add to engine",
                        "description1":"Description 1.",
                        "description2":"Description 2.",
                        "contextid":"2",
                        "courseid":"1",
                        "userid":"2",
                        "owneruserid":"0",
                        "modified":"1519536013",
                        "type":1,
                        "parentid":"core_mocksearch-mock_search_area-1",
                        "@search.action":"mergeOrUpload"
                        }]}';
        } else {
            $expect = '{"value": [
                        {"areaid":"core_mocksearch-mock_search_area",
                        "id":"core_mocksearch-mock_search_area-1",
                        "itemid":1,
                        "title":"A basic title",
                        "content":"Test content to add to engine",
                        "description1":"Description 2.",
                        "contextid":"2",
                        "courseid":"1",
                        "userid":"2",
                        "owneruserid":"0",
                        "modified":"1519536013",
                        "type":1,
                        "parentid":"core_mocksearch-mock_search_area-1",
                        "@search.action":"mergeOrUpload"
                        }]}';
        }

        // Check the results.
        $this->assertJsonStringEqualsJsonString($expect, $requestcontents);

    }

    /**
     * Test check if document payload is ready to send.
     */
    public function test_ready_to_send() {
        set_config('searchurl', 'https://moodle.search.windows.test', 'search_azure');
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        $engine = new \search_azure\engine();
        $result = $engine->ready_to_send(false);

        // Check the results.
        $this->assertEquals(false, $result);
    }

    /**
     * Test check if document payload is ready to send.
     */
    public function test_ready_to_send_sendnow() {
        set_config('searchurl', 'https://moodle.search.windows.test', 'search_azure');
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        $engine = new \search_azure\engine();
        $result = $engine->ready_to_send(true);

        // Check the results.
        $this->assertEquals(true, $result);
    }

    /**
     * Test check if document payload is ready to send.
     */
    public function test_ready_to_send_payloadsize() {
        set_config('searchurl', 'https://moodle.search.windows.test', 'search_azure');
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        $engine = new \search_azure\engine();
        $engine->payloadsize = 16000000;
        $result = $engine->ready_to_send(false);

        // Check the results.
        $this->assertEquals(true, $result);
    }

    /**
     * Test check if document payload is ready to send.
     */
    public function test_ready_to_send_payloadcount() {
        $this->resetAfterTest();

        set_config('searchurl', 'https://moodle.search.windows.test', 'search_azure');
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        $engine = new \search_azure\engine();
        $engine->payloadcount = 1000;
        $result = $engine->ready_to_send(false);

        // Check the results.
        $this->assertEquals(true, $result);
    }

    /**
     * Test the add document method makes correctly formed request.
     */
    public function test_get_records_areaid() {
        set_config('searchurl', 'https://moodle.search.windows.test', 'search_azure');
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        $areaid = 'mod_resource-activity';
        $body1 = '{
            "@odata.count": 3,
            "value": [{
                "@search.score": 1.7477992,
                "id": "core_course-mycourse-5",
                "parentid": "core_course-mycourse-5",
                "itemid": 6,
                "title": "search test",
                "content": "search course summary description description",
                "contextid": "210",
                "areaid": "core_course-mycourse",
                "type": 1,
                "courseid": "6",
                "owneruserid": 0,
                "modified": 1499398979,
                "userid": null,
                "groupid": null,
                "description1": "search test",
                "description2": null,
                "filecontenthash": null
            }]
        }';

        $body2 = '{
            "@odata.count": 3,
            "value": [{
                "@search.score": 1.7477992,
                "id": "core_course-mycourse-6",
                "parentid": "core_course-mycourse-6",
                "itemid": 6,
                "title": "search test",
                "content": "search course summary description description",
                "contextid": "210",
                "areaid": "core_course-mycourse",
                "type": 1,
                "courseid": "6",
                "owneruserid": 0,
                "modified": 1499398979,
                "userid": null,
                "groupid": null,
                "description1": "search test",
                "description2": null,
                "filecontenthash": null
            }]
        }';

        $body3 = '{
            "@odata.count": 3,
            "value": [{
                "@search.score": 1.7477992,
                "id": "core_course-mycourse-7",
                "parentid": "core_course-mycourse-7",
                "itemid": 6,
                "title": "search test",
                "content": "search course summary description description",
                "contextid": "210",
                "areaid": "core_course-mycourse",
                "type": 1,
                "courseid": "6",
                "owneruserid": 0,
                "modified": 1499398979,
                "userid": null,
                "groupid": null,
                "description1": "search test",
                "description2": null,
                "filecontenthash": null
            }]
        }';

        // Create a mock stack and queue a response.
        $container = [];
        $history = Middleware::history($container);
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $body1),
            new Response(200, ['Content-Type' => 'application/json'], $body2),
            new Response(200, ['Content-Type' => 'application/json'], $body3)
        ]);

        $stack = HandlerStack::create($mock);
        // Add the history middleware to the handler stack.
        $stack->push($history);

        // Reflection magic as we are directly testing a private method.
        $method = new ReflectionMethod('\search_azure\engine', 'get_records_areaid');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \search_azure\engine, $areaid, 0, 1, array(), $stack);
        $request = $container[0]['request'];
        $requestcontents = $request->getBody()->getContents();

        // Check the results.
        $this->assertEquals('core_course-mycourse-5', $proxy[0]->id);
        $this->assertEquals('core_course-mycourse-6', $proxy[1]->id);
        $this->assertEquals('core_course-mycourse-7', $proxy[2]->id);
    }


    /**
     * Test Azure Search index deletion.
     */
    public function test_delete() {
        set_config('searchurl', 'https://moodle.search.windows.test', 'search_azure');
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        // Create a mock stack and queue a response.
        $mock = new MockHandler([
            new Response(204),
            new Response(201)
        ]);

        $stack = HandlerStack::create($mock);
        $engine = new \search_azure\engine();
        $result = $engine->delete(false, $stack);

        // Check the results.
        $this->assertTrue(true);
    }

    /**
     * Test Azure Search index deletion.
     */
    public function test_delete_no_index() {
        set_config('searchurl', 'https://moodle.search.windows.test', 'search_azure');
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('apiversion', '2016-09-01', 'search_azure');
        set_config('index', 'moodle', 'search_azure');

        // Create a mock stack and queue a response.
        $mock = new MockHandler([
            new Response(404),
            new Response(201)
        ]);

        $stack = HandlerStack::create($mock);
        $engine = new \search_azure\engine();
        $result = $engine->delete(false, $stack);

        // Check the results.
        $this->assertTrue(true);
    }


    /**
     * Test basic search using real endpoint.
     */
    public function test_basic_search() {
        if ($this->skiptest) {
            $this->markTestSkipped('Azure Search service not set.');
        }

        // Construct the search object and add it to the engine.
        $rec = new \stdClass();
        $rec->content = "Azure";
        $area = $this->area;
        $record = $this->generator->create_record($rec);
        $doc = $area->get_document($record);
        $this->engine->add_document($doc);

        // We need to wait for Azure search to update its index
        // this happens in near realtime, not immediately.
        sleep(1);

        // This is a mock of the search form submission.
        $querydata = new stdClass();
        $querydata->q = 'Azure';
        $querydata->timestart = 0;
        $querydata->timeend = 0;

        // Execute the search.
        $results = $this->search->search($querydata);

        // Check the results.
        $this->assertEquals($results[0]->get('content'), $querydata->q);
    }
}
