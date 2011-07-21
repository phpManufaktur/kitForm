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
if (!$dbKITform->sqlFieldExists(dbKITform::field_links)) {
	if (!$dbKITform->sqlAlterTableAddField(dbKITform::field_links, "VARCHAR(255) NOT NULL DEFAULT ''")) {
		$error .= sprintf('[UPGRADE] %s', $dbKITform->getError());
	}
}

// Release 0.15 - add service provider and email cc
if (!$dbKITform->sqlFieldExists(dbKITform::field_provider_id)) {
	if (!$dbKITform->sqlAlterTableAddField(dbKITform::field_provider_id, "INT(11) NOT NULL DEFAULT '-1'", dbKITform::field_captcha)) {
		$error .= sprintf('[UPGRADE] %s', $dbKITform->getError());
	}
}
if (!$dbKITform->sqlFieldExists(dbKITform::field_email_cc)) {
	if (!$dbKITform->sqlAlterTableAddField(dbKITform::field_email_cc, "TEXT NOT NULL DEFAULT ''", dbKITform::field_provider_id)) {
		$error .= sprintf('[UPGRADE] %s', $dbKITform->getError());
	}
}
if (!$dbKITform->sqlFieldExists(dbKITform::field_email_html)) {
	if (!$dbKITform->sqlAlterTableAddField(dbKITform::field_email_html, "TINYINT NOT NULL DEFAULT '".dbKITform::html_off."'", dbKITform::field_email_cc)) {
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