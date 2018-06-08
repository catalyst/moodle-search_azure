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
 * and the Azure Search search engine.
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
 * Azure Search engine.
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class engine extends \core_search\engine {

    /**
     * @var int Factor to multiply fetch limit by when getting results.
     */
    protected $totalresultdocs = 0;

    /**
     * @var array Stores result from get records by area id method.
     */
    protected $areaiddocs = array();

    /**
     * @var array The payload to be sent to the Azure Search service.
     */
    protected $payload = array();

    /**
     * @var int The number of records in the payload to be sent to the Azure Search service.
     */
    public $payloadcount = 0;

    /**
     * @var int The current size of the payload object.
     */
    public $payloadsize = 0;

    /**
     * @var int Count of how many parent documents are in current payload.
     */
    protected $count = 0;

    /**
     * @var integer The maximum size of payload to send to Azure Search in bytes.
     */
    protected $sendsize = 15000000;

    /**
     * @var integer The maximum number of documents to sent to Azure Search in bytes.
     */
    protected $sendlimit = 990;

    /**
     *
     * @var array Configuration defaults.
     */
    protected $configdefaults = array(
            'searchurl' => '',
            'index' => 'mooodle',
            'apikey' => '',
            'apiversion' => '2016-09-01',
            'fileindexing' => 0,
            'tikahostname' => 'http://127.0.0.1',
            'tikaport' => 9998,
            'tikasendsize' => 512000000
    );

    /**
     * Initialises the search engine configuration.
     *
     * Search engine availability should be checked separately.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->config = (object)array_merge($this->configdefaults, (array)$this->config);
        foreach ($this->config as $name => $value) {
            set_config($name, $value, 'search_azure');
        }

    }

    /**
     * Generates the Azure Search server endpoint URL from
     * the config hostname and port.
     *
     * @param string $path The path to append to the search domain url.
     * @param string $index The name of the search index to use.
     * @return url|bool Returns url if succes or false on error.
     */
    private function get_url($path='', $index='') {
        $returnval = false;
        if (!empty($this->config->searchurl) && !empty($this->config->apiversion) && !empty($this->config->index)) {
            $url = rtrim($this->config->searchurl, "/");
            $apiversion = $this->config->apiversion;

            if ($index === '') {
                $index = $this->config->index;
            }

            $returnval = $url . '/indexes/' . $index . $path . '?api-version=' . $apiversion;
        }

        return $returnval;
    }

    /**
     * Check if index exists in Azure Search service.
     *
     * @param object $stack The Guzzle client stack to use.
     * @return bool True on success False on failure
     */
    private function check_index($stack=false) {
        $returnval = false;
        $response = 404;
        $url = $this->get_url();
        $client = new \search_azure\asrequest($stack);

        if (!empty($this->config->index) && $url) {
            $response = $client->get($url);
            $responsecode = $response->getStatusCode();

        }
        if ($responsecode == 200) {
            $returnval = true;
        }

        return $returnval;
    }

    /**
     * Get the Azure Search mapping.
     *
     * @return array $mapping  The Azure Search mapping.
     */
    private function get_mapping() {
        $requiredfields = \search_azure\document::get_required_fields_definition();
        $optionalfields = \search_azure\document::get_optional_fields_definition();
        $allfields = array_merge($requiredfields, $optionalfields);
        $fieldvalues = array_values($allfields);

        $mapping = array('name' => $this->config->index, 'fields' => $fieldvalues);

        return $mapping;
    }

    /**
     * Create index with mapping in Azure Search backend
     *
     * @param object $stack The Guzzle client stack to use.
     */
    private function create_index($stack=false) {
        $url = $this->get_url();
        $client = new \search_azure\asrequest($stack);
        if (!empty($this->config->index) && $url) {
            $mapping = $this->get_mapping();
            $response = $client->put($url, json_encode($mapping));
            $responsecode = $response->getStatusCode();
        } else {
            throw new \moodle_exception('noconfig', 'search_azure', '');
        }
        if ($responsecode !== 201) {
            throw new \moodle_exception('indexfail', 'search_azure', '');
        }

    }

    /**
     * Is the Azure Search server endpoint configured in Moodle
     * and available.
     *
     * @param object $stack The Guzzle client stack to use.
     * @return true|string Returns true if all good or an error string.
     */
    public function is_server_ready($stack=false) {
        $url = $this->get_url('', false);
        $returnval = true;
        $client = new \search_azure\asrequest($stack);

        if (!$url) {
            $returnval = get_string('noconfig', 'search_azure');
        } else {
            $response = $client->get($url);
            $responsecode = $response->getStatusCode();
        }

        if ($responsecode != 200) {
            $returnval = get_string('noserver', 'search_azure');
        }

        return $returnval;
    }

    /**
     * Called when indexing is triggered.
     * Creates the Index namespace and adds fields if they don't exist.
     *
     * @param bool $fullindex is this a full index of site.
     */
    public function index_starting($fullindex = false) {
        if ($fullindex) {
            // Check if index exists and create it if it doesn't.
            $hasindex = $this->check_index();
            if (!$hasindex) {
                $this->create_index();
            }
        }
    }

    /**
     * Get the currently indexed files for a particular document, returns the total count, and a subset of files.
     *
     * @param \core_search\document $document
     * @param int      $start The row to start the results on. Zero indexed.
     * @param int      $rows The number of rows to fetch
     * @param \GuzzleHttp\Handler\ $stack The stack to use for the HTTP query.
     * @return array   A two element array, the first is the total number of available results, the second is an array
     *                 of documents for the current request.
     */
    private function get_indexed_files($document, $start = 0, $rows = 500, $stack=false) {
        $url = $this->get_url('/docs/search');
        $client = new \search_azure\asrequest($stack);
        $query = new \search_azure\query();
        $queryobj = $query->get_files_query($document, $start, $rows);

        $jsonquery = json_encode($queryobj);
        $response = $client->post($url, $jsonquery)->getBody();
        $results = json_decode($response);

        if (!isset($results->value)) {
            $returnarray = array(0, array());
        } else {
            $count = $results->{'@odata.count'}; // Insanity to access propert staring with @.
            $returnarray = array($count, $results->value);
        }

        return $returnarray;

    }

    /**
     * Get the currently indexed files for a particular document, returns the total count, and a subset of files.
     *
     * @param int      $areaid The area id to get.
     * @param int      $start The row to start the results on. Zero indexed.
     * @param int      $rows The number of rows to fetch.
     * @param array    $returnresults the array of found results.
     * @param \GuzzleHttp\Handler\ $stack The stack to use for the HTTP query.
     * @return array   A two element array, the first is the total number of available results, the second is an array
     *                 of documents for the current request.
     */
    private function get_records_areaid($areaid, $start=0, $rows=500, $returnresults=array(), $stack=false) {
        $count = 0;

        $url = $this->get_url('/docs/search');
        $client = new \search_azure\asrequest($stack);
        $query = new \search_azure\query();
        $queryobj = $query->get_areaid_query($areaid, $start, $rows);

        $jsonquery = json_encode($queryobj);
        $response = $client->post($url, $jsonquery)->getBody();
        $results = json_decode($response);

        if (isset($results->value)) {
            $count = $results->{'@odata.count'}; // Insanity to access propert staring with @.
            $returnresults = array_merge($returnresults, $results->value);
            $start += $rows;
        }

        if ($start < $count) {
            $returnresults = $this->get_records_areaid($areaid, $start, $rows, $returnresults, $stack);
        }

        return $returnresults;
    }

    /**
     * Return a count of total records for the most recently completed
     * execute_query().
     * Must be implemented to return the number of results that available
     * for the most recent call to execute_query().
     * This is used to determine how many pages will be displayed in the paging bar.
     * For more discussion see MDL-53758.
     *
     * @return int
     */
    public function get_query_total_count() {
        return $this->totalresultdocs;
    }

    /**
     * Given an array of files,
     * remove these from the index.
     *
     * @param object $idstodelete Files to remove from index.
     */
    private function delete_indexed_files($idstodelete) {
        // Delete files that are no longer attached.
        foreach ($idstodelete as $id => $type) {
            // We directly delete the item using the client, as the engine delete_by_id won't work on file docs.
            $this->delete_by_id ($id);
        }
    }

    /**
     * Given a document, get that documents associated files
     * and update them in the index.
     *
     * @param \core_search\document $document The document whose files to index/
     * @return array files to add to index and files to delete from index.
     */
    private function filter_indexed_files($document) {
        $rows = 500; // Maximum rows to process at a time.
        $files = $document->get_files(); // Get the attached files.
        // We do this progressively, so we can handle lots of files cleanly.
        list ($numfound, $indexedfiles) = $this->get_indexed_files($document, 0, $rows);
        $count = 0;
        $idstodelete = array ();

        do {
            // Go through each indexed file. We want to not index any stored and unchanged ones, delete any missing ones.
            foreach ($indexedfiles as $indexedfile) {
                $fileid = $indexedfile->id;

                if (isset ( $files [$fileid] )) {
                    // Check for changes that would mean we need to re-index the file. If so, just leave in $files.
                    // Filelib does not guarantee time modified is updated, so we will check important values.
                    if ($indexedfile->modified != $files [$fileid]->get_timemodified ()) {
                        continue;
                    }
                    if (strcmp ( $indexedfile->title, $files [$fileid]->get_filename () ) !== 0) {
                        continue;
                    }
                    if ($indexedfile->filecontenthash != $files [$fileid]->get_contenthash ()) {
                        continue;
                    }
                    // If the file is already indexed, we can just remove it from the files array and skip it.
                    unset ( $files [$fileid] );
                } else {
                    // This means we have found a file that is no longer attached, so we need to delete from the index.
                    // We do it later, since this is progressive, and it could reorder results.
                    $idstodelete [$indexedfile->id] = $indexedfile->type;
                }
            }
            $count += $rows;

            if ($count < $numfound) {
                // If we haven't hit the total count yet, fetch the next batch.
                list ( $numfound, $indexedfiles ) = $this->get_indexed_files ( $document, $count, $rows );
            }
        } while ( $count < $numfound );

        return array($files, $idstodelete);
    }

    /**
     * Add files to the index.
     *
     * @param document $document document
     */
    private function process_document_files($document) {
        // Handle already indexed Files.
        $files = array();
        if (!$document->get_is_new()) {

            // If this isn't a new document, we need to check the exiting indexed files.
            list ($files, $idstodelete) = $this->filter_indexed_files($document);

            // Delete files that are no longer attached.
            $this->delete_indexed_files($idstodelete);

        } else {
            $files = $document->get_files();
        }

        foreach ($files as $fileid => $file) {
            $filedocdata = $document->export_file_for_engine($file);
            $this->batch_add_documents($filedocdata);

        }

        $this->batch_add_documents(false, false, true);

    }

    /**
     * Get the highlighted result sections and use them to replace the
     * source sections.
     *
     * @param object $result the original search result.
     * @return object $highlightedsource the result object with highlighting.
     */
    public function highlight_result($result) {

        if (property_exists($result, '@search.highlights')) {
            foreach ($result->{'@search.highlights'} as $highlightfield => $value) {
                $result->$highlightfield = $value[0]; // Replace _source element with highlight element.

            }
            $highlightedsource = $result;
        } else {
            $highlightedsource = $result;
        }

        return $highlightedsource;

    }

    /**
     * Loop through given iterator of search documents
     * and and have the search engine back end add them
     * to the index.
     *
     * @param iterator/searcharea/array $iterator invalid param types
     * @param searcharea $searcharea the area for the documents to index
     * @param array $options document indexing options
     * @return array Processed document counts
     */
    public function add_documents($iterator, $searcharea, $options) {
        $numrecords = 0;
        $numdocs = 0;
        $numdocsignored = 0;
        $lastindexeddoc = 0;
        $firstindexeddoc = 0;
        $partial = false;

        // First we'll process all the documents, then if we
        // are processing files we'll itterate through again and just add the files.
        foreach ($iterator as $document) {
            // Stop if we have exceeded the time limit (and there are still more items). Always
            // do at least one second's worth of documents otherwise it will never make progress.
            if ($lastindexeddoc !== $firstindexeddoc &&
                    !empty($options['stopat']) && microtime(true) >= $options['stopat']) {
                        $partial = true;
                        break;
            }

            if (!$document instanceof \core_search\document) {
                continue;
            }
            if (isset($options['lastindexedtime']) && $options['lastindexedtime'] == 0) {
                // If we have never indexed this area before, it must be new.
                $document->set_is_new(true);
            }

            $lastindexeddoc = $document->get('modified');
            $docdata = $document->export_for_engine();
            $numrecords++;
            $numdocsignored += $this->batch_add_documents($docdata, true);

            if ($options['indexfiles']) {
                $searcharea->attach_files($document);
                $this->process_document_files($document);
            }
        }

        $numdocsignored += $this->batch_add_documents(false, true, true);
        $numdocs = $numrecords - $numdocsignored;

        return array($numrecords, $numdocs, $numdocsignored, $lastindexeddoc, $partial);
    }

    /**
     * Process response.
     * If no errors were returned from bulk operation then numdocs = numrecords.
     * If there are errors no documents would have been added.
     *
     * @param \GuzzleHttp\response $response
     */
    private function process_response($response) {
        $responsebody = json_decode($response->getBody());
        $numdocsignored = 0;

        if ($response->getStatusCode() == 413) {
            // TODO: add handling to retry sending payload one record at a time.
            debugging (get_string ('addfail', 'search_azure') . ' Request Entity Too Large', DEBUG_DEVELOPER );
            $numdocsignored = $this->count;
        } else if ($response->getStatusCode() != 200) {
            debugging (get_string ('addfail', 'search_azure') .
                    ' Error Code: ' . $response->getStatusCode(), DEBUG_DEVELOPER );
            $numdocsignored = $this->count;
        }
    }

    /**
     *  Azure Search has a limit on how big the HTTP payload can be.
     *  Therefore we limit it to a size in bytes and a document count.
     *
     * @param bool $sendnow
     * @return bool
     */
    public function ready_to_send($sendnow) {
        $readytosend = false;
        if ($sendnow) {
            $readytosend = true;
        } else if ($this->payloadsize >= $this->sendsize || $this->payloadcount >= $this->sendlimit) {
            $readytosend = true;
        }

        return $readytosend;
    }

    /**
     * Add the payload object containing document information
     * in JSON format to the Azure Search index.
     *
     * @param array $docdata
     * @param bool $isdoc
     * @param bool $sendnow
     * @param \GuzzleHttp\Handler\ $stack The stack to use for the HTTP query.
     * @return number Number of documents not indexed.
     */
    private function batch_add_documents($docdata, $isdoc=false, $sendnow=false, $stack=false) {
        $numdocsignored = 0;
        $payloadsize = strlen(json_encode($docdata));

        // Sometimes a document will fail json encoding due to its content.
        // In this case we return early.
        if ($payloadsize == 0) {
            return 1;
        } else if ($docdata !== false) {
            $this->payload[] = $docdata;
            $this->payloadsize += strlen(json_encode($docdata));
            $this->payloadcount++;
        }

        // Track how many parent docs are in the request.
        if ($isdoc) {
            $this->count++;
        }

        $readytosend = $this->ready_to_send($sendnow);

        if (!$readytosend) { // If we don't have enough data to send return early.
            return $numdocsignored;
        } else if ($this->payloadsize > 0) { // Make sure we have at least some data to send.
            $url = $this->get_url('/docs/index');
            $client = new \search_azure\asrequest($stack);
            $payload = array('value' => $this->payload);
            $response = $client->post($url, json_encode($payload));

            $numdocsignored = $this->process_response($response);

            // Reset the counts.
            $this->payload = false;
            $this->payloadsize = 0;
            $this->payloadcount = 0;

            // Reset the parent doc count after attempting to add.
            if ($isdoc) {
                $this->count = 0;
            }
        }

        return $numdocsignored;
    }

    /**
     * Add a document to the index
     *
     * @param document $document
     * @param bool $fileindexing are we indexing files
     * @param object $stack The Guzzle client stack to use.
     * @return bool
     */
    public function add_document($document, $fileindexing = false, $stack=false) {
        $docdata = $document->export_for_engine();
        $record = array('value' => array($docdata));
        $url = $this->get_url('/docs/index');
        $jsondoc = json_encode($record);

        $client = new \search_azure\asrequest($stack);
        $response = $client->post($url, $jsondoc);
        $responsecode = $response->getStatusCode();

        if ($responsecode !== 201 && $responsecode !== 200) {
            debugging(get_string('addfail', 'search_azure') . $response->getBody(), DEBUG_DEVELOPER);
            return false;
        }

        if ($fileindexing) {
            // This will take care of updating all attached files in the index.
            $this->process_document_files($document);
        }
        return true;

    }

    /**
     * Filter the raw query search results.
     *
     * @param array $results The raw search results.
     * @param int $limit The limit for the results to return.
     * @return array $docs The filtered documents.
     */
    private function query_results($results, $limit) {
        $docs = array();
        $doccount = 0;

        if (isset($results->value)) {
            foreach ($results->value as $result) {
                $searcharea = $this->get_search_area($result->areaid);
                if (!$searcharea) {
                    continue;
                }
                $access = $searcharea->check_access($result->itemid);

                if ($access == \core_search\manager::ACCESS_DELETED) {
                    $this->delete_by_id($result->id);
                } else if ($access == \core_search\manager::ACCESS_GRANTED && $doccount < $limit) {

                    // Add hightlighting to document.
                    $highlightedresult = $this->highlight_result($result);

                    $docs[] = $this->to_document($searcharea, (array)$highlightedresult);
                    $doccount++;
                }
                if ($access == \core_search\manager::ACCESS_GRANTED) {
                    $this->totalresultdocs++;
                }

            }

        }

        return $docs;
    }

    /**
     * Takes the user supplied query as well as data from Moodle global
     * search core to construct the search query and execute the query
     * against the search engine.
     * Returns an array of matching result documents.
     *
     * @param array $filters
     * @param array $usercontexts
     * @param int $limit
     * @return core_search\document $docs Result documents.
     */
    public function execute_query($filters, $usercontexts, $limit = 0) {
        $url = $this->get_url('/docs/search');
        $client = new \search_azure\asrequest();

        $returnlimit = \core_search\manager::MAX_RESULTS;

        if ($limit == 0) {
            $limit = $returnlimit;
        }

        $query = new \search_azure\query();
        $asquery = $query->get_query($filters, $usercontexts);

        // Send a request to the server.
        $results = json_decode($client->post($url, json_encode($asquery))->getBody());

        // Iterate through results.
        $docs = $this->query_results($results, $limit);

        // TODO: handle negative cases and errors.
        return $docs;
    }

    /**
     * Deletes the specified document.
     *
     * @param string $id The document id to delete
     * @param \GuzzleHttp\Handler\ $stack The stack to use for the HTTP query.
     * @return bool
     */
    public function delete_by_id($id, $stack=false) {
        $url = $this->get_url('/docs/index');
        $client = new \search_azure\asrequest($stack);

        $record = new \stdClass();
        $record->{'@search.action'} = 'delete';
        $record->id = $id;

        $request = array('value' => array($record));
        $jsondoc = json_encode($request);

        $response = $client->post($url, $jsondoc);
        $responsecode = $response->getStatusCode();

        if ($responsecode !== 200) {
            debugging(get_string('deletefail', 'search_azure') . $response->getBody(), DEBUG_DEVELOPER);
            return false;
        }

        return true;
    }

    /**
     * Manage deletion of content out of Azure Search.
     * If an $areaid is not passed this will delete EVERYTHING!
     *
     * @param bool $areaid | string
     * @param \GuzzleHttp\Handler\ $stack The stack to use for the HTTP query.
     * @return bool $returnval The return status.
     */
    public function delete($areaid=false, $stack=false) {
        $url = $this->get_url();
        $client = new \search_azure\asrequest($stack);
        $returnval = false;

        if ($areaid === false) {
            // Delete all your search engine index contents.
            // Response will return acknowledged True if deletion worked,
            // or a status of not found if index doesn't exist.
            // We'll treat both cases as good.
            $response = $client->delete($url);
            $responsecode = $response->getStatusCode();

            if ($responsecode == 204 || $responsecode == 404) {
                $this->create_index($stack);
                $returnval = true;
            }
        } else {
            $results = $this->get_records_areaid($areaid);
            foreach ($results as $result) {
                $returnval = $this->delete_by_id($result->id, $stack);
            }
        }
        return $returnval;
    }

    /**
     * Returns status of if this backend supports indexing of files
     * and if that support is available and enabled.
     *
     * @return bool
     */
    public function file_indexing_enabled() {
        $returnval = false;
        $client = new \search_azure\asrequest();
        $url = '';
        // Check if we have a valid set of config.
        if (!empty($this->config->tikahostname) &&
            !empty($this->config->tikaport &&
            (bool)$this->config->fileindexing)) {
                $port = $this->config->tikaport;
                $hostname = rtrim($this->config->tikahostname, "/");
                $url = $hostname . ':'. $port;
        }

        // Check we can reach Tika server.
        if ($url !== '') {
            $response = $client->get($url);
            $responsecode = $response->getStatusCode();
            if ($responsecode == 200) {
                $returnval = true;
            }
        }

        return $returnval;
    }

    /**
     * The force merge operation allows to reduce the number of segments by merging
     * them and optimizes the index for faster search operations.
     *
     * This call will block until the merge is complete.
     * If the http connection is lost, the request will continue in the background,
     * and any new requests will block until the previous force merge is complete.
     *
     */
    public function optimize() {
        $url = $this->get_url() . '/' . $this->config->index . '/_forcemerge';
        $client = new \search_azure\asrequest();

        $client->post($url, '');
    }
}
