<?php

/**
 * kitDirList
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


// include dbConnect
if (! class_exists('dbConnectLE')) require_once (WB_PATH . '/modules/dbconnect_le/include.php');

class dbKITdirList extends dbConnectLE {
    
    const field_id = 'dl_id';
    const field_reference = 'dl_reference';
    const field_file_origin = 'dl_file_origin';
    const field_file = 'dl_file';
    const field_path = 'dl_path';
    const field_count = 'dl_count';
    const field_date = 'dl_date';
    const field_user = 'dl_user';
    const field_update = 'dl_update';
    
    private $create_tables = false;

    /**
     * Constructor for dbKITdirList
     * @param boolean $create_tables
     * @version 0.26 - also used by kitForm !!!
     */
    public function __construct($create_tables = false) {
        parent::__construct();
        $this->create_tables = $create_tables;
        $this->setTableName('mod_kit_dirlist');
        $this->addFieldDefinition(self::field_id, "INT NOT NULL AUTO_INCREMENT", true);
        $this->addFieldDefinition(self::field_reference, "VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addFieldDefinition(self::field_file_origin, "VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addFieldDefinition(self::field_file, "VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addFieldDefinition(self::field_path, "TEXT NOT NULL DEFAULT ''");
        $this->addFieldDefinition(self::field_count, "INT(11) NOT NULL DEFAULT '0'");
        $this->addFieldDefinition(self::field_date, "DATETIME DEFAULT '0000-00-00 00:00:00'");
        $this->addFieldDefinition(self::field_user, "VARCHAR(128) NOT NULL DEFAULT ''");
        $this->addFieldDefinition(self::field_update, "TIMESTAMP");
        // check field definitions
        $this->checkFieldDefinitions();
        // set default timezone
        date_default_timezone_set('Europe/Berlin');
        // create tables
        if ($this->create_tables) {
            if (! $this->sqlTableExists()) {
                if (! $this->sqlCreateTable()) {
                    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->getError()));
                    return false;
                }
            }
        }
        return true;
    } // __construct()


} // class dbKITdirList


?>