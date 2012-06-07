/**
 * kitForm
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2011 - 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

function move_up(link,position,number,leptoken) {
	var x;
	x = link + '&' + position + '=' + number + '&leptoken=' + leptoken;
	window.location = x;
	return false;
} // move_up
