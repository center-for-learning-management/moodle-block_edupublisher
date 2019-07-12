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
$string['after_section'] = 'Place after section';
$string['by'] = 'by';
$string['category'] = 'Category';
$string['category_help'] = 'Enter the category id here where published courses will be created';
$string['category_missing'] = 'This is a required field';
$string['channel'] = 'Channel';
$string['channels'] = 'Channels';
$string['clonecourse'] = 'Publish contents within this course';
$string['clonecourse_attention'] = '<strong>Attention:</strong>&nbsp;Refer to help text before you uncheck this!';
$string['clonecourse_help'] = '<strong>Attention:</strong>&nbsp;If you uncheck this box this course itself will be published. All users (even you) will be unenrolled from this course! That means, that user data may be removed unrecoverably!';
$string['comment'] = 'Comment';
$string['comment:mail:subject'] = 'New comment on {$a->title}';
$string['comment:none'] = 'No comments so far';
$string['comment:notify:autotext'] = '<br /><br /><small>Attention: This text was generated automatically.</small>';
$string['comment:template:package_created'] = 'Hello,<br /><br />I just created the package "{$a->title}"!<br /><br />Kind regards';
$string['comment:template:package_editing_granted'] = 'Hello,<br /><br />I just granted you editing permissions for the package "{$a->title}"!<br /><br />Kind regards';
$string['comment:template:package_editing_sealed'] = 'Hello,<br /><br />I just sealed your package "{$a->title}"! If you need to modify it again please contact us!<br /><br />Kind regards';
$string['comment:template:package_published'] = 'Dear author,<br /><br />I published your package "{$a->title}"!<br /><br />Kind regards';
$string['comment:template:package_unpublished'] = 'Dear author,<br /><br />I unpublished your package "{$a->title}"!<br /><br />Kind regards';
$string['comment:template:package_updated'] = 'Hello,<br /><br />I just updated the package "{$a->title}"!<br /><br />Kind regards';
$string['create_channel'] = 'create channel';

$string['commercial_header'] = 'Commercial Content';
$string['commercial_fetchchannel'] = 'Fetch Commercial-Channel';
$string['commercial_publish_as'] = 'Publish commercial content';
$string['commercial_publisher'] = 'Publisher';
$string['commercial_trigger_active'] = 'Check this box to make this offering active!';
$string['commercial_shoplink'] = 'Shop-URL';
$string['commercial_shoplink_help'] = 'Required only if licence validation is set to "internal". Please enter the URL where a licence can be purchased.';
$string['commercial_validateexternal'] = 'external: Licence will be validated on an external site.';
$string['commercial_validateinternal'] = 'internal: Licence must exist before import.';
$string['commercial_validation'] = 'Licence validation';
$string['commercial_validation_help'] = '<strong>External Validation:</strong> Content can always be imported to courses. Learning ressources remain on an external site. Validation of licence is done on the external site.<br /><strong>Internal:</strong> Content can be imported only if a licence has been stored in eduvidual (User-, Course-, oder Schoolcontext)';

$string['default__mailsubject'] = 'eduPublisher-Item handed in for inspection';
$string['default_header'] = 'eduvidual';
$string['default_authorname'] = 'Name of author';
$string['default_authorname_missing'] = 'Please enter a your name!';
$string['default_authormail'] = 'eMail of author';
$string['default_authormail_missing'] = 'Please enter a valid mailaddress!';
$string['default_authormailshow'] = 'Show eMail of author';
$string['default_coursecontents'] = 'Course contents';
$string['default_coursecontents_help'] = 'Course contents';
$string['default_fetchchannel'] = 'Fetch Default-Channel';
$string['default_origins'] = 'Origins';
$string['default_image'] = 'Image';
$string['default_image_help'] = 'Preview image to be shown.';
$string['default_image_label'] = 'Please choose an image that represents the topic of your package. Be aware of copyright! We recommend <a href="http://www.pixabay.com" target="_blank">pixabay.com</a> as source for images.';
$string['default_licence'] = 'Licence';
$string['default_licence_missing'] = 'You have to choose a licence';
$string['default_licenceother'] = 'Other';
$string['default_publish_as'] = 'Publish on this site';
$string['default_title'] = 'Title';
$string['default_trigger_active'] = 'Check this box to make package active!';
$string['default_summary'] = 'Summary';
$string['default_weblink'] = 'Weblink';

$string['defaultrolestudent'] = 'Default role of Students';
$string['defaultrolestudent:description'] = 'This role will be used by edupublisher to automatically enrol someone with student permissions';
$string['defaultroleteacher'] = 'Default role of Teachers';
$string['defaultroleteacher:description'] = 'This role will be used by edupublisher to automatically enrol someone with teacher permissions';

$string['derivative'] = 'Derivative';
$string['details'] = 'Details';

$string['edupublisher:addinstance'] = 'Add block instance';
$string['edupublisher:canuse'] = 'Can use edupublisher';
$string['edupublisher:manage'] = 'Manage block instance';
$string['edupublisher:managedefault'] = 'Manage Default-Items';
$string['edupublisher:manageeduthek'] = 'Manage eduthek-Items';
$string['edupublisher:manageetapas'] = 'Manage eTapas';
$string['edupublisher:myaddinstance'] = 'Add block instance to Dashboard';

