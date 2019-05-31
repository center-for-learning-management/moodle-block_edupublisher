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
 * @copyright  2019 Digital Education Society (http://www.dibig.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_block_edupublisher_upgrade($oldversion=0) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2019021501) {
        // Define table block_edupublisher_comments to be created.
        $table = new xmldb_table('block_edupublisher_comments');

        // Adding fields to table block_edupublisher_comments.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, null, XMLDB_SEQUENCE, null);
        $table->add_field('package', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('permahash', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_edupublisher_comments.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_edupublisher_comments.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2019021501, 'edupublisher');
    }
    if ($oldversion < 2019021503) {
        $table = new xmldb_table('block_edupublisher_comments');
        $field = new xmldb_field('isautocomment', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'permahash');
        // Conditionally launch add field isautocomment.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('ispublic', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'isautocomment');
        // Conditionally launch add field ispublic.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2019021503, 'edupublisher');
    }
    if ($oldversion < 2019050700) {
        // Define table block_edupublisher_pub to be created.
        $table = new xmldb_table('block_edupublisher_pub');

        // Adding fields to table block_edupublisher_pub.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '250', null, XMLDB_NOTNULL, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        // Adding keys to table block_edupublisher_pub.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_edupublisher_pub.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_edupublisher_pub_user to be created.
        $table = new xmldb_table('block_edupublisher_pub_user');

        // Adding fields to table block_edupublisher_pub_user.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('publisherid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_edupublisher_pub_user.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_edupublisher_pub_user.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_edupublisher_lic to be created.
        $table = new xmldb_table('block_edupublisher_lic');

        // Adding fields to table block_edupublisher_lic.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('licencekey', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('redeemid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('amounts', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '-1');

        // Adding keys to table block_edupublisher_lic.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_edupublisher_lic.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_edupublisher_lic_pack to be created.
        $table = new xmldb_table('block_edupublisher_lic_pack');

        // Adding fields to table block_edupublisher_lic_pack.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('packageid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('licenceid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');

        // Adding keys to table block_edupublisher_lic_pack.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_edupublisher_lic_pack.
        if (!$dbman->table_exists($table)) {
           $dbman->create_table($table);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2019050700, 'edupublisher');
    }
    if ($oldversion < 2019050705) {
        // Define field publisherid to be added to block_edupublisher_lic.
        $table = new xmldb_table('block_edupublisher_lic');
        $field = new xmldb_field('publisherid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null, 'id');
        // Conditionally launch add field publisherid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'amounts');
        // Conditionally launch add field created.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('maturity', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'created');
        // Conditionally launch add field maturity.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $index = new xmldb_index('licencekey', XMLDB_INDEX_UNIQUE, array('licencekey'), array('licencekeyscanonlyexistonce'));
        // Conditionally launch add index licencekey.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2019050705, 'edupublisher');
    }


    return true;
}
