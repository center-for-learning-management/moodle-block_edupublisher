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

function xmldb_block_edupublisher_upgrade($oldversion = 0) {
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
        foreach ($olduses as $olduse) {
            $data = array(
                'packageid' => $olduse->package,
                'userid' => $olduse->userid,
                'timeentered' => $olduse->created,
                'cloned' => 1,
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
        $index = new xmldb_index('packageid_externalid', XMLDB_INDEX_UNIQUE, ['packageid', 'externalid']);

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
    if ($oldversion < 2021031100) {
        // Define field evaluator_first_name to be dropped from block_edupublisher_evaluatio.
        $table = new xmldb_table('block_edupublisher_evaluatio');
        $fields = array(
            new xmldb_field('evaluator_first_name'),
            new xmldb_field('evaluator_last_name'),
            new xmldb_field('e_tapa_name'),
            new xmldb_field('e_tapa_link'),
            new xmldb_field('evaluator_email'),
            new xmldb_field('author_contact'),
        );

        foreach ($fields as $field) {
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }
        $field = new xmldb_field('packageid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null, 'id');

        // Conditionally launch add field packageid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2021031100, 'edupublisher');
    }
    if ($oldversion < 2021031101) {
        $table = new xmldb_table('block_edupublisher_comments');
        $field = new xmldb_field('linkurl', XMLDB_TYPE_CHAR, '250', null, null, null, null, 'forchannel');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2021031101, 'edupublisher');
    }
    if ($oldversion < 2021031102) {
        $table = new xmldb_table('block_edupublisher_evaluatio');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null, 'packageid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $index = new xmldb_index('idx_packageid', XMLDB_INDEX_NOTUNIQUE, ['packageid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        upgrade_block_savepoint(true, 2021031102, 'edupublisher');
    }
    if ($oldversion < 2021031103) {
        $table = new xmldb_table('block_edupublisher_evaluatio');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'comments');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2021031103, 'edupublisher');
    }
    if ($oldversion < 2021041600) {
        $table = new xmldb_table('block_edupublisher_evaluatio');
        $field = new xmldb_field('schoollevel', XMLDB_TYPE_INTEGER, '4', null, null, null, null, 'comments');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('evaldate', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'schoollevel');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2021041600, 'edupublisher');
    }
    if ($oldversion < 2021042200) {
        $table = new xmldb_table('block_edupublisher_publish');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sourcecourseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('targetcourseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('importcompleted', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('packageid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('payload', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table block_edupublisher_publish.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table block_edupublisher_publish.
        $table->add_index('idx_sourcecourseid', XMLDB_INDEX_UNIQUE, ['sourcecourseid']);
        $table->add_index('idx_targetcourseid', XMLDB_INDEX_NOTUNIQUE, ['targetcourseid']);
        $table->add_index('idx_packageid', XMLDB_INDEX_NOTUNIQUE, ['packageid']);
        // Conditionally launch create table for block_edupublisher_publish.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2021042200, 'edupublisher');
    }

    if ($oldversion < 2022022500) {
        // Define table block_edupublisher_md_def to be created.
        $table = new xmldb_table('block_edupublisher_md_def');

        // Adding fields to table block_edupublisher_md_def.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('package', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('authormail', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('authormailshow', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('authorname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('image', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('licence', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('origins', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('publishas', XMLDB_TYPE_INTEGER, '1', null, null, null, '1');
        $table->add_field('published', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('schoollevel_primary', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schoollevel_secondary_1', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schoollevel_secondary_2', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schoollevel_tertiary', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('sourcecourse', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('subjectarea_arts', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('subjectarea_economics', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('subjectarea_geography', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('subjectarea_history', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('subjectarea_informatics', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('subjectarea_languages', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('subjectarea_mathematics', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('subjectarea_naturalsciences', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('subjectarea_other', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('subjectarea_philosophy', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('subjectarea_physicaleducation', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('tags', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table block_edupublisher_md_def.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_edupublisher_md_def.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_edupublisher_md_com to be created.
        $table = new xmldb_table('block_edupublisher_md_com');

        // Adding fields to table block_edupublisher_md_com.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('package', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('publishas', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('published', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('publisher', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('shoplink', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('validation', XMLDB_TYPE_CHAR, '20', null, null, null, null);

        // Adding keys to table block_edupublisher_md_com.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_edupublisher_md_com.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_edupublisher_md_edu to be created.
        $table = new xmldb_table('block_edupublisher_md_edu');

        // Adding fields to table block_edupublisher_md_edu.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('package', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('curriculum', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('educationallevel', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('language', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('lticartridge', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('ltisecret', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('ltiurl', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('publishas', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('published', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('schooltype', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table block_edupublisher_md_edu.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_edupublisher_md_edu.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_edupublisher_md_eta to be created.
        $table = new xmldb_table('block_edupublisher_md_eta');

        // Adding fields to table block_edupublisher_md_eta.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('package', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('gegenstand', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('kompetenzen', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('lticartridge', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('ltisecret', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('ltiurl', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('publishas', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('published', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('schulstufe_1', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schulstufe_2', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schulstufe_3', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schulstufe_4', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schulstufe_5', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schulstufe_6', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schulstufe_7', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schulstufe_8', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schulstufe_9', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schulstufe_10', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schulstufe_11', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schulstufe_12', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('schulstufe_13', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('status', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('subtype', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('stundenablauf', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('voraussetzungen', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('vorkenntnisse', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('zeitbedarf', XMLDB_TYPE_CHAR, '20', null, null, null, null);

        // Adding keys to table block_edupublisher_md_eta.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_edupublisher_md_eta.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table block_edupublisher_md_exa to be created.
        $table = new xmldb_table('block_edupublisher_md_exa');

        // Adding fields to table block_edupublisher_md_exa.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('package', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('datasource', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('sourceid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('title', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table block_edupublisher_md_exa.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for block_edupublisher_md_exa.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_block_savepoint(true, 2022022500, 'edupublisher');
    }
    if ($oldversion < 2022030802) {
        /*
         * ATTENTION!!!! Creating indices on text-columns did not work in MySQL.
         * Therefore a working SQL-Query is executed manually for these columns.
         */
        global $CFG, $DB;
        // Define index idx_schoollevel_primary (not unique) to be added to block_edupublisher_md_def.
        $table = new xmldb_table('block_edupublisher_md_def');
        $indices = [
            new xmldb_index('idx_package', XMLDB_INDEX_UNIQUE, ['package']),
            new xmldb_index('idx_authormail', XMLDB_INDEX_NOTUNIQUE, ['authormail']),
            new xmldb_index('idx_authorname', XMLDB_INDEX_NOTUNIQUE, ['authorname']),
            new xmldb_index('idx_licence', XMLDB_INDEX_NOTUNIQUE, ['licence']),
            new xmldb_index('idx_schoollevel_primary', XMLDB_INDEX_NOTUNIQUE, ['schoollevel_primary']),
            new xmldb_index('idx_schoollevel_secondary_1', XMLDB_INDEX_NOTUNIQUE, ['schoollevel_secondary_1']),
            new xmldb_index('idx_schoollevel_secondary_2', XMLDB_INDEX_NOTUNIQUE, ['schoollevel_secondary_2']),
            new xmldb_index('idx_schoollevel_tertiary', XMLDB_INDEX_NOTUNIQUE, ['schoollevel_tertiary']),
            new xmldb_index('idx_subjectarea_arts', XMLDB_INDEX_NOTUNIQUE, ['subjectarea_arts']),
            new xmldb_index('idx_subjectarea_economics', XMLDB_INDEX_NOTUNIQUE, ['subjectarea_economics']),
            new xmldb_index('idx_subjectarea_geography', XMLDB_INDEX_NOTUNIQUE, ['subjectarea_geography']),
            new xmldb_index('idx_subjectarea_history', XMLDB_INDEX_NOTUNIQUE, ['subjectarea_history']),
            new xmldb_index('idx_subjectarea_informatics', XMLDB_INDEX_NOTUNIQUE, ['subjectarea_informatics']),
            new xmldb_index('idx_subjectarea_languages', XMLDB_INDEX_NOTUNIQUE, ['subjectarea_languages']),
            new xmldb_index('idx_subjectarea_mathematics', XMLDB_INDEX_NOTUNIQUE, ['subjectarea_mathematics']),
            new xmldb_index('idx_subjectarea_naturalsciences', XMLDB_INDEX_NOTUNIQUE, ['subjectarea_naturalsciences']),
            new xmldb_index('idx_subjectarea_other', XMLDB_INDEX_NOTUNIQUE, ['subjectarea_other']),
            new xmldb_index('idx_subjectarea_philosophy', XMLDB_INDEX_NOTUNIQUE, ['subjectarea_philosophy']),
            new xmldb_index('idx_subjectarea_physicaleducation', XMLDB_INDEX_NOTUNIQUE, ['subjectarea_physicaleducation']),
            //new xmldb_index('idx_summary', XMLDB_INDEX_NOTUNIQUE, ['summary']),
            //new xmldb_index('idx_tags', XMLDB_INDEX_NOTUNIQUE, ['tags']),
            new xmldb_index('idx_title', XMLDB_INDEX_NOTUNIQUE, ['title']),
        ];
        foreach ($indices as $index) {
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }
        $tabname = "{$CFG->prefix}block_edupublisher_md_def";
        $shortname = "{$CFG->prefix}blocedupmddef";
        $fulltexts = ['summary', 'tags'];
        foreach ($fulltexts as $col) {
            $sql = "CREATE FULLTEXT INDEX
                        {$shortname}_{$col}_ix
                        ON $tabname ($col)";
            try {
                $DB->execute($sql);
            } catch (Exception $ex) {
                throw new \moodle_exception("Unable to create index for $col in $tabname");
            }
        }

        $table = new xmldb_table('block_edupublisher_md_edu');
        $indices = [
            new xmldb_index('idx_package', XMLDB_INDEX_UNIQUE, ['package']),
            //new xmldb_index('idx_curriculum', XMLDB_INDEX_NOTUNIQUE, ['curriculum']),
            //new xmldb_index('idx_educationallevel', XMLDB_INDEX_NOTUNIQUE, ['educationallevel']),
            new xmldb_index('idx_language', XMLDB_INDEX_NOTUNIQUE, ['language']),
            new xmldb_index('idx_publishas', XMLDB_INDEX_NOTUNIQUE, ['publishas']),
            new xmldb_index('idx_published', XMLDB_INDEX_NOTUNIQUE, ['published']),
            //new xmldb_index('idx_schooltype', XMLDB_INDEX_NOTUNIQUE, ['schooltype']),
            //new xmldb_index('idx_type', XMLDB_INDEX_NOTUNIQUE, ['type']),
        ];
        foreach ($indices as $index) {
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }
        $tabname = "{$CFG->prefix}block_edupublisher_md_edu";
        $shortname = "{$CFG->prefix}blocedupmdedu";
        $fulltexts = ['curriculum', 'educationallevel', 'schooltype', 'type'];
        foreach ($fulltexts as $col) {
            $sql = "CREATE FULLTEXT INDEX
                        {$shortname}_{$col}_ix
                        ON $tabname ($col)";
            try {
                $DB->execute($sql);
            } catch (Exception $ex) {
                throw new \moodle_exception("Unable to create index for $col in $tabname");
            }
        }

        $table = new xmldb_table('block_edupublisher_md_eta');
        $indices = [
            new xmldb_index('idx_package', XMLDB_INDEX_UNIQUE, ['package']),
            new xmldb_index('idx_gegenstand', XMLDB_INDEX_NOTUNIQUE, ['gegenstand']),
            //new xmldb_index('idx_kompetenzen', XMLDB_INDEX_NOTUNIQUE, ['kompetenzen']),
            new xmldb_index('idx_publishas', XMLDB_INDEX_NOTUNIQUE, ['publishas']),
            new xmldb_index('idx_published', XMLDB_INDEX_NOTUNIQUE, ['published']),
            new xmldb_index('idx_schulstufe_1', XMLDB_INDEX_NOTUNIQUE, ['schulstufe_1']),
            new xmldb_index('idx_schulstufe_2', XMLDB_INDEX_NOTUNIQUE, ['schulstufe_2']),
            new xmldb_index('idx_schulstufe_3', XMLDB_INDEX_NOTUNIQUE, ['schulstufe_3']),
            new xmldb_index('idx_schulstufe_4', XMLDB_INDEX_NOTUNIQUE, ['schulstufe_4']),
            new xmldb_index('idx_schulstufe_5', XMLDB_INDEX_NOTUNIQUE, ['schulstufe_5']),
            new xmldb_index('idx_schulstufe_6', XMLDB_INDEX_NOTUNIQUE, ['schulstufe_6']),
            new xmldb_index('idx_schulstufe_7', XMLDB_INDEX_NOTUNIQUE, ['schulstufe_7']),
            new xmldb_index('idx_schulstufe_8', XMLDB_INDEX_NOTUNIQUE, ['schulstufe_8']),
            new xmldb_index('idx_schulstufe_9', XMLDB_INDEX_NOTUNIQUE, ['schulstufe_9']),
            new xmldb_index('idx_schulstufe_10', XMLDB_INDEX_NOTUNIQUE, ['schulstufe_10']),
            new xmldb_index('idx_schulstufe_11', XMLDB_INDEX_NOTUNIQUE, ['schulstufe_11']),
            new xmldb_index('idx_schulstufe_12', XMLDB_INDEX_NOTUNIQUE, ['schulstufe_12']),
            new xmldb_index('idx_schulstufe_13', XMLDB_INDEX_NOTUNIQUE, ['schulstufe_13']),
            new xmldb_index('idx_subtype', XMLDB_INDEX_NOTUNIQUE, ['subtype']),
            //new xmldb_index('idx_stundenablauf', XMLDB_INDEX_NOTUNIQUE, ['stundenablauf']),
            new xmldb_index('idx_type', XMLDB_INDEX_NOTUNIQUE, ['type']),
            //new xmldb_index('idx_voraussetzungen', XMLDB_INDEX_NOTUNIQUE, ['voraussetzungen']),
            //new xmldb_index('idx_vorkenntnisse', XMLDB_INDEX_NOTUNIQUE, ['vorkenntnisse']),
            new xmldb_index('idx_zeitbedarf', XMLDB_INDEX_NOTUNIQUE, ['zeitbedarf']),
        ];
        foreach ($indices as $index) {
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }
        $tabname = "{$CFG->prefix}block_edupublisher_md_eta";
        $shortname = "{$CFG->prefix}blocedupmdeta";
        $fulltexts = ['kompetenzen', 'stundenablauf', 'voraussetzungen', 'vorkenntnisse'];
        foreach ($fulltexts as $col) {
            $sql = "CREATE FULLTEXT INDEX
                        {$shortname}_{$col}_ix
                        ON $tabname ($col)";
            try {
                $DB->execute($sql);
            } catch (Exception $ex) {
                throw new \moodle_exception("Unable to create index for $col in $tabname");
            }
        }
        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2022030802, 'edupublisher');
    }
    if ($oldversion < 2022031000) {
        $table = new xmldb_table('block_edupublisher_md_def');
        $field = new xmldb_field('sourcecourse');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        upgrade_block_savepoint(true, 2022031000, 'edupublisher');
    }
    if ($oldversion < 2022032200) {
        // Set status automatically for all evaluated etapas.
        global $DB;
        $etapas = $DB->get_records('block_edupublisher_md_eta', [], '', 'id,package,published');
        foreach ($etapas as $etapa) {
            if (!empty($etapa->published)) {
                $DB->set_field('block_edupublisher_md_eta', 'status', 'public', ['package' => $etapa->package]);
            }
        }
        $evaluations = $DB->get_records('block_edupublisher_evaluatio', [], '', 'id,packageid');
        foreach ($evaluations as $evaluation) {
            $DB->set_field('block_edupublisher_md_eta', 'status', 'eval', ['package' => $evaluation->packageid]);
        }
    }
    if ($oldversion < 2022032201) {
        $table = new xmldb_table('block_edupublisher_packages');
        $field = new xmldb_field('rating', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'title');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $index = new xmldb_index('idx_rating', XMLDB_INDEX_NOTUNIQUE, ['rating']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $index = new xmldb_index('idx_title', XMLDB_INDEX_NOTUNIQUE, ['title']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        global $DB;
        $sql = "SELECT package,AVG(rating) avg
                    FROM {block_edupublisher_rating}
                    GROUP BY package";
        $ratings = $DB->get_records_sql($sql, []);
        foreach ($ratings as $rating) {
            $DB->set_field('block_edupublisher_packages', 'rating', round($rating->avg, 0), ['id' => $rating->package]);
        }
        upgrade_block_savepoint(true, 2022032201, 'edupublisher');
    }
    if ($oldversion < 2022040104) {
        $table = new xmldb_table('block_edupublisher_md_edu');
        $field = new xmldb_field('topic', XMLDB_TYPE_TEXT, null, null, null, null, null, 'schooltype');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2022040104, 'edupublisher');
        \block_edupublisher\locallib::atomize_database();
    }
    if ($oldversion < 2024061900) {
        $table = new xmldb_table('block_edupublisher_md_def');
        $field = new xmldb_field('schoollevel_elementary', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'published');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('schoollevel_adult', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'schoollevel_secondary_2');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $index = new xmldb_index('idx_schoollevel_elementary', XMLDB_INDEX_NOTUNIQUE, ['schoollevel_elementary']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $index = new xmldb_index('idx_schoollevel_adult', XMLDB_INDEX_NOTUNIQUE, ['schoollevel_adult']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $table = new xmldb_table('block_edupublisher_md_eduneu');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('publishas', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');
        $table->add_field('published', XMLDB_TYPE_INTEGER, '19', null, null, null, '0');
        $table->add_field('lticartridge', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('ltisecret', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('ltiurl', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('contenttype_assignment', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');
        $table->add_field('contenttype_exercise', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');
        $table->add_field('contenttype_learningtrack', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');
        $table->add_field('contenttype_supportmaterial', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');
        $table->add_field('kompetenzen', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('purpose_preparation', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');
        $table->add_field('purpose_supervised', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');
        $table->add_field('purpose_selfpaced', XMLDB_TYPE_INTEGER, '2', null, null, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_block_savepoint(true, 2024061900, 'edupublisher');
    }
    if ($oldversion < 2024062800) {
        $table = new xmldb_table('block_edupublisher_md_eduneu');
        $field = new xmldb_field('package', XMLDB_TYPE_INTEGER, '19', null, null, null, null, 'id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $key = new xmldb_key('key_package', XMLDB_KEY_UNIQUE, ['package']);
        $dbman->add_key($table, $key);
        upgrade_block_savepoint(true, 2024062800, 'edupublisher');
    }

    if ($oldversion < 2024101605) {

        // Define field filling_mode to be added to block_edupublisher_md_def.
        $table = new xmldb_table('block_edupublisher_md_def');
        $field = new xmldb_field('filling_mode', XMLDB_TYPE_INTEGER, '5', null, null, null, null, 'title');

        // Conditionally launch add field filling_mode.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // for old items the mode = expert
        $DB->execute("UPDATE {block_edupublisher_md_def} SET filling_mode = 100 WHERE filling_mode IS NULL");

        // Define table block_edupublisher_pkg_items to be created.
        $table = new xmldb_table('block_edupublisher_pkg_items');

        // Adding fields to table block_edupublisher_pkg_items.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('packageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('link', XMLDB_TYPE_CHAR, '250', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sorting', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('didaktische_hinweise', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_edupublisher_pkg_items.
        $table->add_key('idx_id', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table block_edupublisher_pkg_items.
        $table->add_index('idx_packageid', XMLDB_INDEX_NOTUNIQUE, ['packageid']);

        // Conditionally launch create table for block_edupublisher_pkg_items.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // trim leading and trailing commas from a field channels
        $DB->execute("UPDATE {block_edupublisher_packages}
            SET channels = TRIM(BOTH ',' FROM channels)
            WHERE channels IS NOT NULL");

        // new delimiter
        $DB->execute("UPDATE {block_edupublisher_md_def} SET tags=REPLACE(REPLACE(TRIM(BOTH '~~~|~~~' FROM tags), ',',''), '~~~|~~~', ',') WHERE tags LIKE '%~~~|~~~%'");
        $DB->execute("UPDATE {block_edupublisher_md_edu} SET type=REPLACE(REPLACE(TRIM(BOTH '~~~|~~~' FROM type), ',',''), '~~~|~~~', ',') WHERE type LIKE '%~~~|~~~%'");
        $DB->execute("UPDATE {block_edupublisher_md_edu} SET schooltype=REPLACE(REPLACE(TRIM(BOTH '~~~|~~~' FROM schooltype), ',',''), '~~~|~~~', ',') WHERE schooltype LIKE '%~~~|~~~%'");
        $DB->execute("UPDATE {block_edupublisher_md_edu} SET topic=REPLACE(REPLACE(TRIM(BOTH '~~~|~~~' FROM topic), ',',''), '~~~|~~~', ',') WHERE topic LIKE '%~~~|~~~%'");
        $DB->execute("UPDATE {block_edupublisher_md_edu} SET educationallevel=REPLACE(REPLACE(TRIM(BOTH '~~~|~~~' FROM educationallevel), ',',''), '~~~|~~~', ',') WHERE educationallevel LIKE '%~~~|~~~%'");

        $table = new xmldb_table('block_edupublisher_md_def');
        $field = new xmldb_field('image', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field schoollevels to be added to block_edupublisher_md_def.
        $table = new xmldb_table('block_edupublisher_md_def');
        $field = new xmldb_field('schoollevels', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'published');

        // Conditionally launch add field schoollevels.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field subjectareas to be added to block_edupublisher_md_def.
        $table = new xmldb_table('block_edupublisher_md_def');
        $field = new xmldb_field('subjectareas', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'schoollevels');

        // Conditionally launch add field subjectareas.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field contenttypes to be added to block_edupublisher_md_eduneu.
        $table = new xmldb_table('block_edupublisher_md_eduneu');
        $field = new xmldb_field('contenttypes', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'ltiurl');

        // Conditionally launch add field contenttypes.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field purposes to be added to block_edupublisher_md_eduneu.
        $table = new xmldb_table('block_edupublisher_md_eduneu');
        $field = new xmldb_field('purposes', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'kompetenzen');

        // Conditionally launch add field purposes.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->execute("UPDATE {block_edupublisher_md_def} SET schoollevels = ''");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET schoollevels = CONCAT(schoollevels, ',elementary') WHERE schoollevel_elementary = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET schoollevels = CONCAT(schoollevels, ',primary') WHERE schoollevel_primary = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET schoollevels = CONCAT(schoollevels, ',secondary_1') WHERE schoollevel_secondary_1 = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET schoollevels = CONCAT(schoollevels, ',secondary_2') WHERE schoollevel_secondary_2 = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET schoollevels = CONCAT(schoollevels, ',tertiary') WHERE schoollevel_tertiary = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET schoollevels = CONCAT(schoollevels, ',adult') WHERE schoollevel_adult = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET schoollevels = TRIM(BOTH ',' FROM schoollevels)");

        $DB->execute("UPDATE {block_edupublisher_md_def} SET subjectareas = ''");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET subjectareas = CONCAT(subjectareas, ',arts') WHERE subjectarea_arts = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET subjectareas = CONCAT(subjectareas, ',economics') WHERE subjectarea_economics = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET subjectareas = CONCAT(subjectareas, ',geography') WHERE subjectarea_geography = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET subjectareas = CONCAT(subjectareas, ',history') WHERE subjectarea_history = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET subjectareas = CONCAT(subjectareas, ',informatics') WHERE subjectarea_informatics = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET subjectareas = CONCAT(subjectareas, ',languages') WHERE subjectarea_languages = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET subjectareas = CONCAT(subjectareas, ',mathematics') WHERE subjectarea_mathematics = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET subjectareas = CONCAT(subjectareas, ',naturalsciences') WHERE subjectarea_naturalsciences = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET subjectareas = CONCAT(subjectareas, ',philosophy') WHERE subjectarea_philosophy = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET subjectareas = CONCAT(subjectareas, ',physicaleducation') WHERE subjectarea_physicaleducation = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET subjectareas = CONCAT(subjectareas, ',other') WHERE subjectarea_other = 1");
        $DB->execute("UPDATE {block_edupublisher_md_def} SET subjectareas = TRIM(BOTH ',' FROM subjectareas)");

        $DB->execute("UPDATE {block_edupublisher_md_eduneu} SET contenttypes = ''");
        $DB->execute("UPDATE {block_edupublisher_md_eduneu} SET contenttypes = CONCAT(contenttypes, ',assignment') WHERE contenttype_assignment = 1");
        $DB->execute("UPDATE {block_edupublisher_md_eduneu} SET contenttypes = CONCAT(contenttypes, ',exercise') WHERE contenttype_exercise = 1");
        $DB->execute("UPDATE {block_edupublisher_md_eduneu} SET contenttypes = CONCAT(contenttypes, ',learningtrack') WHERE contenttype_learningtrack = 1");
        $DB->execute("UPDATE {block_edupublisher_md_eduneu} SET contenttypes = CONCAT(contenttypes, ',supportmaterial') WHERE contenttype_supportmaterial = 1");
        $DB->execute("UPDATE {block_edupublisher_md_eduneu} SET contenttypes = TRIM(BOTH ',' FROM contenttypes)");

        $DB->execute("UPDATE {block_edupublisher_md_eduneu} SET purposes = ''");
        $DB->execute("UPDATE {block_edupublisher_md_eduneu} SET purposes = CONCAT(purposes, ',preparation') WHERE purpose_preparation = 1");
        $DB->execute("UPDATE {block_edupublisher_md_eduneu} SET purposes = CONCAT(purposes, ',supervised') WHERE purpose_supervised = 1");
        $DB->execute("UPDATE {block_edupublisher_md_eduneu} SET purposes = CONCAT(purposes, ',selfpaced') WHERE purpose_selfpaced = 1");
        $DB->execute("UPDATE {block_edupublisher_md_eduneu} SET purposes = TRIM(BOTH ',' FROM purposes)");

        $table = new xmldb_table('block_edupublisher_md_def');
        $fields = [
            'schoollevel_elementary',
            'schoollevel_primary',
            'schoollevel_secondary_1',
            'schoollevel_secondary_2',
            'schoollevel_tertiary',
            'schoollevel_adult',
            'subjectarea_arts',
            'subjectarea_economics',
            'subjectarea_geography',
            'subjectarea_history',
            'subjectarea_informatics',
            'subjectarea_languages',
            'subjectarea_mathematics',
            'subjectarea_naturalsciences',
            'subjectarea_philosophy',
            'subjectarea_physicaleducation',
            'subjectarea_other',
        ];

        foreach ($fields as $field) {
            $index = new xmldb_index("idx_{$field}", XMLDB_INDEX_NOTUNIQUE, [$field]);
            if ($dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
            }

            $field = new xmldb_field($field);
            // Conditionally launch drop field schoollevel_adult.
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        $table = new xmldb_table('block_edupublisher_md_eduneu');
        $fields = [
            'contenttype_assignment',
            'contenttype_exercise',
            'contenttype_learningtrack',
            'contenttype_supportmaterial',
            'purpose_preparation',
            'purpose_supervised',
            'purpose_selfpaced',
        ];

        foreach ($fields as $field) {
            $index = new xmldb_index("idx_{$field}", XMLDB_INDEX_NOTUNIQUE, [$field]);
            if ($dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
            }

            $field = new xmldb_field($field);
            // Conditionally launch drop field schoollevel_adult.
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        // Define index idx_schoollevels (not unique) to be added to block_edupublisher_md_def.
        $table = new xmldb_table('block_edupublisher_md_def');
        $index = new xmldb_index('idx_schoollevels', XMLDB_INDEX_NOTUNIQUE, ['schoollevels']);

        // Conditionally launch add index idx_schoollevels.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index idx_subjectareas (not unique) to be added to block_edupublisher_md_def.
        $table = new xmldb_table('block_edupublisher_md_def');
        $index = new xmldb_index('idx_subjectareas', XMLDB_INDEX_NOTUNIQUE, ['subjectareas']);

        // Conditionally launch add index idx_subjectareas.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index idx_contenttypes (not unique) to be added to block_edupublisher_md_eduneu.
        $table = new xmldb_table('block_edupublisher_md_eduneu');
        $index = new xmldb_index('idx_contenttypes', XMLDB_INDEX_NOTUNIQUE, ['contenttypes']);

        // Conditionally launch add index idx_contenttypes.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index idx_purposes (not unique) to be added to block_edupublisher_md_eduneu.
        $table = new xmldb_table('block_edupublisher_md_eduneu');
        $index = new xmldb_index('idx_purposes', XMLDB_INDEX_NOTUNIQUE, ['purposes']);

        // Conditionally launch add index idx_purposes.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2024101605, 'edupublisher');
    }

    if ($oldversion < 2024102801) {
        // delete packages without course
        $DB->execute("UPDATE {block_edupublisher_packages} set deleted=?, course=0 WHERE course not in (select id from {course}) and deleted=0", [time()]);

        // delete packages remove course
        $DB->execute("UPDATE {block_edupublisher_packages} set course=0 WHERE course>0 and deleted>0");

        // trim the titles
        $DB->execute("UPDATE {block_edupublisher_packages} set title=TRIM(title) WHERE title LIKE ' %'");

        // update dauer
        $DB->execute("UPDATE {block_edupublisher_md_eta} set zeitbedarf='00:45' WHERE zeitbedarf='01:00'");
        $DB->execute("UPDATE {block_edupublisher_md_eta} set zeitbedarf='01:30' WHERE zeitbedarf='02:00'");
        $DB->execute("UPDATE {block_edupublisher_md_eta} set zeitbedarf='02:15' WHERE zeitbedarf='03:00'");

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2024102801, 'edupublisher');
    }

    if ($oldversion < 2024103000) {

        // Define field competencies to be added to block_edupublisher_pkg_items.
        $table = new xmldb_table('block_edupublisher_pkg_items');
        $field = new xmldb_field('competencies', XMLDB_TYPE_TEXT, null, null, false, null, null, 'sorting');

        // Conditionally launch add field competencies.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->execute("UPDATE {block_edupublisher_pkg_items} SET competencies = ''");

        $field = new xmldb_field('competencies', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'sorting');
        $dbman->change_field_notnull($table, $field);

        // Define index idx_competencies (not unique) to be added to block_edupublisher_pkg_items.
        $table = new xmldb_table('block_edupublisher_pkg_items');
        $index = new xmldb_index('idx_competencies', XMLDB_INDEX_NOTUNIQUE, ['competencies']);

        // Conditionally launch add index idx_competencies.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2024103000, 'edupublisher');
    }

    if ($oldversion < 2024112800) {

        // Define field time_submitted to be added to block_edupublisher_packages.
        $table = new xmldb_table('block_edupublisher_packages');
        $field = new xmldb_field('time_submitted', XMLDB_TYPE_INTEGER, '7', null, XMLDB_NOTNULL, null, '0', 'active');

        // Conditionally launch add field time_submitted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2024112800, 'edupublisher');
    }

    if ($oldversion < 2025010500) {

        // Define field tstamp to be added to block_edupublisher_packages.
        $table = new xmldb_table('block_edupublisher_packages');
        $field = new xmldb_field('tstamp', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0', 'created');

        // Conditionally launch add field tstamp.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->execute('UPDATE {block_edupublisher_packages} SET tstamp = modified');

        $field = new xmldb_field('tstamp', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, 'created');
        $dbman->change_field_default($table, $field);

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2025010500, 'edupublisher');
    }

    if ($oldversion < 2025012300) {

        // Define field is_vorschlag to be added to block_edupublisher_md_eta.
        $table = new xmldb_table('block_edupublisher_md_eta');
        $field = new xmldb_field('is_vorschlag', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'ltiurl');

        // Conditionally launch add field is_vorschlag.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Edupublisher savepoint reached.
        upgrade_block_savepoint(true, 2025012300, 'edupublisher');
    }

    return true;
}
