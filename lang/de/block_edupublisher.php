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

$string['pluginname'] = 'eduPublisher';
$string['page:publisher'] = 'veröffentlichen';

$string['action'] = 'Aktion';
$string['by'] = 'von';
$string['category'] = 'Kategorie';
$string['category_help'] = 'Geben Sie hier die Kategorie ein, in der veröffentlichte Kurse hinterlegt werden sollen.';
$string['category_missing'] = 'Die Kategorie ist erforderlich';
$string['channel'] = 'Kanal';
$string['channels'] = 'Kanäle';
$string['comment'] = 'Comment';

$string['comment:mail:subject'] = 'Neuer Kommentar zu Paket {$a->title}';
$string['comment:none'] = 'Bisher keine Kommentare';
$string['comment:notify:autotext'] = '<br /><br /><small>Achtung: dieser Text wurde automatisch generiert.</small>';
$string['comment:template:package_created'] = 'Hallo,<br /><br />Ich habe gerade ein Paket mit dem Titel {$a->title} erstellt!<br /><br />Liebe Gr&uuml;&szlig;e';
$string['comment:template:package_editing_granted'] = 'Hallo,<br /><br />Ich habe Ihnen gerade Änderungsrechte im Paket "{$a->title}" verliehen!<br /><br />Liebe Gr&uuml;&szlig;e';
$string['comment:template:package_editing_sealed'] = 'Hallo,<br /><br />Ich habe gerade das Paket "{$a->title}" versiegelt! Um weitere &Auml;nderungen vornehmen zu k&ouml;nnen kontaktieren Sie uns bitte!<br /><br />Liebe Gr&uuml;&szlig;e';
$string['comment:template:package_published'] = 'Lieber Autor, liebe Autorin,<br /><br />Ich habe dein/ihr Paket {$a->title} soeben veröffentlicht!<br /><br />Liebe Gr&uuml;&szlig;e';
$string['comment:template:package_unpublished'] = 'Lieber Autor, liebe Autorin,<br /><br />Ich habe dein/ihr Paket {$a->title} soeben auf verborgen gesetzt!<br /><br />Liebe Gr&uuml;&szlig;e';
$string['comment:template:package_updated'] = 'Hallo,<br /><br />Ich habe gerade das Paket mit dem Titel {$a->title} aktualisiert!<br /><br />Liebe Gr&uuml;&szlig;e';
$string['create_channel'] = 'Kanal erstellen';

$string['default__mailsubject'] = 'eduPublisher-Item eingereicht';
$string['default_header'] = 'eduvidual';
$string['default_authorname'] = 'Autor';
$string['default_authorname_missing'] = 'Bitte geben Sie den Namen des Autors/der Autorin/nen ein!';
$string['default_authormail'] = 'Kontakt e-Mail';
$string['default_authormail_missing'] = 'Bitte geben Sie eine gültige Mailadresse ein!';
$string['default_coursecontents'] = 'Kursinhalte';
$string['default_coursecontents_help'] = 'Kursinhalte';
$string['default_fetchchannel'] = 'Default-Kanal abrufen';
$string['default_origins'] = 'Abgeleitetes Werk von';
$string['default_image'] = 'Bild';
$string['default_image_help'] = 'Vorschaubild für Anzeige';
$string['default_image_label'] = 'Bitte wählen Sie ein aussagekräftiges Bild für Ihren Inhalt. Beachten Sie das Urheberrecht! Wir empfehlen <a href="http://www.pixabay.com" target="_blank">pixabay.com</a> als Quelle für Bilder.';
$string['default_licence'] = 'Lizenz';
$string['default_licence_missing'] = 'Sie müssen sich für eine Lizenz entscheiden!';
$string['default_publish_as'] = 'In eduvidual veröffentlichen';
$string['default_title'] = 'Titel';
$string['default_trigger_active'] = 'Paket aktiv!';
$string['default_summary'] = 'Beschreibung';
$string['default_weblink'] = 'Weblink';

