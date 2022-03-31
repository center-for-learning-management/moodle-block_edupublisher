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
$string['after_section'] = 'Nach folgendem Abschnitt einfügen:';
$string['allowguests'] = 'Offen für Gäste';
$string['allowguests_desc'] = 'Wenn diese Option an ist, können Gäste die Suchfunktion verwenden.';
$string['by'] = 'von';
$string['category'] = 'Kategorie';
$string['category_help'] = 'Geben Sie hier die Kategorie ein, in der veröffentlichte Kurse hinterlegt werden sollen.';
$string['category_missing'] = 'Die Kategorie ist erforderlich';
$string['channel'] = 'Kanal';
$string['channels'] = 'Kanäle';
$string['clone_to_course'] = 'In folgenden Kurs kopieren:';
$string['clonecourse'] = 'Veröffentliche Inhalte in neuem Kurs';
$string['clonecourse_attention'] = '<strong>Achtung:</strong>&nbsp;Bitte beachten Sie den Hilfetext, bevor Sie dieses Feld abwählen!';
$string['clonecourse_help'] = '<strong>Achtung:</strong>&nbsp;Falls Sie diese Checkbox abwählen, wird dieser Kurs selbst veröffentlicht. Alle Nutzer/innen (sogar Sie selbst) werden aus dem Kurs ausgetragen. Daten von Nutzer/innen könnten daher unwiederbringlich gelöscht werden!';
$string['comment'] = 'Comment';
$string['comment:evaluation:added'] = 'Hallo,<br /><br />Ich habe gerade eine Erprobung dokumentiert. Diese ist unter diesem <a href="{$a->commentlink}" target="_blank">Link</a> ersichtlich.<br /><br />Liebe Gr&uuml;&szlig;e';
$string['comment:forchannel'] = 'Dieser Kommentar bezieht sich auf den Kanal "{$a->channel}".';
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

$string['commercial_header'] = 'Kommerzielles Angebot';
$string['commercial_fetchchannel'] = 'Kommerzielle Angebote abrufen';
$string['commercial_publisher'] = 'Anbieter';
$string['commercial_publish_as'] = 'Als kommerzielles Angebot veröffentlichen';
$string['commercial_trigger_active'] = 'Paket aktiv!';
$string['commercial_shoplink'] = 'Link zum Kauf';
$string['commercial_shoplink_help'] = 'Nur erforderlich, wenn die Lizenzprüfung auf "intern" gesetzt wurde. Geben Sie hier den Link ein, unter dem die Lizenz gekauft werden kann.';
$string['commercial_validateexternal'] = 'extern: Lizenz wird erst bei der Nutzung geprüft';
$string['commercial_validateinternal'] = 'intern: Lizenz muss vor dem Import vorhanden sein';
$string['commercial_validation'] = 'Lizenzprüfung';
$string['commercial_validation_help'] = '<strong>Externe Prüfung:</strong> Paket kann immer importiert werden. Lernressourcen rufen externes Tool auf. Prüfung der Lizenz erfolgt in externem Tool.<br /><strong>Intern:</strong> Lernpaket kann erst genutzt werden, nachdem eine Lizenz erfasst wurde (Nutzer-, Kurs-, oder Schulkontext)';

$string['danubeai:apikey'] = 'Danube.ai API Schlüssel';
$string['danubeai:apikey:description'] = 'Der API Schlüssel für danube.ai';

