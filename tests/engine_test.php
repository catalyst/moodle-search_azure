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
        $this->resetAfterTest();

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
        $this->resetAfterTest();

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
        $this->resetAfterTest();

        set_config('searchurl', 'https://moodle.search.windows.fake', 'search_azure');
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
        $this->resetAfterTest();

        set_config('searchurl', 'https://moodle.search.windows.fake', 'search_azure');
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
        $this->resetAfterTest();

        set_config('index', 'moodle', 'search_azure');

        $expected = '{
            "name": "moodle",
            "fields": [
                {"name": "id", "type": "Edm.String", "retrievable":true, "searchable": true, "key":true, "filterable": false},
                {"name": "parentid", "type": "Edm.String", "retrievable":true, "searchable": false, "filterable": false},
                {"name": "itemid", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": false},
                {"name": "title", "type": "Edm.String", "retrievable":true, "searchable": true, "filterable": false},
                {"name": "content", "type": "Edm.String", "retrievable":true, "searchable": true, "filterable": false},
                {"name": "description1", "type": "Edm.String", "retrievable":true, "searchable": true, "filterable": false},
                {"name": "description2", "type": "Edm.String", "retrievable":true, "searchable": true, "filterable": false},
                {"name": "filetext", "type": "Edm.String", "retrievable":true, "searchable": true, "filterable": false},
                {"name": "contextid", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": true},
                {"name": "areaid", "type": "Edm.String", "retrievable":true, "searchable": false, "filterable": true},
                {"name": "type", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": false},
                {"name": "courseid", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": true},
                {"name": "owneruserid", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": false},
                {"name": "modified", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": true}
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
        $this->resetAfterTest();

        set_config('searchurl', 'https://moodle.search.windows.fake', 'search_azure');
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
        $this->resetAfterTest();

        set_config('searchurl', 'https://moodle.search.windows.fake', 'search_azure');
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
        $this->resetAfterTest();

        set_config('searchurl', 'https://moodle.search.windows.fake', 'search_azure');
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
}
