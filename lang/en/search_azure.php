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
 * Plugin strings are defined here.
 *
 * @package     search_azure
 * @category    string
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Azure Search';
$string['pluginname_help'] = 'Search backend for the Azure Search search engine';

$string['adminsettings'] = 'Plugin settings';
$string['apikey'] = 'API Key';
$string['apikey_help'] = 'An Azure Search Admin API related to the service you want to use.';
$string['apiversion'] = 'API version';
$string['apiversion_help'] = 'The Azure Search Admin API version to use, (normally the this should alwasy be the default).';
$string['enrichsettings'] = 'Data Enrichment settings';
$string['fileindexing'] = 'Enable file indexing';
$string['fileindexing_help'] = 'Enables file indexing for this plugin. With this option checked you will need to enter details of the Tika service in the "File indexing settings" below.<br/>
You will need to reindex all site contents after enabling this option for all files to be added.';
$string['fileindexsettings'] = 'File indexing settings';
$string['fileindexsettings_help'] = 'Enter the details for the Tika service. These are required if file indexing is enabled above.';
$string['index'] = 'Index';
$string['index_help'] = 'Namespace index to store search data in backend';
$string['pluginsettings'] = 'Plugin Settings';
$string['searchurl'] = 'URL';
$string['searchurl_help'] = 'The FQDN of the Azure Search engine endpoint';
$string['tikahostname'] = 'Tika Hostname';
$string['tikahostname_help'] = 'The FQDN of the Apache Tika endpoint';
$string['tikaport'] = 'Tika Port';
$string['tikaport_help'] = 'The Port of the Apache Tika endpoint';
$string['tikasendsize'] = 'Maximum file size';
$string['tikasendsize_help'] = 'Sending large files to Tika can cause out of memory issues. Therefore we limit it to a size in bytes.';