$string['default__mailsubject'] = 'eduPublisher-Item eingereicht';
$string['default_header'] = 'eduvidual';
$string['default_authorname'] = 'Autor';
$string['default_authorname_missing'] = 'Bitte geben Sie den Namen des Autors/der Autorin/nen ein!';
$string['default_authormail'] = 'Kontakt e-Mail';
$string['default_authormail_missing'] = 'Bitte geben Sie eine gültige Mailadresse ein!';
$string['default_authormailshow'] = 'Zeige e-Mail öffentlich';
$string['default_coursecontents'] = 'Kursinhalte';
$string['default_coursecontents_help'] = 'Kursinhalte';
$string['default_fetchchannel'] = 'Default-Kanal abrufen';
$string['default_origins'] = 'Abgeleitetes Werk von';
$string['default_image'] = 'Bild';
$string['default_image_help'] = 'Vorschaubild für Anzeige';
$string['default_image_label'] = 'Bitte wählen Sie ein aussagekräftiges Bild für Ihren Inhalt. Beachten Sie das Urheberrecht! Wir empfehlen <a href="http://www.pixabay.com" target="_blank">pixabay.com</a> als Quelle für Bilder.';
$string['default_licence'] = 'Lizenz';
$string['default_licence_missing'] = 'Sie müssen sich für eine Lizenz entscheiden!';
$string['default_licenceother'] = 'Sonstige Lizenz';
$string['default_publish_as'] = 'In eduvidual veröffentlichen';
$string['default_schoollevel'] = 'Schulstufe';
$string['default_schoollevel_primary'] = 'Primarstufe';
$string['default_schoollevel_secondary_1'] = 'Sekundarstufe 1';
$string['default_schoollevel_secondary_2'] = 'Sekundarstufe 2';
$string['default_schoollevel_tertiary'] = 'Tertiär';
$string['default_subjectarea'] = 'Gegenstandsbereich';
$string['default_subjectarea_arts'] = 'Kunst';
$string['default_subjectarea_economics'] = 'Wirtschaft';
$string['default_subjectarea_geography'] = 'Geographie';
$string['default_subjectarea_history'] = 'Geschichte';
$string['default_subjectarea_informatics'] = 'Informatik';
$string['default_subjectarea_languages'] = 'Sprachen';
$string['default_subjectarea_mathematics'] = 'Mathematik';
$string['default_subjectarea_naturalsciences'] = 'Naturwissenschaften';
$string['default_subjectarea_other'] = '- Andere -';
$string['default_subjectarea_philosophy'] = 'Philosophie';
$string['default_subjectarea_physicaleducation'] = 'Sport';
$string['default_suppresscomment'] = 'Kein Kommentar';
$string['default_suppresscomment_help'] = 'Speichere, ohne einen Kommentar zu generieren';
$string['default_tags'] = 'Schlüsselworte';
$string['default_title'] = 'Titel';
$string['default_trigger_active'] = 'Paket aktiv!';
$string['default_summary'] = 'Beschreibung';
$string['default_weblink'] = 'Weblink';

$string['defaultrolestudent'] = 'Standardrolle von Lernenden';
$string['defaultrolestudent:description'] = 'Diese Rolle wird von eduPublisher verwendet, falls jemand automatisch Rechte als Lernende/r zugewiesen bekommt.';
$string['defaultrolestudent:missing'] = 'Die Admin-Einstellung zur Standardrolle für Lernende fehlt.';
$string['defaultroleteacher'] = 'Standardrolle von Lehrenden';
$string['defaultroleteacher:description'] = 'Diese Rolle wird von eduPublisher verwendet, falls jemand automatisch Rechte als Lehrende/r zugewiesen bekommt.';

$string['derivative'] = 'Derivat';
$string['details'] = 'Details';

$string['edupublisher:addinstance'] = 'Block hinzufügen';
$string['edupublisher:cancreategroups'] = 'Kann Gruppen erstellen';
$string['edupublisher:canevaluate'] = 'Kann evaluieren';
$string['edupublisher:canuse'] = 'Kann eduPublisher verwenden';
$string['edupublisher:canselfenrol'] = 'Kann sich selbst in eduPublisher-Kurse einschreiben';
$string['edupublisher:canseeevaluation'] = 'Kann Evaluationen einsehen';
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

$string['enablecommercial'] = 'Erlaube kommerziellen Inhalt';
$string['enablecommercial_desc'] = 'Mit dieser Checkbox kann die Findbarkeit von kommerziellen Inhalten global gesteuert werden.';

