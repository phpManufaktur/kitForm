<?php
/**
 * kitForm
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de/kit_form
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 */

define('form_action_account',									'Benutzerkonto'); 
define('form_action_login',										'Anmeldung');
define('form_action_logout',									'Abmeldung/Logout');
define('form_action_newsletter',							'Newsletter An-/Abmeldung');
define('form_action_none',										'- keine Aktion -');
define('form_action_register',								'Registrierung');
define('form_action_send_password',						'Passwort zusenden');

define('form_btn_abort',											'Abbruch'); 
define('form_btn_import',                     'Import starten ...');
define('form_btn_ok',													'Übernehmen');

define('form_captcha_on',											'Aktiviert');
define('form_captcha_off',										'Ausgeschaltet');

define('form_cfg_currency',										'%s €');
define('form_cfg_date_separator',							'.');  
define('form_cfg_date_str',										'd.m.Y');
define('form_cfg_datetime_str',								'd.m.Y H:i');
define('form_cfg_day_names',									"Sonntag, Montag, Dienstag, Mittwoch, Donnerstag, Freitag, Samstag");
define('form_cfg_decimal_separator',          ',');
define('form_cfg_month_names',								"Januar,Februar,März,April,Mai,Juni,Juli,August,September,Oktober,November,Dezember");
define('form_cfg_thousand_separator',					'.');
define('form_cfg_time_long_str',							'H:i:s');
define('form_cfg_time_str',										'H:i');
define('form_cfg_time_zone',									'Europe/Berlin');
define('form_cfg_title',											'Herr,Frau');

define('form_data_type_email',								'E-Mail');
define('form_data_type_date',									'Datum');
define('form_data_type_float',								'Dezimalzahl');
define('form_data_type_integer',							'Ganzzahl');
define('form_data_type_kit',									'KIT Datentyp');
define('form_data_type_password',							'Passwort');
define('form_data_type_text',									'Text');
define('form_data_type_undefined',						'- nicht festgelegt -');

define('form_error_data_type_invalid',				'<p>Der Datentyp <b>%s</b> wird nicht unterstützt!</p>');
define('form_error_email_password_required',	'<p>Die Datenfelder für E-Mail Adresse und/oder Passwort sind nicht gesetzt!</p>');
define('form_error_field_required',           '<p>Das Datenfeld <b>%s</b> ist nicht gesetzt!</p>');
define('form_error_field_type_not_implemented','<p>Der Feldtyp <b>%s</b> ist nicht implementiert!</p>');
define('form_error_form_id_missing',					'<p>Es wurde keine ID für das Formular übergeben!</p>');
define('form_error_form_name_empty',					'<p>Es wurde kein Formular Bezeichner übergeben!</p>');
define('form_error_form_name_invalid',				'<p>Das Formular mit dem Bezeichner <b>%s</b> wurde nicht gefunden!</p>');
define('form_error_kit_field_id_invalid',			'<p>Der <b>ID %03d</b> ist kein KeepInTouch Datenfeld zugeordnet!</p>');
define('form_error_preset_not_exists',				'<p>Das Presetverzeichnis <b>%s</b> existiert nicht, die erforderlichen Templates können nicht geladen werden!</p>');
define('form_error_reading_file',             '<p>Die Datei <b>%s</b> konnte nicht eingelesen werden!</p>');
define('form_error_sending_email',						'<p>Die E-Mail an <b>%s</b> konnte nicht versendet werden!</p>'); 
define('form_error_template_error',						'<p>Fehler bei der Ausführung des Template <b>%s</b>:</p><p>%s</p>');
define('form_error_upload_form_size',					'<p>Die hochgeladene Datei überschreitet die in dem HTML Formular mittels der Anweisung MAX_FILE_SIZE angegebene maximale Dateigröße.</p>');
define('form_error_upload_ini_size',					'<p>Die hochgeladene Datei überschreitet die in der Anweisung upload_max_filesize in php.ini festgelegte Größe von %s</p>');
define('form_error_upload_move_file',					'<p>Die Datei <b>%s</b> konnte nicht in das Zielverzeichnis verschoben werden!</p>');
define('form_error_upload_partial',						'<p>Die Datei <b>%s</b> wurde nur teilweise hochgeladen.</p>');
define('form_error_upload_undefined_error',		'<p>Während der Datenübertragung ist ein nicht näher beschriebener Fehler aufgetreteten.</p>');
define('form_error_writing_file',							'<p>Fehler beim Schreiben der Datei <b>%s</b>.</p>');

