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
 * Azure Search search engine settings.
 *
 * @package     search_azure
 * @copyright   Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('searchplugins', new admin_category('search_azure', get_string('pluginname', 'search_azure')));

    $pluginsettings = new admin_externalpage('search_azure_settings',
            get_string('adminsettings', 'search_azure'),
            new moodle_url('/search/engine/azure/index.php'));

    $enrichsettings = new admin_externalpage('search_azure_enrichsettings',
            get_string('enrichsettings', 'search_azure'),
            new moodle_url('/search/engine/azure/enrich.php'));

    $ADMIN->add('search_azure', $pluginsettings);
    $ADMIN->add('search_azure', $enrichsettings);

    $settings = null;
}
