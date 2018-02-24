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
 * azure search engine unit tests.
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
use \GuzzleHttp\Middleware;
use \GuzzleHttp\Psr7\Response;
use \GuzzleHttp\Psr7\Request;

/**
 * Tests for asrequest class
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_azure_asrequest_testcase extends advanced_testcase {

    /**
     * Test asrequest get functionality
     */
    public function test_get() {
        $this->resetAfterTest();
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');

        $container = [];
        $history = Middleware::history($container);

        // Create a mock and queue two responses.
        $mock = new MockHandler([
                new Response(200, ['Content-Type' => 'text/plain'])
        ]);

        $stack = HandlerStack::create($mock);
        // Add the history middleware to the handler stack.
        $stack->push($history);

        $url = 'http://localhost:8080/foo?bar=blerg';
        $client = new \search_azure\asrequest($stack);
        $response = $client->get($url);
        $request = $container[0]['request'];

        // Check the results.
        $this->assertEquals($request->getUri()->getScheme(), 'http');
        $this->assertEquals($request->getUri()->getHost(),  'localhost');
        $this->assertEquals($request->getUri()->getPort(),  '8080');
        $this->assertEquals($request->getUri()->getPath(), '/foo');
        $this->assertEquals($request->getUri()->getQuery(), 'bar=blerg');

    }

    /**
     * Test asrequest put functionality
     */
    public function test_put() {
        $this->resetAfterTest();
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');

        $container = [];
        $history = Middleware::history($container);

        // Create a mock and queue two responses.
        $mock = new MockHandler([
                new Response(200, ['Content-Type' => 'text/plain'])
        ]);

        $stack = HandlerStack::create($mock);
        // Add the history middleware to the handler stack.
        $stack->push($history);

        $url = 'http://localhost:8080/foo?bar=blerg';
        $params = '{"properties":"value"}';
        $client = new \search_azure\asrequest($stack);
        $response = $client->put($url, $params);
        $request = $container[0]['request'];
        $contentheader = $request->getHeader('content-type');

        // Check the results.
        $this->assertEquals($request->getUri()->getScheme(), 'http');
        $this->assertEquals($request->getUri()->getHost(),  'localhost');
        $this->assertEquals($request->getUri()->getPort(),  '8080');
        $this->assertEquals($request->getUri()->getPath(), '/foo');
        $this->assertEquals($request->getUri()->getQuery(), 'bar=blerg');
        $this->assertTrue($request->hasHeader('content-type'));
        $this->assertEquals($contentheader, array('application/json'));

    }

    /**
     * Test asrequest post functionality
     */
    public function test_post() {
        $this->resetAfterTest();
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');

        $container = [];
        $history = Middleware::history($container);

        // Create a mock and queue two responses.
        $mock = new MockHandler([
                new Response(200, ['Content-Type' => 'text/plain'])
        ]);

        $stack = HandlerStack::create($mock);
        // Add the history middleware to the handler stack.
        $stack->push($history);

        $url = 'http://localhost:8080/foo?bar=blerg';
        $params = '{"properties":"value"}';
        $client = new \search_azure\asrequest($stack);
        $response = $client->post($url, $params);
        $request = $container[0]['request'];
        $contentheader = $request->getHeader('content-type');

        // Check the results.
        $this->assertEquals($request->getUri()->getScheme(), 'http');
        $this->assertEquals($request->getUri()->getHost(),  'localhost');
        $this->assertEquals($request->getUri()->getPort(),  '8080');
        $this->assertEquals($request->getUri()->getPath(), '/foo');
        $this->assertEquals($request->getUri()->getQuery(), 'bar=blerg');
        $this->assertTrue($request->hasHeader('content-type'));
        $this->assertEquals($contentheader, array('application/json'));

    }

    /**
     * Test asrequest delete functionality
     */
    public function test_delete() {
        $this->resetAfterTest();
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');

        $container = [];
        $history = Middleware::history($container);

        // Create a mock and queue two responses.
        $mock = new MockHandler([
                new Response(200, ['Content-Type' => 'text/plain'])
        ]);

        $stack = HandlerStack::create($mock);
        // Add the history middleware to the handler stack.
        $stack->push($history);

        $url = 'http://localhost:8080/foo?bar=blerg';
        $client = new \search_azure\asrequest($stack);
        $response = $client->delete($url);
        $request = $container[0]['request'];

        // Check the results.
        $this->assertEquals($request->getUri()->getScheme(), 'http');
        $this->assertEquals($request->getUri()->getHost(),  'localhost');
        $this->assertEquals($request->getUri()->getPort(),  '8080');
        $this->assertEquals($request->getUri()->getPath(), '/foo');
        $this->assertEquals($request->getUri()->getQuery(), 'bar=blerg');

    }

    /**
     * Test that Guzzle proxy array is correctly constructed
     * from Moodle Proxy settings.
     */
    public function test_proxy_construct() {
        $this->resetAfterTest();
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('proxyhost', 'localhost');
        set_config('proxyport', 3128);
        set_config('proxybypass', 'localhost, 127.0.0.1');

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\search_azure\asrequest', 'proxyconstruct');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \search_azure\asrequest); // Get result of invoked method.

        $expected = ['proxy' => ['http'  => 'tcp://localhost:3128',
                                 'https'  => 'tcp://localhost:3128',
                                 'no' => ['localhost', '127.0.0.1']]];

        $this->assertEquals($proxy, $expected, $canonicalize = true);
    }

    /**
     * Test that Guzzle proxy array is correctly constructed
     * from Moodle Proxy settings.
     * With proxy authentication.
     */
    public function test_proxy_construct_auth() {
        $this->resetAfterTest();
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('proxyhost', 'localhost');
        set_config('proxyport', 3128);
        set_config('proxybypass', 'localhost, 127.0.0.1');
        set_config('proxyuser', 'user1');
        set_config('proxypassword', 'password');

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\search_azure\asrequest', 'proxyconstruct');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \search_azure\asrequest); // Get result of invoked method.

        $expected = ['proxy' => ['http'  => 'tcp://user1:password@localhost:3128',
                                 'https'  => 'tcp://user1:password@localhost:3128',
                                 'no' => ['localhost', '127.0.0.1']]];

        $this->assertEquals($proxy, $expected, $canonicalize = true);
    }

    /**
     * Test that Guzzle proxy array is correctly constructed
     * from Moodle Proxy settings.
     * With proxy authentication and no proxy bypass.
     */
    public function test_proxy_construct_no_bypass() {
        $this->resetAfterTest();
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('proxyhost', 'localhost');
        set_config('proxyport', 3128);
        set_config('proxybypass', '');
        set_config('proxyuser', 'user1');
        set_config('proxypassword', 'password');

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\search_azure\asrequest', 'proxyconstruct');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \search_azure\asrequest); // Get result of invoked method.

        $expected = ['proxy' => ['http'  => 'tcp://user1:password@localhost:3128',
                                 'https'  => 'tcp://user1:password@localhost:3128']];

        $this->assertEquals($proxy, $expected, $canonicalize = true);
    }

    /**
     * Test that Guzzle proxy array is correctly constructed
     * from Moodle Proxy settings.
     * Using socks as the protocol.
     */
    public function test_proxy_construct_socks() {
        $this->resetAfterTest();
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('proxyhost', 'localhost');
        set_config('proxyport', 3128);
        set_config('proxybypass', 'localhost, 127.0.0.1');
        set_config('proxytype', 'SOCKS5');

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\search_azure\asrequest', 'proxyconstruct');
        $method->setAccessible(true); // Allow accessing of private method.
        $proxy = $method->invoke(new \search_azure\asrequest); // Get result of invoked method.

        $expected = ['proxy' => ['http'  => 'socks5://localhost:3128',
                                 'https'  => 'socks5://localhost:3128',
                                 'no' => ['localhost', '127.0.0.1']]];

        $this->assertEquals($proxy, $expected, $canonicalize = true);
    }

    /**
     * Test asrequest get with proxy functionality
     */
    public function test_proxy_get() {
        $this->resetAfterTest();
        set_config('apikey', 'DEADBEEF01234567890', 'search_azure');
        set_config('proxyhost', 'localhost');
        set_config('proxyport', 3128);
        set_config('proxybypass', 'localhost, 127.0.0.1');

        $container = [];
        $history = Middleware::history($container);

        // Create a mock and queue two responses.
        $mock = new MockHandler([
                new Response(200, ['Content-Type' => 'text/plain'])
        ]);

        $stack = HandlerStack::create($mock);
        // Add the history middleware to the handler stack.
        $stack->push($history);

        $url = 'http://localhost:8080/foo?bar=blerg';
        $client = new \search_azure\asrequest($stack);
        $response = $client->get($url);
        $request = $container[0]['request'];
        $hostheader = $request->getHeader('Host');

        $proxy = $container[0]['options']['proxy'];
        $expected = ['http'  => 'tcp://localhost:3128',
                     'https'  => 'tcp://localhost:3128',
                     'no' => ['localhost', '127.0.0.1']];

        // Check the results.
        $this->assertEquals($request->getUri()->getScheme(), 'http');
        $this->assertEquals($request->getUri()->getHost(),  'localhost');
        $this->assertEquals($request->getUri()->getPort(),  '8080');
        $this->assertEquals($request->getUri()->getPath(), '/foo');
        $this->assertEquals($request->getUri()->getQuery(), 'bar=blerg');
        $this->assertEquals($proxy, $expected, $canonicalize = true);

    }
}
