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

// include class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {    
    if (defined('LEPTON_VERSION')) include(WB_PATH.'/framework/class.secure.php'); 
} else {
    $oneback = "../";
    $root = $oneback;
    $level = 1;
    while (($level < 10) && (!file_exists($root.'/framework/class.secure.php'))) {
        $root .= $oneback;
        $level += 1;
    }
    if (file_exists($root.'/framework/class.secure.php')) { 
        include($root.'/framework/class.secure.php'); 
    } else {
        trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", 
                $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
    }
}
// end include class.secure.php


require_once (WB_PATH . '/modules/' . basename ( dirname ( __FILE__ ) ) . '/initialize.php');
require_once (WB_PATH . '/modules/' . basename ( dirname ( __FILE__ ) ) . '/class.backend.php');
require_once (WB_PATH . '/include/captcha/captcha.php');
require_once (WB_PATH . '/framework/class.wb.php');
require_once (WB_PATH . '/modules/kit/class.mail.php');
require_once (WB_PATH . '/modules/droplets_extension/interface.php');
require_once(WB_PATH . '/framework/functions.php');

global $dbKITform;
global $dbKITformFields;
global $dbKITformTableSort;
global $dbKITformData;
global $dbKITformCommands;

class formFrontend {
	
	const request_action = 'act';
	const request_link = 'link';
	const request_key = 'key';
	const request_activation_type = 'at';
	const request_provider_id = 'pid';
	const request_command = 'kfc';
	const request_form_id = 'fid';
	
	const action_default = 'def';
	const action_check_form = 'acf';
	const action_command = 'cmd';
	const action_activation_key = 'key';
	const action_feedback_unsubscribe = 'fun';
	const action_feedback_unsubscribe_check = 'fnc';
	
	const activation_type_newsletter = 'nl';
	const activation_type_account = 'acc';
	const activation_type_default = 'def';
	
	private $page_link = '';
	private $img_url = '';
	private $template_path = '';
	private $error = '';
	private $message = '';
	private $contact = array ();
	
	const param_preset = 'fpreset';
	const param_form = 'form';
	const param_return = 'return';
	const param_css = 'css';
	const param_auto_login_wb = 'auto_login_wb';
	
	const FIELD_FEEDBACK_TEXT = 'feedback_text';
	const FIELD_FEEDBACK_URL = 'feedback_url';
	const FIELD_FEEDBACK_PUBLISH = 'feedback_publish';
	const FIELD_FEEDBACK_SUBSCRIPTION = 'feedback_subscription';
	const FIELD_FEEDBACK_HOMEPAGE = 'feedback_homepage';
	const FIELD_FEEDBACK_SUBJECT = 'feedback_subject';
	const FIELD_FEEDBACK_NICKNAME = 'feedback_nickname';
	
	const PUBLISH_IMMEDIATE = 1;
	const PUBLISH_ACTIVATION = 2;
	const PUBLISH_FORBIDDEN = 4;
	
	const SUBSCRIPE_YES = 1;
	const SUBSCRIPE_NO = 0;
	
	const FORM_ANCHOR = 'kf';
	
	private $params = array (
	        self::param_preset => 1, 
	        self::param_form => '', 
	        self::param_return => false, 
	        self::param_css => true, 
	        self::param_auto_login_wb => false 
	        );
	
	protected $lang;
	
	// protected folder for uploads - uses kitDirList sheme!
	const PROTECTION_FOLDER	= 'kit_protected';
	const CONTACTS_FOLDER = 'contacts';
	const USER_FOLDER = 'user';
	
	protected $general_excluded_extensions = array(
	        'php',
	        'php3',
	        'php4',
	        'php5',
	        'php6',
	        'phps',
	        'js',
	        'htm',
	        'html',
	        'shtml'
	);
	
	
	public function __construct() {
	    global $I18n;
		global $kitLibrary;
		$url = '';
		$_SESSION ['FRONTEND'] = true;
		$kitLibrary->getPageLinkByPageID ( PAGE_ID, $url );
		$this->page_link = $url;
		$this->template_path = WB_PATH . '/modules/' . basename ( dirname ( __FILE__ ) ) . '/htt/';
		$this->img_url = WB_URL . '/modules/' . basename ( dirname ( __FILE__ ) ) . '/images/';
		date_default_timezone_set ( cfg_time_zone );
		$this->lang = $I18n;
	} // __construct()
	

	
	public function getParams() {
		return $this->params;
	} // getParams()
	

