<?php
/**
 * kitEvent
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 */

// prevent this file from being accessed directly
if (!defined('WB_PATH')) die('invalid call of '.$_SERVER['SCRIPT_NAME']);

// include language file
if(!file_exists(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/DE.php'); // Vorgabe: DE verwenden 
	if (!defined('KIT_FORM_LANGUAGE')) define('KIT_FORM_LANGUAGE', 'DE'); // die Konstante gibt an in welcher Sprache KIT Form aktuell arbeitet
}
else {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php');
	if (!defined('KIT_FORM_LANGUAGE')) define('KIT_FORM_LANGUAGE', LANGUAGE); // die Konstante gibt an in welcher Sprache KIT Form aktuell arbeitet
}

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.form.php');
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.droplets.php');

global $admin;

$tables = array('dbKITform', 'dbKITformData', 'dbKITformFields', 'dbKITformTableSort');
$error = '';

foreach ($tables as $table) {
	$create = null;
	$create = new $table();
	if (!$create->sqlTableExists()) {
		if (!$create->sqlCreateTable()) {
			$error .= sprintf('[INSTALLATION %s] %s', $table, $create->getError());
		}
	}
}

// AUTO_INCREMENT FUER dbKITformFields auf 200 setzen!!!
$dbKITformFields = new dbKITformFields();
$SQL = sprintf("ALTER TABLE %s AUTO_INCREMENT = 200", $dbKITformFields->getTableName());
$result = array();
if (!$dbKITformFields->sqlExec($SQL, $result)) {
  $error .= sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError());
}

// Install Droplets
$droplets = new checkDroplets();
if ($droplets->insertDropletsIntoTable()) {
  $message = 'The Droplets for kitForm where successfully installed! Please look at the Help for further informations.';
}
else {
  $message = 'The installation of the Droplets for kitForm failed. Error: '. $droplets->getError();
}
if ($message != "") {
  echo '<script language="javascript">alert ("'.$message.'");</script>';
}


// Prompt Errors
if (!empty($error)) {
	$admin->print_error($error);
}

?>