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

// Checking Requirements 

$PRECHECK['WB_VERSION'] = array('VERSION' => '2.8', 'OPERATOR' => '>=');
$PRECHECK['PHP_VERSION'] = array('VERSION' => '5.2.0', 'OPERATOR' => '>=');
$PRECHECK['WB_ADDONS'] = array(
	'dbconnect_le'	=> array('VERSION' => '0.65', 'OPERATOR' => '>='),
	'dwoo' => array('VERSION' => '0.11', 'OPERATOR' => '>='),
	'droplets' => array('VERSION' => '1.0', 'OPERATOR' => '>='),
	'droplets_extension' => array('VERSION' => '0.16', 'OPERATOR' => '>='),
	'kit' => array('VERSION' => '0.51', 'OPERATOR' => '>='),
	'kit_tools' => array('VERSION' => '0.15', 'OPERATOR' => '>=')
);

/* must be removed, otherwise we'll create an endless loop ... 
// if kitDirList is installed it must at minimum 0.27 ...
if (file_exists(WB_PATH.'/modules/kit_dirlist/include.php')) {
    $PRECHECK['WB_ADDONS']['kit_dirlist'] = array('VERSION' => '0.27', 'OPERATOR' => '>=');
}
*/

global $database;  
$sql = "SELECT `value` FROM `".TABLE_PREFIX."settings` WHERE `name`='default_charset'";
$result = $database->query($sql);
if ($result) {
	$data = $result->fetchRow(MYSQL_ASSOC);
	$PRECHECK['CUSTOM_CHECKS'] = array(
		'Default Charset' => array(
			'REQUIRED' => 'utf-8',
			'ACTUAL' => $data['value'],
			'STATUS' => ($data['value'] === 'utf-8')
		)
	);
}


?>