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
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.backend.php');
require_once(WB_PATH.'/include/captcha/captcha.php');
require_once(WB_PATH.'/framework/class.wb.php');
require_once(WB_PATH.'/modules/kit/class.mail.php');

global $dbKITform;
global $dbKITformFields;
global $dbKITformTableSort;
global $dbKITformData;

if (!is_object($dbKITform)) 					$dbKITform = new dbKITform();
if (!is_object($dbKITformFields))			$dbKITformFields = new dbKITformFields();
if (!is_object($dbKITformData))				$dbKITformData = new dbKITformData();


class formFrontend {
	
	const request_action						= 'fact';
	
	const action_default						= 'def';
	const action_check_form					= 'acf';
	
	private $page_link 					= '';
	private $img_url						= '';
	private $template_path			= '';
	private $error							= '';
	private $message						= '';
	
	const param_preset					= 'fpreset';
	const param_form						= 'form';
	const param_return					= 'return';
	
	private $params = array(
		self::param_preset			=> 1, 
		self::param_form				=> '',	
		self::param_return			=> false
	);
	
	public function __construct() {
		global $formTools;
		$url = '';
		$_SESSION['FRONTEND'] = true;	
		$formTools->getPageLinkByPageID(PAGE_ID, $url);
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
  	switch ($action):
  	case self::action_check_form:
  		$result = $this->checkForm();
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
  	
  	$SQL = sprintf(	"SELECT * FROM %s WHERE %s='%s' AND %s='%s'",
  									$dbKITform->getTableName(),
  									dbKITform::field_name,
  									$this->params[self::param_form],
  									dbKITform::field_status,
  									dbKITform::status_active);
  	$fdata = array();
  	if (!$dbKITform->sqlExec($SQL, $fdata)) {
  		$this->setError($dbKITform->getError()); return false;
  	}
  	if (count($fdata) < 1) {
  		$this->setError(sprintf(form_error_form_name_invalid, $this->params[self::param_form])); return false;
  	}
  	$fdata = $fdata[0];
  	
  	// CAPTCHA
 		ob_start();
			call_captcha();
			$call_captcha = ob_get_contents();
		ob_end_clean();
		
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
  													'value'		=> $fdata[dbKITform::field_action])
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
  					$selected = $_REQUEST[$field_name];
  					$new_array = array();
  					foreach ($newsletter_array as $newsletter) {
  						$newsletter['checked'] = ($newsletter['value'] == $selected) ? 1 : 0;
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
		global $formTools;
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
  		return $this->checkLogin();
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
  			elseif (($field_name == kitContactInterface::kit_email) && !$formTools->validateEMail($_REQUEST[kitContactInterface::kit_email])) {
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
			$contact_array = array();
			foreach ($kitContactInterface->field_array as $key => $value) {
				switch ($key):
				case kitContactInterface::kit_zip_city:
					// nothing to do...
					break;
				case kitContactInterface::kit_newsletter:
					if (isset($_REQUEST[$key])) $contact_array[$key] = implode(',', $_REQUEST[$key]);
					break;
				default:
					if (isset($_REQUEST[$key])) $contact_array[$key] = $_REQUEST[$key];
					break;
				endswitch;
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
					$values[$fid] = (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? $formTools->str2float($_REQUEST[$field[dbKITformFields::field_name]], form_cfg_thousand_separator, form_cfg_decimal_separator) : 0;
					break;
				case dbKITformFields::data_type_integer:
					$values[$fid] = (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? $formTools->str2int($_REQUEST[$field[dbKITformFields::field_nam]], form_cfg_thousand_separator, form_cfg_decimal_separator) : 0;
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
				$items[] = array(
					'label'		=> $field[dbKITformFields::field_title],
					'value'		=> $value
				);
			}
		
			$form = $form_data;
			$form['datetime'] = date(form_cfg_datetime_str, strtotime($form[dbKITformData::field_date]));
			
			$data = array(
				'form'		=> $form,
				'contact'	=> $contact,
				'items'		=> $items
			);
			$client_mail = $this->getTemplate('mail.client.htt', $data);
			
			$mail = new kitMail();
			if (!$mail->mail(form_mail_subject_client, $client_mail, SERVER_EMAIL, SERVER_EMAIL, array($contact[kitContactInterface::kit_email] => $contact[kitContactInterface::kit_email]), false)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(form_error_sending_email, $contact[kitContactInterface::kit_email])));
				return false;
			}
			
			$provider_mail = $this->getTemplate('mail.provider.htt', $data);
			$mail = new mail(); //new kitMail();
			if (!$mail->mail(form_mail_subject_provider, $provider_mail, $contact[kitContactInterface::kit_email], $contact[kitContactInterface::kit_email], array(SERVER_EMAIL => SERVER_EMAIL), false)) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf(form_error_sending_email, SERVER_EMAIL)));
				return false;
			}
			
			return $this->getTemplate('confirm.htt', $data);
		} // checked
		
		$this->setMessage($message);
		return $this->showForm();
  } // checkForm()
  
  public function checkLogin() {
  	return __METHOD__;
  } // checkLogin()
  
} // class formFrontend

?>