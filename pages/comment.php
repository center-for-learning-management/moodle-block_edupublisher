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
 *             2020 onwards Center for Learningmanagement (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');

$id = optional_param('id', 0, PARAM_INT);
$packageid = optional_param('packageid', 0, PARAM_INT);
$perma = optional_param('perma', '', PARAM_TEXT);

$urlpath = $CFG->wwwroot . '/blocks/edupublisher/pages/comment.php';
$urlparams = [];
if (!empty($id))
    $urlparams['id'] = $id;
if (!empty($id))
    $urlparams['packageid'] = $packageid;
if (!empty($id))
    $urlparams['perma'] = $perma;
$url = new \moodle_url($urlpath, $urlparams);
$PAGE->set_url($url);

if (!empty($perma)) {
    $comment = $DB->get_record('block_edupublisher_comments', array('permahash' => $perma));
}
if (!empty($comment->package)) {
    // We loaded a permahash - only show this item!
    $id = $comment->id;
    $packageid = $comment->package;
} else {
    $packageid = required_param('packageid', PARAM_INT);
}

$package = \block_edupublisher\package::get_package($packageid, true);
if (!$package) {
    // No such package exists.
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('error'));
    $PAGE->set_heading(get_string('error'));
    //$PAGE->set_pagelayout('incourse');
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template(
        'block_edupublisher/alert',
        array(
            'content' => 'No such item',
            'type' => 'warning',
            'url' => $CFG->wwwroot . '/my',
        )
    );
    echo $OUTPUT->footer();
    die();
}

// Attention! Guest access will only be active, if the package was published by a moderator!
$PAGE->set_context($package->get_context());
\block_edupublisher\permissions::require_login($package->courseid);
$PAGE->navbar->add($package->get('title', 'default'), new moodle_url('/course/view.php', array('id' => $package->courseid)));
$PAGE->navbar->add(get_string('details', 'block_edupublisher'), new moodle_url('/blocks/edupublisher/pages/package.php', array('id' => $package->id)));
$PAGE->navbar->add(get_string('comments'), $PAGE->url);


$PAGE->set_title($package->get('title'));
$PAGE->set_heading($package->get('title'));
$PAGE->requires->css('/blocks/edupublisher/style/main.css');
$PAGE->requires->css('/blocks/edupublisher/style/ui.css');

echo $OUTPUT->header();

require_once($CFG->dirroot . '/blocks/edupublisher/classes/comment_create_form.php');
$form = new \block_edupublisher\comment_create_form();
if ($data = $form->get_data()) {
    $sendto = ['author', 'allmaintainers'];
    if (!empty($data->ispublic)) {
        $sendto[] = 'commentors';
    }
    $data->id = $package->store_comment($data->content['text'], $sendto, false, $data->ispublic, "", "");
    if (empty($data->id)) {
        echo "<p class=\"alert alert-error\">" . get_string('error') . "</p>";
    } else {
        echo "<p class=\"alert alert-success\">" . get_string('successfully_saved_comment', 'block_edupublisher') . "</p>";
    }
}
if (!empty($id)) {
    // Show single item
    $showsingle = true;
    $comments = array(
        $package->load_comment($id),
    );
    $package = new \block_edupublisher\package($comments[0]->package);
} else {
    $showsingle = false;
    $comments = $package->load_comments($package->get('canmoderate') || $package->userid == $USER->id, 'DESC');
}

if (!$showsingle && isloggedin() && !isguestuser($USER)) {
    $ctx = \context_user::instance($USER->id);
    $userpictureurl = $CFG->wwwroot . '/pluginfile.php/' . $ctx->id . '/user/icon';
    ?>
    <a href="#" class="btn btn-primary"
       onclick="require(['jquery'], function($) { $('#block_edupublisher_comment_form').toggleClass('hidden'); });">
        <?php echo get_string('reply', 'block_edupublisher'); ?>
    </a>
    <div class="hidden" style="border-radius: 15px; border: 2px solid darkblue; margin-bottom: 10px; overflow: hidden;" id="block_edupublisher_comment_form">
        <table border="0" width="100%">
            <tr>
                <!--
                <td valign="top" width="150" style="padding-left: 10px;">
                    <center>
                        <?php echo fullname($USER); ?><br />
                        <img src="<?php echo $userpictureurl; ?>" alt="User Icon" width="50" style="border-radius: 50%;" />
                    </center>
                </td>
                -->
                <td valign="top">
                    <?php
                    $form->set_data(
                        (object)array(
                            'package' => $packageid,
                            'packageid' => $packageid,
                        )
                    );
                    $form->display();
                    ?>
                </td>
            </tr>
        </table>
    </div>
    <?php
}

if (count($comments) == 0) {
    echo $OUTPUT->render_from_template(
        'block_edupublisher/alert',
        array(
            'content' => get_string('comment:none', 'block_edupublisher'),
            'url' => $CFG->wwwroot . '/blocks/edupublisher/pages/package.php?id=' . $packageid,
            'type' => 'warning',
        )
    );
} else {
    require_once($CFG->dirroot . '/blocks/edupublisher/block_edupublisher.php');
    foreach ($comments as $comment) {
        if ($comment->isautocomment) {
            if (!empty($comment->linkurl)) {
                $package->set($comment->linkurl->__toString(), 'commentlink');
            }
            $comment->content = get_string($comment->content, 'block_edupublisher', $package->get_flattened());
            $comment->content .= get_string('comment:notify:autotext', 'block_edupublisher', $package->get_flattened());
        }

        echo $OUTPUT->render_from_template(
            'block_edupublisher/package_comment',
            $comment
        );
    }
}

echo $OUTPUT->footer();