$string['etapas__description'] = 'Die eTapas-Initiative von eEducation Austria ermöglicht es Lehrer/innen eigene Lernszenarien als Open Educational Resource zu veröffentlichen und dafür eine Entlohnung zu erhalten. Für mehr Informationen besuchen Sie bitte die <a href="https://www.eeducation.at/?id=602" target="_blank">eEducation Webseite</a>.';
$string['etapas__mailsubject'] = 'eTapa zur Prüfung eingereicht';
$string['etapas_erprobungen'] = 'Erprobungen';
$string['etapas_fetchchannel'] = 'eTapas-Kanal abrufen';
$string['etapas_header'] = 'eTapa';
$string['etapas_gegenstand'] = 'Gegenstand';
$string['etapas_lticartridge'] = 'LTI Cartridge';
$string['etapas_ltisecret'] = 'LTI Secret';
$string['etapas_ltiurl'] = 'LTI URL';
$string['etapas_publish_as'] = 'Als eTapa veröffentlichen';
$string['etapas_kompetenzen'] = 'Kompetenzen';
$string['etapas_kompetenzen_help'] = '<p class="alert alert-danger">Die Verlinkung von Ressourcen des Moodle-Kurses mit Kompetenzen ist erforderlich, um ein Lernpaket als eTapa einreichen zu können. Bitte verwenden Sie dazu entweder die Moodle-Kompetenzen oder den Block "Kompetenzraster" (exacomp).</p>';
$string['etapas_kompetenzen_missing'] = 'Bitte Kompetenzen zu Aktivitäten und Ressourcen zuordnen';
$string['etapas_vonschule'] = 'Von Schule';
$string['etapas_schulstufe'] = 'Schulstufe';
$string['etapas_status'] = 'Status';
$string['etapas_status_eval'] = 'Erprobt';
$string['etapas_status_inspect'] = 'Freischaltung erforderlich';
$string['etapas_status_public'] = 'Erprobung erforderlich';
$string['etapas_stundenablauf'] = 'Stundenablauf';
$string['etapas_subtype'] = 'Untergruppe';
$string['etapas_type'] = 'Art';
$string['etapas_trigger_active'] = 'Als eTapa aktiv!';
$string['etapas_voraussetzungen'] = 'Voraussetzungen';
$string['etapas_vorkenntnisse'] = 'Vorkenntnisse';
$string['etapas_zeitbedarf'] = 'Zeitbedarf';

$string['exception:name_must_not_be_empty'] = 'Der Name darf nicht leer sein!';
$string['export'] = 'Export';
$string['externalsources'] = 'Externe Quellen';
$string['externalsources:courseformat'] = 'Standard Kursformat für externe Quellen';
$string['externalsources:courseformat:description'] = 'Standard Kursformat bei neuen Kursen für externe Quellen';

$string['fieldextras'] = 'Extras';
$string['fieldhelptext'] = 'Hilfetext';
$string['fieldname'] = 'Name';
$string['fieldtype'] = 'Typ';