$string['defaultrolestudent'] = 'Standardrolle von Lernenden';
$string['defaultrolestudent:description'] = 'Diese Rolle wird von eduPublisher verwendet, falls jemand automatisch Rechte als Lernende/r zugewiesen bekommt.';
$string['defaultroleteacher'] = 'Standardrolle von Lehrenden';
$string['defaultroleteacher:description'] = 'Diese Rolle wird von eduPublisher verwendet, falls jemand automatisch Rechte als Lehrende/r zugewiesen bekommt.';

$string['derivative'] = 'Derivat';
$string['details'] = 'Details';

$string['edupublisher:addinstance'] = 'Block hinzufügen';
$string['edupublisher:canuse'] = 'Kann edupublisher verwenden';
$string['edupublisher:manage'] = 'Block verwalten';
$string['edupublisher:managedefault'] = 'Default-Items verwalten';
$string['edupublisher:manageeduthek'] = 'eduthek-Items verwalten';
$string['edupublisher:manageetapas'] = 'eTapas verwalten';
$string['edupublisher:myaddinstance'] = 'Block zum Dashboard hinzufügen';

$string['eduthek__mailsubject'] = 'eduthek-Item eingereicht';
$string['eduthek_curriculum'] = 'Lehrplanbezug';
$string['eduthek_educationallevel'] = 'Bildungsstufe';
$string['eduthek_fetchchannel'] = 'eduthek-Kanal abrufen';
$string['eduthek_header'] = 'eduthek';
$string['eduthek_language'] = 'Sprache';
$string['eduthek_lticartridge'] = 'LTI Cartridge';
$string['eduthek_ltisecret'] = 'LTI Secret';
$string['eduthek_ltiurl'] = 'LTI URL';
$string['eduthek_publish_as'] = 'In eduthek veröffentlichen';
$string['eduthek_schooltype'] = 'Schultyp';
$string['eduthek_topic'] = 'Themengebiet';
$string['eduthek_trigger_active'] = 'In eduthek aktiv!';
$string['eduthek_type'] = 'Typ';

$string['etapas__description'] = 'Die eTapas-Initiative von eEducation Austria ermöglicht es Lehrer/innen eigene Lernszenarien als Open Educational Resource zu veröffentlichen und dafür eine Entlohnung zu erhalten. Für mehr Informationen besuchen Sie bitte die <a href="https://www.eeducation.at/?id=602" target="_blank">eEducation Webseite</a>.';
$string['etapas__mailsubject'] = 'eTapa zur Prüfung eingereicht';
$string['etapas_erprobungen'] = 'Erprobungen';
$string['etapas_fetchchannel'] = 'eTapas-Kanal abrufen';
$string['etapas_header'] = 'eTapa';
$string['etapas_lticartridge'] = 'LTI Cartridge';
$string['etapas_ltisecret'] = 'LTI Secret';
$string['etapas_ltiurl'] = 'LTI URL';
$string['etapas_publish_as'] = 'Als eTapa veröffentlichen';
$string['etapas_kompetenzen'] = 'Kompetenzen';
$string['etapas_vonschule'] = 'Von Schule';
$string['etapas_schulstufe'] = 'Schulstufe';
$string['etapas_status'] = 'Status';
$string['etapas_status_inspect'] = 'Prüfung';
$string['etapas_status_eval'] = 'Erprobung';
$string['etapas_status_public'] = 'Öffentlich';
$string['etapas_stundenablauf'] = 'Stundenablauf';
$string['etapas_trigger_active'] = 'Als eTapa aktiv!';
$string['etapas_voraussetzungen'] = 'Voraussetzungen';
$string['etapas_vorkenntnisse'] = 'Vorkenntnisse';
$string['etapas_zeitbedarf'] = 'Zeitbedarf';

