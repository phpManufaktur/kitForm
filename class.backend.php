<?php
/**
 * kitForm
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 */

// prevent this file from being accessed directly
if (!defined('WB_PATH')) die('invalid call of '.$_SERVER['SCRIPT_NAME']);

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/initialize.php');
require_once(WB_PATH.'/framework/functions.php');

global $dbKITform;
global $dbKITformFields;
global $dbKITformTableSort;
global $dbKITformData;

if (!is_object($dbKITform)) 					$dbKITform = new dbKITform();
if (!is_object($dbKITformFields))			$dbKITformFields = new dbKITformFields();
if (!is_object($dbKITformTableSort))	$dbKITformTableSort = new dbKITformTableSort();
if (!is_object($dbKITformData))				$dbKITformData = new dbKITformData();


class formBackend {
	
	const request_action						= 'act'; 
	const request_add_free_field		= 'aff';
	//const request_add_kit_action		= 'aka';
	const request_add_kit_field			= 'akf';
	const request_fields						= 'fld';
	const request_free_field_title	= 'fft';
	const request_protocol_id				= 'pid';
	
	const action_about						= 'abt';
	const action_default					= 'def';
	const action_edit							= 'edt';
	const action_edit_check				= 'edtc';
	const action_list							= 'lst';
	const action_protocol					= 'pro';
	const action_protocol_id			= 'pid';
	
	private $tab_navigation_array = array(
		self::action_list								=> form_tab_list,
		self::action_edit								=> form_tab_edit,
		self::action_protocol						=> form_tab_protocol,
		self::action_about							=> form_tab_about
	);
	
	private $page_link 					= '';
	private $img_url						= '';
	private $template_path			= '';
	private $error							= '';
	private $message						= '';
	
	public function __construct() {
		$this->page_link = ADMIN_URL.'/admintools/tool.php?tool=kit_form';
		$this->template_path = WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/htt/' ;
		$this->img_url = WB_URL. '/modules/'.basename(dirname(__FILE__)).'/images/';
		date_default_timezone_set(form_cfg_time_zone);
	} // __construct()
	
