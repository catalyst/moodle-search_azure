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
 * Document representation.
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace search_azure;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');

/**
 * azuresearch engine.
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class document extends \core_search\document {
    /**
     * All required fields any doc should contain.
     *
     * Search engine plugins are responsible of setting their appropriate field types and map these naming to whatever format
     * they need.
     *
     * This format suits Azure Search mapping
     *
     * @var array
     */
    protected static $requiredfields = array(
        'id' => array(
            'name' => 'id',
            'type' => 'Edm.String',
            'retrievable' => true,
            'searchable' => true,
            'key' => true,
            'filterable' => false
        ),
        'parentid' => array(
            'name' => 'parentid',
            'type' => 'Edm.String',
            'retrievable' => true,
            'searchable' => false,
            'filterable' => true
        ),
        'itemid' => array(
            'name' => 'itemid',
            'type' => 'Edm.Int32',
            'retrievable' => true,
            'searchable' => false,
            'filterable' => false
        ),
        'title' => array(
            'name' => 'title',
            'type' => 'Edm.String',
            'retrievable' => true,
            'searchable' => true,
            'filterable' => false
        ),
        'content' => array(
            'name' => 'content',
            'type' => 'Edm.String',
            'retrievable' => true,
            'searchable' => true,
            'filterable' => false
        ),
        'contextid' => array(
            'name' => 'contextid',
            'type' => 'Edm.String',
            'retrievable' => true,
            'searchable' => false,
            'filterable' => true
        ),
        'areaid' => array(
            'name' => 'areaid',
            'type' => 'Edm.String',
            'retrievable' => true,
            'searchable' => false,
            'filterable' => true
        ),
        'type' => array(
            'name' => 'type',
            'type' => 'Edm.Int32',
            'retrievable' => true,
            'searchable' => false,
            'filterable' => true
        ),
        'courseid' => array(
            'name' => 'courseid',
            'type' => 'Edm.String',
            'retrievable' => true,
            'searchable' => false,
            'filterable' => true
        ),
        'owneruserid' => array(
            'name' => 'owneruserid',
            'type' => 'Edm.Int32',
            'retrievable' => true,
            'searchable' => false,
            'filterable' => false
        ),
        'modified' => array(
            'name' => 'modified',
            'type' => 'Edm.Int32',
            'retrievable' => true,
            'searchable' => false,
            'filterable' => true
        ),
    );

    /**
     * All optional fields docs can contain.
     *
     * Although it matches solr fields format, this is just to define the field types. Search
     * engine plugins are responsible of setting their appropriate field types and map these
     * naming to whatever format they need.
     *
     * @var array
     */
    protected static $optionalfields = array(
        'userid' => array(
            'name' => 'userid',
            'type' => 'Edm.Int32',
            'retrievable' => true,
            'searchable' => false,
            'filterable' => false
        ),
        'groupid' => array(
            'name' => 'groupid',
            'type' => 'Edm.Int32',
            'retrievable' => true,
            'searchable' => false,
            'filterable' => false
        ),
        'description1' => array(
            'name' => 'description1',
            'type' => 'Edm.String',
            'retrievable' => true,
            'searchable' => true,
            'filterable' => false
        ),
        'description2' => array(
            'name' => 'description2',
            'type' => 'Edm.String',
            'retrievable' => true,
            'searchable' => true,
            'filterable' => false
        ),
        'filetext' => array(
            'name' => 'filetext',
            'type' => 'Edm.String',
            'retrievable' => false,
            'searchable' => true,
            'filterable' => false
        ),
            'filecontenthash' => array(
                    'name' => 'filecontenthash',
                    'type' => 'Edm.String',
                    'retrievable' => true,
                    'searchable' => false,
                    'filterable' => true
        )
    );

    /**
     * Array of file mimetypes that contain plain text that can be fed directly
     * into Azure Search without text extraction processing.
     *
     * @var array
     */
    protected static $acceptedtext = array(
            'text/html',
            'text/plain',
            'text/csv',
            'text/css',
            'text/javascript',
            'text/ecmascript'
    );

    /**
     * @var array Plugin configuration.
     */
    protected $config = array();

    /**

     * @var integer Port Tika is listening.
     */
    protected $tikaport = 0;

    /**
     * @var string FQDN of Tika instance.
     */
    protected $tikahostname = '';

    /**
     * Constructor for document class.
     * Makes relevant config available.
     *
     * @param int $itemid An id unique to the search area
     * @param string $componentname The search area component Frankenstyle name
     * @param string $areaname The area name (the search area class name)
     * @return void
     */
    public function __construct($itemid, $componentname, $areaname) {
        parent::__construct($itemid, $componentname, $areaname);
        $this->config = get_config('search_azure');
        $this->tikaport = $this->config->tikaport;
        $this->tikahostname = rtrim($this->config->tikahostname, "/");
    }

    /**
     * Use tika to extract text from file.
     *
     * @param \stored_file $file
     * @param \search_azure\asrequest $client client
     * @return string|boolean
     */
    public function extract_text($file, $client) {
        // TODO: add timeout and retries for tika.
        $extractedtext = '';
        $port = $this->tikaport;
        $hostname = $this->tikahostname;
        $url = $hostname . ':'. $port . '/tika/form';
        $filesize = $file->get_filesize();

        if ($filesize <= $this->config->tikasendsize) {
            $response = $client->postfile($url, $file);

            if ($response->getStatusCode() == 200) {
                $extractedtext = (string) $response->getBody();
            }
        }

        return $extractedtext;

    }

    /**
     * Checks if supplied file is plain text that can be directly fed
     * to Azure Search without further processing.
     *
     * @param \stored_file $file File to check.
     * @return boolean
     */
    private function is_text($file) {
        $mimetype = $file->get_mimetype();
        $istext = false;

        if (in_array($mimetype, $this->get_accepted_text_types())) {
            $istext = true;
        }

        return $istext;
    }

    /**
     * Overwritten to use markdown format as we use markdown for solr highlighting.
     *
     * @return int
     */
    protected function get_text_format() {
        return FORMAT_HTML;
    }

    /**
     * Formats a text string coming from the search engine.
     *
     * @param  string $text Text to format
     * @return string HTML text to be renderer
     */
    protected function format_text($text) {
        // Since we allow output for highlighting, we need to encode html entities.
        // This ensures plaintext html chars don't become valid html.
        $out = s($text);

        $startcount = 0;
        $endcount = 0;

        // Remove end/start pairs that span a few common seperation characters. Allows us to highlight phrases instead of words.
        $regex = '|'.query::HIGHLIGHT_END.'([ .,-]{0,3})'.query::HIGHLIGHT_START.'|';
        $out = preg_replace($regex, '$1', $out);

        // Now replace our start and end highlight markers.
        $out = str_replace(query::HIGHLIGHT_START, '<span class="highlight">', $out, $startcount);
        $out = str_replace(query::HIGHLIGHT_END, '</span>', $out, $endcount);

        // This makes sure any highlight tags are balanced, incase truncation or the highlight text contained our markers.
        while ($startcount > $endcount) {
            $out .= '</span>';
            $endcount++;
        }
        while ($startcount < $endcount) {
            $out = '<span class="highlight">' . $out;
            $endcount++;
        }

        return parent::format_text($out);
    }

    /**
     * Apply any defaults to unset fields before export. Called after document building, but before export.
     *
     * Sub-classes of this should make sure to call parent::apply_defaults().
     */
    protected function apply_defaults() {
        parent::apply_defaults();
        // Set the default type, TYPE_TEXT.
        if (!isset($this->data['parentid'])) {
            $this->data['parentid'] = $this->data['id'];
        }
    }

    /**
     * Returns the document ready to submit to the search engine.
     *
     * @throws \coding_exception
     * @return array
     */
    public function export_for_engine() {
        $data = parent::export_for_engine();

        $data['@search.action'] = 'mergeOrUpload';

        return $data;
    }


    /**
     * Export the data for the given file in relation to this document.
     *
     * @param \stored_file $file The stored file we are talking about.
     * @return array
     */
    public function export_file_for_engine($file) {
        $data = $this->export_for_engine();
        $filetext = '';

        if ($this->is_text($file)) {
            // If file is text don't bother converting.
            $filetext = $file->get_content();
        } else {
            // Pass the file off to Tika to extract content.
            $client = new \search_azure\asrequest();
            $filetext = $this->extract_text($file, $client);
        }

        // Construct the document.
        unset($data['content']);
        unset($data['description1']);
        unset($data['description2']);

        $data['id'] = $file->get_id();
        $data['parentid'] = $this->data['id'];
        $data['type'] = \core_search\manager::TYPE_FILE;
        $data['title'] = $file->get_filename();
        $data['modified'] = $file->get_timemodified();
        $data['filetext'] = $filetext;
        $data['filecontenthash'] = $file->get_contenthash();
        $data['@search.action'] = 'mergeOrUpload';

        return $data;
    }

    /**
     * Returns all required fields definitions.
     *
     * @return array
     */
    public static function get_required_fields_definition() {
        return static::$requiredfields;
    }

    /**
     * Returns all optional fields definitions.
     *
     * @return array
     */
    public static function get_optional_fields_definition() {
        return static::$optionalfields;
    }

    /**
     * Returns all accepted text file types.
     *
     * @return array
     */
    public static function get_accepted_text_types() {
        return self::$acceptedtext;
    }
}