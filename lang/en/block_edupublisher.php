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
 * @package   block_edupublisher
 * @copyright 2018 Digital Education Society (http://www.dibig.at)
 * @author    Robert Schrenk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'edupublisher';
$string['page:publisher'] = 'Publish';

$string['action'] = 'Action';
$string['after_section'] = 'Place after section:';
$string['allowguests'] = 'Open for guests';
$string['allowguests_desc'] = 'If this is on, guest users can access the search function.';
$string['allowsubcourses'] = 'Allow subcourses';
$string['allowsubcourses_desc'] = 'If enabled users can clone contents into a subcourse instead of the course itself.';
$string['as_subcourse'] = 'Import package as activity';
$string['as_subcourse:description'] = 'When this option is on, all imported contents will appear as activity in your target course. Otherwise you will get an standard import of all sections to your course.';
$string['by'] = 'by';
$string['cachedef_application'] = 'Application cache for  block_edupublisher';
$string['cachedef_ispackage'] = 'Stores whether a certain course is a learning resource.';
$string['cachedef_packagepath'] = 'This stores the path of visited packages in cache.';
$string['cachedef_publish'] = 'Stores whether a certain course is currently being published.';
$string['cachedef_restore'] = 'Stores certain data when importing courses in the users session.';
$string['category'] = 'Category';
$string['categorysubcourses'] = 'Category for subcourses';
$string['categorysubcourses:missing'] = 'Missing category for subcourses, an administrator needs to fix that.';
$string['category_help'] = 'Enter the category id here where published courses will be created';
$string['category_missing'] = 'This is a required field';
$string['channel'] = 'Channel';
$string['channels'] = 'Channels';
$string['clone_to_course'] = 'Copy to course:';
$string['clonecourse'] = 'Publish contents within this course';
$string['clonecourse_attention'] = '<strong>Attention:</strong>&nbsp;Refer to help text before you uncheck this!';
$string['clonecourse_help'] = '<strong>Attention:</strong>&nbsp;If you uncheck this box this course itself will be published. All users (even you) will be unenrolled from this course! That means, that user data may be removed unrecoverably!';
$string['comment'] = 'Comment';
$string['comment:evaluation:added'] = 'Hello,<br /><br />I just added an evaluation. It can be obtained under the following <a href="{$a->commentlink}" target="_blank">link</a>.<br /><br />Kind regards';
$string['comment:forchannel'] = 'This comment refers to channel "{$a->channel}".';
$string['comment:mail:subject'] = 'New comment on {$a->title}';
$string['comment:none'] = 'No comments so far';
$string['comment:notify:autotext'] = '<br /><br /><small>Attention: This text was generated automatically.</small>';
$string['comment:template:package_created'] = 'Hello,<br /><br />I just created the package "{$a->title}"!<br /><br />Kind regards';
$string['comment:template:package_editing_granted'] = 'Hello,<br /><br />I just granted you editing permissions for the package "{$a->title}"!<br /><br />Kind regards';
$string['comment:template:package_editing_sealed'] = 'Hello,<br /><br />I just sealed your package "{$a->title}"! If you need to modify it again please contact us!<br /><br />Kind regards';
$string['comment:template:package_published'] = 'Dear author,<br /><br />I published your package "{$a->title}"!<br /><br />Kind regards';
$string['comment:template:package_unpublished'] = 'Dear author,<br /><br />I unpublished your package "{$a->title}"!<br /><br />Kind regards';
$string['comment:template:package_updated'] = 'Hello,<br /><br />I just updated the package "{$a->title}"!<br /><br />Kind regards';
$string['coursebackup:importcomplete'] = 'Import completed!';
$string['coursebackup:missing'] = 'Backup for this course has not yet been created!';
$string['coursebackup:notfound'] = 'Backup for this course not found!';
$string['create_channel'] = 'create channel';

$string['commercial_header'] = 'Commercial content';
$string['commercial_fetchchannel'] = 'Fetch commercial-channel';
$string['commercial_publish_as'] = 'Publish commercial content';
$string['commercial_publisher'] = 'Publisher';
$string['commercial_trigger_active'] = 'Check this box to make this offering active!';
$string['commercial_shoplink'] = 'Shop-URL';
$string['commercial_shoplink_help'] = 'Required only if licence validation is set to "internal". Please enter the URL where a licence can be purchased.';
$string['commercial_validateexternal'] = 'external: Licence will be validated on an external site.';
$string['commercial_validateinternal'] = 'internal: Licence must exist before import.';
$string['commercial_validation'] = 'Licence validation';
$string['commercial_validation_help'] = '<strong>External validation:</strong> Content can always be imported to courses. Learning ressources remain on an external site. Validation of licence is done on the external site.<br /><strong>Internal:</strong> Content can be imported only if a licence has been stored in eduvidual (User-, Course-, oder Schoolcontext)';

$string['danubeai:apikey'] = 'Danube.ai API Key';
$string['danubeai:apikey:description'] = 'This is the API Key for danube.ai';

$string['default__mailsubject'] = 'eduPublisher-item handed in for inspection';
$string['default_header'] = 'eduvidual';
$string['default_authorname'] = 'Name of author';
$string['default_authorname_missing'] = 'Please enter a your name!';
$string['default_authormail'] = 'eMail of author';
$string['default_authormail_missing'] = 'Please enter a valid mailaddress!';
$string['default_authormailshow'] = 'Show eMail of author';
$string['default_coursecontents'] = 'Course contents';
$string['default_coursecontents_help'] = 'Course contents';
$string['default_fetchchannel'] = 'Fetch default-channel';
$string['default_origins'] = 'Origins';
$string['default_image'] = 'Image';
$string['default_image_help'] = 'Preview image to be shown.';
$string['default_image_label'] = 'Please choose an image that represents the topic of your package. Be aware of copyright! We recommend <a href="http://www.pixabay.com" target="_blank">pixabay.com</a> as source for images.';
$string['default_licence'] = 'Licence';
$string['default_licence_missing'] = 'You have to choose a licence';
$string['default_licenceother'] = 'Other licence';
$string['default_publish_as'] = 'Publish on this site';
$string['default_schoollevel'] = 'Schoollevel';
$string['default_schoollevel_primary'] = 'Primary level';
$string['default_schoollevel_secondary_1'] = 'Secondary 1';
$string['default_schoollevel_secondary_2'] = 'Secondary 2';
$string['default_schoollevel_tertiary'] = 'Tertiary';
$string['default_subjectarea'] = 'Subjectarea';
$string['default_subjectarea_arts'] = 'Arts';
$string['default_subjectarea_economics'] = 'Economics';
$string['default_subjectarea_geography'] = 'Geography';
$string['default_subjectarea_history'] = 'History';
$string['default_subjectarea_informatics'] = 'Informatics';
$string['default_subjectarea_languages'] = 'Languages';
$string['default_subjectarea_mathematics'] = 'Mathematics';
$string['default_subjectarea_naturalsciences'] = 'Natural sciences';
$string['default_subjectarea_other'] = '- Other -';
$string['default_subjectarea_philosophy'] = 'Philosophy';
$string['default_subjectarea_physicaleducation'] = 'Physical education';
$string['default_suppresscomment'] = 'Suppress comment';
$string['default_suppresscomment_help'] = 'Store without generating a comment.';
$string['default_tags'] = 'Tags';
$string['default_title'] = 'Title';
$string['default_trigger_active'] = 'Check this box to make package active!';
$string['default_summary'] = 'Summary';
$string['default_weblink'] = 'Weblink';

$string['defaultrolestudent'] = 'Default role of Students';
$string['defaultrolestudent:description'] = 'This role will be used by edupublisher to automatically enrol someone with student permissions';
$string['defaultrolestudent:missing'] = 'Missing Admin-Setting for default role of students';
$string['defaultroleteacher'] = 'Default role of Teachers';
$string['defaultroleteacher:description'] = 'This role will be used by edupublisher to automatically enrol someone with teacher permissions';
$string['defaultroleteacher:missing'] = 'Missing Admin-Setting for default role of teachers';

$string['derivative'] = 'Derivative';
$string['details'] = 'Details';

$string['edupublisher:addinstance'] = 'Add edupublisher-block';
$string['edupublisher:cancreategroups'] = 'Can create groups';
$string['edupublisher:canevaluate'] = 'Can evaluate';
$string['edupublisher:canuse'] = 'Can use edupublisher';
$string['edupublisher:canseeevaluation'] = 'Can see evaluations';
$string['edupublisher:canselfenrol'] = 'Can self enrol to eduPublisher courses';
$string['edupublisher:manage'] = 'Manage block instance';
$string['edupublisher:managedefault'] = 'Manage default-items';
$string['edupublisher:manageeduthek'] = 'Manage eduthek-items';
$string['edupublisher:manageetapas'] = 'Manage eTapas';
$string['edupublisher:myaddinstance'] = 'Add edupublisher-block to Dashboard';

$string['eduthek__mailsubject'] = 'eduthek-item handed in for inspection';
$string['eduthek_curriculum'] = 'Curriculum';
$string['eduthek_educationallevel'] = 'Educational level';
$string['eduthek_fetchchannel'] = 'Fetch eduthek-Channel';
$string['eduthek_header'] = 'eduthek';
$string['eduthek_language'] = 'Language';
$string['eduthek_lticartridge'] = 'LTI cartridge';
$string['eduthek_ltisecret'] = 'LTI secret';
$string['eduthek_ltiurl'] = 'LTI URL';
$string['eduthek_publish_as'] = 'Publish in eduthek';
$string['eduthek_schooltype'] = 'Schooltype';
$string['eduthek_topic'] = 'Topic';
$string['eduthek_trigger_active'] = 'Check this box to publish this in eduthek!';
$string['eduthek_type'] = 'Type';

$string['enablecommercial'] = 'Enable commercial content';
$string['enablecommercial_desc'] = 'With this checkbox you can globally control if commercial packages can be found in edupublisher.';
$string['enrolment:meta:notinstalled'] = 'The meta-enrolment plugin is not installed but required.';
$string['enrolment:meta:active'] = 'The meta-enrolment plugin is not active but required.';

$string['etapas__description'] = 'The eTapas-Initiative driven by eEducation Austria allows teachers to hand in their learning sequences as Open Educational Resource and get a reward. For more information please refer to the <a href="https://www.eeducation.at/?id=602" target="_blank">eEducation Website</a>.';
$string['etapas__mailsubject'] = 'eTapa handed in for inspection';
$string['etapas_erprobungen'] = 'Inspections';
$string['etapas_fetchchannel'] = 'Fetch eTapas-Channel';
$string['etapas_gegenstand'] = 'matter';
$string['etapas_header'] = 'eTapa';
$string['etapas_lticartridge'] = 'LTI cartridge';
$string['etapas_ltisecret'] = 'LTI secret';
$string['etapas_ltiurl'] = 'LTI URL';
$string['etapas_publish_as'] = 'Publish as eTapa';
$string['etapas_kompetenzen'] = 'Competencies';
$string['etapas_kompetenzen_help'] = '<p class="alert alert-danger">The linking of resources from the moodle course with competencies is essential, when you want to publish an eTapa. Please use either the Moodle competencies or exacomp.</p>';
$string['etapas_kompetenzen_missing'] = 'Please add competencies to activities and resources';
$string['etapas_vonschule'] = 'From school';
$string['etapas_schulstufe'] = 'Academic year';
$string['etapas_status'] = 'Status';
$string['etapas_status_inspect'] = 'Inspect';
$string['etapas_status_eval'] = 'Evaluate';
$string['etapas_status_public'] = 'Public';
$string['etapas_stundenablauf'] = 'Lessonplan';
$string['etapas_subtype'] = 'Subtype';
$string['etapas_trigger_active'] = 'Check this box to make eTapa active!';
$string['etapas_type'] = 'Type';
$string['etapas_voraussetzungen'] = 'Prerequisites';
$string['etapas_vorkenntnisse'] = 'Prior knowledge';
$string['etapas_zeitbedarf'] = 'duration';

$string['export'] = 'Export';
$string['externalsources'] = 'External sources';
$string['externalsources:courseformat'] = 'Default course format';
$string['externalsources:courseformat:description'] = 'Default course format when creating courses for external sources.';

$string['fieldextras'] = 'Extras';
$string['fieldhelptext'] = 'Helptext';
$string['fieldname'] = 'Name';
$string['fieldtype'] = 'Type';

$string['go_back_to_dashboard'] = 'Go back zu Dashboard';
$string['groups:create'] = 'Create group';
$string['groups:create:error'] = 'Group "{$a->name}" was <strong>not</strong> successfully created!';
$string['groups:create:success'] = 'Group "{$a->name}" was successfully created!';
$string['groups:domains'] = 'E-Maildomains';
$string['groups:domains_desc'] = 'Users can be either granted access by using the capability "<i>block/edupublisher:cancreategroups</i>" or if the users e-mail has a particular domain. You can specify various domains line by line. Each domain <strong>must</strong> start with a "@"-sign!';
$string['groups:enabled'] = 'Enable group creation';
$string['groups:enabled_desc'] = 'This turns the whole feature on and off.';
$string['groups:login_other_account'] = 'Login with another user account';
$string['groups:longtext'] = 'Creating a group allows teachers to see and interact with their students. This ensures learning monitoring and progress control without the need to create and fill your own courses.<br /><br />Share the "<strong>URL for teachers</strong>" with other teachers or follow this URL if you want to use a second user account as a teacher. You can give the "<strong>URL for students</strong>" to your students so that they can join your group. Click on the QR code to enlarge it.';
$string['groups:name'] = 'Name of group';
$string['groups:no_permission'] = 'Only teachers are allowed to create groups within learning packages. It seems that your account does not have the required capability. Please switch to an authorized account!';
$string['groups:no_permission_domains'] = 'In addition to the usual role system, a user account have an e-mail with one of the following domains is also appropriate:';
$string['groups:not_member'] = 'You are no member of this group!';
$string['groups:remove:title'] = 'Confirm removal';
$string['groups:remove:text'] = 'Do you really want to remove this group? You will loose access to your students data!';
$string['groups:rolestudent'] = 'Student-Role';
$string['groups:rolestudent_desc'] = 'Specify which role shall be assigned to students.';
$string['groups:roleteacher'] = 'Teacher-Role';
$string['groups:roleteacher_desc'] = 'Specify which role shall be assigned to teachers.';
$string['groups:settings'] = 'Group creation';
$string['groups:settings:description'] = 'Allow creation of groups in learning packages.';
$string['groups:urlstudent'] = 'URL for students';
$string['groups:urlteacher'] = 'URL for teachers';
$string['guest_not_allowed'] = 'Guests are not allowed to do this!';

$string['initialize_import'] = 'Clone package';
$string['invalid_evaluation'] = 'Invalid evaluation id';
$string['issued_by_user'] = 'Published by';

$string['licence'] = 'Licence';
$string['licence_amount'] = 'Amount';
$string['licence_amount_hint'] = 'Hint: -1 means infinite usages';
$string['licence_amount_infinite'] = 'Infinite usage';
$string['licence_amount_none'] = 'No usage at all';
$string['licence_amount_usages'] = 'Number of usages: {$a->amount}';
$string['licence_already_redeemed'] = 'Licence already redeemed!';
$string['licence_back_to_dashboard'] = 'Back to licence-dashboard';
$string['licence_check_ok'] = 'All licence-keys have been checked and can be created!';
$string['licence_collection'] = 'Collection';
$string['licence_collection_desc'] = 'The "Collection" allows users to import every package that is covered by this licence into a course as much times as you specify per package.<br /><br /><strong>Example:</strong> The licence covers 5 packages, each package is set to amount of 2 --> each package of this collection can be imported to a course 2 times.';
$string['licence_created_successfully'] = 'Licences created successfully!';
$string['licence_generate'] = 'Generate Licences';
$string['licence_generatekeys'] = 'Create licence-keys';
$string['licence_invalid'] = 'Invalid licencekey!';
$string['licence_manage'] = 'Manage Licences';
$string['licence_packages'] = 'Packages';
$string['licence_paste_alternatively'] = 'Alternatively you can paste existing licence-keys!';
$string['licence_pool'] = 'Pool';
$string['licence_pool_desc'] = 'The "Pool" allows users to import any package that is covered by this licence as much times as you specify per licence.<br /><br /><strong>Example:</strong> The licence covers 20 packages, the amount of imports in this licence is set to 5 --> the user can do 5 imports out of these packages into a course.';
$string['licence_redeem'] = 'Redeem licence';
$string['licence_target'] = 'Target';
$string['licence_target_course'] = 'Course';
$string['licence_target_course_desc'] = 'The licence is attached to a course, which is more ore less synonymous with a classroom.';
$string['licence_target_desc'] = 'This specifies to which entity a licence is bound.';
$string['licence_target_org'] = 'Organisation';
$string['licence_target_org_desc'] = 'The licence is attached to an organisation, every user of this organisation can redeem this licence.';
$string['licence_target_user'] = 'User';
$string['licence_target_user_desc'] = 'The licence is attached to a user, the user can redeem this licence in any course.';
$string['licence_type'] = 'Type';
$string['licences'] = 'Licences';


$string['lti'] = 'LTI';
$string['lti_data'] = 'LTI data';
$string['mail_template'] = 'Template for sending emails';
$string['mail_template:description'] = 'You can change the template for sending emails here. This should represent a hole HTML-Page. Any occurence of the string {{{subject}}} will be replace by the subject, {{{content}}} with the content.';
$string['manage'] = 'manage';
$string['missing_capability'] = 'No capability to use edupublisher!';
$string['name'] = 'Name';
$string['no_such_package'] = 'No such package';
$string['oer_header'] = 'Open Educational Resources';
$string['only_viewing_enrol_to_user'] = 'You are only viewing this course. Therefore some activities may not work as expected and your learning progress will not be stored. If you want to have full functionality, please enrol to this course!';
$string['only_viewing_enrol_button'] = 'Yes, enrol me to that course!';
$string['only_viewing_enrol_as_student'] = 'Yes, enrol me to that course as student!';
$string['only_viewing_enrol_as_teacher'] = 'Yes, enrol me to that course as maintainer!';
$string['only_viewing_unenrol_button'] = 'Yes, unenrol me from that course!';
$string['only_viewing_unenrol_as_student'] = 'Yes, unenrol me from that course as student!';
$string['only_viewing_unenrol_as_teacher'] = 'Yes, unenrol me from that course as maintainer!';
$string['overview'] = 'Overview';
$string['package'] = 'Package';
$string['parts_based_upon'] = 'Parts based upon';
$string['parts_published'] = 'Parts published as';
$string['pending_publication'] = 'Pending publication of course {$a->courseid}';
$string['permalink'] = 'Permalink';
$string['permission_denied'] = 'Permission denied';

$string['privacy:metadata'] = 'This plugin does not store any personal data';
$string['privacy:export:comments'] = 'Comments';
$string['privacy:export:evaluatio'] = 'Evaluations';
$string['privacy:export:lic'] = 'Licences';
$string['privacy:export:log'] = 'Log';
$string['privacy:export:packages'] = 'Users packages';
$string['privacy:export:pub_user'] = 'Publisher users';
$string['privacy:export:rating'] = 'Ratings';
$string['privacy:export:uses'] = 'Used packages';

$string['public'] = 'Public';
$string['publish_new_package'] = 'Publish something';
$string['publish_new_package_proceed'] = 'Proceed publication process';
$string['publish_missing_sourcecourseid'] = 'Cannot proceed without source course!';
$string['publish_proceed_label'] = '
    <h3>Publish resources</h3>
    <p>
        This course has been created in regard of the publication process
        of eduPublisher. You can proceed the publication process using the
        following link.
    </p>
    <a href="{$a->wwwroot}/blocks/edupublisher/pages/publish.php?sourcecourseid={$a->sourcecourseid}" class="btn btn-primary">
        Proceed with publication
    </a>
';
$string['publish_stage_confirm'] = 'Start';
$string['publish_stage_confirm_text'] = '
    <p>
        Using this feature, you can publish parts of your course as open
        educational resource. The publishing process consists of four stages:
    </p>
    <ol>
        <li>Confirm that you wish to publish some parts of your course.</li>
        <li>
            Select the activities and resources you want to share.
            These will be automatically imported into a fresh course without any user data.
        </li>
        <li>Fill in the metadata that describe your learning package.</li>
        <li>Our editorial team unlocks the learning package.</li>
    </ol>';
$string['publish_stage_confirm_button'] = 'Ok, proceed!';
$string['publish_stage_import'] = 'Select resources';
$string['publish_stage_metadata'] = 'Enter metadata';
$string['publish_stage_finish'] = 'Finish';
$string['publish_stage_finish_text'] = '
    <h3>Publication finished</h3>
    <p>
        Thank you very much for publishing this as open educational resource!
    </p>
    <p>
        Our editorial team will have a look and publish this learning package soon.
    </p>';
$string['publish_stage_finish_button'] = 'Zum Lernpaket';
$string['publisher'] = 'Publisher';
$string['publisher_logo'] = 'Logo';
$string['rating'] = 'Rating';
$string['relevance:stage_0'] = 'Possibly relevant';
$string['relevance:stage_1'] = 'Less relevant';
$string['relevance:stage_2'] = 'Relevant';
$string['relevance:stage_3'] = 'Very relevant';
$string['removal:title'] = 'Remove package';
$string['removal:text'] = 'Do you really want to remove package #{$a->id} {$a->title}?';
$string['remove_everything'] = 'Do you really want to remove all packages on this Moodle-Instance? (Courses are removed as well)';
$string['removed_everything'] = 'Removed all packages';
$string['removed_package'] = 'Removed package #{$a->id} {$a->title}';
$string['removing_package_course'] = 'Removing course of package #{$a->id} {$a->title}';
$string['reply'] = 'Reply';
$string['resource_catalogue'] = 'Resource catalogue';
$string['search'] = 'Search';
$string['search_in_edupublisher'] = 'Search in eduPublisher';
$string['search:enter_term'] = 'Please enter your search term(s)';
$string['search:noresults'] = 'Sorry, nothing found';
$string['self_enrol'] = 'Self enrolment';
$string['self_enrol_confirm_text'] = 'You are enrolling in a course. That means that your learning process will be stored. Other learners within that course may see your name in particular activities. Learning data, e.g. grades in a quiz, will not be visible to other learners.';
$string['self_unenrol'] = 'Unenrol';
$string['self_unenrol_confirm_text'] = 'If you unenrol from a course your learning progress and data may be removed from the course. If you enrol again your data may be lost and unrecoverable. Are you sure that you want to unenrol?';
$string['send_email_failed'] = 'Sending an email to "{$a->email}" failed.';
$string['settings'] = 'Settings';
$string['subcourse'] = 'Subcourse {$a->fullname}';
$string['successfully_enrolled'] = 'Successfully enrolled';
$string['successfully_published_package'] = 'Successfully published package';
$string['successfully_saved_comment'] = 'Successfully saved comment';
$string['successfully_saved_evaluation'] = 'Successfully saved evaluation';
$string['successfully_saved_package'] = 'Successfully saved package';
$string['successfully_saved_settings'] = 'Successfully saved settings';
$string['successfully_unenrolled'] = 'Successfully unenrolled';
$string['summary'] = 'Summary';
$string['task:coursebackup:title'] = 'Backup courses';
$string['task:externalsources:title'] = 'External sources';
$string['title'] = 'Title';
$string['title_missing'] = 'Missing title';
$string['trigger_editing_permission_grant'] = 'Grant write permission to author';
$string['trigger_editing_permission_remove'] = 'Remove write permission for author';
$string['type'] = 'Type';
$string['votes'] = 'Vote(s)';

$string['wordpress:notification:subject_created'] = 'A new learning package "{$a->title}" was created!';
$string['wordpress:notification:subject_published'] = 'A new learning package "{$a->title}" was published!';
$string['wordpress:notification:subject_unpublished'] = '{$a->title} was unpublished!';
$string['wordpress:notification:subject_updated'] = '{$a->title} was updated!';
$string['wordpress:notification:subject_deleted'] = '{$a->title} was deleted!';

$string['wordpress:notification:text_created'] = '
    <p>Dear users,</p>
    <p>a new learning package with the title <a href="{$a->moodlecourseurl}" target="_blank">{$a->title}</a> was created. It will be published soon, so please stay tuned!</p>
    <p>The description of this learning package is as follows:</p>
    <p>{$a->default_summary}</p>
    <p>Best regards,<br />Your team of {$a->moodlesitename}</p>
    {$a->wpshortcodes}';
$string['wordpress:notification:text_published'] = '
    <p>Dear users,</p>
    <p>a new learning package with the title <a href="{$a->moodlecourseurl}" target="_blank">{$a->title}</a> was published.</p>
    <p>The description of this learning package is as follows:</p>
    <p>{$a->default_summary}</p>
    <p>Best regards,<br />Your team of {$a->moodlesitename}</p>
    {$a->wpshortcodes}';
$string['wordpress:notification:text_unpublished'] = '
    <p>Dear users,</p>
    <p>the learning package with the title <a href="{$a->moodlecourseurl}" target="_blank">{$a->title}</a> was unpublished.</p>
    <p>Best regards,<br />Your team of {$a->moodlesitename}</p>
    {$a->wpshortcodes}';
$string['wordpress:notification:text_updated'] = '
    <p>Dear users,</p>
    <p>the data of the learning package with the title <a href="{$a->moodlecourseurl}" target="_blank">{$a->title}</a> was update.</p>
    <p>Best regards,<br />Your team of {$a->moodlesitename}</p>
    {$a->wpshortcodes}';
$string['wordpress:notification:text_deleted'] = '
    <p>Dear users,</p>
    <p>unfortunately, we had to remove the learning package <a href="{$a->moodlecourseurl}" target="_blank">{$a->title}</a>.</p>
    <p>Best regards,<br />Your team of {$a->moodlesitename}</p>
    {$a->wpshortcodes}';

$string['wordpress:settings'] = 'Wordpress settings';
$string['wordpress:settings:description'] = 'For each kind of action, you can set a particular e-mail address to which a notification will be sent. If it is empty, no e-mail will be send whatsover. Also, for each kind of action you can customize shortcodes. Shortcodes can be used to customize how wordpress handles your posts. Please refer to the following <a href="https://wordpress.com/support/post-by-email/" target="_blank">page</a>.';
$string['wordpress:settings:email'] = 'E-Mail';
$string['wordpress:settings:postifcreated'] = 'Post if created';
$string['wordpress:settings:postifpublished'] = 'Post if published';
$string['wordpress:settings:postifunpublished'] = 'Post if unpublished';
$string['wordpress:settings:postifupdated'] = 'Post if updated';
$string['wordpress:settings:postifdeleted'] = 'Post if deleted';
$string['wordpress:settings:shortcodes'] = 'Shortcodes';

$string['evaluation_by'] = 'Evaluation of {$a->fullname}';
$string['evaluation_introtext'] = 'Prior to making an evaluation, you should have tested the resource with an actual class and real students. Please share your insights into the quality and the improvements made to the learning process with others by filling out the linked evaulation form.';
$string['evaluation_none'] = 'No evaluation was made by now!';
$string['etapas_evaluation'] = 'eTapa evaluation';
$string['required'] = 'This is a required field';
$string['max_length'] = 'Maximum length reached.';
$string['evaluated_on'] = 'Date of evaluation';
$string['evaluated_verytrue'] = 'Very applicable';
$string['evaluated_nottrue'] = 'Very inaccurate';
$string['school'] = 'Location of evaluation';
$string['name_of_school'] = 'Name of the school';
$string['rating_coherent'] = 'The eTapa\'s description is coherent and comprehensible';
$string['rating_plausible'] = 'The eTapa\'s workflow is plausible and suitable for teaching';
$string['rating_preconditions'] = 'The eTapa\'s context and preconditions are reasonable';
$string['rating_content'] = 'The eTapa\'s content is technically correct and suitable for the target group';
$string['reason'] = 'Suggestions';
$string['technology'] = 'Effect of technology';
$string['technology_help'] = 'Based on the SAMR model (Substitution, Augmentation, Modification, Redefinition)';
$string['substitution'] = 'No added value to learning. This learning also works analoguous.';
$string['augmentation'] = 'There was moderate added value to learning.';
$string['modification'] = 'Learning tasks have been defined that would not be possible without technology.';
$string['redefinition'] = 'Through technology, new pedagogical methods have been implemented.';
$string['feedback'] = 'Feedback to the author';
$string['evaluations']  = 'Evaluations';
$string['evaluation_general'] = 'General estimation';
$string['evaluate'] = 'Evaluate';
