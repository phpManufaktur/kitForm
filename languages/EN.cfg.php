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

if (!defined('cfg_currency'))
    define('cfg_currency', '$ %s');
if (!defined('cfg_date_separator'))
    define('cfg_date_separator', '/');
if (!defined('cfg_date_str'))
    define('cfg_date_str', 'm/d/Y');
if (!defined('cfg_datetime_str'))
    define('cfg_datetime_str', 'm/d/Y h:i a');
if (!defined('cfg_day_names'))
    define('cfg_day_names', "Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday");
if (!defined('cfg_decimal_separator'))
    define('cfg_decimal_separator', '.');
if (!defined('cfg_month_names'))
    define('cfg_month_names', "January,February,March,April,May,June,July,August,September,October,November,December");
if (!defined('cfg_thousand_separator'))
    define('cfg_thousand_separator', ',');
if (!defined('cfg_time_long_str'))
    define('cfg_time_long_str', 'h:i:s a');
if (!defined('cfg_time_str'))
    define('cfg_time_str', 'h:i a');
if (!defined('cfg_time_zone'))
    define('cfg_time_zone', 'America/Chicago');
if (!defined('cfg_title'))
    define('cfg_title', 'Mister,Lady');
