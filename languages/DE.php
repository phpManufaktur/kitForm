<?php

/**
 * kitForm
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2011 - 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

// include class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {
  if (defined('LEPTON_VERSION'))
    include(WB_PATH.'/framework/class.secure.php');
}
else {
  $oneback = "../";
  $root = $oneback;
  $level = 1;
  while (($level < 10) && (!file_exists($root.'/framework/class.secure.php'))) {
    $root .= $oneback;
    $level += 1;
  }
  if (file_exists($root.'/framework/class.secure.php')) {
    include($root.'/framework/class.secure.php');
  }
  else {
    trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
  }
}
// end include class.secure.php

if ('á' != "\xc3\xa1") {
  // important: language files must be saved as UTF-8 (without BOM)
  trigger_error('The language file <b>'.basename(__FILE__).'</b> is damaged, it must be saved <b>UTF-8</b> encoded!', E_USER_ERROR);
}

$LANG = array(
  '- no action -'
      => '- keine Aktion -',
  '- not assigned -'
      => '- nicht zugeordnet -',
  '- please select -'
    => '- bitte auswählen -',
  '- select country -'
      => '- Land auswählen -',
  '- select datafield -'
      => '- Datenfeld auswählen -',
  '- select KIT action -'
      => '- Aktion auswählen -',
  '- select provider -'
      => '- Dienstleister auswählen -',
  '<i>- undetermined -</i>'
      => '<i>- nicht festgelegt -</i>',
  '[kitForm] File <a href="{{ link }}">{{ file }}</a> uploaded.'
      => 'Datei <a href="{{ link }}">{{ file }}</a> übertragen.',
  '[kitForm] The contact has saved a form for later transmission (ID {{ id }}).'
      => '[kitForm] Der Kontakt hat ein Formular zur späteren Übermittlung gespeichert (ID {{ id }}).',
  '[kitForm] The contact has <a href="{{ url }}">submitted a form</a>.'
      => '[kitForm] Der Kontakt hat ein <a href="{{ url }}">Formular übermittelt</a>.',
  '[kitForm] The contact has <a href="{{ url }}">submitted a feedback</a>.'
      => '[kitForm] Der Kontakt hat ein <a href="{{ url }}">Feedback übermittelt</a>.',
  '[kitForm] The temporary saved form with the ID {{ id }} was deleted.'
      => '[kitForm] Das temporär gespeicherte Formular mit der ID {{ id }} wurde gelöscht.',

  'Abort'
      => 'Abbruch',
  'About'
      => '?',
  'Account'
      => 'Benutzerkonto',
  'Active'
      => 'Aktiv',
  'Admin'
    => 'Verwaltung',
  'Admin functions'
    => 'Administrative Funktionen',
  'Allowed filetypes'
      => 'Erlaubte Dateitypen',
  'Assume that unpublished feedbacks are SPAM. This function select them and let you delete the form data and additional delete also the associated KIT ID\'s'
    => 'Angenommen wird, dass nicht veröffentlichte Feedbacks SPAM sind. Diese Funktion wählt diese Feedbacks aus und ermöglicht es Ihnen die Formulardaten zu löschen und darüber hinaus auch die zugeordnenten KIT ID\'s',

  'Can\'t create the .htaccess file!'
      => 'Die .htaccess Datei konnte nicht erzeugt werden!',
  'Can\'t create the .htpasswd file!'
      => 'Die .htpasswd Datei konnte nicht erzeugt werden!',
  'Cant\'t load the form <b>{{ form }}</b>!'
      => 'Das Formular mit dem Bezeichner <b>{{ form }}</b> wurde nicht gefunden!',
  'Can\'t open the directory <b>{{ directory }}</b>!'
      => 'Das Verzeichnis <b>{{ directory }}</b> konnte nicht geöffnet werden!',
  'Can\'t send the email to <b>{{ email }}</b>!'
      => 'Die E-Mail an <b>{{ email }}</b> konnte nicht versendet werden!',
  'Change password'
    => 'Passwort ändern',
  '<p>Cannot detect any duplicate form data records!</p>'
    => '<p>Konnte keine doppelten Formular Datensätze feststellen!</p>',
  'Chars'
    => 'Zeichen',
  'Checkbox'
      => 'Checkbox',

  'Date'
      => 'Datum',
  'Delayed execution'
      => 'Übermittlung verzögern',
  'Delete'
      => 'Löschen',
  'Delete the feedback data records!'
    => 'Vorsicht! Feedback Datensätze löschen!',
  'Delete the feedback data records and the associated KIT ID\'s!'
    => 'Vorsicht! Feedback Datensätze sowie die zugeordneten KIT ID\'s löschen!',
  '<p>Delete the form data record <b>{{ id }}</b>.</p>'
    => 'Der Formular Datensatz <b>{{ id }}</b> wurde gelöscht!</p>',
  'Delete these records!'
    => 'Diese Datensätze löschen!',
  'Deleted'
      => 'Gelöscht',
  '<p>Deleted {{ count }} duplicate form data records!</p>'
    => '<p>Es wurden {{ count }} doppelte Datensätze gelöscht!</p>',
  '<p>Deleted {{ count }} feedback records.</p>'
    => '<p>Es wurden {{ count }} Feedback Datensätze gelöscht.</p>',
  '<p>Deleted {{ count }} feedback records and the associated KIT ID\'s.</p>'
    => '<p>Es wurden {{ count }} Feedback Datensätze sowie die zugeordneten KeepInTouch (KIT) ID\'s gelöscht.</p>',
  'Details of the submitted form'
      => 'Details zu dem abgesendeten Formular',
  'Duplicate data records'
    => 'Doppelte Datensätze',

  'Edit'
      => 'Bearbeiten',
  'Edit the form'
      => 'Formular bearbeiten',
  'Enter title ...'
      => 'Titel eingeben ...',
  'Error: Please upgrade <b>{{ addon }}</b>, installed is release <b>{{ release }}</b>, needed is release <b>{{ needed }}</b>.'
    => '<p>Bitte aktualisieren Sie <b>{{ addon }}</b>. Installiert ist die Release <b>{{ release }}</b>, benötigt wird die Release <b>{{ needed }}</b>.</p>',
  'Error: The <b>ID {{ id }}</b> is invalid.'
      => 'Fehler: Die <b>ID {{ id }}</b> ist ungültig.',
  'Error creating the directory <b>{{ directory }}</b>.'
      => 'Das Verzeichnis <b>{{ directory }}</b> konnte nicht erstellt werden.',
  'Error executing template <b>{{ template }}</b>:<br />{{ error }}'
      => '<p>Fehler bei der Ausführung des Template <b>{{ template }}</b>:</p><p>{{ error }}</p>',
  'Error installing the Droplets for kitForm:\n{{ error }}\n'
      => 'Die Droplets für kitForm konnten nicht installiert werden.\nFehlermeldung:\n{{ error }}\n',
  'Error moving the file <b>{{ file }}</b> to the target directory!'
      => 'Die Datei <b>{{ file }}</b> konnte nicht in das Zielverzeichnis verschoben werden!',
  'Error reading the file <b>{{ file }}</b>.'
      => '<p>Die Datei <b>%s</b> konnte nicht eingelesen werden!</p>',
  'Error: The template {{ template }} does not exists in any of the possible paths!'
      => 'Das Template {{ template }} wurde in keinem der möglichen Pfade gefunden!',
  'Error writing the file <b>{{ file }}</b>.'
      => 'Fehler beim Schreiben der Datei <b>{{ file }}</b>.',
  'Export form data'
    => 'Exportiere Formulardaten',
  'Export the submitted form data for a specific form in CSV format'
    => 'Exportiert übermittelte Formulardaten für ein spezifisches Formular im CSV Format (für Excel etc.)',
  '<p>Exported {{ count }} form data records as CSV file.</p><p>Please download the CSV file <a href="{{ download }}">{{ file }}</a>.</p>'
    => '<p>Es wurden {{ count }} Formular Datensätze als CSV Datei exportiert.</p><p>Bitte laden Sie sich die CSV Datei <a href="{{ download }}">{{ file }}</a> herunter.</p>',

  'File upload'
      => 'Datenübertragung',
  'Forgotten password'
      => 'Passwort zusenden',
  'Float'
      => 'Dezimalzahl',

  'Hidden field'
      => 'Verstecktes Datenfeld',
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
  'hint_free_select_hint_val'
      => 'Geben Sie den WERT an, den dieser Eintrag übermitteln soll.',
  'hint_free_select_hint_sel'
      => 'Wählen Sie diesen Schalter, wenn dieser Eintrag in der Auswahlliste in der Voreinstellung aktiviert sein soll.',
  'hint_free_select_hint_txt'
      => 'Geben Sie den TEXT an, der für diesen Eintrag in der Auswahlliste angezeigt werden soll.',
  'hint_free_field_add'
      => 'Wählen Sie den gewünschten Datentyp aus und legen Sie im Eingabefeld einen <b>Titel</b> für das Datenfeld fest, dieser wird in der Feldliste verwendet und später auch im Formular neben dem Feld angezeigt.',
  'hint_free_field_type_delayed'
      => 'Ermöglicht es dem Kunden das Formular zu speichern jedoch noch nicht abzusenden. Der Kunde erhält einen Link der das Beabeiten und Absenden des Formular ermöglicht.',
  'hint_free_field_type_text'
      => 'Eingabefeld für TEXT',
  'hint_free_field_type_text_area'
      => 'Eingabefeld für MASSENTEXT',
  'hint_free_field_type_checkbox'
      => 'CHECKBOXEN definieren',
  'hint_free_field_type_file'
      => 'Die Übertragungsmethode "<b>standard</b>" verwendet das normale Dateiauswahlfeld für Formulare.<br />Für die alternative Übertragungsmethode "<b>uploadify</b>" muß der <b>kitUploader</b> installiert sein. In diesem Fall können nur die festgelegten Dateitypen für den Upload ausgewählt werden und während der Datenübertragung wird ein Fortschrittsbalken eingeblendet. "<b>uploadify</b>" ist für Dateien ab 2 MB geeignet.<br />Legen Sie die erlaubten Dateitypen fest, geben Sie keine Dateitypen an können alle Dateien hochgeladen werden.<br />Die max. Dateigröße ist abhängig von der Konfiguration Ihres Webservers, bitten Sie ggf. Ihren Webmaster die Einstellungen zu ändern.',
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
  'hint_kit_birthday'
      => '',
  'hint_kit_city'
      => '',
  'hint_kit_company'
      => '',
  'hint_kit_contact_language'
      => 'Dieses versteckte Datenfeld hält die Sprache der Seite fest, in der das Formular angezeigt wird und übernimmt diese als "Kontakt Sprache" nach KIT.',
  'hint_kit_country'
      => '',
  'hint_kit_department'
      => '',
  'hint_kit_email'
      => '<span style="color:#800000;">Das E-Mail Feld ist grundsätzlich Pflicht!</span>',
  'hint_kit_email_retype'
      => 'Die E-Mail Adresse muss wiederholt werden um Tippfehler auszuschließen.',
  'hint_kit_fax'
      => '',
  'hint_kit_field_add'
      => 'KIT Datenfelder werden automatisch in KeepInTouch (KIT) übernommen. Bereits eingefügte KIT Datenfelder werden in der Auswahlliste nicht mehr angezeigt.',
  'hint_kit_first_name'
      => '',
  'hint_kit_free_field_1'
      => 'Frei definiertes KIT Eingabefeld',
  'hint_kit_free_field_2'
      => 'Frei definiertes KIT Eingabefeld',
  'hint_kit_free_field_3'
      => 'Frei definiertes KIT Eingabefeld',
  'hint_kit_free_field_4'
      => 'Frei definiertes KIT Eingabefeld',
  'hint_kit_free_field_5'
      => 'Frei definiertes KIT Eingabefeld',
  'hint_kit_free_note_1'
      => 'Frei definiertes KIT Notizfeld',
  'hint_kit_free_note_1'
      => 'Frei definiertes KIT Notizfeld',
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
  'HTML Code (free format)'
      => 'HTML Code (freie Eingabe)',
  'HTML Format'
      => 'HTML Format',

  'Import ...'
      => 'Importieren ...',
  'Input field (max. 255 chars)'
      => 'Eingabefeld (max. 255 Zeichen)',
  'Integer'
      => 'Ganzzahl',
  'Invalid function call'
      => 'Ungültiger oder unvollständiger Funktionsaufruf.',

  'kitForm can\'t determine the URL of the calling page.'
      => 'kitForm konnte die URL der aufrufenden Seite nicht ermitteln!',
  '<p>kitForm provide you with some administrative functions for data handling.</p>'
    => '<p>kitForm unterstützt Sie bei der Datenpflege mit administrativen Funktionen.</p>',

  'label_count_chars'
    => 'Zeichen zählen',
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
  'label_limit_chars'
    => 'Zeichen begrenzen',
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
  'List'
      => 'Übersicht',
  'List of all available forms'
      => 'Übersicht über die verfügbaren Formulare',
  'Locked'
      => 'Gesperrt',
  'Login'
      => 'Anmeldung',
  'Logout'
      => 'Abmeldung/Logout',

  'mark as must field'
      => 'als Pflichtfeld',
  'max. filesize (MB)'
      => 'Max. Dateigröße (MB)',
  'Missing the datafield <b>{{ field }}</b>!'
      => 'Das Datenfeld <b>{{ field }}</b> ist nicht gesetzt!',
  'Missing the form ID!'
      => 'Es wurde keine ID für das Formular übergeben!',
  'Move item down'
      => 'Element nach unten schieben',
  'Move item up'
      => 'Element nach oben schieben',

  '<p>No valid form ID submitted!</p>'
    => '<p>Es wurde keine gültige Formular ID übermittelt!</p>',

  'Off'
      => 'Ausgeschaltet',
  'OK'
      => 'OK',
  'On'
      => 'Angeschaltet',

  'Protocol'
      => 'Protokoll',
  '<p>At minimum the <b>form title</b> must be 5 or more characters long!</p>'
      => '<p>Der <b>Formular Titel</b> darf nicht leer sein oder weniger als 5 Zeichen enthalten!</p>',
  'Please check the forms <b>{{ ids }}</b>.<br />For these forms is no <b>provider</b> defined and they will not work proper!'
      => '<p>Bitte überprüfen Sie die Formulare <b>{{ ids }}</b>!</p><p>Bei diesen Formularen ist <b>kein Dienstleister festgelegt</b> und sie können nicht fehlerfrei ausgeführt werden!</p>',
  'Please enter your email address to unsubscribe from automatical reports at new feedbacks of this site.'
      => '<p>Bitte tragen Sie Ihre E-Mail Adresse ein, um sich von den automatischen Benachrichtigungen bei neuen Kommentaren zu dieser Seite abzumelden.</p>',
  '<p>Please log in to change your password!</p>'
    => '<p>Sie müssen angemeldet sein um Ihr Passwort zu ändern!</p>',
  '<p>Please save the new form before you insert the datafield <b>{{ field }}</b>!</p>'
      => '<p>Das KIT Datenfeld <b>{{ field }}</b> kann erst eingefügt werden, wenn der Datensatz für das Formular erfolgreich angelegt ist.</p>',
  '<p>Please select a datafield <b>and</b> specify a title for the new field!</p>'
      => '<p>Bitte wählen Sie ein Datenfeld aus <b>und</b> geben Sie einen Titel für das neue Datenfeld an.</p>',
  '<p>Please select a service provider for this form!</p>'
      => '<p>Sie haben noch keinen E-Mail Dienstleister für dieses Formular ausgewählt!</p>',
  '<p>Please select the form for which you want to export the submitted data.</p>'
    => '<p>Bitte wählen Sie das Formular aus, zu dem Sie die übermittelten Daten exportieren möchten.</p>',
  '<p>Please start the file upload for the GUID <b>{{ guid }}</b>.</p>'
    => '<p>Bitte starten Sie die Datenübertragung für die GUID <b>{{ guid }}</b>.</p>',
  '<p>Please type in the birthday like <b>{{ date_str }}</b>.</p>'
      => '<p>Bitte geben Sie Ihren Geburtstag in der Form <b>{{ date_str }}</b> an.</p>',
  '<p>Please upload only files with the extension <b>{{ extensions }}</b>, the file {{ file }} is refused.</p>'
      => '<p>Bitte übertragen Sie nur Dateien mit den Endungen <b>{{ extensions }}</b>, die Datei <b>{{ file }}</b> wird zurückgewiesen.</p>',
  'Please wait <strong>{{ seconds }}</strong> seconds before submitting the the form!'
    => 'Bitte warten Sie noch <strong>{{ seconds }}</strong> Sekunden bis Sie das Formular abschicken!',
  'Protocol Details'
      => 'Protokoll, Details',
  'Protocol List'
      => 'Protokoll',
  'Protocol of the submitted forms.<br />Click at the <b>ID</b> or the submission date to get details of the submitted form.<br />Click at contact to switch to KeepInTouch (KIT) and get details of the contact.'
      => '<p>Protokoll der übermittelten Formulare.</p><p>Klicken Sie auf die <b>ID</b> oder das <b>Absendedatum</b> um Details des Formulars zu sehen, klicken Sie auf <b>Kontak</b>t um zu dem jeweiligen Eintrag in KeepInTouch zu gelangen.</p>',

  'Radiobutton'
      => 'Radiobutton',
  'Register'
      => 'Registrierung',
  'Remove duplicate submitted form data'
    => 'Entferne doppelt übermittelte Formular Einträge',
  'Return ...'
    => 'Zurück ...',

  'Save the form and submit it later'
      => 'Formular speichern und Daten noch nicht übermitteln',
  'Search for unpublished feedbacks'
    => 'Suche nach nicht veröffentlichten Feedbacks',
  'Select File'
      => 'Datei auswählen',
  'Select form'
    => 'Formular auswählen',
  'Select a form to get details and editing.<br />To create a new form please select the tab "Edit".'
      => '<p>Wählen Sie das gewünschte Formular zum Bearbeiten aus.</p><p>Um ein neues Formular zu erstellen wählen Sie direkt den Reiter "Bearbeiten".</p>',
  'Select the form'
    => 'Formular auswählen',
  'Select the KeepInTouch (KIT) contact fields you wish to use with this form.'
      => '<p>Wählen Sie die Kontaktfelder aus KeepInTouch (KIT) aus, die im Formular verwendet werden sollen.</p>',
  'Selection list'
      => 'Auswahlliste',
  'Sometimes it could happen that form data are submitted twice, this function try to find them.'
    => 'Gelegentlich kommt es vor, dass Formulardaten doppelt übermittelt werden. Diese Funktion versucht diese zu finden und kann sie löschen.',
  'Subscribe/unsubribe Newsletter'
      => 'Newsletter An-/Abmeldung',
  '<p>System does not allow uploads greater than <b>{{ max_filesize }} MB</b>. Please contact your webmaster to increase this value.</p>'
      => '<p>Die Systemeinstellungen erlauben keine Übertragungen von Dateien, die größer sind als <b>{{ max_filesize }} MB</b>. Bitten Sie Ihren Webmaster diesen Wert zu erhöhen.</p>',

  'Text'
      => 'Text',
  'TEXT Format'
      => 'TEXT Format',
  'Textarea (max. 65,536 chars)'
      => 'Textfeld (max. 65.536 Zeichen)',
  'th_contact'
      => 'Kontakt',
  'th_datetime'
      => 'Datum/Zeit',
  'th_email'
      => 'E-Mail',
  'th_form_name'
      => 'Formular',
  'th_id'
      => 'ID',
  'th_name'
      => 'Bezeichner',
  'th_status'
      => 'Status',
  'th_title'
      => 'Titel',
  'th_timestamp'
      => 'letzte Änderung',
  '<p>Thank you for the feedback!</p><p>Your feedback is already published, we have send you a copy to your email address <b>{{ email }}</b>.</p>'
      => '<p>Vielen Dank für Ihr Beitrag!</p><p>Ihr Feedback wurde sofort freigeschaltet und veröffentlicht, eine Kopie haben wir Ihnen an Ihre E-Mail Adresse <b>{{ email }}</b> gesendet.</p>',
  '<p>Thank your for the feedback!</p><p>We will check and publish your feedback as soon as possible. We have send you a copy of your feedback to your email address <b>{{ email }}</b>.</p>'
      => '<p>Vielen Dank für Ihren Beitrag!</p><p>Ihr Feedback wird vor der Veröffentlichung durch unser Team geprüft, wir bemühen uns um eine rasche Freigabe. Eine Kopie Ihres Beitrag haben wir an Ihre E-Mail Adresse <b>{{ email }}</b> gesendet.</p>',
  '<p>The account for the email address <b>{{ email }}</b> is locked. Please contact the service!</p>'
      => '<p>Das Benutzerkonto für die E-Mail Adresse <b>{{ email }}</b> ist zur Zeit gesperrt. Bitte setzen Sie sich mit dem Kundenservice in Verbindung!</p>',
  '<p>The account for the email address <b>{{ email }}</b> is not active, please contact the service!</p>'
      => '<p>Das Benutzerkonto für die E-Mail Adresse <b>%s</b> ist nicht aktiv, bitte setzen Sie sich mit dem Kundenservice in Verbindung!</p>',
  '<p>The both passwords does not match, please check your input!</p>'
      => '<p>Die beiden Passwörter stimmen nicht überein, bitte wiederholen Sie Ihre Eingabe!</p>',
  '<p>The CAPTCHA code is not correct, please try again!</p>'
      => '<p>Der übermittelte CAPTCHA Code ist nicht korrekt, bitte prüfen Sie Ihre Eingabe!</p>',
  'The command is not complete, missing parameters!'
      => 'Die Parameter sind nicht vollständig.',
  '<p>The datafield <b>{{ field }}</b> was removed.</p>'
      => '<p>Das Datenfeld <b>{{ field }}</b> wurde aus dem Formular entfernt.</p>',
  'The datafields for the email address and/or the password are empty, please check!'
      => 'Die Datenfelder für E-Mail Adresse und/oder Passwort sind nicht gesetzt!',
  '<p>The datatype {{ datatype }} is not supported!</p>'
      => '<p>Der Datentyp {{ datatype }} wird nicht unterstützt!</p>',
  'The datatype <b>{{ type }}</b> is not supported!'
      => 'Der Datentyp <b>{{ type }}</b> wird nicht unterstützt!',
  '<p>The date <b>{{ date }}</b> is invalid!</p>'
      => 'Die Datumsangabe <b>{{ date }}</b> ist ungültig!</p>',
  '<p>The definition of the new checkbox is not complete. Please specify a <b>value</b> and a <b>text</b> for it!</p>'
      => '<p>Die Definition der neuen Checkbox ist nicht vollständig, bitte geben Sie einen <b>Wert</b> und einen <b>Text</b> für die Checkbox an!</p>',
  '<p>The definition of the new radiobutton is not complete. Please specify a <b>value</b> and a <b>text</b> for it!</p>'
      => '<p>Die Definition des neuen Radiobutton ist nicht vollständig, bitte geben Sie einen <b>Wert</b> und einen <b>Text</b> für den Radiobutton!</p>',
  '<p>The definition of the new selection list is not complete. Please specify a <b>value</b> and a <b>text</b> for it!</p>'
      => '<p>Die Definition des neuen Auswahlliste ist nicht vollständig, bitte geben Sie einen <b>Wert</b> und einen <b>Text</b> für die Liste an!</p>',
  'The droplets for kitForm were successfully installed.\n'
      => 'Die Droplets für kitForm wurden erfolgreich installiert.\n',
  '<p>The following data records exists twice and can be deleted.</p>'
    => '<p>Die aufgelisteten Datensätze existieren mehrfach und können ohne Datenverlust gelöscht werden.</p>',
  '<p>The email address and the retyped email address does not match!</p>'
      => '<p>Die E-Mail Adresse und die Wiederholung der E-Mail Adresse stimmen nicht überein!</p>',
  '<p>The email address <b>{{ email }}</b> is already registered, please login with your user data!</p>'
      => '<p>Die E-Mail Adresse <b>{{ email }}</b> ist bereits registriert, bitte melden Sie sich mit Ihren Benutzerdaten an.</p>',
  '<p>The email address <b>{{ email }}</b> is not valid, please check your input.</p>'
      => '<p>Die E-Mail Adresse <b>{{ email }}</b> ist nicht gültig, bitte prüfen Sie Ihre Eingabe.</p>',
  '<p>The email address <b>{{ email }}</b> is not registered.</p>'
      => '<p>Die E-Mail Adresse <b>{{ email }}</b> ist nicht registriert.</p>',
  '<p>The email address <b>{{ email }}</b> does no longer receive messages at new feedbacks on this page.</p><p>The settings of other pages are not changed!</p>'
      => '<p>Die E-Mail Adresse <b>{{ email }}</b> erhält keine Benachrichtigungen mehr, wenn Kommentare auf dieser Seite hinzugefügt werden.</p><p>Benachrichtigungen von andern Seiten sind hiervon nicht betroffen.</p>',
  '<p>The email address <b>{{ email }}</b> does not receive any messages from this page, so nothing was changed.</p>'
      => '<p>Auf dieser Seite sind keine Benachrichtigungen für die E-Mail Adresse <b>{{ email }}</b> aktiv, es wurde nichts geändert.</p>',
  'The feedback form is not complete - missing the datafield <b>feedback_url</b>!'
      => 'Das Feedback Formular ist nicht vollständig, das Feld <b>feedback_url</b> fehlt!',
  '<p>The feedback was refused!</p>'
      => '<p>Das Feedback wurde zurückgewiesen.</p>',
  '<p>The feedback was published.</p>'
      => '<p>Das Feedback wurde veröffentlicht.</p>',
  '<p>The field <b>{{ field }}</b> must be filled out.</p>'
      => '<p>Das Feld <b>{{ field }}</b> ist ein <i>Pflichtfeld</i> und muss ausgefüllt werden.</p>',
  'The field type <b>{{ type }}</b> is not implemented!'
      => 'Der Feldtyp <b>{{ type }}</b> ist nicht implementiert!',
  'The field with the <b>ID {{ id }}</b> is no KIT datafield!'
      => '<p>Der <b>ID {{ id }}</b> ist kein KeepInTouch Datenfeld zugeordnet!',
  '<p>The file <b>{{ file }}</b> does not contain valid form datas.</p>'
      => '<p>Die Datei <b>{{ file }}</b> enthält keine verwertbaren Formulardaten.</p>',
  '<p>The file <b>{{ file }}</b> does not contain valid version informations!</p>'
      => '<p>Die Datei <b>{{ file }}</b> enthält keine gültige Versionsinformationen!</p>',
  '<p>The file {{ file }} is member of a blacklist or use a disallowed file extension.</p>'
      => '<p>Die Datei <b>{{ file }}</b> befindet sich auf einer Sperrliste oder verwendet eine verbotene Dateiendung.</p>',
  '<p>The file size exceeds the limit of {{ size }} MB.</p>'
      => '<p>Die Dateigröße übersteigt das zulässige Limit von <b>{{ size }} MB</b>.</p>',
  '<p>The file size exceeds the php.ini directive "upload_max_size" <b>{{ size }}</b>.</p>'
      => '<p>Die hochgeladene Datei überschreitet die in der Anweisung upload_max_filesize in php.ini festgelegte Größe von {{ size }}.</p>',
  '<p>The file <b>{{ file }}</b> was deleted.<p>'
      => '<p>Die Datei <b>{{ file }}</b> wurde gelöscht.</p>',
  '<p>The file <b>{{ file }}</b> was successfully submitted.</p>'
      => '<p>Die Datei <b>{{ file }}</b> wurde übertragen.</p>',
  '<p>The file <b>{{ file }}</b> was uploaded partial.</p>'
      => '<p>Die Datei <b>{{ file }}</b> wurde nur teilweise hochgeladen.</p>',
  '<p>The following feedback data records are not published.</p><p>If you are shure that ONLY SPAM submissions are unpublished, you may delete the form data and also the assosiated KIT ID\'s.</p>'
    => '<p>Die folgenden Feedback Formulardaten wurden noch nicht veröffentlicht.</p><p>Wenn Sie sicher sind, dass es sich bei diesen Datensätzen um SPAM handelt, können Sie diese löschen und darüber hinaus auch die zugeordneten KIT ID\'s.</p>',
  '<p>The form name <b>{{ name }}</b> is already in use, the import of <b>{{ file }}</b> was aborted.</p>'
      => '<p>Der Formular Bezeichner <b>{{ name }}</b> wird bereits verwendet, der Import der Datei <b>{{ file }}</b> wurde abgebrochen.</p>',
  'The form {{ name }} was successfull imported.\n'
      => 'Das Formular {{ name }} wurde erfolgreich importiert.\n',
  'The form name is empty, please check the parameters for the droplet!'
      => 'Es wurde kein Formular Bezeichner übergeben!',
  '<p>The form name can not changed to <b>{{ name }}</b>, this name is already in use by the form with the <b>ID {{ id }}</b>.</p>'
      => '<p>Der Formular Bezeicher kann nicht in in <b>{{ name }}</b> geändert werden, dieser wird bereits von dem Formular mit der <b>ID {{ id }}</b> verwendet.</p>',
  '<p>The <b>form name</b> must contain 3 charactes at minimum!</p>'
      => '<p>Der <b>Formular Bezeichner</b> darf nicht leer sein und muss mindestens 3 Zeichen enthalten!</p>',
  '<p>The form was successfully exported as <b><a href="{{ url }}">{{ name }}</a></b>.</p>'
      => '<p>Das Formular wurde als <b><a href="{{ url }}">{{ name }}</a></b> erfolgreich exportiert (<i>Rechtsklick: <b>"Speichern unter ..."</b></i>).',
  '<p>The form with the <b>ID {{ id }}</b> was successfully created.</p>'
      => '<p>Das Formular mit der <b>ID {{ id }}</b> wurde angelegt.</p>',
  '<p>The form with the <b>ID {{ id }}</b> was successfully deleted.</p>'
      => '<p>Das Formular mit der <b>ID {{ id }}</b> wurde gelöscht!</p>',
  '<p>The form with the <b>ID {{ id }}</b> was updated.</p>'
      => '<p>Das Formular mit der <b>ID {{ id }}</b> wurde aktualisiert.</p>',
  '<p>The general datafield <b>{{ field }}</b> was added to the form.</p>'
      => '<p>Das allgemeine Datenfeld "<b>{{ field }}</b>" wurde dem Formular hinzugefügt.</p>',
  'The ID {{ id }} is invalid!'
      => 'Die ID {{ id }} ist ungültig!',
  'The item has successfully moved'
      => 'Das Element wurde verschoben.',
  '<p>The KIT datafield <b>{{ field }}</b> was added to the form.</p>'
      => '<p>Das KIT Datenfeld <b>{{ field }}</b> wurde dem Formular hinzugefügt.</p>',
  '<p>The link <b>{{ guid }}</b> was already used and is no longer valid! Please contact the support.</p>'
      => '<p>Der Link <b>{{ guid }}</b> wurde bereits verwendet und ist nicht länger gültig! Bitte nehmen Sie Kontakt mit dem Support auf.</p>',
  '<p>The link to access this form is no longer valid and the temporary saved form data are now deleted.</p><p>Please submit the form or use again the option for a delayed transmission to create a new access link.</p>'
      => '<p><b>Dieses zwischengespeicherte Formular wurde noch nicht übermittelt!</b></p><p>Der Link zum Bearbeiten dieses Formulars ist nicht mehr gültig. Die temporär gespeicherten Formulardaten wurden gelöscht.</p><p>Übermitteln Sie das Formular nach der Bearbeitung oder nutzen Sie die Möglichkeit einer verzögerten Übermittlung damit ein neuer Link für den Zugriff auf dieses Formular erzeugt wird.</p>',
  '<p>The name <b>{{ name }}</b> is already in use by the form with the <b>ID {{ id }}</b>, please use another name!</p>'
      => '<p>Der Formular Bezeichner <b>{{ name }}</b> wird bereits von dem Formular mit der <b>ID {{ id }}</b> verwendet, bitte suchen Sie einen anderen Bezeichner.</p>',
  '<p>The newsletter abonnement for the email address <b>{{ email }}</b> was updated.</p>'
      => '<p>Das Newsletter Abonnement für die E-Mail Adresse <b>{{ email }}</b> wurde aktualisiert.</p>',
  '<p>The password is empty!</p>'
      => '<p>Das Passwort darf nicht leer sein!</p>',
  '<p>The password needs at least a length of {{ lenght }} characters!</p>'
      => '<p>Das Passwort muss mindestens {{ lenght }} Zeichen lang sein!</p>',
  'The preset directory <b>{{ directory }}</b> does not exists, can\'t load any template!'
      => 'Das Presetverzeichnis <b>{{ directory }}</b> existiert nicht, die erforderlichen Templates können nicht geladen werden!',
  'The uploaded file exceeds the directive MAX_FILE_SIZE'
      => 'Die hochgeladene Datei überschreitet die in dem HTML Formular mittels der Anweisung MAX_FILE_SIZE angegebene maximale Dateigröße.',
  '<p>The user account was updated.</p>'
      => '<p>Das Benutzerkonto wurde aktualisiert.</p>',
  '<p>There are no unpublished feedback form data to process!</p>'
      => '<p>Es wurden keine unveröffentlichten Feedback Formulardaten ermittelt.</p>',
  '<p>There exists no submitted data for the form with the ID {{ id }}.</p>'
    => '<p>Für das Formular mit der ID {{ id }} wurden noch keine Daten übermittelt.</p>',
  '<p>There was no file for import!</p>'
      => '<p>Es wurde keine Datei importiert!</p>',
  'Text'
      => 'Text',
  'This command does not exists or was already executed!'
      => 'Der Befehl existiert nicht oder wurde bereits ausgeführt.',
  '<p>To use the upload method <b>uploadify</b> kitUploader must be installed!</p>'
      => '<p>Damit Sie die Upload Methode <b>uploadify</b> nutzen können muss kitUploader installiert sein!</p>',

  '<p><b>{{ value }}</b> is not a valid date, please check your input!</p>'
      => '<p><b>{{ value }}</b> ist kein gültiges Datum, bitte prüfen Sie Ihre Eingabe!</p>',

  'Upload method'
      => 'Übertragungsmethode',
  'Upload mode'
      => 'Übertragungsmodus',
  '<p>Unknown upload method: <b>{{ method }}</b>, allowed methods are <i>standard</i> or <i>uploadify</i>.</p>'
      => 'Unbekannte Upload Methode: <b>{{ method }}</b>, erlaubte Methoden sind <i>standard</i> oder <i>uploadify</i>.</p>',
  'Unpublished feedback data records'
    => 'Nicht veröffentlichte Feedback Formulardaten',
  '<p>Unspecified error, no description available.</p>'
      => '<p>Während der Datenübertragung ist ein nicht näher beschriebener Fehler aufgetreteten.</p>',
  'Unsubscribe Feedback'
      => 'Benachrichtigungen ausschalten',

  'Your account data'
      => 'Ihre Zugangsdaten',
  '<p>You are not authenticated, please login first!</p>'
      => '<p>Sie sind nicht angemeldet, bitte melden Sie sich an!</p>',
  '<p>You have submitted the form to early, please wait for the specified seconds (SPAM protection).</p>'
      => '<p>Sie haben das Formular zu früh übermittelt, bitte warten Sie die angegebene Zeit in Sekunden ab bevor Sie das Formular abschicken (SPAM Schutz).</p>',

  '<p>Welcome!<br />we have send you the username and password by email.</p>'
      => '<p>Herzlich willkommen!<br />Ihre Benutzerdaten haben Sie per E-Mail erhalten.</p>',
  'With this dialog you can create and edit general forms and special forms for KeepInTouch (KIT).'
      => '<p>Mit diesem Dialog erstellen und bearbeiten Sie Formulare für KeepInTouch (KIT)</p>',
  'Words'
    => 'Worte',

  '<p>Your password is not secure, please choose a new password!</p>'
     => '<p>Ihr Passwort ist nicht sicher, bitte legen Sie ein neues Passwort fest!</p>',

);
