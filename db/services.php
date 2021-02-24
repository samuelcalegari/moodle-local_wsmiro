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
 * @package   local_wsmiro
 * @copyright 2020 Moodle Pty Ltd (http://moodle.com)
 * @author    Samuel Calegari
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
    'local_wsmiro_get_logs' => array(
        'classname' => 'local_wsmiro_external',
        'methodname' => 'get_logs',
        'classpath' => 'local/wsmiro/externallib.php',
        'description' => 'Return Logs from a specific user',
        'type' => 'read',
    ),
    'local_wsmiro_get_overview' => array(
        'classname' => 'local_wsmiro_external',
        'methodname' => 'get_overview',
        'classpath' => 'local/wsmiro/externallib.php',
        'description' => 'Return Overview from a specific user',
        'type' => 'read',
    ),
    'local_wsmiro_get_user' => array(
        'classname' => 'local_wsmiro_external',
        'methodname' => 'get_user',
        'classpath' => 'local/wsmiro/externallib.php',
        'description' => 'Return User form userID and email',
        'type' => 'read',
    )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'Miro Service' => array(
        'functions' => array(
            'local_wsmiro_get_logs',
            'local_wsmiro_get_user',
            'local_wsmiro_get_overview',
            'core_enrol_get_enrolled_users'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);