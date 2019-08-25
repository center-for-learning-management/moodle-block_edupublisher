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
    'block_edupublisher_init_import_load_courses' => array(
        'classname'   => 'block_edupublisher_external',
        'methodname'  => 'init_import_load_courses',
        'classpath'   => 'blocks/edupublisher/externallib.php',
        'description' => 'Loads all courses the user has trainer capabilities.',
        'type'        => 'read',
        'ajax'        => 1,
    ),
    'block_edupublisher_init_import_load_sections' => array(
        'classname'   => 'block_edupublisher_external',
        'methodname'  => 'init_import_load_sections',
        'classpath'   => 'blocks/edupublisher/externallib.php',
        'description' => 'Loads all section of a course the user is enrolled in.',
        'type'        => 'read',
        'ajax'        => 1,
    ),
    'block_edupublisher_licence_generate' => array(
        'classname'   => 'block_edupublisher_external',
        'methodname'  => 'licence_generate',
        'classpath'   => 'blocks/edupublisher/externallib.php',
        'description' => 'Generate licences for a publisher and prevent duplicates, BUT NOT STORE THEM!',
        'type'        => 'read',
        'ajax'        => 1,
    ),
    'block_edupublisher_licence_generatenow' => array(
        'classname'   => 'block_edupublisher_external',
        'methodname'  => 'licence_generatenow',
        'classpath'   => 'blocks/edupublisher/externallib.php',
        'description' => 'Write generated licencekeys to database - prevents duplicates!',
        'type'        => 'write',
        'ajax'        => 1,
    ),
    'block_edupublisher_licence_list' => array(
        'classname'   => 'block_edupublisher_external',
        'methodname'  => 'licence_list',
        'classpath'   => 'blocks/edupublisher/externallib.php',
        'description' => 'Lists all licences of a publisher',
        'type'        => 'read',
        'ajax'        => 1,
    ),
    'block_edupublisher_licence_redeem' => array(
        'classname'   => 'block_edupublisher_external',
        'methodname'  => 'licence_redeem',
        'classpath'   => 'blocks/edupublisher/externallib.php',
        'description' => 'Retrieves the licencetype for a licencekey',
        'type'        => 'read',
        'ajax'        => 1,
    ),
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
    'block_edupublisher_store_publisher' => array(
        'classname'   => 'block_edupublisher_external',
        'methodname'  => 'store_publisher',
        'classpath'   => 'blocks/edupublisher/externallib.php',
        'description' => 'Stores information for a publisher',
        'type'        => 'write',
        'ajax'        => 1,
    ),
    'block_edupublisher_store_publisher_user' => array(
        'classname'   => 'block_edupublisher_external',
        'methodname'  => 'store_publisher_user',
        'classpath'   => 'blocks/edupublisher/externallib.php',
        'description' => 'Add or remove users to/from a publisher',
        'type'        => 'write',
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
