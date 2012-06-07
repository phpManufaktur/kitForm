//:Place a kitForm dialog, feedback, action or form everywhere you need it
//:Usage: [[kit_form?form=FORM_NAME]] - specify the name of the form defined in kitForm. Optional Parameters: preset=NUMBER - select the Preset /kit_form/htt/NUMBER, default is 1. css=TRUE|FALSE - use the CSS /kit_form/kit_form.css, default is TRUE, needs DropletsExtension. auto_login_lepton=TRUE|FALSE - enables an automatic login at the KIT interface for authenticated LEPTON users. language=LANG_CODE - if set kitIdea ignore the language settings of the page and use this language instead. fallback_preset=NUMBER - the preset kitIdea should use if a template does not exists in the specified preset directory, default is 1. fallback_language=LANG_CODE - the language kitIdea should use if a template does not exists in the needed language, default is DE (german). debug=TRUE|FALSE - switch the template debugging on or off, default is FALSE.
/**
 * kitForm
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2011 - 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
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
	$params[formFrontend::PARAM_FORM] = (isset($form)) ? strtolower(trim($form)) : '';
	$params[formFrontend::PARAM_PRESET] = (isset($preset)) ? (int) $preset : 1;
	$params[formFrontend::PARAM_CSS] = (isset($css) && (strtolower($css) == 'false')) ? false : true;
	if (isset($auto_login_wb)) {
	    // for downwards compatibility only
	    $params[formFrontend::PARAM_AUTO_LOGIN_LEPTON] = (strtolower($auto_login_wb) == 'true') ? true : false;
	}
	else {
	    $params[formFrontend::PARAM_AUTO_LOGIN_LEPTON] = (isset($auto_login_lepton) && (strtolower($auto_login_lepton) == 'true')) ? true : false;
	}
	$params[formFrontend::PARAM_LANGUAGE] = (isset($language)) ? strtoupper($language) : LANGUAGE;
	$params[formFrontend::PARAM_FALLBACK_LANGUAGE] = (isset($fallback_language)) ? strtoupper($fallback_language) : 'DE';
	$params[formFrontend::PARAM_FALLBACK_PRESET] = (isset($fallback_preset)) ? (int) $fallback_preset : 1;
	$params[formFrontend::PARAM_DEBUG] = (isset($debug) && (strtolower($debug) == 'true')) ? true : false;
	if (!$formular->setParams($params)) return $formular->getError();
	return $formular->action();
}
else {
	return "kitForm is not installed!";
}