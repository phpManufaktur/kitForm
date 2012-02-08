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

// include class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {
	if (defined('LEPTON_VERSION'))
		include (WB_PATH . '/framework/class.secure.php');
} else {
	$oneback = "../";
	$root = $oneback;
	$level = 1;
	while (($level < 10) && (!file_exists($root . '/framework/class.secure.php'))) {
		$root .= $oneback;
		$level += 1;
	}
	if (file_exists($root . '/framework/class.secure.php')) {
		include ($root . '/framework/class.secure.php');
	} else {
		trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
	}
}
// end include class.secure.php

require_once (WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/initialize.php');
require_once (WB_PATH . '/framework/functions.php');

class formBackend {
	
	const request_action = 'act';
	const request_add_free_field = 'aff';
	const request_add_kit_field = 'akf';
	const request_fields = 'fld';
	const request_free_field_title = 'fft';
	const request_import_file = 'impf';
	const request_import_name = 'impn';
	const request_protocol_id = 'pid';
	const request_export = 'exp';
	const request_move = 'mov';
	const request_position = 'pos';
	
	const action_about = 'abt';
	const action_default = 'def';
	const action_edit = 'edt';
	const action_edit_check = 'edtc';
	const action_import = 'imp';
	const action_list = 'lst';
	const action_protocol = 'pro';
	const action_protocol_id = 'pid';
	const action_up = 'up';
	const action_down = 'down';
	const action_move = 'mov';
	
	private $page_link = '';
	private $img_url = '';
	private $template_path = '';
	private $error = '';
	private $message = '';
	
	protected $lang = null;
	protected $file_allowed_filetypes = 'jpg,gif,png,pdf,zip';
	
	public function __construct() {
		global $I18n;
		$this->page_link = ADMIN_URL . '/admintools/tool.php?tool=kit_form';
		$this->template_path = WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/htt/';
		$this->img_url = WB_URL . '/modules/' . basename(dirname(__FILE__)) . '/images/';
		date_default_timezone_set(cfg_time_zone);
		$this->lang = $I18n;
	} // __construct()
	
	/**
	 * Set $this->error to $error
	 * 
	 * @param $error STR       	
	 */
	protected function setError($error) {
		/*
		 * $debug = debug_backtrace(); $caller = next($debug); $this->error =
		 * sprintf('[%s::%s - %s] %s', basename($caller['file']),
		 * $caller['function'], $caller['line'], $error);
		 */
		$this->error = $error;
	} // setError()
	
	/**
	 * Get Error from $this->error;
	 * 
	 * @return STR $this->error
	 */
	public function getError() {
		return $this->error;
	} // getError()
	
	/**
	 * Check if $this->error is empty
	 * 
	 * @return BOOL
	 */
	public function isError() {
		return (bool) !empty($this->error);
	} // isError
	
	/**
	 * Reset Error to empty String
	 */
	protected function clearError() {
		$this->error = '';
	}
	
	/**
	 * Set $this->message to $message
	 * 
	 * @param $message STR       	
	 */
	protected function setMessage($message) {
		$this->message = $message;
	} // setMessage()
	
	/**
	 * Get Message from $this->message;
	 * 
	 * @return STR $this->message
	 */
	public function getMessage() {
		return $this->message;
	} // getMessage()
	
	/**
	 * Check if $this->message is empty
	 * 
	 * @return BOOL
	 */
	public function isMessage() {
		return (bool) !empty($this->message);
	} // isMessage
	
	/**
	 * Return Version of Module
	 * 
	 * @return FLOAT
	 */
	public function getVersion() {
		// read info.php into array
		$info_text = file(WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/info.php');
		if ($info_text == false) {
			return -1;
		}
		// walk through array
		foreach ($info_text as $item) {
			if (strpos($item, '$module_version') !== false) {
				// split string $module_version
				$value = explode('=', $item);
				// return floatval
				return floatval(preg_replace('([\'";,\(\)[:space:][:alpha:]])', '', $value[1]));
			}
		}
		return -1;
	} // getVersion()
	
	/**
	 * Load the desired template, execute the template engine and returns the
	 * resulting template
	 * 
	 * @param $template string
	 *       	 - the file name of the template
	 * @param $template_data array
	 *       	 - the data for the template
	 */
	protected function getTemplate($template, $template_data) {
		global $parser;
		$result = '';
		try {
			$result = $parser->get($this->template_path . $template, $template_data);
		} catch (Exception $e) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('<p>Error executing template <b>{{ template }}</b>:</p><p>{{ error }}</p>', array('template' => $template, 'error' => $e->getMessage()))));
			return false;
		}
		return $result;
	} // getTemplate()
	
	/**
	 * Converts a byte string from PHP.INI (i.e.
	 * 15M) into a integer byte value
	 * 
	 * @param $value string       	
	 * @return integer - byte value
	 */
	protected function convertBytes($value) {
		if (is_numeric($value)) {
			return $value;
		} else {
			$value_length = strlen($value);
			$qty = substr($value, 0, $value_length - 1);
			$unit = strtolower(substr($value, $value_length - 1));
			switch ($unit) :
				case 'k' :
					$qty *= 1024;
					break;
				case 'm' :
					$qty *= 1048576;
					break;
				case 'g' :
					$qty *= 1073741824;
					break;
			endswitch
			;
			return $qty;
		}
	} // convertBytes
	
	/**
	 * Verhindert XSS Cross Site Scripting
	 * 
	 * @param $_REQUEST REFERENCE
	 *       	 Array
	 * @return $request
	 */
	protected function xssPrevent(&$request) {
		if (is_string($request)) {
			$request = html_entity_decode($request);
			$request = strip_tags($request);
			$request = trim($request);
			$request = stripslashes($request);
		}
		return $request;
	} // xssPrevent()
	
	/**
	 * The action handler of the class formBackend
	 * 
	 * @return string dialog or error message
	 */
	public function action() {
		$html_allowed = array();
		foreach ($_REQUEST as $key => $value) {
			if (!in_array($key, $html_allowed)) {
				// special
				if (strpos($key, 'html_free_') === 0)
					continue;
				$_REQUEST[$key] = $this->xssPrevent($value);
			}
		}
		isset($_REQUEST[self::request_action]) ? $action = $_REQUEST[self::request_action] : $action = self::action_default;
		switch ($action) :
			case self::action_about :
				$result = $this->show(self::action_about, $this->dlgAbout());
				break;
			case self::action_edit :
				$result = $this->show(self::action_edit, $this->dlgFormEdit());
				break;
			case self::action_edit_check :
				$result = $this->show(self::action_edit, $this->checkFormEdit());
				break;
			case self::action_protocol :
				$result = $this->show(self::action_protocol, $this->dlgProtocolList());
				break;
			case self::action_protocol_id :
				$result = $this->show(self::action_protocol, $this->dlgProtocolItem());
				break;
			case self::action_import :
				$result = $this->show(self::action_edit, $this->importForm());
				break;
			case self::action_move :
				$result = $this->show(self::action_edit, $this->checkMove());
				break;
			case self::action_list :
			default :
				$result = $this->show(self::action_list, $this->dlgFormList());
				break;
		endswitch
		;
		
		echo $result;
	} // action
	
	/**
	 * Ausgabe des formatierten Ergebnis mit Navigationsleiste
	 * 
	 * @param $action -
	 *       	 aktives Navigationselement
	 * @param $content -
	 *       	 Inhalt
	 * @return ECHO RESULT
	 */
	protected function show($action, $content) {
		$tab_navigation_array = array(self::action_list => $this->lang->translate('List'), self::action_edit => $this->lang->translate('Edit'), self::action_protocol => $this->lang->translate('Protocol'), self::action_about => $this->lang->translate('About'));
		
		$navigation = array();
		foreach ($tab_navigation_array as $key => $value) {
			$navigation[] = array('active' => ($key == $action) ? 1 : 0, 'url' => sprintf('%s&%s=%s', $this->page_link, self::request_action, $key), 'text' => $value);
		}
		$data = array('WB_URL' => WB_URL, 'navigation' => $navigation, 'error' => ($this->isError()) ? 1 : 0, 'content' => ($this->isError()) ? $this->getError() : $content);
		return $this->getTemplate('backend.body.htt', $data);
	} // show()
	
	/**
	 * Check the created or edited form and createor update the database records
	 * and return the dlgFormEdit() dialog.
	 * 
	 * @return string dlgFormEdit() or false on error
	 */
	protected function checkFormEdit() {
		global $dbKITform;
		global $dbKITformFields;
		global $kitContactInterface;
		global $dbKITformTableSort;
		global $kitLibrary;
		
		$checked = true;
		$message = '';
		
		$form_id = isset($_REQUEST[dbKITform::field_id]) ? $_REQUEST[dbKITform::field_id] : -1;
		
		$form_data = $dbKITform->getFields();
		unset($form_data[dbKITform::field_timestamp]);
		foreach ($form_data as $field => $value) {
			switch ($field) :
				case dbKITform::field_id :
					$form_data[$field] = $form_id;
					break;
				case dbKITform::field_name :
					$form_data[$field] = isset($_REQUEST[$field]) ? $_REQUEST[$field] : '';
					if (empty($form_data[$field])) {
						$message .= $this->lang->translate('<p>The <b>form name</b> must contain 3 charactes at minimum!</p>');
						$checked = false;
						break;
					}
					$name = str_replace(' ', '_', strtolower(media_filename(trim($form_data[$field]))));
					$SQL = sprintf("SELECT %s FROM %s WHERE %s='%s' AND %s!='%s'", dbKITform::field_id, $dbKITform->getTableName(), dbKITform::field_name, $name, dbKITform::field_status, dbKITform::status_deleted);
					$result = array();
					if (!$dbKITform->sqlExec($SQL, $result)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
						return false;
					}
					if (count($result) > 0) {
						if (($form_id > 0) && ($result[0][dbKITform::field_id] !== $form_id)) {
							// Formular kann nicht umbenannt werden, der
							// Bezeichner wird bereits verwendet
							$message .= $this->lang->translate('<p>The form name can not changed to <b>{{ name }}</b>, this name is already in use by the form with the <b>ID {{ id }}</b>.</p>', array('name' => $name, 'id' => sprintf('%03d', $result[0][dbKITform::field_id])));
							unset($_REQUEST[$field]);
							$checked = false;
							break;
						} elseif ($form_id < 1) {
							// Der Bezeichner wird bereits verwendet
							$message .= $this->lang->translate('<p>The name <b>{{ name }}</b> is already in use by the form with the <b>ID {{ id }}</b>, please use another name!</p>', array('name' => $name, 'id' => sprintf('%03d', $result[0][dbKITform::field_id])));
							unset($_REQUEST[$field]);
							$checked = false;
							break;
						}
					}
					$form_data[$field] = $name;
					break;
				case dbKITform::field_title :
					$form_data[$field] = isset($_REQUEST[$field]) ? $_REQUEST[$field] : '';
					if (empty($form_data[$field]) || (strlen($form_data[$field]) < 6)) {
						$message .= $this->lang->translate('<p>At minimum the form title must be 5 or more characters long!</p>');
						$checked = false;
					}
					break;
				case dbKITform::field_action :
				case dbKITform::field_description :
				case dbKITform::field_fields :
				case dbKITform::field_must_fields :
					$form_data[$field] = isset($_REQUEST[$field]) ? $_REQUEST[$field] : '';
					break;
				case dbKITform::field_status :
					$form_data[$field] = isset($_REQUEST[$field]) ? $_REQUEST[$field] : dbKITform::status_locked;
					if ($form_data[$field] == dbKITform::status_deleted) {
						// Formular loeschen
						$where = array(dbKITform::field_id => $form_id);
						if (!$dbKITform->sqlDeleteRecord($where)) {
							$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
							return false;
						}
						// Formular Items loeschen
						$where = array(dbKITformFields::field_form_id => $form_id);
						if (!$dbKITformFields->sqlDeleteRecord($where)) {
							$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
							return false;
						}
						// es gibt nichts mehr zu tun, zurueck zur
						// Uebersichtsliste
						$this->lang->translate('<p>The form with the <b>ID {{ id }}</b> was successfully deleted.</p>', array('id' => sprintf('%03d', $form_id)));
						return $this->dlgFormList();
					}
					break;
				case dbKITform::field_provider_id :
					$form_data[$field] = isset($_REQUEST[$field]) ? $_REQUEST[$field] : -1;
					if ($form_data[$field] == -1) {
						// kein Diensleister ausgewaehlt
						$message .= $this->lang->translate('<p>Please select a service provider for this form!</p>');
						$checked = false;
					}
					break;
				case dbKITform::field_email_cc :
					$cc = isset($_REQUEST[$field]) ? $_REQUEST[$field] : '';
					if (!empty($cc)) {
						// CC Adressen auslesen
						$cc_arr = explode(',', $cc);
						$new_arr = array();
						foreach ($cc_arr as $email) {
							if (!$kitLibrary->validateEMail(trim($email))) {
								$message .= $this->lang->translate('<p>The email address <b>{{ email }}</b> is not valid, please check your input.</p>', array('email' => $email));
								$checked = false;
							}
							$new_arr[] = trim($email);
						}
						$cc = implode(',', $new_arr);
					}
					$form_data[$field] = $cc;
					break;
				case dbKITform::field_email_html :
					$form_data[$field] = isset($_REQUEST[$field]) ? $_REQUEST[$field] : dbKITform::html_off;
					break;
				case dbKITform::field_captcha :
					$form_data[$field] = isset($_REQUEST[$field]) ? $_REQUEST[$field] : dbKITform::captcha_on;
					break;
				default :
					// uebrige Felder ueberspringen
					break;
			endswitch
			;
		}
		
		// Action Links pruefen
		$links = array();
		foreach ($dbKITform->action_array as $key => $text) {
			if (isset($_REQUEST[$key]))
				$links[$key] = $_REQUEST[$key];
		}
		// ... und uebernehmen
		$form_data[dbKITform::field_links] = http_build_query($links);
		
		// pruefen ob ein Feld entfernt werden soll oder ob Felder als
		// Pflichtfelder gesetzt werden sollen
		$fields = explode(',', $form_data[dbKITform::field_fields]);
		$must_fields = explode(',', $form_data[dbKITform::field_must_fields]);
		foreach ($fields as $key => $value) {
			if ($value < 100) {
				// KIT Felder
				$field_name = array_search($value, $kitContactInterface->index_array);
				if (!isset($_REQUEST[$field_name])) {
					$message .= $this->lang->translate('<p>The datafield <b>{{ field }}</b> was removed.</p>', array('field' => $kitContactInterface->field_array[$field_name]));
					unset($fields[$key]);
				}
				if (isset($_REQUEST['must_' . $field_name]) && !in_array($value, $must_fields)) {
					$must_fields[] = $value;
				}
			} else {
				// allgemeine Felder
				$further_check = true;
				$where = array(dbKITformFields::field_id => $value);
				$data = array();
				if (!$dbKITformFields->sqlSelectRecord($where, $data)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
					return false;
				}
				if (count($data) < 1) {
					continue;
				/**
				 *
				 * @todo continue instead prompting error is only a workaround!
				 */
					// $this->setError(sprintf('[%s - %s] %s', __METHOD__,
				// __LINE__, kit_error_invalid_id));
					// return false;
				}
				$data = $data[0];
				$field_name = $data[dbKITformFields::field_name];
				$field_id = $data[dbKITformFields::field_id];
				if (!isset($_REQUEST[$field_name])) {
					// Feld entfernen
					$message .= $this->lang->translate('<p>The datafield <b>{{ field }}</b> was removed.</p>', array('field' => $field_name));
					unset($fields[$key]);
					$further_check = false;
					// Tabelle aktualisieren
					$where = array(dbKITformFields::field_id => $field_id);
					if (!$dbKITformFields->sqlDeleteRecord($where)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
						return false;
					}
				}
				if (isset($_REQUEST["must_$field_name"]) && !in_array($value, $must_fields)) {
					$must_fields[] = $value;
				}
				if ($further_check) {
					// erweiterte Pruefung der Felder in Abhaenigkeit des Feld
					// Typen
					switch ($data[dbKITformFields::field_type]) :
						case dbKITformFields::type_text :
							// Einfache Text Eingabefelder pruefen
							$field_data = array(dbKITformFields::field_name => (isset($_REQUEST['name_' . $field_name])) ? $_REQUEST['name_' . $field_name] : 'free_' . $field_id, dbKITformFields::field_title => (isset($_REQUEST['title_' . $field_name])) ? $_REQUEST['title_' . $field_name] : 'title_' . $field_id, dbKITformFields::field_value => (isset($_REQUEST['default_' . $field_name])) ? $_REQUEST['default_' . $field_name] : '', dbKITformFields::field_data_type => (isset($_REQUEST['data_type_' . $field_name])) ? $_REQUEST['data_type_' . $field_name] : dbKITformFields::data_type_text, dbKITformFields::field_hint => (isset($_REQUEST['hint_' . $field_name])) ? $_REQUEST['hint_' . $field_name] : '');
							$where = array(dbKITformFields::field_id => $field_id);
							if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
								$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
								return false;
							}
							break;
						case dbKITformFields::type_text_area :
							// textarea pruefen
							$field_data = array(dbKITformFields::field_name => (isset($_REQUEST['name_' . $field_name])) ? $_REQUEST['name_' . $field_name] : 'free_' . $field_id, dbKITformFields::field_title => (isset($_REQUEST['title_' . $field_name])) ? $_REQUEST['title_' . $field_name] : 'title_' . $field_id, dbKITformFields::field_value => (isset($_REQUEST['default_' . $field_name])) ? $_REQUEST['default_' . $field_name] : '', dbKITformFields::field_data_type => dbKITformFields::data_type_text, dbKITformFields::field_hint => (isset($_REQUEST['hint_' . $field_name])) ? $_REQUEST['hint_' . $field_name] : '');
							$where = array(dbKITformFields::field_id => $field_id);
							if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
								$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
								return false;
							}
							break;
						case dbKITformFields::type_file :
							// FILE Type
							$settings = array();
							parse_str($data[dbKITformFields::field_type_add], $settings);
							$upload_max_filesize = $this->convertBytes(ini_get('upload_max_filesize'));
							$post_max_size = $this->convertBytes(ini_get('post_max_size'));
							$max_filesize = $upload_max_filesize;
							if ($upload_max_filesize > $post_max_size)
								$max_filesize = $post_max_size;
								
								// check if the field NAME has changed ...
							if ($settings['upload_method']['name'] != "upload_method_$field_name") {
								$settings['upload_method']['name'] = "upload_method_$field_name";
								$settings['file_types']['name'] = "file_types_$field_name";
								$settings['max_file_size']['name'] = "max_file_size_$field_name";
							}
							// update settings ...
							if (isset($_REQUEST["upload_method_$field_name"])) {
								// check the upload method
								$dummy = strtolower($_REQUEST["upload_method_$field_name"]);
								switch ($dummy) :
									case 'standard' :
										$settings['upload_method']['value'] = 'standard';
										break;
									case 'uploadify' :
										if (!file_exists(WB_PATH . '/modules/kit_uploader/info.php')) {
											// missing kitUploader
											$message .= $this->lang->translate('<p>To use the upload method <b>uploadify</b> kitUploader must be installed!</p>');
											$settings['upload_method']['value'] = 'standard';
											break;
										}
										$settings['upload_method']['value'] = 'uploadify';
										break;
									default :
										$checked = false;
										$message .= $this->lang->translate('<p>Unknown upload method: <b>{{ method }}</b>, allowed methods are <i>standard</i> or <i>uploadify</i>.</p>', array('method' => $dummy));
										$settings['upload_method']['value'] = 'standard';
										break;
								endswitch
								;
							} else {
								$settings['upload_method']['value'] = 'standard';
							}
							if (isset($_REQUEST["file_types_$field_name"])) {
								// set allowed file extensions, grant lowercase
								// and remove spaces
								$dummy = strtolower($_REQUEST["file_types_$field_name"]);
								$dummy = str_replace(' ', '', $dummy);
								$settings['file_types']['value'] = $dummy;
							} else {
								$settings['file_types']['value'] = $this->file_allowed_filetypes;
							}
							if (isset($_REQUEST["max_file_size_$field_name"])) {
								$max = (int) $_REQUEST["max_file_size_$field_name"];
								if (($max * 1024 * 1024) > $max_filesize) {
									$max = ($max_filesize / 1024 / 1024);
									$message .= $this->lang->translate('<p>System does not allow uploads greater than <b>{{ max_filesize }} MB</b>. Please contact your webmaster to increase this value.</p>', array('max_filesize' => $max_filesize / 1024 / 1024));
								}
								$settings['max_file_size']['value'] = $max;
							} else {
								$settings['max_file_size']['value'] = $max_filesize / 1024 / 1024;
							}
							$field_data = array(dbKITformFields::field_name => (isset($_REQUEST['name_' . $field_name])) ? $_REQUEST['name_' . $field_name] : 'free_' . $field_id, dbKITformFields::field_title => (isset($_REQUEST['title_' . $field_name])) ? $_REQUEST['title_' . $field_name] : 'title_' . $field_id, dbKITformFields::field_value => '', dbKITformFields::field_data_type => dbKITformFields::data_type_undefined, dbKITformFields::field_hint => (isset($_REQUEST['hint_' . $field_name])) ? $_REQUEST['hint_' . $field_name] : '', dbKITformFields::field_type_add => http_build_query($settings));
							$where = array(dbKITformFields::field_id => $field_id);
							if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
								$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
								return false;
							}
							break;
						case dbKITformFields::type_checkbox :
							// CHECKBOX pruefen
							$cboxes = array();
							parse_str($data[dbKITformFields::field_type_add], $cboxes);
							$checkboxes = array();
							foreach ($cboxes as $checkbox) {
								$cb_name = $checkbox['name'];
								if (!isset($_REQUEST['cb_active_' . $cb_name])) {
									continue;
								}
								if (!empty($_REQUEST['cb_value_' . $cb_name]))
									$checkbox['value'] = $_REQUEST['cb_value_' . $cb_name];
								if (!empty($_REQUEST['cb_text_' . $cb_name]))
									$checkbox['text'] = $_REQUEST['cb_text_' . $cb_name];
								$checkbox['checked'] = (isset($_REQUEST['cb_checked_' . $cb_name])) ? 1 : 0;
								$checkboxes[] = $checkbox;
							}
							
							// neue Checkboxen dazunehmen
							if (isset($_REQUEST['cb_active_' . $field_id])) {
								// es soll eine neue Checkbox uebernommen werden
								if (isset($_REQUEST['cb_value_' . $field_id]) && !empty($_REQUEST['cb_value_' . $field_id]) && isset($_REQUEST['cb_text_' . $field_id]) && !empty($_REQUEST['cb_text_' . $field_id])) {
									// ok - checkbox uebernehmen
									$value = str_replace(' ', '_', strtolower(media_filename($_REQUEST['cb_value_' . $field_id])));
									$checkboxes[] = array('name' => $field_id . '_' . $value, 'value' => $value, 'text' => $_REQUEST['cb_text_' . $field_id], 'checked' => isset($_REQUEST['cb_checked_' . $field_id]) ? 1 : 0);
								} else {
									// Definition der Checkbox ist nicht
									// vollstaendig
									$message .= $this->lang->translate('<p>The definition of the new checkbox is not complete. Please specify a <b>value</b> and a <b>text</b> for it!</p>');
								}
							}
							// allgemeine Daten der Checkbox pruefen
							$field_data = array(dbKITformFields::field_name => (isset($_REQUEST['name_' . $field_name])) ? $_REQUEST['name_' . $field_name] : 'free_' . $field_id, dbKITformFields::field_title => (isset($_REQUEST['title_' . $field_name])) ? $_REQUEST['title_' . $field_name] : 'title_' . $field_id, dbKITformFields::field_value => (isset($_REQUEST['default_' . $field_name])) ? $_REQUEST['default_' . $field_name] : '', dbKITformFields::field_data_type => dbKITformFields::data_type_undefined, dbKITformFields::field_hint => (isset($_REQUEST['hint_' . $field_name])) ? $_REQUEST['hint_' . $field_name] : '', dbKITformFields::field_type_add => http_build_query($checkboxes));
							$where = array(dbKITformFields::field_id => $field_id);
							if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
								$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
								return false;
							}
							break;
						case dbKITformFields::type_radio :
							// RADIOBUTTON pruefen
							$rbuttons = array();
							parse_str($data[dbKITformFields::field_type_add], $rbuttons);
							$radios = array();
							foreach ($rbuttons as $radio) {
								$rb_name = $radio['name'];
								if (!isset($_REQUEST['rb_active_' . $rb_name]))
									continue;
								if (!empty($_REQUEST['rb_value_' . $rb_name]))
									$radio['value'] = $_REQUEST['rb_value_' . $rb_name];
								if (!empty($_REQUEST['rb_text_' . $rb_name]))
									$radio['text'] = $_REQUEST['rb_text_' . $rb_name];
								$radio['checked'] = (isset($_REQUEST['rb_checked_' . $field_name]) && ($_REQUEST['rb_checked_' . $field_name] == $radio['value'])) ? 1 : 0;
								$radios[] = $radio;
							}
							// neuen Radiobutton dazunehmen
							if (isset($_REQUEST['rb_active_' . $field_id])) {
								// es soll eine neuer Radio uebernommen werden
								if (isset($_REQUEST['rb_value_' . $field_id]) && !empty($_REQUEST['rb_value_' . $field_id]) && isset($_REQUEST['rb_text_' . $field_id]) && !empty($_REQUEST['rb_text_' . $field_id])) {
									// ok - radiobutton uebernehmen
									$value = str_replace(' ', '_', strtolower(media_filename($_REQUEST['rb_value_' . $field_id])));
									$radios[] = array('name' => $field_id . '_' . $value, 'value' => $value, 'text' => $_REQUEST['rb_text_' . $field_id], 'checked' => 0);
								} else {
									// Definition der Checkbox ist nicht
									// vollstaendig
									$message .= $this->lang->translate('<p>The definition of the new radiobutton is not complete. Please specify a <b>value</b> and a <b>text</b> for it!</p>');
								}
							}
							// allgemeine Daten der Radiobuttons pruefen
							$field_data = array(dbKITformFields::field_name => (isset($_REQUEST['name_' . $field_name])) ? $_REQUEST['name_' . $field_name] : 'free_' . $field_id, dbKITformFields::field_title => (isset($_REQUEST['title_' . $field_name])) ? $_REQUEST['title_' . $field_name] : 'title_' . $field_id, dbKITformFields::field_value => (isset($_REQUEST['default_' . $field_name])) ? $_REQUEST['default_' . $field_name] : '', dbKITformFields::field_data_type => dbKITformFields::data_type_undefined, dbKITformFields::field_hint => (isset($_REQUEST['hint_' . $field_name])) ? $_REQUEST['hint_' . $field_name] : '', dbKITformFields::field_type_add => http_build_query($radios));
							$where = array(dbKITformFields::field_id => $field_id);
							if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
								$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
								return false;
							}
							break;
						case dbKITformFields::type_select :
							// SELECT Auswahlliste pruefen
							$sOptions = array();
							parse_str($data[dbKITformFields::field_type_add], $sOptions);
							$options = array();
							foreach ($sOptions as $option) {
								$opt_name = $option['name'];
								if (!isset($_REQUEST['opt_active_' . $opt_name]))
									continue;
								if (!empty($_REQUEST['opt_value_' . $opt_name]))
									$option['value'] = $_REQUEST['opt_value_' . $opt_name];
								if (!empty($_REQUEST['opt_text_' . $opt_name]))
									$option['text'] = $_REQUEST['opt_text_' . $opt_name];
								$option['checked'] = (isset($_REQUEST['opt_checked_' . $field_name]) && ($_REQUEST['opt_checked_' . $field_name] == $option['value'])) ? 1 : 0;
								$options[] = $option;
							}
							// neues Auswahlfeld dazunehmen
							if (isset($_REQUEST['opt_active_' . $field_id])) {
								// es soll eine neuer OPTION Eintrag uebernommen
								// werden
								if (isset($_REQUEST['opt_value_' . $field_id]) && !empty($_REQUEST['opt_value_' . $field_id]) && isset($_REQUEST['opt_text_' . $field_id]) && !empty($_REQUEST['opt_text_' . $field_id])) {
									// ok - OPTION uebernehmen
									$value = str_replace(' ', '_', strtolower(media_filename($_REQUEST['opt_value_' . $field_id])));
									$options[] = array('name' => $field_id . '_' . $value, 'value' => $value, 'text' => $_REQUEST['opt_text_' . $field_id], 'checked' => 0);
								} else {
									// Definition der Auswahlliste ist nicht
									// vollstaendig
									$message .= $this->lang->translate('<p>The definition of the new selection list is not complete. Please specify a <b>value</b> and a <b>text</b> for it!</p>');
								}
							}
							// allgemeine Daten der Auswahlliste pruefen
							$field_data = array(dbKITformFields::field_name => (isset($_REQUEST['name_' . $field_name])) ? $_REQUEST['name_' . $field_name] : 'free_' . $field_id, dbKITformFields::field_title => (isset($_REQUEST['title_' . $field_name])) ? $_REQUEST['title_' . $field_name] : 'title_' . $field_id, dbKITformFields::field_value => (isset($_REQUEST['size_' . $field_name])) ? $_REQUEST['size_' . $field_name] : '1', dbKITformFields::field_data_type => dbKITformFields::data_type_undefined, dbKITformFields::field_hint => (isset($_REQUEST['hint_' . $field_name])) ? $_REQUEST['hint_' . $field_name] : '', dbKITformFields::field_type_add => http_build_query($options));
							$where = array(dbKITformFields::field_id => $field_id);
							if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
								$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
								return false;
							}
							break;
						case dbKITformFields::type_html :
							// Daten fuer das HTML Feld pruefen
							$field_data = array(dbKITformFields::field_name => (isset($_REQUEST['name_' . $field_name])) ? $_REQUEST['name_' . $field_name] : 'free_' . $field_id, dbKITformFields::field_title => (isset($_REQUEST['title_' . $field_name])) ? $_REQUEST['title_' . $field_name] : 'title_' . $field_id, dbKITformFields::field_value => (isset($_REQUEST['html_' . $field_name])) ? $_REQUEST['html_' . $field_name] : '', dbKITformFields::field_hint => (isset($_REQUEST['hint_' . $field_name])) ? $_REQUEST['hint_' . $field_name] : '', dbKITformFields::field_data_type => dbKITformFields::data_type_text);
							$where = array(dbKITformFields::field_id => $field_id);
							if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
								$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
								return false;
							}
							break;
						case dbKITformFields::type_hidden :
							// Daten fuer versteckte Felder pruefen
							$field_data = array(dbKITformFields::field_name => (isset($_REQUEST['name_' . $field_name])) ? $_REQUEST['name_' . $field_name] : 'free_' . $field_id, dbKITformFields::field_title => (isset($_REQUEST['title_' . $field_name])) ? $_REQUEST['title_' . $field_name] : 'title_' . $field_id, dbKITformFields::field_value => (isset($_REQUEST['value_' . $field_name])) ? $_REQUEST['value_' . $field_name] : '', dbKITformFields::field_data_type => dbKITformFields::data_type_text);
							$where = array(dbKITformFields::field_id => $field_id);
							if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
								$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
								return false;
							}
							break;
						case dbKITformFields::type_delayed :
							// check the data for delayed transmissions
							$type_add = array();
							if (isset($_REQUEST['text_' . $field_name])) {
								$type_add = array('text' => $_REQUEST['text_' . $field_name]);
							}
							$field_data = array(dbKITformFields::field_name => dbKITformFields::kit_delayed_transmission, dbKITformFields::field_title => (isset($_REQUEST['title_' . $field_name])) ? $_REQUEST['title_' . $field_name] : 'title_' . $field_id, dbKITformFields::field_value => 1, dbKITformFields::field_data_type => dbKITformFields::data_type_integer, dbKITformFields::field_hint => (isset($_REQUEST['hint_' . $field_name])) ? $_REQUEST['hint_' . $field_name] : '', dbKITformFields::field_type_add => http_build_query($type_add));
							$where = array(dbKITformFields::field_id => $field_id);
							if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
								$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
								return false;
							}
							break;
						default :
							$message .= $this->lang->translate('<p>The datatype {{ datatype }} is not supported!</p>', array('datatype' => $data[dbKITformFields::field_type]));
					endswitch
					;
				}
			}
		}
		$form_data[dbKITform::field_fields] = implode(',', $fields);
		
		// pruefen ob Pflichtfelder zurueckgestuft werden sollen
		foreach ($must_fields as $key => $value) {
			if ($value < 100) {
				// KIT Felder
				$field_name = array_search($value, $kitContactInterface->index_array);
				if (!isset($_REQUEST['must_' . $field_name])) {
					unset($must_fields[$key]);
				}
			} else {
				// allgemeine Felder
				$where = array(dbKITformFields::field_id => $value);
				$data = array();
				if (!$dbKITformFields->sqlSelectRecord($where, $data)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
					return false;
				}
				if (count($data) < 1) {
					continue;
				/**
				 *
				 * @todo continue is only a workaround, what is the reason for
				 *       invalid ids?
				 */
					// $this->setError(sprintf('[%s - %s] %s', __METHOD__,
				// __LINE__, kit_error_invalid_id));
					// return false;
				}
				$field_name = $data[0][dbKITformFields::field_name];
				if (!isset($_REQUEST["must_$field_name"])) {
					unset($must_fields[$key]);
				}
			}
		}
		$form_data[dbKITform::field_must_fields] = implode(',', $must_fields);
		if ($checked) {
			// Datensatz fuer das Formular uebernehmen oder aktualisieren
			if ($form_id > 0) {
				// Datensatz aktualisieren
				$where = array(dbKITform::field_id => $form_id);
				if (!$dbKITform->sqlUpdateRecord($form_data, $where)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
					return false;
				}
				$message .= $this->lang->translate('<p>The form with the <b>ID {{ id }}</b> was updated.</p>', array('id' => $form_id));
			} else {
				// Datensatz einfuegen
				if (!$dbKITform->sqlInsertRecord($form_data, $form_id)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
					return false;
				}
				$message .= $this->lang->translate('<p>The form with the <b>ID {{ id }}</b> was successfully created.</p>', array('id' => $form_id));
			}
			// $_REQUEST's zuruecksetzen
			foreach ($form_data as $field => $value) {
				if (isset($_REQUEST[$field]))
					unset($_REQUEST[$field]);
			}
			// FORM_ID setzen
			$_REQUEST[dbKITform::field_id] = $form_id;
		}
		
		// KIT Datenfelder hinzufuegen
		$kit_fields = $kitContactInterface->index_array;
		if (isset($_REQUEST[self::request_add_kit_field]) && (array_key_exists($_REQUEST[self::request_add_kit_field], $kit_fields))) {
			$new_field = $_REQUEST[self::request_add_kit_field];
			if ($form_id > 0) {
				// Formular ist gueltig, neues Datenfeld einfuegen
				$fields = explode(',', $form_data[dbKITform::field_fields]);
				$fields[] = $kit_fields[$new_field];
				$where = array(dbKITform::field_id => $form_id);
				$form_data[dbKITform::field_fields] = implode(',', $fields);
				// nur die Felder aktualisieren
				$data = array(dbKITform::field_fields => $form_data[dbKITform::field_fields]);
				if (!$dbKITform->sqlUpdateRecord($data, $where)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
					return false;
				}
				$message .= $this->lang->translate('<p>The KIT datafield <b>{{ field }}</b> was added to the form.</p>', array('field' => $kitContactInterface->field_array[$new_field]));
			} else {
				// Formular ist noch nicht aktiv
				$message .= $this->lang->translate('<p>Please save the new form before you insert the datafield <b>{{ field }}</b>!</p>', array('field' => $kitContactInterface->field_array[$new_field]));
			}
		}
		
		// Allgemeine Felder hinzufuegen
		if (isset($_REQUEST[self::request_add_free_field]) && ($_REQUEST[self::request_add_free_field] != -1)) {
			if (isset($_REQUEST[self::request_free_field_title]) && ($_REQUEST[self::request_free_field_title] !== $this->lang->translate('Enter title ...')) && !empty($_REQUEST[self::request_free_field_title])) {
				if ($form_id > 0) {
					// Formular ist gueltig, neues Datenfeld hinzufuegen
					$data = array(dbKITformFields::field_type => $_REQUEST[self::request_add_free_field], dbKITformFields::field_title => $_REQUEST[self::request_free_field_title], dbKITformFields::field_form_id => $form_id);
					$field_id = -1;
					if (!$dbKITformFields->sqlInsertRecord($data, $field_id)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
						return false;
					}
					if ($data[dbKITformFields::field_type] == dbKITformFields::type_file) {
						// create settings for file uploads
						$upload_max_filesize = $this->convertBytes(ini_get('upload_max_filesize'));
						$post_max_size = $this->convertBytes(ini_get('post_max_size'));
						$max_filesize = $upload_max_filesize;
						
						$settings = array('upload_method' => array('label' => 'Upload method', 'name' => "upload_method_free_$field_id", 'value' => 'standard'), 'file_types' => array('label' => 'Allowed filetypes', 'name' => "file_types_free_$field_id", 'value' => $this->file_allowed_filetypes), 'max_file_size' => array('label' => 'max. filesize (MB)', 'name' => "max_file_size_free_$field_id", 'value' => $max_filesize / 1024 / 1024));
						$data = array(dbKITformFields::field_name => "free_$field_id", dbKITformFields::field_type_add => http_build_query($settings));
					} elseif ($data[dbKITformFields::field_type] == dbKITformFields::type_delayed) {
						// create settings for delayed transmissions
						$data = array(dbKITformFields::field_name => dbKITformFields::kit_delayed_transmission, dbKITformFields::field_value => 1);
					} else {
						// create all other fields ...
						$data = array(dbKITformFields::field_name => "free_$field_id");
					}
					$where = array(dbKITformFields::field_id => $field_id);
					if (!$dbKITformFields->sqlUpdateRecord($data, $where)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
						return false;
					}
					$fields = explode(',', $form_data[dbKITform::field_fields]);
					$fields[] = $field_id;
					$where = array(dbKITform::field_id => $form_id);
					$form_data[dbKITform::field_fields] = implode(',', $fields);
					$data[dbKITform::field_fields] = $form_data[dbKITform::field_fields];
					if (!$dbKITform->sqlUpdateRecord($data, $where)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
						return false;
					}
					$message .= $this->lang->translate('<p>The general datafield <b>{{ field }}</b> was added to the form.</p>', array('field' => $_REQUEST[self::request_free_field_title]));
				} else {
					// Formular ist noch nicht aktiv
					$message .= $this->lang->translate('<p>Please save the new form before you insert the datafield <b>{{ field }}</b>!</p>', array('field' => $_REQUEST[self::request_free_field_title]));
				}
			} else {
				// Titel fehlt oder ist unguelig
				$message .= $this->lang->translate('<p>Please select a datafield <b>and</b> specify a title for the new field!</p>');
			}
		}
		
		// Sortierung pruefen
		$where = array(dbKITformTableSort::field_value => $form_id, dbKITformTableSort::field_table => 'mod_kit_form');
		$sorter = array();
		if (!$dbKITformTableSort->sqlSelectRecord($where, $sorter)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformTableSort->getError()));
			return false;
		}
		if (count($sorter) > 0) {
			$form_fields = explode(',', $form_data[dbKITform::field_fields]);
			$sort_fields = explode(',', $sorter[0][dbKITformTableSort::field_order]);
			// erster Schritt: Sortierfeld bereinigen
			$unset = array_diff($sort_fields, $form_fields);
			foreach ($unset as $id) {
				$key = array_search($id, $sort_fields);
				unset($sort_fields[$key]);
			}
			// zweiter Schritt: Sortierfeld ergaenzen
			$add = array_diff($form_fields, $sort_fields);
			foreach ($add as $id) {
				$sort_fields[] = $id;
			}
			// letzter Schritt: Sortierfeld uebernehmen
			$form_data[dbKITform::field_fields] = implode(',', $sort_fields);
			$where = array(dbKITform::field_id => $form_id);
			// nur die Sortierung aktualisieren
			$data = array(dbKITform::field_fields => $form_data[dbKITform::field_fields]);
			if (!$dbKITform->sqlUpdateRecord($data, $where)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
				return false;
			}
		}
		$this->setMessage($message);
		return $this->dlgFormEdit();
	} // checkFormEdit()
	
	/**
	 * Dialog for creating or editing a form
	 * 
	 * @return string dialog
	 */
	protected function dlgFormEdit() {
		global $dbKITform;
		global $dbKITformFields;
		global $dbKITformTableSort;
		global $kitContactInterface;
		global $dbCfg;
		
		// Soll der Dialog exportiert werden?
		if (isset($_REQUEST[self::request_export]) && $_REQUEST[self::request_export] > 0) {
			$message = $this->getMessage();
			$this->setMessage('');
			if (!$this->exportForm())
				return false;
			$message .= $this->getMessage();
			$this->setMessage($message);
		}
		
		$form_id = isset($_REQUEST[dbKITform::field_id]) ? (int) $_REQUEST[dbKITform::field_id] : -1;
		
		$form_data = array();
		if ($form_id > 0) {
			// Datensatz auslesen
			$SQL = sprintf("SELECT * FROM %s WHERE %s='%s'", $dbKITform->getTableName(), dbKITform::field_id, $form_id);
			if (!$dbKITform->sqlExec($SQL, $form_data)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
				return false;
			}
			if (count($form_data) < 1) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The ID {{ id }} is invalid!', array('id', $form_id))));
				return false;
			}
			$form_data = $form_data[0];
		} else {
			// Default Werte setzen
			$form_data = $dbKITform->getFields();
			$form_data[dbKITform::field_status] = dbKITform::status_active;
			$form_data[dbKITform::field_fields] = $kitContactInterface->index_array[kitContactInterface::kit_email];
			$form_data[dbKITform::field_must_fields] = $kitContactInterface->index_array[kitContactInterface::kit_email];
			$form_data[dbKITform::field_captcha] = dbKITform::captcha_on;
			$form_data[dbKITform::field_action] = dbKITform::action_none;
			$form_data[dbKITform::field_email_cc] = '';
			$form_data[dbKITform::field_provider_id] = -1;
			$form_data[dbKITform::field_email_html] = dbKITform::html_off;
		}
		
		// alle Felder
		$fields = explode(',', $form_data[dbKITform::field_fields]);
		// Pflichtfelder
		$must_fields = explode(',', $form_data[dbKITform::field_must_fields]);
		// gesperrte Felder
		$disabled_fields = array(kitContactInterface::kit_email);
		
		if (in_array($kitContactInterface->index_array[kitContactInterface::kit_email_retype], $fields)) {
			// additional check if the field for retyping email address is used
			if (!in_array($kitContactInterface->index_array[kitContactInterface::kit_email_retype], $must_fields)) {
				$must_fields[] = $kitContactInterface->index_array[kitContactInterface::kit_email_retype];
			}
		}
		
		// pruefen ob Daten per REQUEST uebergeben wurden
		foreach ($form_data as $field => $value) {
			if (!isset($_REQUEST[$field]))
				continue;
			$form_data[$field] = $_REQUEST[$field];
		}
		
		// Service Provider auslesen
		$service_provider = array();
		if (!$kitContactInterface->getServiceProviderList($service_provider)) {
			if ($kitContactInterface->isError()) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
				return false;
			} elseif ($kitContactInterface->isMessage()) {
				$this->setMessage($kitContactInterface->getMessage());
			}
		}
		
		$form = array();
		// in dieser Schleife werden allgemeine Formulardaten gesetzt
		foreach ($form_data as $field => $value) {
			switch ($field) :
				// zuerst spezielle Value Felder setzen:
				case dbKITform::field_id :
					$form[$field]['value'] = ($form_id > 0) ? sprintf('%03d', $form_data[$field]) : $this->lang->translate('<i>- undetermined -</i>');
				// kein break!!!
				case dbKITform::field_id :
				case dbKITform::field_name :
				case dbKITform::field_title :
				case dbKITform::field_description :
				case dbKITform::field_email_cc :
					// sonstige Werte setzen
					$form[$field]['label'] = $this->lang->translate('label_' . $field);
					$form[$field]['name'] = $field;
					$form[$field]['hint'] = $this->lang->translate('hint_' . $field);
					// Value Feld nur setzen, wenn dies noch nicht geschehen ist
					if (!isset($form[$field]['value']))
						$form[$field]['value'] = $form_data[$field];
					break;
				case dbKITform::field_status :
					// Status Array zusammenstellen
					$form[$field]['items'] = array();
					foreach ($dbKITform->status_array as $value => $text) {
						if (($form_id < 1) && ($value == dbKITform::status_deleted))
							continue; // bei
							                                                                       // neuen
							                                                                       // Datensaetzen
							                                                                       // kein
							                                                                       // "Loeschen"
						$form[$field]['items'][] = array('value' => $value, 'text' => $text);
					}
					$form[$field]['label'] = $this->lang->translate('label_' . $field);
					$form[$field]['name'] = $field;
					$form[$field]['hint'] = $this->lang->translate('hint_' . $field);
					// Value Feld nur setzen, wenn dies noch nicht geschehen ist
					if (!isset($form[$field]['value']))
						$form[$field]['value'] = $form_data[$field];
					break;
				case dbKITform::field_provider_id :
					// Service Provider Array zusammenstellen
					$form[$field]['items'] = array();
					$form[$field]['items'][] = array('value' => -1, 'text' => $this->lang->translate('- select provider -'));
					foreach ($service_provider as $provider) {
						$form[$field]['items'][] = array('value' => $provider['id'], 'text' => sprintf('[%s] %s', $provider['name'], $provider['email']));
					}
					$form[$field]['label'] = $this->lang->translate('label_' . $field);
					$form[$field]['name'] = $field;
					$form[$field]['hint'] = $this->lang->translate('hint_' . $field, array('admin_url' => ADMIN_URL));
					// Value Feld nur setzen, wenn dies noch nicht geschehen ist
					if (!isset($form[$field]['value']))
						$form[$field]['value'] = $form_data[$field];
					break;
				case dbKITform::field_email_html :
					// E-Mail Versand mit HTML?
					$form[$field]['items'] = array();
					foreach ($dbKITform->html_array as $value => $text) {
						$form[$field]['items'][] = array('value' => $value, 'text' => $text);
					}
					$form[$field]['label'] = $this->lang->translate('label_' . $field);
					$form[$field]['name'] = $field;
					$form[$field]['hint'] = $this->lang->translate('hint_' . $field);
					// Value Feld nur setzen, wenn dies noch nicht geschehen ist
					if (!isset($form[$field]['value']))
						$form[$field]['value'] = $form_data[$field];
					break;
				case dbKITform::field_captcha :
					// Captcha Array zusammenstellen
					$form[$field]['items'] = array();
					foreach ($dbKITform->captcha_array as $value => $text) {
						$form[$field]['items'][] = array('value' => $value, 'text' => $text);
					}
					$form[$field]['label'] = $this->lang->translate('label_' . $field);
					$form[$field]['name'] = $field;
					$form[$field]['hint'] = $this->lang->translate('hint_' . $field);
					// Value Feld nur setzen, wenn dies noch nicht geschehen ist
					if (!isset($form[$field]['value']))
						$form[$field]['value'] = $form_data[$field];
					break;
				case dbKITform::field_action :
					if ($value == dbKITform::action_login) {
						// beim Login Dialog muss das Passwort Feld enthalten
						// sein
						if (!in_array($kitContactInterface->index_array[kitContactInterface::kit_password], $fields)) {
							$fields[] = $kitContactInterface->index_array[kitContactInterface::kit_password];
							$form_data[dbKITform::field_fields] = implode(',', $fields);
						}
						if (!in_array($kitContactInterface->index_array[kitContactInterface::kit_password], $must_fields)) {
							$must_fields[] = $kitContactInterface->index_array[kitContactInterface::kit_password];
							$form_data[dbKITform::field_must_fields] = implode(',', $must_fields);
						}
						if (!in_array(kitContactInterface::kit_password, $disabled_fields))
							$disabled_fields[] = kitContactInterface::kit_password;
					}
				default :
					// nothing to do, skip
					break;
			endswitch
			;
		}
		// zusaetzliche Formularfelder setzen
		$form['export']['label'] = $this->lang->translate('label_form_export');
		$form['export']['name'] = self::request_export;
		$form['export']['value'] = $form_id;
		$form['export']['hint'] = $this->lang->translate('hint_form_export');
		
		$form_fields = array();
		foreach ($fields as $field_id) {
			if ($field_id < 100) {
				// KIT Datenfeld
				$field_name = array_search($field_id, $kitContactInterface->index_array);
				$label = $kitContactInterface->field_array[$field_name];
				if (($field_id > 30) && ($field_id < 38)) {
					// additional field or note
					if ($field_id < 36) {
						// additional field
						$additional_fields = $dbCfg->getValue(dbKITcfg::cfgAdditionalFields);
						foreach ($additional_fields as $add_field) {
							list($i, $val) = explode('|', $add_field);
							$i += 30; // add the KIT offset for the field
							          // (KIT_FREE_FIELD_1 == 31)
							if ($i == $field_id) {
								$label = $val . '*';
								break;
							}
						}
					} else {
						// additional note
						$additional_notes = $dbCfg->getValue(dbKITcfg::cfgAdditionalNotes);
						foreach ($additional_notes as $add_note) {
							list($i, $val) = explode('|', $add_note);
							$i += 35; // add the KIT offset for the note
							          // (KIT_FREE_NOTE_1 == 36)
							if ($i == $field_id) {
								$label = $val . '*';
								break;
							}
						}
					}
				}
				$form_fields[$field_name] = array('id' => $field_id, 'label' => $this->lang->translate('label_kit_label_marker', array('name' => $label)), 'name' => $field_name, 'must' => array('name' => 'must_' . $field_name, 'value' => (in_array($field_id, $must_fields)) ? 1 : 0, 'text' => $this->lang->translate('mark as must field')), 'hint' => array('dialog' => $this->lang->translate('hint_' . $field_name)), 'disabled' => in_array($field_name, $disabled_fields) ? 1 : 0);
			} else {
				// allgemeines Datenfeld
				$where = array(dbKITformFields::field_id => $field_id);
				$data = array();
				if (!$dbKITformFields->sqlSelectRecord($where, $data)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
					return false;
				}
				if (count($data) < 1) {
					continue;
				/**
				 *
				 * @todo continue is only a workaraound ...
				 */
					// $this->setError(sprintf('[%s - %s] %s', __METHOD__,
				// __LINE__, sprintf(kit_error_invalid_id, $field_id)));
					// return false;
				}
				$data = $data[0];
				// allgemeine Werte und Einstellungen
				if (empty($data[dbKITformFields::field_name]))
					$data[dbKITformFields::field_name] = 'field_' . $field_id;
				$field_name = $data[dbKITformFields::field_name];
				// Datentypen Auswahl
				$data_types = array();
				foreach ($dbKITformFields->data_type_array as $value => $text) {
					$data_types[] = array('value' => $value, 'text' => $text);
				}
				switch ($data[dbKITformFields::field_type]) :
					case dbKITformFields::type_text :
						// INPUT TEXT
						$form_fields[$field_name] = array('id' => $field_id, 'label' => $this->lang->translate('label_free_label_marker', array('title' => $data[dbKITformFields::field_title])), 'name' => $field_name, 'field' => array('name' => 'name_' . $field_name, 'value' => $field_name, 'label' => $this->lang->translate('label_name_label')), 'must' => array('name' => 'must_' . $field_name, 'value' => (in_array($field_id, $must_fields)) ? 1 : 0, 'text' => $this->lang->translate('mark as must field')), 'hint' => array('dialog' => $this->lang->translate('hint_free_field_type_text'), 'name' => "hint_$field_name", 'value' => $data[dbKITformFields::field_hint], 'label' => $this->lang->translate('label_hint_label')), 'title' => array('name' => "title_$field_name", 'value' => $data[dbKITformFields::field_title], 'label' => $this->lang->translate('label_title_label')), 'default' => array('value' => $data[dbKITformFields::field_value], 'name' => "default_$field_name", 'label' => $this->lang->translate('label_default_label')), 'type' => array('type' => $data[dbKITformFields::field_type], 'name' => "type_$field_name", 'value' => $dbKITformFields->type_array[$data[dbKITformFields::field_type]], 'label' => $this->lang->translate('label_type_label')), 'data_type' => array('array' => $data_types, 'value' => $data[dbKITformFields::field_data_type], 'name' => "data_type_$field_name", 'label' => $this->lang->translate('label_data_type_label')));
						break;
					case dbKITformFields::type_text_area :
						// TEXTAREA
						$form_fields[$field_name] = array('id' => $field_id, 'label' => $this->lang->translate('label_free_label_marker', array('title' => $data[dbKITformFields::field_title])), 'name' => $field_name, 'field' => array('name' => 'name_' . $field_name, 'value' => $field_name, 'label' => $this->lang->translate('label_name_label')), 'must' => array('name' => 'must_' . $field_name, 'value' => (in_array($field_id, $must_fields)) ? 1 : 0, 'text' => $this->lang->translate('mark as must field')), 'hint' => array('dialog' => $this->lang->translate('hint_free_field_type_text_area'), 'name' => "hint_$field_name", 'value' => $data[dbKITformFields::field_hint], 'label' => $this->lang->translate('label_hint_label')), 'title' => array('name' => "title_$field_name", 'value' => $data[dbKITformFields::field_title], 'label' => $this->lang->translate('label_title_label')), 'default' => array('value' => $data[dbKITformFields::field_value], 'name' => "default_$field_name", 'label' => $this->lang->translate('label_default_label')), 'type' => array('type' => $data[dbKITformFields::field_type], 'name' => "type_$field_name", 'value' => $dbKITformFields->type_array[$data[dbKITformFields::field_type]], 'label' => $this->lang->translate('label_type_label')));
						break;
					case dbKITformFields::type_checkbox :
						// CHECKBOX
						// zusaetzliche Felder auslesen
						$checkboxes = array();
						parse_str($data[dbKITformFields::field_type_add], $checkboxes);
						// Option: neues Feld hinzufuegen
						$checkboxes[] = array('name' => $field_id, 'value' => '', 'text' => '', 'checked' => 0);
						$form_fields[$field_name] = array('id' => $field_id, 'label' => $this->lang->translate('label_free_label_marker', array('title' => $data[dbKITformFields::field_title])), 'name' => $field_name, 'field' => array('name' => 'name_' . $field_name, 'value' => $field_name, 'label' => $this->lang->translate('label_name_label')), 'must' => array('name' => 'must_' . $field_name, 'value' => (in_array($field_id, $must_fields)) ? 1 : 0, 'text' => $this->lang->translate('mark as must field')), 'hint' => array('dialog' => $this->lang->translate('hint_free_field_type_checkbox'), 'name' => "hint_$field_name", 'value' => $data[dbKITformFields::field_hint], 'label' => $this->lang->translate('label_hint_label'), 'hint_add' => $this->lang->translate('hint_free_checkbox_hint_add'), 'hint_val' => $this->lang->translate('hint_free_checkbox_hint_val'), 'hint_txt' => $this->lang->translate('hint_free_checkbox_hint_txt'), 'hint_sel' => $this->lang->translate('hint_free_checkbox_hint_sel')), 'title' => array('name' => "title_$field_name", 'value' => $data[dbKITformFields::field_title], 'label' => $this->lang->translate('label_title_label')), 'type' => array('type' => $data[dbKITformFields::field_type], 'name' => "type_$field_name", 'value' => $dbKITformFields->type_array[$data[dbKITformFields::field_type]], 'label' => $this->lang->translate('label_type_label')), 'checkbox' => $checkboxes, 'move' => array('img_src' => $this->img_url, 'position' => self::request_position, 'leptoken' => (isset($_GET['leptoken'])) ? $_GET['leptoken'] : '', 'count' => count($checkboxes) - 1, 'up' => array('text' => $this->lang->translate('Move item up'), 'link' => sprintf('%s&%s', $this->page_link, http_build_query(array(self::request_action => self::action_move, self::request_move => self::action_up, dbKITformFields::field_id => $field_id, dbKITform::field_id => $form_id)))), 'down' => array('text' => $this->lang->translate('Move item down'), 'link' => sprintf('%s&%s', $this->page_link, http_build_query(array(self::request_action => self::action_move, self::request_move => self::action_down, dbKITformFields::field_id => $field_id, dbKITform::field_id => $form_id))))));
						break;
					case dbKITformFields::type_radio :
						// RADIOBUTTONS
						// zusaetzliche Felder auslesen
						$radios = array();
						parse_str($data[dbKITformFields::field_type_add], $radios);
						// Option: neues Feld hinzufuegen
						$radios[] = array('name' => $field_id, 'value' => '', 'text' => '', 'checked' => 0);
						$form_fields[$field_name] = array('id' => $field_id, 'label' => $this->lang->translate('label_free_label_marker', array('title' => $data[dbKITformFields::field_title])), 'name' => $field_name, 'field' => array('name' => 'name_' . $field_name, 'value' => $field_name, 'label' => $this->lang->translate('label_name_label')), 'must' => array('name' => 'must_' . $field_name, 'value' => (in_array($field_id, $must_fields)) ? 1 : 0, 'text' => $this->lang->translate('mark as must field')), 'hint' => array('dialog' => $this->lang->translate('hint_free_field_type_radiobutton'), 'name' => "hint_$field_name", 'value' => $data[dbKITformFields::field_hint], 'label' => $this->lang->translate('label_hint_label'), 'hint_add' => $this->lang->translate('hint_free_radio_hint_add'), 'hint_val' => $this->lang->translate('hint_free_radio_hint_val'), 'hint_txt' => $this->lang->translate('hint_free_radio_hint_txt'), 'hint_sel' => $this->lang->translate('hint_free_radio_hint_sel')), 'title' => array('name' => "title_$field_name", 'value' => $data[dbKITformFields::field_title], 'label' => $this->lang->translate('label_title_label')), 'type' => array('type' => $data[dbKITformFields::field_type], 'name' => "type_$field_name", 'value' => $dbKITformFields->type_array[$data[dbKITformFields::field_type]], 'label' => $this->lang->translate('label_type_label')), 'radios' => $radios, 'move' => array('img_src' => $this->img_url, 'position' => self::request_position, 'leptoken' => (isset($_GET['leptoken'])) ? $_GET['leptoken'] : '', 'count' => count($radios) - 1, 'up' => array('text' => $this->lang->translate('Move item up'), 'link' => sprintf('%s&%s', $this->page_link, http_build_query(array(self::request_action => self::action_move, self::request_move => self::action_up, dbKITformFields::field_id => $field_id, dbKITform::field_id => $form_id)))), 'down' => array('text' => $this->lang->translate('Move item down'), 'link' => sprintf('%s&%s', $this->page_link, http_build_query(array(self::request_action => self::action_move, self::request_move => self::action_down, dbKITformFields::field_id => $field_id, dbKITform::field_id => $form_id))))));
						break;
					case dbKITformFields::type_delayed :
						// delayed execution for the form
						$type_add = array();
						parse_str($data[dbKITformFields::field_type_add], $type_add);
						if (!isset($type_add['text']))
							$type_add['text'] = $this->lang->translate('Save the form and submit it later');
						$form_fields[$field_name] = array('id' => $field_id, 'label' => $this->lang->translate('label_free_label_marker', array('title' => $data[dbKITformFields::field_title])), 'name' => dbKITformFields::kit_delayed_transmission, 						// $field_name,
						'field' => array('name' => 'name_' . $field_name, 'value' => $field_name, 'label' => $this->lang->translate('label_name_label')), 'must' => array('name' => 'must_' . $field_name, 'value' => (in_array($field_id, $must_fields)) ? 1 : 0, 'text' => $this->lang->translate('mark as must field')), 'hint' => array('dialog' => $this->lang->translate('hint_free_field_type_delayed'), 'name' => "hint_$field_name", 'value' => $data[dbKITformFields::field_hint], 'label' => $this->lang->translate('label_hint_label')), 'title' => array('name' => "title_$field_name", 'value' => $data[dbKITformFields::field_title], 'label' => $this->lang->translate('label_title_label')), 'type' => array('type' => $data[dbKITformFields::field_type], 'name' => "type_$field_name", 'value' => $dbKITformFields->type_array[$data[dbKITformFields::field_type]], 'label' => $this->lang->translate('label_type_label')), 'value' => array('value' => 1, 'name' => dbKITformFields::kit_delayed_transmission, 'label' => $this->lang->translate('label_value_label')), 'text' => array('value' => $type_add['text'], 'name' => "text_$field_name", 'label' => $this->lang->translate('Text')))

						;
						break;
					case dbKITformFields::type_file :
						// File upload
						$settings = array();
						parse_str($data[dbKITformFields::field_type_add], $settings);
						$setting_array = array();
						foreach ($settings as $key => $setting) {
							$setting_array[$key] = array('label' => $this->lang->translate($setting['label']), 'name' => $setting['name'], 'value' => $setting['value']);
						}
						$form_fields[$field_name] = array(
								'id' => $field_id, 
								'label' => $this->lang->translate('label_free_label_marker', 
										array('title' => $data[dbKITformFields::field_title])), 
								'name' => $field_name, 
								'field' => array(
										'name' => 'name_' . $field_name, 
										'value' => $field_name, 
										'label' => $this->lang->translate('label_name_label')), 
								'must' => array(
										'name' => 'must_' . $field_name, 
										'value' => (in_array($field_id, $must_fields)) ? 1 : 0, 
										'text' => $this->lang->translate('mark as must field')), 
								'title' => array(
										'name' => "title_$field_name", 
										'value' => $data[dbKITformFields::field_title], 
										'label' => $this->lang->translate('label_title_label')), 
								'type' => array(
										'type' => $data[dbKITformFields::field_type], 
										'name' => "type_$field_name", 
										'value' => $dbKITformFields->type_array[$data[dbKITformFields::field_type]], 
										'label' => $this->lang->translate('label_type_label')),						
										// additional settings for file uploads
								'settings' => $setting_array, 
								'hint' => array(
										'dialog' => $this->lang->translate('hint_free_field_type_file'), 
										'name' => "hint_$field_name", 
										'value' => $data[dbKITformFields::field_hint], 
										'label' => $this->lang->translate('label_hint_label'))
								);
						break;
					case dbKITformFields::type_select :
						// SELECT Auswahl
						// zusaetzliche Felder auslesen
						$options = array();
						parse_str($data[dbKITformFields::field_type_add], $options);
						// Option: neues Feld hinzufuegen
						$options[] = array('name' => $field_id, 'value' => '', 'text' => '', 'checked' => 0);
						$form_fields[$field_name] = array('id' => $field_id, 'label' => $this->lang->translate('label_free_label_marker', array('title' => $data[dbKITformFields::field_title])), 'name' => $field_name, 'field' => array('name' => 'name_' . $field_name, 'value' => $field_name, 'label' => $this->lang->translate('label_name_label')), 'must' => array('name' => 'must_' . $field_name, 'value' => (in_array($field_id, $must_fields)) ? 1 : 0, 'text' => $this->lang->translate('mark as must field')), 'hint' => array('dialog' => $this->lang->translate('hint_free_field_type_select'), 'name' => "hint_$field_name", 'value' => $data[dbKITformFields::field_hint], 'label' => $this->lang->translate('label_hint_label'), 'hint_add' => $this->lang->translate('hint_free_select_hint_add'), 'hint_val' => $this->lang->translate('hint_free_select_hint_val'), 'hint_txt' => $this->lang->translate('hint_free_select_hint_txt'), 'hint_sel' => $this->lang->translate('hint_free_select_hint_sel')), 'title' => array('name' => "title_$field_name", 'value' => $data[dbKITformFields::field_title], 'label' => $this->lang->translate('label_title_label')), 'type' => array('type' => $data[dbKITformFields::field_type], 'name' => "type_$field_name", 'value' => $dbKITformFields->type_array[$data[dbKITformFields::field_type]], 'label' => $this->lang->translate('label_type_label')), 'size' => array('name' => "size_$field_name", 'value' => $data[dbKITformFields::field_value], 'label' => $this->lang->translate('label_size_label')), 'options' => $options, 'move' => array('img_src' => $this->img_url, 'position' => self::request_position, 'leptoken' => (isset($_GET['leptoken'])) ? $_GET['leptoken'] : '', 'count' => count($options) - 1, 'up' => array('text' => $this->lang->translate('Move item up'), 'link' => sprintf('%s&%s', $this->page_link, http_build_query(array(self::request_action => self::action_move, self::request_move => self::action_up, dbKITformFields::field_id => $field_id, dbKITform::field_id => $form_id)))), 'down' => array('text' => $this->lang->translate('Move item down'), 'link' => sprintf('%s&%s', $this->page_link, http_build_query(array(self::request_action => self::action_move, self::request_move => self::action_down, dbKITformFields::field_id => $field_id, dbKITform::field_id => $form_id))))));
						break;
					case dbKITformFields::type_html :
						$form_fields[$field_name] = array('id' => $field_id, 'label' => $this->lang->translate('label_free_label_marker', array('title' => $data[dbKITformFields::field_title])), 'name' => $field_name, 'must' => array('name' => 'must_' . $field_name, 'value' => (in_array($field_id, $must_fields)) ? 1 : 0, 'text' => $this->lang->translate('mark as must field')), 'title' => array('name' => "title_$field_name", 'value' => $data[dbKITformFields::field_title], 'label' => $this->lang->translate('label_title_label')), 'hint' => array('dialog' => $this->lang->translate('hint_free_field_type_html')), 'type' => array('type' => $data[dbKITformFields::field_type], 'name' => "type_$field_name", 'value' => $dbKITformFields->type_array[$data[dbKITformFields::field_type]], 'label' => $this->lang->translate('label_type_label')), 'html' => array('name' => "html_$field_name", 'value' => $data[dbKITformFields::field_value], 'label' => $this->lang->translate('label_html_label')));
						break;
					case dbKITformFields::type_hidden :
						// HIDDEN Feld
						$form_fields[$field_name] = array('id' => $field_id, 'label' => $this->lang->translate('label_free_label_marker', array('title' => $data[dbKITformFields::field_title])), 'name' => $field_name, 'field' => array('name' => 'name_' . $field_name, 'value' => $field_name, 'label' => $this->lang->translate('label_name_label')), 'must' => array('name' => 'must_' . $field_name, 'value' => (in_array($field_id, $must_fields)) ? 1 : 0, 'text' => $this->lang->translate('mark as must field')), 'hint' => array('dialog' => $this->lang->translate('hint_free_field_type_hidden')), 'title' => array('name' => "title_$field_name", 'value' => $data[dbKITformFields::field_title], 'label' => $this->lang->translate('label_title_label')), 'value' => array('value' => $data[dbKITformFields::field_value], 'name' => "value_$field_name", 'label' => $this->lang->translate('label_value_label')), 'type' => array('type' => $data[dbKITformFields::field_type], 'name' => "type_$field_name", 'value' => $dbKITformFields->type_array[$data[dbKITformFields::field_type]], 'label' => $this->lang->translate('label_type_label')));
						break;
					default :
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The field type <b>{{ type }}</b> is not implemented!', array('type' => $data[dbKITformFields::field_type]))));
						return false;
				endswitch
				;
			}
		}
		
		// KIT LINKS hinzufuegen
		$links = array();
		parse_str($form_data[dbKITform::field_links], $links);
		$form['kit_link'] = array();
		foreach ($dbKITform->action_array as $name => $text) {
			$SQL = sprintf("SELECT * FROM %s WHERE %s='%s' AND %s='%s'", $dbKITform->getTableName(), dbKITform::field_status, dbKITform::status_active, dbKITform::field_action, $name);
			$link_forms = array();
			if (!$dbKITform->sqlExec($SQL, $link_forms)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
				return false;
			}
			$value = array();
			$value[] = array('value' => dbKITform::action_none, 'text' => $this->lang->translate('- not assigned -'), 'selected' => (isset($links[$name]) && ($links[$name] == dbKITform::action_none)) ? 1 : 0);
			foreach ($link_forms as $link) {
				$value[] = array('value' => $link[dbKITform::field_name], 'text' => $link[dbKITform::field_name], 'selected' => (isset($links[$name]) && ($links[$name] == $link[dbKITform::field_name])) ? 1 : 0);
			}
			$form['kit_link'][] = array('label' => $this->lang->translate('label_kit_link', array('text' => $text)), 'name' => $name, 'hint' => $this->lang->translate('hint_kit_link_add', array('form' => $text)), 'value' => $value);
		}
		
		// neue KIT Aktion hinzufuegen
		$form['kit_action']['label'] = $this->lang->translate('label_kit_action_add');
		$form['kit_action']['name'] = dbKITform::field_action; // self::request_add_kit_action;
		$form['kit_action']['hint'] = $this->lang->translate('hint_kit_action_add');
		$form['kit_action']['value'] = array();
		$form['kit_action']['value'][] = array('value' => -1, 'text' => $this->lang->translate('- select KIT action -'));
		$field_array = $dbKITform->action_array;
		asort($field_array);
		foreach ($field_array as $value => $text) {
			$form['kit_action']['value'][] = array('value' => $value, 'text' => $text, 'selected' => ($value == $form_data[dbKITform::field_action]) ? 1 : 0);
		}
		
		// neues KIT Feld hinzufuegen
		$form['kit_field']['label'] = $this->lang->translate('label_kit_field_add');
		$form['kit_field']['name'] = self::request_add_kit_field;
		$form['kit_field']['hint'] = $this->lang->translate('hint_kit_field_add');
		$form['kit_field']['value'] = array();
		$form['kit_field']['value'][] = array('value' => -1, 'text' => $this->lang->translate('- select datafield -'));
		$field_array = $kitContactInterface->field_array;
		
		// remove the additional fields from the list
		unset($field_array[kitContactInterface::kit_free_field_1]);
		unset($field_array[kitContactInterface::kit_free_field_2]);
		unset($field_array[kitContactInterface::kit_free_field_3]);
		unset($field_array[kitContactInterface::kit_free_field_4]);
		unset($field_array[kitContactInterface::kit_free_field_5]);
		unset($field_array[kitContactInterface::kit_free_note_1]);
		unset($field_array[kitContactInterface::kit_free_note_2]);
		
		// check if additional fields are defined
		$show_additional_fields = false;
		$additional_fields = $dbCfg->getValue(dbKITcfg::cfgAdditionalFields);
		if (isset($additional_fields[0]) && !empty($additional_fields[0]))
			$show_additional_fields = true;
		$additional_notes = $dbCfg->getValue(dbKITcfg::cfgAdditionalNotes);
		if (isset($additional_notes[0]) && !empty($additional_notes[0]))
			$show_additional_fields = true;
		if (count($additional_notes) > 0)
			$show_additional_fields = true;
		if ($show_additional_fields) {
			// adapt the list ...
			foreach ($additional_fields as $field_data) {
				if (empty($field_data))
					continue;
				if (false === (strpos($field_data, '|')))
					continue;
				list($fid, $label) = explode('|', $field_data);
				$field_array[sprintf('kit_free_field_%d', $fid)] = sprintf('%s*', $label);
			}
			foreach ($additional_notes as $field_data) {
				if (empty($field_data))
					continue;
				if (false === (strpos($field_data, '|')))
					continue;
				list($fid, $label) = explode('|', $field_data);
				$field_array[sprintf('kit_free_note_%d', $fid)] = sprintf('%s*', $label);
			}
		}
		asort($field_array);
		foreach ($field_array as $field => $text) {
			if (in_array($kitContactInterface->index_array[$field], $fields))
				continue;
			$form['kit_field']['value'][] = array('value' => $field, 'text' => $text);
		}
		
		// Allgemeine Felder hinzufuegen
		$form['free_field']['label'] = $this->lang->translate('label_free_field_add');
		$form['free_field']['name'] = self::request_add_free_field;
		$form['free_field']['hint'] = $this->lang->translate('hint_free_field_add');
		$form['free_field']['value'] = array();
		$form['free_field']['value'][] = array('value' => -1, 'text' => $this->lang->translate('- select datafield -'));
		$field_array = $dbKITformFields->type_array;
		asort($field_array);
		if (isset($form_fields[dbKITformFields::kit_delayed_transmission])) {
			// delayed transmission can only used once!
			unset($field_array[dbKITformFields::type_delayed]);
		}
		foreach ($field_array as $field => $text) {
			$form['free_field']['value'][] = array('value' => $field, 'text' => $text);
		}
		$form['free_field']['title']['label'] = $this->lang->translate('label_free_field_title');
		$form['free_field']['title']['name'] = self::request_free_field_title;
		$form['free_field']['title']['value'] = $this->lang->translate('label_free_field_title');
		
		$sorter_table = 'mod_kit_form';
		$sorter_active = 0;
		if ($form_id > 0) {
			$SQL = sprintf("SELECT * FROM %s WHERE %s='%s' AND %s='%s'", $dbKITformTableSort->getTableName(), dbKITformTableSort::field_table, $sorter_table, dbKITformTableSort::field_value, $form_id);
			$sorter = array();
			if (!$dbKITformTableSort->sqlExec($SQL, $sorter)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformTableSort->getError()));
				return false;
			}
			if (count($sorter) < 1) {
				$data = array(dbKITformTableSort::field_table => $sorter_table, dbKITformTableSort::field_value => $form_id, dbKITformTableSort::field_order => '');
				if (!$dbKITformTableSort->sqlInsertRecord($data)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformTableSort->getError()));
					return false;
				}
			}
			$sorter_active = 1;
		}
		// Dialog ausgeben
		$data = array('form_action' => $this->page_link, 'action_name' => self::request_action, 'action_value' => self::action_edit_check, 'form_name' => dbKITform::field_id, 'form_value' => $form_id, 'header' => $this->lang->translate('Edit the form'), 'is_intro' => ($this->isMessage()) ? 0 : 1, 'intro' => ($this->isMessage()) ? $this->getMessage() : $this->lang->translate('With this dialog you can create and edit general forms and special forms for KeepInTouch (KIT).'), 'btn_ok' => $this->lang->translate('OK'), 'btn_abort' => $this->lang->translate('Abort'), 'abort_location' => $this->page_link, 'form' => $form, 'fields' => $form_fields, 'kit_fields_intro' => $this->lang->translate('Select the KeepInTouch (KIT) contact fields you wish to use with this form.'), 'sorter_table' => $sorter_table, 'sorter_active' => $sorter_active, 'sorter_value' => $form_id, 'fields_name' => dbKITform::field_fields, 'fields_value' => $form_data[dbKITform::field_fields], 'must_fields_name' => dbKITform::field_must_fields, 'must_fields_value' => $form_data[dbKITform::field_must_fields]);
		return $this->getTemplate('backend.form.edit.htt', $data);
	} // dlgFormEdit()
	
	protected function checkMove() {
		global $dbKITformFields;
		
		$field_id = (isset($_REQUEST[dbKITformFields::field_id])) ? (int) $_REQUEST[dbKITformFields::field_id] : -1;
		$position = (isset($_REQUEST[self::request_position])) ? (int) $_REQUEST[self::request_position] : -1;
		$move_up = (isset($_REQUEST[self::request_move]) && ($_REQUEST[self::request_move] == self::action_up)) ? true : false;
		$SQL = sprintf("SELECT %s FROM %s WHERE %s='%s'", dbKITformFields::field_type_add, $dbKITformFields->getTableName(), dbKITformFields::field_id, $field_id);
		$result = array();
		if (!$dbKITformFields->sqlExec($SQL, $result)) {
			$this->setError(sprintf('[%s  %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
			return false;
		}
		if (count($result) < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Error: The <b>ID {{ id }}</b> is invalid.'), array('id' => $field_id)));
			return false;
		}
		parse_str($result[0][dbKITformFields::field_type_add], $fields);
		if ($move_up) {
			$new_position = $position - 1;
			if ($new_position < 0)
				$new_position = 0;
		} else {
			$new_position = $position + 1;
			if ($new_position >= count($fields))
				$new_position = -1;
		}
		
		$new_fields = array();
		if ($move_up) {
			for($i = 0; $i < count($fields); $i++) {
				if ($i == $new_position)
					$new_fields[] = $fields[$position];
				if ($i == $position)
					continue;
				$new_fields[] = $fields[$i];
			}
		} elseif ($new_position > -1) {
			for($i = 0; $i < count($fields); $i++) {
				if ($i == $position) {
					$new_fields[] = $fields[$new_position];
					$new_fields[] = $fields[$position];
					continue;
				}
				if ($i == $new_position)
					continue;
				$new_fields[] = $fields[$i];
			}
		} else {
			// nothing to do ...
			$new_fields = $fields;
		}
		$where = array(dbKITformFields::field_id => $field_id);
		$data = array(dbKITformFields::field_type_add => http_build_query($new_fields));
		if (!$dbKITformFields->sqlUpdateRecord($data, $where)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
			return false;
		}
		$this->setMessage($this->lang->translate('The item has successfully moved'));
		return $this->dlgFormEdit();
	} // checkMove()
	
	/**
	 * Shows a list with all available forms
	 * 
	 * @return string dialog
	 */
	protected function dlgFormList() {
		global $dbKITform;
		
		$SQL = sprintf("SELECT * FROM %s WHERE %s!='%s' ORDER BY %s DESC", $dbKITform->getTableName(), dbKITform::field_status, dbKITform::status_deleted, dbKITform::field_timestamp);
		$forms = array();
		if (!$dbKITform->sqlExec($SQL, $forms)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
			return false;
		}
		
		$list = array();
		foreach ($forms as $form) {
			$list[] = array('id' => $form[dbKITform::field_id], 'link' => sprintf('%s&%s=%s&%s=%s', $this->page_link, self::request_action, self::action_edit, dbKITform::field_id, $form[dbKITform::field_id]), 'name' => $form[dbKITform::field_name], 'status' => $dbKITform->status_array[$form[dbKITform::field_status]], 'title' => $form[dbKITform::field_title], 'timestamp' => date(cfg_datetime_str, strtotime($form[dbKITform::field_timestamp])));
		}
		
		// check if provider isset for all forms...
		$SQL = sprintf("SELECT %s FROM %s WHERE %s!='%s' AND %s<'1'", dbKITform::field_id, $dbKITform->getTableName(), dbKITform::field_status, dbKITform::status_deleted, dbKITform::field_provider_id);
		$providers = array();
		if (!$dbKITform->sqlExec($SQL, $providers)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
			return false;
		}
		if (count($providers) > 0) {
			// there are some records without a provider
			$check = array();
			foreach ($providers as $provider) {
				$check[] = $provider[dbKITform::field_id];
			}
			$cs = implode(', ', $check);
			$this->setMessage($this->lang->translate('Please check the forms <b>{{ ids }}</b>.<br />For these forms is no <b>provider</b> defined and they will not work proper!', array('ids' => $cs)));
		}
		
		$data = array('head' => $this->lang->translate('List of all available forms'), 'is_message' => $this->isMessage() ? 1 : 0, 'intro' => $this->isMessage() ? $this->getMessage() : $this->lang->translate('Select a form to get details and editing.<br />To create a new form please select the tab "Edit".'), 'header' => array('id' => $this->lang->translate('th_id'), 'name' => $this->lang->translate('th_name'), 'status' => $this->lang->translate('th_status'), 'title' => $this->lang->translate('th_title'), 'timestamp' => $this->lang->translate('th_timestamp')), 'forms' => $list, 'form_action' => $this->page_link, 'action_name' => self::request_action, 'action_value' => self::action_import, 'import' => array('file' => self::request_import_file, 'rename' => $this->lang->translate('label_import_form_rename'), 'name' => self::request_import_name, 'label' => $this->lang->translate('label_import_form')), 'btn_import' => $this->lang->translate('Import ...'));
		return $this->getTemplate('backend.form.list.htt', $data);
	} // dlgFormList()
	
	/**
	 * Shows an about dialog for kitForm
	 * 
	 * @return string dialog
	 */
	protected function dlgAbout() {
		$data = array('version' => sprintf('%01.2f', $this->getVersion()), 'img_url' => $this->img_url . '/kit_form_logo_400_267.jpg', 'release_notes' => file_get_contents(WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/info.txt'));
		return $this->getTemplate('backend.about.htt', $data);
	} // dlgAbout()
	
	/**
	 * Show the protocol of all submitted forms
	 * 
	 * @return string dialog
	 */
	protected function dlgProtocolList() {
		global $dbKITform;
		global $dbKITformData;
		global $kitContactInterface;
		
		$SQL = sprintf("SELECT * FROM %s WHERE %s='%s' ORDER BY %s DESC LIMIT 100", $dbKITformData->getTableName(), dbKITformData::field_status, dbKITformData::status_active, dbKITformData::field_date);
		$items = array();
		if (!$dbKITformData->sqlExec($SQL, $items)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
			return false;
		}
		$list = array();
		foreach ($items as $item) {
			$contact = array();
			if (!$kitContactInterface->getContact($item[dbKITformData::field_kit_id], $contact)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
				return false;
			}
			$contact['link'] = sprintf('%s&%s=%s', ADMIN_URL . '/admintools/tool.php?tool=kit&act=con', dbKITcontact::field_id, $item[dbKITformData::field_kit_id]);
			$where = array(dbKITform::field_id => $item[dbKITformData::field_form_id]);
			$form = array();
			if (!$dbKITform->sqlSelectRecord($where, $form)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
				return false;
			}
			if (count($form) < 1) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The ID {{ id }} is invalid!', array('id' => $item[dbKITformData::field_form_id]))));
				return false;
			}
			$form = $form[0];
			$form['id'] = $item[dbKITformData::field_id];
			$form['link'] = sprintf('%s&%s=%s&%s=%s', $this->page_link, self::request_action, self::action_protocol_id, self::request_protocol_id, $item[dbKITformData::field_id]);
			$form['datetime'] = date(cfg_datetime_str, strtotime($item[dbKITformData::field_date]));
			$list[] = array('contact' => $contact, 'form' => $form);
		} // foreach
		
		$data = array('head' => $this->lang->translate('Protocol List'), 'intro' => $this->lang->translate('Protocol of the submitted forms.<br />Click at the <b>ID</b> or the submission date to get details of the submitted form.<br />Click at contact to switch to KeepInTouch (KIT) and get details of the contact.'), 'header' => array('id' => $this->lang->translate('th_id'), 'form_name' => $this->lang->translate('th_form_name'), 'datetime' => $this->lang->translate('th_datetime'), 'contact' => $this->lang->translate('th_contact'), 'email' => $this->lang->translate('th_email')), 'list' => $list);
		return $this->getTemplate('backend.protocol.list.htt', $data);
	} // dlgProtocolList()
	
	/**
	 * Shows details for a selected protocol item
	 * 
	 * @return string dialog
	 */
	public function dlgProtocolItem() {
		global $dbKITform;
		global $dbKITformData;
		global $dbKITformFields;
		global $kitContactInterface;
		
		$protocol_id = (isset($_REQUEST[self::request_protocol_id])) ? $_REQUEST[self::request_protocol_id] : -1;
		
		$SQL = sprintf("SELECT * FROM %s WHERE %s='%s'", $dbKITformData->getTableName(), dbKITformData::field_id, $protocol_id);
		$protocol = array();
		if (!$dbKITformData->sqlExec($SQL, $protocol)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
			return false;
		}
		if (count($protocol) < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The ID {{ id }} is invalid!', array('id' => $protocol_id))));
			return false;
		}
		
		$protocol = $protocol[0];
		$protocol['datetime'] = date(cfg_datetime_str, strtotime($protocol[dbKITformData::field_date]));
		
		$contact = array();
		if (!$kitContactInterface->getContact($protocol[dbKITformData::field_kit_id], $contact)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
			return false;
		}
		$contact['id'] = $protocol[dbKITformData::field_kit_id];
		$contact['link'] = sprintf('%s&%s=%s', ADMIN_URL . '/admintools/tool.php?tool=kit&act=con', dbKITcontact::field_id, $protocol[dbKITformData::field_kit_id]);
		$where = array(dbKITform::field_id => $protocol[dbKITformData::field_form_id]);
		$form = array();
		if (!$dbKITform->sqlSelectRecord($where, $form)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
			return false;
		}
		if (count($form) < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The ID {{ id }} is invalid!', array('id' => $protocol[dbKITformData::field_form_id]))));
			return false;
		}
		$form = $form[0];
		
		$form_values = array();
		parse_str($protocol[dbKITformData::field_values], $form_values);
		$form_fields = explode(',', $protocol[dbKITformData::field_fields]);
		$items = array();
		foreach ($form_fields as $fid) {
			$where = array(dbKITformFields::field_id => $fid);
			$field = array();
			$value = '';
			if (!$dbKITformFields->sqlSelectRecord($where, $field)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
				return false;
			}
			if (count($field) < 1) {
				// still continue, don't prompt a error ...
				continue;
				// $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
			// sprintf(kit_error_invalid_id, $fid)));
				// return false;
			}
			$field = $field[0];
			switch ($field[dbKITformFields::field_data_type]) :
				case dbKITformFields::data_type_date :
					$value = date(cfg_datetime_str, $form_values[$fid]);
					break;
				case dbKITformFields::data_type_float :
					$value = number_format($form_values[$fid], 2, cfg_decimal_separator, cfg_thousand_separator);
					break;
				case dbKITformFields::data_type_integer :
				case dbKITformFields::data_type_text :
				default :
					$value = (is_array($form_values[$fid])) ? implode(', ', $form_values[$fid]) : $form_values[$fid];
			endswitch
			;
			$items[] = array('label' => $field[dbKITformFields::field_title], 'value' => $value);
		}
		
		$data = array('head' => $this->lang->translate('Protocol Details'), 'intro' => $this->lang->translate('Details of the submitted form'), 'protocol' => $protocol, 'contact' => $contact, 'form' => $form, 'items' => $items);
		return $this->getTemplate('backend.protocol.detail.htt', $data);
	} // dlgProtocolItem()
	
	/**
	 * Exportiert einen kit_form Dialog
	 * 
	 * @global OBJECT $dbKITform
	 * @global OBJECT $dbKITformFields
	 * @global OBJECT $kitContactInterface
	 * @return string dlgFormEdit()
	 */
	protected function exportForm() {
		global $dbKITform;
		global $dbKITformFields;
		global $kitContactInterface;
		
		if (!isset($_REQUEST[self::request_export]) || $_REQUEST[self::request_export] < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Missing the form ID!')));
			return false;
		}
		
		$form_id = $_REQUEST[self::request_export];
		
		$SQL = sprintf("SELECT * FROM %s WHERE %s='%s'", $dbKITform->getTableName(), dbKITform::field_id, $form_id);
		$form = array();
		if (!$dbKITform->sqlExec($SQL, $form)) {
			$this->setError(sprintf('%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
			return false;
		}
		if (count($form) < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The ID {{ id }} is invalid!', array('id' => $form_id))));
			return false;
		}
		$form = $form[0];
		
		$field_array = explode(',', $form[dbKITform::field_fields]);
		$get_fields = array();
		foreach ($field_array as $i) {
			// nur freie Formularfelder uebernehmen (id > 199)
			if ($i > 199)
				$get_fields[] = $i;
		}
		
		$fields = array();
		if (count($get_fields) > 0) {
			$SQL = sprintf("SELECT * FROM %s WHERE %s IN (%s)", $dbKITformFields->getTableName(), dbKITformFields::field_id, implode(',', $get_fields));
			if (!$dbKITformFields->sqlExec($SQL, $fields)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
				return false;
			}
		}
		
		$export = array('version' => $this->getVersion(), 'form' => $form, 'fields' => $fields);
		
		$xfile = http_build_query($export);
		$file = $kitContactInterface->getTempDir() . $form[dbKITform::field_name] . '.kit_form';
		if (false === file_put_contents($file, $xfile)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Error writing the file <b>{{ file }}</b>.')));
			return false;
		}
		$file_url = str_replace(WB_PATH, WB_URL, $file);
		$this->setMessage($this->lang->translate('<p>The form was successfully exported as <b><a href="{{ url }}">{{ name }}</a></b>.</p>', array('url' => $file_url, 'name' => basename($file_url))));
		return true;
	} // exportFormDlg()
	
	/**
	 * Importiert im kitForm Backend einen kit_form Dialog und zeigt den
	 * Dialog anschliessend im dlgFormEdit() Dialog an.
	 * 
	 * @global OBJECT $dbKITform
	 * @global OBJECT $dbKITformFields
	 * @global OBJECT $kitContactInterface
	 * @return BOOL
	 */
	protected function importForm() {
		global $dbKITform;
		global $dbKITformFields;
		global $kitContactInterface;
		
		$upl_file = '';
		if (isset($_FILES[self::request_import_file]) && (is_uploaded_file($_FILES[self::request_import_file]['tmp_name']))) {
			if ($_FILES[self::request_import_file]['error'] == UPLOAD_ERR_OK) {
				$tmp_file = $_FILES[self::request_import_file]['tmp_name'];
				$upl_file = $kitContactInterface->getTempDir() . $_FILES[self::request_import_file]['name'];
				if (!move_uploaded_file($tmp_file, $upl_file)) {
					// error moving file
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Error moving the file <b>{{ file }}</b> to the target directory!', array('file' => $upl_file))));
					return false;
				}
				// Upload erfolgreich
				chmod($upl_file, 0755);
			} else {
				$error = '';
				switch ($_FILES[self::request_import_file]['error']) :
					case UPLOAD_ERR_INI_SIZE :
						$error = $this->lang->translate('The file size exceeds the php.ini directive "upload_max_size" <b>{{ size }}</b>.', array('size' => ini_get('upload_max_filesize')));
						break;
					case UPLOAD_ERR_FORM_SIZE :
						$error = $this->lang->translate('The uploaded file exceeds the directive MAX_FILE_SIZE');
						break;
					case UPLOAD_ERR_PARTIAL :
						$error = $this->lang->translate('The file <b>{{ file }}</b> was uploaded partial', array('file' => $_FILES[self::request_import_file]['name']));
						break;
					default :
						$error = $this->lang->translate('Unspecified error, no description available');
				endswitch
				;
				$this->setError($error);
				return false;
			}
		} else {
			// es wurde keine Datei uebertragen
			$this->setMessage($this->lang->translate('<p>There was no file for import!</p>'));
			return $this->dlgFormEdit();
		}
		
		/**
		 * Eigentlicher Import der Datei
		 */
		$form_rename = (isset($_REQUEST[self::request_import_name])) ? $_REQUEST[self::request_import_name] : '';
		$form_id = -1;
		$message = '';
		if (!$dbKITform->importFormFile($upl_file, $form_rename, $form_id, $message, false)) {
			// Import war nicht Fehlerfrei...
			if ($this->isError()) {
				return false;
			} else {
				$this->setMessage($message);
				return $this->dlgFormEdit();
			}
		} else {
			$this->setMessage($message);
			$_REQUEST[dbKITform::field_id] = $form_id;
			return $this->dlgFormEdit();
		}
	} // importForm()

} // class formBackend

?>