define('form_header_edit_form',								'Formular bearbeiten');
define('form_header_form_list',								'Übersicht über die verfügbaren Formulare');
define('form_header_protocol_detail',					'Protokoll, Details');
define('form_header_protocol_list',						'Protokoll');

define('form_hint_form_id',										'');
define('form_hint_form_email_cc',							'Sie können <b>zusätzliche E-Mail Empfänger</b> festlegen, diese erhalten ebenfalls eine Information über neu empfangene Formulare. Trennen Sie die E-Mail Adressen mit einem Komma voneinander.');
define('form_hint_form_email_html',						'Legen Sie fest ob die Benachrichtigungsmails im HTML Format oder als NUR TEXT versendet werden sollen.');
define('form_hint_form_export',								'Sie können diesen Dialog als Datei exportieren und ihn in anderen Installationen importieren.');
define('form_hint_form_name',									'Legen Sie einen <b>Bezeichner</b> z.B. "kontakt" für diesen Dialog fest. Der Bezeichner darf keine Sonderzeichen, Leerzeichen, Umlaute o.ä. enthalten, er wird im Droplet <i>[[kit_form?form=<b>kontakt</b>]]</i> als Parameter für das Formular verwendet. Bezeichner werden automatisch bereinigt und in Kleinbuchstaben umgewandelt!');
define('form_hint_form_title',								'Legen Sie zur einfachen Kennzeichnung des Formulares einen Titel für das Formular fest, z.B. "Newsletter Anmeldung". Der Formular Titel wird als <b>Betreff</b> bzw. <b>Subject</b> in den Benachrichtigungs E-Mails verwendet.');
define('form_hint_form_desc',									'Beschreiben Sie die Funktion des Formulares.');
define('form_hint_form_captcha',							'Schalten Sie den CAPTCHA Spamschutz ein oder aus.');
define('form_hint_form_provider',							'Wählen Sie einen <b><a href="%s/admintools/tool.php?tool=kit&act=cfg&ctab=cftp">aktiven Dienstleister</a></b> für den E-Mail Versand dieses Formular aus. Der Dienstleister erhält automatisch eine Benachrichtigung bei neu eingegangenen Formularen');
define('form_hint_form_status',								''); 
define('form_hint_free_checkbox_hint_add',		'Setzen Sie ein Häkchen um eine Checkbox hinzufügen, entfernen Sie das Häkchen um eine Checkbox zu entfernen.');
define('form_hint_free_checkbox_hint_val',		'Geben Sie den WERT an, den die Checkbox übermitteln soll.');
define('form_hint_free_checkbox_hint_sel',		'Setzen Sie ein Häkchen, wenn die Checkbox in der Voreinstellung aktiviert sein soll.');
define('form_hint_free_checkbox_hint_txt',		'Geben Sie den TEXT an, der rechts neben der Checkbox angezeigt werden soll.');
define('form_hint_free_radio_hint_add',				'Setzen Sie ein Häkchen um einen Radiobutton hinzufügen, entfernen Sie das Häkchen um einen Radiobutton zu entfernen.');
define('form_hint_free_radio_hint_val',				'Geben Sie den WERT an, den dieser Radiobutton übermitteln soll.');
define('form_hint_free_radio_hint_sel',				'Wählen Sie diesen Schalter, wenn dieser Radiobutton in der Voreinstellung aktiviert sein soll.');
define('form_hint_free_radio_hint_txt',				'Geben Sie den TEXT an, der rechts neben dem Radiobutton angezeigt werden soll.');
define('form_hint_free_select_hint_add',			'Setzen Sie ein Häkchen um einen Eintrag zur Auswahlliste hinzufügen, entfernen Sie das Häkchen um einen Eintrag zu entfernen.');
define('form_hint_free_select_hint_val',			'Geben Sie den WERT an, den dieser Eintrag übermitteln soll.');
define('form_hint_free_select_hint_sel',			'Wählen Sie diesen Schalter, wenn dieser Eintrag in der Auswahlliste in der Voreinstellung aktiviert sein soll.');
define('form_hint_free_select_hint_txt',			'Geben Sie den TEXT an, der für diesen Eintrag in der Auswahlliste angezeigt werden soll.');
define('form_hint_free_field_add',						'Wählen Sie den gewünschten Datentyp aus und legen Sie im Eingabefeld einen <b>Titel</b> für das Datenfeld fest, dieser wird in der Feldliste verwendet und später auch im Formular neben dem Feld angezeigt.');
define('form_hint_free_field_type_text',			'Eingabefeld für TEXT');
define('form_hint_free_field_type_text_area',	'Eingabefeld für MASSENTEXT');
define('form_hint_free_field_type_checkbox',	'CHECKBOXEN definieren');
define('form_hint_free_field_type_hidden',		'Versteckte Datenfelder');
define('form_hint_free_field_type_html',			'HTML CODE');
define('form_hint_free_field_type_radiobutton','RADIOBUTTONS definieren');
define('form_hint_free_field_type_select',		'SELECT Auswahl definieren');
define('form_hint_kit_action_add',						'Sie können Aktionen festlegen, die für KeepInTouch automatisch durchgeführt werden, z.B. das Formular als Anmelde- oder Registrierdialog zu verwenden. Freie Datenfelder werden in diesem Fall von kitForm mit Ausnahme von Zuweisungen über versteckte Datenfelder ignoriert.');
define('form_hint_kit_address_type',					'Adresstyp (Privat, Dienstlich) zur Auswahl anzeigen');
define('form_hint_kit_city',									'');
define('form_hint_kit_company',								'');
define('form_hint_kit_country',								'');
define('form_hint_kit_department',						'');
define('form_hint_kit_email',									'<span style="color:#800000;">Das E-Mail Feld ist grundsätzlich Pflicht!</span>');
define('form_hint_kit_fax',										'');
define('form_hint_kit_field_add',							'KIT Datenfelder werden automatisch in KeepInTouch (KIT) übernommen. Bereits eingefügte KIT Datenfelder werden in der Auswahlliste nicht mehr angezeigt.');
define('form_hint_kit_first_name',						'');
define('form_hint_kit_last_name',							'');
define('form_hint_kit_link_add',							'KIT AKTIONS-Formular: <b>%s</b>');
define('form_hint_kit_newsletter',						'Anmeldung für die Newsletter ermöglichen');
define('form_hint_kit_password',							'Passwortabfrage für die Anmeldung und Registrierung');
define('form_hint_kit_password_retype',				'Passwortwiederholung (für die Registrierung)');
define('form_hint_kit_phone',									'');
define('form_hint_kit_phone_mobile',					'');
define('form_hint_kit_street',								'');
define('form_hint_kit_title',									'Anrede nach Geschlecht');
define('form_hint_kit_title_academic',				'Akademische Titel');
define('form_hint_kit_zip',										'');
define('form_hint_kit_zip_city',							'Die Eingabefelder für Postleitzahl und Stadt werden in einer Zeile zusammengefasst');

