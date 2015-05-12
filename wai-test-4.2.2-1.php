#!/usr/bin/php
<?php
/** _    _          ___           __ _     (R)
 * | |  (_)_ _____ / __|___ _ _  / _(_)__ _
 * | |__| \ V / -_) (__/ _ \ ' \|  _| / _` |
 * |____|_|\_/\___|\___\___/_||_|_| |_\__, |
 *                                    |___/
 * LiveConfig Web Application Installer (LC WAI)
 * Web-App-Name: WordPress
 * Web-App-Version: 4.2.2
 * @author Christoph Russow
 * @copyright Copyright (c) 2009-2015 Keppler IT GmbH.
 * @version 1.0
 * --------------------------------------------------------------------------
 */

define('WAI_INCLUDE', 'yes');
require_once("installer.inc.php");
if (!version_compare(WAI_API_VERSION, "1.0.1", ">=")) { die("ERROR Wrong API version in installer library - please update your LiveConfig installation!\n"); }
$installer = new Installer();

/*
 * Configuration starts here
 */

/* Files to download */
$LCWAI_DOWNLOADS['ALL'] = array( // Downloads for ALL languages
);
$LCWAI_DOWNLOADS['de'] = array( // Downloads for 'de' (german) language
  'PACKAGE' => array('NAME' => 'wordpress-4.2.2-de_DE.tar.gz',
                     'SHA1' => '917981f56ab86b8448422ec0bcf20de9dcd5974f',
                     'URL'  => 'http://de.wordpress.org/wordpress-4.2.2-de_DE.tar.gz'),
);
$LCWAI_DOWNLOADS['en'] = array( // Downloads for 'en' (english) language
  'PACKAGE' => array('NAME' => 'wordpress-4.2.2.tar.gz',
                     'SHA1' => 'd3a70d0f116e6afea5b850f793a81a97d2115039',
                     'URL'  => 'http://www.wordpress.org/wordpress-4.2.2.tar.gz'),
);

/* Variables Liveconfig has to ask the user */
$LCWAI_USER_VARS = array();

/*
$LCWAI_USER_VARS[0] = array(
  'name' => 'LC_TABLE_PREFIX',
  'type' => 'text',
  'regex' => '^[A-Za-z][A-Za-z0-9]{0,3}_$',
  'displaytext' => array(
    'de' => 'Tabellenpräfix',
    'en' => 'Table prefix'
  ),
  'description' => array(
    'de' => 'Einen Tabellenpräfix vergeben oder den zufällig generierten belassen. Idealerweise besteht dieser aus 3 bis 4 Zeichen, enthält nur alphanumerische Zeichen und MUSS mit einem Unterstrich enden.',
    'en' => 'Choose a table prefix or use the randomly generated. Should be three or four characters long, must contain only alphanumeric characters, and MUST end with an underscore.'
  ),
  'defaultvalue' => array(
    'de' => $installer->get_random_str(3).'_',
    'en' => $installer->get_random_str(3).'_',
  ),
);
*/

/*
 * Program logic
 *   starts here
 */

switch($argv[1]) {
  case 'getvars':
    $installer->wai_getvars($LCWAI_USER_VARS);
    break;
  case 'install':
    wai_install();
    break;
  case 'uninstall':
    wai_uninstall();
    break;
  case 'update':
    $installer->wai_update();
    break;
  case 'upgrade':
    $installer->wai_upgrade();
    break;
  case 'download':
    $installer->wai_download($LCWAI_DOWNLOADS);
    break;
  case 'getversion';
    wai_getversion();
    break;
  default:
    print "Usage ".$argv[0]." getvars|install|uninstall|update|upgrade|download|getversion\n";
    exit;
}

exit;

/*
 * Definition of package specific functions
 *  starts here
 */

/*
 * wai_install()
 *   installs the package
 */
