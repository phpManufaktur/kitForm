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


class dbKITform extends dbConnectLE {
	
	const field_id										= 'form_id';
	const field_name									= 'form_name';
	const field_title									= 'form_title';
	const field_description						= 'form_desc';
	const field_fields								= 'form_fields';
	const field_must_fields						= 'form_must_fields';
	const field_action								= 'form_action';
	const field_captcha								= 'form_captcha';
	const field_status								= 'form_status';
	const field_timestamp							= 'form_timestamp';
	
	const status_active								= 1;
	const status_locked								= 2;
	const status_deleted							= 0; 
	
	public $status_array = array(
		self::status_active			=> form_status_active,
		self::status_locked			=> form_status_locked,
		self::status_deleted		=> form_status_deleted 
	);
	
	const captcha_on									= 1;
	const captcha_off									= 0;
	
	public $captcha_array = array(
		self::captcha_on				=> form_captcha_on,
		self::captcha_off				=> form_captcha_off
	);
	
	const action_none									= 0;
	const action_login								= 1;
	const action_register							= 2;
	const action_send_password				= 3;
	const action_newsletter						= 4;
	
	public $action_array = array(
		//self::action_none						=> form_action_none,
		self::action_login					=> form_action_login,
		self::action_register				=> form_action_register,
		self::action_send_password	=> form_action_send_password,
		self::action_newsletter			=> form_action_newsletter
	);
	
	private $createTables 		= false;
  
  public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_kit_form');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
  	$this->addFieldDefinition(self::field_name, "VARCHAR(80) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_title, "VARCHAR(80) NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_description, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_fields, "TEXT NOT NULL DEFAULT ''");
  	$this->addFieldDefinition(self::field_must_fields, "TEXT NOT NULL DEFAULT ''"); 
  	$this->addFieldDefinition(self::field_action, "TINYINT NOT NULL DEFAULT '".self::action_none."'");
  	$this->addFieldDefinition(self::field_captcha, "TINYINT NOT NULL DEFAULT '".self::captcha_on."'");
  	$this->addFieldDefinition(self::field_status, "TINYINT NOT NULL DEFAULT '".self::status_active."'"); 
  	$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP");
  	$this->setIndexFields(array(self::field_name));
  	$this->checkFieldDefinitions();
  	// Tabelle erstellen
  	if ($this->createTables) {
  		if (!$this->sqlTableExists()) {
  			if (!$this->sqlCreateTable()) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			}
  		}
  	}
  } // __construct()
	
} // class dbKITform

class dbKITformFields extends dbConnectLE {

	const field_id								= 'field_id';
	const field_form_id						= 'form_id';
	const field_type							= 'field_type';
	const field_type_add					= 'field_type_add';
	const field_name							= 'field_name';
	const field_title							= 'field_title';
	const field_value							= 'field_value';
	const field_data_type					= 'field_data_type';
	const field_hint							= 'field_hint';
	const field_status						= 'field_status';
	const field_timestamp					= 'field_timestamp';
	
	const type_checkbox						= 'checkbox';
	const type_hidden							= 'hidden';
	const type_html								= 'html';
	const type_radio							= 'radio';
	const type_select							= 'select';	
	const type_text								= 'text';
	const type_text_area					= 'text_area';
	const type_undefined					= 'undefined';
	
	public $type_array = array(
		self::type_text							=> form_type_text,
		self::type_text_area				=> form_type_text_area,
		self::type_checkbox					=> form_type_checkbox,
		self::type_radio						=> form_type_radio,
		self::type_select						=> form_type_select,
		self::type_hidden						=> form_type_hidden,
		self::type_html							=> form_type_html,
	);
	
	const kit_data_undefined			= 'null';
	
	const data_type_date					= 'date';
	const data_type_float					= 'float';
	const data_type_integer				= 'int';
	const data_type_text					= 'text';
	const data_type_undefined			= 'null';
	
	public $data_type_array = array(
		self::data_type_date				=> form_data_type_date,
		self::data_type_float				=> form_data_type_float,
		self::data_type_integer			=> form_data_type_integer,
		self::data_type_text				=> form_data_type_text
	);
	
