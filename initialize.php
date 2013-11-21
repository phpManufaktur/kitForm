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

if (!defined('LEPTON_PATH'))
  require_once WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/wb2lepton.php';

// for extended error reporting set to true!
if (!defined('KIT_DEBUG')) define('KIT_DEBUG', true);

require_once (LEPTON_PATH . '/modules/kit_tools/debug.php');

// use LEPTON 2.x I18n for access to language files
if (!class_exists('CAT_Helper_I18n') && !class_exists('LEPTON_Helper_I18n')) {
    require_once LEPTON_PATH . '/modules/' . basename(dirname(__FILE__)) . '/framework/LEPTON/Helper/I18n.php';
}

global $I18n;

if (!is_object($I18n)) {
    if (class_exists('CAT_Helper_I18n')) {
        // this is a BlackCat environment
        $I18n = new CAT_Helper_I18n(array('lang' => LANGUAGE));
    }
    else {
        // all other environments
        $I18n = new LEPTON_Helper_I18n(array('lang' => LANGUAGE));
    }
}
else {
    $I18n->addFile('DE.php', LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/languages/');
}

if (! file_exists(LEPTON_PATH . '/modules/' . basename(dirname(__FILE__)) . '/languages/' . LANGUAGE . '.php')) {
    if (! defined('KIT_FORM_LANGUAGE')) define('KIT_FORM_LANGUAGE', 'DE'); // important: language flag is used by template selection
} else {
    if (! defined('KIT_FORM_LANGUAGE')) define('KIT_FORM_LANGUAGE', LANGUAGE);
}
// load language depending onfiguration
if (!file_exists(LEPTON_PATH.'/modules/' . basename(dirname(__FILE__)) . '/languages/' . LANGUAGE . '.cfg.php')) {
    require_once(LEPTON_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/DE.cfg.php');
} else {
    require_once(LEPTON_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.cfg.php');
}

if (! class_exists('kitContactInterface')) require_once (LEPTON_PATH . '/modules/kit/class.interface.php');
if (! class_exists('kitToolsLibrary')) require_once (LEPTON_PATH . '/modules/kit_tools/class.tools.php');

require_once (LEPTON_PATH . '/modules/' . basename(dirname(__FILE__)) . '/class.form.php');

if (!class_exists('Dwoo'))
  require_once LEPTON_PATH.'/modules/dwoo/include.php';

// initialize the template engine
global $parser;
if (!is_object($parser)) {
  $cache_path = LEPTON_PATH.'/temp/cache';
  if (!file_exists($cache_path)) mkdir($cache_path, 0755, true);
  $compiled_path = LEPTON_PATH.'/temp/compiled';
  if (!file_exists($compiled_path)) mkdir($compiled_path, 0755, true);
  $parser = new Dwoo($compiled_path, $cache_path);
}

global $kitLibrary;
if (! is_object($kitLibrary)) $kitLibrary = new kitToolsLibrary();

// if kitDirList is not installed use framework and create table if needed
global $dbKITdirList;
if (file_exists(LEPTON_PATH.'/modules/kit_dirlist/class.link.php')) {
    require_once LEPTON_PATH.'/modules/kit_dirlist/class.link.php';
}
else {
    require_once LEPTON_PATH.'/modules/kit_form/framework/KIT/kit_dirlist/class.link.php';
}
if (!is_object($dbKITdirList)) {
    $dbKITdirList = new dbKITdirList();
    if (!$dbKITdirList->sqlTableExists()) $dbKITdirList->sqlCreateTable();
}
if (!$dbKITdirList->sqlFieldExists(dbKITdirList::field_reference)) {
    // add the additional field for references
    $dbKITdirList->sqlAlterTableAddField(dbKITdirList::field_reference, "VARCHAR(255) NOT NULL DEFAULT ''", dbKITdirList::field_id);
    $dbKITdirList->sqlAlterTableAddField(dbKITdirList::field_file_orgin, "VARCHAR(255) NOT NULL DEFAULT ''", dbKITdirList::field_id);
}

?>
