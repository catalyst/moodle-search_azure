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
require_once($CFG->dirroot . '/search/tests/fixtures/mock_search_area.php');

use \GuzzleHttp\Handler\MockHandler;
use \GuzzleHttp\HandlerStack;
use \GuzzleHttp\Middleware;
use \GuzzleHttp\Psr7\Response;
use \GuzzleHttp\Psr7\Request;

/**
 * Azure Search engine.
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_azure_document_testcase extends advanced_testcase {
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

        $this->generator = self::getDataGenerator()->get_plugin_generator('core_search');
        $this->generator->setup();
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
     * Test text file extraction
     */
    public function test_export_text_file_for_engine() {
        global $CFG;

        set_config('tikahostname', 'http://127.0.0.1', 'search_azure');
        set_config('tikaport', 9998, 'search_azure');

        // Create file to analyze.
        $fs = get_file_storage();
        $filerecord = array(
                'contextid' => 1,
                'component' => 'mod_test',
                'filearea' => 'search',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'testfile.txt');
        $content = 'All the news that\'s fit to print';
        $file = $fs->create_file_from_string($filerecord, $content);

        // Construct the search object.
        $rec = new \stdClass();
        $rec->content = "azure";
        $area = new core_mocksearch\search\mock_search_area();
        $record = $this->generator->create_record($rec);
        $info = unserialize($record->info);

        // Mock out the client and response.
        // Add missing data to stub record object.
        $builder = $this->getMockBuilder('\search_azure\document');
        $builder->setMethods(array('extract_text'));
        $builder->setConstructorArgs(array('1', 'core_mocksearch', 'mock_search_area'));
        $stub = $builder->getMock();

        $stub->set('title', $info->title);
        $stub->set('content', $info->content);
        $stub->set('description1', $info->description1);
        $stub->set('description1', $info->description2);
        $stub->set('contextid', $info->contextid);
        $stub->set('courseid', $info->courseid);
        $stub->set('userid', $info->userid);
        $stub->set('owneruserid', $info->owneruserid);
        $stub->set('modified', $record->timemodified);

        $filearray = $stub->export_file_for_engine($file);
        $this->assertEquals($content, $filearray['filetext']);
    }

    /**
     * Test binary text file extraction
     */
    public function test_export_binary_text_file_for_engine() {
        global $CFG;

        set_config('tikahostname', 'http://127.0.0.1', 'search_azure');
        set_config('tikaport', 9998, 'search_azure');

        // Create file to analyze.
        $fs = get_file_storage();
        $filerecord = array(
                'contextid' => 1,
                'component' => 'mod_test',
                'filearea' => 'search',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'testfile.pdf');
        $content = 'All the news that\'s fit to print';
        $fileurl = $CFG->dirroot . '/search/engine/azure/tests/fixtures/test.pdf';
        $file = $fs->create_file_from_pathname($filerecord, $fileurl);

        // Construct the search object.
        $rec = new \stdClass();
        $rec->content = "azure";
        $area = new core_mocksearch\search\mock_search_area();
        $record = $this->generator->create_record($rec);
        $info = unserialize($record->info);

        // Mock out the client and response.
        // Add missing data to stub record object.
        $builder = $this->getMockBuilder('\search_azure\document');
        $builder->setMethods(array('extract_text'));
        $builder->setConstructorArgs(array('1', 'core_mocksearch', 'mock_search_area'));
        $stub = $builder->getMock();

        $stub->set('title', $info->title);
        $stub->set('content', $info->content);
        $stub->set('description1', $info->description1);
        $stub->set('description1', $info->description2);
        $stub->set('contextid', $info->contextid);
        $stub->set('courseid', $info->courseid);
        $stub->set('userid', $info->userid);
        $stub->set('owneruserid', $info->owneruserid);
        $stub->set('modified', $record->timemodified);

        $stub->method('extract_text')->willReturn($content);

        $filearray = $stub->export_file_for_engine($file);
        $this->assertEquals($content, $filearray['filetext']);
    }

    /**
     * Test binary text file extraction request
     */
    public function test_export_text_tika_init() {
        global $CFG;
        set_config('tikahostname', 'http://127.0.0.1', 'search_azure');
        set_config('tikaport', 9998, 'search_azure');
        set_config('tikasendsize', 512000000, 'search_azure');

        // Create file to analyze.
        $fs = get_file_storage();
        $filerecord = array(
                'contextid' => 1,
                'component' => 'mod_test',
                'filearea' => 'search',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'testfile.pdf');
        $content = 'All the news that\'s fit to print';
        $fileurl = $CFG->dirroot . '/search/engine/azure/tests/fixtures/test.pdf';
        $file = $fs->create_file_from_pathname($filerecord, $fileurl);

        // Construct the search object.
        $rec = new \stdClass();
        $rec->content = "azure";
        $area = new core_mocksearch\search\mock_search_area();
        $record = $this->generator->create_record($rec);
        $info = unserialize($record->info);

        // Mock out the client and response.
        // Add missing data to stub record object.
        $builder = $this->getMockBuilder('\search_azure\document');
        $builder->setMethods(array('__construct'));
        $builder->setConstructorArgs(array('1', 'core_mocksearch', 'mock_search_area'));
        $stub = $builder->getMock();

        $stub->set('title', $info->title);
        $stub->set('content', $info->content);
        $stub->set('description1', $info->description1);
        $stub->set('description1', $info->description2);
        $stub->set('contextid', $info->contextid);
        $stub->set('courseid', $info->courseid);
        $stub->set('userid', $info->userid);
        $stub->set('owneruserid', $info->owneruserid);
        $stub->set('modified', $record->timemodified);

        // Create as request client with mocked stack.
        $container = [];
        $history = Middleware::history($container);

        // Create a mock and queue two responses.
        $mock = new MockHandler([
                new Response(200, ['Content-Type' => 'text/plain'], $content)
        ]);

        $stack = HandlerStack::create($mock);
        // Add the history middleware to the handler stack.
        $stack->push($history);

        $esclient = new \search_azure\asrequest($stack);

        $result = $stub->extract_text($file, $esclient);
        $this->assertEquals($content, $result);
    }

    /**
     * Test binary text file extraction request
     */
    public function test_export_for_engine() {
        global $CFG;
        set_config('tikahostname', 'http://127.0.0.1', 'search_azure');
        set_config('tikaport', 9998, 'search_azure');

        // Construct the search object.
        $rec = new \stdClass();
        $rec->content = "azure";
        $area = new core_mocksearch\search\mock_search_area();
        $record = $this->generator->create_record($rec);
        $info = unserialize($record->info);

        $document = new \search_azure\document('1', 'core_mocksearch', 'mock_search_area');
        $document->set('title', $info->title);
        $document->set('content', $info->content);
        $document->set('description1', $info->description1);
        $document->set('description1', $info->description2);
        $document->set('contextid', $info->contextid);
        $document->set('courseid', $info->courseid);
        $document->set('userid', $info->userid);
        $document->set('owneruserid', $info->owneruserid);
        $document->set('modified', $record->timemodified);

        $result = $document->export_for_engine();
        $this->assertEquals('mergeOrUpload', $result['@search.action']);
    }
}