function wai_install() {
  global $LCWAI_DOWNLOADS;
  global $installer;
  //entpacken
  //installations vorgang durchführen
  if(($vars = $installer->get_env_vars(array('LC_DST', 'LC_SRC', 'LC_LANG', 'LC_MYSQL_DB', 'LC_MYSQL_USER', 'LC_MYSQL_PW', 'LC_MYSQL_HOST', 'LC_RUN_AS_USER'), array('MYSQL_PORT'))) === false) {
    return;
  }

  if(!is_dir($vars['LC_DST'])) {
    print "ERROR destination '" . $vars['LC_DST'] . "' is not a directory\n";
    return;
  }

  $lang = $vars['LC_LANG'];
  if (!isset($LCWAI_DOWNLOADS[$lang]) || !isset($LCWAI_DOWNLOADS[$lang]['PACKAGE'])) {
    $lang = 'en';
  }

  if($installer->package_extract($vars['LC_DST'], $vars['LC_SRC'].'/'.$LCWAI_DOWNLOADS[$lang]['PACKAGE']['NAME']) === false) {
    return;
  }

  if($installer->move($vars['LC_DST']."/wordpress/*", $vars['LC_DST']."/") === false) {
    return;
  }

  //create settings file
  $cfg_src_file = $vars['LC_DST'].'/wp-config-sample.php';
  $cfg_dst_file = $vars['LC_DST'].'/wp-config.php';

  if(($cfg_content = file_get_contents($cfg_src_file)) === false) {
    print "ERROR failed to read configfile template\n";
    return;
  }

  $mysql_db     = "define('DB_NAME', '".$vars['LC_MYSQL_DB']."');";
  $mysql_user   = "define('DB_USER', '".$vars['LC_MYSQL_USER']."');";
  $mysql_pw     = "define('DB_PASSWORD', '".$vars['LC_MYSQL_PW']."');";
  $mysql_host   = "define('DB_HOST', '".$vars['LC_MYSQL_HOST']."');";
  $mysql_prefix = "\$table_prefix  = 'wp_';";

  $cfg_content = str_replace("define('DB_NAME', 'database_name_here');" , $mysql_db     , $cfg_content);
  $cfg_content = str_replace("define('DB_USER', 'username_here');"      , $mysql_user   , $cfg_content);
  $cfg_content = str_replace("define('DB_PASSWORD', 'password_here');"  , $mysql_pw     , $cfg_content);
  $cfg_content = str_replace("define('DB_HOST', 'localhost');"          , $mysql_host   , $cfg_content);
  $cfg_content = str_replace("\$table_prefix  = 'wp_';"                 , $mysql_prefix , $cfg_content);

  $auth_key         = "define('AUTH_KEY', '".$installer->get_random_str(60, true)."');";
  $secure_aut_key   = "define('SECURE_AUTH_KEY', '".$installer->get_random_str(60, true)."');";
  $logged_in_key    = "define('LOGGED_IN_KEY', '".$installer->get_random_str(60, true)."');";
  $nonce_key        = "define('NONCE_KEY', '".$installer->get_random_str(60, true)."');";
  $aut_salt         = "define('AUTH_SALT', '".$installer->get_random_str(60, true)."');";
  $secure_aut_salt  = "define('SECURE_AUTH_SALT', '".$installer->get_random_str(60, true)."');";
  $logged_in_salt   = "define('LOGGED_IN_SALT', '".$installer->get_random_str(60, true)."');";
  $nonce_salt       = "define('NONCE_SALT', '".$installer->get_random_str(60, true)."');";

  $cfg_content = str_replace("define('AUTH_KEY',         'put your unique phrase here');" , $auth_key , $cfg_content);
  $cfg_content = str_replace("define('SECURE_AUTH_KEY',  'put your unique phrase here');" , $secure_aut_key , $cfg_content);
  $cfg_content = str_replace("define('LOGGED_IN_KEY',    'put your unique phrase here');" , $logged_in_key , $cfg_content);
  $cfg_content = str_replace("define('NONCE_KEY',        'put your unique phrase here');" , $nonce_key , $cfg_content);
  $cfg_content = str_replace("define('AUTH_SALT',        'put your unique phrase here');" , $aut_salt , $cfg_content);
  $cfg_content = str_replace("define('SECURE_AUTH_SALT', 'put your unique phrase here');" , $secure_aut_salt , $cfg_content);
  $cfg_content = str_replace("define('LOGGED_IN_SALT',   'put your unique phrase here');" , $logged_in_salt , $cfg_content);
  $cfg_content = str_replace("define('NONCE_SALT',       'put your unique phrase here');" , $nonce_salt , $cfg_content);

  if(file_put_contents($cfg_dst_file, $cfg_content) === false) {
    print "ERROR failed to write config file\n";
    return;
  }

  if($vars['LC_RUN_AS_USER'] == "no") {
    //ToDo: chmod files directory o+w
    if($installer->chmod($vars['LC_DST'].'/wp-content', 0666, 0777, true) === false) {
      print "ERROR failed to chmod wp-content directory!\n";
      return;
    }
  }

  print "OK\n";

  //give liveconfig the url to present a link to the user where he/she can finish the installation
  print "forward_url\t/\n";
  print "admin_url\t/wp-admin/\n";
}

/*
 * wai_uninstall()
 *   deinstalls the package
 */
function wai_uninstall() {
  global $installer;
  if(($vars = $installer->get_env_vars(array('LC_DST'))) === false) {
    return;
  }

  if($installer->remove($vars['LC_DST']."/*") === false) {
    return;
  }

  print "OK\n";
}

/*
 * wai_getversion()
 *   returns the current installed package version
 */
function wai_getversion() {
  global $installer;

  if(($vars = $installer->get_env_vars(array('LC_DST'))) === false) {
    return;
  }

  if(!is_dir($vars['LC_DST'])) {
    print "ERROR destination '" . $vars['LC_DST'] . "' is not a directory\n";
    return;
  }

  include($vars['LC_DST']."/wp-includes/version.php");
  print "OK\n";
  print "version\t".$wp_version."\n";
}

?>