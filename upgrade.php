<?php

/**
 * kitForm
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2011 - 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

// include class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {
  if (defined('LEPTON_VERSION'))
    include(WB_PATH.'/framework/class.secure.php');
}
else {
  $oneback = "../";
  $root = $oneback;
  $level = 1;
  while (($level < 10) && (!file_exists($root.'/framework/class.secure.php'))) {
    $root .= $oneback;
    $level += 1;
  }
  if (file_exists($root.'/framework/class.secure.php')) {
    include($root.'/framework/class.secure.php');
  }
  else {
    trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
  }
}
// end include class.secure.php

// use LEPTON 2.x I18n for access to language files
if (!class_exists('LEPTON_Helper_I18n')) require_once WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/framework/LEPTON/Helper/I18n.php';

global $I18n;
if (!is_object($I18n)) {
    $I18n = new LEPTON_Helper_I18n();
}
else {
    $I18n->addFile('DE.php', WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/languages/');
}

if (! file_exists(WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/languages/' . LANGUAGE . '.php')) {
    if (! defined('KIT_FORM_LANGUAGE')) define('KIT_FORM_LANGUAGE', 'DE'); // important: language flag is used by template selection
} else {
    if (! defined('KIT_FORM_LANGUAGE')) define('KIT_FORM_LANGUAGE', LANGUAGE);
}
// load language depending onfiguration
if (!file_exists(WB_PATH.'/modules/' . basename(dirname(__FILE__)) . '/languages/' . LANGUAGE . '.cfg.php')) {
    require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/DE.cfg.php');
} else {
    require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.cfg.php');
}

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.form.php');
require_once(WB_PATH.'/modules/kit_tools/class.droplets.php');

global $admin;

$error = '';

global $dbKITform;
if (!is_object($dbKITform)) $dbKITform = new dbKITform();

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

// Release 0.21
global $dbKITformCommands;
if (!is_object($dbKITformCommands)) $dbKITformCommands = new dbKITformCommands();

if (!$dbKITformCommands->sqlTableExists()) {
    if (!$dbKITformCommands->sqlCreateTable()) {
        $error .= sprintf('[UPGRADE] %s', $dbKITformCommands->getError());
    }
}

// Release 0.26
global $dbKITformData;
if (!is_object($dbKITformData)) $dbKITformData = new dbKITformData();

if (!$dbKITformData->sqlFieldExists(dbKITformData::field_status)) {
    if (!$dbKITformData->sqlAlterTableAddField(dbKITformData::field_status, "TINYINT NOT NULL DEFAULT '".dbKITformData::status_active."'", dbKITformData::field_values)) {
        $error .= sprintf('[UPGRADE] %s', $dbKITformData->getError());
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

// remove Droplets
$dbDroplets = new dbDroplets();
$droplets = array('kit_form');
foreach ($droplets as $droplet) {
	$where = array(dbDroplets::field_name => $droplet);
	if (!$dbDroplets->sqlDeleteRecord($where)) {
		$message = sprintf('[UPGRADE] Error uninstalling Droplet: %s', $dbDroplets->getError());
	}
}
// Install Droplets
$droplets = new checkDroplets();
$droplets->droplet_path = WB_PATH.'/modules/kit_form/droplets/';
if ($droplets->insertDropletsIntoTable()) {
  $message .= $I18n->translate('The droplets for kitForm were successfully installed.\n');
}
else {
  $message .= $I18n->translate('Error installing the Droplets for kitForm:\n{{ error }}\n',
          array('error' => $droplets->getError()));
}
if ($message != "") {
  echo '<script language="javascript">alert ("'.$message.'");</script>';
}


// Prompt Errors
if (!empty($error))
	$admin->print_error($error);