$string['go_back_to_dashboard'] = 'Zurück zum Dashboard';
$string['groups:create'] = 'Gruppe anlegen';
$string['groups:create:error'] = 'Gruppe "{$a->name}" wurde <strong>nicht</strong> erfolgreich erstellt!';
$string['groups:create:success'] = 'Gruppe "{$a->name}" wurde erfolgreich erstellt!';
$string['groups:domains'] = 'E-Maildomains';
$string['groups:domains_desc'] = 'Nutzer/innen kann die Funktion entweder über die Capability "<i>block/edupublisher:cancreategroups</i>" vergeben werden, oder indem die E-Mailadresse eine bestimmte Domain aufweist. Mehrere Domains können zeilenweise angegeben werden. Jede Zeile muss mit einem "@"-Zeichen beginnen!';
$string['groups:enabled'] = 'Erlaube Gruppenerstellung';
$string['groups:enabled_desc'] = 'Diese Einstellung schaltet das ganze Feature an und aus.';
$string['groups:login_other_account'] = 'Mit anderem Konto anmelden';
$string['groups:longtext'] = 'Die Erstellung einer Gruppe ermöglicht es, dass Lehrer/innen ihre Schüler/innen sehen und mit ihnen in Kontakt treten können. Damit ist eine Lernbegleitung und Lernfortschrittskontrolle gewährleistet, ohne dass eigene Kurse erstellt und befüllt werden müssen.<br /><br />Geben Sie die URL "<strong>für Lehrer/innen</strong>" an andere Lehrpersonen weiter, oder folgen Sie dieser URL falls Sie ein zweites Nutzerkonto als Lehrperson verwenden möchten. Die URL "<strong>für Schüler/innen</strong>" können Sie Ihren Schüler/innen geben, damit diese Ihrer Gruppe beitreten können. Um den QR-Code zu vergrößeren, können Sie diesen anklicken.';
$string['groups:name'] = 'Gruppenname';
$string['groups:no_permission'] = 'Nur Lehrer/innen können Gruppen in Lernpaketen erstellen. Leider verfügt Ihr Nutzerkonto nicht über die erforderliche Berechtigung. Bitte wechseln Sie auf ein berechtigtes Nutzerkonto!';
$string['groups:no_permission_domains'] = 'Zusätzlich zum üblichen Berechtigungssystem wird jedes Nutzerkonto akzeptiert, dessen E-Mailadresse zu einer der folgenden Domains gehört:';
$string['groups:not_member'] = 'Sie gehören nicht zu dieser Gruppe!';
$string['groups:remove:title'] = 'Löschung bestätigen';
$string['groups:remove:text'] = 'Wollen Sie diese Gruppe wirklich löschen? Sie werden die Einsicht in die Daten Ihrer Schüler/innen verlieren!';
$string['groups:rolestudent'] = 'Lernenden-Rolle';
$string['groups:rolestudent_desc'] = 'Welche Rolle soll Lernenden vergeben werden?';
$string['groups:roleteacher'] = 'Lehrenden-Rolle';
$string['groups:roleteacher_desc'] = 'Welche Rolle soll Lehrenden vergeben werden?';
$string['groups:settings'] = 'Gruppenerstellung';
$string['groups:settings:description'] = 'Gruppenerstellung in zentralen Lernpaketen konfigurieren.';
$string['groups:urlstudent'] = 'Für Schüler/innen';
$string['groups:urlteacher'] = 'Für Lehrer/innen';
$string['guest_not_allowed'] = 'Für Gäste ist diese Aktion nicht gestattet!';

$string['initialize_import'] = 'Lernpaket kopieren';
$string['invalid_evaluation'] = 'Ungültige Evaluations-ID';
$string['issued_by_user'] = 'Veröffentlicht von Nutzer/in ';

$string['licence'] = 'Lizenz';
$string['licence_amount'] = 'Anzahl';
$string['licence_amount_hint'] = 'Hinweis: -1 bedeutet unbegrenzte Nutzungen';
$string['licence_amount_infinite'] = 'Unbegrenzte Nutzungen';
$string['licence_amount_none'] = 'Keine Nutzung möglich';
$string['licence_amount_usages'] = 'Anzahl an Nutzungen: {$a->amount}';
$string['licence_already_redeemed'] = 'Lizenz wurde bereits aktiviert!';
$string['licence_back_to_dashboard'] = 'Zurück zum Lizenz-Dashboard';
$string['licence_check_ok'] = 'Alle Lizenzschlüssel wurden geprüft und können angelegt werden!';
$string['licence_collection'] = 'Sammlung';
$string['licence_collection_desc'] = 'Die "Sammlung" ermöglicht es Nutzer/innen jedes Lernpaket, das von einer Lizenz umfasst wird, so oft in einen Kurs zu importieren, wie Sie pro Lernpaket festgelegt haben.<br /><br /><strong>Beispiel:</strong> Die Lizenz umfasst 5 Lernpakete, jedes Lernpaket wird auf die Anzahl 2 gesetzt --> Jedes Lernpaket aus diesem Korb kann in 2 Kurse importiert werden.';
$string['licence_created_successfully'] = 'Lizenzen wurden erfolgreich angelegt!';
$string['licence_generate'] = 'Lizenzen anlegen';
$string['licence_generatekeys'] = 'Lizenzschlüssel generieren';
$string['licence_invalid'] = 'Lizenzschlüssel ungültig!';
$string['licence_manage'] = 'Lizenzen verwalten';
$string['licence_packages'] = 'Pakete';
$string['licence_paste_alternatively'] = 'Alternativ können Sie auch bestehende Lizenz-Schlüssel hier einfügen!';
$string['licence_pool'] = 'Korb';
$string['licence_pool_desc'] = 'Der "Korb" ermöglicht es Nutzer/innen jedes beliebige Paket, das von einer Lizenz umfasst wird, so oft in einen Kurs zu importieren, wie Sie in dieser Lizenz festgelegt haben.<br /><br /><strong>Beispiel:</strong> Die Lizenz umfasst 20 Lernpakete, die Anzahl an Imports wurde auf 5 gesetzt --> Die Nutzer/innen können 5 mal eines dieser Lernpakete in einen Kurs importieren.';
$string['licence_redeem'] = 'Lizenz einlösen';
$string['licence_target'] = 'Kontext';
$string['licence_target_course'] = 'Kurs';
$string['licence_target_course_desc'] = 'Die Lizenz wird einem Kurs zugeordnet. Das entspricht in den meisten Fällen einer Schulklasse.';
$string['licence_target_desc'] = 'Dies legt fest, an welche Entität die Lizenz gebunden wird.';
$string['licence_target_org'] = 'Schule';
$string['licence_target_org_desc'] = 'Die Lizenz wird einer Schule zugeordnet. Alle Lernbegleiter/innen dieser Schule können die Lizenz verwenden.';
$string['licence_target_user'] = 'Nutzer/in';
$string['licence_target_user_desc'] = 'Die Lizenz wird einer einzelnen Person zugeordnet. Die Lizenz kann nur von dieser Person in beliebigen Schulen genutzt werden.';
$string['licence_type'] = 'Typ';
$string['licences'] = 'Lizenzen';

