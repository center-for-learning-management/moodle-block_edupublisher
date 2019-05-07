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
 * @package    block_edupublisher
 * @copyright  2018 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// We define the web service functions to install.
$functions = array(
    'block_edupublisher_list' => array(
        'classname'   => 'block_edupublisher_external',
        'methodname'  => 'list',
        'classpath'   => 'blocks/edupublisher/externallib.php',
        'description' => 'Returns a list of published resources in this moodle.',
        'type'        => 'read',
    ),
    'block_edupublisher_rate' => array(
        'classname'   => 'block_edupublisher_external',
        'methodname'  => 'rate',
        'classpath'   => 'blocks/edupublisher/externallib.php',
        'description' => 'Dos a rating for a package and returns the average rating.',
        'type'        => 'write',
        'ajax'        => 1,
    ),
    'block_edupublisher_search' => array(
        'classname'   => 'block_edupublisher_external',
        'methodname'  => 'search',
        'classpath'   => 'blocks/edupublisher/externallib.php',
        'description' => 'Performs a search based on a search string',
        'type'        => 'read',
        'ajax'        => 1,
    ),
    'block_edupublisher_trigger_active' => array(
        'classname'   => 'block_edupublisher_external',
        'methodname'  => 'trigger_active',
        'classpath'   => 'blocks/edupublisher/externallib.php',
        'description' => 'Triggers active-state of a package or channel.',
        'type'        => 'write',
        'ajax'        => 1,
    ),
);


// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'eduPublisher' => array(
        'functions' => array (
            'local_edupublisher_list',
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);
