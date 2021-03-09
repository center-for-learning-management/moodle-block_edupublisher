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
    if ($oldversion < 2019062901) {
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
        upgrade_block_savepoint(true, 2019062901, 'edupublisher');
    }
    if ($oldversion < 2019062901) {
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
        upgrade_block_savepoint(true, 2019062901, 'edupublisher');
    }

    if ($oldversion < 2019120300) {
        $table = new xmldb_table('block_edupublisher_evaluatio');

        // Adding fields to table block_edupublisher_evaluatio.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('evaluator_first_name', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('evaluator_last_name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('e_tapa_name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('e_tapa_link', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('evaluator_email', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('author_contact', XMLDB_TYPE_BINARY, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('evaluated_on', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('evaluated_at', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('comprehensible_description', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('suitable_workflow', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('reasonable_preconditions', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('correct_content', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('improvement_specification', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('technology_application', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('comments', XMLDB_TYPE_TEXT, null, null, null, null, null);
        // Adding keys to table block_edupublisher_evaluatio.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));


        // Conditionally launch create table for block_edupublisher_evaluatio.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2019120300, 'edupublisher');
    }

    if ($oldversion < 2020010800) {
        $sql = "DELETE FROM {block_edupublisher_metadata} WHERE field=?";
        $DB->execute($sql, array('etapas_vonschule'));
        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2020010800, 'edupublisher');
    }
    if ($oldversion < 2020051401) {
        $table = new xmldb_table('block_edupublisher_log');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('packageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeentered', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('viewed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enrolled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('unenrolled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('cloned', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $olduses = $DB->get_records('block_edupublisher_uses', array());
        foreach ($olduses AS $olduse) {
            $data = array(
                'packageid' => $olduse->package,
                'userid' => $olduse->userid,
                'timeentered' => $olduse->created,
                'cloned' => 1
            );
            $DB->insert_record('block_edupublisher_log', $data);
        }

        upgrade_block_savepoint(true, 2020051401, 'edupublisher');
    }
    if ($oldversion < 2020062600) {
        $table = new xmldb_table('block_edupublisher_comments');
        $field = new xmldb_field('forchannel', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null, 'ispublic');
        // Conditionally launch add field isautocomment.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2020062600, 'edupublisher');
    }

    // In the next version we can remove the table "block_edupublisher_uses".
    // But we keep it after this update to avoid risk of loosing data.
    if ($oldversion < 2020062600) {
        // Define table block_edupublisher_pub_ext to be created.
        $table = new xmldb_table('block_edupublisher_pub_ext');

        // Adding fields to table block_edupublisher_pub_ext.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('publisherid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('url', XMLDB_TYPE_CHAR, '250', null, XMLDB_NOTNULL, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_edupublisher_pub_ext.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_edupublisher_pub_ext.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2020062600, 'edupublisher');
    }
    if ($oldversion < 2020121500) {
        $table = new xmldb_table('block_edupublisher_packages');
        $field = new xmldb_field('backuped', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'modified');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2020121500, 'edupublisher');
    }

    if ($oldversion < 2021011501) {
        // Define table block_edupublisher_externals to be created.
        $table = new xmldb_table('block_edupublisher_externals');

        // Adding fields to table block_edupublisher_externals.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('pubid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('url', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('authuser', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('authpwd', XMLDB_TYPE_CHAR, '40', null, null, null, null);

        // Adding keys to table block_edupublisher_externals.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_edupublisher_externals.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_edupublisher_extpack to be created.
        $table = new xmldb_table('block_edupublisher_extpack');

        // Adding fields to table block_edupublisher_extpack.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('extid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('packageid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('lasttimemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table block_edupublisher_extpack.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_edupublisher_extpack.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_edupublisher_extsect to be created.
        $table = new xmldb_table('block_edupublisher_extsect');

        // Adding fields to table block_edupublisher_extsect.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('packageid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('externalid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('coursesection', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table block_edupublisher_extsect.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_edupublisher_extsect.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_edupublisher_extitem to be created.
        $table = new xmldb_table('block_edupublisher_extitem');

        // Adding fields to table block_edupublisher_extitem.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table block_edupublisher_extitem.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_edupublisher_extitem.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2021011501, 'edupublisher');
    }
    if ($oldversion < 2021011503) {
        $table = new xmldb_table('block_edupublisher_extitem');
        $field = new xmldb_field('packageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2021011503, 'edupublisher');
    }
    if ($oldversion < 2021011504) {
        $table = new xmldb_table('block_edupublisher_extitem');
        $field = new xmldb_field('externalid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'sectionid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2021011504, 'edupublisher');
    }
    if ($oldversion < 2021011800) {
        // Define index coursesection (unique) to be added to block_edupublisher_extsect.
        $table = new xmldb_table('block_edupublisher_extsect');
        $index = new xmldb_index('coursesection', XMLDB_INDEX_UNIQUE, ['coursesection']);

        // Conditionally launch add index coursesection.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2021011800, 'edupublisher');
    }
    if ($oldversion < 2021011801) {
        // Define index packageid_externalid (unique) to be added to block_edupublisher_extsect.
        $table = new xmldb_table('block_edupublisher_extsect');
        $index = new xmldb_index('packageid_externalid', XMLDB_INDEX_UNIQUE, ['packageid','externalid']);

        // Conditionally launch add index coursesection.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2021011801, 'edupublisher');
    }
    if ($oldversion < 2021030901) {
        // Define index packageid_externalid (unique) to be added to block_edupublisher_extsect.
        $table = new xmldb_table('block_edupublisher_pub');
        $field = new xmldb_field('mail', XMLDB_TYPE_CHAR, '250', null, null, null, '', 'name');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2021030901, 'edupublisher');
    }


    return true;
}