$string['lti'] = 'LTI';
$string['lti_data'] = 'LTI data';
$string['mail_template'] = 'Vorlage für eMails';
$string['mail_template:description'] = 'Hier kann die Vorlage für eMails bearbeitet werden. Dieser Inhalt sollte eine komplette HTML-Seite repräsentieren. Jedes Vorkommnis der Zeichenkette {{{subject}}} wird durch den Betreff ersetzt, {{content}}} durch den Inhalt.';
$string['manage'] = 'verwalten';
$string['missing_capability'] = 'Sie sind leider nicht berechtigt eduPublisher zu verwenden!';
$string['name'] = 'Name';
$string['no_such_package'] = 'Das angeforderte Paket existiert nicht.';
$string['oer_header'] = 'Offene Bildungsressourcen';
$string['only_viewing_enrol_to_user'] = 'Sie nutzen diesen Kurs nur im Betrachtungsmodus. Daher könnten einige Aktivitäten nicht wie erwartet funktionieren und Lernfortschritte werden nicht gespeichert. Um die volle Funktionalität zu gewährleisten, müssen Sie sich in den Kurs einschreiben!';
$string['only_viewing_enrol_button'] = 'Ja, mich in diesen Kurs einschreiben!';
$string['only_viewing_enrol_as_student'] = 'Ja, mich in diesen Kurs als Lernende/r einschreiben!';
$string['only_viewing_enrol_as_teacher'] = 'Ja, mich in diesen Kurs als Verwalter/in einschreiben!';
$string['only_viewing_unenrol_button'] = 'Ja, mich aus diesem Kurs austragen!';
$string['only_viewing_unenrol_as_student'] = 'Ja, mich aus diesem Kurs als Lernende/r austragen!';
$string['only_viewing_unenrol_as_teacher'] = 'Ja, mich aus diesem Kurs als Verwalter/in austragen!';
$string['overview'] = 'Überblick';
$string['package'] = 'Paket';
$string['parts_based_upon'] = 'Inhalte basieren auf';
$string['parts_published'] = 'Inhalte veröffentlicht als';
$string['pending_publication'] = 'Offene Publikation von Kurs {$a->courseid}';
$string['permalink'] = 'Permalink';
$string['permission_denied'] = 'Zugriff verweigert';