$string['exportcourse'] = 'Veröffentliche Inhalte in neuem Kurs';
$string['exportcourse_attention'] = '<strong>Achtung:</strong>&nbsp;Bitte beachten Sie den Hilfetext, bevor Sie dieses Feld abwählen!';
$string['exportcourse_help'] = '<strong>Achtung:</strong>&nbsp;Falls Sie diese Checkbox abwählen, wird dieser Kurs selbst veröffentlicht. Alle Nutzer/innen (sogar Sie selbst) werden aus dem Kurs ausgetragen. Daten von Nutzer/innen könnten daher unwiederbringlich gelöscht werden!';
$string['fieldextras'] = 'Extras';
$string['fieldhelptext'] = 'Hilfetext';
$string['fieldname'] = 'Name';
$string['fieldtype'] = 'Typ';
$string['go_back_to_dashboard'] = 'Zurück zum Dashboard';
$string['issued_by_user'] = 'Veröffentlicht von Nutzer/in ';
$string['lti'] = 'LTI';
$string['lti_data'] = 'LTI data';
$string['mail_template'] = 'Vorlage für eMails';
$string['mail_template:description'] = 'Hier kann die Vorlage für eMails bearbeitet werden. Dieser Inhalt sollte eine komplette HTML-Seite repräsentieren. Jedes Vorkommnis der Zeichenkette {{{subject}}} wird durch den Betreff ersetzt, {{content}}} durch den Inhalt.';
$string['manage'] = 'verwalten';
$string['name'] = 'Name';
$string['no_such_package'] = 'Das angeforderte Paket existiert nicht.';
$string['overview'] = 'Überblick';
$string['package'] = 'Paket';
$string['parts_based_upon'] = 'Inhalte basieren auf';
$string['parts_published'] = 'Inhalte veröffentlicht als';
$string['permalink'] = 'Permalink';
$string['permission_denied'] = 'Zugriff verweigert';
$string['public'] = 'Öffentlich';
$string['publish_new_package'] = 'Inhalte veröffentlichen';
$string['rating'] = 'Bewertung';
$string['relevance:stage_0'] = 'Möglicherweise relevant';
$string['relevance:stage_1'] = 'Weniger relevant';
$string['relevance:stage_2'] = 'Relevant';
$string['relevance:stage_3'] = 'Sehr relevant';
$string['removal:title'] = 'Paket entfernen';
$string['removal:text'] = 'Wollen Sie das Paket #{$a->id} {$a->title} wirklich entfernen?';
$string['remove_everything'] = 'Wollen Sie wirklich alle Pakete dieser Moodle-Instanz entfernen? (Kurse werden ebenfalls gelöscht!)';
$string['removed_everything'] = 'Alle Pakete entfernt';
$string['removed_package'] = 'Paket #{$a->id} {$a->title} entfernt';
$string['removing_package_course'] = 'Entferne Kurs zum Paket #{$a->id} {$a->title}';
$string['reply'] = 'Antworten';
$string['search'] = 'Suche';
$string['search_in_edupublisher'] = 'Suche in eduPublisher';
$string['search:enter_term'] = 'Bitte geben Sie Suchworte ein!';
$string['search:noresults'] = 'Leider keine Ergebnisse!';
$string['settings'] = 'Einstellungen';
$string['successfully_published_package'] = 'Paket erfolgreich veröffentlicht';
$string['successfully_saved_comment'] = 'Kommentar erfolgreich gespeichert';
$string['successfully_saved_package'] = 'Paket erfolgreich gespeichert';
$string['successfully_saved_settings'] = 'Einstellungen erfolgreich gespeichert';
$string['summary'] = 'Zusammenfassung';
$string['title'] = 'Titel';
$string['title_missing'] = 'Titel fehlt';
$string['trigger_editing_permission_grant'] = 'Der/dem Autor/in Schreibrechte verleihen';
$string['trigger_editing_permission_remove'] = 'Der/dem Autor/in Schreibrechte wegnehmen';
$string['type'] = 'Typ';
$string['votes'] = 'Stimme(n)';
