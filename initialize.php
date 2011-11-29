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
    if (defined('LEPTON_VERSION')) include (WB_PATH . '/framework/class.secure.php');
} else {
    $oneback = "../";
    $root = $oneback;
    $level = 1;
    while (($level < 10) && (! file_exists($root . '/framework/class.secure.php'))) {
        $root .= $oneback;
        $level += 1;
    }
    if (file_exists($root . '/framework/class.secure.php')) {
        include ($root . '/framework/class.secure.php');
    } else {
        trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
    }
}
// end include class.secure.php


// for extended error reporting set to true!
if (! defined('KIT_DEBUG')) define('KIT_DEBUG', true);
require_once (WB_PATH . '/modules/kit_tools/debug.php');

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

if (! class_exists('Dwoo')) require_once (WB_PATH . '/modules/dwoo/include.php');
if (! class_exists('kitContactInterface')) require_once (WB_PATH . '/modules/kit/class.interface.php');
if (! class_exists('kitToolsLibrary')) require_once (WB_PATH . '/modules/kit_tools/class.tools.php');

require_once (WB_PATH . '/modules/' . basename(dirname(__FILE__)) . '/class.form.php');

global $parser;
global $kitLibrary;

if (! is_object($parser)) $parser = new Dwoo();
if (! is_object($kitLibrary)) $kitLibrary = new kitToolsLibrary();

?>