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

/*
// include language file
if(!file_exists(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/DE.php'); // Vorgabe: DE verwenden 
	if (!defined('KIT_FORM_LANGUAGE')) define('KIT_FORM_LANGUAGE', 'DE'); // die Konstante gibt an in welcher Sprache KIT Form aktuell arbeitet
}
else {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php');
	if (!defined('KIT_FORM_LANGUAGE')) define('KIT_FORM_LANGUAGE', LANGUAGE); // die Konstante gibt an in welcher Sprache KIT Form aktuell arbeitet
}
*/
// use LEPTON 2.x I18n for access to language files
if (! class_exists('LEPTON_Helper_I18n')) require_once WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/framework/LEPTON/Helper/I18n.php';
if (! file_exists(WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/languages/' . LANGUAGE . '.php')) {
    if (! defined('KIT_FORM_LANGUAGE')) define('KIT_FORM_LANGUAGE', 'DE'); // important: language flag is used by template selection
} else {
    if (! defined('KIT_FORM_LANGUAGE')) define('KIT_FORM_LANGUAGE', LANGUAGE);
}
$lang = new LEPTON_Helper_I18n();

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.form.php');
require_once(WB_PATH.'/modules/kit_tools/class.droplets.php');

global $admin;

$tables = array('dbKITform', 'dbKITformData', 'dbKITformFields', 'dbKITformTableSort', 'dbKITformCommands');
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

// Standardformulare installieren
$message = '';
global $dbKITform;
if (!is_object($dbKITform)) $dbKITform = new dbKITform();

if (!$dbKITform->installStandardForms($message)) {
	if ($dbKITform->isError()) $error .= sprintf('[UPGRADE] %s', $dbKITform->getError());
}
$message = strip_tags($message);

// Install Droplets
$droplets = new checkDroplets();
$droplets->droplet_path = WB_PATH.'/modules/kit_form/droplets/';
if ($droplets->insertDropletsIntoTable()) {
  $message .= $lang->translate('The droplets for kitForm were successfully installed.\n');
}
else {
  $message .= $lang->translate('Error installing the Droplets for kitForm:\n{{ error }}\n',
          array('error' => $droplets->getError()));
}
if ($message != "") {
  echo '<script language="javascript">alert ("'.$message.'");</script>';
}

// Prompt Errors
if (!empty($error)) {
	$admin->print_error($error);
}

?>