$string['privacy:metadata'] = 'Dieses Plugin speichert keine personenbezogenen Daten';
$string['privacy:export:comments'] = 'Kommentare';
$string['privacy:export:evaluatio'] = 'Evaluationen';
$string['privacy:export:lic'] = 'Lizenzen';
$string['privacy:export:log'] = 'Log';
$string['privacy:export:packages'] = 'Eigene Lernressourcen';
$string['privacy:export:pub_user'] = 'Verlagsnutzer';
$string['privacy:export:rating'] = 'Bewertungen';
$string['privacy:export:uses'] = 'Genutzte Ressourcen';

$string['public'] = 'Öffentlich';
$string['publish_new_package'] = 'Inhalte veröffentlichen';
$string['publish_new_package_proceed'] = 'Veröffentlichung fortsetzen';
$string['publish_missing_sourcecourseid'] = 'Eine Fortsetzung ist ohne Quellkurs nicht möglich!';
$string['publish_proceed_label'] = '
    <h3>Veröffentlichung von Ressourcen</h3>
    <p>
        Dieser Kurs wurde im Rahmen der Veröffentlichungsprozesses von offenen
        Bildungsressourcen für den Ressourcenkatalog erstellt. Sie können mit der
        Veröffentlichung fortfahren, indem Sie den folgenden Link anklicken:
    </p>
    <a href="{$a->wwwroot}/blocks/edupublisher/pages/publish.php?sourcecourseid={$a->sourcecourseid}" class="btn btn-primary">
        Mit Veröffentlichung fortfahren
    </a>
';
$string['publish_stage_confirm'] = 'Start';
$string['publish_stage_confirm_text'] = '
    <p>
        Mittels dieser Funktion können Sie Teile Ihres Kurses für andere als
        offene Bildungsressource freigeben. Der Freigabeprozess besteht aus
        vier Schritten:
    </p>
    <ol>
        <li>Bestätigen Sie, dass Sie Inhalte freigeben möchten.</li>
        <li>
            Wählen Sie die Aktivitäten und Ressourcen aus, die Sie teilen möchten.
            Diese werden in einen neuen Kurs ohne Nutzerdaten importiert.
        </li>
        <li>Beschreiben Sie den Inhalt in einem kurzen Webformular.</li>
        <li>Unsere Redaktion schaltet das Lernpaket frei.</li>
    </ol>';
$string['publish_stage_confirm_button'] = 'Ok, fortsetzen!';
$string['publish_stage_import'] = 'Inhalte auswählen';
$string['publish_stage_metadata'] = 'Metadaten angeben';
$string['publish_stage_finish'] = 'Fertig';
$string['publish_stage_finish_text'] = '
    <h3>Veröffentlichung erfolgreich</h3>
    <p>
        Vielen Dank für die Veröffentlichung dieser Inhalte als offene Bildungsressource!
    </p>
    <p>
        Unser Redaktionsteam wird das Lernpaket nach einer kurzen Prüfung freigeben.
    </p>';