define('form_html_off',												'NUR TEXT Format');
define('form_html_on',												'HTML Format');

define('form_intro_edit_form',								'<p>Mit diesem Dialog erstellen und bearbeiten Sie Formulare für KeepInTouch (KIT)</p>');
define('form_intro_form_list',								'<p>Wählen Sie das gewünschte Formular zum Bearbeiten aus.</p><p>Um ein neues Formular zu erstellen wählen Sie direkt den Reiter "Bearbeiten".</p>');
define('form_intro_kit_fields',								'<p>Wählen Sie die Kontaktfelder aus KeepInTouch (KIT) aus, die im Formular verwendet werden sollen.</p>');
define('form_intro_protocol_detail',					'<p>Details zu dem abgesendeten Formular</p>');
define('form_intro_protocol_list',						'<p>Protokoll über die verwendeten Formulare.</p><p>Klicken Sie auf die <b>ID</b> oder das <b>Absendedatum</b> um Details des Formulars zu sehen, klicken Sie auf <b>Kontak</b>t um zu dem jeweiligen Eintrag in KeepInTouch zu gelangen.</p>');

define('form_label_data_type_label',					'Datentyp');
define('form_label_default_label',						'Vorgabewert');
define('form_label_form_captcha',							'CAPTCHA Spamschutz');
define('form_label_form_desc',								'Formular Beschreibung');
define('form_label_form_email_cc',						'E-Mail Empfänger (CC)');
define('form_label_form_email_html',					'E-Mail Format');
define('form_label_form_export',							'Formular exportieren');
define('form_label_free_field_add',						'Freies Datenfeld hinzufügen');
define('form_label_free_field_title',					'Titel eingeben...');
define('form_label_form_id',									'Formular ID');
define('form_label_form_name',								'Formular Bezeichner');
define('form_label_form_provider',						'E-Mail Dienstleister');
define('form_label_form_status',							'Status');
define('form_label_form_title',								'Formular Titel');
define('form_label_free_label_marker',				'%s <i style="font-weight:normal;">(FREE)</i>');
define('form_label_hint_label',								'Hilfe, Hinweis');
define('form_label_html_label',								'HTML Code');
define('form_label_import_form',              'Formular importieren:');
define('form_label_import_form_rename',       'Neuer Formular Bezeichner:');
define('form_label_kit_action_add',						'<i>KIT</i> Aktion hinzufügen');
define('form_label_kit_field_add',						'<i>KIT</i> Datenfeld hinzufügen');
define('form_label_kit_label_marker',					'%s <i style="font-weight:normal;">(KIT)</i>');
define('form_label_kit_link',									'<i>KIT LINK</i>: %s');
define('form_label_name_label',								'Feld Bezeichner');
define('form_label_size_label',								'Größe');
define('form_label_title_label',							'Feld Titel');
define('form_label_type_label',								'Feld Typ');
define('form_label_value_label',							'Feld Wert');