	/**
    * Set $this->error to $error
    * 
    * @param STR $error
    */
  public function setError($error) {
  	$debug = debug_backtrace();
    $caller = next($debug);
  	$this->error = sprintf('[%s::%s - %s] %s', basename($caller['file']), $caller['function'], $caller['line'], $error);
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
  public function clearError() {
  	$this->error = '';
  }

  /** Set $this->message to $message
    * 
    * @param STR $message
    */
  public function setMessage($message) {
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
    $info_text = file(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/info.php');
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
  
  public function getTemplate($template, $template_data) {
  	global $parser;
  	try {
  		$result = $parser->get($this->template_path.$template, $template_data); 
  	} catch (Exception $e) {
  		$this->setError(sprintf(form_error_template_error, $template, $e->getMessage()));
  		return false;
  	}
  	return $result;
  } // getTemplate()
  
  
  /**
   * Verhindert XSS Cross Site Scripting
   * 
   * @param REFERENCE $_REQUEST Array
   * @return $request
   */
	public function xssPrevent(&$request) { 
  	if (is_string($request)) {
	    $request = html_entity_decode($request);
	    $request = strip_tags($request);
	    $request = trim($request);
	    $request = stripslashes($request);
  	}
	  return $request;
  } // xssPrevent()
	
  public function action() {
  	$html_allowed = array();
  	foreach ($_REQUEST as $key => $value) {
  		if (!in_array($key, $html_allowed)) {
  			// special
  			if (strpos($key, 'html_free_') === 0) continue; 
  			$_REQUEST[$key] = $this->xssPrevent($value);	  			
  		} 
  	}
    isset($_REQUEST[self::request_action]) ? $action = $_REQUEST[self::request_action] : $action = self::action_default;
  	switch ($action):
  	case self::action_about:
  		$this->show(self::action_about, $this->dlgAbout());
  		break;
  	case self::action_edit:
  		$this->show(self::action_edit, $this->dlgFormEdit());
  		break;
  	case self::action_edit_check:
  		$this->show(self::action_edit, $this->checkFormEdit());
  		break;
  	case self::action_protocol:
  		$this->show(self::action_protocol, $this->dlgProtocolList());
  		break;
  	case self::action_protocol_id:
  		$this->show(self::action_protocol, $this->dlgProtocolItem());
  		break;
  	case self::action_list:
  	default:
  		$this->show(self::action_list, $this->dlgFormList());
  		break;
  	endswitch;
  } // action
	
  	
  /**
   * Ausgabe des formatierten Ergebnis mit Navigationsleiste
   * 
   * @param $action - aktives Navigationselement
   * @param $content - Inhalt
   * 
   * @return ECHO RESULT
   */
  public function show($action, $content) {
  	$navigation = array();
  	foreach ($this->tab_navigation_array as $key => $value) {
  		$navigation[] = array(
  			'active' 	=> ($key == $action) ? 1 : 0,
  			'url'			=> sprintf('%s&%s=%s', $this->page_link, self::request_action, $key),
  			'text'		=> $value
  		);
  	}
  	$data = array(
  		'WB_URL'			=> WB_URL,
  		'navigation'	=> $navigation,
  		'error'				=> ($this->isError()) ? 1 : 0,
  		'content'			=> ($this->isError()) ? $this->getError() : $content
  	);
  	echo $this->getTemplate('backend.body.htt', $data);
  } // show()
	
  public function checkFormEdit() {
  	global $dbKITform;
  	global $dbKITformFields;
  	global $kitContactInterface;
  	global $dbKITformTableSort;
  	
  	$checked = true;
  	$message = '';
  	
  	$form_id = isset($_REQUEST[dbKITform::field_id]) ? $_REQUEST[dbKITform::field_id] : -1;
  	
  	$form_data = $dbKITform->getFields(); 
  	unset($form_data[dbKITform::field_timestamp]);
  	foreach ($form_data as $field => $value) {
  		switch ($field):
  		case dbKITform::field_id:
  			$form_data[$field] = $form_id;
  			break;
  		case dbKITform::field_name:
  			$form_data[$field] = isset($_REQUEST[$field]) ? $_REQUEST[$field] : '';
  			if (empty($form_data[$field])) {
  				$message .= form_msg_form_name_empty;
  				$checked = false;
  				break;
  			}
  			$name = str_replace(' ', '_', strtolower(media_filename(trim($form_data[$field]))));
  			$SQL = sprintf( "SELECT %s FROM %s WHERE %s='%s' AND %s!='%s'",
  											dbKITform::field_id,
  											$dbKITform->getTableName(),
  											dbKITform::field_name,
  											$name,
  											dbKITform::field_status,
  											dbKITform::status_deleted);
  			$result = array();
  			if (!$dbKITform->sqlExec($SQL, $result)) {
  				$this->setError($dbKITform->getError()); return false;
  			}
  			if (count($result) > 0) {
  				if (($form_id > 0) && ($result[0][dbKITform::field_id] !== $form_id)) {
  					// Formular kann nicht umbenannt werden, der Bezeichner wird bereits verwendet
  					$message .= sprintf(form_msg_form_name_rename_rejected, $name, $result[0][dbKITform::field_id]);
  					unset($_REQUEST[$field]);
  					$checked = false;
  					break;
  				}
  				elseif ($form_id < 1) {
  					// Der Bezeichner wird bereits verwendet
  					$message .= sprintf(form_msg_form_name_rejected, $name, $result[0][dbKITform::field_id]);
  					unset($_REQUEST[$field]);
  					$checked = false;
  					break; 
  				}
  			}
  			$form_data[$field] = $name;
  			break;
  		case dbKITform::field_title:
  			$form_data[$field] = isset($_REQUEST[$field]) ? $_REQUEST[$field] : '';
  			if (empty($form_data[$field]) || (strlen($form_data[$field]) < 6)) {
  				$message .= form_msg_form_title_empty;
  				$checked = false;
  			}
  			break;
  		case dbKITform::field_action: 
  		case dbKITform::field_description:
  		case dbKITform::field_fields:
  		case dbKITform::field_must_fields:
  			$form_data[$field] = isset($_REQUEST[$field]) ? $_REQUEST[$field] : '';
  			break;
  		case dbKITform::field_status:
  			$form_data[$field] = isset($_REQUEST[$field]) ? $_REQUEST[$field] : dbKITform::status_locked;
  			if ($form_data[$field] == dbKITform::status_deleted) {
  				// Formular loeschen
  				$where = array(dbKITform::field_id => $form_id);
  				if (!$dbKITform->sqlDeleteRecord($where)) {
  					$this->setError($dbKITform->getError()); return false;
  				}
  				// Formular Items loeschen
  				$where = array(dbKITformFields::field_form_id => $form_id);
  				if (!$dbKITformFields->sqlDeleteRecord($where)) {
  					$this->setError($dbKITform->getError()); return false;
  				}
  				// es gibt nichts mehr zu tun, zurueck zur Uebersichtsliste
  				$this->setMessage(sprintf(form_msg_form_deleted, $form_id));
  				return $this->dlgFormList();
  			}
  			break;
  		case dbKITform::field_captcha:
  			$form_data[$field] = isset($_REQUEST[$field]) ? $_REQUEST[$field] : dbKITform::captcha_on;
  			break;
  		default:
  			// uebrige Felder ueberspringen
  			break;
  		endswitch;
  	}
  	
  	// Action Links pruefen
  	$links = array();
  	foreach ($dbKITform->action_array as $key => $text) {
  		if (isset($_REQUEST[$key])) $links[$key] = $_REQUEST[$key];
  	}
  	// ... und uebernehmen
  	$form_data[dbKITform::field_links] = http_build_query($links);
  	
  	// pruefen ob ein Feld entfernt werden soll oder ob Felder als Pflichtfelder gesetzt werden sollen
  	$fields = explode(',', $form_data[dbKITform::field_fields]);
  	$must_fields = explode(',', $form_data[dbKITform::field_must_fields]);
  	foreach ($fields as $key => $value) {
  		if ($value < 100) {
  			// KIT Felder
	  		$field_name = array_search($value, $kitContactInterface->index_array);
	  		if (!isset($_REQUEST[$field_name])) {
	  			$message .= sprintf(form_msg_field_removed, $kitContactInterface->field_array[$field_name]);
	  			unset($fields[$key]);
	  		}
	  		if (isset($_REQUEST['must_'.$field_name]) && !in_array($value, $must_fields)) {
	  			$must_fields[] = $value;
	  		}
  		}
  		else {
  			// allgemeine Felder
  			$further_check = true;
  			$where = array(dbKITformFields::field_id => $value);
  			$data = array();
  			if (!$dbKITformFields->sqlSelectRecord($where, $data)) {
  				$this->setError($dbKITformFields->getError()); return false;
  			}
  			if (count($data) < 1) {
  				$this->setError(kit_error_invalid_id, $value); return false;
  			}
  			$data = $data[0];
  			$field_name = $data[dbKITformFields::field_name];
  			$field_id = $data[dbKITformFields::field_id];
  			if (!isset($_REQUEST[$field_name])) {
  				// Feld entfernen
  				$message .= sprintf(form_msg_field_removed, $field_name);
  				unset($fields[$key]);
  				$further_check = false;
  				// Tabelle aktualisieren
  				$where = array(dbKITformFields::field_id => $field_id);
  				if (!$dbKITformFields->sqlDeleteRecord($where)) {
  					$this->setError($dbKITformFields->getError()); return false;
  				}
  			}
  			if (isset($_REQUEST["must_$field_name"]) && !in_array($value, $must_fields)) {
  				$must_fields[] = $value;
  			}  		
  			if ($further_check) {
  				// erweiterte Pruefung der Felder in Abhaenigkeit des Feld Typen
  				switch ($data[dbKITformFields::field_type]):
  				case dbKITformFields::type_text:
  					// Einfache Text Eingabefelder pruefen
  					$field_data = array(
  						dbKITformFields::field_name				=> (isset($_REQUEST['name_'.$field_name])) ? $_REQUEST['name_'.$field_name] : 'free_'.$field_id,
  						dbKITformFields::field_title			=> (isset($_REQUEST['title_'.$field_name])) ? $_REQUEST['title_'.$field_name] : 'title_'.$field_id,
  						dbKITformFields::field_value			=> (isset($_REQUEST['default_'.$field_name])) ? $_REQUEST['default_'.$field_name] : '',
  						dbKITformFields::field_data_type	=> (isset($_REQUEST['data_type_'.$field_name])) ? $_REQUEST['data_type_'.$field_name] : dbKITformFields::data_type_text,
  						dbKITformFields::field_hint				=> (isset($_REQUEST['hint_'.$field_name])) ? $_REQUEST['hint_'.$field_name] : ''	
  					);
  					$where = array(dbKITformFields::field_id => $field_id);
  					if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
  						$this->setError($dbKITformFields->getError()); return false;
  					}
  					break;
  				case dbKITformFields::type_text_area:
  					// textarea pruefen
  					$field_data = array(
  						dbKITformFields::field_name				=> (isset($_REQUEST['name_'.$field_name])) ? $_REQUEST['name_'.$field_name] : 'free_'.$field_id,
  						dbKITformFields::field_title			=> (isset($_REQUEST['title_'.$field_name])) ? $_REQUEST['title_'.$field_name] : 'title_'.$field_id,
  						dbKITformFields::field_value			=> (isset($_REQUEST['default_'.$field_name])) ? $_REQUEST['default_'.$field_name] : '',
  						dbKITformFields::field_data_type	=> dbKITformFields::data_type_text,
  						dbKITformFields::field_hint				=> (isset($_REQUEST['hint_'.$field_name])) ? $_REQUEST['hint_'.$field_name] : ''	
  					);
  					$where = array(dbKITformFields::field_id => $field_id);
  					if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
  						$this->setError($dbKITformFields->getError()); return false;
  					}
  					break;
  				case dbKITformFields::type_checkbox:
  					// CHECKBOX pruefen
  					parse_str($data[dbKITformFields::field_type_add], $cboxes);
  					$checkboxes = array();
  					foreach ($cboxes as $checkbox) {
  						$cb_name = $checkbox['name'];
  						if (!isset($_REQUEST['cb_active_'.$cb_name])) {
  							continue; 
  						}
							if (!empty($_REQUEST['cb_value_'.$cb_name])) $checkbox['value'] = $_REQUEST['cb_value_'.$cb_name];
							if (!empty($_REQUEST['cb_text_'.$cb_name])) $checkbox['text'] = $_REQUEST['cb_text_'.$cb_name];
							$checkbox['checked'] = (isset($_REQUEST['cb_checked_'.$cb_name])) ? 1 : 0;   					
  						$checkboxes[] = $checkbox;
  					}
  					
	  				// neue Checkboxen dazunehmen
	  				if (isset($_REQUEST['cb_active_'.$field_id])) { 
	  					// es soll eine neue Checkbox uebernommen werden
	  					if (isset($_REQUEST['cb_value_'.$field_id]) && !empty($_REQUEST['cb_value_'.$field_id]) && isset($_REQUEST['cb_text_'.$field_id]) && !empty($_REQUEST['cb_text_'.$field_id])) {
	  						// ok - checkbox uebernehmen
	  						$value = str_replace(' ', '_', strtolower(media_filename($_REQUEST['cb_value_'.$field_id])));
	  						$checkboxes[] = array(
	  							'name'		=> $field_id.'_'.$value,
	  							'value'		=> $value,
	  							'text'		=> $_REQUEST['cb_text_'.$field_id],
	  							'checked'	=> isset($_REQUEST['cb_checked_'.$field_id]) ? 1 : 0
	  						);
	  					}
	  					else {
	  						// Definition der Checkbox ist nicht vollstaendig
	  						$message .= form_msg_free_checkbox_invalid;
	  					}
	  				}
	  				// allgemeine Daten der Checkbox pruefen
	  				$field_data = array(
  						dbKITformFields::field_name				=> (isset($_REQUEST['name_'.$field_name])) ? $_REQUEST['name_'.$field_name] : 'free_'.$field_id,
  						dbKITformFields::field_title			=> (isset($_REQUEST['title_'.$field_name])) ? $_REQUEST['title_'.$field_name] : 'title_'.$field_id,
  						dbKITformFields::field_value			=> (isset($_REQUEST['default_'.$field_name])) ? $_REQUEST['default_'.$field_name] : '',
  						dbKITformFields::field_data_type	=> dbKITformFields::data_type_undefined,
  						dbKITformFields::field_hint				=> (isset($_REQUEST['hint_'.$field_name])) ? $_REQUEST['hint_'.$field_name] : '',
  						dbKITformFields::field_type_add		=> http_build_query($checkboxes)	
  					);
  					$where = array(dbKITformFields::field_id => $field_id);
  					if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
  						$this->setError($dbKITformFields->getError()); return false;
  					}
  					break;
  				case dbKITformFields::type_radio:
  					// RADIOBUTTON pruefen
  					parse_str($data[dbKITformFields::field_type_add], $rbuttons);
  					$radios = array();
  					foreach ($rbuttons as $radio) {
  						$rb_name = $radio['name'];
  						if (!isset($_REQUEST['rb_active_'.$rb_name])) continue;
							if (!empty($_REQUEST['rb_value_'.$rb_name])) $radio['value'] = $_REQUEST['rb_value_'.$rb_name];
							if (!empty($_REQUEST['rb_text_'.$rb_name])) $radio['text'] = $_REQUEST['rb_text_'.$rb_name];
							$radio['checked'] = (isset($_REQUEST['rb_checked_'.$field_name]) && ($_REQUEST['rb_checked_'.$field_name] == $radio['value'])) ? 1 : 0;
							$radios[] = $radio;
  					}
  					// neuen Radiobutton dazunehmen
	  				if (isset($_REQUEST['rb_active_'.$field_id])) { 
	  					// es soll eine neuer Radio uebernommen werden
	  					if (isset($_REQUEST['rb_value_'.$field_id]) && !empty($_REQUEST['rb_value_'.$field_id]) && isset($_REQUEST['rb_text_'.$field_id]) && !empty($_REQUEST['rb_text_'.$field_id])) {
	  						// ok - radiobutton uebernehmen
	  						$value = str_replace(' ', '_', strtolower(media_filename($_REQUEST['rb_value_'.$field_id])));
	  						$radios[] = array(
	  							'name'		=> $field_id.'_'.$value,
	  							'value'		=> $value,
	  							'text'		=> $_REQUEST['rb_text_'.$field_id],
	  							'checked'	=> 0
	  						);
	  					}
	  					else {
	  						// Definition der Checkbox ist nicht vollstaendig
	  						$message .= form_msg_free_radio_invalid;
	  					}
	  				}
	  				// allgemeine Daten der Radiobuttons pruefen
	  				$field_data = array(
  						dbKITformFields::field_name				=> (isset($_REQUEST['name_'.$field_name])) ? $_REQUEST['name_'.$field_name] : 'free_'.$field_id,
  						dbKITformFields::field_title			=> (isset($_REQUEST['title_'.$field_name])) ? $_REQUEST['title_'.$field_name] : 'title_'.$field_id,
  						dbKITformFields::field_value			=> (isset($_REQUEST['default_'.$field_name])) ? $_REQUEST['default_'.$field_name] : '',
  						dbKITformFields::field_data_type	=> dbKITformFields::data_type_undefined,
  						dbKITformFields::field_hint				=> (isset($_REQUEST['hint_'.$field_name])) ? $_REQUEST['hint_'.$field_name] : '',
  						dbKITformFields::field_type_add		=> http_build_query($radios)	
  					);
  					$where = array(dbKITformFields::field_id => $field_id);
  					if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
  						$this->setError($dbKITformFields->getError()); return false;
  					}
  					break;
  				case dbKITformFields::type_select:
  					// SELECT Auswahlliste pruefen
  					parse_str($data[dbKITformFields::field_type_add], $sOptions);
  					$options = array();
  					foreach ($sOptions as $option) {
  						$opt_name = $option['name'];
  						if (!isset($_REQUEST['opt_active_'.$opt_name])) continue;
							if (!empty($_REQUEST['opt_value_'.$opt_name])) $option['value'] = $_REQUEST['opt_value_'.$opt_name];
							if (!empty($_REQUEST['opt_text_'.$opt_name])) $option['text'] = $_REQUEST['opt_text_'.$opt_name];
							$option['checked'] = (isset($_REQUEST['opt_checked_'.$field_name]) && ($_REQUEST['opt_checked_'.$field_name] == $option['value'])) ? 1 : 0;
							$options[] = $option;
  					}
  					// neues Auswahlfeld dazunehmen
	  				if (isset($_REQUEST['opt_active_'.$field_id])) { 
	  					// es soll eine neuer OPTION Eintrag uebernommen werden
	  					if (isset($_REQUEST['opt_value_'.$field_id]) && !empty($_REQUEST['opt_value_'.$field_id]) && isset($_REQUEST['opt_text_'.$field_id]) && !empty($_REQUEST['opt_text_'.$field_id])) {
	  						// ok - OPTION uebernehmen
	  						$value = str_replace(' ', '_', strtolower(media_filename($_REQUEST['opt_value_'.$field_id])));
	  						$options[] = array(
	  							'name'		=> $field_id.'_'.$value,
	  							'value'		=> $value,
	  							'text'		=> $_REQUEST['opt_text_'.$field_id],
	  							'checked'	=> 0
	  						);
	  					}
	  					else {
	  						// Definition der Auswahlliste ist nicht vollstaendig
	  						$message .= form_msg_free_select_invalid;
	  					}
	  				}	  				
  					// allgemeine Daten der Auswahlliste pruefen
	  				$field_data = array(
  						dbKITformFields::field_name				=> (isset($_REQUEST['name_'.$field_name])) ? $_REQUEST['name_'.$field_name] : 'free_'.$field_id,
  						dbKITformFields::field_title			=> (isset($_REQUEST['title_'.$field_name])) ? $_REQUEST['title_'.$field_name] : 'title_'.$field_id,
  						dbKITformFields::field_value			=> (isset($_REQUEST['size_'.$field_name])) ? $_REQUEST['size_'.$field_name] : '1',
  						dbKITformFields::field_data_type	=> dbKITformFields::data_type_undefined,
  						dbKITformFields::field_hint				=> (isset($_REQUEST['hint_'.$field_name])) ? $_REQUEST['hint_'.$field_name] : '',
  						dbKITformFields::field_type_add		=> http_build_query($options)	
  					);
  					$where = array(dbKITformFields::field_id => $field_id);
  					if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
  						$this->setError($dbKITformFields->getError()); return false;
  					}
  					break;
  				case dbKITformFields::type_html:
  					// Daten fuer das HTML Feld pruefen
	  				$field_data = array(
  						dbKITformFields::field_name				=> (isset($_REQUEST['name_'.$field_name])) ? $_REQUEST['name_'.$field_name] : 'free_'.$field_id,
  						dbKITformFields::field_title			=> (isset($_REQUEST['title_'.$field_name])) ? $_REQUEST['title_'.$field_name] : 'title_'.$field_id,
  						dbKITformFields::field_value			=> (isset($_REQUEST['html_'.$field_name])) ? $_REQUEST['html_'.$field_name] : '',
  						dbKITformFields::field_data_type	=> dbKITformFields::data_type_text,
  					);
  					$where = array(dbKITformFields::field_id => $field_id);
  					if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
  						$this->setError($dbKITformFields->getError()); return false;
  					}
  					break;
  				case dbKITformFields::type_hidden:
  					// Daten fuer versteckte Felder pruefen
  					$field_data = array(
  						dbKITformFields::field_name				=> (isset($_REQUEST['name_'.$field_name])) ? $_REQUEST['name_'.$field_name] : 'free_'.$field_id,
  						dbKITformFields::field_title			=> (isset($_REQUEST['title_'.$field_name])) ? $_REQUEST['title_'.$field_name] : 'title_'.$field_id,
  						dbKITformFields::field_value			=> (isset($_REQUEST['value_'.$field_name])) ? $_REQUEST['value_'.$field_name] : '',
  						dbKITformFields::field_data_type	=> dbKITformFields::data_type_text,
  					);
  					$where = array(dbKITformFields::field_id => $field_id);
  					if (!$dbKITformFields->sqlUpdateRecord($field_data, $where)) {
  						$this->setError($dbKITformFields->getError()); return false;
  					}
  					break;
  				default:
  					$message .= "<p>Datentyp ".$data[dbKITformFields::field_type]." wird nicht unterstützt!</p>";
  				endswitch;
  			}
  		}
  	}
  	$form_data[dbKITform::field_fields] = implode(',', $fields);
  	
  	// pruefen ob Pflichtfelder zurueckgestuft werden sollen
  	foreach ($must_fields as $key => $value) {
  		if ($value < 100) {
  			// KIT Felder
	  		$field_name = array_search($value, $kitContactInterface->index_array);
	  		if (!isset($_REQUEST['must_'.$field_name])) {
	  			unset($must_fields[$key]);
	  		}
  		}
  		else {
  			// allgemeine Felder
  			$where = array(dbKITformFields::field_id => $value);
  			$data = array();
  			if (!$dbKITformFields->sqlSelectRecord($where, $data)) {
  				$this->setError($dbKITformFields->getError()); return false;
  			}
  			if (count($data) < 1) {
  				$this->setError(kit_error_invalid_id, $value); return false;
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
  				$this->setError($dbKITform->getError()); return false;
  			}
  			$message .= sprintf(form_msg_form_updated, $form_id);
  		}
  		else {
  			// Datensatz einfuegen
  			if (!$dbKITform->sqlInsertRecord($form_data, $form_id)) {
  				$this->setError($dbKITform->getError()); return false;
  			}
  			$message .= sprintf(form_msg_form_inserted, $form_id);
  		}
  		// $_REQUEST's zuruecksetzen
  		foreach ($form_data as $field => $value) {
  			if (isset($_REQUEST[$field])) unset($_REQUEST[$field]);
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
  				$this->setError($dbKITform->getError()); return false;
  			}
  			$message .= sprintf(form_msg_kit_field_add_success, $kitContactInterface->field_array[$new_field]);
  		} 
  		else {
  			// Formular ist noch nicht aktiv
  			$message .= sprintf(form_msg_kit_field_add_form_null, $kitContactInterface->field_array[$new_field]);
  		}
  	}
  	
  	// Allgemeine Felder hinzufuegen
  	if (isset($_REQUEST[self::request_add_free_field]) && ($_REQUEST[self::request_add_free_field] != -1)) {
  		if (isset($_REQUEST[self::request_free_field_title]) && ($_REQUEST[self::request_free_field_title] !== form_label_free_field_title) && !empty($_REQUEST[self::request_free_field_title])) {
  			if ($form_id > 0) {
  				// Formular ist gueltig, neues Datenfeld hinzufuegen
  				$data = array(
  					dbKITformFields::field_type 		=> $_REQUEST[self::request_add_free_field],
  					dbKITformFields::field_title 		=> $_REQUEST[self::request_free_field_title],
  					dbKITformFields::field_form_id 	=> $form_id);
  				$field_id = -1;
  				if (!$dbKITformFields->sqlInsertRecord($data, $field_id)) {
  					$this->setError($dbKITformFields->getError()); return false;
  				}
  				$data = array(dbKITformFields::field_name => "free_$field_id");
  				$where = array(dbKITformFields::field_id => $field_id);
  				if (!$dbKITformFields->sqlUpdateRecord($data, $where)) {
  					$this->setError($dbKITformFields->getError()); return false;
  				}
  				$fields = explode(',', $form_data[dbKITform::field_fields]);
  				$fields[] = $field_id;
  				$where = array(dbKITform::field_id => $form_id);
  				$form_data[dbKITform::field_fields] = implode(',', $fields);
  				$data[dbKITform::field_fields] = $form_data[dbKITform::field_fields];
  				if (!$dbKITform->sqlUpdateRecord($data, $where)) {
  					$this->setError($dbKITform->getError()); return false;
  				}
  				$message .= sprintf(form_msg_free_field_add_success, $_REQUEST[self::request_free_field_title]);
  			}
  			else {
  				// Formular ist noch nicht aktiv
  				$message .= form_msg_free_field_add_form_null;
  			}
  		}
  		else {
  			// Titel fehlt oder ist unguelig
  			$message .= form_msg_free_field_invalid;
  		}
  	}
  	
  	// Sortierung pruefen
  	$where = array(
  		dbKITformTableSort::field_value => $form_id,
  		dbKITformTableSort::field_table => 'mod_kit_form'); 
  	$sorter = array();
  	if (!$dbKITformTableSort->sqlSelectRecord($where, $sorter)) {
  		$this->setError($dbKITformTableSort->getError()); return false;
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
  			$this->setError($dbKITform->getError()); return false;
  		}
  	}

  	$this->setMessage($message);
  	return $this->dlgFormEdit();
  } // checkFormEdit()
  
  public function dlgFormEdit() {
  	global $dbKITform;
  	global $dbKITformFields;
  	global $dbKITformTableSort;
  	global $kitContactInterface;
  	
  	$form_id = isset($_REQUEST[dbKITform::field_id]) ? (int) $_REQUEST[dbKITform::field_id] : -1;
  	
  	$form_data = array();
  	if ($form_id > 0) {
  		// Datensatz auslesen
  		$SQL = sprintf(	"SELECT * FROM %s WHERE %s='%s'", 
  										$dbKITform->getTableName(), 
  										dbKITform::field_id, $form_id);
  		if (!$dbKITform->sqlExec($SQL, $form_data)) {
  			$this->setError($dbKITform->getError()); return false;
  		}
  		if (count($form_data) < 1) {
  			$this->setError(sprintf(kit_error_invalid_id, $form_id)); return false;
  		}
  		$form_data = $form_data[0];
  	}
  	else {
  		// Default Werte setzen
  		$form_data = $dbKITform->getFields();
  		$form_data[dbKITform::field_status] = dbKITform::status_active;
  		$form_data[dbKITform::field_fields] = $kitContactInterface->index_array[kitContactInterface::kit_email];
  		$form_data[dbKITform::field_must_fields] = $kitContactInterface->index_array[kitContactInterface::kit_email];
  		$form_data[dbKITform::field_captcha] = dbKITform::captcha_on;
  		$form_data[dbKITform::field_action] = dbKITform::action_none;
  	}
  	
  	// alle Felder
  	$fields = explode(',', $form_data[dbKITform::field_fields]);
  	// Pflichtfelder
  	$must_fields = explode(',', $form_data[dbKITform::field_must_fields]);
  	// gesperrte Felder
		$disabled_fields = array(kitContactInterface::kit_email);
		
  	// pruefen ob Daten per REQUEST uebergeben wurden
  	foreach ($form_data as $field => $value) {
  		if (!isset($_REQUEST[$field])) continue;
  		$form_data[$field] = $_REQUEST[$field];
  	}
  	
  	$form = array();
  	// in dieser Schleife werden allgemeine Formulardaten gesetzt
  	foreach ($form_data as $field => $value) {
  		switch ($field):
  		// zuerst spezielle Value Felder setzen:
  		case dbKITform::field_id:
  			$form[$field]['value'] = ($form_id > 0) ? sprintf('%03d', $form_data[$field]) : form_text_not_established; 
  			// kein break!!!
  		case dbKITform::field_id:
  		case dbKITform::field_name:
  		case dbKITform::field_title:
  		case dbKITform::field_description:
  			// sonstige Werte setzen
  			$form[$field]['label']  = constant('form_label_'.$field);
  			$form[$field]['name']		= $field;
  			$form[$field]['hint'] 	= constant('form_hint_'.$field);
  			// Value Feld nur setzen, wenn dies noch nicht geschehen ist
  			if (!isset($form[$field]['value'])) $form[$field]['value'] = $form_data[$field];
  		case dbKITform::field_status: 
  			// Status Array zusammenstellen
		  	$form[$field]['items'] = array();
		  	foreach ($dbKITform->status_array as $value => $text) {
		  		if (($form_id < 1) && ($value == dbKITform::status_deleted)) continue; // bei neuen Datensaetzen kein "Loeschen"
		  		$form[$field]['items'][] = array('value' => $value, 'text' => $text);
		  	}		  	
  			$form[$field]['label']  = constant('form_label_'.$field);
  			$form[$field]['name']		= $field;
  			$form[$field]['hint'] 	= constant('form_hint_'.$field);
  			// Value Feld nur setzen, wenn dies noch nicht geschehen ist
  			if (!isset($form[$field]['value'])) $form[$field]['value'] = $form_data[$field];
  			break;
  		case dbKITform::field_captcha:
  			// Captcha Array zusammenstellen
  			$form[$field]['items'] = array();
  			foreach ($dbKITform->captcha_array as $value => $text) {
  				$form[$field]['items'][] = array('value' => $value, 'text' => $text);
  			}
  			$form[$field]['label']  = constant('form_label_'.$field);
  			$form[$field]['name']		= $field;
  			$form[$field]['hint'] 	= constant('form_hint_'.$field);
  			// Value Feld nur setzen, wenn dies noch nicht geschehen ist
  			if (!isset($form[$field]['value'])) $form[$field]['value'] = $form_data[$field];
  			break;
  		case dbKITform::field_action:
  			if ($value == dbKITform::action_login) {
  				// beim Login Dialog muss das Passwort Feld enthalten sein
  				if (!in_array($kitContactInterface->index_array[kitContactInterface::kit_password], $fields)) {
  					$fields[] = $kitContactInterface->index_array[kitContactInterface::kit_password];	
  					$form_data[dbKITform::field_fields] = implode(',', $fields);
  				}
  				if (!in_array($kitContactInterface->index_array[kitContactInterface::kit_password], $must_fields)) {
  					$must_fields[] = $kitContactInterface->index_array[kitContactInterface::kit_password];
  					$form_data[dbKITform::field_must_fields] = implode(',', $must_fields);
  				}
  				if (!in_array(kitContactInterface::kit_password, $disabled_fields)) $disabled_fields[] = kitContactInterface::kit_password; 
  			}
  		default:
  			// nothing to do, skip
  			break;
  		endswitch;
  	}
  	$form_fields = array();
  	foreach ($fields as $field_id) {
  		if ($field_id < 100) {
  			// KIT Datenfeld
  			$field_name = array_search($field_id, $kitContactInterface->index_array);
  			$form_fields[$field_name] = array(
	  			'id'					=> $field_id,
	  			'label'				=> sprintf(form_label_kit_label_marker, $kitContactInterface->field_array[$field_name]),
	  			'name'				=> $field_name,
	  			//'value'				=> 1,
	  			'must'				=> array(	'name'		=> 'must_'.$field_name,
	  															'value'		=> (in_array($field_id, $must_fields)) ? 1 : 0,
	  															'text'		=> form_text_must_field),
	  			'hint'				=> array(	'dialog' 	=> constant('form_hint_'.$field_name)),
	  			'disabled'		=> in_array($field_name, $disabled_fields) ? 1 : 0
	  		);
  		}
  		else {
  			// allgemeines Datenfeld
  			$where = array(dbKITformFields::field_id => $field_id);
  			$data = array();
  			if (!$dbKITformFields->sqlSelectRecord($where, $data)) {
  				$this->setError($dbKITformFields->getError()); return false;
  			}
  			if (count($data) < 1) {
  				$this->setError(sprintf(kit_error_invalid_id, $field_id)); return false;
  			}
  			$data = $data[0];
  			// allgemeine Werte und Einstellungen
  			if (empty($data[dbKITformFields::field_name])) $data[dbKITformFields::field_name] = 'field_'.$field_id;
  			$field_name = $data[dbKITformFields::field_name];
  			// Datentypen Auswahl
  			$data_types = array(); 
  			foreach ($dbKITformFields->data_type_array as $value => $text) {
  				$data_types[] = array(
  					'value'	=> $value,
  					'text'	=> $text
  				);
  			}
  			switch ($data[dbKITformFields::field_type]):
  			case dbKITformFields::type_text:
  				// INPUT TEXT
  				$form_fields[$field_name] = array(
  					'id'				=> $field_id,
  					'label'			=> sprintf(form_label_free_label_marker, $data[dbKITformFields::field_title]),
  					'name'			=> $field_name,
  					'field'			=> array(	'name' 		=> 'name_'.$field_name,
  																'value'		=> $field_name,
  																'label' 	=> form_label_name_label),
  					'must'			=> array( 'name'		=> 'must_'.$field_name,
  																'value'		=> (in_array($field_id, $must_fields)) ? 1 : 0,
  																'text'		=> form_text_must_field),
  					'hint'			=> array( 'dialog'	=> form_hint_free_field_type_text,
  																'name'		=> "hint_$field_name",
  																'value'		=> $data[dbKITformFields::field_hint],
  																'label'		=> form_label_hint_label),
  					'title'			=> array( 'name'		=> "title_$field_name",
  																'value'		=> $data[dbKITformFields::field_title],
  																'label'		=> form_label_title_label),
  					'default'		=> array( 'value'		=> $data[dbKITformFields::field_value],
  																'name'		=> "default_$field_name",
  																'label'		=> form_label_default_label),
  					'type'			=> array(	'type'		=> $data[dbKITformFields::field_type],
  																'name'		=> "type_$field_name",
  																'value'		=> $dbKITformFields->type_array[$data[dbKITformFields::field_type]],
  																'label'		=> form_label_type_label),
  					'data_type'	=> array(	'array'		=> $data_types,
  																'value' 	=> $data[dbKITformFields::field_data_type],				
  																'name'		=> "data_type_$field_name",
  																'label'		=> form_label_data_type_label)
  				);
  				break;
  			case dbKITformFields::type_text_area:
  				// TEXTAREA
  				$form_fields[$field_name] = array(
  					'id'				=> $field_id,
  					'label'			=> sprintf(form_label_free_label_marker, $data[dbKITformFields::field_title]),
  					'name'			=> $field_name,
  					'field'			=> array(	'name' 		=> 'name_'.$field_name,
  																'value'		=> $field_name,
  																'label' 	=> form_label_name_label),
  					'must'			=> array( 'name'		=> 'must_'.$field_name,
  																'value'		=> (in_array($field_id, $must_fields)) ? 1 : 0,
  																'text'		=> form_text_must_field),
  					'hint'			=> array( 'dialog'	=> form_hint_free_field_type_text_area,
  																'name'		=> "hint_$field_name",
  																'value'		=> $data[dbKITformFields::field_hint],
  																'label'		=> form_label_hint_label),
  					'title'			=> array( 'name'		=> "title_$field_name",
  																'value'		=> $data[dbKITformFields::field_title],
  																'label'		=> form_label_title_label),
  					'default'		=> array( 'value'		=> $data[dbKITformFields::field_value],
  																'name'		=> "default_$field_name",
  																'label'		=> form_label_default_label),
  					'type'			=> array(	'type'		=> $data[dbKITformFields::field_type],
  																'name'		=> "type_$field_name",
  																'value'		=> $dbKITformFields->type_array[$data[dbKITformFields::field_type]],
  																'label'		=> form_label_type_label)  					
  					);
  				break;
  			case dbKITformFields::type_checkbox:
  				// CHECKBOX
  				// zusaetzliche Felder auslesen
  				parse_str($data[dbKITformFields::field_type_add], $checkboxes);
  				// Option: neues Feld hinzufuegen 
  				$checkboxes[] = array('name' => $field_id, 'value' => '', 'text' => '', 'checked' => 0);
  				$form_fields[$field_name] = array(
  					'id'				=> $field_id,
  					'label'			=> sprintf(form_label_free_label_marker, $data[dbKITformFields::field_title]),
  					'name'			=> $field_name,
  					'field'			=> array(	'name' 		=> 'name_'.$field_name,
  																'value'		=> $field_name,
  																'label' 	=> form_label_name_label),
  					'must'			=> array( 'name'		=> 'must_'.$field_name,
  																'value'		=> (in_array($field_id, $must_fields)) ? 1 : 0,
  																'text'		=> form_text_must_field),
  					'hint'			=> array( 'dialog'	=> form_hint_free_field_type_checkbox,
  																'name'		=> "hint_$field_name",
  																'value'		=> $data[dbKITformFields::field_hint],
  																'label'		=> form_label_hint_label,
  																'hint_add'=> form_hint_free_checkbox_hint_add,
  																'hint_val'=> form_hint_free_checkbox_hint_val,
  																'hint_txt'=> form_hint_free_checkbox_hint_txt,
  																'hint_sel'=> form_hint_free_checkbox_hint_sel),
  					'title'			=> array( 'name'		=> "title_$field_name",
  																'value'		=> $data[dbKITformFields::field_title],
  																'label'		=> form_label_title_label),
  					'type'			=> array(	'type'		=> $data[dbKITformFields::field_type],
  																'name'		=> "type_$field_name",
  																'value'		=> $dbKITformFields->type_array[$data[dbKITformFields::field_type]],
  																'label'		=> form_label_type_label),
  					'checkbox'	=> $checkboxes	  					
  				);
  				break;
  			case dbKITformFields::type_radio:
  				// RADIOBUTTONS
  				// zusaetzliche Felder auslesen
  				parse_str($data[dbKITformFields::field_type_add], $radios);
  				// Option: neues Feld hinzufuegen 
  				$radios[] = array('name' => $field_id, 'value' => '', 'text' => '', 'checked' => 0);
  				$form_fields[$field_name] = array(
  					'id'				=> $field_id,
  					'label'			=> sprintf(form_label_free_label_marker, $data[dbKITformFields::field_title]),
  					'name'			=> $field_name,
  					'field'			=> array(	'name' 		=> 'name_'.$field_name,
  																'value'		=> $field_name,
  																'label' 	=> form_label_name_label),
  					'must'			=> array( 'name'		=> 'must_'.$field_name,
  																'value'		=> (in_array($field_id, $must_fields)) ? 1 : 0,
  																'text'		=> form_text_must_field),
  					'hint'			=> array( 'dialog'	=> form_hint_free_field_type_radiobutton,
  																'name'		=> "hint_$field_name",
  																'value'		=> $data[dbKITformFields::field_hint],
  																'label'		=> form_label_hint_label,
  																'hint_add'=> form_hint_free_radio_hint_add,
  																'hint_val'=> form_hint_free_radio_hint_val,
  																'hint_txt'=> form_hint_free_radio_hint_txt,
  																'hint_sel'=> form_hint_free_radio_hint_sel),
  					'title'			=> array( 'name'		=> "title_$field_name",
  																'value'		=> $data[dbKITformFields::field_title],
  																'label'		=> form_label_title_label),
  					'type'			=> array(	'type'		=> $data[dbKITformFields::field_type],
  																'name'		=> "type_$field_name",
  																'value'		=> $dbKITformFields->type_array[$data[dbKITformFields::field_type]],
  																'label'		=> form_label_type_label),
  					'radios'		=> $radios	  					
  				);
  				break;
  			case dbKITformFields::type_select:
  				// SELECT Auswahl
  				// zusaetzliche Felder auslesen
  				parse_str($data[dbKITformFields::field_type_add], $options);
  				// Option: neues Feld hinzufuegen 
  				$options[] = array('name' => $field_id, 'value' => '', 'text' => '', 'checked' => 0);
  				$form_fields[$field_name] = array(
  					'id'				=> $field_id,
  					'label'			=> sprintf(form_label_free_label_marker, $data[dbKITformFields::field_title]),
  					'name'			=> $field_name,
  					'field'			=> array(	'name' 		=> 'name_'.$field_name,
  																'value'		=> $field_name,
  																'label' 	=> form_label_name_label),
  					'must'			=> array( 'name'		=> 'must_'.$field_name,
  																'value'		=> (in_array($field_id, $must_fields)) ? 1 : 0,
  																'text'		=> form_text_must_field),
  					'hint'			=> array( 'dialog'	=> form_hint_free_field_type_select,
  																'name'		=> "hint_$field_name",
  																'value'		=> $data[dbKITformFields::field_hint],
  																'label'		=> form_label_hint_label,
  																'hint_add'=> form_hint_free_select_hint_add,
  																'hint_val'=> form_hint_free_select_hint_val,
  																'hint_txt'=> form_hint_free_select_hint_txt,
  																'hint_sel'=> form_hint_free_select_hint_sel),
  					'title'			=> array( 'name'		=> "title_$field_name",
  																'value'		=> $data[dbKITformFields::field_title],
  																'label'		=> form_label_title_label),
  					'type'			=> array(	'type'		=> $data[dbKITformFields::field_type],
  																'name'		=> "type_$field_name",
  																'value'		=> $dbKITformFields->type_array[$data[dbKITformFields::field_type]],
  																'label'		=> form_label_type_label),
  					'size'			=> array(	'name'		=> "size_$field_name",
  																'value'		=> $data[dbKITformFields::field_value],
  																'label'		=> form_label_size_label),
  					'options'		=> $options	  					
  				);
  				break;
  			case dbKITformFields::type_html:
  				$form_fields[$field_name] = array(
  					'id'				=> $field_id,
  					'label'			=> sprintf(form_label_free_label_marker, $data[dbKITformFields::field_title]),
  					'name'			=> $field_name,
  					//'value'			=> $data[dbKITformFields::field_value],
  					'must'			=> array( 'name'		=> 'must_'.$field_name,
  																'value'		=> (in_array($field_id, $must_fields)) ? 1 : 0,
  																'text'		=> form_text_must_field),
  					'title'			=> array( 'name'		=> "title_$field_name",
  																'value'		=> $data[dbKITformFields::field_title],
  																'label'		=> form_label_title_label),
  					'hint'			=> array( 'dialog'	=> form_hint_free_field_type_html),
  					'type'			=> array(	'type'		=> $data[dbKITformFields::field_type],
  																'name'		=> "type_$field_name",
  																'value'		=> $dbKITformFields->type_array[$data[dbKITformFields::field_type]],
  																'label'		=> form_label_type_label),
  					'html'			=> array(	'name'		=> "html_$field_name",
  																'value'		=> $data[dbKITformFields::field_value],
  																'label'		=> form_label_html_label)
  				);
  				break;
  			case dbKITformFields::type_hidden:
  				// HIDDEN Feld
  				$form_fields[$field_name] = array(
  					'id'				=> $field_id,
  					'label'			=> sprintf(form_label_free_label_marker, $data[dbKITformFields::field_title]),
  					'name'			=> $field_name,
  					'field'			=> array(	'name' 		=> 'name_'.$field_name,
  																'value'		=> $field_name,
  																'label' 	=> form_label_name_label),
  					'must'			=> array( 'name'		=> 'must_'.$field_name,
  																'value'		=> (in_array($field_id, $must_fields)) ? 1 : 0,
  																'text'		=> form_text_must_field),
  					'hint'			=> array( 'dialog'	=> form_hint_free_field_type_hidden),
  					'title'			=> array( 'name'		=> "title_$field_name",
  																'value'		=> $data[dbKITformFields::field_title],
  																'label'		=> form_label_title_label),
  					'value'			=> array( 'value'		=> $data[dbKITformFields::field_value],
  																'name'		=> "value_$field_name",
  																'label'		=> form_label_value_label),
  					'type'			=> array(	'type'		=> $data[dbKITformFields::field_type],
  																'name'		=> "type_$field_name",
  																'value'		=> $dbKITformFields->type_array[$data[dbKITformFields::field_type]],
  																'label'		=> form_label_type_label)
  				);
  				break;
  			default:
  				$this->setError(sprintf(form_error_field_type_not_implemented, $data[dbKITformFields::field_type]));
  				return false;
  			endswitch;
  		}
  	}
  	
  	// KIT LINKS hinzufuegen
  	parse_str($form_data[dbKITform::field_links], $links);
  	$form['kit_link'] = array();
  	foreach ($dbKITform->action_array as $name => $text) {
  		$SQL = sprintf(	"SELECT * FROM %s WHERE %s='%s' AND %s='%s'",
  										$dbKITform->getTableName(),
  										dbKITform::field_status,
  										dbKITform::status_active,
  										dbKITform::field_action,
  										$name);
  		$link_forms = array();
  		if (!$dbKITform->sqlExec($SQL, $link_forms)) {
  			$this->setError($dbKITform->getError()); return false;
  		}
  		$value = array();
  		$value[] = array(
  			'value'			=> dbKITform::action_none,
  			'text'			=> form_text_no_link_assigned,
  			'selected'	=> (isset($links[$name]) && ($links[$name] == dbKITform::action_none)) ? 1 : 0 
  		);
  		foreach ($link_forms as $link) {
  			$value[] = array(
  				'value'			=> $link[dbKITform::field_name],
  				'text'			=> $link[dbKITform::field_name],
  				'selected'	=> (isset($links[$name]) && ($links[$name] == $link[dbKITform::field_name])) ? 1 : 0
  			);
  		}
  		$form['kit_link'][] = array(
  			'label'			=> sprintf(form_label_kit_link, $text),
  			'name'			=> $name,
  			'hint'			=> sprintf(form_hint_kit_link_add, $text),
  			'value'			=> $value
  		);
  	}
  	
  	// neue KIT Aktion hinzufuegen
  	$form['kit_action']['label'] = form_label_kit_action_add;
  	$form['kit_action']['name'] = dbKITform::field_action; //self::request_add_kit_action;
  	$form['kit_action']['hint'] = form_hint_kit_action_add;
  	$form['kit_action']['value'] = array();
  	$form['kit_action']['value'][] = array(
  		'value'	=> -1,
  		'text'	=> form_text_select_kit_action
  	);
  	$field_array = $dbKITform->action_array;
  	asort($field_array);
  	foreach ($field_array as $value => $text) {
  		$form['kit_action']['value'][] = array(
  			'value'			=> $value,
  			'text'			=> $text,
  			'selected'	=> ($value == $form_data[dbKITform::field_action]) ? 1 : 0
  		);
  	}
  	

  	// neues KIT Feld hinzufuegen
  	$form['kit_field']['label'] = form_label_kit_field_add;
  	$form['kit_field']['name'] = self::request_add_kit_field;
  	$form['kit_field']['hint'] = form_hint_kit_field_add;
  	$form['kit_field']['value'] = array();
  	$form['kit_field']['value'][] = array(
  		'value' => -1,
  		'text'	=> form_text_select_kit_field
  	);
  	$field_array = $kitContactInterface->field_array;
  	asort($field_array);
  	foreach ($field_array as $field => $text) {
  		if (in_array($kitContactInterface->index_array[$field], $fields)) continue;
			$form['kit_field']['value'][] = array(
				'value'	=> $field,
				'text'	=> $text
			);  		
  	}
  	
  	// Allgemeine Felder hinzufuegen
  	$form['free_field']['label'] = form_label_free_field_add;
  	$form['free_field']['name'] = self::request_add_free_field;
  	$form['free_field']['hint'] = form_hint_free_field_add;
  	$form['free_field']['value'] = array(); 
  	$form['free_field']['value'][] = array(
  		'value'	=> -1,
  		'text'	=> form_text_select_free_field
  	);
  	$field_array = $dbKITformFields->type_array;
  	asort($field_array);
  	foreach ($field_array as $field => $text) {
  		$form['free_field']['value'][] = array(
  			'value'	=> $field,
  			'text'	=> $text
  		);
  	}
  	$form['free_field']['title']['label'] = form_label_free_field_title;
  	$form['free_field']['title']['name'] = self::request_free_field_title;
  	$form['free_field']['title']['value'] = form_label_free_field_title;
  	
  	$sorter_table = 'mod_kit_form';
  	$sorter_active = 0;
  	if ($form_id > 0) {
  		$SQL = sprintf( "SELECT * FROM %s WHERE %s='%s' AND %s='%s'",
  										$dbKITformTableSort->getTableName(),
  										dbKITformTableSort::field_table,
  										$sorter_table,
  										dbKITformTableSort::field_value,
  										$form_id);
  		$sorter = array();
  		if (!$dbKITformTableSort->sqlExec($SQL, $sorter)) {
  			$this->setError($dbKITformTableSort->getError()); return false;
  		} 
  		if (count($sorter) < 1) {
  			$data = array(
  				dbKITformTableSort::field_table => $sorter_table,
  				dbKITformTableSort::field_value => $form_id,
  				dbKITformTableSort::field_order => ''
  			);
  			if (!$dbKITformTableSort->sqlInsertRecord($data)) {
  				$this->setError($dbKITformTableSort->getError()); return false;
  			}
  		}
  		$sorter_active = 1;
  	}
  	// Dialog ausgeben
  	$data = array(
  		'form_action'				=> $this->page_link,
  		'action_name'				=> self::request_action,
  		'action_value'			=> self::action_edit_check,
  		'form_name'					=> dbKITform::field_id,
  		'form_value'				=> $form_id, 
  		'header'						=> form_header_edit_form,
  		'is_intro'					=> ($this->isMessage()) ? 0 : 1,
  		'intro'							=> ($this->isMessage()) ? $this->getMessage() : form_intro_edit_form,
  		'btn_ok'						=> form_btn_ok,
  		'btn_abort'					=> form_btn_abort,
  		'abort_location'		=> $this->page_link,
  		'form'							=> $form,
  		'fields'						=> $form_fields,
  		'kit_fields_intro'	=> form_intro_kit_fields,
  		'sorter_table'			=> $sorter_table,
  		'sorter_active'			=> $sorter_active,
  		'sorter_value'			=> $form_id,
  		'fields_name'				=> dbKITform::field_fields,
  		'fields_value'			=> $form_data[dbKITform::field_fields],
  		'must_fields_name'	=> dbKITform::field_must_fields,
  		'must_fields_value'	=> $form_data[dbKITform::field_must_fields]
  	);
  	return $this->getTemplate('backend.form.edit.htt', $data);
  } // dlgFormEdit()
  
  public function dlgFormList() {
  	global $dbKITform;
  	
  	$SQL = sprintf( "SELECT * FROM %s WHERE %s!='%s' ORDER BY %s DESC",
										$dbKITform->getTableName(),
										dbKITform::field_status,
										dbKITform::status_deleted,
										dbKITform::field_timestamp);
		$orms = array();
		if (!$dbKITform->sqlExec($SQL, $forms)) {
			$this->setError($dbKITform->getError()); return false;
		}
		
		$list = array();
		foreach ($forms as $form) {
			$list[] = array(
				'id'				=> $form[dbKITform::field_id],
				'link'			=> sprintf(	'%s&%s=%s&%s=%s', 
																$this->page_link, 
																self::request_action, self::action_edit,
																dbKITform::field_id, $form[dbKITform::field_id]),
				'name'			=> $form[dbKITform::field_name],
				'status'		=> $dbKITform->status_array[$form[dbKITform::field_status]],
				'title'			=> $form[dbKITform::field_title],
				'timestamp'	=> date(form_cfg_datetime_str, strtotime($form[dbKITform::field_timestamp]))										
			);
		}
		$data = array(
			'head'					=> form_header_form_list,
			'is_message'		=> $this->isMessage() ? 1 : 0,
			'intro'					=> $this->isMessage() ? $this->getMessage() : form_intro_form_list,
			'header'				=> array(	'id'				=> form_th_id,
																'name'			=> form_th_name,
																'status'		=> form_th_status,
																'title'			=> form_th_title,
																'timestamp'	=> form_th_timestamp),
			'forms'					=> $list
		);
  	return $this->getTemplate('backend.form.list.htt', $data);
  } // dlgFormList()
  
  public function dlgAbout() {
  	$data = array(
  		'version'					=> sprintf('%01.2f', $this->getVersion()),
  		'img_url'					=> $this->img_url.'/kit_form_logo_400_267.jpg',
  		'release_notes'		=> file_get_contents(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/info.txt'),
  	);
  	return $this->getTemplate('backend.about.htt', $data);
  } // dlgAbout()
  
	public function dlgProtocolList() {
		global $dbKITform;
		global $dbKITformData;
		global $kitContactInterface;
		
		$SQL = sprintf( "SELECT * FROM %s ORDER BY %s DESC LIMIT 100",
										$dbKITformData->getTableName(),
										dbKITformData::field_date);
		$items = array();
		if (!$dbKITformData->sqlExec($SQL, $items)) {
			$this->setError($dbKITformData->getError()); return false;
		}
		$list = array();		
		foreach ($items as $item) {
			if (!$kitContactInterface->getContact($item[dbKITformData::field_kit_id], $contact)) {
				$this->setError($kitContactInterface->getError()); return false;
			}
			$contact['link'] = sprintf(	'%s&%s=%s',
																	ADMIN_URL.'/admintools/tool.php?tool=kit&act=con',
																	dbKITcontact::field_id,
																	$item[dbKITformData::field_kit_id]);
			$where = array(dbKITform::field_id => $item[dbKITformData::field_form_id]);
			$form = array();
			if (!$dbKITform->sqlSelectRecord($where, $form)) {
				$this->setError($dbKITform->getError()); return false;
			}
			if (count($form) < 1) {
				$this->setError(sprintf(kit_error_invalid_id, $item[dbKITformData::field_form_id])); return false;
			}
			$form = $form[0];
			$form['id'] = $item[dbKITformData::field_id];
			$form['link'] = sprintf('%s&%s=%s&%s=%s', $this->page_link, self::request_action, self::action_protocol_id, self::request_protocol_id, $item[dbKITformData::field_id]);
			$form['datetime'] = date(form_cfg_datetime_str, strtotime($item[dbKITformData::field_date]));
			$list[] = array(
				'contact'		=> $contact,
				'form'			=> $form
			);
		} // foreach
		
		$data = array(
			'head'			=> form_header_protocol_list,
			'intro'			=> form_intro_protocol_list,
			'header'		=> array(	'id'				=> form_th_id,
														'form_name'	=> form_th_form_name,
														'datetime'	=> form_th_datetime,
														'contact'		=> form_th_contact,
														'email'			=> form_th_email),
			'list'			=> $list
		);
		return $this->getTemplate('backend.protocol.list.htt', $data); 
	} // dlgProtocolList()
  
	public function dlgProtocolItem() {
		global $dbKITform;
		global $dbKITformData;
		global $dbKITformFields;
		global $kitContactInterface;
		
		$protocol_id = (isset($_REQUEST[self::request_protocol_id])) ? $_REQUEST[self::request_protocol_id] : -1;
		
		$SQL = sprintf( "SELECT * FROM %s WHERE %s='%s'",
										$dbKITformData->getTableName(),
										dbKITformData::field_id,
										$protocol_id);
		$protocol = array();
		if (!$dbKITformData->sqlExec($SQL, $protocol)) {
			$this->setError($dbKITformData->getError()); return false;
		}
		if (count($protocol) < 1) {
			$this->setError(sprintf(kit_error_invalid_id, $protocol_id));
			return false;
		}
		
		$protocol = $protocol[0];
		$protocol['datetime'] = date(form_cfg_datetime_str, strtotime($protocol[dbKITformData::field_date]));
		
		if (!$kitContactInterface->getContact($protocol[dbKITformData::field_kit_id], $contact)) {
			$this->setError($kitContactInterface->getError()); return false;
		}
		$contact['id'] = $protocol[dbKITformData::field_kit_id];
		$contact['link'] = sprintf(	'%s&%s=%s',
																ADMIN_URL.'/admintools/tool.php?tool=kit&act=con',
																dbKITcontact::field_id,
																$protocol[dbKITformData::field_kit_id]);
		$where = array(dbKITform::field_id => $protocol[dbKITformData::field_form_id]);
		$form = array();
		if (!$dbKITform->sqlSelectRecord($where, $form)) {
			$this->setError($dbKITform->getError()); return false;
		}
		if (count($form) < 1) {
			$this->setError(sprintf(kit_error_invalid_id, $protocol[dbKITformData::field_form_id])); return false;
		}
		$form = $form[0];

		parse_str($protocol[dbKITformData::field_values], $form_values);
		$form_fields = explode(',', $protocol[dbKITformData::field_fields]);
		$items = array();
		foreach ($form_fields as $fid) {
			$where = array(dbKITformFields::field_id => $fid);
			$field = array();
			if (!$dbKITformFields->sqlSelectRecord($where, $field)) {
				$this->setError($dbKITformFields->getError()); return false;
			}
			if (count($field) < 1) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(kit_error_invalid_id, $fid))); return false;
			}
			$field = $field[0];
			switch ($field[dbKITformFields::field_data_type]):
			case dbKITformFields::data_type_date:
				$value = date(form_cfg_datetime_str, $form_values[$fid]);
				break;
			case dbKITformFields::data_type_float:
				$value = number_format($form_values[$fid], 2, form_cfg_decimal_separator, form_cfg_thousand_separator);
				break;
			case dbKITformFields::data_type_integer:
			case dbKITformFields::data_type_text:
			default:
				$value = (is_array($form_values[$fid])) ? implode(', ', $form_values[$fid]) : $form_values[$fid];
			endswitch;
			$items[] = array(
				'label'		=> $field[dbKITformFields::field_title],
				'value'		=> $value
			);
		}
		
		$data = array(
			'head'			=> form_header_protocol_detail,
			'intro'			=> form_intro_protocol_detail,
			'protocol'	=> $protocol,
			'contact'		=> $contact,
			'form'			=> $form,
			'items'			=> $items
		);
		return $this->getTemplate('backend.protocol.detail.htt', $data);
	} // dlgProtocolItem()
	
} // class formBackend

?>