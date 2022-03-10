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
 * @copyright  2020 Center for Learningmanagement (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edupublisher;

defined('MOODLE_INTERNAL') || die;

class wordpress {
    /**
     * Should be called if any action was taken on a package.
     * @param type kind of action (created, published, unpublished, updated or deleted).
     * @param package the full package as object.
     */
    public static function action($type, $package) {
        $types = [ 'created', 'published', 'unpublished', 'updated', 'deleted'];
        if (!in_array($type, $types)) {
            return;
        }
        $email = get_config('block_edupublisher', 'wordpress_email_if_' . $type);
        if (empty($email)) {
            return;
        }

        global $CFG, $DB, $PAGE, $SITE;
        $PAGE->set_context(\context_system::instance());
        $package->moodlesitename = $SITE->fullname;
        $moodlecourseurl = new \moodle_url('/course/view.php', [ 'id' => $package->get('course') ]);
        $package->moodlecourseurl = $moodlecourseurl->__toString();
        $package->wpshortcodes = get_config('block_edupublisher', 'wordpress_shortcodes_if_' . $type);
        $channels = [];
        $_channels = explode(',', $package->get('channels'));
        foreach ($_channels as $channel) {
            if (!empty($channel) && !empty($package->get('published', $channel))) {
                $channels[] = $channel;
            }
        }
        $package->set(str_replace('{channels}', implode(' ', $channels), $package->get('wpshortcodes')), 'wpshortcode');

        $messagehtml = get_string('wordpress:notification:text_' . $type, 'block_edupublisher', $package->get_flattened());
        $messagetext = html_to_text($messagehtml);
        $subject = get_string('wordpress:notification:subject_' . $type , 'block_edupublisher', $package->get_flattened());

        $mail = get_mailer();
        $noreplyaddressdefault = 'noreply@' . get_host_from_url($CFG->wwwroot);
        $noreplyaddress = empty($CFG->noreplyaddress) ? $noreplyaddressdefault : $CFG->noreplyaddress;
        $mail->Sender = $noreplyaddress;

        $mail->From = $noreplyaddress;
        $mail->FromName = $SITE->fullname . ' block_edupublisher';

        $mail->addAddress($email);

        $mail->Subject = $subject;
        $mail->Encoding = 'quoted-printable';
        $mail->Body = "<html><body>$messagehtml</body></html>";
        $mail->AltBody = "\n$messagetext\n";

        if ($type != 'deleted') {
            $fs = get_file_storage();
            $context = \context_course::instance($package->get('course'));
            $files = $fs->get_area_files($context->id, 'block_edupublisher', 'default_image', $package->get('id'));
            foreach ($files as $file) {
                if (in_array($file->get_filename(), array('.'))) continue;
                $mail->addStringAttachment($file->get_content(), $file->get_filename(), 'base64', $file->get_mimetype());
            }
        }

        $mail->send();
    }
    /**
     * Add required settings to admin settings page.
     * @param settings the node settings are attached to.
    **/
    public static function admin_settings_page($settings) {
        global $ADMIN;
        if (empty($ADMIN) || !$ADMIN->fulltree) {
            return;
        }

        $heading = get_string('wordpress:settings', 'block_edupublisher');
        $text    = get_string('wordpress:settings:description', 'block_edupublisher');
        $settings->add(new \admin_setting_heading('block_edupublisher_wordpress', '', "<h3>$heading</h3><p>$text</p>"));

        $types = [ 'created', 'published', 'unpublished', 'updated', 'deleted'];
        foreach ($types as $type) {
            $heading = get_string('wordpress:settings:postif' . $type, 'block_edupublisher');
            $settings->add(new \admin_setting_heading('block_edupublisher_wordpress_' . $type, '', "<h4>$heading</h4>"));
            $settings->add(
                new \admin_setting_configtext(
                    'block_edupublisher/wordpress_email_if_' . $type,
                    get_string('wordpress:settings:email', 'block_edupublisher'),
                    '',
                    '',
                    PARAM_EMAIL
                )
            );
            $settings->add(
                new \admin_setting_configtextarea(
                    'block_edupublisher/wordpress_shortcodes_if_' . $type,
                    get_string('wordpress:settings:shortcodes', 'block_edupublisher'),
                    '',
                    '',
                    PARAM_TEXT
                )
            );
        }
    }
}
