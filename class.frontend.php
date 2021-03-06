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

require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/initialize.php');
require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.backend.php');
require_once(WB_PATH.'/include/captcha/captcha.php');
require_once(WB_PATH.'/framework/class.wb.php');
require_once(WB_PATH.'/modules/kit/class.mail.php');
if (!defined('CAT_VERSION')) {
    require_once(WB_PATH.'/modules/droplets_extension/interface.php');
}
require_once(WB_PATH.'/framework/functions.php');

global $dbKITform;
global $dbKITformFields;
global $dbKITformTableSort;
global $dbKITformData;
global $dbKITformCommands;
global $dbMemos;

class formFrontend {

  const request_action = 'act';
  const request_link = 'link';
  const request_key = 'key';
  const request_activation_type = 'at';
  const request_provider_id = 'pid';
  const request_command = 'kfc';
  const request_form_id = 'fid';
  const REQUEST_SPECIAL_LINK = 'ksl'; // KIT Special Link = KSL

  const SESSION_SPECIAL_LINK = 'KIT_SPECIAL_LINK';

  const action_default = 'def';
  const action_check_form = 'acf';
  const action_command = 'cmd';
  const action_activation_key = 'key';
  const action_feedback_unsubscribe = 'fun';
  const action_feedback_unsubscribe_check = 'fnc';

  const activation_type_newsletter = 'nl';
  const activation_type_account = 'acc';
  const activation_type_default = 'def';

  private $page_link = '';
  private $img_url = '';
  private $template_path = '';
  private $error = '';
  private $message = '';
  private $contact = array();

  const FIELD_FEEDBACK_TEXT = 'feedback_text';
  const FIELD_FEEDBACK_URL = 'feedback_url';
  const FIELD_FEEDBACK_PUBLISH = 'feedback_publish';
  const FIELD_FEEDBACK_SUBSCRIPTION = 'feedback_subscription';
  const FIELD_FEEDBACK_HOMEPAGE = 'feedback_homepage';
  const FIELD_FEEDBACK_SUBJECT = 'feedback_subject';
  const FIELD_FEEDBACK_NICKNAME = 'feedback_nickname';

  const PUBLISH_IMMEDIATE = 1;
  const PUBLISH_ACTIVATION = 2;
  const PUBLISH_FORBIDDEN = 4;

  const SUBSCRIPE_YES = 1;
  const SUBSCRIPE_NO = 0;

  const FORM_ANCHOR = 'kf';

  const PARAM_AUTO_LOGIN_LEPTON = 'auto_login_lepton';
  const PARAM_CSS = 'css';
  const PARAM_DEBUG = 'debug';
  const PARAM_DELAY_SECONDS = 'delay';
  const PARAM_FALLBACK_LANGUAGE = 'fallback_language';
  const PARAM_FALLBACK_PRESET = 'fallback_preset';
  const PARAM_FORM = 'form';
  const PARAM_LANGUAGE = 'language';
  const PARAM_PRESET = 'kf_preset';
  const PARAM_RETURN = 'return';

  private $params = array(
      self::PARAM_AUTO_LOGIN_LEPTON => false,
      self::PARAM_CSS => true,
      self::PARAM_DEBUG => false,
      self::PARAM_DELAY_SECONDS => 20,
      self::PARAM_FALLBACK_LANGUAGE => 'DE',
      self::PARAM_FALLBACK_PRESET => 1,
      self::PARAM_FORM => '',
      self::PARAM_LANGUAGE => KIT_FORM_LANGUAGE,
      self::PARAM_PRESET => 1,
      self::PARAM_RETURN => false,
      );

  protected $lang;

  // protected folder for uploads - uses kitDirList sheme!
  const PROTECTION_FOLDER = 'kit_protected';
  const CONTACTS_FOLDER = 'contacts';
  const USER_FOLDER = 'user';

  protected $general_excluded_extensions = array(
      'php',
      'php3',
      'php4',
      'php5',
      'php6',
      'phps',
      'js',
      'htm',
      'html',
      'shtml');

  protected static $table_prefix = TABLE_PREFIX;

  /**
   * Constructor for kitForm
   */
  public function __construct() {
    global $I18n;
    global $kitLibrary;
    $url = '';
    $_SESSION['FRONTEND'] = true;
    $kitLibrary->getPageLinkByPageID(PAGE_ID, $url);
    $this->page_link = $url;
    $this->template_path = WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/htt/';
    $this->img_url = WB_URL.'/modules/'.basename(dirname(__FILE__)).'/images/';
    date_default_timezone_set(cfg_time_zone);
    $this->lang = $I18n;
    $this->lang->addFile('DE.php', WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/languages/');
    $this->lang->addFile('EN.php', WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/languages/');
    // use another table prefix or change protocol limit?
    if (file_exists(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/config.json')) {
      $config = json_decode(file_get_contents(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/config.json'), true);
      if (isset($config['table_prefix']))
        self::$table_prefix = $config['table_prefix'];
    }
  } // __construct()


  /**
   * Check dependency to to other KIT modules
   *
   * @return boolean true on success
   */
  public function checkDependency() {
    // check dependency for KIT
    global $PRECHECK;
    global $database;

    // need the precheck.php
    require_once(WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/precheck.php');

    if (isset($PRECHECK['KIT']['kit'])) {
      $table = self::$table_prefix.'addons';
      $version = $database->get_one("SELECT `version` FROM $table WHERE `directory`='kit'", MYSQL_ASSOC);
      if (!version_compare($version, $PRECHECK['KIT']['kit']['VERSION'], $PRECHECK['KIT']['kit']['OPERATOR'])) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
            $this->lang->translate('Error: Please upgrade <b>{{ addon }}</b>, installed is release <b>{{ release }}</b>, needed is release <b>{{ needed }}</b>.',
                array('addon' => 'KeepInTouch', 'release' => $version, 'needed' => $PRECHECK['KIT']['kit']['VERSION']))));
        return false;
      }
    }
    if (file_exists(WB_PATH.'/modules/kit_dirlist/info.php')) {
      // check only if kitDirList is installed
      if (isset($PRECHECK['KIT']['kit_dirlist'])) {
        $table = self::$table_prefix.'addons';
        $version = $database->get_one("SELECT `version` FROM $table WHERE `directory`='kit_dirlist'", MYSQL_ASSOC);
        if (!version_compare($version, $PRECHECK['KIT']['kit_dirlist']['VERSION'], $PRECHECK['KIT']['kit_dirlist']['OPERATOR'])) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
            $this->lang->translate('Error: Please upgrade <b>{{ addon }}</b>, installed is release <b>{{ release }}</b>, needed is release <b>{{ needed }}</b>.',
                array('addon' => 'kitDirList', 'release' => $version, 'needed' => $PRECHECK['KIT']['kit_dirlist']['VERSION']))));
          return false;
        }
      }
    } // if file_exists()
    return true;
  } // checkDependency()

  /**
   * Get the parameters - this function is important for the kit_form droplet.
   *
   * @return array $params
   */
  public function getParams() {
    return $this->params;
  } // getParams()

