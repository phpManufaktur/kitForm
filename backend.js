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
function move_up(link,position,number,leptoken) {
	var x;
	x = link + '&' + position + '=' + number + '&leptoken=' + leptoken;
	window.location = x;
	return false;
} // move_up
