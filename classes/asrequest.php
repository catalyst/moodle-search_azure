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
 * Provides request signing
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace search_azure;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/search/engine/azure/guzzle/autoloader.php');

/**
 * Class creates the API calls to Azure Search.
 *
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class asrequest {
    /**
     * @var bool True if we should sign requests, false if not.
     */
    private $signing = false;

    /**
     * @var azure search plugin config.
     */
    private $config = null;

    /**
     * Initialises the search engine configuration.
     *
     * Search engine availability should be checked separately.
     *
     * @param \GuzzleHttp\HandlerStack $handler Optional custom Guzzle handler stack
     * @return void
     */
    public function __construct($handler = false) {
        $this->config = get_config('search_azure');

        // Allow the caller to instansite the Guzzle client
        // with a custom handler.
        if ($handler) {
            $this->client = new \GuzzleHttp\Client(['handler' => $handler]);
        } else {
            $this->client = new \GuzzleHttp\Client();
        }
    }

    /**
     * Constructs the Guzzle Proxy settings array
     * based on Moodle's server proxy admin settings.
     *
     * @return array $proxy Proxy settings for Guzzle to use.
     */
    private function proxyconstruct() {
        global $CFG;
        $proxy = array();
        $options = array();
        $protocol = 'tcp';
        $auth = '';
        $server = '';
        $uri = '';

        if (! empty ( $CFG->proxyhost )) {
            // Set the server details.
            if (empty ( $CFG->proxyport )) {
                $server = $CFG->proxyhost;
            } else {
                $server = $CFG->proxyhost . ':' . $CFG->proxyport;
            }

            // Set the authentication details.
            if (! empty ( $CFG->proxyuser ) and ! empty ( $CFG->proxypassword )) {
                $auth = $CFG->proxyuser . ':' . $CFG->proxypassword . '@';
            }

            // Set the proxy type.
            if (! empty ( $CFG->proxytype ) && $CFG->proxytype == 'SOCKS5') {
                $protocol = 'socks5';
            }

            // Construct proxy URI.
            $uri = $protocol . '://' . $auth . $server;

            // Populate proxy options array.
            $options['http'] = $uri;
            $options['https'] = $uri;

            // Set excluded domains.
            if (! empty ($CFG->proxybypass) ) {
                $nospace = preg_replace('/\s/', '', $CFG->proxybypass);
                $options['no'] = explode(',', $nospace);
            }

            // Finally populate proxy settings array.
            $proxy['proxy'] = $options;

        }

        return $proxy;
    }

    /**
     * Process GET requests to azuresearch.
     *
     * @param string $url
     * @return \GuzzleHttp\Psr7\Response
     */
    public function get($url) {
        $psr7request = new \GuzzleHttp\Psr7\Request('GET', $url);
        $proxy = $this->proxyconstruct();

        // Requests that receive a 4xx or 5xx response will throw a
        // Guzzle\Http\Exception\BadResponseException. We want to
        // handle this in a sane way and provide the caller with
        // a useful response. So we catch the error and return the
        // resposne.
        try {
            $response = $this->client->send($psr7request, $proxy);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;

    }

    /**
     * Process PUT requests to azuresearch.
     *
     * @param string $url
     * @param array $params
     * @return \GuzzleHttp\Psr7\Response
     */
    public function put($url, $params=null) {
        $headers = ['content-type' => 'application/x-www-form-urlencoded'];
        $psr7request = new \GuzzleHttp\Psr7\Request('PUT', $url, $headers, $params);
        $proxy = $this->proxyconstruct();

        // Requests that receive a 4xx or 5xx response will throw a
        // Guzzle\Http\Exception\BadResponseException. We want to
        // handle this in a sane way and provide the caller with
        // a useful response. So we catch the error and return the
        // resposne.
        try {
            $response = $this->client->send($psr7request, $proxy);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;

    }

    /**
     * Creates post API requests.
     * @param string $url
     * @param unknown $params
     * @return \Psr\Http\Message\ResponseInterface|NULL
     */
    public function post($url, $params) {
        $headers = ['content-type' => 'application/x-www-form-urlencoded'];
        $psr7request = new \GuzzleHttp\Psr7\Request('POST', $url, $headers, $params);
        $proxy = $this->proxyconstruct();

        // Requests that receive a 4xx or 5xx response will throw a
        // Guzzle\Http\Exception\BadResponseException. We want to
        // handle this in a sane way and provide the caller with
        // a useful response. So we catch the error and return the
        // resposne.
        try {
            $response = $this->client->send($psr7request, $proxy);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;

    }

    /**
     * Posts a Moodle file object to provided URL.
     *
     * @param string $url URL to post file to.
     * @param file $file Moodle file object to post
     * @return \Psr\Http\Message\ResponseInterface|NULL
     */
    public function postfile($url, $file) {
        $headers = [];
        $contents = $file->get_content_file_handle();
        $multipart = new \GuzzleHttp\Psr7\MultipartStream([
                [
                        'name' => 'upload_file',
                        'contents' => $contents
                ],
        ]);

        $psr7request = new \GuzzleHttp\Psr7\Request('POST', $url, $headers, $multipart);
        $proxy = $this->proxyconstruct();

        // Requests that receive a 4xx or 5xx response will throw a
        // Guzzle\Http\Exception\BadResponseException. We want to
        // handle this in a sane way and provide the caller with
        // a useful response. So we catch the error and return the
        // resposne.
        try {
            $response = $this->client->send($psr7request, $proxy);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;

    }

    /**
     * Creates delete API requests.
     *
     * @param unknown $url
     * @return \Psr\Http\Message\ResponseInterface|NULL
     */
    public function delete($url) {
        $psr7request = new \GuzzleHttp\Psr7\Request('DELETE', $url);
        $proxy = $this->proxyconstruct();

        // Requests that receive a 4xx or 5xx response will throw a
        // Guzzle\Http\Exception\BadResponseException. We want to
        // handle this in a sane way and provide the caller with
        // a useful response. So we catch the error and return the
        // resposne.
        try {
            $response = $this->client->send($psr7request, $proxy);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = $e->getResponse();
        }

        return $response;

    }
}
