<?php

/**
 * kitForm
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 * 
 * FOR VERSION- AND RELEASE NOTES PLEASE LOOK AT INFO.TXT!
 */

// try to include LEPTON class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {	
	if (defined('LEPTON_VERSION')) include(WB_PATH.'/framework/class.secure.php');
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'].'/framework/class.secure.php')) {
	include($_SERVER['DOCUMENT_ROOT'].'/framework/class.secure.php'); 
} else {
	$subs = explode('/', dirname($_SERVER['SCRIPT_NAME']));	$dir = $_SERVER['DOCUMENT_ROOT'];
	$inc = false;
	foreach ($subs as $sub) {
		if (empty($sub)) continue; $dir .= '/'.$sub;
		if (file_exists($dir.'/framework/class.secure.php')) { 
			include($dir.'/framework/class.secure.php'); $inc = true;	break; 
		} 
	}
	if (!$inc) trigger_error(sprintf("[ <b>%s</b> ] Can't include LEPTON class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
}
// end include LEPTON class.secure.php

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/initialize.php');
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.backend.php');
require_once(WB_PATH.'/include/captcha/captcha.php');
require_once(WB_PATH.'/framework/class.wb.php');
require_once(WB_PATH.'/modules/kit/class.mail.php');
require_once(WB_PATH.'/modules/droplets_extension/interface.php');

global $dbKITform;
global $dbKITformFields;
global $dbKITformTableSort;
global $dbKITformData;

if (!is_object($dbKITform)) 					$dbKITform = new dbKITform();
if (!is_object($dbKITformFields))			$dbKITformFields = new dbKITformFields();
if (!is_object($dbKITformData))				$dbKITformData = new dbKITformData();


class formFrontend {
	
	const request_action						= 'act';
	const request_link							= 'link';
	const request_key								= 'key';
	const request_activation_type		= 'at';
	
	const action_default						= 'def';
	const action_check_form					= 'acf';
	const action_activation_key			= 'key';
	
	const activation_type_newsletter	= 'nl';
	const activation_type_account			= 'acc';
	const activation_type_default			= 'def';
	
	private $page_link 					= '';
	private $img_url						= '';
	private $template_path			= '';
	private $error							= '';
	private $message						= '';
	private $contact						= array();
	
	const param_preset					= 'fpreset';
	const param_form						= 'form';
	const param_return					= 'return';
	const param_css							= 'css';
	
	private $params = array(
		self::param_preset			=> 1, 
		self::param_form				=> '',	
		self::param_return			=> false,
		self::param_css					=> true
	);
	
	public function __construct() {
		global $kitLibrary;
		$url = '';
		$_SESSION['FRONTEND'] = true;	
		$kitLibrary->getPageLinkByPageID(PAGE_ID, $url);
		$this->page_link = $url; 
		$this->template_path = WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/htt/' ;
		$this->img_url = WB_URL. '/modules/'.basename(dirname(__FILE__)).'/images/';
		date_default_timezone_set(form_cfg_time_zone);
	} // __construct()
	
	public function getParams() {
		return $this->params;
	} // getParams()
	
	public function setParams($params = array()) {
		$this->params = $params;
		$this->template_path = WB_PATH.'/modules/kit_form/htt/'.$this->params[self::param_preset].'/'.KIT_FORM_LANGUAGE.'/';
		if (!file_exists($this->template_path)) {
			$this->setError(sprintf(form_error_preset_not_exists, '/modules/kit_form/htt/'.$this->params[self::param_preset].'/'.KIT_FORM_LANGUAGE.'/'));
			return false;
		}
		return true;
	} // setParams()
	
	/**
    * Set $this->error to $error
    * 
    * @param STR $error
    */
  public function setError($error) {
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

  public function setContact($contact) {
  	$this->contact = $contact;
  } // setContact();
  
  
  public function getContact() {
  	return $this->contact;
  } // getContact()
  
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
  	if ($this->isError()) return sprintf('<div class="error">%s</div>', $this->getError());
  	$html_allowed = array();
  	foreach ($_REQUEST as $key => $value) {
  		if (!in_array($key, $html_allowed)) {
  			$_REQUEST[$key] = $this->xssPrevent($value);	  			
  		} 
  	} 
  	
  	isset($_REQUEST[self::request_action]) ? $action = $_REQUEST[self::request_action] : $action = self::action_default;
  	
  	// CSS laden? 
    if ($this->params[self::param_css]) { 
			if (!is_registered_droplet_css('kit_form', PAGE_ID)) { 
	  		register_droplet_css('kit_form', PAGE_ID, 'kit_form', 'kit_form.css');
			}
    }
    elseif (is_registered_droplet_css('kit_form', PAGE_ID)) {
		  unregister_droplet_css('kit_form', PAGE_ID);
    }
    
  	switch ($action):
  	case self::action_check_form: 
  		$result = $this->checkForm();
  		break;
  	case self::action_activation_key:
  		$result = $this->checkActivationKey();
  		break;
  	case self::action_default:
  	default: 
  		$result = $this->showForm();
  		break;
  	endswitch;

  	if ($this->isError()) $result = sprintf('<div class="error">%s</div>', $this->getError());
		return $result;
  } // action
	
  public function showForm() { 
  	global $dbKITform;
  	global $dbKITformFields;
  	global $kitContactInterface;
  	
  	if (empty($this->params)) {
  		$this->setError(form_error_form_name_empty); return false;
  	}
  	
  	$form_id = -1;
  	$form_name = 'none';
  	
  	if (isset($_REQUEST[self::request_link])) {
  		$form_name = $_REQUEST[self::request_link];	
  	}
  	elseif (isset($_REQUEST[dbKITform::field_id])) {
  		$form_id = $_REQUEST[dbKITform::field_id];
  	}
  	else {
  	 	$form_name = $this->params[self::param_form];
  	}
  	if ($form_id > 0) {
  		$SQL = sprintf( "SELECT * FROM %s WHERE %s='%s'",
  										$dbKITform->getTableName(),
  										dbKITform::field_id,
  										$form_id);
  	}
  	else {
	  	$SQL = sprintf(	"SELECT * FROM %s WHERE %s='%s' AND %s='%s'",
	  									$dbKITform->getTableName(),
	  									dbKITform::field_name,
	  									$form_name,
	  									dbKITform::field_status,
	  									dbKITform::status_active);
  	}
  	$fdata = array();
  	if (!$dbKITform->sqlExec($SQL, $fdata)) {
  		$this->setError($dbKITform->getError()); return false;
  	}
  	if (count($fdata) < 1) {
  		$this->setError(sprintf(form_error_form_name_invalid, $form_name)); return false;
  	}
  	$fdata = $fdata[0];
  	
  	if ($fdata[dbKITform::field_action] == dbKITform::action_logout) {
  		// Sonderfall: beim LOGOUT wird direkt der Bestaetigungsdialog angezeigt
  		if ($kitContactInterface->isAuthenticated()) {
  			// Abmelden und Verabschieden...
  			return $this->Logout();
  		}
  		else {
  			// Benutzer ist nicht angemeldet...
  			$data = array(
  				'message' => form_msg_not_authenticated
  			);
  			return $this->getTemplate('prompt.htt', $data);  				
  		}
  	}
  	elseif ($fdata[dbKITform::field_action] == dbKITform::action_account) { 
  		// Das Benutzerkonto zum Bearbeiten anzeigen
  		if ($kitContactInterface->isAuthenticated()) {
  			// ok - User ist angemeldet
  			if (!$kitContactInterface->getContact($_SESSION[kitContactInterface::session_kit_contact_id], $contact)) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
  				return false;
  			}
  			foreach ($contact as $key => $value) {
  				if (!isset($_REQUEST[$key])) $_REQUEST[$key] = $value;
  			}
  		}
  		else {
  			// Dialog kann nicht angezeigt werden, Benutzer ist nicht angemeldet!
  			$data = array(
  				'message' => form_msg_not_authenticated
  			);
  			return $this->getTemplate('prompt.htt', $data);
  		}
  	}
  	// CAPTCHA
 		ob_start();
			call_captcha();
			$call_captcha = ob_get_contents();
		ob_end_clean();
		
		// Links auslesen
		parse_str($fdata[dbKITform::field_links], $links);
		$links['command'] = sprintf('%s%s%s', $this->page_link, (strpos($this->page_link, '?') === false) ? '?' : '&' ,self::request_link);
		// Formulardaten
  	$form_data = array(
  		'name'			=> 'kit_form',
  		'action'		=> array(	'link'		=> $this->page_link,
  													'name'		=> self::request_action,
  													'value'		=> self::action_check_form),
  		'id'				=> array(	'name'		=> dbKITform::field_id,
  													'value'		=> $fdata[dbKitform::field_id]),
  		'response'	=> ($this->isMessage()) ? $this->getMessage() : NULL,
  		'btn'				=> array(	'ok'			=> form_btn_ok,
  													'abort'		=> form_btn_abort),
  		'title'			=> $fdata[dbKITform::field_title],
  		'captcha'		=> array(	'active'	=> ($fdata[dbKITform::field_captcha] == dbKITform::captcha_on) ? 1 : 0,
  													'code'		=> $call_captcha),
  		'kit_action'=> array(	'name'		=> dbKITform::field_action,
  													'value'		=> $fdata[dbKITform::field_action]),
  		'links'			=> $links
  	);
		
  	// Felder auslesen und Array aufbauen
  	$fields_array = explode(',', $fdata[dbKITform::field_fields]);
  	$must_array = explode(',', $fdata[dbKITform::field_must_fields]);
  	$form_fields = array();
  	foreach ($fields_array as $field_id) {
  		if ($field_id < 100) {
  			// IDs 1-99 sind fuer KIT reserviert
  			if (false === ($field_name = array_search($field_id, $kitContactInterface->index_array))) {
  				// $field_id nicht gefunden
  				$this->setError(sprintf(form_error_kit_field_id_invalid, $field_id)); return false;
  			}
  			switch ($field_name):
  			case kitContactInterface::kit_title:
  			case kitContactInterface::kit_title_academic:
  				// Anrede und akademische Titel
  				if ($field_name == kitContactInterface::kit_title) {
  					$kitContactInterface->getFormPersonTitleArray($title_array);
  				} else {
  					$kitContactInterface->getFormPersonTitleAcademicArray($title_array);
  				}
  				if (isset($_REQUEST[$field_name])) { 
  					$selected = $_REQUEST[$field_name]; 
  					$new_array = array();
  					foreach ($title_array as $title) {
  						$title['checked'] = ($title['value'] == $selected) ? 1 : 0;
  						$new_array[] = $title;
  					}
  					$title_array = $new_array;
  				}
  				$form_fields[$field_name] = array(
  					'id'				=> $field_id,
  					'type'			=> $field_name,
  					'name'			=> $field_name,
  					'value'			=> '',
  					'must'			=> (in_array($field_id, $must_array)) ? 1 : 0,
	  				'label'			=> $kitContactInterface->field_array[$field_name],
  					'hint'			=> constant('form_hint_'.$field_name),	
  					'titles'		=> $title_array	
  				);
  				break;
  			case kitContactInterface::kit_address_type:
  				// Adresstyp auswaehlen
  				$kitContactInterface->getFormAddressTypeArray($address_type_array);
  				if (isset($_REQUEST[$field_name])) {
  					$selected = $_REQUEST[$field_name];
  					$new_array = array();
  					foreach ($address_type_array as $address_type) {
  						$address_type['checked'] = ($address_type['value'] == $selected) ? 1 : 0;
  						$new_array[] = $address_type;
  					}
  					$address_type_array = $new_array;
  				}
  				$form_fields[$field_name] = array(
  					'id'				=> $field_id,
  					'type'			=> $field_name,
  					'name'			=> $field_name,
  					'value'			=> 1,
  					'must'			=> (in_array($field_id, $must_array)) ? 1 : 0,
	  				'label'			=> $kitContactInterface->field_array[$field_name],
  					'hint'			=> constant('form_hint_'.$field_name),	
  					'address_types'	=> $address_type_array	
  				);  				
  				break;
  			case kitContactInterface::kit_first_name:
  			case kitContactInterface::kit_last_name:
  			case kitContactInterface::kit_company:
  			case kitContactInterface::kit_department:
  			case kitContactInterface::kit_fax:
  			case kitContactInterface::kit_phone:
  			case kitContactInterface::kit_phone_mobile:
  			case kitContactInterface::kit_street:
  			case kitContactInterface::kit_city:
  			case kitContactInterface::kit_zip:
  			case kitContactInterface::kit_email:
  			case kitContactInterface::kit_password:
  			case kitContactInterface::kit_password_retype:
  				$form_fields[$field_name] = array(
  					'id'				=> $field_id,
  					'type'			=> $field_name,
  					'name'			=> $field_name,
  					'value'			=> (isset($_REQUEST[$field_name])) ? $_REQUEST[$field_name] : '',
  					'must'			=> (in_array($field_id, $must_array)) ? 1 : 0,
	  				'label'			=> $kitContactInterface->field_array[$field_name],
  					'hint'			=> constant('form_hint_'.$field_name),	
  				);
  				break;
  			case kitContactInterface::kit_zip_city:
  				// Auswahl fuer Postleitzahl und Stadt
  				$form_fields[$field_name] = array(
  					'id'					=> $field_id,
  					'type'				=> $field_name,
  					'name_zip'		=> kitContactInterface::kit_zip,
  					'value_zip'		=> (isset($_REQUEST[kitContactInterface::kit_zip])) ? $_REQUEST[kitContactInterface::kit_zip] : '',
  					'name_city'		=> kitContactInterface::kit_city,
  					'value_city'	=> (isset($_REQUEST[kitContactInterface::kit_city])) ? $_REQUEST[kitContactInterface::kit_city] : '',
  					'must'			=> (in_array($field_id, $must_array)) ? 1 : 0,
	  				'label'			=> $kitContactInterface->field_array[$field_name],
  					'hint'			=> constant('form_hint_'.$field_name),	
  				);
  				break;
  			case kitContactInterface::kit_newsletter: 
  				$kitContactInterface->getFormNewsletterArray($newsletter_array);
  				if (isset($_REQUEST[$field_name])) { 
  					$select_array = (is_array($_REQUEST[$field_name])) ? $_REQUEST[$field_name] : explode(',', $_REQUEST[$field_name]);
  					//$select_array = $_REQUEST[$field_name]; 
  					$new_array = array();
  					foreach ($newsletter_array as $newsletter) {
  						$newsletter['checked'] = (in_array($newsletter['value'], $select_array)) ? 1 : 0; 
  						$new_array[] = $newsletter;
  					}
  					$newsletter_array = $new_array; 
  				}
  				$form_fields[$field_name] = array(
  					'id'					=> $field_id,
  					'type'				=> $field_name,
  					'name'				=> $field_name,
  					'value'				=> '',
  					'must'				=> (in_array($field_id, $must_array)) ? 1 : 0,
	  				'label'				=> $kitContactInterface->field_array[$field_name],
  					'hint'				=> constant('form_hint_'.$field_name),	
  					'newsletters'	=> $newsletter_array	
  				);
  				break;
  			default: 
  				// Datentyp nicht definiert - Fehler ausgeben
  				$this->setError(sprintf(form_error_data_type_invalid, $field_key));
  				return false;
  			endswitch;
  		}
  		else {
  			// ab 100 sind allgemeine Felder
	  		$where = array(dbKITformFields::field_id => $field_id);
	  		$field = array();
	  		if (!$dbKITformFields->sqlSelectRecord($where, $field)) {
	  			$this->setError($dbKITformFields->getError()); return false;
	  		}
	  		if (count($field) < 1) {
	  			$this->setError(sprintf(kit_error_invalid_id, $field_id)); return false;
	  		}
	  		$field = $field[0];
	  		switch ($field[dbKITformFields::field_type]):
	  		case dbKITformFields::type_checkbox:
	  			// CHECKBOX
	  			parse_str($field[dbKITformFields::field_type_add], $checkboxes);
	  			if (isset($_REQUEST[$field[dbKITformFields::field_name]])) {
	  				$checked_array = $_REQUEST[$field[dbKITformFields::field_name]];
	  				$checked_boxes = array();
		  			foreach ($checkboxes as $checkbox) {
		  				$checkbox['checked'] = (in_array($checkbox['value'], $checked_array)) ? 1 : 0;
		  				$checked_boxes[] = $checkbox;
		  			}
		  			$checkboxes = $checked_boxes;
	  			}
	  			$form_fields[$field[dbKITformFields::field_name]] = array(
	  				'id'				=> $field[dbKITformFields::field_id],
	  				'type'			=> $field[dbKITformFields::field_type],
	  				'name'			=> $field[dbKITformFields::field_name],
	  				'hint'			=> $field[dbKITformFields::field_hint],
	  				'label'			=> $field[dbKITformFields::field_title],
	  				'must'			=> (in_array($field_id, $must_array)) ? 1 : 0,
	  				'value'			=> $field[dbKITformFields::field_value],
	  				'checkbox'	=> $checkboxes
	  			);
	  			break;
	  		case dbKITformFields::type_hidden:
	  			$form_fields[$field[dbKITformFields::field_name]] = array(
	  				'id'				=> $field[dbKITformFields::field_id],
	  				'type'			=> $field[dbKITformFields::field_type],
	  				'name'			=> $field[dbKITformFields::field_name],
	  				'value'			=> $field[dbKITformFields::field_value]
	  			);
	  			break;
	  		case dbKITformFields::type_html:
	  			$form_fields[$field[dbKITformFields::field_name]] = array(
	  				'id'				=> $field[dbKITformFields::field_id],
	  				'type'			=> $field[dbKITformFields::field_type],
	  				'value'			=> $field[dbKITformFields::field_value]
	  			);
	  			break;
	  		case dbKITformFields::type_radio:
	  			parse_str($field[dbKITformFields::field_type_add], $radios);
	  			if (isset($_REQUEST[$field[dbKITformFields::field_name]])) {
	  				$checked = $_REQUEST[$field[dbKITformFields::field_name]];
	  				$checked_radios = array();
	  				foreach ($radios as $radio) {
	  					$radio['checked'] = ($radio['value'] == $checked) ? 1 : 0;
	  					$checked_radios[] = $radio;
	  				}
	  				$radios = $checked_radios;
	  			}
	  			$form_fields[$field[dbKITformFields::field_name]] = array(
	  				'id'				=> $field[dbKITformFields::field_id],
	  				'type'			=> $field[dbKITformFields::field_type],
	  				'name'			=> $field[dbKITformFields::field_name],
	  				'hint'			=> $field[dbKITformFields::field_hint],
	  				'label'			=> $field[dbKITformFields::field_title],
	  				'must'			=> (in_array($field_id, $must_array)) ? 1 : 0,
	  				'value'			=> $field[dbKITformFields::field_value],
	  				'radio'			=> $radios
	  			);
	  			break;
	  		case dbKITformFields::type_select:
	  			parse_str($field[dbKITformFields::field_type_add], $options);
	  			if (isset($_REQUEST[$field[dbKITformFields::field_name]])) {
	  				$checked = $_REQUEST[$field[dbKITformFields::field_name]];
	  				$checked_options = array();
	  				foreach ($options as $option) {
	  					$option['checked'] = ($option['value'] == $checked) ? 1 : 0;
	  					$checked_options[] = $option;
	  				}
	  				$options = $checked_options;
	  			}
	  			$form_fields[$field[dbKITformFields::field_name]] = array(
	  				'id'				=> $field[dbKITformFields::field_id],
	  				'type'			=> $field[dbKITformFields::field_type],
	  				'name'			=> $field[dbKITformFields::field_name],
	  				'hint'			=> $field[dbKITformFields::field_hint],
	  				'label'			=> $field[dbKITformFields::field_title],
	  				'must'			=> (in_array($field_id, $must_array)) ? 1 : 0,
	  				'value'			=> $field[dbKITformFields::field_value],
	  				'option'		=> $options
	  			);
	  			break;
	  		case dbKITformFields::type_text_area:	
	  		case dbKITformFields::type_text: 
	  			$form_fields[$field[dbKITformFields::field_name]] = array(
	  				'id'				=> $field[dbKITformFields::field_id],
	  				'type'			=> $field[dbKITformFields::field_type],
	  				'name'			=> $field[dbKITformFields::field_name],
	  				'hint'			=> $field[dbKITformFields::field_hint],
	  				'label'			=> $field[dbKITformFields::field_title],
	  				'must'			=> (in_array($field_id, $must_array)) ? 1 : 0,
	  				'value'			=> isset($_REQUEST[$field[dbKITformFields::field_name]]) ? $_REQUEST[$field[dbKITformFields::field_name]] : $field[dbKITformFields::field_value]
	  			);
	  			break;
	  		default: continue;
	  			$this->setError(sprintf(form_error_data_type_invalid, $field[dbKITformFields::field_type]), false); return false;
	  		endswitch;
  		}
  	}
  	
  	$data = array(
  		'form'		=> $form_data,
  		'fields'	=> $form_fields,
  	);
  	return $this->getTemplate('form.htt', $data);
  }	// showForm()
  
  /**
   * Ueberprueft das Formular, zeigt das Formular bei Fehlern erneut an.
   * Wenn alles in Ordnung ist, werden die Daten gesichert und 
   * Benachrichtigungs E-Mails versendet.
   * @return STR FORMULAR oder ERFOLGSMELDUNG
   */
  public function checkForm() {
		global $dbKITform;
		global $dbKITformFields;
		global $kitContactInterface;
		global $kitLibrary;
		global $dbKITformData;
		global $dbContact;
		
		if (!isset($_REQUEST[dbKITform::field_id])) { 
			$this->setError(form_error_form_id_missing); return false;
		}
		$form_id = $_REQUEST[dbKITform::field_id];
		$where = array(dbKITform::field_id => $form_id);
		$form = array();
		if (!$dbKITform->sqlSelectRecord($where, $form)) {
			$this->setError($dbKITform->getError()); return false;
		}
		if (count($form) < 1) {
			$this->setError(sprintf(kit_error_invalid_id, $form_id)); return false;
		}
		$form = $form[0];
		
		// pruefen, ob eine Aktion ausgefuehrt werden soll
		switch ($form[dbKITform::field_action]):
  	case dbKITform::action_login:
  		return $this->checkLogin($form);
  	case dbKITform::action_logout:
  		return $this->Logout($form);
  	case dbKITform::action_send_password:
  		return $this->sendNewPassword($form);
  	case dbKITform::action_newsletter:
  		return $this->subscribeNewsletter($form);
  	case dbKITform::action_register:
  	case dbKITform::action_account:
  		/*
  		 * Diese speziellen Aktionen werden erst durchgefuehrt, 
  		 * wenn die allgemeinen Daten bereits geprueft sind
  		 */
  	default:
  		// nothing to do - go ahead...
		endswitch;
		
		$message = '';
		$checked = true;
		// CAPTCHA pruefen?
		if ($form[dbKITform::field_captcha] == dbKITform::captcha_on) {
			unset($_SESSION['kf_captcha']);
			if (!isset($_REQUEST['captcha']) || ($_REQUEST['captcha'] != $_SESSION['captcha'])) {
				$message .= form_msg_captcha_invalid;
				$checked = false;
			}			
		}
		
		// zuerst die Pflichtfelder pruefen
		$must_array = explode(',', $form[dbKITform::field_must_fields]);
		foreach ($must_array as $must_id) {
			if ($must_id < 100) {
  			// IDs 1-99 sind fuer KIT reserviert
  			if (false === ($field_name = array_search($must_id, $kitContactInterface->index_array))) {
  				// $field_id nicht gefunden
  				$this->setError(sprintf(form_error_kit_field_id_invalid, $must_id)); return false;
  			}
  			if (!isset($_REQUEST[$field_name]) || empty($_REQUEST[$field_name])) {
  				// Feld muss gesetzt sein
  				$message .= sprintf(form_msg_must_field_missing, $kitContactInterface->field_array[$field_name]);
  				$checked = false;
  			}
  			elseif (($field_name == kitContactInterface::kit_email) && !$kitLibrary->validateEMail($_REQUEST[kitContactInterface::kit_email])) {
  				// E-Mail Adresse pruefen
  				$message .= sprintf(kit_msg_email_invalid, $_REQUEST[kitContactInterface::kit_email]);
  				$checked = false;
  			}
			}
			else {
				// freie Datenfelder
				$where = array(dbKITformFields::field_id => $must_id);
	  		$field = array();
	  		if (!$dbKITformFields->sqlSelectRecord($where, $field)) {
	  			$this->setError($dbKITformFields->getError()); return false;
	  		}
	  		if (count($field) < 1) {
	  			$this->setError(sprintf(kit_error_invalid_id, $must_id)); return false;
	  		}
	  		$field = $field[0];
	  		$field_name = $field[dbKITformFields::field_name];
	  		if (!isset($_REQUEST[$field_name]) || empty($_REQUEST[$field_name])) {
	  			// Feld muss gesetzt sein
  				$message .= sprintf(form_msg_must_field_missing, $field[dbKITformFields::field_title]);
  				$checked = false;
	  		}
	  		else {
	  			// erweiterte Pruefung
	  			switch ($field[dbKITformFields::field_data_type]):
	  			case dbKITformFields::data_type_date:
	  				if (false === ($timestamp = strtotime($_REQUEST[$field_name]))) {
	  					$message .= sprintf(form_msg_date_invalid, $_REQUEST[$field_name]);
	  					$checked = false;
	  				}
	  				break;
	  			default:
	  				// alle anderen Datentypen ohne Pruefung...
	  			endswitch;
	  		}
			}
		} // foreach
		
		if ($checked) { 
			// Daten sind ok und koennen uebernommen werden 
			$password_changed = false;
			$password = '';
			$contact_array = array();
			$field_array = $kitContactInterface->field_array;
			$field_array[kitContactInterface::kit_intern] = ''; // Feld fuer internen Verteiler hinzufuegen 
			foreach ($field_array as $key => $value) {
				switch ($key):
				case kitContactInterface::kit_zip_city:
					// nothing to do...
					break;
				case kitContactInterface::kit_newsletter:
					if (isset($_REQUEST[$key])) {
						if (is_array($_REQUEST[$key])) {
							$contact_array[$key] = implode(',', $_REQUEST[$key]);
						}
						else {
							$contact_array[$key] = $_REQUEST[$key];
						}
					}
					break;
				case kitContactInterface::kit_password:
					// kit_password wird ignoriert
					break;
				case kitContactInterface::kit_password_retype:
					if ((isset($_REQUEST[$key]) && !empty($_REQUEST[$key])) &&
							(isset($_REQUEST[kitContactInterface::kit_password]) && !empty($_REQUEST[kitContactInterface::kit_password]))) {
						// nur pruefen, wenn beide Passwortfelder gesetzt sind
						if (!$kitContactInterface->changePassword($_SESSION[kitContactInterface::session_kit_aid], 
																											$_SESSION[kitContactInterface::session_kit_contact_id], 
																											$_REQUEST[kitContactInterface::kit_password], 
																											$_REQUEST[kitContactInterface::kit_password_retype])) {
							// Fehler beim Aendern des Passwortes
							unset($_REQUEST[kitContactInterface::kit_password]);
							unset($_REQUEST[kitContactInterface::kit_password_retype]);
							if ($kitContactInterface->isError()) {
								$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
								return false;
							}							
							$message .= $kitContactInterface->getMessage();
							break;
						}
						else {
							// Passwort wurde geaendert
							$password_changed = true;
							$password = $_REQUEST[kitContactInterface::kit_password];
							unset($_REQUEST[kitContactInterface::kit_password]);
							unset($_REQUEST[kitContactInterface::kit_password_retype]);
							$message .= $kitContactInterface->getMessage();
							break;
						}
					}
					break;
				default:
					if (isset($_REQUEST[$key])) $contact_array[$key] = $_REQUEST[$key];
					break;
				endswitch;
			}
			
			if ($form[dbKITform::field_action] == dbKITform::action_register) {
				// es handelt sich um einen Registrierdialog, die weitere Bearbeitung an 
				// $this->registerAccount() uebergeben
				return $this->registerAccount($form, $contact_array);	
			}
			elseif ($form[dbKITform::field_action] == dbKITform::action_account) { 
				// Es wird das Benutzerkonto bearbeitet
				if (!$kitContactInterface->updateContact($_SESSION[kitContactInterface::session_kit_contact_id], $contact_array)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
					return false;
				}
				if ($password_changed) {
					// Passwort wurde geaendert, E-Mail Bestaetigung versenden 
					$data = array(
						'contact'		=> $contact_array,
						'password'	=> $password
					);
					$client_mail = $this->getTemplate('mail.client.password.htt', $data);
					$mail = new kitMail();
					if (!$mail->mail(form_mail_subject_client_access, $client_mail, SERVER_EMAIL, SERVER_EMAIL, array($contact_array[kitContactInterface::kit_email] => $contact_array[kitContactInterface::kit_email]), false)) {
						$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(form_error_sending_email, $contact_array[kitContactInterface::kit_email])));
						return false;
					}
			
				}
				// Mitteilung, dass das Benutzerkonto aktualisiert wurde
				if (empty($message)) $message = form_msg_account_updated;
				$this->setMessage($message);
				return $this->showForm();
			}
			
			if ($kitContactInterface->isEMailRegistered($_REQUEST[kitContactInterface::kit_email], $contact_id, $status)) { 
				// E-Mail Adresse existiert bereits, Datensatz ggf. aktualisieren
				if (!$kitContactInterface->updateContact($contact_id, $contact_array)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
					return false;
				}				
			}
			elseif ($kitContactInterface->isError()) {
				// Fehler bei der Datenbankabfrage
				$this->setError($kitContactInterface->getError()); 
				return false;
			}
			else {
				// E-Mail Adresse ist noch nicht registriert
				if (!$kitContactInterface->addContact($contact_array, $contact_id)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
					return false;
				}				
			}
			
			// Kontakt Datensatz ist erstellt oder aktualisiert, allgemeine Daten uebernehmen und E-Mails versenden
			$fields = array();
			$values = array();
			$fields_array = explode(',', $form[dbKITform::field_fields]);
			foreach ($fields_array as $fid) {
				if ($fid > 99) $fields[] = $fid;
			}	
			foreach ($fields as $fid) {
				$where = array(dbKITformFields::field_id => $fid);
				$field = array();
				if (!$dbKITformFields->sqlSelectRecord($where, $field)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
					return false;
				}
				if (count($field) < 1) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
					return false;
				}
				$field = $field[0];
				switch ($field[dbKITformFields::field_data_type]):
				case dbKITformFields::data_type_date:
					$values[$fid] = (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? date('Y-m-d H:i:s', strtotime($_REQUEST[$field[dbKITformFields::field_name]])) : '0000-00-00 00:00:00';
					break;
				case dbKITformFields::data_type_float:
					$values[$fid] = (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? $kitLibrary->str2float($_REQUEST[$field[dbKITformFields::field_name]], form_cfg_thousand_separator, form_cfg_decimal_separator) : 0;
					break;
				case dbKITformFields::data_type_integer:
					$values[$fid] = (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? $kitLibrary->str2int($_REQUEST[$field[dbKITformFields::field_name]], form_cfg_thousand_separator, form_cfg_decimal_separator) : 0;
					break;
				default:
					$values[$fid] = (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? $_REQUEST[$field[dbKITformFields::field_name]] : '';
					break;
				endswitch;
			}
			$form_data = array(
				dbKITformData::field_form_id			=> $form_id,
				dbKITformData::field_kit_id				=> $contact_id,
				dbKITformData::field_date					=> date('Y-m-d H:i:s'),
				dbKITformData::field_fields				=> implode(',', $fields),
				dbKITformData::field_values				=> http_build_query($values)
			);
			if (!$dbKITformData->sqlInsertRecord($form_data, $data_id)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
				return false;
			}
			// ok - Daten sind gesichert, vorab LOG schreiben
			$dbContact->addSystemNotice($contact_id, sprintf(form_protocol_form_send, 
																	sprintf('%s&%s=%s&%s=%s',
																	ADMIN_URL.'/admintools/tool.php?tool=kit_form',
																	formBackend::request_action,
																	formBackend::action_protocol_id,
																	formBackend::request_protocol_id,
																	$data_id))); 
			
			if (!$kitContactInterface->getContact($contact_id, $contact)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
				return false;
			}
			
			if ($this->params[self::param_return] == true) {
				// direkt zum aufrufenden Programm zurueckkehren
				$result = array(
					'contact'		=> $contact,
					'result'		=> true
				);
				return $result;
			}
			
			$items = array();
			foreach ($fields as $fid) {
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
					$value = date(form_cfg_datetime_str, $values[$fid]);
					break;
				case dbKITformFields::data_type_float:
					$value = number_format($values[$fid], 2, form_cfg_decimal_separator, form_cfg_thousand_separator);
					break;
				case dbKITformFields::data_type_integer:
				case dbKITformFields::data_type_text:
				default:
					$value = (is_array($values[$fid])) ? implode(', ', $values[$fid]) : $values[$fid];
				endswitch;
				$items[$field[dbKITformFields::field_name]] = array(
					'label'		=> $field[dbKITformFields::field_title],
					'value'		=> $value
				);
			}

			// E-Mail Versand vorbereiten
			$provider_data = array();
			if (!$kitContactInterface->getServiceProviderByID($form[dbKITform::field_provider_id], $provider_data)) {
				if ($kitContactInterface->isError()) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
				}
				else {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage()));
				}
				return false;
			}
			$provider_email = $provider_data['email'];
			$provider_name  = $provider_data['name'];
			
			$form_d = $form_data;
			$form_d['datetime'] = date(form_cfg_datetime_str, strtotime($form_d[dbKITformData::field_date]));
			
			$data = array(
				'form'		=> $form_d,
				'contact'	=> $contact,
				'items'		=> $items
			);
			$client_mail = $this->getTemplate('mail.client.htt', $data);
			
			// E-Mail an den Absender des Formulars
			$mail = new kitMail($form[dbKITform::field_provider_id]);
			if (!$mail->mail(	form_mail_subject_client, 
												$client_mail, $provider_data['email'], 
												$provider_data['name'], 
												array($contact[kitContactInterface::kit_email] => $contact[kitContactInterface::kit_email]), 
												($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false)) {
				$err = $mail->getMailError();
				if (empty($err)) $err = sprintf(form_error_sending_email, $contact[kitContactInterface::kit_email]);
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $err));
				return false;
			}
			// E-Mail an den Betreiber der Website
			$provider_mail = $this->getTemplate('mail.provider.htt', $data);
			$cc_array = array();
			$ccs = explode(',', $form[dbKITform::field_email_cc]);
			foreach ($ccs as $cc) $cc_array[$cc] = $cc;
			$mail = new kitMail($form[dbKITform::field_provider_id]);
			if (!$mail->mail(	form_mail_subject_provider, 
												$provider_mail, 
												$contact[kitContactInterface::kit_email], 
												$contact[kitContactInterface::kit_email], 
												array($provider_data['email'] => $provider_data['name']), 
												($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false,
												$cc_array)) {
				$err = $mail->getMailError();
				if (empty($err)) $err = sprintf(form_error_sending_email, $contact[kitContactInterface::kit_email]);
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $err));
				return false;
			}
			return $this->getTemplate('confirm.htt', $data);
		} // checked
		
		if ($checked  == false) {
			if (isset($_REQUEST[kitContactInterface::kit_password])) unset($_REQUEST[kitContactInterface::kit_password]);
			if (isset($_REQUEST[kitContactInterface::kit_password_retype])) unset($_REQUEST[kitContactInterface::kit_password_retype]);			
		}
		
		$this->setMessage($message);
		return $this->showForm();
  } // checkForm()
  
  /**
   * Prueft den LOGIN und schaltet den User ggf. frei
   * 
   * @return BOOL true on success BOOL false on program error STR dialog on invalid login
   */
  public function checkLogin($form_data=array()) {
  	global $kitContactInterface;
  	global $kitLibrary;
  	
  	if (!isset($_REQUEST[kitContactInterface::kit_email]) || !isset($_REQUEST[kitContactInterface::kit_password])) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, form_error_email_password_required)); 
  		return false;
  	}
  	if (!$kitLibrary->validateEMail($_REQUEST[kitContactInterface::kit_email])) {
  		unset($_REQUEST[kitContactInterface::kit_password]);
  		$this->setMessage(sprintf(kit_msg_email_invalid, $_REQUEST[kitContactInterface::kit_email]));
  		return $this->showForm();
  	}
  	if ($kitContactInterface->checkLogin($_REQUEST[kitContactInterface::kit_email], $_REQUEST[kitContactInterface::kit_password], $contact, $must_change_password)) {
  		// Login erfolgreich
  		$this->setContact($contact);
  		return true;
  	}
  	elseif ($kitContactInterface->isError()) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
  		return false;
  	}
  	else {
  		// Fehler beim Login...
  		unset($_REQUEST[kitContactInterface::kit_password]);
  		$this->setMessage($kitContactInterface->getMessage());
  		return $this->showForm();
  	}
  } // checkLogin()
  
  /**
   * Sendet dem User ein neues Passwort zu
   * 
   * @param ARRAY $form_data
   * @return BOOL false on program error STR dialog/message on success
   */
  public function sendNewPassword($form_data=array()) {
  	global $kitContactInterface;
  	
  	if (!isset($_REQUEST[kitContactInterface::kit_email])) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(form_error_field_required, kitContactInterface::kit_email)));
  		return false;
  	}
  	if (!$kitContactInterface->isEMailRegistered($_REQUEST[kitContactInterface::kit_email], $contact_id, $status)) {
  		// E-Mail Adresse ist nicht registriert
  		$this->setMessage(sprintf(form_msg_email_not_registered, $_REQUEST[kitContactInterface::kit_email]));
  		return $this->showForm();
  	}
  	if ($status != dbKITcontact::status_active) {
  		// Der Kontakt ist NICHT AKTIV!
  		$this->setMessage(sprintf(form_msg_contact_not_active, $_REQUEST[kitContactInterface::kit_email]));
  		return $this->showForm();
  	}
  	// CAPTCHA pruefen?
		if ($form_data[dbKITform::field_captcha] == dbKITform::captcha_on) {
			unset($_SESSION['kf_captcha']);
			if (!isset($_REQUEST['captcha']) || ($_REQUEST['captcha'] != $_SESSION['captcha'])) {
				$this->setMessage(form_msg_captcha_invalid);
				return $this->showForm();
			}			
		}
		
  	// neues Passwort anfordern
  	if (!$kitContactInterface->generateNewPassword($_REQUEST[kitContactInterface::kit_email], $newPassword)) {
  		if ($kitContactInterface->isError()) {
  			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
  			return false;
  		}
  		$this->setMessage($kitContactInterface->getMessage());
  		return $this->showForm();
  	}
  	if (!$kitContactInterface->getContact($contact_id, $contact)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
  		return false;
  	}
  	
  	$data = array(
  		'contact'		=> $contact,
  		'password'	=> $newPassword
  	);
  	
  	$client_mail = $this->getTemplate('mail.client.password.htt', $data);
			
		$mail = new kitMail();
		if (!$mail->mail(form_mail_subject_client_access, $client_mail, SERVER_EMAIL, SERVER_EMAIL, array($contact[kitContactInterface::kit_email] => $contact[kitContactInterface::kit_email]), false)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(form_error_sending_email, $contact[kitContactInterface::kit_email])));
			return false;
		}
		
		return $this->getTemplate('confirm.password.htt', $data);			
  } // sendNewPassword()
  
  public function registerAccount($form_data=array(), $contact_data=array()) {
  	global $kitContactInterface;
  	
  	if ($kitContactInterface->isEMailRegistered($contact_data[kitContactInterface::kit_email], $contact_id, $status)) {
  		// diese E-Mail Adresse ist bereits registriert
  		if ($status == dbKITcontact::status_active) {
  			// Kontakt ist aktiv
	  		$this->setMessage(sprintf(form_msg_contact_already_registered, $contact_data[kitContactInterface::kit_email]));
	  		return $this->showForm();
  		}
  		else {
  			// Kontakt ist gesperrt
  			$this->setMessage(sprintf(form_msg_contact_locked, $contact_data[kitContactInterface::kit_email]));
  			return $this->showForm();
  		}
  	}
  	elseif ($kitContactInterface->isError()) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
  		return false;
  	}
  	
  	// alles ok - neuen Datensatz anlegen
  	if (!$kitContactInterface->addContact($contact_data, $contact_id, $register_data)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
  		return false;
  	}
  	$form_data['datetime'] = date(form_cfg_datetime_str);
		$form_data['activation_link'] = sprintf('%s%s%s=%s&%s=%s', $this->page_link, (strpos($this->page_link, '?') === false) ? '?' : '&', self::request_action, self::action_activation_key, self::request_key, $register_data[dbKITregister::field_register_key]);	
		
  	// Benachrichtigungen versenden
  	$data = array(
  		'contact'		=> $contact_data,
  		'form'			=> $form_data
  	);
  	$client_mail = $this->getTemplate('mail.client.register.htt', $data);
			
		$mail = new kitMail();
		if (!$mail->mail(form_mail_subject_client_register, $client_mail, SERVER_EMAIL, SERVER_EMAIL, array($contact_data[kitContactInterface::kit_email] => $contact_data[kitContactInterface::kit_email]), false)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(form_error_sending_email, $contact_data[kitContactInterface::kit_email])));
			return false;
		}
		
		$provider_mail = $this->getTemplate('mail.provider.register.htt', $data);
		$mail = new kitMail();
		if (!$mail->mail(form_mail_subject_provider_register, $provider_mail, $contact_data[kitContactInterface::kit_email], $contact_data[kitContactInterface::kit_email], array(SERVER_EMAIL => SERVER_EMAIL), false)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(form_error_sending_email, SERVER_EMAIL)));
			return false;
		}
		
		return $this->getTemplate('confirm.register.htt', $data); 
  } // registerAccount()
  
  /**
   * Aktivierungskey ueberpruefen, Datensatz freischalten und Benutzer einloggen...
   * @return STR Dialog 
   */
  public function checkActivationKey() {
  	global $kitContactInterface;
  	
  	if (!isset($_REQUEST[self::request_key])) {
  		$this->setError(sprintf(form_error_field_required, self::request_key));
  		return false;
  	}
  	
  	if (!$kitContactInterface->checkActivationKey($_REQUEST[self::request_key], $register, $contact, $password)) {
  		if ($this->isError()) {
  			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
  			return false;
  		}
  		$this->setMessage($kitContactInterface->getMessage());  
  		return $this->showForm();
  	}
  	// Benutzer anmelden
  	$_SESSION[kitContactInterface::session_kit_aid] = $register[dbKITregister::field_id];
		$_SESSION[kitContactInterface::session_kit_key] = $register[dbKITregister::field_register_key];
		$_SESSION[kitContactInterface::session_kit_contact_id] = $register[dbKITregister::field_contact_id];
		// Passwort pruefen
		if ($password == -1) {
			// Benutzer war bereits freigeschaltet und das Konto ist aktiv
			$this->setMessage(form_msg_welcome);
			return $this->showForm(); 
		}
  	$data = array(
  		'contact'		=> $contact,
  		'password'	=> $password
  	);
  	
  	$activation_type = (isset($_REQUEST[self::request_activation_type])) ? $_REQUEST[self::request_activation_type] : self::activation_type_account;
  	
  	switch($activation_type):
  	case self::activation_type_newsletter:
  		$mail_template	= 'mail.client.activation.newsletter.htt';
  		$prompt_template = 'confirm.activation.newsletter.htt';
  		break;
  	case self::activation_type_account:
  	default:
  		$mail_template	= 'mail.client.activation.account.htt';
  		$prompt_template = 'confirm.activation.account.htt';
  		break;
  	endswitch;
  	
  	$client_mail = $this->getTemplate($mail_template, $data);
			
		$mail = new kitMail();
		if (!$mail->mail(form_mail_subject_client_access, $client_mail, SERVER_EMAIL, SERVER_EMAIL, array($contact[kitContactInterface::kit_email] => $contact[kitContactInterface::kit_email]), false)) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(form_error_sending_email, $contact[kitContactInterface::kit_email])));
			return false;
		}
		
		return $this->getTemplate($prompt_template, $data);
  } // checkActivationKey()
  
  /**
   * Logout 
   * @return STR Dialog
   */
  public function Logout() {
  	global $kitContactInterface;
  	
  	if (!$kitContactInterface->getContact($_SESSION[kitContactInterface::session_kit_contact_id], $contact)) {
  		$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
  		return false;
  	}
  	$data = array(
  		'contact'	=> $contact
  	);
  	$kitContactInterface->logout();
  	return $this->getTemplate('confirm.logout.htt', $data);  	
  } // Logout()
  
  public function subscribeNewsletter($form_data=array()) {
  	global $kitContactInterface;
  	
  	$use_subscribe=false;
  	$subscribe = false;
  	// pruefen ob kit_newsletter_subscribe verwendet wird
  	if (isset($_REQUEST[kitContactInterface::kit_newsletter_subscribe])) {
  		$use_subscribe = true;
  		if (is_bool($_REQUEST[kitContactInterface::kit_newsletter_subscribe])) {
  			$subscribe = $_REQUEST[kitContactInterface::kit_newsletter_subscribe];
  		}
  		elseif (is_numeric($_REQUEST[kitContactInterface::kit_newsletter_subscribe])) {
  			$subscribe = ($_REQUEST[kitContactInterface::kit_newsletter_subscribe] == 1) ? true : false;
  		}
  		else {
  			$subscribe = (strtolower($_REQUEST[kitContactInterface::kit_newsletter_subscribe]) == 'true') ? true : false;
  		}
  	}
  	
  	$newsletter = '';
  	if (isset($_REQUEST[kitContactInterface::kit_newsletter]) && is_array($_REQUEST[kitContactInterface::kit_newsletter])) {
  		$newsletter = implode(',', $_REQUEST[kitContactInterface::kit_newsletter]);
  	}
  	elseif (isset($_REQUEST[kitContactInterface::kit_newsletter])) {
  		$newsletter = $_REQUEST[kitContactInterface::kit_newsletter];
  	}
  	
  	$email = $_REQUEST[kitContactInterface::kit_email];
  	
  	if (!$kitContactInterface->subscribeNewsletter($email, $newsletter, $subscribe, $use_subscribe, $register, $contact, $send_activation)) {
  		if ($kitContactInterface->isError()) {
  			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
  			return false;
  		}
  		$this->setMessage($kitContactInterface->getMessage());
  		return $this->showForm();
  	}
  	$message = $kitContactInterface->getMessage();
  	if ($send_activation == false) {
  		$message .= sprintf(form_msg_newsletter_abonnement_updated, $email);
  		$this->setMessage($message);
  		$data = array(
  			'message'	=> $this->getMessage()
  		);
  		return $this->getTemplate('prompt.htt', $data);
  	}
  	else {
  		// Aktivierungskey versenden
  		$form = array();
  		$form['activation_link'] = sprintf(	'%s%s%s', 
  																				$this->page_link, 
  																				(strpos($this->page_link, '?') === false) ? '?' : '&', 
  																				http_build_query(array(
  																					self::request_action 	=> self::action_activation_key, 
  																					self::request_key			=> $register[dbKITregister::field_register_key]
  																				)));
  		$form['datetime'] = date(form_cfg_datetime_str); 
  		$data = array(
  			'form'		=> $form,
  			'contact'	=> $contact
  		);
  		$client_mail = $this->getTemplate('mail.client.register.newsletter.htt', $data);
			
			$mail = new kitMail();
			if (!$mail->mail(form_mail_subject_client_register, $client_mail, SERVER_EMAIL, SERVER_EMAIL, array($contact[kitContactInterface::kit_email] => $contact[kitContactInterface::kit_email]), false)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(form_error_sending_email, $contact[kitContactInterface::kit_email])));
				return false;
			}
			
			$provider_mail = $this->getTemplate('mail.provider.register.newsletter.htt', $data);
			$mail = new kitMail();
			if (!$mail->mail(form_mail_subject_provider_register, $provider_mail, $contact[kitContactInterface::kit_email], $contact[kitContactInterface::kit_email], array(SERVER_EMAIL => SERVER_EMAIL), false)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(form_error_sending_email, SERVER_EMAIL)));
				return false;
			}
			
			return $this->getTemplate('confirm.register.newsletter.htt', $data);
  	}
  	
  } // subscribeNewsletter()
  
} // class formFrontend

?>