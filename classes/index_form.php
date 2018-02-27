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
 * Main Admin settings form class.
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace search_azure;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Main Admin settings form class.
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class index_form extends \moodleform {

    /**
     * Build form for the general setting admin page for plugin.
     */
    public function definition() {
        $config = get_config('search_azure');
        $mform = $this->_form;

        // Basic settings.
        $mform->addElement('header', 'azuresettings', get_string('azuresettings', 'search_azure'));

        $mform->addElement('text', 'searchurl',  get_string ('searchurl', 'search_azure'));
        $mform->setType('searchurl', PARAM_URL);
        $mform->addHelpButton('searchurl', 'searchurl', 'search_azure');
        $mform->addRule('searchurl', get_string ('required'), 'required', '', 'client');
        if (isset($config->searchurl)) {
            $mform->setDefault('searchurl', $config->searchurl);
        } else {
            $mform->setDefault('searchurl', '');
        }

        $mform->addElement('text', 'index',  get_string ('index', 'search_azure'));
        $mform->setType('index', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('index', 'index', 'search_azure');
        $mform->addRule('index', get_string ('required'), 'required', '', 'client');
        if (isset($config->index)) {
            $mform->setDefault('index', $config->index);
        } else {
            $mform->setDefault('index', 'moodle');
        }

        $mform->addElement('text', 'apikey',  get_string ('apikey', 'search_azure'));
        $mform->setType('apikey', PARAM_TEXT);
        $mform->addHelpButton('apikey', 'apikey', 'search_azure');
        if (isset($config->apikey)) {
            $mform->setDefault('apikey', $config->apikey);
        } else {
            $mform->setDefault('apikey', '');
        }

        $mform->addElement('text', 'apiversion',  get_string ('apiversion', 'search_azure'));
        $mform->setType('apiversion', PARAM_TEXT);
        $mform->addHelpButton('apiversion', 'apiversion', 'search_azure');
        if (isset($config->apiversion)) {
            $mform->setDefault('apiversion', $config->apiversion);
        } else {
            $mform->setDefault('apiversion', '2016-09-01');
        }

        $this->add_action_buttons();
    }

}
