<?php

/**
 * kitForm
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2011 - 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

// Mindestparameter gesetzt?
if (!isset($_POST['rowID']) || !isset($_POST['sorter_table']) ||
    !isset($_POST['sorter_value']) || !is_numeric($_POST['sorter_value'])) exit();
// Sorter ausgeschaltet?
if (isset($_POST['sorter_active']) && ($_POST['sorter_active'] == 0)) exit();

require_once('../../config.php');
require_once(WB_PATH.'/framework/initialize.php');

global $database;

/**
 * Sanitize a text variable and prepare it for saving in a MySQL record
 *
 * @param string $text
 * @return string
 */
function sanitizeText ($text)
{
    $search = array("<",">","\"","'","\\","\x00","\n","\r","'",'"',"\x1a");
    $replace = array("&lt;","&gt;","&quot;","&#039;","\\\\","\\0","\\n","\\r","\'",'\"',"\\Z");
    return str_replace($search, $replace, $text);
}

$sorter_table = sanitizeText($_POST['sorter_table']);

switch ($sorter_table):

case 'mod_kit_form':
    // Frageboegen sortieren
    $ids = $_POST['rowID'];
    if (!is_array($ids)) {
        exit();
    }
    foreach ($ids as $id) {
        if (!is_numeric($id)) {
            exit();
        }
    }
    $rowIDs = implode(',', $ids);
    $sorter_value = $_POST['sorter_value'];
    $SQL = sprintf(    "UPDATE %smod_kit_form_table_sort SET sort_order='%s' WHERE sort_table='%s' AND sort_value='%s'",
                                    TABLE_PREFIX, $rowIDs, $sorter_table, $sorter_value);
    $database->query($SQL);
    if ($database->is_error()) {
        echo $database->get_error();
    }
    else {
        echo "Sorted: $rowIDs";
    }
    break;
default:
    echo "no handling defined for: ".$_POST['sorter_table'];
endswitch;
