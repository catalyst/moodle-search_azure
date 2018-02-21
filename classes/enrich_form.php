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
 * Document enrichment settings form class.
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace search_azure;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Document enrichment settings form class.
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrich_form extends \moodleform {

    /**
     * Build form for the general setting admin page for plugin.
     */
    public function definition() {
        $config = get_config('search_azure');
        $mform = $this->_form;

        // File indexing settings.
        $mform->addElement('header', 'fileindexsettings', get_string('fileindexsettings', 'search_azure'));

        $mform->addElement('advcheckbox',
                'fileindexing',
                get_string ('fileindexing', 'search_azure'),
                'Enable', array(), array(0, 1));
        $mform->setType('fileindexing', PARAM_INT);
        $mform->addHelpButton('fileindexing', 'fileindexing', 'search_azure');
        if (isset($config->fileindexing)) {
            $mform->setDefault('fileindexing', $config->fileindexing);
        } else {
            $mform->setDefault('fileindexing', 0);
        }

        $mform->addElement('text', 'tikahostname',  get_string ('tikahostname', 'search_azure'));
        $mform->setType('tikahostname', PARAM_URL);
        $mform->addHelpButton('tikahostname', 'tikahostname', 'search_azure');
        $mform->disabledIf('tikahostname', 'fileindexing');
        if (isset($config->tikahostname)) {
            $mform->setDefault('tikahostname', $config->tikahostname);
        } else {
            $mform->setDefault('tikahostname', 'http://127.0.0.1');
        }

        $mform->addElement('text', 'tikaport',  get_string ('tikaport', 'search_azure'));
        $mform->setType('tikaport', PARAM_INT);
        $mform->addHelpButton('tikaport', 'tikaport', 'search_azure');
        $mform->disabledIf('tikaport', 'fileindexing');
        if (isset($config->tikaport)) {
            $mform->setDefault('tikaport', $config->tikaport);
        } else {
            $mform->setDefault('tikaport', 9998);
        }

        $mform->addElement('text', 'tikasendsize',  get_string ('tikasendsize', 'search_azure'));
        $mform->setType('tikasendsize', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('tikasendsize', 'tikasendsize', 'search_azure');
        $mform->disabledIf('tikasendsize', 'fileindexing');
        if (isset($config->tikasendsize)) {
            $mform->setDefault('tikasendsize', $config->tikasendsize);
        } else {
            $mform->setDefault('tikasendsize', 512000000);
        }

        $this->add_action_buttons();
    }

}