$string['publish_stage_finish_button'] = 'Zum Lernpaket';
$string['publisher'] = 'Anbieter';
$string['publisher_logo'] = 'Logo';
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
$string['resource_catalogue'] = 'Ressourcenkatalog';
$string['search'] = 'Suche';
$string['search_for_term_author_etc'] = 'Suche nach Titel, Schlagwörtern, Autor/innen, ...';
$string['search_in_edupublisher'] = 'Suche in eduPublisher';
$string['search:enter_term'] = 'Bitte geben Sie Suchworte ein!';
$string['search:noresults'] = 'Leider keine Ergebnisse!';
$string['self_enrol'] = 'Selbsteinschreibung';
$string['self_enrol_confirm_text'] = 'Sie sind dabei sich in diesen Kurs einzuschreiben. Dadurch wird es möglich, dass Ihr Lernfortschritt gespeichert wird. Andere Lernende könnten in bestimmten Aktivitäten Ihren Namen sehen. Ihre Kursbewertungen (bspw. in Quizzes) sind für andere Lernende nicht sichtbar.';
$string['self_unenrol'] = 'Austragen';
$string['self_unenrol_confirm_text'] = 'Wenn Sie sich aus einem Kurs austragen, werden Ihre Lernfortschritte und Daten möglicherweise gelöscht. Selbst wenn Sie sich wieder in den Kurs einschreiben, sind diese Daten möglicherweise für immer verloren. Sind Sie sicher, dass Sie das machen wollen?';
$string['send_email_failed'] = 'Fehler beim Versand einer E-Mail an "{$a->email}".';
$string['settings'] = 'Einstellungen';
$string['star_multiple'] = '{$a->stars} Sterne';
$string['star_none'] = 'Keine Bewertung';
$string['star_single'] = '1 Stern';
$string['successfully_enrolled'] = 'Erfolgreich eingeschrieben';
$string['successfully_published_package'] = 'Paket erfolgreich veröffentlicht';
$string['successfully_saved_comment'] = 'Kommentar erfolgreich gespeichert';
$string['successfully_saved_evaluation'] = 'Erprobung erfolgreich gespeichert';
$string['successfully_saved_package'] = 'Paket erfolgreich gespeichert';
$string['successfully_saved_settings'] = 'Einstellungen erfolgreich gespeichert';
$string['successfully_unenrolled'] = 'Erfolgreich ausgetragen!';
$string['summary'] = 'Zusammenfassung';
$string['task:externalsources:title'] = 'Externe Quellen';
$string['title'] = 'Titel';
$string['title_missing'] = 'Titel fehlt';
$string['trigger_editing_permission_grant'] = 'Der/dem Autor/in Schreibrechte verleihen';
$string['trigger_editing_permission_remove'] = 'Der/dem Autor/in Schreibrechte wegnehmen';
$string['type'] = 'Typ';
$string['votes'] = 'Stimme(n)';

$string['wordpress:notification:subject_created'] = 'Das neue Lernpaket "{$a->title}" wurde erstellt!';
$string['wordpress:notification:subject_published'] = 'Das Lernpaket "{$a->title}" wurde soeben veröffentlicht!';
$string['wordpress:notification:subject_unpublished'] = '{$a->title} wurde inaktiv gestellt!';
$string['wordpress:notification:subject_updated'] = '{$a->title} wurde akualisiert!';
$string['wordpress:notification:subject_deleted'] = '{$a->title} wurde gelöscht!';

$string['wordpress:notification:text_created'] = '
    <p>Lieber Nutzer/innen,</p>
    <p>Das neue Lernpaket mit dem Titel <a href="{$a->moodlecourseurl}" target="_blank">{$a->title}</a> wurde erstellt. Wir werden es so rasch als möglich veröffentlichen!</p>
    <p>Die Beschreibung dieses Lernpakets lautet wie folgt:</p>
    <p>{$a->default_summary}</p>
    <p>Mit besten Grüßen,<br />Ihr Team von {$a->moodlesitename}</p>
    {$a->wpshortcodes}';
$string['wordpress:notification:text_published'] = '
    <p>Lieber Nutzer/innen,</p>
    <p>Das Lernpaket mit dem Titel <a href="{$a->moodlecourseurl}" target="_blank">{$a->title}</a> wurde soeben veröffentlicht!</p>
    <p>Die Beschreibung dieses Lernpakets lautet wie folgt:</p>
    <p>{$a->default_summary}</p>
    <p>Mit besten Grüßen,<br />Ihr Team von {$a->moodlesitename}</p>
    {$a->wpshortcodes}';
$string['wordpress:notification:text_unpublished'] = '
    <p>Lieber Nutzer/innen,</p>
    <p>Das Lernpaket mit dem Titel <a href="{$a->moodlecourseurl}" target="_blank">{$a->title}</a> wurde inaktiv gestellt.</p>
    <p>Mit besten Grüßen,<br />Ihr Team von {$a->moodlesitename}</p>
    {$a->wpshortcodes}';
$string['wordpress:notification:text_updated'] = '
    <p>Lieber Nutzer/innen,</p>
    <p>Das Lernpaket <a href="{$a->moodlecourseurl}" target="_blank">{$a->title}</a> wurde aktualisiert.</p>
    <p>Mit besten Grüßen,<br />Ihr Team von {$a->moodlesitename}</p>
    {$a->wpshortcodes}';