$string['eduthek__mailsubject'] = 'eduthek-Item handed in for inspection';
$string['eduthek_curriculum'] = 'Curriculum';
$string['eduthek_educationallevel'] = 'Educational Level';
$string['eduthek_fetchchannel'] = 'Fetch eduthek-Channel';
$string['eduthek_header'] = 'eduthek';
$string['eduthek_language'] = 'Language';
$string['eduthek_lticartridge'] = 'LTI Cartridge';
$string['eduthek_ltisecret'] = 'LTI Secret';
$string['eduthek_ltiurl'] = 'LTI URL';
$string['eduthek_publish_as'] = 'Publish in eduthek';
$string['eduthek_schooltype'] = 'Schooltype';
$string['eduthek_topic'] = 'Topic';
$string['eduthek_trigger_active'] = 'Check this box to publish this in eduthek!';
$string['eduthek_type'] = 'Type';

$string['enablecommercial'] = 'Enable commercial content';
$string['enablecommercial_desc'] = 'With this checkbox you can globally allow or prevent commercial packages from being published in eduPublisher. <strong>Note: </strong> This only applies to new publications. Existing publications will not be affected!';

$string['etapas__description'] = 'The eTapas-Initiative driven by eEducation Austria allows teachers to hand in their learning sequences as Open Educational Resource and get a reward. For more information please refer to the <a href="https://www.eeducation.at/?id=602" target="_blank">eEducation Website</a>.';
$string['etapas__mailsubject'] = 'eTapa handed in for inspection';
$string['etapas_erprobungen'] = 'Inspections';
$string['etapas_fetchchannel'] = 'Fetch eTapas-Channel';
$string['etapas_header'] = 'eTapa';
$string['etapas_lticartridge'] = 'LTI Cartridge';
$string['etapas_ltisecret'] = 'LTI Secret';
$string['etapas_ltiurl'] = 'LTI URL';
$string['etapas_publish_as'] = 'Publish as eTapa';
$string['etapas_kompetenzen'] = 'Competencies';
$string['etapas_vonschule'] = 'From School';
$string['etapas_schulstufe'] = 'Academic year';
$string['etapas_status'] = 'Status';
$string['etapas_status_inspect'] = 'Inspect';
$string['etapas_status_eval'] = 'Evaluate';
$string['etapas_status_public'] = 'Public';
$string['etapas_stundenablauf'] = 'Lessonplan';
$string['etapas_subtype'] = 'Subtype';
$string['etapas_trigger_active'] = 'Check this box to make eTapa active!';
$string['etapas_voraussetzungen'] = 'Prerequisites';
$string['etapas_vorkenntnisse'] = 'Prior knowledge';
$string['etapas_zeitbedarf'] = 'duration';

$string['export'] = 'Export';

$string['fieldextras'] = 'Extras';
$string['fieldhelptext'] = 'Helptext';
$string['fieldname'] = 'Name';
$string['fieldtype'] = 'Type';
$string['go_back_to_dashboard'] = 'Go back zu Dashboard';
$string['initialize_import'] = 'Use package';
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
$string['name'] = 'Name';
$string['no_such_package'] = 'No such package';
$string['overview'] = 'Overview';
$string['package'] = 'Package';
$string['parts_based_upon'] = 'Parts based upon';
$string['parts_published'] = 'Parts published as';
$string['permalink'] = 'Permalink';
$string['permission_denied'] = 'Permission denied';
$string['public'] = 'Public';
$string['publish_new_package'] = 'Publish something';
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
$string['search'] = 'Search';
$string['search_in_edupublisher'] = 'Search in eduPublisher';
$string['search:enter_term'] = 'Please enter your search term(s)';
$string['search:noresults'] = 'Sorry, nothing found';
$string['settings'] = 'Settings';
$string['successfully_published_package'] = 'Successfully published package';
$string['successfully_saved_comment'] = 'Successfully saved comment';
$string['successfully_saved_package'] = 'Successfully saved package';
$string['successfully_saved_settings'] = 'Successfully saved settings';
$string['summary'] = 'Summary';
$string['title'] = 'Title';
$string['title_missing'] = 'Missing title';
$string['trigger_editing_permission_grant'] = 'Grant write permission to author';
$string['trigger_editing_permission_remove'] = 'Remove write permission for author';
$string['type'] = 'Type';
$string['votes'] = 'Vote(s)';

$string['etapas_evaluation'] = 'eTapas Evaluation';
$string['required'] = 'This is a required field';
$string['max_length'] = 'Maximum length reached.';
$string['author_contact'] = 'The author(s) may contact you for further information';
$string['evaluated_on'] = 'The eTapa was evaluated on';
$string['school'] = 'The eTapa was evaluated at';
$string['name_of_school'] = 'name of the school';
$string['rating_coherent'] = 'The eTapa\'s description is coherent and comprehensible';
$string['rating_plausible'] = 'The eTapa\'s workflow is plausible and suitable for teaching';
$string['rating_preconditions'] = 'The eTapa\'s context and preconditions are reasonable';
$string['rating_content'] = 'The eTapa\'s content is technically correct and suitable for the target group';
$string['reason'] = 'Please specify what needs to be improved to get a 5 star rating for the questions above';
$string['technology'] = 'By applying technology, the exercice was';
$string['substitution'] = 'not changed. It would have worked without technology (substition)';
$string['augmentation'] = 'improved in a way that technology provided an added value (augmentation)';
$string['modification'] = 'drastically changed so that it would have been hard without technology (modification)';
$string['redefinition'] = 'completely changed so that it would have been impossible without technology (redefinition)';
$string['feedback'] = 'General feedback to the authors';
$string['evaluator_first_name'] = 'Evaluator first name';
$string['evaluator_last_name'] = 'Evaluator last name';
$string['evaluator_email'] = 'Evaluator email';

