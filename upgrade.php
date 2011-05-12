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

global $admin;

$error = '';

if (!$dbKITform->sqlFieldExists(dbKITform::field_action)) {
	if (!$dbKITform->sqlAlterTableAddField(dbKITform::field_action, "VARCHAR(30) NOT NULL DEFAULT '".dbKITform::action_none."'")) {
		$error .= sprintf('[UPGRADE] %s', $dbKITform->getError());
	}
}

// Formulare installieren
$message = '';
if (!$dbKITform->installStandardForms($message)) {
	if ($dbKITform->isError()) $error .= sprintf('[UPGRADE] %s', $dbKITform->getError());
}

if (!empty($message)) {
	echo '<script language="javascript">alert ("'.$message.'");</script>';
}

// Prompt Errors
if (!empty($error)) {
	$admin->print_error($error);
}

?>