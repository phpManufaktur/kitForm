//:interface to kitForm
//:Please visit http://phpManufaktur.de for informations about kitForm!
/**
 * kitForm
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 */
if (file_exists(WB_PATH.'/modules/kit_form/class.frontend.php')) {
	require_once(WB_PATH.'/modules/kit_form/class.frontend.php');
	$formular = new formFrontend();
	$params = $formular->getParams();
	$params[formFrontend::param_form] = (isset($form)) ? strtolower(trim($form)) : '';
	$params[formFrontend::param_preset] = (isset($preset)) ? (int) $preset : 1;
	if (!$formular->setParams($params)) return $formular->getError();
	return $formular->action();
}
else {
	return "kitForm is not installed!";
}