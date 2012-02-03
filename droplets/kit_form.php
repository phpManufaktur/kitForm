//:interface to kitForm
//:Please visit http://phpManufaktur.de for informations about kitForm!
/**
 * kitForm
 * 
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2011-2012 - phpManufaktur by Ralf Hertsch
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 * 
 * FOR VERSION- AND RELEASE NOTES PLEASE LOOK AT INFO.TXT!
 */

if (file_exists(WB_PATH.'/modules/kit_uploader/info.php') && isset($form)) {
    // load the jQuery preset for uploadify if needed
    global $database;
    $SQL = "SELECT form_id FROM ".TABLE_PREFIX."mod_kit_form WHERE form_name = '$form'";
    $fid = $database->get_one($SQL);
    $SQL = "SELECT field_type_add FROM ".TABLE_PREFIX."mod_kit_form_fields WHERE form_id='$fid' AND field_type='file'";
    if (false !== ($query = $database->query($SQL))) {
        while (false !== ($add = $query->fetchRow(MYSQL_ASSOC))) {
            parse_str($add['field_type_add'], $settings);
            if (isset($settings['upload_method']['value']) && ($settings['upload_method']['value'] == 'uploadify')) {
                // load jQuery preset for uploadify
                include_once WB_PATH.'/modules/libraryadmin/include.php';
                $new_page = includePreset($wb_page_data, 'lib_jquery', 'kit_uploadify', 'kit_uploader', NULL, false, NULL, NULL );
                if (!empty($new_page)) {
                    $wb_page_data = $new_page;
                }
                break;
            }
        }
    }
} 
 
if (file_exists(WB_PATH.'/modules/kit_form/class.frontend.php')) {
	require_once(WB_PATH.'/modules/kit_form/class.frontend.php');
	$formular = new formFrontend();
	$params = $formular->getParams();
	$params[formFrontend::param_form] = (isset($form)) ? strtolower(trim($form)) : '';
	$params[formFrontend::param_preset] = (isset($preset)) ? (int) $preset : 1;
	$params[formFrontend::param_css] = (isset($css) && (strtolower($css) == 'false')) ? false : true;
	$params[formFrontend::param_auto_login_wb] = (isset($auto_login_wb) && (strtolower($auto_login_wb) == 'true')) ? true : false;
	if (!$formular->setParams($params)) return $formular->getError();
	return $formular->action();
}
else {
	return "kitForm is not installed!";
}