//define('form_mail_subject_client',						'Ihre Anfrage');
define('form_mail_subject_client_access',			'Ihre Zugangsdaten');
//define('form_mail_subject_client_register',		'Ihre Registrierung'); 
//define('form_mail_subject_provider',					'Anfrage über die Website');
//define('form_mail_subject_provider_register',	'Registrierung über die Website');

define('form_msg_account_updated',						'<p>Das Benutzerkonto wurde aktualisiert.</p>'); 
define('form_msg_captcha_invalid',						'<p>Der übermittelte CAPTCHA Code ist nicht korrekt, bitte prüfen Sie Ihre Eingabe!</p>');
define('form_msg_contact_already_registered',	'<p>Die E-Mail Adresse <b>%s</b> ist bereits registriert, bitte melden Sie sich mit Ihren Benutzerdaten an.</p>');
define('form_msg_contact_locked',							'<p>Das Benutzerkonto für die E-Mail Adresse <b>%s</b> ist zur Zeit gesperrt. Bitte setzen Sie sich mit dem Kundenservice in Verbindung!</p>');
define('form_msg_contact_not_active',					'<p>Das Benutzerkonto für die E-Mail Adresse <b>%s</b> ist nicht aktiv, bitte setzen Sie sich mit dem Kundenservice in Verbindung!</p>'); 
define('form_msg_date_invalid',								'<p><b>%s</b> ist kein gültiges Datum, bitte prüfen Sie Ihre Eingabe!</p>');
define('form_msg_email_not_registered',				'<p>Die E-Mail Adresse <b>%s</b> ist nicht registriert.</p>');
define('form_msg_form_deleted',								'<p>Das Formular mit der <b>ID %03d</b> wurde gelöscht!</p>');
define('form_msg_form_exported',							'<p>Das Formular wurde als <b><a href="%s">%s</a></b> erfolgreich exportiert (<i>Rechtsklick: <b>"Speichern unter ..."</b></i>).');
define('form_msg_form_inserted',							'<p>Das Formular mit der <b>ID %03d</b> wurde angelegt.</p>');
define('form_msg_form_name_empty',						'<p>Der <b>Formular Bezeichner</b> darf nicht leer sein und muss mindestens 3 Zeichen enthalten!</p>');
define('form_msg_form_name_rename_rejected',	'<p>Der Formular Bezeicher kann nicht in in <b>%s</b> geändert werden, dieser wird bereits von dem Formular mit der <b>ID %03d</b> verwendet.</p>');
define('form_msg_form_name_rejected',					'<p>Der Formular Bezeichner <b>%s</b> wird bereits von dem Formular mit der <b>ID %03d</b> verwendet, bitte suchen Sie einen anderen Bezeichner.</p>');
define('form_msg_form_title_empty',						'<p>Der <b>Formular Titel</b> darf nicht leer sein oder weniger als 5 Zeichen enthalten!</p>');
define('form_msg_form_updated',								'<p>Das Formular mit der <b>ID %03d</b> wurde aktualisiert.</p>');
define('form_msg_free_checkbox_invalid',			'<p>Die Definition der neuen Checkbox ist nicht vollständig, bitte geben Sie einen <b>Wert</b> und einen <b>Text</b> für die Checkbox an!</p>');
define('form_msg_free_field_invalid',					'<p>Bitte wählen Sie ein Datenfeld aus <b>und</b> geben Sie einen Titel für das neue Datenfeld an.</p>');
define('form_msg_free_field_add_form_null',		'<p>Ein allgemeines Datenfeld kann erst hinzugefügt werden, wenn der Datensatz für das Formular erfolgreich angelegt ist.</p>');
define('form_msg_free_field_add_success',			'<p>Das allgemeine Datenfeld "<b>%s</b>" wurde dem Formular hinzugefügt.</p>');
define('form_msg_free_radio_invalid',					'<p>Die Definition des neuen Radiobutton ist nicht vollständig, bitte geben Sie einen <b>Wert</b> und einen <b>Text</b> für den Radiobutton an!</p>');
define('form_msg_free_select_invalid',				'<p>Die Definition des neuen Eintrag für die Auswahlliste ist nicht vollständig, bitte geben Sie einen <b>Wert</b> und einen <b>Text</b> für den Eintrag an!</p>');
define('form_msg_kit_field_add_form_null',		'<p>Das KIT Datenfeld <b>%s</b> kann erst eingefügt werden, wenn der Datensatz für das Formular erfolgreich angelegt ist.</p>');
define('form_msg_kit_field_add_success',			'<p>Das KIT Datenfeld <b>%s</b> wurde dem Formular hinzugefügt.</p>');
define('form_msg_import_file_empty',          '<p>Die Datei <b>%s</b> enthält keine verwertbaren Formulardaten.</p>');
define('form_msg_import_name_already_exists', '<p>Der Formular Bezeichner <b>%s</b> wird bereits verwendet, der Import von <b>%s</b> wurde abgebrochen.</p>');
define('form_msg_import_file_version_invalid','<p>Die Datei <b>%s</b> enthält keine gültige Versionsinformation!</p>');
define('form_msg_import_success',             'Das Formular %s wurde erfolgreich importiert. \n');
define('form_msg_install_droplets_failed',		'Die Droplets für kitForm konnten nicht installiert werden.\nFehlermeldung:\n%s');
define('form_msg_install_droplets_success',		'Die Droplets für kitForm wurden erfolgreich installiert.');
define('form_msg_must_field_missing',					'<p>Das Feld <b>%s</b> ist ein <i>Pflichtfeld</i> und muss ausgefüllt werden.</p>');
define('form_msg_newsletter_abonnement_updated','<p>Das Newsletter Abonnement für die E-Mail Adresse <b>%s</b> wurde aktualisiert.</p>');
define('form_msg_not_authenticated',					'<p>Sie sind nicht angemeldet!</p>');
define('form_msg_provider_missing',						'<p>Sie haben noch keinen E-Mail Dienstleister für dieses Formular ausgewählt!</p>');
define('form_msg_field_removed',							'<p>Das Datenfeld <b>%s</b> wurde aus dem Formular entfernt.</p>');
define('form_msg_upload_no_file',             '<p>Es wurde keine Datei importiert!</p>');
define('form_msg_welcome',										'<p>Herzlich willkommen!<br />Ihre Benutzerdaten haben Sie per E-Mail erhalten.</p>');