	const status_active						= 1;
	const status_locked						= 0;
	const status_deleted					= -1; 
	
	public $status_array = array(
		self::status_active			=> form_status_active,
		self::status_locked			=> form_status_locked,
		self::status_deleted		=> form_status_deleted 
	);
	
	private $createTables 		= false;
  
	public function __construct($createTables = false) {
  	$this->createTables = $createTables;
  	parent::__construct();
  	$this->setTableName('mod_kit_form_fields');
  	$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true); // WICHTIG: Zaehler 1-100 sind fuer KIT reserviert!!!
  	$this->addFieldDefinition(self::field_form_id, "INT(11) NOT NULL DEFAULT '-1'");
		$this->addFieldDefinition(self::field_type, "VARCHAR(30) NOT NULL DEFAULT '".self::type_undefined."'");
		$this->addFieldDefinition(self::field_type_add, "TEXT NOT NULL DEFAULT ''", false, false, true);
		$this->addFieldDefinition(self::field_name, "VARCHAR(40) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_title, "VARCHAR(80) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_value, "TEXT NOT NULL DEFAULT ''", false, false, true);
		$this->addFieldDefinition(self::field_data_type, "VARCHAR(30) NOT NULL DEFAULT '".self::data_type_text."'");
		$this->addFieldDefinition(self::field_hint, "TEXT NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_status, "TINYINT NOT NULL DEFAULT '".self::status_active."'"); 
  	$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP");
  	$this->setIndexFields(array(self::field_name, self::field_form_id));
  	$this->checkFieldDefinitions();
  	// Tabelle erstellen
  	if ($this->createTables) {
  		if (!$this->sqlTableExists()) {
  			if (!$this->sqlCreateTable()) {
  				$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
  			}
  		}
  	}
  } // __construct()
} // class dbKITformFields
  
class dbKITformTableSort extends dbConnectLE {
	
	const field_id				= 'sort_id';
	const field_table			= 'sort_table';
	const field_value			= 'sort_value';
	const field_order			= 'sort_order';
	const field_timestamp	= 'sort_timestamp';
	
	private $create_tables = false;
	
	public function __construct($create_tables=false) {
		$this->create_tables = $create_tables;
		parent::__construct();
		$this->setTableName('mod_kit_form_table_sort');
		$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
		$this->addFieldDefinition(self::field_table, "VARCHAR(64) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_value, "VARCHAR(255) NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_order, "TEXT NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP");
		$this->checkFieldDefinitions();
		if ($this->create_tables) {
			if (!$this->sqlTableExists()) {
				if (!$this->sqlCreateTable()) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
					return false;
				}
			}
		}
	} // __construct()	
	
} // class dbKITformTableSort
  
class dbKITformData extends dbConnectLE {
	
	const field_id				= 'data_id';
	const field_form_id		= 'form_id';
	const field_kit_id		= 'kit_id';
	const field_date			=	'data_date';
	const field_fields		= 'data_fields';
	const field_values		= 'data_values';
	const field_timestamp	= 'data_timestamp';
	
	public $create_tables = false;
	
	public function __construct($create_tables=false) {
		$this->create_tables = $create_tables;
		parent::__construct();
		$this->setTableName('mod_kit_form_data');
		$this->addFieldDefinition(self::field_id, "INT(11) NOT NULL AUTO_INCREMENT", true);
		$this->addFieldDefinition(self::field_form_id, "INT(11) NOT NULL DEFAULT '-1'");
		$this->addFieldDefinition(self::field_kit_id, "INT(11) NOT NULL DEFAULT '-1'");
		$this->addFieldDefinition(self::field_date, "DATETIME");
		$this->addFieldDefinition(self::field_fields, "TEXT NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_values, "MEDIUMTEXT NOT NULL DEFAULT ''");
		$this->addFieldDefinition(self::field_timestamp, "TIMESTAMP");
		$this->setIndexFields(array(self::field_form_id, self::field_kit_id));
		$this->checkFieldDefinitions();
		if ($this->create_tables) {
			if (!$this->sqlTableExists()) {
				if (!$this->sqlCreateTable()) {
					$this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
					return false;
				}
			}
		}
	} // __construct()	
	
} // class dbKITformData 

?>