	public function setParams($params = array()) {
		$this->params = $params;
		$this->template_path = WB_PATH . '/modules/kit_form/htt/' . $this->params [self::param_preset] . '/' . KIT_FORM_LANGUAGE . '/';
		if (! file_exists ( $this->template_path )) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,  
			        $this->lang->translate('The preset directory <b>{{ directory }}</b> does not exists, can\'t load any template!', array('directory' => '/modules/kit_form/htt/'.$this->params[self::param_preset].'/'.KIT_FORM_LANGUAGE.'/' ) )));
			return false;
		}
		return true;
	} // setParams()
	

	/**
	 * Set $this->error to $error
	 * 
	 * @param string $error
	 */
	public function setError($error) {
		$this->error = $error;
	} // setError()
	

	/**
	 * Get Error from $this->error;
	 * 
	 * @return string $this->error
	 */
	public function getError() {
		return $this->error;
	} // getError()
	

	/**
	 * Check if $this->error is empty
	 * 
	 * @return boolean
	 */
	public function isError() {
		return ( bool ) ! empty ( $this->error );
	} // isError
	
	/**
	 * Set the contact array of the user
	 * 
	 * @param array $contact
	 */
	protected function setContact($contact) {
		$this->contact = $contact;
	} // setContact();
	
	/**
	 * Return the contact array of the user
	 * 
	 * @return array
	 */
	protected function getContact() {
		return $this->contact;
	} // getContact()
	

	/**
	 * Reset Error to empty String
	 */
	protected function clearError() {
		$this->error = '';
	}
	
	/** Set $this->message to $message
	 * 
	 * @param string $message
	 */
	public function setMessage($message) {
		$this->message = $message;
	} // setMessage()
	

	/**
	 * Get Message from $this->message;
	 * 
	 * @return string $this->message
	 */
	public function getMessage() {
		return $this->message;
	} // getMessage()
	

	/**
	 * Check if $this->message is empty
	 * 
	 * @return boolean
	 */
	public function isMessage() {
		return ( bool ) ! empty ( $this->message );
	} // isMessage
	

	/**
	 * Execute the desired template and return the completed template
	 * 
	 * @param string $template - the filename of the template without path
	 * @param array $template_data - the template data
	 * @return string template or boolean false on error
	 */
	protected function getTemplate($template, $template_data) {
		global $parser;
		try {
			$result = $parser->get ( $this->template_path . $template, $template_data );
		} catch ( Exception $e ) {
			$this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__,  
			        $this->lang->translate('Error executing template <b>{{ template }}</b>:<br />{{ error }}', 
			                array('template' => $template, 'error' => $e->getMessage()))));
			return false;
		}
		return $result;
	} // getTemplate()
	

	/**
	 * Verhindert XSS Cross Site Scripting
	 * 
	 * @param reference array $request
	 * @return $request
	 */
	protected function xssPrevent(&$request) {
		if (is_string ( $request )) {
			$request = html_entity_decode ( $request );
			$request = strip_tags ( $request );
			$request = trim ( $request );
			$request = stripslashes ( $request );
		}
		return $request;
	} // xssPrevent()
	
	/**
	 * The action handler of kitForm - call this function after creating a new
	 * instance of kitForm!
	 * 
	 * @return string result
	 */
	public function action() {
		if ($this->isError ())
			return sprintf ( '<div class="error">%s</div>', $this->getError () );
		$html_allowed = array ();
		foreach ( $_REQUEST as $key => $value ) {
			if (! in_array ( $key, $html_allowed )) {
				$_REQUEST [$key] = $this->xssPrevent ( $value );
			}
		}
		
		isset ( $_REQUEST [self::request_action] ) ? $action = $_REQUEST [self::request_action] : $action = self::action_default;
		
		// CSS laden? 
		if ($this->params [self::param_css]) { 
			if (! is_registered_droplet_css ( 'kit_form', PAGE_ID )) {
				register_droplet_css ( 'kit_form', PAGE_ID, 'kit_form', 'kit_form.css' );
			}
		} elseif (is_registered_droplet_css ( 'kit_form', PAGE_ID )) {
			unregister_droplet_css ( 'kit_form', PAGE_ID );
		}
		
		switch ($action) :
	    case self::action_feedback_unsubscribe:
		    $result = $this->showFeedbackUnsubscribe();
		    break;
	    case self::action_feedback_unsubscribe_check:
	        $result = $this->checkFeedbackUnsubscribe();
	        break;
	    case self::action_command:
	        $result = $this->checkCommand();
	        break;
		case self::action_check_form :
			$result = $this->checkForm ();
			break;
		case self::action_activation_key :
			$result = $this->checkActivationKey ();
			break;
		case self::action_default :
		default :
			$result = $this->showForm ();
			break;
		endswitch;
		
		if ($this->isError ())
			$result = sprintf('<a name="%s"></a><div class="error">%s</div>', self::FORM_ANCHOR, $this->getError());
		return $result;
	} // action
	
	/**
	 * This master function collects all datas of a form, prepare and return 
	 * the complete form
	 * 
	 * @return string form or boolean false on error
	 */
	protected function showForm() {
		global $dbKITform;
		global $dbKITformFields;
		global $kitContactInterface;
		global $kitLibrary;
		
		if (empty($this->params)) {
			$this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__, 
			        $this->lang->translate('The form name is empty, please check the parameters for the droplet!')));
			return false;
		}
		
		$form_id = - 1;
		$form_name = 'none';
		
		// special: feedback form
		$is_feedback_form = false;
		// special: file upload
		$is_file_upload = false;
		
		if (isset($_REQUEST[self::request_link])) {
			$form_name = $_REQUEST[self::request_link];
		} elseif (isset($_REQUEST[dbKITform::field_id])) {
			$form_id = $_REQUEST[dbKITform::field_id];
		} else {
			$form_name = $this->params[self::param_form];
		}
		
		if ($form_id > 0) {
			$SQL = sprintf("SELECT * FROM %s WHERE %s='%s'", $dbKITform->getTableName(), dbKITform::field_id, $form_id);
		} else {
			$SQL = sprintf("SELECT * FROM %s WHERE %s='%s' AND %s='%s'", $dbKITform->getTableName(), dbKITform::field_name, $form_name, dbKITform::field_status, dbKITform::status_active);
		}
		$fdata = array ();
		if (!$dbKITform->sqlExec($SQL, $fdata)) {
			$this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError ()) );
			return false;
		}
		if (count($fdata) < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, 
			        $this->lang->translate('Cant\'t load the form <b>{{ form }}</b>!', array('form' => $form_name ))));
			return false;
		}
		$fdata = $fdata [0];
		$form_id = $fdata[dbKITform::field_id];
		
		if ($fdata [dbKITform::field_action] == dbKITform::action_logout) {
			// Sonderfall: beim LOGOUT wird direkt der Bestaetigungsdialog angezeigt
			if ($kitContactInterface->isAuthenticated ()) {
				// Abmelden und Verabschieden...
				return $this->Logout ();
			} else {
				// Benutzer ist nicht angemeldet...
				$data = array ('message' => $this->lang->translate('<p>You are not authenticated, please login first!</p>'));
				return $this->getTemplate ( 'prompt.htt', $data );
			}
		} elseif ($fdata [dbKITform::field_action] == dbKITform::action_account) {
			// Das Benutzerkonto zum Bearbeiten anzeigen
			if ($kitContactInterface->isAuthenticated ()) {
				// ok - User ist angemeldet
				$contact = array();
				if (! $kitContactInterface->getContact ( $_SESSION [kitContactInterface::session_kit_contact_id], $contact )) {
					$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
					return false;
				}
				foreach ( $contact as $key => $value ) {
					if (! isset ( $_REQUEST [$key] ))
						$_REQUEST [$key] = $value;
				}
			} else {
				// Dialog kann nicht angezeigt werden, Benutzer ist nicht angemeldet!
				$data = array ('message' => $this->lang->translate('<p>You are not authenticated, please login first!</p>'));
				return $this->getTemplate ( 'prompt.htt', $data );
			}
		}
		// CAPTCHA
		ob_start ();
		call_captcha ();
		$call_captcha = ob_get_contents ();
		ob_end_clean ();
		
		// Links auslesen
		parse_str ( $fdata [dbKITform::field_links], $links );
		$links ['command'] = sprintf ( '%s%s%s', $this->page_link, (strpos ( $this->page_link, '?' ) === false) ? '?' : '&', self::request_link );
		// Formulardaten
		$form_data = array (
		        'name' => 'kit_form',
		        'anchor' => self::FORM_ANCHOR, 
		        'action' => array (
		                'link' => $this->page_link, 
		                'name' => self::request_action, 
		                'value' => self::action_check_form 
		                ), 
		        'id' => array (
		                'name' => dbKITform::field_id, 
		                'value' => $fdata [dbKitform::field_id] 
		                ), 
		        'response' => ($this->isMessage ()) ? $this->getMessage () : NULL, 
		        'btn' => array (
		                'ok' => $this->lang->translate('OK'), 
		                'abort' => $this->lang->translate('Abort')
		                ), 
		        'title' => $fdata [dbKITform::field_title], 
		        'captcha' => array (
		                'active' => (
		                        $fdata [dbKITform::field_captcha] == dbKITform::captcha_on) ? 1 : 0, 
		                'code' => $call_captcha 
		                ), 
		        'kit_action' => array (
		                'name' => dbKITform::field_action, 
		                'value' => $fdata [dbKITform::field_action] 
		                ), 
		        'links' => $links 
		        );
		
		// Felder auslesen und Array aufbauen
		$fields_array = explode ( ',', $fdata [dbKITform::field_fields] );
		$must_array = explode ( ',', $fdata [dbKITform::field_must_fields] );
		$form_fields = array ();
		$upload_id = (isset($_REQUEST['upload_id'])) ? $_REQUEST['upload_id'] : $kitLibrary->createGUID();
		foreach ( $fields_array as $field_id ) {
			if ($field_id < 100) {
				// IDs 1-99 sind fuer KIT reserviert
				if (false === ($field_name = array_search ( $field_id, $kitContactInterface->index_array ))) {
					// $field_id nicht gefunden
					$this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__,  
					        $this->lang->translate('The field with the <b>ID {{ id }}</b> is no KIT datafield!', array('id' => sprintf('%03d', $field_id)))));
					return false;
				}
				switch ($field_name) :
					case kitContactInterface::kit_title :
					case kitContactInterface::kit_title_academic :
						// Anrede und akademische Titel
						$title_array = array();
						if ($field_name == kitContactInterface::kit_title) {
							$kitContactInterface->getFormPersonTitleArray ( $title_array );
						} else {
							$kitContactInterface->getFormPersonTitleAcademicArray ( $title_array );
						}
						if (isset($_REQUEST[$field_name])) {
							$selected = $_REQUEST [$field_name];
							$new_array = array ();
							foreach ( $title_array as $title ) {
								$title ['checked'] = ($title ['value'] == $selected) ? 1 : 0;
								$new_array [] = $title;
							}
							$title_array = $new_array;
						}
						$form_fields [$field_name] = array (
						        'id' => $field_id, 
						        'type' => $field_name, 
						        'name' => $field_name, 
						        'value' => '', 
						        'must' => (in_array($field_id, $must_array )) ? 1 : 0, 
						        'label' => $kitContactInterface->field_array [$field_name], 
						        'hint' => $this->lang->translate('hint_'.$field_name), 
						        'titles' => $title_array );
						break;
					case kitContactInterface::kit_address_type :
						// Adresstyp auswaehlen
						$address_type_array = array();
						$kitContactInterface->getFormAddressTypeArray ( $address_type_array );
						if (isset ( $_REQUEST [$field_name] )) {
							$selected = $_REQUEST [$field_name];
							$new_array = array ();
							foreach ( $address_type_array as $address_type ) {
								$address_type ['checked'] = ($address_type ['value'] == $selected) ? 1 : 0;
								$new_array [] = $address_type;
							}
							$address_type_array = $new_array;
						}
						$form_fields [$field_name] = array (
						        'id' => $field_id, 
						        'type' => $field_name, 
						        'name' => $field_name, 
						        'value' => 1, 
						        'must' => (in_array ( $field_id, $must_array )) ? 1 : 0, 
						        'label' => $kitContactInterface->field_array [$field_name], 
						        'hint' => $this->lang->translate('hint_' . $field_name ), 
						        'address_types' => $address_type_array 
						        );
						break;
					case kitContactInterface::kit_first_name :
					case kitContactInterface::kit_last_name :
					case kitContactInterface::kit_company :
					case kitContactInterface::kit_department :
					case kitContactInterface::kit_fax :
					case kitContactInterface::kit_phone :
					case kitContactInterface::kit_phone_mobile :
					case kitContactInterface::kit_street :
					case kitContactInterface::kit_city :
					case kitContactInterface::kit_zip :
					case kitContactInterface::kit_email :
					case kitContactInterface::kit_email_retype:
					case kitContactInterface::kit_password :
					case kitContactInterface::kit_password_retype :
						$form_fields [$field_name] = array (
						    'id' => $field_id, 
						    'type' => $field_name, 
						    'name' => $field_name, 
						    'value' => (isset ( $_REQUEST [$field_name] )) ? $_REQUEST [$field_name] : '', 
						    'must' => (in_array ( $field_id, $must_array )) ? 1 : 0, 
						    'label' => $kitContactInterface->field_array [$field_name], 
						    'hint' => $this->lang->translate('hint_' . $field_name ) 
						);
						break;
					case kitContactInterface::kit_zip_city :
						// Auswahl fuer Postleitzahl und Stadt
						$form_fields [$field_name] = array (
						    'id' => $field_id, 
						    'type' => $field_name, 
						    'name_zip' => kitContactInterface::kit_zip, 
						    'value_zip' => (isset ( $_REQUEST [kitContactInterface::kit_zip] )) ? $_REQUEST [kitContactInterface::kit_zip] : '', 
						    'name_city' => kitContactInterface::kit_city, 
						    'value_city' => (isset ( $_REQUEST [kitContactInterface::kit_city] )) ? $_REQUEST [kitContactInterface::kit_city] : '', 
						    'must' => (in_array ( $field_id, $must_array )) ? 1 : 0, 
						    'label' => $kitContactInterface->field_array [$field_name], 
						    'hint' => $this->lang->translate('hint_' . $field_name ) 
						);
						break;
					case kitContactInterface::kit_newsletter :
						$newsletter_array = array();
						$kitContactInterface->getFormNewsletterArray ( $newsletter_array );
						if (isset ( $_REQUEST [$field_name] )) {
							$select_array = (is_array ( $_REQUEST [$field_name] )) ? $_REQUEST [$field_name] : explode ( ',', $_REQUEST [$field_name] );
							//$select_array = $_REQUEST[$field_name]; 
							$new_array = array ();
							foreach ( $newsletter_array as $newsletter ) {
								$newsletter ['checked'] = (in_array ( $newsletter ['value'], $select_array )) ? 1 : 0;
								$new_array [] = $newsletter;
							}
							$newsletter_array = $new_array;
						}
						$form_fields [$field_name] = array (
						        'id' => $field_id, 
						        'type' => $field_name, 
						        'name' => $field_name, 
						        'value' => '', 
						        'must' => (in_array ( $field_id, $must_array )) ? 1 : 0, 
						        'label' => $kitContactInterface->field_array [$field_name], 
						        'hint' => $this->lang->translate('hint_' . $field_name ), 
						        'newsletters' => $newsletter_array );
						break;
					default :
						// Datentyp nicht definiert - Fehler ausgeben
						$this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__, 
						        $this->lang->translate('The datatype <b>{{ type }}</b> is not supported!', array('type' => $field_name))));
						return false;
				endswitch
				;
			} else {
				// ab 100 sind allgemeine Felder
				$where = array (dbKITformFields::field_id => $field_id );
				$field = array ();
				if (! $dbKITformFields->sqlSelectRecord ( $where, $field )) {
					$this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError () ));
					return false;
				}
				if (count ( $field ) < 1) {
				    /*
				     * Don't through an error - just continue ...
				     * @todo using a logfile to document problems with the form ?
				     */
				    continue;
					// $this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf ( kit_error_invalid_id, $field_id )) );
					// return false;
				}
				$field = $field [0];
				if ($field[dbKITformFields::field_name] == self::FIELD_FEEDBACK_TEXT) {
				    // special: this is a feedback form!
				    $is_feedback_form = true;
				}
				switch ($field [dbKITformFields::field_type]) :
					case dbKITformFields::type_checkbox :
						// CHECKBOX
						parse_str($field[dbKITformFields::field_type_add], $checkboxes);
						if (isset($_REQUEST[$field[dbKITformFields::field_name]])) {
							$checked_array = $_REQUEST[$field [dbKITformFields::field_name]];
							$checked_boxes = array ();
							foreach ($checkboxes as $checkbox ) {
							    $checkbox['checked'] = (in_array($checkbox['value'], $checked_array)) ? 1 : 0;
								$checked_boxes[$checkbox['name']] = $checkbox;
							}
							$checkboxes = $checked_boxes;
						}
						$form_fields[$field[dbKITformFields::field_name]] = array (
						        'id' => $field [dbKITformFields::field_id], 
						        'type' => $field [dbKITformFields::field_type], 
						        'name' => $field [dbKITformFields::field_name], 
						        'hint' => $field [dbKITformFields::field_hint], 
						        'label' => $field [dbKITformFields::field_title], 
						        'must' => (in_array ( $field_id, $must_array )) ? 1 : 0, 
						        'value' => $field [dbKITformFields::field_value], 
						        'checkbox' => $checkboxes 
						        );
						break;
					case dbKITformFields::type_delayed:
					    // DELAYED transmission
					    parse_str($field[dbKITformFields::field_type_add], $type_add);
					    $form_fields[$field[dbKITformFields::field_name]] = array(
					            'id' => $field[dbKITformFields::field_id],
					            'type' => $field[dbKITformFields::field_type],
					            'name' => $field[dbKITformFields::field_name],
					            'hint' => $field[dbKITformFields::field_hint],
					            'label' => $field[dbKITformFields::field_title],
					            'must' => (in_array($field_id, $must_array)) ? 1 : 0,
					            'value' => $field[dbKITformFields::field_value],
					            'checkbox' => array(
					                    'text' => (isset($type_add['text'])) ? $type_add['text'] : '',
					                    'name' => $field[dbKITformFields::field_name],
					                    'value' => $field[dbKITformFields::field_value],
					                    'checked' => (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? 1 : 0
					                    )
					            );
					    break;
					case dbKITformFields::type_hidden :
						$form_fields[$field[dbKITformFields::field_name]] = array(
						        'id' => $field [dbKITformFields::field_id], 
						        'type' => $field [dbKITformFields::field_type], 
						        'name' => $field [dbKITformFields::field_name], 
						        'value' => $field [dbKITformFields::field_value] 
						        );
						break;
					case dbKITformFields::type_file:
					    parse_str($field[dbKITformFields::field_type_add], $settings);
					    $ext_array = explode(',', $settings['file_types']['value']);
					    $file_ext = '';
					    $file_desc = '';
					    foreach ($ext_array as $ext) {
					        if (empty($ext)) continue;
					        if (!empty($file_ext)) {
					            $file_ext .= ';';
					            $file_desc .= ', ';
					        }
					        $file_ext .= sprintf('*.%s', $ext);
					        $file_desc .= sprintf('.%s', $ext);
					    }
					    $form_fields[$field[dbKITformFields::field_name]] = array(
        					    'id' => $field [dbKITformFields::field_id],
        					    'type' => $field [dbKITformFields::field_type],
        					    'name' => $field [dbKITformFields::field_name],
        					    'hint' => $field [dbKITformFields::field_hint],
        					    'label' => $field [dbKITformFields::field_title],
        					    'must' => (in_array ( $field_id, $must_array )) ? 1 : 0,
					            'settings' => $settings,
					            'upload_id' => $upload_id,
					            'file_desc' => $file_desc,
					            'file_ext' => $file_ext,
					            'file_size' => $settings['max_file_size']['value']*1024*1024,
					            'select_file' => $this->lang->translate('Select File')       					    
					            );
					    $is_file_upload = true;
					    break;
					case dbKITformFields::type_html :
						$form_fields [$field [dbKITformFields::field_name]] = array (
						        'id' => $field [dbKITformFields::field_id], 
						        'type' => $field [dbKITformFields::field_type], 
						        'value' => $field [dbKITformFields::field_value] 
						);
						break;
					case dbKITformFields::type_radio :
						parse_str ( $field [dbKITformFields::field_type_add], $radios );
						if (isset ( $_REQUEST [$field [dbKITformFields::field_name]] )) {
							$checked = $_REQUEST [$field [dbKITformFields::field_name]];
							$checked_radios = array ();
							foreach ( $radios as $radio ) {
								$radio ['checked'] = ($radio ['value'] == $checked) ? 1 : 0;
								$checked_radios [] = $radio;
							}
							$radios = $checked_radios;
						}
						$form_fields [$field [dbKITformFields::field_name]] = array (
						        'id' => $field [dbKITformFields::field_id], 
						        'type' => $field [dbKITformFields::field_type], 
						        'name' => $field [dbKITformFields::field_name], 
						        'hint' => $field [dbKITformFields::field_hint], 
						        'label' => $field [dbKITformFields::field_title], 
						        'must' => (in_array ( $field_id, $must_array )) ? 1 : 0, 
						        'value' => $field [dbKITformFields::field_value], 
						        'radio' => $radios 
						        );
						break;
					case dbKITformFields::type_select :
						parse_str ( $field [dbKITformFields::field_type_add], $options );
						if (isset ( $_REQUEST [$field [dbKITformFields::field_name]] )) {
							$checked = $_REQUEST [$field [dbKITformFields::field_name]];
							$checked_options = array ();
							foreach ( $options as $option ) {
								$option ['checked'] = ($option ['value'] == $checked) ? 1 : 0;
								$checked_options [] = $option;
							}
							$options = $checked_options;
						}
						$form_fields [$field [dbKITformFields::field_name]] = array (
						        'id' => $field [dbKITformFields::field_id], 
						        'type' => $field [dbKITformFields::field_type], 
						        'name' => $field [dbKITformFields::field_name], 
						        'hint' => $field [dbKITformFields::field_hint], 
						        'label' => $field [dbKITformFields::field_title], 
						        'must' => (in_array ( $field_id, $must_array )) ? 1 : 0, 'value' => $field [dbKITformFields::field_value], 
						        'option' => $options 
						        );
						break;
					case dbKITformFields::type_text_area :
					case dbKITformFields::type_text :
						$form_fields [$field [dbKITformFields::field_name]] = array (
						        'id' => $field [dbKITformFields::field_id], 
						        'type' => $field [dbKITformFields::field_type], 
						        'name' => $field [dbKITformFields::field_name], 
						        'hint' => $field [dbKITformFields::field_hint], 
						        'label' => $field [dbKITformFields::field_title], 
						        'must' => (in_array ( $field_id, $must_array )) ? 1 : 0, 
						        'value' => isset ( $_REQUEST [$field [dbKITformFields::field_name]] ) ? $_REQUEST [$field [dbKITformFields::field_name]] : $field [dbKITformFields::field_value] 
						);
						break;
					default :
						//continue;
						$this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__, 
						        $this->lang->translate('The datatype <b>{{ type }}</b> is not supported!', array('type' => $field [dbKITformFields::field_type]))));
						return false;
				endswitch
				;
			}
		}
		
		if ($is_feedback_form) {
		    return $this->showFeedbackForm($form_id, $form_data, $form_fields);
		}
		else {
		    $data = array (
		            'WB_URL' => WB_URL,
		            'form' => $form_data, 
		            'fields' => $form_fields );
		    return $this->getTemplate ( 'form.htt', $data );
		}
	} // showForm()
	

	/**
	 * Ueberprueft das Formular, zeigt das Formular bei Fehlern erneut an.
	 * Wenn alles in Ordnung ist, werden die Daten gesichert und 
	 * Benachrichtigungs E-Mails versendet.
	 * 
	 * @return string FORMULAR oder ERFOLGSMELDUNG
	 */
	protected function checkForm() {
		global $dbKITform;
		global $dbKITformFields;
		global $kitContactInterface;
		global $kitLibrary;
		global $dbKITformData;
		global $dbContact;
		global $dbKITdirList;
		
		if (! isset ( $_REQUEST [dbKITform::field_id] )) {
			$this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Missing the form ID!')));
			return false;
		}
		$form_id = $_REQUEST [dbKITform::field_id];
		$where = array (dbKITform::field_id => $form_id );
		$form = array ();
		if (! $dbKITform->sqlSelectRecord ( $where, $form )) {
			$this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError ()));
			return false;
		}
		if (count ( $form ) < 1) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, 
			        $this->lang->translate('The ID {{ id }} is invalid!', array('id' => $form_id))));
			return false;
		}
		$form = $form [0];
		
		// pruefen, ob eine Aktion ausgefuehrt werden soll
		switch ($form [dbKITform::field_action]) :
			case dbKITform::action_login :
				return $this->checkLogin ( $form );
			case dbKITform::action_logout :
				return $this->Logout ( $form );
			case dbKITform::action_send_password :
				return $this->sendNewPassword ( $form );
			case dbKITform::action_newsletter :
			//	return $this->subscribeNewsletter ( $form );
			case dbKITform::action_register :
			case dbKITform::action_account :
			/*
  		 * Diese speziellen Aktionen werden erst durchgefuehrt, 
  		 * wenn die allgemeinen Daten bereits geprueft sind
  		 */
			default :
		// nothing to do - go ahead...
		endswitch
		;
		
		$message = '';
		$checked = true;
		// CAPTCHA pruefen?
		if ($form [dbKITform::field_captcha] == dbKITform::captcha_on) {
			unset ( $_SESSION ['kf_captcha'] );
			if (! isset ( $_REQUEST ['captcha'] ) || ($_REQUEST ['captcha'] != $_SESSION ['captcha'])) {
				$message .= $this->lang->translate('<p>The CAPTCHA code is not correct, please try again!</p>');
				$checked = false;
			}
		}
		
		// zuerst die Pflichtfelder pruefen
		$must_array = explode ( ',', $form [dbKITform::field_must_fields] );
		foreach ( $must_array as $must_id ) {
			if ($must_id < 100) {
				// IDs 1-99 sind fuer KIT reserviert
				if (false === ($field_name = array_search ( $must_id, $kitContactInterface->index_array ))) {
					// $field_id nicht gefunden
					$this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__, 
					        $this->lang->translate('The field with the <b>ID {{ id }}</b> is no KIT datafield!', array('id' => $must_id))));
					return false;
				}
				if (!isset($_REQUEST[$field_name]) || empty($_REQUEST[$field_name])) {
					// Feld muss gesetzt sein
					$message .= $this->lang->translate('<p>The field <b>{{ field }}</b> must be filled out.</p>', 
					        array('field' => $kitContactInterface->field_array [$field_name]));
					$checked = false;
				} elseif ($field_name == kitContactInterface::kit_email) {
				    // check email address
				    if (isset($_REQUEST[kitContactInterface::kit_email_retype]) && 
				            ($_REQUEST[kitContactInterface::kit_email] != $_REQUEST[kitContactInterface::kit_email_retype])) {
				        // comparing email and retyped email address failed ...
				        unset($_REQUEST[kitContactInterface::kit_email_retype]);
				        $message .= $this->lang->translate('<p>The email address and the retyped email address does not match!</p>');
				        $checked = false;
				    }
				    elseif (!$kitLibrary->validateEMail($_REQUEST[kitContactInterface::kit_email])) {
				        // checking email address failed ...
				        unset($_REQUEST[kitContactInterface::kit_email_retype]);
				        $message .= $this->lang->translate('<p>The email address <b>{{ email }}</b> is not valid, please check your input.</p>',
				                array('email' => $_REQUEST [kitContactInterface::kit_email]));
				        $checked = false;
				    }
				}
			} else {
				// freie Datenfelder
				$where = array (dbKITformFields::field_id => $must_id );
				$field = array ();
				if (! $dbKITformFields->sqlSelectRecord ( $where, $field )) {
					$this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError ()) );
					return false;
				}
				if (count ( $field ) < 1) {
				    continue;
				    /**
				     * @todo only a workaround ...
				     */
					//$this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__, sprintf ( kit_error_invalid_id, $must_id ) ));
					//return false;
				}
				$field = $field [0];
				$field_name = $field[dbKITformFields::field_name];
				if ($field[dbKITformFields::field_type] == dbKITformFields::type_file) {
				    // file upload?
				    continue;
				}
				elseif (!isset($_REQUEST[$field_name]) || empty($_REQUEST[$field_name])) {
					// Feld muss gesetzt sein
					$message .= $this->lang->translate('<p>The field <b>{{ field }}</b> must be filled out.</p>',
					        array('field' => $field [dbKITformFields::field_title]));
					$checked = false;
				} else {
					// erweiterte Pruefung
					switch ($field [dbKITformFields::field_data_type]) :
						case dbKITformFields::data_type_date :
							if (false === ($timestamp = strtotime ( $_REQUEST [$field_name] ))) {
								$message .= $this->lang->translate('<p><b>{{ value }}</b> is not a valid date, please check your input!</p>',
								        array('value' => $_REQUEST [$field_name]));
								$checked = false;
							}
							break;
						default :
						    // alle anderen Datentypen ohne Pruefung...
					endswitch;
				}
			}
		} // foreach
		
		// file upload?
		$uploaded_files = array();
		$uploaded_files['count'] = 0;
		$file_array = array();
		
		if ($checked) {
    		$where = array(
    		        dbKITformFields::field_form_id => $form_id,
    		        dbKITformFields::field_type => dbKITformFields::type_file);
    		if (!$dbKITformFields->sqlSelectRecord($where, $file_array)) {
    		    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
    		    return false;
    		}
    		
    		if (count($file_array) > 0) {
    		    // check the protected upload directory
    		    $upload_path = WB_PATH.MEDIA_DIRECTORY.DIRECTORY_SEPARATOR.self::PROTECTION_FOLDER;
    		    if (!file_exists($upload_path)) {
    		        if (!mkdir($upload_path, 0755, true)) {
    		            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
    		                    $this->lang->translate('Error creating the directory <b>{{ directory }}</b>.', array('directory' => $upload_path))));
    		            return false;
    		        }
    		    }
    		    // check if .htaccess and .htpasswd exists...
    		    if (!file_exists($upload_path.DIRECTORY_SEPARATOR.'.htaccess') || !file_exists($upload_path.DIRECTORY_SEPARATOR.'.htpasswd')) {
    		        if (!$this->createProtection()) return false;
    		    }
    		    
    		    // get the email address
    	        $email = $_REQUEST[kitContactInterface::kit_email];
    	        // the user directory for this upload - the directory will be created when moving the uploaded file!
    	        $upload_path .= DIRECTORY_SEPARATOR.self::CONTACTS_FOLDER.DIRECTORY_SEPARATOR.$email[0].DIRECTORY_SEPARATOR.$email.DIRECTORY_SEPARATOR.self::USER_FOLDER.DIRECTORY_SEPARATOR.date('ymd-His').DIRECTORY_SEPARATOR;		    		    
    		}
    		
    		foreach ($file_array as $file) {
    		    parse_str($file[dbKITformFields::field_type_add], $settings);
    		    $method = $settings['upload_method']['value'];
    		    if ($method == 'standard') {
        		    // method: standard - check the file uploads
        		    if (!isset($_FILES[$file[dbKITformFields::field_name]]) && (in_array($file[dbKITformFields::field_id], $must_array))) {
        		        // file upload is a MUST field
        		        $message .= $this->lang->translate('<p>The field <b>{{ field }}</b> must be filled out.</p>',
        		                array('field' => $kitContactInterface->field_array [$field_name]));
        		        $checked = false;
        		        // go ahead...
        		        continue;
        		    }
        		    if (isset($_FILES[$file[dbKITformFields::field_name]]) && (is_uploaded_file($_FILES[$file[dbKITformFields::field_name]]['tmp_name']))) {
        		        // file was uploaded with the standard method
        		        if ($_FILES[$file[dbKITformFields::field_name]]['error'] == UPLOAD_ERR_OK) {
        		            // upload without error
        		            if ($checked) {
            		            $fext = explode('.', $_FILES[$file[dbKITformFields::field_name]]['name']);
            		            // file extension
            					$ext = strtolower(end($fext));
            					if (in_array($ext, $this->general_excluded_extensions)) {
            						// disallowed file or filetype - delete uploaded file
            						$message .= $this->lang->translate('<p>The file {{ file }} is member of a blacklist or use a disallowed file extension.</p>',
            						        array('file' => basename($_FILES[$file[dbKITformFields::field_name]]['name'])));
            						$checked = false;
            					}
            					// get the settings for this file
            					parse_str($file[dbKITformFields::field_type_add], $settings);
            					if (!empty($settings['file_types'])) {
            					    $ext_array = explode(',', $settings['file_types']['value']);
            					    if (!in_array($ext, $ext_array)) {
            					        $message .= $this->lang->translate('<p>Please upload only files with the extension <b>{{ extensions }}</b>, the file {{ file }} is refused.</p>',
            					                array('extensions' => implode(', ', $ext_array), 'file' => basename($_FILES[$file[dbKITformFields::field_name]]['name'])));
            					        $checked = false;
            					    }
            					}
            					if ($_FILES[$file[dbKITformFields::field_name]]['size'] > ($settings['max_file_size']['value']*1024*1024)) {
            					    $message .= $this->lang->translate('<p>The file size exceeds the limit of {{ size }} MB.</p>',
            					            array('size' => $settings['max_file_size']));
            					    $checked = false;
            					}
        		            }
        					if (!$checked) {
        					    // not checked - delete the file and continue
        					    @unlink($_FILES[$file[dbKITformFields::field_name]]['tmp_name']);
        					    continue;
        					}
        					// now create the directory
        					if (!file_exists($upload_path)) {
        					    if (!mkdir($upload_path, 0755, true)) {
        					        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
        					                $this->lang->translate('Error creating the directory <b>{{ directory }}</b>.', array('directory' => $upload_path))));
        					        return false;
        					    }
        					}
        					$mf = media_filename($_FILES[$file[dbKITformFields::field_name]]['name']);
        					if (!move_uploaded_file($_FILES[$file[dbKITformFields::field_name]]['tmp_name'], $upload_path.$mf)) {
        					    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, 
        					            $this->lang->translate('Error moving the file <b>{{ file }}</b> to the target directory!',
        					                    array('file' => $_FILES[$file[dbKITformFields::field_name]]['name']))));
        					    return false;
        					}
        					// create dbKITdirList entry
        					$data = array(
        					        dbKITdirList::field_count => 0,
        					        dbKITdirList::field_date => date('Y-m-d H:i:s'),
        					        dbKITdirList::field_file => $mf,
        					        dbKITdirList::field_path => $upload_path.$mf,
        					        dbKITdirList::field_user => $email
        					        );
        					$file_id = -1;
        					if (!$dbKITdirList->sqlInsertRecord($data, $file_id)) {
        					    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITdirList->getError()));
        					    return false;
        					}
        					// add $file_id to the uploaded files ...
        					$uploaded_files['count'] += 1;
        					$uploaded_files['items'][$file_id] = array(
        					        'id' => $file_id,
        					        'name' => $mf,
        					        'name_origin' => $_FILES[$file[dbKITformFields::field_name]]['name'],
        					        'size' => $_FILES[$file[dbKITformFields::field_name]]['size'],
        					        'path' => substr($upload_path.$mf, strlen(WB_PATH)),
        					        'download' => WB_URL.'/modules/kit/kdl.php?id='.$file_id
        					        );
        					$contact_id = -1;
        					if (!$kitContactInterface->isEMailRegistered($email, $contact_id)) {
        					    if ($kitContactInterface->isError()) {
        					        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
        					        return false;
        					    }
        					    // contact does not exists
        					    $data = array(
        					            kitContactInterface::kit_email => $email
        					            );
        					    if (!$kitContactInterface->addContact($data, $contact_id)) {
        					        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
        					        return false;
        					    }
        					}
        					// add notice to KIT
        					$kitContactInterface->addNotice($contact_id, 
        					        $this->lang->translate('[kitForm] File <a href="{{ link }}">{{ file }}</a> uploaded.', 
        					                array('link' => WB_URL.'/modules/kit/kdl.php?id='.$file_id, 'file' => $mf)));
        					
        		        }
        		        else {
        		            // handling upload errors
        		            switch ($_FILES[$file[dbKITformFields::field_name]]['error']):
        		            case UPLOAD_ERR_INI_SIZE:
        		                $message .= $this->lang->translate('<p>The file size exceeds the php.ini directive "upload_max_size" <b>{{ size }}</b>.</p>',
        		                        array('size' => ini_get('upload_max_filesize')));
        		                $checked = false;
        		                break;
        		            case UPLOAD_ERR_PARTIAL:
        		                $message .= $this->lang->translate('<p>The file <b>{{ file }}</b> was uploaded partial.</p>',
        		                        array('file' => $_FILES[dbKITformFields::field_name]['name']));
        		                $checked = false;
        		                break;
        		            default:
        		                $message .= $this->lang->translate('<p>Unspecified error, no description available.</p>');
        		                $checked = false;
        		            endswitch;
        		            // delete temporary file 
        		            @unlink($_FILES[$file[dbKITformFields::field_name]]['tmp_name']);
        		        }
        		    }
    		    } // method: standard
    		    else {
    		        // method: uploadify
    		        if (isset($_REQUEST['upload_delete']) && !empty($_REQUEST['upload_delete']) && isset($_REQUEST['upload_id'])) {
    		            // if 'upload_delete' isset and not empty the file was uploaded and then deleted
    		            $where = array(
    		                    dbKITdirList::field_reference => $_REQUEST['upload_id'],
    		                    dbKITdirList::field_file_origin => $_REQUEST['upload_delete']
    		                    );
    		            if (!$dbKITdirList->sqlDeleteRecord($where)) {
    		                $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITdirList->getError()));
    		                return false;
    		            }
    		            $message .= $this->lang->translate('<p>The file <b>{{ file }}</b> was deleted.<p>', array('file' => $_REQUEST['upload_delete']));
    		        }
    		        if (isset($_REQUEST['upload_id'])) {
    		            $where = array(
    		                    dbKITdirList::field_reference => $_REQUEST['upload_id']
    		                    );
    		            $uploads = array();
    		            if (!$dbKITdirList->sqlSelectRecord($where, $uploads)) {
    		                $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITdirList->getError()));
    		                return false;
    		            }
    		            if ((count($uploads) < 1) && (in_array($file[dbKITformFields::field_id], $must_array))) {
    		                // file upload is a MUST field
    		                $message .= $this->lang->translate('<p>The field <b>{{ field }}</b> must be filled out.</p>',
    		                        array('field' => $kitContactInterface->field_array [$field_name]));
    		                $checked = false;
    		                // go ahead...
    		                continue;
    		            }
    		            foreach ($uploads as $upload) {
    		                // now create the directory
        					if (!file_exists($upload_path)) {
        					    if (!mkdir($upload_path, 0755, true)) {
        					        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
        					                $this->lang->translate('Error creating the directory <b>{{ directory }}</b>.', array('directory' => $upload_path))));
        					        return false;
        					    }
        					}
        					// move the uploads from temporary directory to the target directory
    		                if (!rename($upload[dbKITdirList::field_path], $upload_path.$upload[dbKITdirList::field_file])) {
    		                    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, 		                            
    		                            $this->lang->translate('Error moving file <b>{{ file_origin }}</b> to <b>{{ file_target }}</b>.',
    		                                    array('file_origin' => $upload[dbKITdirList::field_path], 'file_target' => $upload_path.$upload[dbKITdirList::field_file]))));
    		                    return false;
    		                }
    		                // delete the temporary directory if empty
    		                if ($this->isDirectoryEmpty($upload_path)) {
    		                    @unlink($upload_path);
    		                }
    		                $data = array(
    		                        dbKITdirList::field_path => $upload_path.$upload[dbKITdirList::field_file],
    		                        dbKITdirList::field_user => $email
    		                        );
    		                $where = array(
    		                        dbKITdirList::field_id => $upload[dbKITdirList::field_id]
    		                        );
    		                if (!$dbKITdirList->sqlUpdateRecord($data, $where)) {
    		                    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITdirList->getError()));
    		                    return false;
    		                }
    		                // add $file_id to the uploaded files ...
    		                $uploaded_files['count'] += 1;
    		                $uploaded_files['items'][$upload[dbKITdirList::field_id]] = array(
    		                        'id' => $upload[dbKITdirList::field_id],
    		                        'name' => $upload[dbKITdirList::field_file],
    		                        'name_origin' => $upload[dbKITdirList::field_file_origin],
    		                        'size' => filesize($upload_path.$upload[dbKITdirList::field_file]),
    		                        'path' => substr($upload_path.$upload[dbKITdirList::field_file], strlen(WB_PATH)),
    		                        'download' => WB_URL.'/modules/kit/kdl.php?id='.$upload[dbKITdirList::field_id]
    		                );
    		                $contact_id = -1;
    		                if (!$kitContactInterface->isEMailRegistered($email, $contact_id)) {
    		                    if ($kitContactInterface->isError()) {
    		                        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
    		                        return false;
    		                    }
    		                    // contact does not exists
    		                    $data = array(
    		                            kitContactInterface::kit_email => $email
    		                    );
    		                    if (!$kitContactInterface->addContact($data, $contact_id)) {
    		                        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
    		                        return false;
    		                    }
    		                }
    		            }
    		        }
    		    }
    		} // foreach
		} // if checked
    		
		if ($checked) {
			// Daten sind ok und koennen uebernommen werden 
			
			// Sonderfall: Newsletter Dialog
			if ($form[dbKITform::field_action] == dbKITform::action_newsletter) return $this->subscribeNewsletter($form);
			
			$password_changed = false;
			$password = '';
			$contact_array = array ();
			$field_array = $kitContactInterface->field_array;
			$field_array [kitContactInterface::kit_intern] = ''; // Feld fuer internen Verteiler hinzufuegen 
			foreach ( $field_array as $key => $value ) {
				switch ($key) :
					case kitContactInterface::kit_zip_city :
						// nothing to do...
						break;
					case kitContactInterface::kit_newsletter :
						if (isset ( $_REQUEST [$key] )) {
							if (is_array ( $_REQUEST [$key] )) {
								$contact_array [$key] = implode ( ',', $_REQUEST [$key] );
							} else {
								$contact_array [$key] = $_REQUEST [$key];
							}
						}
						break;
					case kitContactInterface::kit_password :
						// kit_password wird ignoriert
						break;
					case kitContactInterface::kit_password_retype :
						if ((isset($_REQUEST[$key]) && !empty($_REQUEST[$key])) && 
						    (isset($_REQUEST[kitContactInterface::kit_password]) && 
						    !empty($_REQUEST[kitContactInterface::kit_password]))) {
							// nur pruefen, wenn beide Passwortfelder gesetzt sind
							if (!$kitContactInterface->changePassword($_SESSION[kitContactInterface::session_kit_aid], 
							        $_SESSION[kitContactInterface::session_kit_contact_id], 
							        $_REQUEST[kitContactInterface::kit_password], 
							        $_REQUEST[kitContactInterface::kit_password_retype] )) {
								// Fehler beim Aendern des Passwortes
								unset ( $_REQUEST [kitContactInterface::kit_password] );
								unset ( $_REQUEST [kitContactInterface::kit_password_retype] );
								if ($kitContactInterface->isError ()) {
									$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
									return false;
								}
								$message .= $kitContactInterface->getMessage();
								break;
							} else {
								// Passwort wurde geaendert
								$password_changed = true;
								$password = $_REQUEST[kitContactInterface::kit_password];
								unset($_REQUEST [kitContactInterface::kit_password]);
								unset($_REQUEST [kitContactInterface::kit_password_retype]);
								$message .= $kitContactInterface->getMessage();
								break;
							}
						}
						break;
					default :
						if (isset($_REQUEST[$key]))
							$contact_array[$key] = $_REQUEST[$key];
						break;
				endswitch;
			}
			
			if ($form [dbKITform::field_action] == dbKITform::action_register) {
				// es handelt sich um einen Registrierdialog, die weitere Bearbeitung an 
				// $this->registerAccount() uebergeben
				return $this->registerAccount ( $form, $contact_array );
			} elseif ($form [dbKITform::field_action] == dbKITform::action_account) {
				// Es wird das Benutzerkonto bearbeitet
				if (! $kitContactInterface->updateContact ( $_SESSION [kitContactInterface::session_kit_contact_id], $contact_array )) {
					$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
					return false;
				}
				if ($password_changed) {
					// Passwort wurde geaendert, E-Mail Bestaetigung versenden 
					$form['subject'] = $form[dbKITform::field_title];
					$data = array ('contact' => $contact_array, 'password' => $password, 'form' => $form );
					$provider_data = array ();
					if (! $kitContactInterface->getServiceProviderByID ( $form [dbKITform::field_provider_id], $provider_data )) {
						if ($kitContactInterface->isError ()) {
							$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
						} else {
							$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage () ) );
						}
						return false;
					}
					$provider_email = $provider_data ['email'];
					$provider_name = $provider_data ['name'];
					
					$client_mail = $this->getTemplate ( 'mail.client.password.htt', $data );
					if ($form [dbKITform::field_email_html] == dbKITform::html_off) $client_mail = strip_tags($client_mail);
					$client_subject = strip_tags($this->getTemplate('mail.client.subject.htt', $data));
					
					$mail = new kitMail ( $form [dbKITform::field_provider_id] );
					if (! $mail->mail ( $client_subject, $client_mail, $provider_email, $provider_name, array ($contact_array [kitContactInterface::kit_email] => $contact_array [kitContactInterface::kit_email] ), false )) {
						$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, 
						        $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array('email' => $contact_array[kitContactInterface::kit_email]))));
						return false;
					}
				
				}
				// Mitteilung, dass das Benutzerkonto aktualisiert wurde
				if (empty ( $message ))
					$message = $this->lang->translate('<p>The user account was updated.</p>');
				$this->setMessage ( $message );
				return $this->showForm ();
			}
			$contact_id = -1;
			$status = '';
			if ($kitContactInterface->isEMailRegistered ( $_REQUEST [kitContactInterface::kit_email], $contact_id, $status )) {
				// E-Mail Adresse existiert bereits, Datensatz ggf. aktualisieren
				if (! $kitContactInterface->updateContact ( $contact_id, $contact_array )) {
					$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
					return false;
				}
			} elseif ($kitContactInterface->isError ()) {
				// Fehler bei der Datenbankabfrage
				$this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ));
				return false;
			} else {
				// E-Mail Adresse ist noch nicht registriert
				if (! $kitContactInterface->addContact ( $contact_array, $contact_id )) {
					$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
					return false;
				}
			}
			
			// Kontakt Datensatz ist erstellt oder aktualisiert, allgemeine Daten uebernehmen und E-Mails versenden
			$fields = array ();
			$values = array ();
			$fields_array = explode ( ',', $form [dbKITform::field_fields] );
			foreach ( $fields_array as $fid ) {
				if ($fid > 99)
					$fields [] = $fid;
			}
			
			// DELAYED TRANSMISSION ?
			$delayed_transmission = (isset($_REQUEST[dbKITformFields::kit_delayed_transmission])) ? true : false;
			
			foreach ($fields as $fid) {
				$where = array(dbKITformFields::field_id => $fid );
				$field = array();
				if (!$dbKITformFields->sqlSelectRecord($where, $field)) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
					return false;
				}
				if (count($field) < 1) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
					return false;
				}
				$field = $field [0];
				switch ($field [dbKITformFields::field_data_type]) :
			    case dbKITformFields::data_type_date :
				    $values[$fid] = (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? date('Y-m-d H:i:s', strtotime($_REQUEST[$field[dbKITformFields::field_name]])) : '0000-00-00 00:00:00';
					break;
				case dbKITformFields::data_type_float :
					$values[$fid] = (isset($_REQUEST[$field[dbKITformFields::field_name]] )) ? $kitLibrary->str2float($_REQUEST[$field[dbKITformFields::field_name]], cfg_thousand_separator, cfg_decimal_separator) : 0;
					break;
				case dbKITformFields::data_type_integer :
				    $values[$fid] = (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? $kitLibrary->str2int($_REQUEST[$field[dbKITformFields::field_name]], cfg_thousand_separator, cfg_decimal_separator) : 0;
					break;
				default :
					$values[$fid] = (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? $_REQUEST [$field[dbKITformFields::field_name]] : '';
					break;
				endswitch;
			}
			$form_data = array (
			        dbKITformData::field_form_id => $form_id, 
			        dbKITformData::field_kit_id => $contact_id, 
			        dbKITformData::field_date => date('Y-m-d H:i:s'), 
			        dbKITformData::field_fields => implode(',', $fields), 
			        dbKITformData::field_values => http_build_query($values),
			        dbKITformData::field_status => $delayed_transmission ? dbKITformData::status_delayed : dbKITformData::status_active
			        );
			$data_id = -1;
			if (!$dbKITformData->sqlInsertRecord($form_data, $data_id)) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError () ) );
				return false;
			}
			
			/*
			 * check for special actions by field names, i.e. Feedback Form...
			*/
			$is_feedback_form = false;
			$SQL = sprintf("SELECT %s FROM %s WHERE %s='%s' AND %s='%s'",
			        dbKITformFields::field_id,
			        $dbKITformFields->getTableName(),
			        dbKITformFields::field_form_id,
			        $form_id,
			        dbKITformFields::field_name,
			        self::FIELD_FEEDBACK_TEXT);
			$result = array();
			if (!$dbKITformFields->sqlExec($SQL, $result)) {
			    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
			    return false;
			}
			if (count($result) == 1) {
			    // exec special action: Feedback for the Website
			    $is_feedback_form = true;
			}
			// end: special actions
			
			/**
			 * Leave here at delayed transmission
			 */
			if ($delayed_transmission) return $this->delayedTransmission($data_id, $contact_id);
			
			
			// ok - Daten sind gesichert, vorab LOG schreiben
			if ($is_feedback_form) {
			    $protocol = $this->lang->translate('[kitForm] The contact has <a href="{{ url }}">submitted a feedback</a>',
			            array('url' => sprintf('%s&%s',
			                    ADMIN_URL . '/admintools/tool.php?tool=kit_form',
			                    http_build_query(array(
			                            formBackend::request_action => formBackend::action_protocol_id, 
			                            formBackend::request_protocol_id => $data_id
			                            ))
			                    )));
			}
			else {
			    $protocol = $this->lang->translate('[kitForm] The contact has <a href="{{ url }}">submitted a form</a>.',
			            array('url' => sprintf('%s&%s',
			                    ADMIN_URL . '/admintools/tool.php?tool=kit_form',
			                    http_build_query(array(
			                            formBackend::request_action => formBackend::action_protocol_id,
			                            formBackend::request_protocol_id => $data_id
			                    ))
			            )));
			}
			$dbContact->addSystemNotice($contact_id, $protocol);

			if (isset($uploaded_files['items'])) {
    			foreach ($uploaded_files['items'] as $file) {
    			    // add a system notice for each file
    			    $kitContactInterface->addNotice($contact_id,
    			            $this->lang->translate('[kitForm] File <a href="{{ link }}">{{ file }}</a> uploaded.',
    			                    array('link' => WB_URL.'/modules/kit/kdl.php?id='.$file['id'], 'file' => $file['name'])));
    			}
			}
			
			$contact = array();
			if (! $kitContactInterface->getContact ( $contact_id, $contact )) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
				return false;
			}
			
			if ($this->params [self::param_return] == true) {
				// direkt zum aufrufenden Programm zurueckkehren
				$result = array ('contact' => $contact, 'result' => true );
				return $result;
			}
			
			// Feedback Form? Leave here...
			if ($is_feedback_form) return $this->checkFeedbackForm($form_data, $contact, $data_id);
			
			$items = array ();
			foreach ( $fields as $fid ) {
				$where = array (dbKITformFields::field_id => $fid );
				$field = array ();
				if (! $dbKITformFields->sqlSelectRecord ( $where, $field )) {
					$this->setError (sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError ()) );
					return false;
				}
				if (count ( $field ) < 1) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
					        $this->lang->translate('The ID {{ id }} is invalid!', array('id' => $fid))));
					return false;
				}
				$field = $field [0];
				switch ($field [dbKITformFields::field_data_type]) :
					case dbKITformFields::data_type_date :
						$value = date ( cfg_datetime_str, $values [$fid] );
						break;
					case dbKITformFields::data_type_float :
						$value = number_format ( $values [$fid], 2, cfg_decimal_separator, cfg_thousand_separator );
						break;
					case dbKITformFields::data_type_integer :
					case dbKITformFields::data_type_text :
					default :
						$value = (is_array ( $values [$fid] )) ? implode ( ', ', $values [$fid] ) : $values [$fid];
						//$items = (is_array($values[$fid])) ? $values[$fid] : array();
				endswitch;
				$items [$field [dbKITformFields::field_name]] = array(
				        'label' => $field[dbKITformFields::field_title], 
				        'value' => $value,
				        'type' => $field[dbKITformFields::field_type]
				        );
			}
			
			// E-Mail Versand vorbereiten
			$provider_data = array ();
			if (! $kitContactInterface->getServiceProviderByID ( $form [dbKITform::field_provider_id], $provider_data )) {
				if ($kitContactInterface->isError ()) {
					$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
				} else {
					$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage () ) );
				}
				return false;
			}
			$provider_email = $provider_data ['email'];
			$provider_name = $provider_data ['name'];
			
			$form_d = $form_data;
			$form_d['datetime'] = date(cfg_datetime_str, strtotime($form_d[dbKITformData::field_date]));
			$form_d['subject'] = $form[dbKITform::field_title];
			
			$data = array (
			        'form' => $form_d, 
			        'contact' => $contact, 
			        'items' => $items,
			        'files' => $uploaded_files
			        );
			
			$client_mail = $this->getTemplate ( 'mail.client.htt', $data );
			if ($form[dbKITform::field_email_html] == dbKITform::html_off) $client_mail = strip_tags($client_mail);
			$client_subject = strip_tags($this->getTemplate('mail.client.subject.htt', $data));
			
			// E-Mail an den Absender des Formulars
			$mail = new kitMail ( $form [dbKITform::field_provider_id] );
			if (! $mail->mail ( $client_subject, $client_mail, $provider_email, $provider_name, array ($contact [kitContactInterface::kit_email] => $contact [kitContactInterface::kit_email] ), ($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false )) {
				$err = $mail->getMailError ();
				if (empty ( $err ))
					$err = $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array('email' => $contact[kitContactInterface::kit_email]));
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $err ) );
				return false;
			}
			// E-Mail an den Betreiber der Website
			$provider_mail = $this->getTemplate ( 'mail.provider.htt', $data );
			if ($form[dbKITform::field_email_html] == dbKITform::html_off) $provider_mail = strip_tags($provider_mail);
			$provider_subject = stripslashes($this->getTemplate('mail.provider.subject.htt', $data));
			
			$cc_array = array ();
			$ccs = explode ( ',', $form [dbKITform::field_email_cc] );
			foreach ( $ccs as $cc ) {
				if (!empty($cc)) $cc_array [$cc] = $cc;
			}
			$mail = new kitMail ( $form [dbKITform::field_provider_id] );
			if (! $mail->mail ( $provider_subject, $provider_mail, $contact [kitContactInterface::kit_email], $contact [kitContactInterface::kit_email], array ($provider_email => $provider_name ), ($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false, $cc_array )) {
				$err = $mail->getMailError ();
				if (empty ( $err ))
					$err = $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array('email' => $contact[kitContactInterface::kit_email]));
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $err ) );
				return false;
			}
			return $this->getTemplate ( 'confirm.htt', $data );
		} // checked
		

		if ($checked == false) {
			if (isset ( $_REQUEST [kitContactInterface::kit_password] ))
				unset ( $_REQUEST [kitContactInterface::kit_password] );
			if (isset ( $_REQUEST [kitContactInterface::kit_password_retype] ))
				unset ( $_REQUEST [kitContactInterface::kit_password_retype] );
		}
		else {
		    unset($_REQUEST['upload_id']);
		}
		
		$this->setMessage ( $message );
		return $this->showForm ();
	} // checkForm()
	
	/**
	 * Process a form as delayed transmission, confirm it to the user, send a
	 * email with link to edit the form and write the protocol
	 * 
	 * @param integer $data_id
	 * @param integer $contact_id
	 * @return string confirm message or boolean false on error
	 */
	protected function delayedTransmission($data_id, $contact_id) {
	    global $kitContactInterface;
	    global $dbKITformData;
	    global $dbKITform;
	    global $dbKITformCommands;
	    global $kitLibrary;
	    
	    $contact = array();
	    if (!$kitContactInterface->getContact($contact_id, $contact)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
	        return false;
	    }
	    
	    $form_data = array();
	    $where = array(
	            dbKITformData::field_id => $data_id
	            );
	    if (!$dbKITformData->sqlSelectRecord($where, $form_data)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
	        return false;
	    }
	    $form_data = $form_data[0];

	    $form = array();
	    $where = array(
	            dbKITform::field_id => $form_data[dbKITformData::field_form_id]
	            );
	    if (!$dbKITform->sqlSelectRecord($where, $form)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
	        return false;
	    }
	    $form = $form[0];
	    	    
	    // write log file
	    $protocol = $this->lang->translate('[kitForm] The contact has saved a form for later transmission (ID {{ id }}).', array('id' => $data_id));
	    $kitContactInterface->addNotice($contact_id, $protocol);
	    
	    // create command
	    $cmd_delayed = $kitLibrary->createGUID();
	    
	    $data = array(
	            dbKITformCommands::FIELD_COMMAND => $cmd_delayed,
	            dbKITformCommands::FIELD_PARAMS => http_build_query($form_data),
	            dbKITformCommands::FIELD_TYPE => dbKITformCommands::TYPE_DELAYED_TRANSMISSION,
	            dbKITformCommands::FIELD_STATUS => dbKITformCommands::STATUS_WAITING
	    );
	    if (!$dbKITformCommands->sqlInsertRecord($data)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformCommands->getError()));
	        return false;
	    }
	    
	    // gather the data for displaying and sending the confirmation to the client 
	    $data = array(
	            'form' => array(
	                    'subject' => $form[dbKITform::field_title],
	                    'title' => $form[dbKITform::field_title],
	                    'link' => sprintf('%s?%s#%s',
		                        $this->page_link,
		                        http_build_query(array(
		                                self::request_action => self::action_command,
		                                self::request_command => $cmd_delayed
		                                )),
		                        self::FORM_ANCHOR
		                        )
	                    ),
	            'contact' => $contact
	            );
	    
	    // get the provider data
	    $provider_data = array ();
	    if (!$kitContactInterface->getServiceProviderByID($form[dbKITform::field_provider_id], $provider_data)) {
	        if ($kitContactInterface->isError()) {
	            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
	        } else {
	            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage()));
	        }
	        return false;
	    }
	    
	    // send the mail to the client
	    $client_mail = $this->getTemplate('mail.client.delayed.transmission.htt', $data);
	    if ($form[dbKITform::field_email_html] == dbKITform::html_off) $client_mail = strip_tags($client_mail);
	    $client_subject = strip_tags($this->getTemplate('mail.client.subject.htt', $data));
	    
	    // send email to the client
	    $mail = new kitMail($form[dbKITform::field_provider_id]);
	    if (!$mail->mail($client_subject, $client_mail, $provider_data ['email'], $provider_data ['email'], 
	            array($contact[kitContactInterface::kit_email] => $contact[kitContactInterface::kit_email]), 
	            ($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false )) {
	        // get the error information
	        $err = $mail->getMailError();
	        if (empty($err))
	            // if no description available set general fault message
	            $err = $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', 
	                    array('email' => $contact[kitContactInterface::kit_email]));
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $err));
	        return false;
	    }
	    
	    return $this->getTemplate('confirm.delayed.transmission.htt', $data);
	} // delayedTransmission()
	
	/**
	 * Check if a directory is empty or not
	 * 
	 * @param string $directory
	 * @return boolean
	 */
	protected function isDirectoryEmpty($directory) {
	    // if directory not exists return true...
	    if (!file_exists($directory)) return true;
	    // get a handle
	    if (false === ($handle = @opendir($directory))) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, 
	                $this->lang->translate('Can\'t open the directory <b>{{ directory }}</b>!', array('directory' => $directory))));
	        return false;
	    }	    
	    // read directory
	    while(false !== ($f = readdir($handle))) {
	        // . and .. exists always!
	        if ($f == "." || $f == "..") { 
	            continue;
	        } 
	        else {
	            // directory is not empty
	            closedir($handle);
	            return false;
	        } 
	    }  
	    closedir($handle);
	    return true;
	} // isDirectoryEmpty()
	
	/**
	 * Prueft den LOGIN und schaltet den User ggf. frei
	 * 
	 * @param array $form_data
	 * @return boolean true on success BOOL false on program error STR dialog on invalid login
	 */
	protected function checkLogin($form_data = array()) {
		global $kitContactInterface;
		global $kitLibrary;
		
		if (! isset ( $_REQUEST [kitContactInterface::kit_email] ) || ! isset ( $_REQUEST [kitContactInterface::kit_password] )) {
			$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The datafields for the email address and/or the password are empty, please check!')));
			return false;
		}
		if (! $kitLibrary->validateEMail ( $_REQUEST [kitContactInterface::kit_email] )) {
			unset ( $_REQUEST [kitContactInterface::kit_password] );
			$this->setMessage($this->lang->translate('<p>The email address <b>{{ email }}</b> is not valid, please check your input.</p>',
			        array('email' => $_REQUEST [kitContactInterface::kit_email])));
			return $this->showForm ();
		}
		$contact = array();
		$must_change_password = false;
		if ($kitContactInterface->checkLogin ( $_REQUEST [kitContactInterface::kit_email], $_REQUEST [kitContactInterface::kit_password], $contact, $must_change_password )) {
			// Login erfolgreich
			$this->setContact ( $contact );
			return true;
		} elseif ($kitContactInterface->isError ()) {
			$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
			return false;
		} else {
			// Fehler beim Login...
			unset ( $_REQUEST [kitContactInterface::kit_password] );
			$this->setMessage ( $kitContactInterface->getMessage () );
			return $this->showForm ();
		}
	} // checkLogin()
	
	/**
	 * Special form: Feedback Form
	 * Shows a thread with all comments to the desired page and a dialog
	 * for the feedback itself.
	 * All "normal" data for displaying the form are already collected and
	 * present, this function adds only the special features for displaying
	 * the feedback thread.
	 * 
	 * @param integer $form_id - ID of the used form
	 * @param array $form_data - form data, ready for parser
	 * @param array $form_fields - field data, ready for parser
	 * @return string feedback form on success or boolean false on error
	 */
	protected function showFeedbackForm($form_id, $form_data, $form_fields) {
	    global $dbKITform;
	    global $dbKITformData;
	    global $dbKITformFields;
	    global $kitLibrary;
	    
	    // get all previous data of the feedback form
	    $SQL = sprintf("SELECT * FROM %s WHERE %s='%s' ORDER BY %s ASC",
	            $dbKITformData->getTableName(),
	            dbKITformData::field_form_id,
	            $form_id,
	            dbKITformData::field_date);
	    $feedbacks = array();
	    if (!$dbKITformData->sqlExec($SQL, $feedbacks)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
	        return false;
	    }
	    
	    // get the fields of this form
	    $where = array(dbKITformFields::field_form_id => $form_id);
	    $ffields = array();
	    if (!$dbKITformFields->sqlSelectRecord($where, $ffields)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
	        return false;
	    }
	    foreach ($ffields as $ff) {
	        switch ($ff[dbKITformFields::field_name]):
	        case self::FIELD_FEEDBACK_HOMEPAGE:
	            $fb_homepage = $ff[dbKITformFields::field_id]; break;
	        case self::FIELD_FEEDBACK_NICKNAME:
	            $fb_nickname = $ff[dbKITformFields::field_id]; break;
	        case self::FIELD_FEEDBACK_PUBLISH:
	            $fb_publish = $ff[dbKITformFields::field_id]; break;
	        case self::FIELD_FEEDBACK_SUBJECT:
	            $fb_subject = $ff[dbKITformFields::field_id]; break;
	        case self::FIELD_FEEDBACK_SUBSCRIPTION:
	            $fb_subscription = $ff[dbKITformFields::field_id]; break;
	        case self::FIELD_FEEDBACK_TEXT:
	            $fb_text = $ff[dbKITformFields::field_id]; break;
	        case self::FIELD_FEEDBACK_URL:
	            $fb_url = $ff[dbKITformFields::field_id]; break;    
	        endswitch;
	    }
	    
	    $url = '';
	    if (!$kitLibrary->getUrlByPageID(PAGE_ID, $url)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('kitForm can\'t determine the URL of the calling page.')));
	        return false;
	    }
	    if (!isset($form_fields[self::FIELD_FEEDBACK_URL])) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The feedback form is not complete - missing the datafield <b>feedback_url</b>!')));
	        return false;
	    }
	    $form_fields[self::FIELD_FEEDBACK_URL]['value'] = $url; 
	    
	    $feedback_array = array();
	    foreach ($feedbacks as $feedback) {
	        parse_str($feedback[dbKITformData::field_values], $fields);
	        $publish = true;
	        if (isset($fields[$fb_publish]) && ($fields[$fb_publish] != self::PUBLISH_IMMEDIATE)) $publish = false;
	        if (!isset($fields[$fb_url])) continue;
	        if ($publish && ($fields[$fb_url] == $url)) {
	            $feedback_array[] = array(
	                    'url' => $url,
	                    'subject' => isset($fields[$fb_subject]) ? $fields[$fb_subject] : '',
	                    'text' => isset($fields[$fb_text]) ? $fields[$fb_text] : '',
	                    'homepage' => isset($fields[$fb_homepage]) ? $fields[$fb_homepage] : '',
	                    'nickname' => isset($fields[$fb_nickname]) ? $fields[$fb_nickname] : '',
	                    'date' => array(
	                            'timestamp' => $feedback[dbKITformData::field_date],
	                            'formatted' => date(cfg_datetime_str, strtotime($feedback[dbKITformData::field_date]))
	                            ),
	                    );
	        }
	    }	    
	    $data = array(
	            'feedback' => array(
	                    'items' => $feedback_array,
	                    'count' => count($feedback_array)
	                    ),
	            'form' => $form_data,
	            'fields' => $form_fields
	            );
	    return $this->getTemplate ( 'feedback.htt', $data );
	} // showFeedbackForm()
	
	/**
	 * Check the feedback form and return the submitted feedback
	 * 
	 * @param array $form_data
	 * @param array $contact_data
	 * @param integer $data_id
	 * @return string feedback form or boolean false on error
	 */
	protected function checkFeedbackForm($form_data = array(), $contact_data = array(), $data_id) {
	    global $dbKITform;
	    global $dbKITformFields;
	    global $kitContactInterface;
	    global $kitLibrary;
	    global $dbKITformData;
	    global $dbKITformCommands;
	    
	    // set FORM_ID
	    $form_id = $form_data['form_id'];
	    // set message
	    $message = '';
	    
	    // get the form itself
	    $where = array(dbKITform::field_id => $form_id);
	    $form = array();
	    if (!$dbKITform->sqlSelectRecord($where, $form)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
	        return false;
	    }
	    $form = $form[0];

	    // get the form fields
	    $where = array(dbKITformFields::field_form_id => $form_id);
	    $form_fields = array();
	    if (!$dbKITformFields->sqlSelectRecord($where, $form_fields)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
	        return false;
	    }
	    
	    foreach ($form_fields as $ff) {
	        switch ($ff[dbKITformFields::field_name]):
	        case self::FIELD_FEEDBACK_HOMEPAGE:
	            $fb_homepage = $ff[dbKITformFields::field_id]; break;
	        case self::FIELD_FEEDBACK_NICKNAME:
	            $fb_nickname = $ff[dbKITformFields::field_id]; break;
	        case self::FIELD_FEEDBACK_PUBLISH:
	            $fb_publish = $ff[dbKITformFields::field_id]; break;
	        case self::FIELD_FEEDBACK_SUBJECT:
	            $fb_subject = $ff[dbKITformFields::field_id]; break;
	        case self::FIELD_FEEDBACK_SUBSCRIPTION:
	            $fb_subscription = $ff[dbKITformFields::field_id]; break;
	        case self::FIELD_FEEDBACK_TEXT:
	            $fb_text = $ff[dbKITformFields::field_id]; break;
	        case self::FIELD_FEEDBACK_URL:
	            $fb_url = $ff[dbKITformFields::field_id]; break;
	            endswitch;
	    }
	    
	    // get the submitted data
	    $where = array(dbKITformData::field_id => $data_id);
	    $f_data = array();
	    if (!$dbKITformData->sqlSelectRecord($where, $f_data)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
	        return false;
	    }
	    $f_data = $f_data[0];
	    parse_str($f_data[dbKITformData::field_values], $values);
	    $feedback_array = array(
	            self::FIELD_FEEDBACK_HOMEPAGE => isset($fb_homepage) ? $values[$fb_homepage] : '',
	            self::FIELD_FEEDBACK_NICKNAME => isset($fb_nickname) ? $values[$fb_nickname] : '',
	            self::FIELD_FEEDBACK_PUBLISH => isset($fb_publish) ? $values[$fb_publish] : self::PUBLISH_IMMEDIATE,
	            self::FIELD_FEEDBACK_SUBJECT => isset($fb_subject) ? $values[$fb_subject] : '',
	            self::FIELD_FEEDBACK_SUBSCRIPTION => isset($fb_subscription) ? $values[$fb_subscription] : self::SUBSCRIPE_NO,
	            self::FIELD_FEEDBACK_TEXT => isset($fb_text) ? $values[$fb_text] : '',
	            self::FIELD_FEEDBACK_URL => isset($fb_url) ? $values[$fb_url] : ''
	            );
	  	    
		// prepare sending emails
		$provider_data = array ();
		if (!$kitContactInterface->getServiceProviderByID($form[dbKITform::field_provider_id], $provider_data )) {
			if ($kitContactInterface->isError()) {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
			} 
			else {
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage()));
			}
			return false;
		}
		$provider_email = $provider_data['email'];
		$provider_name = $provider_data['name'];
		
		// create and save commands
		$cmd_publish = $kitLibrary->createGUID();
		$cmd_refuse = $kitLibrary->createGUID();
		
		$data = array(
		        dbKITformCommands::FIELD_COMMAND => $cmd_publish,
		        dbKITformCommands::FIELD_PARAMS => http_build_query(array(
		                'form' => $form, 
		                'contact' => $contact_data,
		                'data_id' => $data_id)),
		        dbKITformCommands::FIELD_TYPE => dbKITformCommands::TYPE_FEEDBACK_PUBLISH,
		        dbKITformCommands::FIELD_STATUS => dbKITformCommands::STATUS_WAITING
		        );
		if (!$dbKITformCommands->sqlInsertRecord($data)) {
		    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformCommands->getError()));
		    return false;
		}
		
		$data = array(
		        dbKITformCommands::FIELD_COMMAND => $cmd_refuse,
		        dbKITformCommands::FIELD_PARAMS => http_build_query(array(
		                'form' => $form,
		                'contact' => $contact_data,
		                'data_id' => $data_id)),
		        dbKITformCommands::FIELD_TYPE => dbKITformCommands::TYPE_FEEDBACK_REFUSE,
		        dbKITformCommands::FIELD_STATUS => dbKITformCommands::STATUS_WAITING
		);
		if (!$dbKITformCommands->sqlInsertRecord($data)) {
		    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformCommands->getError()));
		    return false;
		}
		
		// send E-Mail to the feedback author
		$data = array(
		        'feedback' => array(
		                'field' => $feedback_array,
		                'unsubscribe_link' => sprintf('%s?%s#%s',
		                        $this->page_link,
		                        http_build_query(array(
		                                self::request_action => self::action_feedback_unsubscribe,
		                                self::request_form_id => $form_id)),
		                        self::FORM_ANCHOR)
		                ),
		        'contact' => $contact_data,
		        'command' => array(
		                'publish_feedback' => sprintf('%s?%s#%s',
		                        $this->page_link,
		                        http_build_query(array(
		                                self::request_action => self::action_command,
		                                self::request_command => $cmd_publish
		                                )),
		                        self::FORM_ANCHOR
		                        ),
		                'refuse_feedback' => sprintf('%s?%s#%s',
		                        $this->page_link,
		                        http_build_query(array(
		                                self::request_action => self::action_command,
		                                self::request_command => $cmd_refuse
		                                )),
		                        self::FORM_ANCHOR
		                        ),
		                ),
		        );

		$client_mail = $this->getTemplate('mail.feedback.author.submit.htt', $data );
		if ($form[dbKITform::field_email_html] == dbKITform::html_off) $client_mail = strip_tags($client_mail);
		$client_subject = strip_tags($this->getTemplate('mail.feedback.subject.htt', array('subject' => $form[dbKITform::field_title])));
		
		// email to the feedback author
		$mail = new kitMail($form[dbKITform::field_provider_id]);
		if (!$mail->mail(
		        $client_subject, 
		        $client_mail, 
		        $provider_email, 
		        $provider_name, 
		        array($contact_data[kitContactInterface::kit_email] => $contact_data[kitContactInterface::kit_email]), 
		        ($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false)
		        ) {
		    $err = $mail->getMailError ();
		    if (empty ( $err ))
		        $err = $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array('email' => $contact_data[kitContactInterface::kit_email]));
		    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $err));
		    return false;
		}
		// Mitteilung auf der Seite
		if ($feedback_array[self::FIELD_FEEDBACK_PUBLISH] == self::PUBLISH_IMMEDIATE) {
		    $message .= $this->lang->translate('<p>Thank you for the feedback!</p><p>Your feedback is already published, we have send you a copy to your email address <b>{{ email }}</b>.</p>',
		            array('email' => $contact_data[kitContactInterface::kit_email]));
		}
		else {
		    $message .= $this->lang->translate('<p>Thank your for the feedback!</p><p>We will check and publish your feedback as soon as possible. We have send you a copy of your feedback to your email address <b>{{ email }}</b>.</p>',
		            array('email' => $contact_data[kitContactInterface::kit_email]));
		}
		
		// send email to webmaster
		$provider_mail = $this->getTemplate('mail.feedback.provider.submit.htt', $data );
		if ($form[dbKITform::field_email_html] == dbKITform::html_off) $provider_mail = strip_tags($provider_mail);
		$provider_subject = stripslashes($this->getTemplate('mail.feedback.subject.htt', array('subject' => $form[dbKITform::field_title])));
		
		$cc_array = array();
		$ccs = explode(',', $form[dbKITform::field_email_cc]);
		foreach( $ccs as $cc ) {
		    if (!empty($cc)) $cc_array[$cc] = $cc;
		}
		$mail = new kitMail($form[dbKITform::field_provider_id]);
		if (!$mail->mail(
		        $provider_subject, 
		        $provider_mail, 
		        $contact_data[kitContactInterface::kit_email], 
		        $contact_data[kitContactInterface::kit_email], 
		        array($provider_email => $provider_name ), 
		        ($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false, $cc_array 
		        )) {
		    $err = $mail->getMailError ();
		    if (empty ( $err ))
		        $err = $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array('email' => $contact_data[kitContactInterface::kit_email]));
		    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $err));
		    return false;
		}
		
		$subscriber_emails = array();
		if (isset($fb_subscription) && ($feedback_array[self::FIELD_FEEDBACK_PUBLISH] == self::PUBLISH_IMMEDIATE)) {		
    		// get subsribers ...
    		$where = array(dbKITformData::field_form_id => $form_id);
    		$sub_data = array();
    		if (!$dbKITformData->sqlSelectRecord($where, $sub_data)) {
    		    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
    		    return false;
    		}
    		foreach ($sub_data as $sub) {
    		    parse_str($sub[dbKITformData::field_values], $values);
    		    if (isset($values[$fb_subscription][0]) && ($values[$fb_subscription][0] == self::SUBSCRIPE_YES)) {
    		        $cont = array();
    		        if (!$kitContactInterface->getContact($sub[dbKITformData::field_kit_id], $cont)) {
    		            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, 
    		                    $this->lang->translate('The ID {{ id }} is invalid!', array('id' => $sub[dbKITformData::field_kit_id]))));
    		            return false;
    		        }
    		        if (!in_array($cont[kitContactInterface::kit_email], $subscriber_emails) && 
    		                ($cont[kitContactInterface::kit_email] != $contact_data[kitContactInterface::kit_email])) {
    		            $subscriber_emails[] = $cont[kitContactInterface::kit_email];
    		        }
    		    }
    		}
		}
		
		if (count($subscriber_emails) > 0) {
		    $subscriber_mail = $this->getTemplate('mail.feedback.subscriber.submit.htt', $data );
		    if ($form[dbKITform::field_email_html] == dbKITform::html_off) $subscriber_mail = strip_tags($subscriber_mail);
		    $subscriber_subject = stripslashes($this->getTemplate('mail.feedback.subject.htt', array('subject' => $form[dbKITform::field_title])));
		    
		    $bcc_array = array();
		    foreach( $subscriber_emails as $cc ) {
		        if (!empty($cc)) $bcc_array[$cc] = $cc;
		    }
		    $mail = new kitMail($form[dbKITform::field_provider_id]);
		    if (!$mail->mail(
		            $subscriber_subject,
		            $subscriber_mail,
		            $provider_email,
		            $provider_name,
		            array($provider_email => $provider_name ),
		            ($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false,
		            array(), 
		            $bcc_array
		    )) {
		        $err = $mail->getMailError ();
		        if (empty ( $err ))
		            $err = $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array('email' => $contact_data[kitContactInterface::kit_email]));
		        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $err));
		        return false;
		    }
		    
		}
		
	    // unset all $_REQUESTs for data fields to show an empty form
	    foreach ($form_fields as $ffield) unset($_REQUEST[$ffield[dbKITformFields::field_name]]);
	    // unset all contact fields
	    foreach($contact_data as $key => $value) unset($_REQUEST[$key]);
	    // set messages for the feedback author
	    $this->setMessage($message);
	    // show the feedback form again
	    return $this->showForm();
	} // checkFeedback()

	/**
	 * Show dialog to unsubscribe from feedback messages for a page
	 * 
	 * @return string dialog
	 */
	protected function showFeedbackUnsubscribe() {
	    global $kitContactInterface;
	    
	    $form_id = isset($_REQUEST[self::request_form_id]) ? $_REQUEST[self::request_form_id] : -1;
	    
	    if ($form_id < 1) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Invalid function call')));
	        return false;
	    }
	    
	    // CAPTCHA
	    ob_start ();
	    call_captcha ();
	    $call_captcha = ob_get_contents ();
	    ob_end_clean ();
	    
	    $data = array(
	            'form' => array(
	                    'title' => $this->lang->translate('Unsubscribe Feedback'),
	                    'response' => ($this->isMessage ()) ? $this->getMessage () : $this->lang->translate('Please enter your email address to unsubscribe from automatical reports at new feedbacks of this site.'),
	                    'name' => 'feedback_unsubscribe',
	                    'action' => array(
	                            'link' => $this->page_link,
	                            'name' => self::request_action,
	                            'value' => self::action_feedback_unsubscribe_check
	                    ),
	                    'anchor' => self::FORM_ANCHOR,
	                    'id' => array(
	                            'name' => self::request_form_id,
	                            'value' => $form_id
	                            ),
	                    kitContactInterface::kit_email => array(
	                            'label' => $kitContactInterface->field_array[kitContactInterface::kit_email],
	                            'name' => kitContactInterface::kit_email,
	                            'value' => '',
	                            'hint' => ''
	                            ),
	                    'btn' => array (
	                            'ok' => $this->lang->translate('OK'), 
	                            'abort' => $this->lang->translate('Abort')
	                            ), 
	                    'captcha' => array(
	                            'code' => $call_captcha)
	            ));
	    return $this->getTemplate('feedback.unsubscribe.htt', $data);
	} // showFeedbackUnsubscribe()
	
	protected function checkFeedbackUnsubscribe() {
	    global $kitLibrary;
	    global $kitContactInterface;
	    global $dbKITformData;
	    global $dbKITformFields;
	    
	    $email = isset($_REQUEST[kitContactInterface::kit_email]) ? $_REQUEST[kitContactInterface::kit_email] : '';
	    if (!$kitLibrary->validateEMail($email)) {
	       $this->setMessage($this->lang->translate('<p>The email address <b>{{ email }}</b> is not valid, please check your input.</p>',
	               array('email' => $email)));
	       return $this->showFeedbackUnsubscribe(); 
	    }
	    
	    // check CAPTCHA
	    unset($_SESSION['kf_captcha']);
	    if (!isset($_REQUEST['captcha']) || ($_REQUEST['captcha'] != $_SESSION['captcha'])) {
	        $this->setMessage($this->lang->translate('<p>The CAPTCHA code is not correct, please try again!</p>'));
	        return $this->showFeedbackUnsubscribe();
	    }
	    
	    $form_id = isset($_REQUEST[self::request_form_id]) ? $_REQUEST[self::request_form_id] : -1;
	    
	    $status = dbKITcontact::status_active;
	    $contact_id = -1;
	    if (!$kitContactInterface->isEMailRegistered($email, $contact_id, $status)) {
	        if ($kitContactInterface->isError()) {
	            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
	            return false;
	        }            
            $this->setMessage($this->lang->translate('<p>The email address <b>{{ email }}</b> is not registered.</p>', 
                    array('email' => $email)));
            return $this->showFeedbackUnsubscribe();
	    }
	    
	    // search for form datas for this user
	    $where = array(
	            dbKITformData::field_kit_id => $contact_id,
	            dbKITformData::field_form_id => $form_id
	            );
	    $form_data = array();
	    if (!$dbKITformData->sqlSelectRecord($where, $form_data)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
	        return false;
	    }
	    // get field id for feedback_subscription
	    $where = array(
	            dbKITformFields::field_form_id => $form_id,
	            dbKITformFields::field_name => self::FIELD_FEEDBACK_SUBSCRIPTION
	            );
	    $fields = array();
	    if (!$dbKITformFields->sqlSelectRecord($where, $fields)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
	        return false;
	    }
	    if (count($fields) < 1) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Invalid function call')));
	        return false;
	    }
	    $fb_subscription = $fields[0][dbKITformFields::field_id];
	    // get field id for feedback_url
	    $where = array(
	            dbKITformFields::field_form_id => $form_id,
	            dbKITformFields::field_name => self::FIELD_FEEDBACK_URL
	    );
	    $fields = array();
	    if (!$dbKITformFields->sqlSelectRecord($where, $fields)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
	        return false;
	    }
	    if (count($fields) < 1) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Invalid function call')));
	        return false;
	    }
	    $fb_url = $fields[0][dbKITformFields::field_id];
	    
	    
	    $url = '';
	    $kitLibrary->getUrlByPageID(PAGE_ID, $url);
	    
	    $unsubscribed = false;
	    foreach ($form_data as $data) {
	        parse_str($data[dbKITformData::field_values], $values);
	        if (isset($values[$fb_subscription][0]) && isset($values[$fb_url])) {
	            if (($values[$fb_subscription][0] == self::SUBSCRIPE_YES) && ($values[$fb_url] == $url)) {
	                // update record
	                $values[$fb_subscription][0] = self::SUBSCRIPE_NO;
	                $where = array(
	                        dbKITformData::field_id => $data[dbKITformData::field_id]
	                        );
	                $upd = array(
	                        dbKITformData::field_values => http_build_query($values),
	                        dbKITformData::field_timestamp => date('Y-m-d H:i:s')
	                        );
	                if (!$dbKITformData->sqlUpdateRecord($upd, $where)) {
	                    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
	                    return false;
	                }
	                $unsubscribed = true;
	            }
	        }
	    }
	    if ($unsubscribed) {
	        $this->setMessage($this->lang->translate('<p>The email address <b>{{ email }}</b> does no longer receive messages at new feedbacks on this page.</p><p>The settings of other pages are not changed!</p>',
	                array('email' => $email)));
	    }
	    else {
	        $this->setMessage($this->lang->translate('<p>The email address <b>{{ email }}</b> does not receive any messages from this page, so nothing was changed.</p>',
	                array('email' => $email)));
	    }
	    return $this->showForm();
	} // checkFeedbackUnsubscribe()
	
	protected function checkCommand() {
	    global $dbKITformCommands;
	    global $dbKITformData;
	    global $dbKITformFields;
	    global $kitContactInterface;
	    global $dbKITform;
	    
	    if (!isset($_REQUEST[self::request_command])) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('This command does not exists or was already executed!')));
	        return false;
	    }
	    $where = array(dbKITformCommands::FIELD_COMMAND => $_REQUEST[self::request_command]);
	    $command = array();
	    if (!$dbKITformCommands->sqlSelectRecord($where, $command)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformCommands->getError()));
	        return false;
	    }
	    if (count($command) == 1) {
	        $command = $command[0];
	        if (($command[dbKITformCommands::FIELD_TYPE] == dbKITformCommands::TYPE_FEEDBACK_PUBLISH) ||
	                ($command[dbKITformCommands::FIELD_TYPE] == dbKITformCommands::TYPE_FEEDBACK_REFUSE)) {
	            // Feedback zurueckweisen
	            parse_str($command[dbKITformCommands::FIELD_PARAMS], $params);
	            if (isset($params['data_id'])) {
	                $form_data = array();
	                $where = array(dbKITformData::field_id => $params['data_id']);
	                if (!$dbKITformData->sqlSelectRecord($where, $form_data)) {
	                    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
	                    return false;
	                }
	                if (count($form_data) == 1) {
	                    $form_data = $form_data[0];
	                    // get the form fields
	                    $where = array(dbKITformFields::field_form_id => $form_data[dbKITformData::field_form_id]);
	                    $form_fields = array();
	                    if (!$dbKITformFields->sqlSelectRecord($where, $form_fields)) {
	                        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
	                        return false;
	                    }
	                    foreach ($form_fields as $ff) {
	                        switch ($ff[dbKITformFields::field_name]):
	                        case self::FIELD_FEEDBACK_HOMEPAGE:
	                            $fb_homepage = $ff[dbKITformFields::field_id]; break;
	                        case self::FIELD_FEEDBACK_NICKNAME:
	                            $fb_nickname = $ff[dbKITformFields::field_id]; break;
	                        case self::FIELD_FEEDBACK_PUBLISH:
	                            $fb_publish = $ff[dbKITformFields::field_id]; break;
	                        case self::FIELD_FEEDBACK_SUBJECT:
	                            $fb_subject = $ff[dbKITformFields::field_id]; break;
	                        case self::FIELD_FEEDBACK_SUBSCRIPTION:
	                            $fb_subscription = $ff[dbKITformFields::field_id]; break;
	                        case self::FIELD_FEEDBACK_TEXT:
	                            $fb_text = $ff[dbKITformFields::field_id]; break;
	                        case self::FIELD_FEEDBACK_URL:
	                            $fb_url = $ff[dbKITformFields::field_id]; break;
	                        endswitch;
	                    }
	                    if (isset($fb_publish)) {
	                        parse_str($form_data[dbKITformData::field_values], $values);
	                        if (isset($values[$fb_publish])) {
	                            if ($command[dbKITformCommands::FIELD_TYPE] == dbKITformCommands::TYPE_FEEDBACK_REFUSE) {
	                                $values[$fb_publish] = self::PUBLISH_FORBIDDEN;
	                            }
	                            else {
	                                $values[$fb_publish] = self::PUBLISH_IMMEDIATE;
	                            }
	                            $where = array(
	                                    dbKITformData::field_id => $params['data_id']
	                                    );
	                            $data = array(
	                                    dbKITformData::field_values => http_build_query($values),
	                                    dbKITformData::field_timestamp => date('Y-m-d H:i:s')
	                                    );
	                            if (!$dbKITformData->sqlUpdateRecord($data, $where)) {
	                                $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
	                                return false;
	                            }
	                            // delete command
	                            $where = array(dbKITformCommands::FIELD_ID => $command[dbKITformCommands::FIELD_ID]);
	                            if (!$dbKITformCommands->sqlDeleteRecord($where)) {
	                                $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformCommands->getError()));
	                                return false;
	                            } 
	                            if ($command[dbKITformCommands::FIELD_TYPE] == dbKITformCommands::TYPE_FEEDBACK_REFUSE) {
	                                // feedback is successfully refused!
	                                $this->setMessage($this->lang->translate('<p>The feedback was refused!</p>'));
	                            }
	                            else {
	                                // feedback is now published - check for subscriber!
	                                $subscriber_emails = array();
	                                $where = array(dbKITformData::field_form_id => $form_data[dbKITformData::field_form_id]);
	                                $sub_data = array();
	                                if (!$dbKITformData->sqlSelectRecord($where, $sub_data)) {
	                                    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
	                                    return false;
	                                }
	                                foreach ($sub_data as $sub) {
	                                    parse_str($sub[dbKITformData::field_values], $values);
	                                    if (isset($values[$fb_subscription][0]) && ($values[$fb_subscription][0] == self::SUBSCRIPE_YES)) {
	                                        $cont = array();
	                                        if (!$kitContactInterface->getContact($sub[dbKITformData::field_kit_id], $cont)) {
	                                            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
	                                                    $this->lang->translate('The ID {{ id }} is invalid!', array('id' => $sub[dbKITformData::field_kit_id]))));
	                                            return false;
	                                        }
	                                        if (!in_array($cont[kitContactInterface::kit_email], $subscriber_emails) &&
	                                                ($cont[kitContactInterface::kit_email] != $params['contact'][kitContactInterface::kit_email])) {
	                                            $subscriber_emails[] = $cont[kitContactInterface::kit_email];
	                                        }
	                                    }
	                                }
	                                if (count($subscriber_emails) > 0) {
	                                    // prepare emails and send out...
	                                    $form = array();
	                                    $where = array(
	                                            dbKITform::field_id => $form_data[dbKITformData::field_form_id]
	                                            );
	                                    if (!$dbKITform->sqlSelectRecord($where, $form)) {
	                                        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
	                                        return false;
	                                    }
	                                    $form = $form[0];
	                                    // prepare sending emails
	                                    $provider_data = array ();
	                                    if (!$kitContactInterface->getServiceProviderByID($form[dbKITform::field_provider_id], $provider_data )) {
	                                        if ($kitContactInterface->isError()) {
	                                            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
	                                        }
	                                        else {
	                                            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage()));
	                                        }
	                                        return false;
	                                    }
	                                    $provider_email = $provider_data['email'];
	                                    $provider_name = $provider_data['name'];
	                                    
	                                    $feedback_array = array(
	                                            self::FIELD_FEEDBACK_HOMEPAGE => isset($fb_homepage) ? $values[$fb_homepage] : '',
	                                            self::FIELD_FEEDBACK_NICKNAME => isset($fb_nickname) ? $values[$fb_nickname] : '',
	                                            self::FIELD_FEEDBACK_PUBLISH => isset($fb_publish) ? $values[$fb_publish] : self::PUBLISH_IMMEDIATE,
	                                            self::FIELD_FEEDBACK_SUBJECT => isset($fb_subject) ? $values[$fb_subject] : '',
	                                            self::FIELD_FEEDBACK_SUBSCRIPTION => isset($fb_subscription) ? $values[$fb_subscription] : self::SUBSCRIPE_NO,
	                                            self::FIELD_FEEDBACK_TEXT => isset($fb_text) ? $values[$fb_text] : '',
	                                            self::FIELD_FEEDBACK_URL => isset($fb_url) ? $values[$fb_url] : ''
	                                    );
	                                    
	                                    $body_data = array(
	                                            'feedback' => array(
	                                                    'field' => $feedback_array,
	                                                    'unsubscribe_link' => sprintf('%s?%s#%s',
	                                                            $this->page_link,
	                                                            http_build_query(array(
	                                                                    self::request_action => self::action_feedback_unsubscribe,
	                                                                    self::request_form_id => $form_data[dbKITformData::field_form_id])),
	                                                            self::FORM_ANCHOR)
	                                            ));
	                                    
	                                    
	                                    $subscriber_mail = $this->getTemplate('mail.feedback.subscriber.submit.htt', $body_data );
	                                    if ($form[dbKITform::field_email_html] == dbKITform::html_off) $subscriber_mail = strip_tags($subscriber_mail);
	                                    $subscriber_subject = stripslashes($this->getTemplate('mail.feedback.subject.htt', array('subject' => $form[dbKITform::field_title])));
	                                    
	                                    $bcc_array = array();
	                                    foreach( $subscriber_emails as $cc ) {
	                                        if (!empty($cc)) $bcc_array[$cc] = $cc;
	                                    }
	                                    $mail = new kitMail($form[dbKITform::field_provider_id]);
	                                    if (!$mail->mail(
	                                            $subscriber_subject,
	                                            $subscriber_mail,
	                                            $provider_email,
	                                            $provider_name,
	                                            array($provider_email => $provider_name ),
	                                            ($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false,
	                                            array(),
	                                            $bcc_array
	                                    )) {
	                                        $err = $mail->getMailError ();
	                                        if (empty ( $err ))
	                                            $err = $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array('email' => $provider_email));
	                                        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $err));
	                                        return false;
	                                    }
	                                }
	                                
	                                $this->setMessage($this->lang->translate('<p>The feedback was published.</p>'));
	                            }
	                            return $this->showForm();    
	                        }
	                    }
	                }
                    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, 
                            $this->lang->translate('The ID {{ id }} is invalid!', array('id' => $params['data_id']))));
                    return false;
	            }
	            else {
	                $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, 
	                        $this->lang->translate('The command is not complete, missing parameters!')));
	                return false;
	            }
	        }
	        elseif ($command[dbKITformCommands::FIELD_TYPE] == dbKITformCommands::TYPE_DELAYED_TRANSMISSION) {
	            // DELAYED TRANSMISSION - load the form data and show the form again
	            parse_str($command[dbKITformCommands::FIELD_PARAMS], $params);
	            if (isset($params[dbKITformData::field_id])) {
	                // read the transmitted data for this form
	                $form_data = array();
	                $where = array(
	                        dbKITformData::field_id => $params[dbKITformData::field_id],
	                        dbKITformData::field_status => dbKITformData::status_delayed
	                        );
	                if (!$dbKITformData->sqlSelectRecord($where, $form_data)) {
	                    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
	                    return false;
	                }
	                if (count($form_data) < 1) {
	                    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
	                            $this->lang->translate( 'The ID {{ id }} is invalid!', array('id' => $params['data_id']))));
	                    return false;
	                }
	                $form_data = $form_data[0];
	                $fields = array();
	                parse_str($form_data[dbKITformData::field_values], $fields);
	                foreach ($fields as $key => $value) {
	                    // set $_REQUESTs for each free field
	                    $where = array(
	                            dbKITformFields::field_id => $key
	                            );
	                    $field_data = array();
	                    if (!$dbKITformFields->sqlSelectRecord($where, $field_data)) {
	                        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
	                        return false;
	                    }
	                    // prompt no error, still continue on error ...
	                    if (count($field_data) < 1) continue;
	                    $field_data = $field_data[0];
	                    // special: don't set the delayed transmission again!
	                    if ($field_data[dbKITformFields::field_type] == dbKITformFields::type_delayed) continue;
	                    $_REQUEST[$field_data[dbKITformFields::field_name]] = $value;
	                }
	                // get the contact data
	                if (isset($params['kit_id'])) {
	                    $contact = array();
	                    if (!$kitContactInterface->getContact($params['kit_id'], $contact)) {
	                        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
	                        return false;
	                    }
	                    foreach ($contact as $key => $value) {
	                        $_REQUEST[$key] = $value;
	                    }
	                }	   
	                // delete the saved form data
	                $where = array(
	                        dbKITformData::field_id => $params[dbKITformData::field_id]
	                        );
	                $data = array(
	                        dbKITformData::field_status => dbKITformData::status_deleted
	                        );
	                if (!$dbKITformData->sqlUpdateRecord($data, $where)) {
	                    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
	                    return false;
	                }
	                // delete the command
	                $where = array(dbKITformCommands::FIELD_ID => $command[dbKITformCommands::FIELD_ID]);
	                if (!$dbKITformCommands->sqlDeleteRecord($where)) {
	                    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformCommands->getError()));
	                    return false;
	                }
	                // write protocol
	                $notice = $this->lang->translate(
	                        '[kitForm] The temporary saved form with the ID {{ id }} was deleted.', 
	                        array('id' => $params['data_id']));
	                $kitContactInterface->addNotice($params['kit_id'], $notice);
	                // message to inform the user about the deletion
	                $this->setMessage($this->lang->translate('<p>The link to access this form is no longer valid and the temporary saved form data are now deleted.</p><p>Please submit the form or use again the option for a delayed transmission to create a new access link.</p>'));
	                            
	                return $this->showForm();
	            }
	            else {
	                $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, 
	                        $this->lang->translate('The command is not complete, missing parameters!')));
	                return false;
	            }        
	        }
	        else {
	            // unknown command
	            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('This command does not exists or was already executed!')));
	            return false;
	        }
	    }
	    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('This command does not exists or was already executed!')));
	    return false;
	} // checkCommand()
	
	/**
	 * Sendet dem User ein neues Passwort zu
	 * 
	 * @param array $form_data - Formulardaten
	 * @return boolean false on program error STR dialog/message on success
	 */
	protected function sendNewPassword($form_data = array()) {
		global $kitContactInterface;
		
		if (! isset ( $_REQUEST [kitContactInterface::kit_email] )) {
			$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Missing the datafield <b>{{ field }}</b>!', array('field' => kitContactInterface::kit_email))));
			return false;
		}
		$contact_id = -1;
		$status = dbKITcontact::status_active;
		if (! $kitContactInterface->isEMailRegistered ( $_REQUEST [kitContactInterface::kit_email], $contact_id, $status )) {
			// E-Mail Adresse ist nicht registriert
			$this->setMessage ($this->lang->translate('<p>The email address <b>{{ email }}</b> is not registered.</p>',
			        array('email' => $_REQUEST [kitContactInterface::kit_email])));
			return $this->showForm ();
		}
		if ($status != dbKITcontact::status_active) {
			// Der Kontakt ist NICHT AKTIV!
			$this->setMessage ($this->lang->translate('<p>The account for the email address <b>{{ email }}</b> is not active, please contact the service!</p>',
			        array('email' => $_REQUEST [kitContactInterface::kit_email])));
			return $this->showForm ();
		}
		// CAPTCHA pruefen?
		if ($form_data [dbKITform::field_captcha] == dbKITform::captcha_on) {
			unset ( $_SESSION ['kf_captcha'] );
			if (! isset ( $_REQUEST ['captcha'] ) || ($_REQUEST ['captcha'] != $_SESSION ['captcha'])) {
				$this->setMessage ($this->lang->translate('<p>The CAPTCHA code is not correct, please try again!</p>'));
				return $this->showForm ();
			}
		}
		
		// neues Passwort anfordern
		$newPassword = '';
		if (! $kitContactInterface->generateNewPassword ( $_REQUEST [kitContactInterface::kit_email], $newPassword )) {
			if ($kitContactInterface->isError ()) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
				return false;
			}
			$this->setMessage ( $kitContactInterface->getMessage () );
			return $this->showForm ();
		}
		$contact = array();
		if (! $kitContactInterface->getContact ( $contact_id, $contact )) {
			$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
			return false;
		}
		
		$form_data['subject'] = $form_data[dbKITform::field_title];
		
		$data = array ('contact' => $contact, 'password' => $newPassword, 'form' => $form_data );
		
		$provider_data = array ();
		if (! $kitContactInterface->getServiceProviderByID ( $form_data [dbKITform::field_provider_id], $provider_data )) {
			if ($kitContactInterface->isError ()) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
			} else {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage () ) );
			}
			return false;
		}
		$provider_email = $provider_data ['email'];
		$provider_name = $provider_data ['name'];
		
		$client_mail = $this->getTemplate ( 'mail.client.password.htt', $data );
		if ($form_data [dbKITform::field_email_html] == dbKITform::html_off) $client_mail = strip_tags($client_mail);
		$client_subject = strip_tags($this->getTemplate('mail.client.subject.htt', $data));
		
		$mail = new kitMail ( $form_data [dbKITform::field_provider_id] );
		if (! $mail->mail ( $client_subject, $client_mail, $provider_email, $provider_name, array ($contact [kitContactInterface::kit_email] => $contact [kitContactInterface::kit_email] ), ($form_data [dbKITform::field_email_html] == dbKITform::html_on) ? true : false )) {
			$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, 
			        $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array('email' => $contact[kitContactInterface::kit_email]))));
			return false;
		}
		
		return $this->getTemplate ( 'confirm.password.htt', $data );
	} // sendNewPassword()
	

	/**
	 * Registriert ein Benutzerkonto und versendet einen Aktivierungslink
	 * 
	 * @param array $form_data - Formulardaten
	 * @param array $contact_data - Kontaktdaten
	 */
	protected function registerAccount($form_data = array(), $contact_data = array()) {
		global $kitContactInterface;
		
		$contact_id = -1;
		$status = dbKITcontact::status_active;
		if ($kitContactInterface->isEMailRegistered ( $contact_data [kitContactInterface::kit_email], $contact_id, $status )) {
			// diese E-Mail Adresse ist bereits registriert
			if ($status == dbKITcontact::status_active) {
				// Kontakt ist aktiv
				$this->setMessage ($this->lang->translate('<p>The email address <b>{{ email }}</b> is already registered, please login with your user data!</p>',
				        array('email' => $contact_data[kitContactInterface::kit_email])));
				return $this->showForm ();
			} else {
				// Kontakt ist gesperrt
				$this->setMessage ($this->lang->translate('<p>The account for the email address <b>{{ email }}</b> is locked. Please contact the service!</p>',
				        array('email' => $contact_data [kitContactInterface::kit_email])));
				return $this->showForm ();
			}
		} elseif ($kitContactInterface->isError ()) {
			$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
			return false;
		}
		
		// alles ok - neuen Datensatz anlegen
		$register_data = array();
		if (! $kitContactInterface->addContact($contact_data, $contact_id, $register_data)) {
			$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
			return false;
		}
		$form_data ['datetime'] = date ( cfg_datetime_str );
		$form_data ['activation_link'] = sprintf('%s%s%s', 
												 $this->page_link, 
												 (strpos($this->page_link, '?') === false) ? '?' : '&', 
												 http_build_query(array(self::request_action => self::action_activation_key, self::request_key => $register_data [dbKITregister::field_register_key], self::request_provider_id => $form_data [dbKITform::field_provider_id], self::request_activation_type => self::activation_type_account ) ) );
		$form_data['subject'] = $form_data[dbKITform::field_title];
		// Benachrichtigungen versenden
		
		$provider_data = array ();
		if (! $kitContactInterface->getServiceProviderByID ( $form_data [dbKITform::field_provider_id], $provider_data )) {
			if ($kitContactInterface->isError ()) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
			} else {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage () ) );
			}
			return false;
		}
		$provider_email = $provider_data ['email'];
		$provider_name = $provider_data ['name'];
		
		$data = array ('contact' => $contact_data, 'form' => $form_data );
		
		$client_mail = $this->getTemplate ( 'mail.client.register.htt', $data );
		if ($form_data [dbKITform::field_email_html] == dbKITform::html_off) $client_mail = strip_tags($client_mail);
		$client_subject = strip_tags($this->getTemplate('mail.client.subject.htt', $data));
		
		$mail = new kitMail ( $form_data [dbKITform::field_provider_id] );
		if (! $mail->mail ( $client_subject, $client_mail, $provider_email, $provider_name, array ($contact_data [kitContactInterface::kit_email] => $contact_data [kitContactInterface::kit_email] ), ($form_data [dbKITform::field_email_html] == dbKITform::html_on) ? true : false  )) {
			$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, 
			        $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array('email' => $contact_data[kitContactInterface::kit_email]))));
			return false;
		}
		
		$provider_subject = $this->getTemplate('mail.provider.subject.htt', $data);
		$provider_mail = $this->getTemplate ( 'mail.provider.register.htt', $data );
		if ($form_data [dbKITform::field_email_html] == dbKITform::html_off) $provider_mail = strip_tags($provider_mail);
		
		$cc_array = array ();
		$ccs = explode ( ',', $form_data [dbKITform::field_email_cc] );
		foreach ( $ccs as $cc )
			if (!empty($cc)) $cc_array [$cc] = $cc;
			
		$mail = new kitMail ( $form_data [dbKITform::field_provider_id] );
		if (! $mail->mail ( $provider_subject, $provider_mail, $contact_data [kitContactInterface::kit_email], $contact_data [kitContactInterface::kit_email], array ($provider_email => $provider_name ), ($form_data[dbKITform::field_email_html] == dbKITform::html_on) ? true : false, $cc_array )) {
			$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, 
			        $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array('email' => SERVER_EMAIL))));
			return false;
		}
		
		return $this->getTemplate ( 'confirm.register.htt', $data );
	} // registerAccount()
	
	/**
	 * Check the authentication of a LEPTON user
	 * 
	 * @param string $username
	 * @param string $password
	 */
	protected function authenticate_wb_user($username, $password) {
		global $database;
		global $wb;
		$query = sprintf("SELECT * FROM %susers WHERE username='%s' AND password='%s' AND active = '1'", TABLE_PREFIX, $username, $password);
		$results = $database->query($query);
		if ($database->is_error()) {
			$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
			return false;
		}
		$results_array = $results->fetchRow();
		$num_rows = $results->numRows();
		if ($num_rows) {
			$user_id = $results_array['user_id'];
			$this->user_id = $user_id;
			$_SESSION['USER_ID'] = $user_id;
			$_SESSION['GROUP_ID'] = $results_array['group_id'];
			$_SESSION['GROUPS_ID'] = $results_array['groups_id'];
			$_SESSION['USERNAME'] = $results_array['username'];
			$_SESSION['DISPLAY_NAME'] = $results_array['display_name'];
			$_SESSION['EMAIL'] = $results_array['email'];
			$_SESSION['HOME_FOLDER'] = $results_array['home_folder'];
			/*
			// Run remember function if needed
			if($this->remember == true) {
				$this->remember($this->user_id);
			}
			*/
			// Set language
			if($results_array['language'] != '') {
				$_SESSION['LANGUAGE'] = $results_array['language'];
			}
			// Set timezone
			if($results_array['timezone'] != '-72000') {
				$_SESSION['TIMEZONE'] = $results_array['timezone'];
			} else {
				// Set a session var so apps can tell user is using default tz
				$_SESSION['USE_DEFAULT_TIMEZONE'] = true;
			}
			// Set date format
			if($results_array['date_format'] != '') {
				$_SESSION['DATE_FORMAT'] = $results_array['date_format'];
			} else {
				// Set a session var so apps can tell user is using default date format
				$_SESSION['USE_DEFAULT_DATE_FORMAT'] = true;
			}
			// Set time format
			if($results_array['time_format'] != '') {
				$_SESSION['TIME_FORMAT'] = $results_array['time_format'];
			} else {
				// Set a session var so apps can tell user is using default time format
				$_SESSION['USE_DEFAULT_TIME_FORMAT'] = true;
			}
			$_SESSION['SYSTEM_PERMISSIONS'] = array();
			$_SESSION['MODULE_PERMISSIONS'] = array();
			$_SESSION['TEMPLATE_PERMISSIONS'] = array();
			$_SESSION['GROUP_NAME'] = array();

			$first_group = true;
			foreach (explode(",", $wb->get_session('GROUPS_ID')) as $cur_group_id)
            {
				$query = sprintf("SELECT * FROM %sgroups WHERE group_id='%s'", TABLE_PREFIX, $cur_group_id);
				$results = $database->query($query);
				$results_array = $results->fetchRow();
				$_SESSION['GROUP_NAME'][$cur_group_id] = $results_array['name'];
				// Set system permissions
				if($results_array['system_permissions'] != '') {
					$_SESSION['SYSTEM_PERMISSIONS'] = array_merge($_SESSION['SYSTEM_PERMISSIONS'], explode(',', $results_array['system_permissions']));
				}
				// Set module permissions
				if($results_array['module_permissions'] != '') {
					if ($first_group) {
          	$_SESSION['MODULE_PERMISSIONS'] = explode(',', $results_array['module_permissions']);
          } else {
          	$_SESSION['MODULE_PERMISSIONS'] = array_intersect($_SESSION['MODULE_PERMISSIONS'], explode(',', $results_array['module_permissions']));
					}
				}
				// Set template permissions
				if($results_array['template_permissions'] != '') {
					if ($first_group) {
          	$_SESSION['TEMPLATE_PERMISSIONS'] = explode(',', $results_array['template_permissions']);
          } else {
          	$_SESSION['TEMPLATE_PERMISSIONS'] = array_intersect($_SESSION['TEMPLATE_PERMISSIONS'], explode(',', $results_array['template_permissions']));
					}
				}
				$first_group = false;
			}	
			// Update the users table with current ip and timestamp
			$get_ts = time();
			$get_ip = $_SERVER['REMOTE_ADDR'];
			$query = sprintf("UPDATE %susers SET login_when= '%s', login_ip='%s' WHERE user_id='%s'", TABLE_PREFIX, $get_ts, $get_ip, $user_id);
			$database->query($query);
		}
		// Return if the user exists or not
		return $num_rows;
	} // authenticate_wb_user
	
	/**
	 * Aktivierungskey ueberpruefen, Datensatz freischalten und Benutzer einloggen...
	 * 
	 * @return string Dialog 
	 * @intern Diese Routine nutzt ein statisches SUBJECT im Gegensatz zu allen anderen E-Mail Routinen
	 */
	protected function checkActivationKey() {
		global $kitContactInterface;
		global $dbKITform;
		
		if (! isset ( $_REQUEST [self::request_key] )) {
			$this->setError ( sprintf('[%s - %s] %s', __METHOD__, __LINE__, 
			        $this->lang->translate('Missing the datafield <b>{{ field }}</b>!', array('field' => self::request_key))));
			return false;
		}
		
		$register = array();
		$contact = array();
		$password = '';
		if (! $kitContactInterface->checkActivationKey($_REQUEST[self::request_key], $register, $contact, $password )) {
			if ($this->isError ()) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
				return false;
			}
			$this->setMessage ( $kitContactInterface->getMessage () ); 
			return $this->showForm ();
		}
		// Benutzer anmelden
		$_SESSION [kitContactInterface::session_kit_aid] = $register [dbKITregister::field_id];
		$_SESSION [kitContactInterface::session_kit_key] = $register [dbKITregister::field_register_key];
		$_SESSION [kitContactInterface::session_kit_contact_id] = $register [dbKITregister::field_contact_id];
		
		// if auto_login_wb
		if ($this->params[self::param_auto_login_wb]) {
			if (!$this->authenticate_wb_user($register[dbKITregister::field_email], $register[dbKITregister::field_password])) {
				$error = $this->isError() ? $this->getError() : $this->lang->translate('<p>Unspecified error, no description available.</p>');
				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $error));
				return false;
			}
		}
		
		// Passwort pruefen
		if ($password == - 1) {
			// Benutzer war bereits freigeschaltet und das Konto ist aktiv
			$this->setMessage ($this->lang->translate('<p>Welcome!<br />we have send you the username and password by email.</p>'));
			return $this->showForm ();
		}
		$data = array ('contact' => $contact, 'password' => $password );
		
		$activation_type = (isset ( $_REQUEST [self::request_activation_type] )) ? $_REQUEST [self::request_activation_type] : self::activation_type_account;
		
		switch ($activation_type) :
			case self::activation_type_newsletter :
				$mail_template = 'mail.client.activation.newsletter.htt';
				$prompt_template = 'confirm.activation.newsletter.htt';
				break;
			case self::activation_type_account :
			default :
				$mail_template = 'mail.client.activation.account.htt';
				$prompt_template = 'confirm.activation.account.htt';
				break;
		endswitch;
		
		$client_mail = strip_tags($this->getTemplate ( $mail_template, $data ));
		$provider_id = (isset ( $_REQUEST [self::request_provider_id] )) ? $_REQUEST [self::request_provider_id] : - 1;
		
		$provider_data = array ();
		if (! $kitContactInterface->getServiceProviderByID ( $provider_id, $provider_data )) {
			if ($kitContactInterface->isError ()) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
			} else {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage () ) );
			}
			return false;
		}
		$provider_email = $provider_data ['email'];
		$provider_name = $provider_data ['name'];
		
		// Standard E-Mail Routine verwenden
		$mail = new kitMail ( $provider_id );
		if (! $mail->mail ($this->lang->translate('Your account data'), $client_mail, $provider_email, $provider_name, array ($contact [kitContactInterface::kit_email] => $contact [kitContactInterface::kit_email] ), false )) {
			$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, 
			        $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array('email' => $contact[kitContactInterface::kit_email]))));
			return false;
		}
		return $this->getTemplate ( $prompt_template, $data );
	} // checkActivationKey()
	

	/**
	 * Logout 
	 * 
	 * @return string Logout dialog
	 */
	protected function Logout() {
		global $kitContactInterface;
		
		$contact = array();
		if (! $kitContactInterface->getContact ( $_SESSION [kitContactInterface::session_kit_contact_id], $contact )) {
			$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
			return false;
		}
		$data = array ('contact' => $contact );
		$kitContactInterface->logout ();
		return $this->getTemplate ( 'confirm.logout.htt', $data );
	} // Logout()
	
	/**
	 * Subscribe a user to the newsletter
	 * 
	 * @param array $form_data
	 * @return string confirmation dialog on success or boolean false on error
	 */
	protected function subscribeNewsletter($form_data = array()) {
		global $kitContactInterface;
		
		$use_subscribe = false;
		$subscribe = false;
		// pruefen ob kit_newsletter_subscribe verwendet wird
		if (isset ( $_REQUEST [kitContactInterface::kit_newsletter_subscribe] )) {
			$use_subscribe = true;
			if (is_bool ( $_REQUEST [kitContactInterface::kit_newsletter_subscribe] )) {
				$subscribe = $_REQUEST [kitContactInterface::kit_newsletter_subscribe];
			} elseif (is_numeric ( $_REQUEST [kitContactInterface::kit_newsletter_subscribe] )) {
				$subscribe = ($_REQUEST [kitContactInterface::kit_newsletter_subscribe] == 1) ? true : false;
			} else {
				$subscribe = (strtolower ( $_REQUEST [kitContactInterface::kit_newsletter_subscribe] ) == 'true') ? true : false;
			}
		}
		
		$newsletter = '';
		if (isset ( $_REQUEST [kitContactInterface::kit_newsletter] ) && is_array ( $_REQUEST [kitContactInterface::kit_newsletter] )) {
			$newsletter = implode ( ',', $_REQUEST [kitContactInterface::kit_newsletter] );
		} elseif (isset ( $_REQUEST [kitContactInterface::kit_newsletter] )) {
			$newsletter = $_REQUEST [kitContactInterface::kit_newsletter];
		}
		
		$email = $_REQUEST [kitContactInterface::kit_email];
		
		$register = array();
		$contact = array();
		$send_activation = false;
		if (! $kitContactInterface->subscribeNewsletter($email, $newsletter, $subscribe, $use_subscribe, $register, $contact, $send_activation )) {
			if ($kitContactInterface->isError ()) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
				return false;
			}
			$this->setMessage ( $kitContactInterface->getMessage () );
			return $this->showForm ();
		}
		$message = $kitContactInterface->getMessage ();
		if ($send_activation == false) {
			$message .= sprintf ($this->lang->translate('<p>The newsletter abonnement for the email address <b>{{ email }}</b> was updated.</p>',
			        array('email' => $email)));
			$this->setMessage ( $message );
			$data = array ('message' => $this->getMessage () );
			return $this->getTemplate ( 'prompt.htt', $data );
		} else {
			// Aktivierungskey versenden
			$form = array (
				'activation_link' => sprintf ( '%s%s%s', $this->page_link, (strpos ( $this->page_link, '?' ) === false) ? '?' : '&', http_build_query ( array (self::request_action => self::action_activation_key, self::request_key => $register [dbKITregister::field_register_key], self::request_provider_id => $form_data [dbKITform::field_provider_id], self::request_activation_type => self::activation_type_newsletter ) ) ),
				'datetime' 				=> date ( cfg_datetime_str ),
				'subject'					=> $form_data[dbKITform::field_title]						
			);
			$data = array ('form' => $form, 'contact' => $contact );
			$provider_data = array ();
			if (! $kitContactInterface->getServiceProviderByID ( $form_data [dbKITform::field_provider_id], $provider_data )) {
				if ($kitContactInterface->isError ()) {
					$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError () ) );
				} else {
					$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage () ) );
				}
				return false;
			}
			$provider_email = $provider_data ['email'];
			$provider_name = $provider_data ['name'];
			$client_mail = $this->getTemplate ( 'mail.client.register.newsletter.htt', $data );
			if ($form_data[dbKITform::field_email_html] == dbKITform::html_off) $client_mail = strip_tags($client_mail);
			$client_subject = strip_tags($this->getTemplate('mail.client.subject.htt', $data));
			
			$mail = new kitMail ( $form_data [dbKITform::field_provider_id] );
			if (! $mail->mail ( $client_subject, $client_mail, $provider_email, $provider_name, array ($contact [kitContactInterface::kit_email] => $contact [kitContactInterface::kit_email] ), ($form_data [dbKITform::field_email_html] == dbKITform::html_on) ? true : false )) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, 
				        $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array('email' => $contact[kitContactInterface::kit_email]))));
				return false;
			}
			
			$provider_mail = $this->getTemplate ( 'mail.provider.register.newsletter.htt', $data );
			if ($form_data[dbKITform::field_email_html] == dbKITform::html_off) $provider_mail = strip_tags($provider_mail);
			$provider_subject = strip_tags($this->getTemplate('mail.provider.subject.htt', $data));
			
			$cc_array = array ();
			$ccs = explode ( ',', $form_data [dbKITform::field_email_cc] );
			foreach ( $ccs as $cc )
				if (!empty($cc)) $cc_array [$cc] = $cc;
			
			$mail = new kitMail ( $form_data [dbKITform::field_provider_id] );
			if (! $mail->mail ( $provider_subject, $provider_mail, $contact [kitContactInterface::kit_email], $contact [kitContactInterface::kit_email], array ($provider_email => $provider_name ), ($form_data[dbKITform::field_email_html] == dbKITform::html_on) ? true : false, $cc_array )) {
				$this->setError ( sprintf ( '[%s - %s] %s', __METHOD__, __LINE__, 
				        $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array('email' => SERVER_EMAIL))));
				return false;
			}
			
			return $this->getTemplate ( 'confirm.register.newsletter.htt', $data );
		}
	
	} // subscribeNewsletter()

	/**
	 * Create .htaccess protection
	 * @return boolean
	 */
	protected function createProtection() {
	    global $kitLibrary;
	    $protection_path = WB_PATH.MEDIA_DIRECTORY.DIRECTORY_SEPARATOR.self::PROTECTION_FOLDER.DIRECTORY_SEPARATOR;
	    $data = sprintf("# .htaccess generated by kitForm\nAuthUserFile %s\nAuthGroupFile /dev/null".
	            "\nAuthName \"KIT - Protected Media Directory\"\nAuthType Basic\n<Limit GET>\n".
	            "require valid-user\n</Limit>",$protection_path.'.htpasswd');
	    if (false === file_put_contents($protection_path.'.htaccess', $data)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Can\'t create the .htaccess file!')));
	        return false;
	    }
	    $data = sprintf("# .htpasswd generated by kitForm\nkit_protector:%s", crypt($kitLibrary->generatePassword()));
	    if (false === file_put_contents($protection_path.'.htpasswd', $data)) {
	        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Can\'t create the .htpasswd file!')));
	        return false;
	    }
	    return true;
	} // createProtection()
	
	
} // class formFrontend


?>