define('form_protocol_form_send',							'[kitForm] Der Kontakt hat ein <a href="%s">Formular übermittelt</a>.'); 

define('form_status_active',									'Aktiv');
define('form_status_deleted',									'Gelöscht');
define('form_status_locked',									'Gesperrt');

define('form_tab_about',											'?');
define('form_tab_edit',												'Bearbeiten');
define('form_tab_list',												'Formulare');
define('form_tab_protocol',										'Protokoll');

define('form_text_must_field',								'als Pflichtfeld');
define('form_text_not_established',						'<i>- nicht festgelegt -</i>');
define('form_text_no_link_assigned',					'- nicht zugeordnet -');
define('form_text_select_free_field',					'- Datenfeld auswählen -');
define('form_text_select_kit_action',					'- Aktion auswählen -');
define('form_text_select_kit_field',					'- Datenfeld auswählen -');
define('form_text_select_provider',						'- Provider auswählen -');

define('form_th_contact',											'Kontakt');
define('form_th_datetime',										'Datum/Zeit');
define('form_th_email',												'E-Mail');
define('form_th_form_name',										'Formular');
define('form_th_id',													'ID');
define('form_th_name',												'Bezeichner');
define('form_th_status',											'Status');
define('form_th_title',												'Titel');
define('form_th_timestamp',										'letzte Änderung');

define('form_type_checkbox',									'Checkbox');
define('form_type_hidden',										'Verstecktes Datenfeld');
define('form_type_html',											'HTML Code (freie Eingabe)');
define('form_type_kit',												'KeepInTouch Datenfeld');
define('form_type_radio',											'Radiobutton');
define('form_type_select',										'Auswahlliste');
define('form_type_text',											'Eingabefeld (max. 255 Zeichen)');
define('form_type_text_area',									'Textfeld (max. 65.536 Zeichen');
define('form_type_undefined',									'- nicht festgelegt -');

?>