  /**
   * Set the parameters - this function will be called by the kit_form
   * droplet.
   *
   * @param $params array
   * @return boolean true on success
   */
  public function setParams($params = array()) {
    $this->params = $params;
    // check only the preset path but not the subdirectories with the languages!
    if (!file_exists($this->template_path.$this->params[self::PARAM_PRESET])) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The preset directory <b>{{ directory }}</b> does not exists, can\'t load any template!', array(
          'directory' => '/modules/kit_form/htt/'.$this->params[self::PARAM_PRESET].'/'))));
      return false;
    }
    return true;
  } // setParams()

  /**
   * Set $this->error to $error
   *
   * @param $error string
   */
  public function setError($error) {
    $this->error = $error;
  } // setError()

  /**
   * Get Error from $this->error;
   *
   * @return string $this->error
   */
  public function getError() {
    return $this->error;
  } // getError()

  /**
   * Check if $this->error is empty
   *
   * @return boolean
   */
  public function isError() {
    return (bool) !empty($this->error);
  } // isError

  /**
   * Set the contact array of the user
   *
   * @param $contact array
   */
  protected function setContact($contact) {
    $this->contact = $contact;
  } // setContact();

  /**
   * Return the contact array of the user
   *
   * @return array
   */
  protected function getContact() {
    return $this->contact;
  } // getContact()

  /**
   * Reset Error to empty String
   */
  protected function clearError() {
    $this->error = '';
  }

  /**
   * Set $this->message to $message
   *
   * @param $message string
   */
  public function setMessage($message) {
    $this->message = $message;
  } // setMessage()

  /**
   * Get Message from $this->message;
   *
   * @return string $this->message
   */
  public function getMessage() {
    return $this->message;
  } // getMessage()

  /**
   * Check if $this->message is empty
   *
   * @return boolean
   */
  public function isMessage() {
    return (bool) !empty($this->message);
  } // isMessage

  /**
   * Execute the desired template and return the completed template
   *
   * @param $template string
   *            - the filename of the template without path
   * @param $template_data array
   *            - the template data
   * @return string template or boolean false on error
   */
  protected function getTemplate($template, $template_data) {
    global $parser;
    $template_path = $this->template_path.$this->params[self::PARAM_PRESET].'/'.$this->params[self::PARAM_LANGUAGE].'/'.$template;
    if (!file_exists($template_path)) {
      // template does not exist - fallback to default language!
      $template_path = $this->template_path.$this->params[self::PARAM_PRESET].'/'.$this->params[self::PARAM_FALLBACK_LANGUAGE].'/'.$template;
      if (!file_exists($template_path)) {
        // template does not exists - fallback to the default preset!
        $template_path = $this->template_path.$this->params[self::PARAM_FALLBACK_PRESET].'/'.$this->params[self::PARAM_LANGUAGE].'/'.$template;
        if (!file_exists($template_path)) {
          // template does not exists - fallback to the default preset and the default language
          $template_path = $this->template_path.$this->params[self::PARAM_FALLBACK_PRESET].'/'.$this->params[self::PARAM_FALLBACK_LANGUAGE].'/'.$template;
          if (!file_exists($template_path)) {
            // template does not exists in any possible path - give up!
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Error: The template {{ template }} does not exists in any of the possible paths!', array(
                'template',
                $template))));
            return false;
          }
        }
      }
    }

    // add the template_path to the $template_data (for debugging purposes)
    if (!isset($template_data['template_path']))
      $template_data['template_path'] = $template_path;
    // add the debug flag to the $template_data
    if (!isset($template_data['DEBUG']))
      $template_data['DEBUG'] = (int) $this->params[self::PARAM_DEBUG];

    try {
      // try to execute the template with Dwoo
      $result = $parser->get($template_path, $template_data);
    }
    catch (Exception $e) {
      // prompt the Dwoo error
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Error executing template <b>{{ template }}</b>:<br />{{ error }}', array(
          'template' => $template,
          'error' => $e->getMessage()))));
      return false;
    }
    return $result;
  } // getTemplate()

  /**
   * Verhindert XSS Cross Site Scripting
   *
   * @param
   *            reference array $request
   * @return $request
   */
  protected function xssPrevent(&$request) {
    if (is_string($request)) {
      $request = html_entity_decode($request);
      $request = strip_tags($request);
      $request = trim($request);
      $request = stripslashes($request);
    }
    return $request;
  } // xssPrevent()

  /**
   * The action handler of kitForm - call this function after creating a new
   * instance of kitForm!
   *
   * @return string result
   */
  public function action() {
    // we can ignore calls by DropletsExtions...
    if (isset($_SESSION['DROPLET_EXECUTED_BY_DROPLETS_EXTENSION'])) return '- passed call by DropletsExtension -';

    // CSS laden?
    if (defined('CAT_VERSION')) {
        if ($this->params[self::PARAM_CSS]) {
            if (!CAT_Helper_Droplet::is_registered_droplet_css('kit_form', PAGE_ID)) {
                CAT_Helper_Droplet::register_droplet_css('kit_form', PAGE_ID, 'kit_form', 'kit_form.css');
            }
        }
        elseif (CAT_Helper_Droplet::is_registered_droplet_css('kit_form', PAGE_ID)) {
            CAT_Helper_Droplet::unregister_droplet_css('kit_form', PAGE_ID);
        }
    }
    else {
        if ($this->params[self::PARAM_CSS]) {
          if (!is_registered_droplet_css('kit_form', PAGE_ID)) {
            register_droplet_css('kit_form', PAGE_ID, 'kit_form', 'kit_form.css');
          }
        }
        elseif (is_registered_droplet_css('kit_form', PAGE_ID)) {
          unregister_droplet_css('kit_form', PAGE_ID);
        }
    }

    // check dependency
    $this->checkDependency();

    if ($this->isError())
      return sprintf('<a name="%s"></a><div class="error">%s</div>', self::FORM_ANCHOR, $this->getError());

    /**
     * to prevent cross site scripting XSS it is important to look also to
     * $_REQUESTs which are needed by other KIT addons. Addons which need
     * a $_REQUEST with HTML should set a key in $_SESSION['KIT_HTML_REQUEST']
     */
    $html_allowed = array();
    if (isset($_SESSION['KIT_HTML_REQUEST']))
      $html_allowed = $_SESSION['KIT_HTML_REQUEST'];
    $html = array();
    foreach ($html as $key)
      $html_allowed[] = $key;
    $_SESSION['KIT_HTML_REQUEST'] = $html_allowed;
    foreach ($_REQUEST as $key => $value) {

      if (stripos($key, 'amp;') == 0) {
        $key = substr($key, 4);
        $_REQUEST[$key] = $value;
        unset($_REQUEST['amp;'.$key]);
      }

      if (!in_array($key, $html_allowed)) {
        $_REQUEST[$key] = $this->xssPrevent($value);
      }
    }

    isset($_REQUEST[self::request_action]) ? $action = $_REQUEST[self::request_action] : $action = self::action_default;
    switch ($action) :
      case self::action_feedback_unsubscribe:
        $result = $this->showFeedbackUnsubscribe();
        break;
      case self::action_feedback_unsubscribe_check:
        $result = $this->checkFeedbackUnsubscribe();
        break;
      case self::action_command:
        $result = $this->checkCommand();
        break;
      case self::action_check_form:
        $result = $this->checkForm();
        break;
      case self::action_activation_key:
        $result = $this->checkActivationKey();
        break;
      case self::action_default:
      default:
        $result = $this->showForm();
        break;
    endswitch;

    if ($this->isError())
      $result = sprintf('<a name="%s"></a><div class="error">%s</div>', self::FORM_ANCHOR, $this->getError());
    return $result;
  } // action

  /**
   * Check for KIT Special Links
   *
   * @return boolean
   */
  private function checkSpecialLink() {
    global $database;

    if (isset($_GET[self::REQUEST_SPECIAL_LINK]) || isset($_SESSION[self::SESSION_SPECIAL_LINK])) {
      $guid = (isset($_GET[self::REQUEST_SPECIAL_LINK])) ? $_GET[self::REQUEST_SPECIAL_LINK] : $_SESSION[self::SESSION_SPECIAL_LINK];
      $SQL = "SELECT * FROM `".self::$table_prefix."mod_kit_links` WHERE `guid`='$guid'";
      $query = $database->query($SQL);
      if ($database->is_error()) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
        return false;
      }
      if ($query->numRows() == 1) {
        $link = $query->fetchRow(MYSQL_ASSOC);
        if ($link['type'] == 'UPLOAD') {
          // handle an upload link
          if (($link['status'] != 'ACTIVE') || (($link['option'] == 'THROW-AWAY') && ($link['count'] > 0))) {
            // throw away link was already used!
            $this->setMessage($this->lang->translate('<p>The link <b>{{ guid }}</b> was already used and is no longer valid! Please contact the support.</p>', array('guid' => $guid)));
            return false;
          }
          $SQL = "SELECT `contact_email`, `contact_email_standard` FROM `".self::$table_prefix."mod_kit_contact` WHERE `contact_id`='{$link['kit_id']}'";
          $query = $database->query($SQL);
          if ($database->is_error()) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
            return false;
          }
          $contact = $query->fetchRow(MYSQL_ASSOC);
          $email_array = explode(';', $contact['contact_email']);
          if (!isset($email_array[$contact['contact_email_standard']])) {
            // no valid email address
            $this->setMessage($this->lang->translate('<p>Sorry, got no valid email address for the GUID <b>{{ guid }}</b>! Please contact the support!</p>',
                array('guid' => $guid)));
            return false;
          }
          list($email_type, $email) = explode('|', $email_array[$contact[dbKITcontact::field_email_standard]]);
          // set the email address as _$_REQUEST
          $_REQUEST[kitContactInterface::kit_email] = $email;
          // set a session var to indicate the special link
          $_SESSION[self::SESSION_SPECIAL_LINK] = $guid;
          $this->setMessage($this->lang->translate('<p>Please start the file upload for the GUID <b>{{ guid }}</b>.</p>', array('guid' => $guid)));
        }
      }
    }
    return true;
  } // checkSpecialLink()

  /**
   * Process a KIT special link
   *
   * @return boolean
   */
  private function processSpecialLink($message) {
    global $database;

    if (!isset($_SESSION[self::SESSION_SPECIAL_LINK]))
      return false;

    $SQL = "SELECT * FROM `".self::$table_prefix."mod_kit_links` WHERE `guid`='{$_SESSION[self::SESSION_SPECIAL_LINK]}'";
    $query = $database->query($SQL);
    if ($database->is_error()) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
      return false;
    }
    if ($query->numRows() == 1) {
      $link = $query->fetchRow(MYSQL_ASSOC);
      $count = $link['count']+1;
      $last_call = date('Y-m-d H:i:s');
      if ($link['option'] == 'THROW-AWAY') {
        $SQL = "UPDATE `".self::$table_prefix."mod_kit_links` SET `status`='LOCKED', `count`='$count', `last_call`='$last_call' WHERE `guid`='{$_SESSION[self::SESSION_SPECIAL_LINK]}'";
      }
      else {
        $SQL = "UPDATE `".self::$table_prefix."mod_kit_links` SET `count`='$count', `last_call`='$last_call' WHERE `guid`='{$_SESSION[self::SESSION_SPECIAL_LINK]}'";
      }
      $query = $database->query($SQL);
      if ($database->is_error()) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
        return false;
      }
      unset($_SESSION[self::SESSION_SPECIAL_LINK]);
      return true;
    }
    unset($_SESSION[self::SESSION_SPECIAL_LINK]);
    return false;
  } // processSpecialLink()

  /**
   * This master function collects all datas of a form, prepare and return
   * the complete form
   *
   * @return string form or boolean false on error
   */
  protected function showForm($clear_fields=false) {
    global $dbKITform;
    global $dbKITformFields;
    global $kitContactInterface;
    global $kitLibrary;
    global $dbContactAddress;
    global $dbCfg;

    if (empty($this->params)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The form name is empty, please check the parameters for the droplet!')));
      return false;
    }

    // check for special links
    $this->checkSpecialLink();

    $form_id = -1;
    $form_name = 'none';

    // special: feedback form
    $is_feedback_form = false;
    // special: file upload
    $is_file_upload = false;

    if (isset($_REQUEST[self::request_link])) {
      $form_name = $_REQUEST[self::request_link];
    }
    elseif (isset($_REQUEST[dbKITform::field_id])) {
      $form_id = $_REQUEST[dbKITform::field_id];
    }
    else {
      $form_name = $this->params[self::PARAM_FORM];
    }

    if ($form_id > 0) {
      $SQL = sprintf("SELECT * FROM %s WHERE %s='%s'", $dbKITform->getTableName(), dbKITform::field_id, $form_id);
    }
    else {
      $SQL = sprintf("SELECT * FROM %s WHERE %s='%s' AND %s='%s'", $dbKITform->getTableName(), dbKITform::field_name, $form_name, dbKITform::field_status, dbKITform::status_active);
    }
    $fdata = array();
    if (!$dbKITform->sqlExec($SQL, $fdata)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
      return false;
    }
    if (count($fdata) < 1) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Cant\'t load the form <b>{{ form }}</b>!', array(
          'form' => $form_name))));
      return false;
    }
    $fdata = $fdata[0];
    $form_id = $fdata[dbKITform::field_id];

    if ($fdata[dbKITform::field_action] == dbKITform::action_logout) {
      // Sonderfall: beim LOGOUT wird direkt der Bestaetigungsdialog angezeigt
      if ($kitContactInterface->isAuthenticated()) {
        // Abmelden und Verabschieden...
        return $this->Logout();
      }
      else {
        // Benutzer ist nicht angemeldet...
        $data = array(
            'message' => $this->lang->translate('<p>You are not authenticated, please login first!</p>'));
        return $this->getTemplate('prompt.htt', $data);
      }
    }
    elseif (($fdata[dbKITform::field_action] == dbKITform::action_account) || ($fdata[dbKITform::field_action] == dbKITform::action_change_password)) {
      // Das Benutzerkonto zum Bearbeiten anzeigen
      if ($kitContactInterface->isAuthenticated()) {
        // ok - User ist angemeldet
        $contact = array();
        if (!$kitContactInterface->getContact($_SESSION[kitContactInterface::session_kit_contact_id], $contact)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
          return false;
        }
        foreach ($contact as $key => $value) {
          if (!isset($_REQUEST[$key]))
            $_REQUEST[$key] = $value;
        }
      }
      else {
        // Dialog kann nicht angezeigt werden, Benutzer ist nicht angemeldet!
        $data = array(
            'message' => $this->lang->translate('<p>You are not authenticated, please login first!</p>'));
        return $this->getTemplate('prompt.htt', $data);
      }
    }

    // CAPTCHA
    ob_start();
    call_captcha();
    $call_captcha = ob_get_contents();
    ob_end_clean();

    // Links auslesen
    $parse = str_replace('&amp;', '&', $fdata[dbKITform::field_links]);
    parse_str($parse, $links);
    $links['command'] = sprintf('%s%s%s', $this->page_link, (strpos($this->page_link, '?') === false) ? '?' : '&', self::request_link);
    // Formulardaten
    $form_data = array(
        'name' => 'kit_form',
        'anchor' => self::FORM_ANCHOR,
        'action' => array(
            'link' => $this->page_link,
            'name' => self::request_action,
            'value' => self::action_check_form),
        'id' => array(
            'name' => dbKITform::field_id,
            'value' => $fdata[dbKitform::field_id]),
        'response' => ($this->isMessage()) ? $this->getMessage() : NULL,
        'btn' => array(
            'ok' => $this->lang->translate('OK'),
            'abort' => $this->lang->translate('Abort')),
        'title' => $fdata[dbKITform::field_title],
        'captcha' => array(
            'active' => ($fdata[dbKITform::field_captcha] == dbKITform::captcha_on) ? 1 : 0,
            'code' => $call_captcha),
        'kit_action' => array(
            'name' => dbKITform::field_action,
            'value' => $fdata[dbKITform::field_action]),
        'links' => $links,
        'wait' => array(
            'seconds' => array(
                'name' => 'wait_seconds',
                // we need milliseconds!
                'value' => $this->params[self::PARAM_DELAY_SECONDS]*100
            ),
            'start' => array(
                'name' => 'wait_start',
                'value' => time()
            )
        )
    );

    // Felder auslesen und Array aufbauen
    $fields_array = explode(',', $fdata[dbKITform::field_fields]);
    $must_array = explode(',', $fdata[dbKITform::field_must_fields]);
    $form_fields = array();
    $upload_id = (isset($_REQUEST['upload_id'])) ? $_REQUEST['upload_id'] : $kitLibrary->createGUID();
    foreach ($fields_array as $field_id) {
      if ($field_id < 100) {
        // IDs 1-99 sind fuer KIT reserviert
        if (false === ($field_name = array_search($field_id, $kitContactInterface->index_array))) {
          // $field_id nicht gefunden
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The field with the <b>ID {{ id }}</b> is no KIT datafield!', array(
              'id' => sprintf('%03d', $field_id)))));
          return false;
        }
        switch ($field_name) :
          case kitContactInterface::kit_title:
          case kitContactInterface::kit_title_academic:
          // Anrede und akademische Titel
            $title_array = array();
            if ($field_name == kitContactInterface::kit_title) {
              $kitContactInterface->getFormPersonTitleArray($title_array);
            }
            else {
              $kitContactInterface->getFormPersonTitleAcademicArray($title_array);
            }
            if (isset($_REQUEST[$field_name])) {
              $selected = $_REQUEST[$field_name];
              $new_array = array();
              foreach ($title_array as $title) {
                $title['checked'] = ($title['value'] == $selected) ? 1 : 0;
                $new_array[] = $title;
              }
              $title_array = $new_array;
            }
            $form_fields[$field_name] = array(
                'id' => $field_id,
                'type' => $field_name,
                'name' => $field_name,
                'value' => '',
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'label' => $kitContactInterface->field_array[$field_name],
                'hint' => $this->lang->translate('hint_'.$field_name),
                'titles' => $title_array);
            break;
          case kitContactInterface::kit_address_type:
          // Adresstyp auswaehlen
            $address_type_array = array();
            $kitContactInterface->getFormAddressTypeArray($address_type_array);
            if (isset($_REQUEST[$field_name])) {
              $selected = $_REQUEST[$field_name];
              $new_array = array();
              foreach ($address_type_array as $address_type) {
                $address_type['checked'] = ($address_type['value'] == $selected) ? 1 : 0;
                $new_array[] = $address_type;
              }
              $address_type_array = $new_array;
            }
            $form_fields[$field_name] = array(
                'id' => $field_id,
                'type' => $field_name,
                'name' => $field_name,
                'value' => 1,
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'label' => $kitContactInterface->field_array[$field_name],
                'hint' => $this->lang->translate('hint_'.$field_name),
                'address_types' => $address_type_array);
            break;
          case kitContactInterface::kit_first_name:
          case kitContactInterface::kit_last_name:
          case kitContactInterface::kit_company:
          case kitContactInterface::kit_department:
          case kitContactInterface::kit_fax:
          case kitContactInterface::kit_phone:
          case kitContactInterface::kit_phone_mobile:
          case kitContactInterface::kit_street:
          case kitContactInterface::kit_city:
          case kitContactInterface::kit_zip:
          case kitContactInterface::kit_email:
          case kitContactInterface::kit_email_retype:
          case kitContactInterface::kit_password:
          case kitContactInterface::kit_password_retype:
          case kitContactInterface::kit_birthday:
            $form_fields[$field_name] = array(
                'id' => $field_id,
                'type' => $field_name,
                'name' => $field_name,
                'value' => (isset($_REQUEST[$field_name])) ? $_REQUEST[$field_name] : '',
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'label' => $kitContactInterface->field_array[$field_name],
                'hint' => $this->lang->translate('hint_'.$field_name));
            break;
          case kitContactInterface::kit_free_field_1:
          case kitContactInterface::kit_free_field_2:
          case kitContactInterface::kit_free_field_3:
          case kitContactInterface::kit_free_field_4:
          case kitContactInterface::kit_free_field_5:
          case kitContactInterface::kit_free_note_1:
          case kitContactInterface::kit_free_note_2:
            if ($field_id < 36) {
              // additional field
              $additional_fields = $dbCfg->getValue(dbKITcfg::cfgAdditionalFields);
              foreach ($additional_fields as $add_field) {
                list($i, $val) = explode('|', $add_field);
                $i += 30; // add the KIT offset for the field
                // (KIT_FREE_FIELD_1 == 31)
                if ($i == $field_id) {
                  $label = $val;
                  break;
                }
              }
            }
            else {
              // additional note
              $additional_notes = $dbCfg->getValue(dbKITcfg::cfgAdditionalNotes);
              foreach ($additional_notes as $add_note) {
                list($i, $val) = explode('|', $add_note);
                $i += 35; // add the KIT offset for the note
                // (KIT_FREE_NOTE_1 == 36)
                if ($i == $field_id) {
                  $label = $val;
                  break;
                }
              }
            }
            $form_fields[$field_name] = array(
                'id' => $field_id,
                'type' => $field_name,
                'name' => $field_name,
                'value' => (isset($_REQUEST[$field_name])) ? $_REQUEST[$field_name] : '',
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'label' => $label,
                'hint' => $this->lang->translate('hint_'.$field_name));
            break;
          case kitContactInterface::kit_zip_city:
          // Auswahl fuer Postleitzahl und Stadt
            $form_fields[$field_name] = array(
                'id' => $field_id,
                'type' => $field_name,
                'name_zip' => kitContactInterface::kit_zip,
                'value_zip' => (isset($_REQUEST[kitContactInterface::kit_zip])) ? $_REQUEST[kitContactInterface::kit_zip] : '',
                'name_city' => kitContactInterface::kit_city,
                'value_city' => (isset($_REQUEST[kitContactInterface::kit_city])) ? $_REQUEST[kitContactInterface::kit_city] : '',
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'label' => $kitContactInterface->field_array[$field_name],
                'hint' => $this->lang->translate('hint_'.$field_name));
            break;
          case kitContactInterface::kit_newsletter:
            $newsletter_array = array();
            $kitContactInterface->getFormNewsletterArray($newsletter_array);
            if (isset($_REQUEST[$field_name])) {
              $select_array = (is_array($_REQUEST[$field_name])) ? $_REQUEST[$field_name] : explode(',', $_REQUEST[$field_name]);
              // $select_array = $_REQUEST[$field_name];
              $new_array = array();
              foreach ($newsletter_array as $newsletter) {
                $newsletter['checked'] = (in_array($newsletter['value'], $select_array)) ? 1 : 0;
                $new_array[$newsletter['value']] = $newsletter;
              }
              $newsletter_array = $new_array;
            }
            $form_fields[$field_name] = array(
                'id' => $field_id,
                'type' => $field_name,
                'name' => $field_name,
                'value' => '',
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'label' => $kitContactInterface->field_array[$field_name],
                'hint' => $this->lang->translate('hint_'.$field_name),
                'newsletters' => $newsletter_array);
            break;
          case kitContactInterface::kit_country:
            $country_array = array();
            $country_array[] = array(
                'value' => '',
                'text' => $this->lang->translate('- select country -'));
            $countries = $dbContactAddress->country_array;
            unset($countries['-1']);
            setlocale(LC_ALL, 'de_DE');
            asort($countries, SORT_LOCALE_STRING);
            foreach ($countries as $code => $country) {
              $country_array[] = array('value' => $code, 'text' => $country);
            }
            $form_fields[$field_name] = array(
                'id' => $field_id,
                'type' => $field_name,
                'name' => $field_name,
                'value' => 'DE',
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'label' => $kitContactInterface->field_array[$field_name],
                'hint' => $this->lang->translate('hint_'.$field_name),
                'countries' => $country_array);
            break;
          case kitContactInterface::kit_contact_language:
            $lg = $kitContactInterface->getConfigurationValue(dbKITcfg::cfgContactLanguageDefault);
            $lg = strtolower(trim($lg));
            $form_fields[$field_name] = array(
                'id' => $field_id,
                'type' => $field_name,
                'name' => $field_name,
                'value' => $lg,
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'label' => $kitContactInterface->field_array[$field_name],
                'hint' => $this->lang->translate('hint_'.$field_name));
            break;
          default:
          // Datentyp nicht definiert - Fehler ausgeben
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The datatype <b>{{ type }}</b> is not supported!', array(
                'type' => $field_name))));
            return false;
        endswitch;
      }
      else {
        // ab 100 sind allgemeine Felder
        $where = array(dbKITformFields::field_id => $field_id);
        $field = array();
        if (!$dbKITformFields->sqlSelectRecord($where, $field)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
          return false;
        }
        if (count($field) < 1) {
          /*
           * Don't through an error - just continue ... @todo using a
           * logfile to document problems with the form ?
           */
          continue;
          // $this->setError (sprintf('[%s - %s] %s', __METHOD__,
          // __LINE__, sprintf ( kit_error_invalid_id, $field_id )) );
          // return false;
        }
        $field = $field[0];
        if ($field[dbKITformFields::field_name] == self::FIELD_FEEDBACK_TEXT) {
          // special: this is a feedback form!
          $is_feedback_form = true;
        }
        switch ($field[dbKITformFields::field_type]) :
          case dbKITformFields::type_checkbox:
          // CHECKBOX
            $parse = str_replace('&amp;', '&', $field[dbKITformFields::field_type_add]);
            parse_str($parse, $checkboxes);
            if (isset($_REQUEST[$field[dbKITformFields::field_name]])) {
              $checked_array = $_REQUEST[$field[dbKITformFields::field_name]];
              $checked_boxes = array();
              foreach ($checkboxes as $checkbox) {
                $checkbox['checked'] = (in_array($checkbox['value'], $checked_array)) ? 1 : 0;
                $checked_boxes[$checkbox['name']] = $checkbox;
              }
              $checkboxes = $checked_boxes;
            }
            else {
              $cbs = array();
              foreach ($checkboxes as $checkbox)
                $cbs[$checkbox['name']] = $checkbox;
              $checkboxes = $cbs;
            }
            $form_fields[$field[dbKITformFields::field_name]] = array(
                'id' => $field[dbKITformFields::field_id],
                'type' => $field[dbKITformFields::field_type],
                'name' => $field[dbKITformFields::field_name],
                'hint' => $field[dbKITformFields::field_hint],
                'label' => $field[dbKITformFields::field_title],
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'value' => $field[dbKITformFields::field_value],
                'checkbox' => $checkboxes);
            break;
          case dbKITformFields::type_delayed:
          // DELAYED transmission
            $parse = str_replace('&amp;', '&', $field[dbKITformFields::field_type_add]);
            parse_str($parse, $type_add);
            $form_fields[$field[dbKITformFields::field_name]] = array(
                'id' => $field[dbKITformFields::field_id],
                'type' => $field[dbKITformFields::field_type],
                'name' => $field[dbKITformFields::field_name],
                'hint' => $field[dbKITformFields::field_hint],
                'label' => $field[dbKITformFields::field_title],
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'value' => $field[dbKITformFields::field_value],
                'checkbox' => array(
                    'text' => (isset($type_add['text'])) ? $type_add['text'] : '',
                    'name' => $field[dbKITformFields::field_name],
                    'value' => $field[dbKITformFields::field_value],
                    'checked' => (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? 1 : 0));
            break;
          case dbKITformFields::type_hidden:
            $form_fields[$field[dbKITformFields::field_name]] = array(
                'id' => $field[dbKITformFields::field_id],
                'type' => $field[dbKITformFields::field_type],
                'name' => $field[dbKITformFields::field_name],
                'value' => $field[dbKITformFields::field_value]);
            break;
          case dbKITformFields::type_file:
            $parse = str_replace('&amp;', '&', $field[dbKITformFields::field_type_add]);
            parse_str($parse, $settings);
            $ext_array = explode(',', $settings['file_types']['value']);
            $file_ext = '';
            $file_desc = '';
            foreach ($ext_array as $ext) {
              if (empty($ext))
                continue;
              if (!empty($file_ext)) {
                $file_ext .= ';';
                $file_desc .= ', ';
              }
              $file_ext .= sprintf('*.%s', $ext);
              $file_desc .= sprintf('.%s', $ext);
            }
            $form_fields[$field[dbKITformFields::field_name]] = array(
                'id' => $field[dbKITformFields::field_id],
                'type' => $field[dbKITformFields::field_type],
                'name' => $field[dbKITformFields::field_name],
                'hint' => $field[dbKITformFields::field_hint],
                'label' => $field[dbKITformFields::field_title],
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'settings' => $settings,
                'upload_id' => $upload_id,
                'file_desc' => $file_desc,
                'file_ext' => $file_ext,
                'file_size' => $settings['max_file_size']['value'] * 1024 * 1024,
                'select_file' => $this->lang->translate('Select File'));
            $is_file_upload = true;
            break;
          case dbKITformFields::type_html:
            $form_fields[$field[dbKITformFields::field_name]] = array(
                'id' => $field[dbKITformFields::field_id],
                'type' => $field[dbKITformFields::field_type],
                'value' => $field[dbKITformFields::field_value]);
            break;
          case dbKITformFields::type_radio:
            $parse = str_replace('&amp;', '&', $field[dbKITformFields::field_type_add]);
            parse_str($parse, $radios);
            if (isset($_REQUEST[$field[dbKITformFields::field_name]])) {
              $checked = $_REQUEST[$field[dbKITformFields::field_name]];
              $checked_radios = array();
              foreach ($radios as $radio) {
                $radio['checked'] = ($radio['value'] == $checked) ? 1 : 0;
                $checked_radios[] = $radio;
              }
              $radios = $checked_radios;
            }
            else {
              $rbs = array();
              foreach ($radios as $radio)
                $rbs[$radio['name']] = $radio;
              $radios = $rbs;
            }
            $form_fields[$field[dbKITformFields::field_name]] = array(
                'id' => $field[dbKITformFields::field_id],
                'type' => $field[dbKITformFields::field_type],
                'name' => $field[dbKITformFields::field_name],
                'hint' => $field[dbKITformFields::field_hint],
                'label' => $field[dbKITformFields::field_title],
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'value' => $field[dbKITformFields::field_value],
                'radio' => $radios);
            break;
          case dbKITformFields::type_select:
            $parse = str_replace('&amp;', '&', $field[dbKITformFields::field_type_add]);
            parse_str($parse, $options);
            if (isset($_REQUEST[$field[dbKITformFields::field_name]])) {
              $checked = $_REQUEST[$field[dbKITformFields::field_name]];
              $checked_options = array();
              foreach ($options as $option) {
                $option['checked'] = ($option['value'] == $checked) ? 1 : 0;
                $checked_options[] = $option;
              }
              $options = $checked_options;
            }
            $form_fields[$field[dbKITformFields::field_name]] = array(
                'id' => $field[dbKITformFields::field_id],
                'type' => $field[dbKITformFields::field_type],
                'name' => $field[dbKITformFields::field_name],
                'hint' => $field[dbKITformFields::field_hint],
                'label' => $field[dbKITformFields::field_title],
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'value' => $field[dbKITformFields::field_value],
                'option' => $options);
            break;
          case dbKITformFields::type_text_area:
            $parse = str_replace('&amp;', '&', $field[dbKITformFields::field_type_add]);
            parse_str($parse, $additional);
            $form_fields[$field[dbKITformFields::field_name]] = array(
                'id' => $field[dbKITformFields::field_id],
                'type' => $field[dbKITformFields::field_type],
                'name' => $field[dbKITformFields::field_name],
                'hint' => $field[dbKITformFields::field_hint],
                'label' => $field[dbKITformFields::field_title],
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'value' => isset($_REQUEST[$field[dbKITformFields::field_name]]) ? $_REQUEST[$field[dbKITformFields::field_name]] : $field[dbKITformFields::field_value],
                'count_chars' => isset($additional['count_chars']) ? $additional['count_chars'] : 0,
                'limit_chars' => isset($additional['limit_chars']) ? $additional['limit_chars'] : -1
                );
            break;
          case dbKITformFields::type_text:
            $form_fields[$field[dbKITformFields::field_name]] = array(
                'id' => $field[dbKITformFields::field_id],
                'type' => $field[dbKITformFields::field_type],
                'name' => $field[dbKITformFields::field_name],
                'hint' => $field[dbKITformFields::field_hint],
                'label' => $field[dbKITformFields::field_title],
                'must' => (in_array($field_id, $must_array)) ? 1 : 0,
                'value' => isset($_REQUEST[$field[dbKITformFields::field_name]]) ? $_REQUEST[$field[dbKITformFields::field_name]] : $field[dbKITformFields::field_value]);
            break;
          default:
          // continue;
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The datatype <b>{{ type }}</b> is not supported!', array(
                'type' => $field[dbKITformFields::field_type]))));
            return false;
        endswitch;
      }
    }
    if ($is_feedback_form) {
      return $this->showFeedbackForm($form_id, $form_data, $form_fields, $clear_fields);
    }
    else {
      $data = array(
          'WB_URL' => WB_URL,
          'form' => $form_data,
          'fields' => $form_fields);
      return $this->getTemplate('form.htt', $data);
    }
  } // showForm()

  /**
   * Ueberprueft das Formular, zeigt das Formular bei Fehlern erneut an.
   * Wenn alles in Ordnung ist, werden die Daten gesichert und
   * Benachrichtigungs E-Mails versendet.
   *
   * @return string FORMULAR oder ERFOLGSMELDUNG
   */
  protected function checkForm() {
    global $dbKITform;
    global $dbKITformFields;
    global $kitContactInterface;
    global $kitLibrary;
    global $dbKITformData;
    global $dbContact;
    global $dbKITdirList;
    global $dbCfg;
    global $dbMemos;

    if (!isset($_REQUEST[dbKITform::field_id])) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Missing the form ID!')));
      return false;
    }
    $form_id = $_REQUEST[dbKITform::field_id];
    $where = array(dbKITform::field_id => $form_id);
    $form = array();
    if (!$dbKITform->sqlSelectRecord($where, $form)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
      return false;
    }
    if (count($form) < 1) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The ID {{ id }} is invalid!', array(
          'id' => $form_id))));
      return false;
    }
    $form = $form[0];
    // pruefen, ob eine Aktion ausgefuehrt werden soll
    switch ($form[dbKITform::field_action]) :
      case dbKITform::action_login:
        return $this->checkLogin($form);
      case dbKITform::action_logout:
        return $this->Logout($form);
      case dbKITform::action_send_password:
        return $this->sendNewPassword($form);
      case dbKITform::action_change_password:
        return $this->changePassword($form);
      case dbKITform::action_newsletter:
        // return $this->subscribeNewsletter ( $form );
      case dbKITform::action_register:
      case dbKITform::action_account: /*
                                       * Diese speziellen Aktionen werden erst durchgefuehrt, wenn die
                                       * allgemeinen Daten bereits geprueft sind
                                       */
      default:
        // nothing to do - go ahead...
    endswitch;

    $message = '';
    $checked = true;
    // CAPTCHA pruefen?
    if ($form[dbKITform::field_captcha] == dbKITform::captcha_on) {
      unset($_SESSION['kf_captcha']);
      if (!isset($_REQUEST['captcha']) || ($_REQUEST['captcha'] != $_SESSION['captcha'])) {
        $message .= $this->lang->translate('<p>The CAPTCHA code is not correct, please try again!</p>');
        $checked = false;
      }
    }

    // check wait for seconds
    if (isset($_REQUEST['wait_seconds']) && ((int) $_REQUEST['wait_seconds'] > 0) && isset($_REQUEST['wait_start'])) {
      $start = (int) $_REQUEST['wait_start'];
      $seconds = (int) ($_REQUEST['wait_seconds']/100);
      $stop = mktime(date('H', $start), date('i', $start), date('s', $start)+$seconds, date('m', $start), date('d', $start), date('Y', $start));
      if ($stop > time()) {
        $message .= $this->lang->translate('<p>You have submitted the form to early, please wait for the specified seconds (SPAM protection).</p>');
        $checked = false;
      }
    }

    // zuerst die Pflichtfelder pruefen
    $must_array = explode(',', $form[dbKITform::field_must_fields]);
    if (in_array('20', $must_array)) {
      // special: key must transformed!
      $key = array_search('20', $must_array);
      unset($must_array[$key]);
      $must_array[] = 18;
      $must_array[] = 19;
    }
    foreach ($must_array as $must_id) {
      if ($must_id < 100) {
        // IDs 1-99 sind fuer KIT reserviert
        if (false === ($field_name = array_search($must_id, $kitContactInterface->index_array))) {
          // $field_id nicht gefunden
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The field with the <b>ID {{ id }}</b> is no KIT datafield!', array(
              'id' => $must_id))));
          return false;
        }
        if (!isset($_REQUEST[$field_name]) || empty($_REQUEST[$field_name])) {
          // Feld muss gesetzt sein
          $message .= $this->lang->translate('<p>The field <b>{{ field }}</b> must be filled out.</p>', array(
              'field' => $kitContactInterface->field_array[$field_name]));
          $checked = false;
        }
        elseif ($field_name == kitContactInterface::kit_email) {
          // check email address
          if (isset($_REQUEST[kitContactInterface::kit_email_retype]) && ($_REQUEST[kitContactInterface::kit_email] != $_REQUEST[kitContactInterface::kit_email_retype])) {
            // comparing email and retyped email address failed ...
            unset($_REQUEST[kitContactInterface::kit_email_retype]);
            $message .= $this->lang->translate('<p>The email address and the retyped email address does not match!</p>');
            $checked = false;
          }
          elseif (!$kitLibrary->validateEMail($_REQUEST[kitContactInterface::kit_email])) {
            // checking email address failed ...
            unset($_REQUEST[kitContactInterface::kit_email_retype]);
            $message .= $this->lang->translate('<p>The email address <b>{{ email }}</b> is not valid, please check your input.</p>', array(
                'email' => $_REQUEST[kitContactInterface::kit_email]));
            $checked = false;
          }
        }
      }
      else {
        // freie Datenfelder
        $where = array(dbKITformFields::field_id => $must_id);
        $field = array();
        if (!$dbKITformFields->sqlSelectRecord($where, $field)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
          return false;
        }
        if (count($field) < 1) {
          continue;
          /**
           *
           * @todo only a workaround ...
           */
          // $this->setError (sprintf('[%s - %s] %s', __METHOD__,
          // __LINE__, sprintf ( kit_error_invalid_id, $must_id ) ));
          // return false;
        }
        $field = $field[0];
        $field_name = $field[dbKITformFields::field_name];
        if ($field[dbKITformFields::field_type] == dbKITformFields::type_file) {
          // file upload?
          continue;
        }
        elseif (!isset($_REQUEST[$field_name]) || empty($_REQUEST[$field_name])) {
          // Feld muss gesetzt sein
          $message .= $this->lang->translate('<p>The field <b>{{ field }}</b> must be filled out.</p>', array(
              'field' => $field[dbKITformFields::field_title]));
          $checked = false;
        }
        else {
          // erweiterte Pruefung
          switch ($field[dbKITformFields::field_data_type]) :
            case dbKITformFields::data_type_date:
              if (false === ($timestamp = strtotime($_REQUEST[$field_name]))) {
                $message .= $this->lang->translate('<p><b>{{ value }}</b> is not a valid date, please check your input!</p>', array(
                    'value' => $_REQUEST[$field_name]));
                $checked = false;
              }
              break;
            default:
              // alle anderen Datentypen ohne Pruefung...
          endswitch;
        }
      }
    } // foreach

    // special: check if kit_birthday is valid
    $kit_birthday = '';
    if (isset($_REQUEST[kitContactInterface::kit_birthday])) {
      if ((strpos($_REQUEST[kitContactInterface::kit_birthday], cfg_date_separator) == false) && (in_array($kitContactInterface->index_array[kitContactInterface::kit_birthday], $must_array))) {
        $message .= $this->lang->translate('<p>Please type in the birthday like <b>{{ date_str }}</b>.</p>', array(
            'date_str' => cfg_date_str));
        $checked = false;
      }
      elseif (!empty($_REQUEST[kitContactInterface::kit_birthday])) {
        $barray = explode(cfg_date_separator, $_REQUEST[kitContactInterface::kit_birthday]);
        $df = explode(cfg_date_separator, cfg_date_str);
        if (count($barray) == 3) {
          $da = array();
          for ($i = 0; $i < 3; $i++)
            $da[$df[$i]] = $barray[$i];
          if ($da['Y'] < 100) $da['Y'] = 1900 + $da['Y'];
          if (($da['Y'] < 1900) || ($da['Y'] > date('Y')) || ($da['m'] < 1) || ($da['m'] > 12) || ($da['d'] < 1) || ($da['d'] > 31)) {
            $checked = false;
            $message .= $this->lang->translate('<p>The date <b>{{ date }}</b> is invalid!</p>', array(
                'date' => $_REQUEST[kitContactInterface::kit_birthday]));
          }
          if ($checked && (false !== ($date = mktime(0, 0, 0, $da['m'], $da['d'], $da['Y'])))) {
            $kit_birthday = $date;
          }
          elseif ($checked) {
            $checked = false;
            $message .= $this->lang->translate('<p>The date <b>{{ date }}</b> is invalid!</p>', array(
                'date' => $_REQUEST[kitContactInterface::kit_birthday]));
          }
        }
        else {
          // date is invalid
          $message .= $this->lang->translate('<p>Please type in the birthday like <b>{{ date_str }}<b>.</p>', array(
              'date_str' => cfg_date_str));
          $checked = false;
        }
      }
    }

    // file upload?
    $uploaded_files = array();
    $uploaded_files['count'] = 0;
    $file_array = array();

    if ($checked) {
      $where = array(
          dbKITformFields::field_form_id => $form_id,
          dbKITformFields::field_type => dbKITformFields::type_file);
      if (!$dbKITformFields->sqlSelectRecord($where, $file_array)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
        return false;
      }

      if (count($file_array) > 0) {
        // check the protected upload directory
        $upload_path = WB_PATH.MEDIA_DIRECTORY.DIRECTORY_SEPARATOR.self::PROTECTION_FOLDER;
        if (!file_exists($upload_path)) {
          if (!mkdir($upload_path, 0755, true)) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Error creating the directory <b>{{ directory }}</b>.', array(
                'directory' => $upload_path))));
            return false;
          }
        }
        // check if .htaccess and .htpasswd exists...
        if (!file_exists($upload_path.DIRECTORY_SEPARATOR.'.htaccess') || !file_exists($upload_path.DIRECTORY_SEPARATOR.'.htpasswd')) {
          if (!$this->createProtection())
            return false;
        }

        // get the email address
        $email = $_REQUEST[kitContactInterface::kit_email];
        // the user directory for this upload - the directory will be
        // created when moving the uploaded file!
        $upload_path .= DIRECTORY_SEPARATOR.self::CONTACTS_FOLDER.DIRECTORY_SEPARATOR.$email[0].DIRECTORY_SEPARATOR.$email.DIRECTORY_SEPARATOR.self::USER_FOLDER.DIRECTORY_SEPARATOR.date('ymd-His').DIRECTORY_SEPARATOR;
      }

      foreach ($file_array as $file) {
        $parse = str_replace('&amp;', '&', $file[dbKITformFields::field_type_add]);
        parse_str($parse, $settings);
        $method = $settings['upload_method']['value'];
        if ($method == 'standard') {
          // method: standard - check the file uploads
          if (!isset($_FILES[$file[dbKITformFields::field_name]]) && (in_array($file[dbKITformFields::field_id], $must_array))) {
            // file upload is a MUST field
            $message .= $this->lang->translate('<p>The field <b>{{ field }}</b> must be filled out.</p>', array(
                'field' => $kitContactInterface->field_array[$field_name]));
            $checked = false;
            // go ahead...
            continue;
          }
          if (isset($_FILES[$file[dbKITformFields::field_name]]) && (is_uploaded_file($_FILES[$file[dbKITformFields::field_name]]['tmp_name']))) {
            // file was uploaded with the standard method
            if ($_FILES[$file[dbKITformFields::field_name]]['error'] == UPLOAD_ERR_OK) {
              // upload without error
              if ($checked) {
                $fext = explode('.', $_FILES[$file[dbKITformFields::field_name]]['name']);
                // file extension
                $ext = strtolower(end($fext));
                if (in_array($ext, $this->general_excluded_extensions)) {
                  // disallowed file or filetype - delete
                  // uploaded file
                  $message .= $this->lang->translate('<p>The file {{ file }} is member of a blacklist or use a disallowed file extension.</p>', array(
                      'file' => basename($_FILES[$file[dbKITformFields::field_name]]['name'])));
                  $checked = false;
                }
                // get the settings for this file
                $parse = str_replace('&amp;', '&', $file[dbKITformFields::field_type_add]);
                parse_str($parse, $settings);
                if (!empty($settings['file_types'])) {
                  $ext_array = explode(',', $settings['file_types']['value']);
                  if (!in_array($ext, $ext_array)) {
                    $message .= $this->lang->translate('<p>Please upload only files with the extension <b>{{ extensions }}</b>, the file {{ file }} is refused.</p>', array(
                        'extensions' => implode(', ', $ext_array),
                        'file' => basename($_FILES[$file[dbKITformFields::field_name]]['name'])));
                    $checked = false;
                  }
                }
                if ($_FILES[$file[dbKITformFields::field_name]]['size'] > ($settings['max_file_size']['value'] * 1024 * 1024)) {
                  $message .= $this->lang->translate('<p>The file size exceeds the limit of {{ size }} MB.</p>', array(
                      'size' => $settings['max_file_size']['value']));
                  $checked = false;
                }
              }
              if (!$checked) {
                // not checked - delete the file and continue
                @unlink($_FILES[$file[dbKITformFields::field_name]]['tmp_name']);
                continue;
              }
              // now create the directory
              if (!file_exists($upload_path)) {
                if (!mkdir($upload_path, 0755, true)) {
                  $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Error creating the directory <b>{{ directory }}</b>.', array(
                      'directory' => $upload_path))));
                  return false;
                }
              }
              $mf = media_filename($_FILES[$file[dbKITformFields::field_name]]['name']);
              if (!move_uploaded_file($_FILES[$file[dbKITformFields::field_name]]['tmp_name'], $upload_path.$mf)) {
                $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Error moving the file <b>{{ file }}</b> to the target directory!', array(
                    'file' => $_FILES[$file[dbKITformFields::field_name]]['name']))));
                return false;
              }
              // create dbKITdirList entry
              $data = array(
                  dbKITdirList::field_count => 0,
                  dbKITdirList::field_date => date('Y-m-d H:i:s'),
                  dbKITdirList::field_file => $mf,
                  dbKITdirList::field_path => $upload_path.$mf,
                  dbKITdirList::field_user => $email);
              $file_id = -1;
              if (!$dbKITdirList->sqlInsertRecord($data, $file_id)) {
                $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITdirList->getError()));
                return false;
              }
              // add $file_id to the uploaded files ...
              $uploaded_files['count'] += 1;
              $uploaded_files['items'][$file_id] = array(
                  'id' => $file_id,
                  'name' => $mf,
                  'name_origin' => $_FILES[$file[dbKITformFields::field_name]]['name'],
                  'size' => $_FILES[$file[dbKITformFields::field_name]]['size'],
                  'path' => substr($upload_path.$mf, strlen(WB_PATH)),
                  'download' => WB_URL.'/modules/kit/kdl.php?id='.$file_id);
              $contact_id = -1;
              if (!$kitContactInterface->isEMailRegistered($email, $contact_id)) {
                if ($kitContactInterface->isError()) {
                  $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
                  return false;
                }
                // contact does not exists
                $data = array(kitContactInterface::kit_email => $email);
                if (!$kitContactInterface->addContact($data, $contact_id)) {
                  $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
                  return false;
                }
              }
              /*
               * see setting notice for uploads below! no longer needed!
              // add notice to KIT
              $kitContactInterface->addNotice($contact_id, $this->lang->translate('[kitForm] File <a href="{{ link }}">{{ file }}</a> uploaded.', array(
                  'link' => WB_URL.'/modules/kit/kdl.php?id='.$file_id,
                  'file' => $mf)));
              */
            }
            else {
              // handling upload errors
              switch ($_FILES[$file[dbKITformFields::field_name]]['error']) :
                case UPLOAD_ERR_INI_SIZE:
                  $message .= $this->lang->translate('<p>The file size exceeds the php.ini directive "upload_max_size" <b>{{ size }}</b>.</p>', array(
                      'size' => ini_get('upload_max_filesize')));
                  $checked = false;
                  break;
                case UPLOAD_ERR_PARTIAL:
                  $message .= $this->lang->translate('<p>The file <b>{{ file }}</b> was uploaded partial.</p>', array(
                      'file' => $_FILES[dbKITformFields::field_name]['name']));
                  $checked = false;
                  break;
                default:
                  $message .= $this->lang->translate('<p>Unspecified error, no description available.</p>');
                  $checked = false;
              endswitch;
              // delete temporary file
              @unlink($_FILES[$file[dbKITformFields::field_name]]['tmp_name']);
            }
          }
        } // method: standard
        else {
          // method: uploadify
          if (isset($_REQUEST['upload_delete']) && !empty($_REQUEST['upload_delete']) && isset($_REQUEST['upload_id'])) {
            // if 'upload_delete' isset and not empty the file was
            // uploaded and then deleted
            $where = array(
                dbKITdirList::field_reference => $_REQUEST['upload_id'],
                dbKITdirList::field_file_origin => $_REQUEST['upload_delete']);
            if (!$dbKITdirList->sqlDeleteRecord($where)) {
              $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITdirList->getError()));
              return false;
            }
            $message .= $this->lang->translate('<p>The file <b>{{ file }}</b> was deleted.<p>', array(
                'file' => $_REQUEST['upload_delete']));
          }
          if (isset($_REQUEST['upload_id'])) {
            $where = array(
                dbKITdirList::field_reference => $_REQUEST['upload_id']);
            $uploads = array();
            if (!$dbKITdirList->sqlSelectRecord($where, $uploads)) {
              $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITdirList->getError()));
              return false;
            }
            if ((count($uploads) < 1) && (in_array($file[dbKITformFields::field_id], $must_array))) {
              // file upload is a MUST field
              $message .= $this->lang->translate('<p>The field <b>{{ field }}</b> must be filled out.</p>', array(
                  'field' => $kitContactInterface->field_array[$field_name]));
              $checked = false;
              // go ahead...
              continue;
            }
            foreach ($uploads as $upload) {
              // now create the directory
              if (!file_exists($upload_path)) {
                if (!mkdir($upload_path, 0755, true)) {
                  $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Error creating the directory <b>{{ directory }}</b>.', array(
                      'directory' => $upload_path))));
                  return false;
                }
              }
              // move the uploads from temporary directory to the
              // target directory
              if (!rename($upload[dbKITdirList::field_path], $upload_path.$upload[dbKITdirList::field_file])) {
                $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Error moving file <b>{{ file_origin }}</b> to <b>{{ file_target }}</b>.', array(
                    'file_origin' => $upload[dbKITdirList::field_path],
                    'file_target' => $upload_path.$upload[dbKITdirList::field_file]))));
                return false;
              }
              // delete the temporary directory if empty
              if ($this->isDirectoryEmpty($upload_path)) {
                @unlink($upload_path);
              }
              $data = array(
                  dbKITdirList::field_path => $upload_path.$upload[dbKITdirList::field_file],
                  dbKITdirList::field_user => $email);
              $where = array(
                  dbKITdirList::field_id => $upload[dbKITdirList::field_id]);
              if (!$dbKITdirList->sqlUpdateRecord($data, $where)) {
                $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITdirList->getError()));
                return false;
              }
              // add $file_id to the uploaded files ...
              $uploaded_files['count'] += 1;
              $uploaded_files['items'][$upload[dbKITdirList::field_id]] = array(
                  'id' => $upload[dbKITdirList::field_id],
                  'name' => $upload[dbKITdirList::field_file],
                  'name_origin' => $upload[dbKITdirList::field_file_origin],
                  'size' => filesize($upload_path.$upload[dbKITdirList::field_file]),
                  'path' => substr($upload_path.$upload[dbKITdirList::field_file], strlen(WB_PATH)),
                  'download' => WB_URL.'/modules/kit/kdl.php?id='.$upload[dbKITdirList::field_id]);
              $contact_id = -1;
              if (!$kitContactInterface->isEMailRegistered($email, $contact_id)) {
                if ($kitContactInterface->isError()) {
                  $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
                  return false;
                }
                // contact does not exists
                $data = array(kitContactInterface::kit_email => $email);
                if (!$kitContactInterface->addContact($data, $contact_id)) {
                  $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
                  return false;
                }
              }
            }
          }
        }
      } // foreach
    } // if checked

    if ($checked) {
      // Daten sind ok und koennen uebernommen werden

      // Sonderfall: Newsletter Dialog
      if ($form[dbKITform::field_action] == dbKITform::action_newsletter)
        return $this->subscribeNewsletter($form);

      $password_changed = false;
      $password = '';
      $contact_array = array();
      $field_array = $kitContactInterface->field_array;
      // Feld fuer internen Verteiler hinzufuegen
      $field_array[kitContactInterface::kit_intern] = '';
      foreach ($field_array as $key => $value) {
        switch ($key) :
          case kitContactInterface::kit_free_field_1:
          case kitContactInterface::kit_free_field_2:
          case kitContactInterface::kit_free_field_3:
          case kitContactInterface::kit_free_field_4:
          case kitContactInterface::kit_free_field_5:
          case kitContactInterface::kit_free_note_1:
          case kitContactInterface::kit_free_note_2:
          case kitContactInterface::kit_zip_city:
          // nothing to do...
            break;
          case kitContactInterface::kit_newsletter:
            if (isset($_REQUEST[$key])) {
              if (is_array($_REQUEST[$key])) {
                $contact_array[$key] = implode(',', $_REQUEST[$key]);
              }
              else {
                $contact_array[$key] = $_REQUEST[$key];
              }
            }
            break;
          case kitContactInterface::kit_password:
          // kit_password wird ignoriert
            break;
          case kitContactInterface::kit_password_retype:
            if ((isset($_REQUEST[$key]) && !empty($_REQUEST[$key])) && (isset($_REQUEST[kitContactInterface::kit_password]) && !empty($_REQUEST[kitContactInterface::kit_password]))) {
              // nur pruefen, wenn beide Passwortfelder gesetzt sind
              if (!$kitContactInterface->changePassword($_SESSION[kitContactInterface::session_kit_register_id], $_SESSION[kitContactInterface::session_kit_contact_id], $_REQUEST[kitContactInterface::kit_password], $_REQUEST[kitContactInterface::kit_password_retype])) {
                // Fehler beim Aendern des Passwortes
                unset($_REQUEST[kitContactInterface::kit_password]);
                unset($_REQUEST[kitContactInterface::kit_password_retype]);
                if ($kitContactInterface->isError()) {
                  $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
                  return false;
                }
                $message .= $kitContactInterface->getMessage();
                break;
              }
              else {
                // Passwort wurde geaendert
                $password_changed = true;
                $password = $_REQUEST[kitContactInterface::kit_password];
                unset($_REQUEST[kitContactInterface::kit_password]);
                unset($_REQUEST[kitContactInterface::kit_password_retype]);
                $message .= $kitContactInterface->getMessage();
                break;
              }
            }
            break;
          case kitContactInterface::kit_birthday:
          // check birthday
            if (isset($_REQUEST[kitContactInterface::kit_birthday]) && !empty($kit_birthday)) {
              $contact_array[$key] = date('Y-m-d H:i:s', $kit_birthday);
            }
            break;
          case kitContactInterface::kit_contact_language:
          // check contact language
            if (isset($_REQUEST[kitContactInterface::kit_contact_language]) && ($_REQUEST[kitContactInterface::kit_contact_language] !== strtolower(LANGUAGE))) {
              $contact_array[$key] = strtolower(LANGUAGE);
            }
          default:
            if (isset($_REQUEST[$key]))
              $contact_array[$key] = $_REQUEST[$key];
            break;
        endswitch;
      }

      if ($form[dbKITform::field_action] == dbKITform::action_register) {
        // es handelt sich um einen Registrierdialog, die weitere Bearbeitung an $this->registerAccount() uebergeben
        return $this->registerAccount($form, $contact_array);
      }
      elseif ($form[dbKITform::field_action] == dbKITform::action_account) {
        // Es wird das Benutzerkonto bearbeitet
        if (!$kitContactInterface->updateContact($_SESSION[kitContactInterface::session_kit_contact_id], $contact_array)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
          return false;
        }
        if ($password_changed) {
          // Passwort wurde geaendert, E-Mail Bestaetigung versenden
          $form['subject'] = $form[dbKITform::field_title];
          $data = array(
              'contact' => $contact_array,
              'password' => $password,
              'form' => $form);
          $provider_data = array();
          if (!$kitContactInterface->getServiceProviderByID($form[dbKITform::field_provider_id], $provider_data)) {
            if ($kitContactInterface->isError()) {
              $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
            }
            else {
              $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage()));
            }
            return false;
          }
          $provider_email = $provider_data['email'];
          $provider_name = $provider_data['name'];

          $client_mail = $this->getTemplate('mail.client.password.htt', $data);
          if ($form[dbKITform::field_email_html] == dbKITform::html_off)
            $client_mail = strip_tags($client_mail);
          $client_subject = strip_tags($this->getTemplate('mail.client.subject.htt', $data));

          $mail = new kitMail($form[dbKITform::field_provider_id]);
          if (!$mail->mail($client_subject, $client_mail, $provider_email, $provider_name, array(
              $contact_array[kitContactInterface::kit_email] => $contact_array[kitContactInterface::kit_email]), false)) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
                'email' => $contact_array[kitContactInterface::kit_email]))));
            return false;
          }

        }
        // Mitteilung, dass das Benutzerkonto aktualisiert wurde
        if (empty($message))
          $message = $this->lang->translate('<p>The user account was updated.</p>');
        $this->setMessage($message);
        return $this->showForm();
      }
      $contact_id = -1;
      $status = '';
      if ($kitContactInterface->isEMailRegistered($_REQUEST[kitContactInterface::kit_email], $contact_id, $status)) {
        // E-Mail Adresse existiert bereits, Datensatz ggf.
        // aktualisieren
        if (!$kitContactInterface->updateContact($contact_id, $contact_array)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
          return false;
        }
      }
      elseif ($kitContactInterface->isError()) {
        // Fehler bei der Datenbankabfrage
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
        return false;
      }
      else {
        // E-Mail Adresse ist noch nicht registriert
        if (!$kitContactInterface->addContact($contact_array, $contact_id)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
          return false;
        }
      }

      // special: check additional fields and notes
      $check_array = array(
          kitContactInterface::kit_free_field_1 => dbKITcontact::field_free_1,
          kitContactInterface::kit_free_field_2 => dbKITcontact::field_free_2,
          kitContactInterface::kit_free_field_3 => dbKITcontact::field_free_3,
          kitContactInterface::kit_free_field_4 => dbKITcontact::field_free_4,
          kitContactInterface::kit_free_field_5 => dbKITcontact::field_free_5,
          kitContactInterface::kit_free_note_1 => dbKITcontact::field_free_note_1,
          kitContactInterface::kit_free_note_2 => dbKITcontact::field_free_note_2
          );

      foreach ($check_array as $check_field => $replace_field) {
        if (isset($_REQUEST[$check_field])) {
          // get the old user defined fields for compare
          $SQL = sprintf("SELECT %s,%s,%s,%s,%s,%s,%s FROM %s WHERE %s='%s'", dbKITcontact::field_free_1, dbKITcontact::field_free_2, dbKITcontact::field_free_3, dbKITcontact::field_free_4, dbKITcontact::field_free_5, dbKITcontact::field_free_note_1, dbKITcontact::field_free_note_2, $dbContact->getTableName(), dbKITcontact::field_id, $contact_id);
          $field_array = array();
          if (!$dbContact->sqlExec($SQL, $field_array)) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbContact->getError()));
            return false;
          }
          $field_array = $field_array[0];

          $fid = $field_array[$kitContactInterface->field_assign[$check_field]];
          if (($fid < 1) && (!empty($_REQUEST[$check_field]))) {
            // field is not empty and does not exists in in dbKITmemo
            $data = array(
                dbKITmemos::field_contact_id => $contact_id,
                dbKITmemos::field_memo => trim($_REQUEST[$check_field]),
                dbKITmemos::field_status => dbKITmemos::status_active,
                dbKITmemos::field_update_by => 'SYSTEM',
                dbKITmemos::field_update_when => date('Y-m-d H:i:s'));
            $mid = -1;
            if (!$dbMemos->sqlInsertRecord($data, $mid)) {
              $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbMemos->getError()));
              return false;
            }
            // update contact record!
            $where = array(dbKITcontact::field_id => $contact_id);
            $data = array($replace_field => $mid);
            if (!$dbContact->sqlUpdateRecord($data, $where)) {
              $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbContact->getError()));
              return false;
            }
          }
          elseif ($fid > 0) {
            // field already exists in dbKITmemo - get the data field
            $where = array(dbKITmemos::field_id => $fid);
            $memo = array();
            if (!$dbMemos->sqlSelectRecord($where, $memo)) {
              $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbMemos->getError()));
              return false;
            }
            $memo = $memo[0];
            if (trim($_REQUEST[$check_field] != $memo[dbKITmemos::field_memo])) {
              // entries differ - update record
              $data = array(
                  dbKITmemos::field_memo => trim($_REQUEST[$check_field]),
                  dbKITmemos::field_status => dbKITmemos::status_active,
                  dbKITmemos::field_update_by => 'SYSTEM',
                  dbKITmemos::field_update_when => date('Y-m-d H:i:s'));
              if (!$dbMemos->sqlUpdateRecord($data, $where)) {
                $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbMemos->getError()));
                return false;
              }
            }
          }

        }
      }

      // Kontakt Datensatz ist erstellt oder aktualisiert, allgemeine
      // Daten uebernehmen und E-Mails versenden
      $fields = array();
      $values = array();
      $fields_array = explode(',', $form[dbKITform::field_fields]);
      foreach ($fields_array as $fid) {
        if ($fid > 99)
          $fields[] = $fid;
      }

      // DELAYED TRANSMISSION ?
      $delayed_transmission = (isset($_REQUEST[dbKITformFields::kit_delayed_transmission])) ? true : false;

      foreach ($fields as $fid) {
        $where = array(dbKITformFields::field_id => $fid);
        $field = array();
        if (!$dbKITformFields->sqlSelectRecord($where, $field)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
          return false;
        }
        // if no valid record still continue to the next
        if (count($field) < 1) continue;
        $field = $field[0];
        switch ($field[dbKITformFields::field_data_type]) :
          case dbKITformFields::data_type_date:
            $values[$fid] = (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? date('Y-m-d H:i:s', strtotime($_REQUEST[$field[dbKITformFields::field_name]])) : '0000-00-00 00:00:00';
            break;
          case dbKITformFields::data_type_float:
            $values[$fid] = (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? $kitLibrary->str2float($_REQUEST[$field[dbKITformFields::field_name]], cfg_thousand_separator, cfg_decimal_separator) : 0;
            break;
          case dbKITformFields::data_type_integer:
            $values[$fid] = (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? $kitLibrary->str2int($_REQUEST[$field[dbKITformFields::field_name]], cfg_thousand_separator, cfg_decimal_separator) : 0;
            break;
          default:
            $values[$fid] = (isset($_REQUEST[$field[dbKITformFields::field_name]])) ? $_REQUEST[$field[dbKITformFields::field_name]] : '';
            break;
        endswitch;
      } // foreach

      $form_data = array(
          dbKITformData::field_form_id => $form_id,
          dbKITformData::field_kit_id => $contact_id,
          dbKITformData::field_date => date('Y-m-d H:i:s'),
          dbKITformData::field_fields => implode(',', $fields),
          dbKITformData::field_values => http_build_query($values),
          dbKITformData::field_status => $delayed_transmission ? dbKITformData::status_delayed : dbKITformData::status_active);
      $data_id = -1;
      if (!$dbKITformData->sqlInsertRecord($form_data, $data_id)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
        return false;
      }

      /*
       * check for special actions by field names, i.e. Feedback Form...
       */
      $is_feedback_form = false;
      $SQL = sprintf("SELECT %s FROM %s WHERE %s='%s' AND %s='%s'", dbKITformFields::field_id, $dbKITformFields->getTableName(), dbKITformFields::field_form_id, $form_id, dbKITformFields::field_name, self::FIELD_FEEDBACK_TEXT);
      $result = array();
      if (!$dbKITformFields->sqlExec($SQL, $result)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
        return false;
      }
      if (count($result) == 1) {
        // exec special action: Feedback for the Website
        $is_feedback_form = true;
      }
      // end: special actions

      /**
       * Leave here at delayed transmission
       */
      if ($delayed_transmission)
        return $this->delayedTransmission($data_id, $contact_id);

      // ok - Daten sind gesichert, vorab LOG schreiben
      if ($is_feedback_form) {
        $protocol = $this->lang->translate('[kitForm] The contact has <a href="{{ url }}">submitted a feedback</a>', array(
            'url' => sprintf('%s&%s', ADMIN_URL.'/admintools/tool.php?tool=kit_form', http_build_query(array(
                formBackend::request_action => formBackend::action_protocol_id,
                formBackend::request_protocol_id => $data_id)))));
      }
      else {
        $protocol = $this->lang->translate('[kitForm] The contact has <a href="{{ url }}">submitted a form</a>.', array(
            'url' => sprintf('%s&%s', ADMIN_URL.'/admintools/tool.php?tool=kit_form', http_build_query(array(
                formBackend::request_action => formBackend::action_protocol_id,
                formBackend::request_protocol_id => $data_id)))));
      }
      $dbContact->addSystemNotice($contact_id, $protocol);

      if (isset($uploaded_files['items'])) {
        foreach ($uploaded_files['items'] as $file) {
          // add a system notice for each file
          $kitContactInterface->addNotice($contact_id, $this->lang->translate('[kitForm] File <a href="{{ link }}">{{ file }}</a> uploaded.', array(
              'link' => WB_URL.'/modules/kit/kdl.php?id='.$file['id'],
              'file' => $file['name'])));
          // check for special links
          if (isset($_SESSION[self::SESSION_SPECIAL_LINK]))
            $this->processSpecialLink($message);
        }
      }

      $contact = array();
      if (!$kitContactInterface->getContact($contact_id, $contact)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
        return false;
      }
      if ($this->params[self::PARAM_RETURN] == true) {
        // direkt zum aufrufenden Programm zurueckkehren
        $result = array('contact' => $contact, 'result' => true);
        return $result;
      }
      // Feedback Form? Leave here...
      if ($is_feedback_form)
        return $this->checkFeedbackForm($form_data, $contact, $data_id);

      $items = array();
      foreach ($fields as $fid) {
        $where = array(dbKITformFields::field_id => $fid);
        $field = array();
        if (!$dbKITformFields->sqlSelectRecord($where, $field)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
          return false;
        }
        if (count($field) < 1) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The ID {{ id }} is invalid!', array(
              'id' => $fid))));
          return false;
        }
        $field = $field[0];
        switch ($field[dbKITformFields::field_data_type]) :
          case dbKITformFields::data_type_date:
            $value = date(cfg_datetime_str, $values[$fid]);
            break;
          case dbKITformFields::data_type_float:
            $value = number_format($values[$fid], 2, cfg_decimal_separator, cfg_thousand_separator);
            break;
          case dbKITformFields::data_type_integer:
          case dbKITformFields::data_type_text:
          default:
            $value = (is_array($values[$fid])) ? implode(', ', $values[$fid]) : $values[$fid];
        endswitch;
        $items[$field[dbKITformFields::field_name]] = array(
            'name' => $field[dbKITformFields::field_name],
            'label' => $field[dbKITformFields::field_title],
            'value' => $value,
            'type' => $field[dbKITformFields::field_type]);
      }
      // E-Mail Versand vorbereiten
      $provider_data = array();
      if (!$kitContactInterface->getServiceProviderByID($form[dbKITform::field_provider_id], $provider_data)) {
        if ($kitContactInterface->isError()) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
        }
        else {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage()));
        }
        return false;
      }
      $provider_email = $provider_data['email'];
      $provider_name = $provider_data['name'];
      $relaying = ($provider_data['relaying'] == 1) ? true : false;

      $form_d = $form_data;
      $form_d['datetime'] = date(cfg_datetime_str, strtotime($form_d[dbKITformData::field_date]));
      $form_d['subject'] = $form[dbKITform::field_title];

      $data = array(
          'form' => $form_d,
          'contact' => $contact,
          'items' => $items,
          'files' => $uploaded_files);
      $client_mail = $this->getTemplate('mail.client.htt', $data);
      if ($form[dbKITform::field_email_html] == dbKITform::html_off)
        $client_mail = strip_tags($client_mail);
      $client_subject = strip_tags($this->getTemplate('mail.client.subject.htt', $data));

      // E-Mail an den Absender des Formulars
      $mail = new kitMail($form[dbKITform::field_provider_id]);
      if (!$mail->mail($client_subject, $client_mail, $provider_email, $provider_name, array(
          $contact[kitContactInterface::kit_email] => $contact[kitContactInterface::kit_email]), ($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false)) {
        $err = $mail->getMailError();
        if (empty($err))
          $err = $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
              'email' => $contact[kitContactInterface::kit_email]));
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $err));
        return false;
      }
      // E-Mail an den Betreiber der Website
      $provider_mail = $this->getTemplate('mail.provider.htt', $data);
      if ($form[dbKITform::field_email_html] == dbKITform::html_off)
        $provider_mail = strip_tags($provider_mail);
      $provider_subject = stripslashes($this->getTemplate('mail.provider.subject.htt', $data));

      $cc_array = array();
      $ccs = explode(',', $form[dbKITform::field_email_cc]);
      foreach ($ccs as $cc) {
        if (!empty($cc))
          $cc_array[$cc] = $cc;
      }
      $mail = new kitMail($form[dbKITform::field_provider_id]);
      if (!$relaying) {
        $mail->AddReplyTo($contact[kitContactInterface::kit_email], $contact[kitContactInterface::kit_email]);
        $from_email = $provider_email;
        $from_name = $contact[kitContactInterface::kit_email];
      }
      else {
        $from_email = $contact[kitContactInterface::kit_email];
        $from_name = $contact[kitContactInterface::kit_email];
      }
      if (!$mail->mail($provider_subject, $provider_mail, $from_email, $from_name, array(
          $provider_email => $provider_name), ($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false, $cc_array)) {
        $err = $mail->getMailError();
        if (empty($err))
          $err = $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
              'email' => $contact[kitContactInterface::kit_email]));
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $err));
        return false;
      }
      return $this->getTemplate('confirm.htt', $data);
    } // checked

    if ($checked == false) {
      if (isset($_REQUEST[kitContactInterface::kit_password]))
        unset($_REQUEST[kitContactInterface::kit_password]);
      if (isset($_REQUEST[kitContactInterface::kit_password_retype]))
        unset($_REQUEST[kitContactInterface::kit_password_retype]);
    }
    else {
      unset($_REQUEST['upload_id']);
    }

    $this->setMessage($message);
    return $this->showForm();
  } // checkForm()

  /**
   * Process a form as delayed transmission, confirm it to the user, send a
   * email with link to edit the form and write the protocol
   *
   * @param $data_id integer
   * @param $contact_id integer
   * @return string confirm message or boolean false on error
   */
  protected function delayedTransmission($data_id, $contact_id) {
    global $kitContactInterface;
    global $dbKITformData;
    global $dbKITform;
    global $dbKITformCommands;
    global $kitLibrary;

    $contact = array();
    if (!$kitContactInterface->getContact($contact_id, $contact)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
      return false;
    }

    $form_data = array();
    $where = array(dbKITformData::field_id => $data_id);
    if (!$dbKITformData->sqlSelectRecord($where, $form_data)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
      return false;
    }
    $form_data = $form_data[0];

    $form = array();
    $where = array(
        dbKITform::field_id => $form_data[dbKITformData::field_form_id]);
    if (!$dbKITform->sqlSelectRecord($where, $form)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
      return false;
    }
    $form = $form[0];

    // write log file
    $protocol = $this->lang->translate('[kitForm] The contact has saved a form for later transmission (ID {{ id }}).', array(
        'id' => $data_id));
    $kitContactInterface->addNotice($contact_id, $protocol);

    // create command
    $cmd_delayed = $kitLibrary->createGUID();

    $data = array(
        dbKITformCommands::FIELD_COMMAND => $cmd_delayed,
        dbKITformCommands::FIELD_PARAMS => http_build_query($form_data),
        dbKITformCommands::FIELD_TYPE => dbKITformCommands::TYPE_DELAYED_TRANSMISSION,
        dbKITformCommands::FIELD_STATUS => dbKITformCommands::STATUS_WAITING);
    if (!$dbKITformCommands->sqlInsertRecord($data)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformCommands->getError()));
      return false;
    }

    // gather the data for displaying and sending the confirmation to the
    // client
    $data = array(
        'form' => array(
            'subject' => $form[dbKITform::field_title],
            'title' => $form[dbKITform::field_title],
            'link' => sprintf('%s?%s#%s', $this->page_link, http_build_query(array(
                self::request_action => self::action_command,
                self::request_command => $cmd_delayed)), self::FORM_ANCHOR)),
        'contact' => $contact);

    // get the provider data
    $provider_data = array();
    if (!$kitContactInterface->getServiceProviderByID($form[dbKITform::field_provider_id], $provider_data)) {
      if ($kitContactInterface->isError()) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
      }
      else {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage()));
      }
      return false;
    }

    // send the mail to the client
    $client_mail = $this->getTemplate('mail.client.delayed.transmission.htt', $data);
    if ($form[dbKITform::field_email_html] == dbKITform::html_off)
      $client_mail = strip_tags($client_mail);
    $client_subject = strip_tags($this->getTemplate('mail.client.subject.htt', $data));

    // send email to the client
    $mail = new kitMail($form[dbKITform::field_provider_id]);
    if (!$mail->mail($client_subject, $client_mail, $provider_data['email'], $provider_data['email'], array(
        $contact[kitContactInterface::kit_email] => $contact[kitContactInterface::kit_email]), ($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false)) {
      // get the error information
      $err = $mail->getMailError();
      if (empty($err))
      // if no description available set general fault message
        $err = $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
            'email' => $contact[kitContactInterface::kit_email]));
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $err));
      return false;
    }

    return $this->getTemplate('confirm.delayed.transmission.htt', $data);
  } // delayedTransmission()

  /**
   * Check if a directory is empty or not
   *
   * @param $directory string
   * @return boolean
   */
  protected function isDirectoryEmpty($directory) {
    // if directory not exists return true...
    if (!file_exists($directory))
      return true;
    // get a handle
    if (false === ($handle = @opendir($directory))) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Can\'t open the directory <b>{{ directory }}</b>!', array(
          'directory' => $directory))));
      return false;
    }
    // read directory
    while (false !== ($f = readdir($handle))) {
      // . and .. exists always!
      if ($f == "." || $f == "..") {
        continue;
      }
      else {
        // directory is not empty
        closedir($handle);
        return false;
      }
    }
    closedir($handle);
    return true;
  } // isDirectoryEmpty()

  /**
   * Prueft den LOGIN und schaltet den User ggf.
   * frei
   *
   * @param $form_data array
   * @return boolean true on success BOOL false on program error STR dialog on
   *         invalid login
   */
  protected function checkLogin($form_data = array()) {
    global $kitContactInterface;
    global $kitLibrary;

    if (!isset($_REQUEST[kitContactInterface::kit_email]) || !isset($_REQUEST[kitContactInterface::kit_password])) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The datafields for the email address and/or the password are empty, please check!')));
      return false;
    }
    if (!$kitLibrary->validateEMail($_REQUEST[kitContactInterface::kit_email])) {
      unset($_REQUEST[kitContactInterface::kit_password]);
      $this->setMessage($this->lang->translate('<p>The email address <b>{{ email }}</b> is not valid, please check your input.</p>', array(
          'email' => $_REQUEST[kitContactInterface::kit_email])));
      return $this->showForm();
    }
    $contact = array();
    $must_change_password = false;
    if ($kitContactInterface->checkLogin($_REQUEST[kitContactInterface::kit_email], $_REQUEST[kitContactInterface::kit_password], $contact, $must_change_password)) {
      if ($must_change_password) {
        // the user must change his password!
        unset($_REQUEST[kitContactInterface::kit_password]);
        if (isset($form_data[dbKITform::field_links])) {
          $parse = str_replace('&amp;', '&', $form_data[dbKITform::field_links]);
          parse_str($parse, $links);
          if (isset($links[dbKITform::action_change_password]) && ($links[dbKITform::action_change_password] != dbKITform::action_none)) {
            // load the desired form
            unset($_REQUEST[self::request_link]);
            unset($_REQUEST[dbKITform::field_id]);
            $this->params[self::PARAM_FORM] = $links[dbKITform::action_change_password];
            $this->setMessage($this->lang->translate('<p>Your password is not secure, please choose a new password!</p>'));
            return $this->showForm();
          }
        }
      }
      // Login erfolgreich
      $this->setContact($contact);
      return true;
    }
    elseif ($kitContactInterface->isError()) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
      return false;
    }
    else {
      // Fehler beim Login...
      unset($_REQUEST[kitContactInterface::kit_password]);
      $this->setMessage($kitContactInterface->getMessage());
      return $this->showForm();
    }
  } // checkLogin()

  /**
   * Special form: Feedback Form
   * Shows a thread with all comments to the desired page and a dialog
   * for the feedback itself.
   * All "normal" data for displaying the form are already collected and
   * present, this function adds only the special features for displaying
   * the feedback thread.
   *
   * @param $form_id integer
   *            - ID of the used form
   * @param $form_data array
   *            - form data, ready for parser
   * @param $form_fields array
   *            - field data, ready for parser
   * @return string feedback form on success or boolean false on error
   */
  protected function showFeedbackForm($form_id, $form_data, $form_fields, $clear_fields) {
    global $dbKITform;
    global $dbKITformData;
    global $dbKITformFields;
    global $kitLibrary;

    // get all previous data of the feedback form
    $SQL = sprintf("SELECT * FROM %s WHERE %s='%s' ORDER BY %s ASC", $dbKITformData->getTableName(), dbKITformData::field_form_id, $form_id, dbKITformData::field_date);
    $feedbacks = array();
    if (!$dbKITformData->sqlExec($SQL, $feedbacks)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
      return false;
    }

    // get the fields of this form
    $where = array(dbKITformFields::field_form_id => $form_id);
    $ffields = array();
    if (!$dbKITformFields->sqlSelectRecord($where, $ffields)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
      return false;
    }
    foreach ($ffields as $ff) {
      switch ($ff[dbKITformFields::field_name]) :
        case self::FIELD_FEEDBACK_HOMEPAGE:
          $fb_homepage = $ff[dbKITformFields::field_id];
          break;
        case self::FIELD_FEEDBACK_NICKNAME:
          $fb_nickname = $ff[dbKITformFields::field_id];
          break;
        case self::FIELD_FEEDBACK_PUBLISH:
          $fb_publish = $ff[dbKITformFields::field_id];
          break;
        case self::FIELD_FEEDBACK_SUBJECT:
          $fb_subject = $ff[dbKITformFields::field_id];
          break;
        case self::FIELD_FEEDBACK_SUBSCRIPTION:
          $fb_subscription = $ff[dbKITformFields::field_id];
          break;
        case self::FIELD_FEEDBACK_TEXT:
          $fb_text = $ff[dbKITformFields::field_id];
          break;
        case self::FIELD_FEEDBACK_URL:
          $fb_url = $ff[dbKITformFields::field_id];
          break;
      endswitch;
    }

    if ($clear_fields) {
      // the feedback was just submitted - clear the fields of the form!
      $rewrite = array();
      foreach ($form_fields as $key => $ff) {
        $ff['value'] = '';
        $rewrite[$key] = $ff;
      }
      $form_fields = $rewrite;
    }

    $url = '';
    if (!$kitLibrary->getUrlByPageID(PAGE_ID, $url)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('kitForm can\'t determine the URL of the calling page.')));
      return false;
    }
    if (!isset($form_fields[self::FIELD_FEEDBACK_URL])) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The feedback form is not complete - missing the datafield <b>feedback_url</b>!')));
      return false;
    }
    $form_fields[self::FIELD_FEEDBACK_URL]['value'] = $url;

    $feedback_array = array();
    foreach ($feedbacks as $feedback) {
      $parse = str_replace('&amp;', '&', $feedback[dbKITformData::field_values]);
      parse_str($parse, $fields);
      $publish = true;
      if (isset($fields[$fb_publish]) && ($fields[$fb_publish] != self::PUBLISH_IMMEDIATE))
        $publish = false;
      if (!isset($fields[$fb_url]))
        continue;
      if ($publish && ($fields[$fb_url] == $url)) {
        $feedback_array[] = array(
            'url' => $url,
            'subject' => isset($fields[$fb_subject]) ? $fields[$fb_subject] : '',
            'text' => isset($fields[$fb_text]) ? $fields[$fb_text] : '',
            'homepage' => isset($fields[$fb_homepage]) ? $fields[$fb_homepage] : '',
            'nickname' => isset($fields[$fb_nickname]) ? $fields[$fb_nickname] : '',
            'date' => array(
                'timestamp' => $feedback[dbKITformData::field_date],
                'formatted' => date(cfg_datetime_str, strtotime($feedback[dbKITformData::field_date]))));
      }
    }
    $data = array(
        'feedback' => array(
            'items' => $feedback_array,
            'count' => count($feedback_array)),
        'form' => $form_data,
        'fields' => $form_fields);
    return $this->getTemplate('feedback.htt', $data);
  } // showFeedbackForm()

  /**
   * Check the feedback form and return the submitted feedback
   *
   * @param $form_data array
   * @param $contact_data array
   * @param $data_id integer
   * @return string feedback form or boolean false on error
   */
  protected function checkFeedbackForm($form_data = array(), $contact_data = array(), $data_id) {
    global $dbKITform;
    global $dbKITformFields;
    global $kitContactInterface;
    global $kitLibrary;
    global $dbKITformData;
    global $dbKITformCommands;

    // set FORM_ID
    $form_id = $form_data['form_id'];
    // set message
    $message = '';

    // get the form itself
    $where = array(dbKITform::field_id => $form_id);
    $form = array();
    if (!$dbKITform->sqlSelectRecord($where, $form)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
      return false;
    }
    $form = $form[0];

    // get the form fields
    $where = array(dbKITformFields::field_form_id => $form_id);
    $form_fields = array();
    if (!$dbKITformFields->sqlSelectRecord($where, $form_fields)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
      return false;
    }

    foreach ($form_fields as $ff) {
      switch ($ff[dbKITformFields::field_name]) :
        case self::FIELD_FEEDBACK_HOMEPAGE:
          $fb_homepage = $ff[dbKITformFields::field_id];
          break;
        case self::FIELD_FEEDBACK_NICKNAME:
          $fb_nickname = $ff[dbKITformFields::field_id];
          break;
        case self::FIELD_FEEDBACK_PUBLISH:
          $fb_publish = $ff[dbKITformFields::field_id];
          break;
        case self::FIELD_FEEDBACK_SUBJECT:
          $fb_subject = $ff[dbKITformFields::field_id];
          break;
        case self::FIELD_FEEDBACK_SUBSCRIPTION:
          $fb_subscription = $ff[dbKITformFields::field_id];
          break;
        case self::FIELD_FEEDBACK_TEXT:
          $fb_text = $ff[dbKITformFields::field_id];
          break;
        case self::FIELD_FEEDBACK_URL:
          $fb_url = $ff[dbKITformFields::field_id];
          break;
      endswitch;
    }

    // get the submitted data
    $where = array(dbKITformData::field_id => $data_id);
    $f_data = array();
    if (!$dbKITformData->sqlSelectRecord($where, $f_data)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
      return false;
    }
    $f_data = $f_data[0];
    $parse = str_replace('&amp;', '&', $f_data[dbKITformData::field_values]);
    parse_str($parse, $values);
    $feedback_array = array(
        self::FIELD_FEEDBACK_HOMEPAGE => isset($fb_homepage) ? $values[$fb_homepage] : '',
        self::FIELD_FEEDBACK_NICKNAME => isset($fb_nickname) ? $values[$fb_nickname] : '',
        self::FIELD_FEEDBACK_PUBLISH => isset($fb_publish) ? $values[$fb_publish] : self::PUBLISH_IMMEDIATE,
        self::FIELD_FEEDBACK_SUBJECT => isset($fb_subject) ? $values[$fb_subject] : '',
        self::FIELD_FEEDBACK_SUBSCRIPTION => isset($fb_subscription) ? $values[$fb_subscription] : self::SUBSCRIPE_NO,
        self::FIELD_FEEDBACK_TEXT => isset($fb_text) ? $values[$fb_text] : '',
        self::FIELD_FEEDBACK_URL => isset($fb_url) ? $values[$fb_url] : '');

    // prepare sending emails
    $provider_data = array();
    if (!$kitContactInterface->getServiceProviderByID($form[dbKITform::field_provider_id], $provider_data)) {
      if ($kitContactInterface->isError()) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
      }
      else {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage()));
      }
      return false;
    }
    $provider_email = $provider_data['email'];
    $provider_name = $provider_data['name'];
    $relaying = ($provider_data['relaying'] == 1) ? true : false;

    // create and save commands
    $cmd_publish = $kitLibrary->createGUID();
    $cmd_refuse = $kitLibrary->createGUID();

    $data = array(
        dbKITformCommands::FIELD_COMMAND => $cmd_publish,
        dbKITformCommands::FIELD_PARAMS => http_build_query(array(
            'form' => $form,
            'contact' => $contact_data,
            'data_id' => $data_id)),
        dbKITformCommands::FIELD_TYPE => dbKITformCommands::TYPE_FEEDBACK_PUBLISH,
        dbKITformCommands::FIELD_STATUS => dbKITformCommands::STATUS_WAITING);
    if (!$dbKITformCommands->sqlInsertRecord($data)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformCommands->getError()));
      return false;
    }

    $data = array(
        dbKITformCommands::FIELD_COMMAND => $cmd_refuse,
        dbKITformCommands::FIELD_PARAMS => http_build_query(array(
            'form' => $form,
            'contact' => $contact_data,
            'data_id' => $data_id)),
        dbKITformCommands::FIELD_TYPE => dbKITformCommands::TYPE_FEEDBACK_REFUSE,
        dbKITformCommands::FIELD_STATUS => dbKITformCommands::STATUS_WAITING);
    if (!$dbKITformCommands->sqlInsertRecord($data)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformCommands->getError()));
      return false;
    }

    // send E-Mail to the feedback author
    $data = array(
        'feedback' => array(
            'field' => $feedback_array,
            'unsubscribe_link' => sprintf('%s?%s#%s', $this->page_link, http_build_query(array(
                self::request_action => self::action_feedback_unsubscribe,
                self::request_form_id => $form_id)), self::FORM_ANCHOR)),
        'contact' => $contact_data,
        'command' => array(
            'publish_feedback' => sprintf('%s?%s#%s', $this->page_link, http_build_query(array(
                self::request_action => self::action_command,
                self::request_command => $cmd_publish)), self::FORM_ANCHOR),
            'refuse_feedback' => sprintf('%s?%s#%s', $this->page_link, http_build_query(array(
                self::request_action => self::action_command,
                self::request_command => $cmd_refuse)), self::FORM_ANCHOR)));

    $client_mail = $this->getTemplate('mail.feedback.author.submit.htt', $data);
    if ($form[dbKITform::field_email_html] == dbKITform::html_off)
      $client_mail = strip_tags($client_mail);
    $client_subject = strip_tags($this->getTemplate('mail.feedback.subject.htt', array(
        'subject' => $form[dbKITform::field_title])));

    // email to the feedback author
    $mail = new kitMail($form[dbKITform::field_provider_id]);
    if (!$mail->mail($client_subject, $client_mail, $provider_email, $provider_name, array(
        $contact_data[kitContactInterface::kit_email] => $contact_data[kitContactInterface::kit_email]), ($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false)) {
      $err = $mail->getMailError();
      if (empty($err))
        $err = $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
            'email' => $contact_data[kitContactInterface::kit_email]));
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $err));
      return false;
    }
    // Mitteilung auf der Seite
    if ($feedback_array[self::FIELD_FEEDBACK_PUBLISH] == self::PUBLISH_IMMEDIATE) {
      $message .= $this->lang->translate('<p>Thank you for the feedback!</p><p>Your feedback is already published, we have send you a copy to your email address <b>{{ email }}</b>.</p>', array(
          'email' => $contact_data[kitContactInterface::kit_email]));
    }
    else {
      $message .= $this->lang->translate('<p>Thank your for the feedback!</p><p>We will check and publish your feedback as soon as possible. We have send you a copy of your feedback to your email address <b>{{ email }}</b>.</p>', array(
          'email' => $contact_data[kitContactInterface::kit_email]));
    }

    // send email to webmaster
    $provider_mail = $this->getTemplate('mail.feedback.provider.submit.htt', $data);
    if ($form[dbKITform::field_email_html] == dbKITform::html_off)
      $provider_mail = strip_tags($provider_mail);
    $provider_subject = stripslashes($this->getTemplate('mail.feedback.subject.htt', array(
        'subject' => $form[dbKITform::field_title])));

    $cc_array = array();
    $ccs = explode(',', $form[dbKITform::field_email_cc]);
    foreach ($ccs as $cc) {
      if (!empty($cc))
        $cc_array[$cc] = $cc;
    }
    $mail = new kitMail($form[dbKITform::field_provider_id]);
    if (!$relaying) {
      $mail->AddReplyTo($contact_data[kitContactInterface::kit_email], $contact_data[kitContactInterface::kit_email]);
      $from_email = $provider_email;
      $from_name = $contact_data[kitContactInterface::kit_email];
    }
    else {
      $from_email = $contact_data[kitContactInterface::kit_email];
      $from_name = $contact_data[kitContactInterface::kit_email];
    }
    if (!$mail->mail($provider_subject, $provider_mail, $from_email, $from_name, array(
        $provider_email => $provider_name), ($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false, $cc_array)) {
      $err = $mail->getMailError();
      if (empty($err))
        $err = $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
            'email' => $contact_data[kitContactInterface::kit_email]));
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $err));
      return false;
    }

    $subscriber_emails = array();
    if (isset($fb_subscription) && ($feedback_array[self::FIELD_FEEDBACK_PUBLISH] == self::PUBLISH_IMMEDIATE)) {
      // get subsribers ...
      $where = array(dbKITformData::field_form_id => $form_id);
      $sub_data = array();
      if (!$dbKITformData->sqlSelectRecord($where, $sub_data)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
        return false;
      }
      foreach ($sub_data as $sub) {
        $parse = str_replace('&amp;', '&', $sub[dbKITformData::field_values]);
        parse_str($parse, $values);
        if (isset($values[$fb_subscription][0]) && ($values[$fb_subscription][0] == self::SUBSCRIPE_YES)) {
          $cont = array();
          if (!$kitContactInterface->getContact($sub[dbKITformData::field_kit_id], $cont)) {
            /* it is possible that an ID is deleted, so dont prompt a error, still continue ...
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The ID {{ id }} is invalid!', array(
                'id' => $sub[dbKITformData::field_kit_id]))));
            return false;
            */
            continue;
          }
          if (!in_array($cont[kitContactInterface::kit_email], $subscriber_emails) && ($cont[kitContactInterface::kit_email] != $contact_data[kitContactInterface::kit_email])) {
            $subscriber_emails[] = $cont[kitContactInterface::kit_email];
          }
        }
      }
    }

    if (count($subscriber_emails) > 0) {
      $subscriber_mail = $this->getTemplate('mail.feedback.subscriber.submit.htt', $data);
      if ($form[dbKITform::field_email_html] == dbKITform::html_off)
        $subscriber_mail = strip_tags($subscriber_mail);
      $subscriber_subject = stripslashes($this->getTemplate('mail.feedback.subject.htt', array(
          'subject' => $form[dbKITform::field_title])));

      $bcc_array = array();
      foreach ($subscriber_emails as $cc) {
        if (!empty($cc))
          $bcc_array[$cc] = $cc;
      }
      $mail = new kitMail($form[dbKITform::field_provider_id]);
      if (!$mail->mail($subscriber_subject, $subscriber_mail, $provider_email, $provider_name, array(
          $provider_email => $provider_name), ($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false, array(), $bcc_array)) {
        $err = $mail->getMailError();
        if (empty($err))
          $err = $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
              'email' => $contact_data[kitContactInterface::kit_email]));
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $err));
        return false;
      }

    }
    // set messages for the feedback author
    $this->setMessage($message);
    // show the feedback form again, set the switch to clear the form fields
    return $this->showForm(true);
  } // checkFeedback()

  /**
   * Show dialog to unsubscribe from feedback messages for a page
   *
   * @return string dialog
   */
  protected function showFeedbackUnsubscribe() {
    global $kitContactInterface;

    $form_id = isset($_REQUEST[self::request_form_id]) ? $_REQUEST[self::request_form_id] : -1;

    if ($form_id < 1) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Invalid function call')));
      return false;
    }


    // CAPTCHA
    ob_start();
    call_captcha();
    $call_captcha = ob_get_contents();
    ob_end_clean();

    $data = array(
        'form' => array(
            'title' => $this->lang->translate('Unsubscribe Feedback'),
            'response' => ($this->isMessage()) ? $this->getMessage() : $this->lang->translate('Please enter your email address to unsubscribe from automatical reports at new feedbacks of this site.'),
            'name' => 'feedback_unsubscribe',
            'action' => array(
                'link' => $this->page_link,
                'name' => self::request_action,
                'value' => self::action_feedback_unsubscribe_check),
            'anchor' => self::FORM_ANCHOR,
            'id' => array('name' => self::request_form_id, 'value' => $form_id),
            kitContactInterface::kit_email => array(
                'label' => $kitContactInterface->field_array[kitContactInterface::kit_email],
                'name' => kitContactInterface::kit_email,
                'value' => '',
                'hint' => ''),
            'btn' => array(
                'ok' => $this->lang->translate('OK'),
                'abort' => $this->lang->translate('Abort')),
            'captcha' => array('code' => $call_captcha)
            )
        );
    return $this->getTemplate('feedback.unsubscribe.htt', $data);
  } // showFeedbackUnsubscribe()

  protected function checkFeedbackUnsubscribe() {
    global $kitLibrary;
    global $kitContactInterface;
    global $dbKITformData;
    global $dbKITformFields;

    $email = isset($_REQUEST[kitContactInterface::kit_email]) ? $_REQUEST[kitContactInterface::kit_email] : '';
    if (!$kitLibrary->validateEMail($email)) {
      $this->setMessage($this->lang->translate('<p>The email address <b>{{ email }}</b> is not valid, please check your input.</p>', array(
          'email' => $email)));
      return $this->showFeedbackUnsubscribe();
    }

    // check CAPTCHA
    unset($_SESSION['kf_captcha']);
    if (!isset($_REQUEST['captcha']) || ($_REQUEST['captcha'] != $_SESSION['captcha'])) {
      $this->setMessage($this->lang->translate('<p>The CAPTCHA code is not correct, please try again!</p>'));
      return $this->showFeedbackUnsubscribe();
    }

    $form_id = isset($_REQUEST[self::request_form_id]) ? $_REQUEST[self::request_form_id] : -1;

    $status = dbKITcontact::status_active;
    $contact_id = -1;
    if (!$kitContactInterface->isEMailRegistered($email, $contact_id, $status)) {
      if ($kitContactInterface->isError()) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
        return false;
      }
      $this->setMessage($this->lang->translate('<p>The email address <b>{{ email }}</b> is not registered.</p>', array(
          'email' => $email)));
      return $this->showFeedbackUnsubscribe();
    }

    // search for form datas for this user
    $where = array(
        dbKITformData::field_kit_id => $contact_id,
        dbKITformData::field_form_id => $form_id);
    $form_data = array();
    if (!$dbKITformData->sqlSelectRecord($where, $form_data)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
      return false;
    }
    // get field id for feedback_subscription
    $where = array(
        dbKITformFields::field_form_id => $form_id,
        dbKITformFields::field_name => self::FIELD_FEEDBACK_SUBSCRIPTION);
    $fields = array();
    if (!$dbKITformFields->sqlSelectRecord($where, $fields)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
      return false;
    }
    if (count($fields) < 1) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Invalid function call')));
      return false;
    }
    $fb_subscription = $fields[0][dbKITformFields::field_id];
    // get field id for feedback_url
    $where = array(
        dbKITformFields::field_form_id => $form_id,
        dbKITformFields::field_name => self::FIELD_FEEDBACK_URL);
    $fields = array();
    if (!$dbKITformFields->sqlSelectRecord($where, $fields)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
      return false;
    }
    if (count($fields) < 1) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Invalid function call')));
      return false;
    }
    $fb_url = $fields[0][dbKITformFields::field_id];

    $url = '';
    $kitLibrary->getUrlByPageID(PAGE_ID, $url);

    $unsubscribed = false;
    foreach ($form_data as $data) {
      $parse = str_replace('&amp;', '&', $data[dbKITformData::field_values]);
      parse_str($parse, $values);
      if (isset($values[$fb_subscription][0]) && isset($values[$fb_url])) {
        if (($values[$fb_subscription][0] == self::SUBSCRIPE_YES) && ($values[$fb_url] == $url)) {
          // update record
          $values[$fb_subscription][0] = self::SUBSCRIPE_NO;
          $where = array(
              dbKITformData::field_id => $data[dbKITformData::field_id]);
          $upd = array(
              dbKITformData::field_values => http_build_query($values),
              dbKITformData::field_timestamp => date('Y-m-d H:i:s'));
          if (!$dbKITformData->sqlUpdateRecord($upd, $where)) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
            return false;
          }
          $unsubscribed = true;
        }
      }
    }
    if ($unsubscribed) {
      $this->setMessage($this->lang->translate('<p>The email address <b>{{ email }}</b> does no longer receive messages at new feedbacks on this page.</p><p>The settings of other pages are not changed!</p>', array(
          'email' => $email)));
    }
    else {
      $this->setMessage($this->lang->translate('<p>The email address <b>{{ email }}</b> does not receive any messages from this page, so nothing was changed.</p>', array(
          'email' => $email)));
    }
    return $this->showForm();
  } // checkFeedbackUnsubscribe()

  protected function checkCommand() {
    global $dbKITformCommands;
    global $dbKITformData;
    global $dbKITformFields;
    global $kitContactInterface;
    global $dbKITform;

    if (!isset($_REQUEST[self::request_command])) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('This command does not exists or was already executed!')));
      return false;
    }
    $where = array(
        dbKITformCommands::FIELD_COMMAND => $_REQUEST[self::request_command]);
    $command = array();
    if (!$dbKITformCommands->sqlSelectRecord($where, $command)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformCommands->getError()));
      return false;
    }
    if (count($command) == 1) {
      $command = $command[0];
      if (($command[dbKITformCommands::FIELD_TYPE] == dbKITformCommands::TYPE_FEEDBACK_PUBLISH) || ($command[dbKITformCommands::FIELD_TYPE] == dbKITformCommands::TYPE_FEEDBACK_REFUSE)) {
        // Feedback zurueckweisen
        $parse = str_replace('&amp;', '&', $command[dbKITformCommands::FIELD_PARAMS]);
        parse_str($parse, $params);
        if (isset($params['data_id'])) {
          $form_data = array();
          $where = array(dbKITformData::field_id => $params['data_id']);
          if (!$dbKITformData->sqlSelectRecord($where, $form_data)) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
            return false;
          }
          if (count($form_data) == 1) {
            $form_data = $form_data[0];
            // get the form fields
            $where = array(
                dbKITformFields::field_form_id => $form_data[dbKITformData::field_form_id]);
            $form_fields = array();
            if (!$dbKITformFields->sqlSelectRecord($where, $form_fields)) {
              $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
              return false;
            }
            foreach ($form_fields as $ff) {
              switch ($ff[dbKITformFields::field_name]) :
                case self::FIELD_FEEDBACK_HOMEPAGE:
                  $fb_homepage = $ff[dbKITformFields::field_id];
                  break;
                case self::FIELD_FEEDBACK_NICKNAME:
                  $fb_nickname = $ff[dbKITformFields::field_id];
                  break;
                case self::FIELD_FEEDBACK_PUBLISH:
                  $fb_publish = $ff[dbKITformFields::field_id];
                  break;
                case self::FIELD_FEEDBACK_SUBJECT:
                  $fb_subject = $ff[dbKITformFields::field_id];
                  break;
                case self::FIELD_FEEDBACK_SUBSCRIPTION:
                  $fb_subscription = $ff[dbKITformFields::field_id];
                  break;
                case self::FIELD_FEEDBACK_TEXT:
                  $fb_text = $ff[dbKITformFields::field_id];
                  break;
                case self::FIELD_FEEDBACK_URL:
                  $fb_url = $ff[dbKITformFields::field_id];
                  break;
              endswitch;
            }
            if (isset($fb_publish)) {
              $parse = str_replace('&amp;', '&', $form_data[dbKITformData::field_values]);
              parse_str($parse, $values);
              if (isset($values[$fb_publish])) {
                if ($command[dbKITformCommands::FIELD_TYPE] == dbKITformCommands::TYPE_FEEDBACK_REFUSE) {
                  $values[$fb_publish] = self::PUBLISH_FORBIDDEN;
                }
                else {
                  $values[$fb_publish] = self::PUBLISH_IMMEDIATE;
                }
                $where = array(dbKITformData::field_id => $params['data_id']);
                $data = array(
                    dbKITformData::field_values => http_build_query($values),
                    dbKITformData::field_timestamp => date('Y-m-d H:i:s'));
                if (!$dbKITformData->sqlUpdateRecord($data, $where)) {
                  $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
                  return false;
                }
                // delete command
                $where = array(
                    dbKITformCommands::FIELD_ID => $command[dbKITformCommands::FIELD_ID]);
                if (!$dbKITformCommands->sqlDeleteRecord($where)) {
                  $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformCommands->getError()));
                  return false;
                }
                if ($command[dbKITformCommands::FIELD_TYPE] == dbKITformCommands::TYPE_FEEDBACK_REFUSE) {
                  // feedback is successfully refused!
                  $this->setMessage($this->lang->translate('<p>The feedback was refused!</p>'));
                }
                else {
                  // feedback is now published - check for
                  // subscriber!
                  $subscriber_emails = array();
                  $where = array(
                      dbKITformData::field_form_id => $form_data[dbKITformData::field_form_id]);
                  $sub_data = array();
                  if (!$dbKITformData->sqlSelectRecord($where, $sub_data)) {
                    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
                    return false;
                  }
                  foreach ($sub_data as $sub) {
                    $parse = str_replace('&amp;', '&', $sub[dbKITformData::field_values]);
                    parse_str($parse, $values);
                    if (isset($values[$fb_subscription][0]) && ($values[$fb_subscription][0] == self::SUBSCRIPE_YES)) {
                      $cont = array();
                      if (!$kitContactInterface->getContact($sub[dbKITformData::field_kit_id], $cont)) {
                        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The ID {{ id }} is invalid!', array(
                            'id' => $sub[dbKITformData::field_kit_id]))));
                        return false;
                      }
                      if (!in_array($cont[kitContactInterface::kit_email], $subscriber_emails) && ($cont[kitContactInterface::kit_email] != $params['contact'][kitContactInterface::kit_email])) {
                        $subscriber_emails[] = $cont[kitContactInterface::kit_email];
                      }
                    }
                  }
                  if (count($subscriber_emails) > 0) {
                    // prepare emails and send out...
                    $form = array();
                    $where = array(
                        dbKITform::field_id => $form_data[dbKITformData::field_form_id]);
                    if (!$dbKITform->sqlSelectRecord($where, $form)) {
                      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITform->getError()));
                      return false;
                    }
                    $form = $form[0];
                    // prepare sending emails
                    $provider_data = array();
                    if (!$kitContactInterface->getServiceProviderByID($form[dbKITform::field_provider_id], $provider_data)) {
                      if ($kitContactInterface->isError()) {
                        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
                      }
                      else {
                        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage()));
                      }
                      return false;
                    }
                    $provider_email = $provider_data['email'];
                    $provider_name = $provider_data['name'];

                    $feedback_array = array(
                        self::FIELD_FEEDBACK_HOMEPAGE => isset($fb_homepage) ? $values[$fb_homepage] : '',
                        self::FIELD_FEEDBACK_NICKNAME => isset($fb_nickname) ? $values[$fb_nickname] : '',
                        self::FIELD_FEEDBACK_PUBLISH => isset($fb_publish) ? $values[$fb_publish] : self::PUBLISH_IMMEDIATE,
                        self::FIELD_FEEDBACK_SUBJECT => isset($fb_subject) ? $values[$fb_subject] : '',
                        self::FIELD_FEEDBACK_SUBSCRIPTION => isset($fb_subscription) ? $values[$fb_subscription] : self::SUBSCRIPE_NO,
                        self::FIELD_FEEDBACK_TEXT => isset($fb_text) ? $values[$fb_text] : '',
                        self::FIELD_FEEDBACK_URL => isset($fb_url) ? $values[$fb_url] : '');

                    $body_data = array(
                        'feedback' => array(
                            'field' => $feedback_array,
                            'unsubscribe_link' => sprintf('%s?%s#%s', $this->page_link, http_build_query(array(
                                self::request_action => self::action_feedback_unsubscribe,
                                self::request_form_id => $form_data[dbKITformData::field_form_id])), self::FORM_ANCHOR)));

                    $subscriber_mail = $this->getTemplate('mail.feedback.subscriber.submit.htt', $body_data);
                    if ($form[dbKITform::field_email_html] == dbKITform::html_off)
                      $subscriber_mail = strip_tags($subscriber_mail);
                    $subscriber_subject = stripslashes($this->getTemplate('mail.feedback.subject.htt', array(
                        'subject' => $form[dbKITform::field_title])));

                    $bcc_array = array();
                    foreach ($subscriber_emails as $cc) {
                      if (!empty($cc))
                        $bcc_array[$cc] = $cc;
                    }
                    $mail = new kitMail($form[dbKITform::field_provider_id]);
                    if (!$mail->mail($subscriber_subject, $subscriber_mail, $provider_email, $provider_name, array(
                        $provider_email => $provider_name), ($form[dbKITform::field_email_html] == dbKITform::html_on) ? true : false, array(), $bcc_array)) {
                      $err = $mail->getMailError();
                      if (empty($err))
                        $err = $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
                            'email' => $provider_email));
                      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $err));
                      return false;
                    }
                  }

                  $this->setMessage($this->lang->translate('<p>The feedback was published.</p>'));
                }
                return $this->showForm();
              }
            }
          }
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The ID {{ id }} is invalid!', array(
              'id' => $params['data_id']))));
          return false;
        }
        else {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The command is not complete, missing parameters!')));
          return false;
        }
      }
      elseif ($command[dbKITformCommands::FIELD_TYPE] == dbKITformCommands::TYPE_DELAYED_TRANSMISSION) {
        // DELAYED TRANSMISSION - load the form data and show the form
        // again
        $parse = str_replace('&amp;', '&', $command[dbKITformCommands::FIELD_PARAMS]);
        parse_str($parse, $params);
        if (isset($params[dbKITformData::field_id])) {
          // read the transmitted data for this form
          $form_data = array();
          $where = array(
              dbKITformData::field_id => $params[dbKITformData::field_id],
              dbKITformData::field_status => dbKITformData::status_delayed);
          if (!$dbKITformData->sqlSelectRecord($where, $form_data)) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
            return false;
          }
          if (count($form_data) < 1) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The ID {{ id }} is invalid!', array(
                'id' => $params['data_id']))));
            return false;
          }
          $form_data = $form_data[0];
          $fields = array();
          $parse = str_replace('&amp;', '&', $form_data[dbKITformData::field_values]);
          parse_str($parse, $fields);
          foreach ($fields as $key => $value) {
            // set $_REQUESTs for each free field
            $where = array(dbKITformFields::field_id => $key);
            $field_data = array();
            if (!$dbKITformFields->sqlSelectRecord($where, $field_data)) {
              $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformFields->getError()));
              return false;
            }
            // prompt no error, still continue on error ...
            if (count($field_data) < 1)
              continue;
            $field_data = $field_data[0];
            // special: don't set the delayed transmission again!
            if ($field_data[dbKITformFields::field_type] == dbKITformFields::type_delayed)
              continue;
            $_REQUEST[$field_data[dbKITformFields::field_name]] = $value;
          }
          // get the contact data
          if (isset($params['kit_id'])) {
            $contact = array();
            if (!$kitContactInterface->getContact($params['kit_id'], $contact)) {
              $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
              return false;
            }
            foreach ($contact as $key => $value) {
              $_REQUEST[$key] = $value;
            }
          }
          // delete the saved form data
          $where = array(
              dbKITformData::field_id => $params[dbKITformData::field_id]);
          $data = array(
              dbKITformData::field_status => dbKITformData::status_deleted);
          if (!$dbKITformData->sqlUpdateRecord($data, $where)) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformData->getError()));
            return false;
          }
          // delete the command
          $where = array(
              dbKITformCommands::FIELD_ID => $command[dbKITformCommands::FIELD_ID]);
          if (!$dbKITformCommands->sqlDeleteRecord($where)) {
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbKITformCommands->getError()));
            return false;
          }
          // write protocol
          $notice = $this->lang->translate('[kitForm] The temporary saved form with the ID {{ id }} was deleted.', array(
              'id' => $params['data_id']));
          $kitContactInterface->addNotice($params['kit_id'], $notice);
          // message to inform the user about the deletion
          $this->setMessage($this->lang->translate('<p>The link to access this form is no longer valid and the temporary saved form data are now deleted.</p><p>Please submit the form or use again the option for a delayed transmission to create a new access link.</p>'));

          return $this->showForm();
        }
        else {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('The command is not complete, missing parameters!')));
          return false;
        }
      }
      else {
        // unknown command
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('This command does not exists or was already executed!')));
        return false;
      }
    }
    return $this->showForm();
    /*
    $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('This command does not exists or was already executed!')));
    return false;
    */
  } // checkCommand()

  protected function changePassword($form_data = array()) {
    global $kitContactInterface;

    if (!isset($_REQUEST[kitContactInterface::kit_email])) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Missing the datafield <b>{{ field }}</b>!', array(
          'field' => kitContactInterface::kit_email))));
      // reset the passwords!
      unset($_REQUEST[kitContactInterface::kit_password]);
      unset($_REQUEST[kitContactInterface::kit_password_retype]);
      return false;
    }
    $contact_id = -1;
    $status = dbKITcontact::status_active;
    if (!$kitContactInterface->isEMailRegistered($_REQUEST[kitContactInterface::kit_email], $contact_id, $status)) {
      // E-Mail Adresse ist nicht registriert
      $this->setMessage($this->lang->translate('<p>The email address <b>{{ email }}</b> is not registered.</p>', array(
          'email' => $_REQUEST[kitContactInterface::kit_email])));
      // reset the passwords!
      unset($_REQUEST[kitContactInterface::kit_password]);
      unset($_REQUEST[kitContactInterface::kit_password_retype]);
      return $this->showForm();
    }
    if ($status != dbKITcontact::status_active) {
      // Der Kontakt ist NICHT AKTIV!
      $this->setMessage($this->lang->translate('<p>The account for the email address <b>{{ email }}</b> is not active, please contact the service!</p>', array(
          'email' => $_REQUEST[kitContactInterface::kit_email])));
      // reset the passwords!
      unset($_REQUEST[kitContactInterface::kit_password]);
      unset($_REQUEST[kitContactInterface::kit_password_retype]);
      return $this->showForm();
    }
    // CAPTCHA pruefen?
    if ($form_data[dbKITform::field_captcha] == dbKITform::captcha_on) {
      unset($_SESSION['kf_captcha']);
      if (!isset($_REQUEST['captcha']) || ($_REQUEST['captcha'] != $_SESSION['captcha'])) {
        $this->setMessage($this->lang->translate('<p>The CAPTCHA code is not correct, please try again!</p>'));
        // reset the passwords!
        unset($_REQUEST[kitContactInterface::kit_password]);
        unset($_REQUEST[kitContactInterface::kit_password_retype]);
        return $this->showForm();
      }
    }

    // check if the passwords are valid
    if (!isset($_REQUEST[kitContactInterface::kit_password]) || !isset($_REQUEST[kitContactInterface::kit_password_retype]) || ($_REQUEST[kitContactInterface::kit_password] != $_REQUEST[kitContactInterface::kit_password_retype]) || (empty($_REQUEST[kitContactInterface::kit_password]))) {
      $password = trim($_REQUEST[kitContactInterface::kit_password]);
      $retype = trim($_REQUEST[kitContactInterface::kit_password_retype]);
      if (empty($password) || (empty($retype))) {
        $this->setMessage($this->lang->translate('<p>The password is empty!</p>'));
      }
      elseif (strlen($password) < $kitContactInterface->getConfigurationValue(kitContactInterface::CONFIG_PASSWORD_MINIMUM_LENGHT)) {
        $this->setMessage($this->lang->translate('<p>The password needs at least a length of {{ lenght }} characters!</p>', array(
            'lenght' => $kitContactInterface->getConfigurationValue(kitContactInterface::CONFIG_PASSWORD_MINIMUM_LENGHT))));
      }
      else {
        $this->setMessage($this->lang->translate('<p>The both passwords does not match, please check your input!</p>'));
      }
      // reset the passwords!
      unset($_REQUEST[kitContactInterface::kit_password]);
      unset($_REQUEST[kitContactInterface::kit_password_retype]);
      return $this->showForm();
    }

    $password = trim($_REQUEST[kitContactInterface::kit_password]);
    $retype = trim($_REQUEST[kitContactInterface::kit_password_retype]);

    if (!$kitContactInterface->isAuthenticated() || ($_SESSION[kitContactInterface::session_kit_contact_id] != $contact_id)) {
      $this->setMessage($this->lang->translate('<p>Please log in to change your password!</p>'));
      // reset the passwords!
      unset($_REQUEST[kitContactInterface::kit_password]);
      unset($_REQUEST[kitContactInterface::kit_password_retype]);
      return $this->showForm();
    }

    if (!$kitContactInterface->changePassword($_SESSION[kitContactInterface::session_kit_register_id], $contact_id, $password, $retype)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
      return false;
    }

    $contact = array();
    if (!$kitContactInterface->getContact($contact_id, $contact)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
      return false;
    }

    $form_data['subject'] = $form_data[dbKITform::field_title];

    $data = array(
        'contact' => $contact,
        'password' => $password,
        'form' => $form_data);

    $provider_data = array();
    if (!$kitContactInterface->getServiceProviderByID($form_data[dbKITform::field_provider_id], $provider_data)) {
      if ($kitContactInterface->isError()) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
      }
      else {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage()));
      }
      return false;
    }
    $provider_email = $provider_data['email'];
    $provider_name = $provider_data['name'];

    $client_mail = $this->getTemplate('mail.client.password.htt', $data);
    if ($form_data[dbKITform::field_email_html] == dbKITform::html_off)
      $client_mail = strip_tags($client_mail);
    $client_subject = strip_tags($this->getTemplate('mail.client.subject.htt', $data));

    $mail = new kitMail($form_data[dbKITform::field_provider_id]);
    if (!$mail->mail($client_subject, $client_mail, $provider_email, $provider_name, array(
        $contact[kitContactInterface::kit_email] => $contact[kitContactInterface::kit_email]), ($form_data[dbKITform::field_email_html] == dbKITform::html_on) ? true : false)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
          'email' => $contact[kitContactInterface::kit_email]))));
      return false;
    }

    return $this->getTemplate('confirm.password.htt', $data);
  } // changePassword()

  /**
   * Sendet dem User ein neues Passwort zu
   *
   * @param $form_data array
   *            - Formulardaten
   * @return boolean false on program error STR dialog/message on success
   */
  protected function sendNewPassword($form_data = array()) {
    global $kitContactInterface;

    if (!isset($_REQUEST[kitContactInterface::kit_email])) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Missing the datafield <b>{{ field }}</b>!', array(
          'field' => kitContactInterface::kit_email))));
      return false;
    }
    $contact_id = -1;
    $status = dbKITcontact::status_active;
    if (!$kitContactInterface->isEMailRegistered($_REQUEST[kitContactInterface::kit_email], $contact_id, $status)) {
      // E-Mail Adresse ist nicht registriert
      $this->setMessage($this->lang->translate('<p>The email address <b>{{ email }}</b> is not registered.</p>', array(
          'email' => $_REQUEST[kitContactInterface::kit_email])));
      return $this->showForm();
    }
    if ($status != dbKITcontact::status_active) {
      // Der Kontakt ist NICHT AKTIV!
      $this->setMessage($this->lang->translate('<p>The account for the email address <b>{{ email }}</b> is not active, please contact the service!</p>', array(
          'email' => $_REQUEST[kitContactInterface::kit_email])));
      return $this->showForm();
    }
    // CAPTCHA pruefen?
    if ($form_data[dbKITform::field_captcha] == dbKITform::captcha_on) {
      unset($_SESSION['kf_captcha']);
      if (!isset($_REQUEST['captcha']) || ($_REQUEST['captcha'] != $_SESSION['captcha'])) {
        $this->setMessage($this->lang->translate('<p>The CAPTCHA code is not correct, please try again!</p>'));
        return $this->showForm();
      }
    }

    // neues Passwort anfordern
    $newPassword = '';
    if (!$kitContactInterface->generateNewPassword($_REQUEST[kitContactInterface::kit_email], $newPassword)) {
      if ($kitContactInterface->isError()) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
        return false;
      }
      $this->setMessage($kitContactInterface->getMessage());
      return $this->showForm();
    }
    $contact = array();
    if (!$kitContactInterface->getContact($contact_id, $contact)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
      return false;
    }

    $form_data['subject'] = $form_data[dbKITform::field_title];

    $data = array(
        'contact' => $contact,
        'password' => $newPassword,
        'form' => $form_data);

    $provider_data = array();
    if (!$kitContactInterface->getServiceProviderByID($form_data[dbKITform::field_provider_id], $provider_data)) {
      if ($kitContactInterface->isError()) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
      }
      else {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage()));
      }
      return false;
    }
    $provider_email = $provider_data['email'];
    $provider_name = $provider_data['name'];

    $client_mail = $this->getTemplate('mail.client.password.htt', $data);
    if ($form_data[dbKITform::field_email_html] == dbKITform::html_off)
      $client_mail = strip_tags($client_mail);
    $client_subject = strip_tags($this->getTemplate('mail.client.subject.htt', $data));

    $mail = new kitMail($form_data[dbKITform::field_provider_id]);
    if (!$mail->mail($client_subject, $client_mail, $provider_email, $provider_name, array(
        $contact[kitContactInterface::kit_email] => $contact[kitContactInterface::kit_email]), ($form_data[dbKITform::field_email_html] == dbKITform::html_on) ? true : false)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
          'email' => $contact[kitContactInterface::kit_email]))));
      return false;
    }

    return $this->getTemplate('confirm.password.htt', $data);
  } // sendNewPassword()

  /**
   * Registriert ein Benutzerkonto und versendet einen Aktivierungslink
   *
   * @param $form_data array
   *            - Formulardaten
   * @param $contact_data array
   *            - Kontaktdaten
   */
  protected function registerAccount($form_data = array(), $contact_data = array()) {
    global $kitContactInterface;

    $contact_id = -1;
    $status = dbKITcontact::status_active;
    if ($kitContactInterface->isEMailRegistered($contact_data[kitContactInterface::kit_email], $contact_id, $status)) {
      // diese E-Mail Adresse ist bereits registriert
      if ($status == dbKITcontact::status_active) {
        // Kontakt ist aktiv
        $this->setMessage($this->lang->translate('<p>The email address <b>{{ email }}</b> is already registered, please login with your user data!</p>', array(
            'email' => $contact_data[kitContactInterface::kit_email])));
        return $this->showForm();
      }
      else {
        // Kontakt ist gesperrt
        $this->setMessage($this->lang->translate('<p>The account for the email address <b>{{ email }}</b> is locked. Please contact the service!</p>', array(
            'email' => $contact_data[kitContactInterface::kit_email])));
        return $this->showForm();
      }
    }
    elseif ($kitContactInterface->isError()) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
      return false;
    }

    // alles ok - neuen Datensatz anlegen
    $register_data = array();
    if (!$kitContactInterface->addContact($contact_data, $contact_id, $register_data)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
      return false;
    }
    $form_data['datetime'] = date(cfg_datetime_str);
    $form_data['activation_link'] = sprintf('%s%s%s', $this->page_link, (strpos($this->page_link, '?') === false) ? '?' : '&', http_build_query(array(
        self::request_action => self::action_activation_key,
        self::request_key => $register_data[dbKITregister::field_register_key],
        self::request_provider_id => $form_data[dbKITform::field_provider_id],
        self::request_activation_type => self::activation_type_account)));
    $form_data['subject'] = $form_data[dbKITform::field_title];
    // Benachrichtigungen versenden

    $provider_data = array();
    if (!$kitContactInterface->getServiceProviderByID($form_data[dbKITform::field_provider_id], $provider_data)) {
      if ($kitContactInterface->isError()) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
      }
      else {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage()));
      }
      return false;
    }
    $provider_email = $provider_data['email'];
    $provider_name = $provider_data['name'];
    $relaying = (bool) $provider_data['relaying'];

    $data = array('contact' => $contact_data, 'form' => $form_data);

    $client_mail = $this->getTemplate('mail.client.register.htt', $data);
    if ($form_data[dbKITform::field_email_html] == dbKITform::html_off)
      $client_mail = strip_tags($client_mail);
    $client_subject = strip_tags($this->getTemplate('mail.client.subject.htt', $data));

    $mail = new kitMail($form_data[dbKITform::field_provider_id]);
    if (!$mail->mail($client_subject, $client_mail, $provider_email, $provider_name, array(
        $contact_data[kitContactInterface::kit_email] => $contact_data[kitContactInterface::kit_email]), ($form_data[dbKITform::field_email_html] == dbKITform::html_on) ? true : false)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
          'email' => $contact_data[kitContactInterface::kit_email]))));
      return false;
    }

    $provider_subject = $this->getTemplate('mail.provider.subject.htt', $data);
    $provider_mail = $this->getTemplate('mail.provider.register.htt', $data);
    if ($form_data[dbKITform::field_email_html] == dbKITform::html_off)
      $provider_mail = strip_tags($provider_mail);

    $cc_array = array();
    $ccs = explode(',', $form_data[dbKITform::field_email_cc]);
    foreach ($ccs as $cc)
      if (!empty($cc))
        $cc_array[$cc] = $cc;

    $mail = new kitMail($form_data[dbKITform::field_provider_id]);
    if (!$relaying) {
      $mail->AddReplyTo($contact_data[kitContactInterface::kit_email]);
      $from_name = $contact_data[kitContactInterface::kit_email];
      $from_email = $provider_email;
    }
    else {
      $from_name = $contact_data[kitContactInterface::kit_email];
      $from_email = $contact_data[kitContactInterface::kit_email];
    }
    if (!$mail->mail($provider_subject, $provider_mail, $from_email, $from_name, array(
        $provider_email => $provider_name), ($form_data[dbKITform::field_email_html] == dbKITform::html_on) ? true : false, $cc_array)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
          'email' => SERVER_EMAIL))));
      return false;
    }

    return $this->getTemplate('confirm.register.htt', $data);
  } // registerAccount()

  /**
   * Check the authentication of a LEPTON user
   *
   * @param $username string
   * @param $password string
   */
  protected function authenticate_wb_user($username, $password) {
    global $database;
    global $wb;
    $query = sprintf("SELECT * FROM %susers WHERE username='%s' AND password='%s' AND active = '1'", self::$table_prefix, $username, $password);
    $results = $database->query($query);
    if ($database->is_error()) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
      return false;
    }
    $results_array = $results->fetchRow();
    $num_rows = $results->numRows();
    if ($num_rows) {
      $user_id = $results_array['user_id'];
      $this->user_id = $user_id;
      $_SESSION['USER_ID'] = $user_id;
      $_SESSION['GROUP_ID'] = $results_array['group_id'];
      $_SESSION['GROUPS_ID'] = $results_array['groups_id'];
      $_SESSION['USERNAME'] = $results_array['username'];
      $_SESSION['DISPLAY_NAME'] = $results_array['display_name'];
      $_SESSION['EMAIL'] = $results_array['email'];
      $_SESSION['HOME_FOLDER'] = $results_array['home_folder'];
      /*
       * // Run remember function if needed if($this->remember == true) {
       * $this->remember($this->user_id); }
       */
      // Set language
      if ($results_array['language'] != '') {
        $_SESSION['LANGUAGE'] = $results_array['language'];
      }
      // Set timezone
      if ($results_array['timezone'] != '-72000') {
        $_SESSION['TIMEZONE'] = $results_array['timezone'];
      }
      else {
        // Set a session var so apps can tell user is using default tz
        $_SESSION['USE_DEFAULT_TIMEZONE'] = true;
      }
      // Set date format
      if ($results_array['date_format'] != '') {
        $_SESSION['DATE_FORMAT'] = $results_array['date_format'];
      }
      else {
        // Set a session var so apps can tell user is using default date
        // format
        $_SESSION['USE_DEFAULT_DATE_FORMAT'] = true;
      }
      // Set time format
      if ($results_array['time_format'] != '') {
        $_SESSION['TIME_FORMAT'] = $results_array['time_format'];
      }
      else {
        // Set a session var so apps can tell user is using default time
        // format
        $_SESSION['USE_DEFAULT_TIME_FORMAT'] = true;
      }
      $_SESSION['SYSTEM_PERMISSIONS'] = array();
      $_SESSION['MODULE_PERMISSIONS'] = array();
      $_SESSION['TEMPLATE_PERMISSIONS'] = array();
      $_SESSION['GROUP_NAME'] = array();

      $first_group = true;
      foreach (explode(",", $wb->get_session('GROUPS_ID')) as $cur_group_id) {
        $query = sprintf("SELECT * FROM %sgroups WHERE group_id='%s'", TABLE_PREFIX, $cur_group_id);
        $results = $database->query($query);
        $results_array = $results->fetchRow();
        $_SESSION['GROUP_NAME'][$cur_group_id] = $results_array['name'];
        // Set system permissions
        if ($results_array['system_permissions'] != '') {
          $_SESSION['SYSTEM_PERMISSIONS'] = array_merge($_SESSION['SYSTEM_PERMISSIONS'], explode(',', $results_array['system_permissions']));
        }
        // Set module permissions
        if ($results_array['module_permissions'] != '') {
          if ($first_group) {
            $_SESSION['MODULE_PERMISSIONS'] = explode(',', $results_array['module_permissions']);
          }
          else {
            $_SESSION['MODULE_PERMISSIONS'] = array_intersect($_SESSION['MODULE_PERMISSIONS'], explode(',', $results_array['module_permissions']));
          }
        }
        // Set template permissions
        if ($results_array['template_permissions'] != '') {
          if ($first_group) {
            $_SESSION['TEMPLATE_PERMISSIONS'] = explode(',', $results_array['template_permissions']);
          }
          else {
            $_SESSION['TEMPLATE_PERMISSIONS'] = array_intersect($_SESSION['TEMPLATE_PERMISSIONS'], explode(',', $results_array['template_permissions']));
          }
        }
        $first_group = false;
      }
      // Update the users table with current ip and timestamp
      $get_ts = time();
      $get_ip = $_SERVER['REMOTE_ADDR'];
      $query = sprintf("UPDATE %susers SET login_when= '%s', login_ip='%s' WHERE user_id='%s'", TABLE_PREFIX, $get_ts, $get_ip, $user_id);
      $database->query($query);
    }
    // Return if the user exists or not
    return $num_rows;
  } // authenticate_wb_user

  /**
   * Aktivierungskey ueberpruefen, Datensatz freischalten und Benutzer
   * einloggen...
   *
   * @return string Dialog
   *         @intern Diese Routine nutzt ein statisches SUBJECT im Gegensatz
   *         zu allen anderen E-Mail Routinen
   */
  protected function checkActivationKey() {
    global $kitContactInterface;
    global $dbKITform;

    if (!isset($_REQUEST[self::request_key])) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Missing the datafield <b>{{ field }}</b>!', array(
          'field' => self::request_key))));
      return false;
    }

    $register = array();
    $contact = array();
    $password = '';
    if (!$kitContactInterface->checkActivationKey($_REQUEST[self::request_key], $register, $contact, $password)) {
      if ($this->isError()) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
        return false;
      }
      $this->setMessage($kitContactInterface->getMessage());
      return $this->showForm();
    }
    // Benutzer anmelden
    $_SESSION[kitContactInterface::session_kit_register_id] = $register[dbKITregister::field_id];
    $_SESSION[kitContactInterface::session_kit_key] = $register[dbKITregister::field_register_key];
    $_SESSION[kitContactInterface::session_kit_contact_id] = $register[dbKITregister::field_contact_id];

    // if auto_login_wb
    if ($this->params[self::PARAM_AUTO_LOGIN_LEPTON]) {
      if (!$this->authenticate_wb_user($register[dbKITregister::field_email], $register[dbKITregister::field_password])) {
        $error = $this->isError() ? $this->getError() : $this->lang->translate('<p>Unspecified error, no description available.</p>');
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $error));
        return false;
      }
    }

    // Passwort pruefen
    if ($password == -1) {
      // Benutzer war bereits freigeschaltet und das Konto ist aktiv
      $this->setMessage($this->lang->translate('<p>Welcome!<br />we have send you the username and password by email.</p>'));
      return $this->showForm();
    }
    $newsletter_account_info = $kitContactInterface->getConfigurationValue(dbKITcfg::cfgNewsletterAccountInfo);
    $data = array('contact' => $contact, 'password' => $password, 'newsletter_account_info' => (int) $newsletter_account_info);

    $activation_type = (isset($_REQUEST[self::request_activation_type])) ? $_REQUEST[self::request_activation_type] : self::activation_type_account;

    switch ($activation_type) :
      case self::activation_type_newsletter:
        $mail_template = 'mail.client.activation.newsletter.htt';
        $prompt_template = 'confirm.activation.newsletter.htt';
        break;
      case self::activation_type_account:
      default:
        $mail_template = 'mail.client.activation.account.htt';
        $prompt_template = 'confirm.activation.account.htt';
        break;
    endswitch;

    if (($activation_type == self::activation_type_account) ||
        (($activation_type == self::activation_type_newsletter) && $newsletter_account_info)) {
      $client_mail = strip_tags($this->getTemplate($mail_template, $data));
      $provider_id = (isset($_REQUEST[self::request_provider_id])) ? $_REQUEST[self::request_provider_id] : -1;

      $provider_data = array();
      if (!$kitContactInterface->getServiceProviderByID($provider_id, $provider_data)) {
        if ($kitContactInterface->isError()) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
        }
        else {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage()));
        }
        return false;
      }
      $provider_email = $provider_data['email'];
      $provider_name = $provider_data['name'];

      // Standard E-Mail Routine verwenden
      $mail = new kitMail($provider_id);
      if (!$mail->mail($this->lang->translate('Your account data'), $client_mail, $provider_email, $provider_name, array(
          $contact[kitContactInterface::kit_email] => $contact[kitContactInterface::kit_email]), false)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
            'email' => $contact[kitContactInterface::kit_email]))));
        return false;
      }
    }
    return $this->getTemplate($prompt_template, $data);
  } // checkActivationKey()

  /**
   * Logout
   *
   * @return string Logout dialog
   */
  protected function Logout() {
    global $kitContactInterface;

    $contact = array();
    if (!$kitContactInterface->getContact($_SESSION[kitContactInterface::session_kit_contact_id], $contact)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
      return false;
    }
    $data = array('contact' => $contact);
    $kitContactInterface->logout();
    return $this->getTemplate('confirm.logout.htt', $data);
  } // Logout()

  /**
   * Subscribe a user to the newsletter
   *
   * @param $form_data array
   * @return string confirmation dialog on success or boolean false on error
   */
  protected function subscribeNewsletter($form_data = array()) {
    global $kitContactInterface;
    global $dbContactArrayCfg;

    $use_subscribe = false;
    $subscribe = false;
    // pruefen ob kit_newsletter_subscribe verwendet wird
    if (isset($_REQUEST[kitContactInterface::kit_newsletter_subscribe])) {
      $use_subscribe = true;
      if (is_bool($_REQUEST[kitContactInterface::kit_newsletter_subscribe])) {
        $subscribe = $_REQUEST[kitContactInterface::kit_newsletter_subscribe];
      }
      elseif (is_numeric($_REQUEST[kitContactInterface::kit_newsletter_subscribe])) {
        $subscribe = ($_REQUEST[kitContactInterface::kit_newsletter_subscribe] == 1) ? true : false;
      }
      else {
        $subscribe = (strtolower($_REQUEST[kitContactInterface::kit_newsletter_subscribe]) == 'true') ? true : false;
      }
    }

    $newsletter = '';
    if (isset($_REQUEST[kitContactInterface::kit_newsletter]) && is_array($_REQUEST[kitContactInterface::kit_newsletter])) {
      $newsletter = implode(',', $_REQUEST[kitContactInterface::kit_newsletter]);
    }
    elseif (isset($_REQUEST[kitContactInterface::kit_newsletter])) {
      $newsletter = $_REQUEST[kitContactInterface::kit_newsletter];
    }
    $email = $_REQUEST[kitContactInterface::kit_email];

    $register = array();
    $contact = array();
    $send_activation = false;
    if (!$kitContactInterface->subscribeNewsletter($email, $newsletter, $subscribe, $use_subscribe, $register, $contact, $send_activation)) {
      if ($kitContactInterface->isError()) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
        return false;
      }
      $this->setMessage($kitContactInterface->getMessage());
      return $this->showForm();
    }
    $message = $kitContactInterface->getMessage();

    // special: check contact language
    if (isset($_REQUEST[kitContactInterface::kit_contact_language]) && ($_REQUEST[kitContactInterface::kit_contact_language] !== strtolower(LANGUAGE))) {
      $update = array();
      $update[kitContactInterface::kit_contact_language] = strtolower(LANGUAGE);
      if (!$kitContactInterface->updateContact($register[dbKITregister::field_contact_id], $update)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
        return false;
      }
    }

    if ($send_activation == false) {
      $message .= sprintf($this->lang->translate('<p>The newsletter abonnement for the email address <b>{{ email }}</b> was updated.</p>', array(
          'email' => $email)));
      $this->setMessage($message);
      $data = array('message' => $this->getMessage());
      return $this->getTemplate('prompt.htt', $data);
    }
    else {
      // Aktivierungskey versenden
      $form = array(
          'activation_link' => sprintf('%s%s%s', $this->page_link, (strpos($this->page_link, '?') === false) ? '?' : '&', http_build_query(array(
              self::request_action => self::action_activation_key,
              self::request_key => $register[dbKITregister::field_register_key],
              self::request_provider_id => $form_data[dbKITform::field_provider_id],
              self::request_activation_type => self::activation_type_newsletter))),
          'datetime' => date(cfg_datetime_str),
          'subject' => $form_data[dbKITform::field_title]);
      $newsletter_array = array();
      $na = explode(',', $newsletter);
      foreach ($na as $nl) {
        $SQL = sprintf("SELECT %s FROM %s WHERE %s='%s'",
            dbKITcontactArrayCfg::field_value,
            $dbContactArrayCfg->getTableName(),
            dbKITcontactArrayCfg::field_identifier,
            $nl);
        $result = array();
        if (!$dbContactArrayCfg->sqlExec($SQL, $result)) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $dbContactArrayCfg->getError()));
          return false;
        }
        if (count($result) > 0) {
          $newsletter_array[] = array(
              'name' => $nl,
              'value' => $result[0][dbKITcontactArrayCfg::field_value]
              );
        }
      }

      $data = array('form' => $form, 'contact' => $contact, 'newsletter' => $newsletter_array);
      $provider_data = array();
      if (!$kitContactInterface->getServiceProviderByID($form_data[dbKITform::field_provider_id], $provider_data)) {
        if ($kitContactInterface->isError()) {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getError()));
        }
        else {
          $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $kitContactInterface->getMessage()));
        }
        return false;
      }
      $provider_email = $provider_data['email'];
      $provider_name = $provider_data['name'];
      $relaying = (bool) $provider_data['relaying'];

      $client_mail = $this->getTemplate('mail.client.register.newsletter.htt', $data);
      if ($form_data[dbKITform::field_email_html] == dbKITform::html_off)
        $client_mail = strip_tags($client_mail);
      $client_subject = strip_tags($this->getTemplate('mail.client.subject.htt', $data));

      $mail = new kitMail($form_data[dbKITform::field_provider_id]);
      if (!$mail->mail($client_subject, $client_mail, $provider_email, $provider_name, array(
          $contact[kitContactInterface::kit_email] => $contact[kitContactInterface::kit_email]), ($form_data[dbKITform::field_email_html] == dbKITform::html_on) ? true : false)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
            'email' => $contact[kitContactInterface::kit_email]))));
        return false;
      }

      $provider_mail = $this->getTemplate('mail.provider.register.newsletter.htt', $data);
      if ($form_data[dbKITform::field_email_html] == dbKITform::html_off)
        $provider_mail = strip_tags($provider_mail);
      $provider_subject = strip_tags($this->getTemplate('mail.provider.subject.htt', $data));

      $cc_array = array();
      $ccs = explode(',', $form_data[dbKITform::field_email_cc]);
      foreach ($ccs as $cc)
        if (!empty($cc))
          $cc_array[$cc] = $cc;

      $mail = new kitMail($form_data[dbKITform::field_provider_id]);
      if (!$relaying) {
        $mail->AddReplyTo($contact[kitContactInterface::kit_email]);
        $from_name = $contact[kitContactInterface::kit_email];
        $from_email = $provider_email;
      }
      else {
        $from_name = $contact[kitContactInterface::kit_email];
        $from_email = $contact[kitContactInterface::kit_email];
      }
      if (!$mail->mail($provider_subject, $provider_mail, $from_email, $from_name, array(
          $provider_email => $provider_name), ($form_data[dbKITform::field_email_html] == dbKITform::html_on) ? true : false, $cc_array)) {
        $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Can\'t send the email to <b>{{ email }}</b>!', array(
            'email' => SERVER_EMAIL))));
        return false;
      }

      return $this->getTemplate('confirm.register.newsletter.htt', $data);
    }

  } // subscribeNewsletter()

  /**
   * Create .
   * htaccess protection
   *
   * @return boolean
   */
  protected function createProtection() {
    global $kitLibrary;
    $protection_path = WB_PATH.MEDIA_DIRECTORY.DIRECTORY_SEPARATOR.self::PROTECTION_FOLDER.DIRECTORY_SEPARATOR;
    $data = sprintf("# .htaccess generated by kitForm\nAuthUserFile %s\nAuthGroupFile /dev/null"."\nAuthName \"KIT - Protected Media Directory\"\nAuthType Basic\n<Limit GET>\n"."require valid-user\n</Limit>", $protection_path.'.htpasswd');
    if (false === file_put_contents($protection_path.'.htaccess', $data)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Can\'t create the .htaccess file!')));
      return false;
    }
    $data = sprintf("# .htpasswd generated by kitForm\nkit_protector:%s", crypt($kitLibrary->generatePassword()));
    if (false === file_put_contents($protection_path.'.htpasswd', $data)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Can\'t create the .htpasswd file!')));
      return false;
    }
    return true;
  } // createProtection()

} // class formFrontend

?>
