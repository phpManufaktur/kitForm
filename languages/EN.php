<?php

/**
 * kitForm
 * 
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2011-2012 - phpManufaktur by Ralf Hertsch
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 * 
 * FOR VERSION- AND RELEASE NOTES PLEASE LOOK AT INFO.TXT!
 */

/**
 * ATTENTION: Then english language file contains only some entries, nearly all
 * definitions are determined in the source code, here you will find only some
 * typos and improved translations.
 * 
 * PLEASE LOOK AT DE.PHP TO GET A COMPLETE LANGUAGE FILE!
 */

$LANG = array(
        // Hint texts are defined as typos
        'hint_form_id'
            => '',
        'hint_form_email_cc' 
            => 'Sie können <b>zusätzliche E-Mail Empfänger</b> festlegen, diese erhalten ebenfalls eine Information über neu empfangene Formulare. Trennen Sie die E-Mail Adressen mit einem Komma voneinander.',
        'hint_form_email_html'
            => 'Legen Sie fest ob die Benachrichtigungsmails im HTML Format oder als NUR TEXT versendet werden sollen.',
        'hint_form_export'
            => 'Sie können diesen Dialog als Datei exportieren und ihn in anderen Installationen importieren.',
        'hint_form_name'
            => 'Legen Sie einen <b>Bezeichner</b> z.B. "kontakt" für diesen Dialog fest. Der Bezeichner darf keine Sonderzeichen, Leerzeichen, Umlaute o.ä. enthalten, er wird im Droplet <i>[[kit_form?form=<b>kontakt</b>]]</i> als Parameter für das Formular verwendet. Bezeichner werden automatisch bereinigt und in Kleinbuchstaben umgewandelt!',
        'hint_form_title'
            => 'Legen Sie zur einfachen Kennzeichnung des Formulares einen Titel für das Formular fest, z.B. "Newsletter Anmeldung". Der Formular Titel wird als <b>Betreff</b> bzw. <b>Subject</b> in den Benachrichtigungs E-Mails verwendet.',
        'hint_form_desc'
            => 'Beschreiben Sie die Funktion des Formulares.',
        'hint_form_captcha'
            => 'Schalten Sie den CAPTCHA Spamschutz ein oder aus.',
        'hint_form_provider'
            => 'Wählen Sie einen <b><a href="{{ admin_url }}/admintools/tool.php?tool=kit&act=cfg&ctab=cftp">aktiven Dienstleister</a></b> für den E-Mail Versand dieses Formular aus. Der Dienstleister erhält automatisch eine Benachrichtigung bei neu eingegangenen Formularen',
        'hint_form_status'
            => '',
        'hint_free_checkbox_hint_add'
            => 'Setzen Sie ein Häkchen um eine Checkbox hinzufügen, entfernen Sie das Häkchen um eine Checkbox zu entfernen.',
        'hint_free_checkbox_hint_val'
            => 'Geben Sie den WERT an, den die Checkbox übermitteln soll.',
        'hint_free_checkbox_hint_sel'
            => 'Setzen Sie ein Häkchen, wenn die Checkbox in der Voreinstellung aktiviert sein soll.',
        'hint_free_checkbox_hint_txt'
            => 'Geben Sie den TEXT an, der rechts neben der Checkbox angezeigt werden soll.',
        'hint_free_radio_hint_add'
            => 'Setzen Sie ein Häkchen um einen Radiobutton hinzufügen, entfernen Sie das Häkchen um einen Radiobutton zu entfernen.',
        'hint_free_radio_hint_val'
            => 'Geben Sie den WERT an, den dieser Radiobutton übermitteln soll.',
        'hint_free_radio_hint_sel'
            => 'Wählen Sie diesen Schalter, wenn dieser Radiobutton in der Voreinstellung aktiviert sein soll.',
        'hint_free_radio_hint_txt'
            => 'Geben Sie den TEXT an, der rechts neben dem Radiobutton angezeigt werden soll.',
        'hint_free_select_hint_add'
            => 'Setzen Sie ein Häkchen um einen Eintrag zur Auswahlliste hinzufügen, entfernen Sie das Häkchen um einen Eintrag zu entfernen.',
        'hint_free_field_type_delayed'
            => 'Enables a delayed transmission of the form. If checked by the user the form will be saved but not processed and the user get a link to edit and submit the saved form.',
        'hint_free_select_hint_val'
            => 'Geben Sie den WERT an, den dieser Eintrag übermitteln soll.',
        'hint_free_select_hint_sel'
            => 'Wählen Sie diesen Schalter, wenn dieser Eintrag in der Auswahlliste in der Voreinstellung aktiviert sein soll.',
        'hint_free_select_hint_txt'
            => 'Geben Sie den TEXT an, der für diesen Eintrag in der Auswahlliste angezeigt werden soll.',
        'hint_free_field_add'
            => 'Wählen Sie den gewünschten Datentyp aus und legen Sie im Eingabefeld einen <b>Titel</b> für das Datenfeld fest, dieser wird in der Feldliste verwendet und später auch im Formular neben dem Feld angezeigt.',
        'hint_free_field_type_text'
            => 'Eingabefeld für TEXT',
        'hint_free_field_type_text_area'
            => 'Eingabefeld für MASSENTEXT',
        'hint_free_field_type_checkbox'
            => 'CHECKBOXEN definieren',
        'hint_free_field_type_file'
            => 'Define File Uploads',
        'hint_free_field_type_hidden'
            => 'Versteckte Datenfelder',
        'hint_free_field_type_html'
            => 'HTML CODE',
        'hint_free_field_type_radiobutton'
            => 'RADIOBUTTONS definieren',
        'hint_free_field_type_select'
            => 'SELECT Auswahl definieren',
        'hint_kit_action_add'
            => 'Sie können Aktionen festlegen, die für KeepInTouch automatisch durchgeführt werden, z.B. das Formular als Anmelde- oder Registrierdialog zu verwenden. Freie Datenfelder werden in diesem Fall von kitForm mit Ausnahme von Zuweisungen über versteckte Datenfelder ignoriert.',
        'hint_kit_address_type'
            => 'Adresstyp (Privat, Dienstlich) zur Auswahl anzeigen',
        'hint_kit_city'
            => '',
        'hint_kit_company'
            => '',
        'hint_kit_country'
            => '',
        'hint_kit_department'
            => '',
        'hint_kit_email'
            => '<span style="color:#800000;">Das E-Mail Feld ist grundsätzlich Pflicht!</span>',
        'hint_kit_email_retype'
            => '',
        'hint_kit_fax'
            => '',
        'hint_kit_field_add'
            => 'KIT Datenfelder werden automatisch in KeepInTouch (KIT) übernommen. Bereits eingefügte KIT Datenfelder werden in der Auswahlliste nicht mehr angezeigt.',
        'hint_kit_first_name'
            => '',
        'hint_kit_last_name'
            => '',
        'hint_kit_link_add'
            => 'KIT AKTIONS-Formular: <b>{{ form }}</b>',
        'hint_kit_newsletter'
            => 'Anmeldung für die Newsletter ermöglichen',
        'hint_kit_password'
            => 'Passwortabfrage für die Anmeldung und Registrierung',
        'hint_kit_password_retype'
            => 'Passwortwiederholung (für die Registrierung)',
        'hint_kit_phone'
            => '',
        'hint_kit_phone_mobile'
            => '',
        'hint_kit_street'
            => '',
        'hint_kit_title'
            => 'Anrede nach Geschlecht',
        'hint_kit_title_academic'
            => 'Akademische Titel',
        'hint_kit_zip'
            => '',
        'hint_kit_zip_city'
            => 'Die Eingabefelder für Postleitzahl und Stadt werden in einer Zeile zusammengefasst',

        // Labels are defined as typos!
        'label_data_type_label'
            => 'Datentyp',
        'label_default_label'
            => 'Vorgabewert',
        'label_form_captcha'
            => 'CAPTCHA Spamschutz',
        'label_form_desc'
            => 'Formular Beschreibung',
        'label_form_email_cc'
            => 'E-Mail Empfänger (CC)',
        'label_form_email_html'
            => 'E-Mail Format',
        'label_form_export'
            => 'Formular exportieren',
        'label_free_field_add'
            => 'Freies Datenfeld hinzufügen',
        'label_form_id'
            => 'Formular ID',
        'label_form_name'
            => 'Formular Bezeichner',
        'label_form_provider'
            => 'E-Mail Dienstleister',
        'label_form_status'
            => 'Status',
        'label_form_title'
            => 'Formular Titel',
        'label_free_field_title'
            => '',
        'label_free_label_marker'
            => '{{ title }} <i style="font-weight:normal;">(FREE)</i>',
        'label_hint_label'
            => 'Hilfe, Hinweis',
        'label_html_label'
            => 'HTML Code',
        'label_import_form'
            => 'Formular importieren:',
        'label_import_form_rename'
            => 'Neuer Formular Bezeichner:',
        'label_kit_action_add'
            => '<i>KIT</i> Aktion hinzufügen',
        'label_kit_field_add'
            => '<i>KIT</i> Datenfeld hinzufügen',
        'label_kit_label_marker'
            => '{{ name }} <i style="font-weight:normal;">(KIT)</i>',
        'label_kit_link'
            => '<i>KIT LINK</i>: {{ text }}',
        'label_name_label'
            => 'Feld Bezeichner',
        'label_size_label'
            => 'Größe',
        'label_title_label'
            => 'Feld Titel',
        'label_type_label'
            => 'Feld Typ',
        'label_value_label'
            => 'Feld Wert',
            
        // table header
        'th_contact' 
            => 'Contact',
        'th_datetime' 
            => 'Date/Time',
        'th_email' 
            => 'E-Mail',
        'th_form_name' 
            => 'Form',
        'th_id' 
            => 'ID',
        'th_name' 
            => 'Name',
        'th_status' 
            => 'Status',
        'th_title' 
            => 'Title',
        'th_timestamp' 
            => 'Last change',
        
    );