$string['wordpress:notification:text_deleted'] = '
    <p>Lieber Nutzer/innen,</p>
    <p>Leider mussten wir das Lernpaket <a href="{$a->moodlecourseurl}" target="_blank">{$a->title}</a> entfernen.</p>
    <p>Mit besten Grüßen,<br />Ihr Team von {$a->moodlesitename}</p>
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

$string['wordpress:settings'] = 'Wordpress Einstellungen';
$string['wordpress:settings:description'] = 'Für jede Art von Aktion können Sie eine separate E-Mailadresse angeben, an die eine Benachrichtigung geschickt wird. Sofern keine E-Mailadresse angegeben wird, wird keine Benachrichtigung geschickt. Außerdem können Sie für jede Art von Aktion unterschiedliche Shortcodes eingeben. Mit Shortcodes kann gesteuert werden, wie Wordpress mit den Beiträgen umgeht. Mehr Informationen finden Sie auf der folgenden <a href="https://wordpress.com/support/post-by-email/" target="_blank">Seite</a>.';
$string['wordpress:settings:email'] = 'E-Mail';
$string['wordpress:settings:postifcreated'] = 'Beitrag bei Erstellung';
$string['wordpress:settings:postifpublished'] = 'Beitrag bei Veröffentlichung';
$string['wordpress:settings:postifunpublished'] = 'Beitrag bei Inaktivschaltung';
$string['wordpress:settings:postifupdated'] = 'Beitrag bei Aktualisierung';
$string['wordpress:settings:postifdeleted'] = 'Beitrag bei Löschung';
$string['wordpress:settings:shortcodes'] = 'Shortcodes';


$string['evaluation_by'] = 'Evaluation von {$a->fullname}';
$string['evaluation_introtext'] = 'Bevor Sie eine Evaluation erfassen, sollten Sie das Lernpaket mit einer tatsächlichen Klasse und echten Schüler/innen ausprobiert haben. Bitte teilen Sie danach anderen Lehrer/innen mit, wie die Qualität dieser Lernressource war und welche Verbesserungen für den Lernprozess damit möglich wurden, indem Sie das folgende Evaluationsformular ausfüllen.';
$string['evaluation_none'] = 'Bisher wurde keine Evaluation durchgeführt.';
$string['etapas_evaluation'] = 'eTapa Evaluierung';
$string['required'] = 'Dies ist ein Pflichtfeld';
$string['max_length'] = 'Maximale Anzahl an Buchstaben erreicht';
$string['evaluated_on'] = 'Datum der Evaluierung';
$string['evaluated_verytrue'] = 'trifft sehr zu';
$string['evaluated_nottrue'] = 'trifft nicht zu';
$string['school'] = 'Ort der Evaluierung';
$string['name_of_school'] = 'Name der Schule';
$string['rating_coherent'] = 'Die Beschreibung des eTapa ist verständlich und nachvollziehbar';
$string['rating_plausible'] = 'Der Ablaufplan ist plausibel und als Anleitung für den Unterricht geeignet';
$string['rating_preconditions'] = 'Die Vorkenntnisse und Voraussetzungen sind nachvollziehbar';
$string['rating_content'] = 'Die Inhalte sind fachlich korrekt und für die Zielgruppe passend';
$string['reason'] = 'Anregungen';
$string['technology'] = 'Auswirkung der Technologie';
$string['technology_help'] = 'Gemessen am SAMR-Modell (Substitution, Augmentation, Modification, Redefinition)';
$string['substitution'] = 'Kein Mehrwert für das Lernen, das hätte ohne Technologie ebenso gut geklappt.';
$string['augmentation'] = 'Es gab einen moderaten Mehrwert für das Lernen.';
$string['modification'] = 'Es wurden Lernaufgaben definiert, die ohne Technologie nicht möglich wären.';
$string['redefinition'] = 'Durch die Technologie wurden neue pädagogische Methoden umgesetzt.';
$string['feedback'] = 'Feedback für den/die Autor/in';
$string['evaluations'] = 'Evaluierungen';
$string['evaluation_general'] = 'Allgemeine Einschätzung';
$string['evaluate'] = 'Evaluieren';
