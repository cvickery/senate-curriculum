<?php
//  .../[test_]Curriculum/scripts/init_session.php

/*  Common setup for all pages:
 *    set_include_path(get_include_path() 
 *      . PATH_SEPARATOR . [path_to_this_dir]
 *      . PATH_SEPARATOR . [ path_path_to_include_dir ]);
 *    require this
 *    ...
 *
 *  Postconditions: global variables set
 *
 *    $site_home_url will be a string suitable for redirecting to the site home directory,
 *    namely one of the following:
 *      https://senate.qc.cuny.edu/Curriculum
 *      https://senate.qc.cuny.edu/test_Curriculum
 *      http://localhost/senate.qc.cuny.edu/test_Curriculum
 *
 *    $curric_db will be a resource to the curric or test_curric database.
 *
 *    $dump_if_testing will be either an empty string or an HTML comment string with
 *    debugging information.
 *
 *    $form_name will be the name of the form being submitted, if any
 *
 *    $person will be unserialized copy of $_SESSION['person'] iff person is logged in.
 */
  //  Default $site_home_url components
  $http_protocol  = 'https';
  $http_host      = 'senate.qc.cuny.edu';
  $home_dir       = 'Curriculum';

  //  Force HTTPS connection if not already in place and not coming from localhost
  if ( !isset($_SERVER['HTTPS']))
  {
    if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'localhost')
    {
      $http_protocol  = 'http';  // Exception for off-site development
      $http_host      = 'localhost/senate.qc.cuny.edu';
    }
    else
    {
      //  Force https connection
      header("Location: https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}");
      exit;
    }
  }

//  Modules used by all pages
//  -------------------------------------------------------------------------------------
  date_default_timezone_set('America/New_York');
  session_start();
  require_once('credentials.inc');
  require_once('nav_functions.php');
  require_once('atoms.inc');
  require_once('assertions.php');
  require_once('classes.php');
  require_once('utils.php');

//  When in a testing directory
//  -------------------------------------------------------------------------------------
/*  If the current directory includes the string 'test', enable logging, and dump
 *  _SESSION and _POST arrays into a comment string that can be displayed in the page
 *  body.
 *
 *  The logging functions require debug.log to be in the same directory and writeable by
 *  the web server. (touch debug.log; sudo chown _www $_)
 *    log_msg(msg)      writes a timestamped message
 *    log_var(msg, var) messge plus var_dump(var)
 *
 *  Note: if $dump_if_testing causes problems when the comment is echoed, it means you
 *  didn't sanitize user input before saving it in _SESSION. For example, if a
 *  justification contains '--' it will break the html comment syntax.
 */
  $dump_if_testing = '';
  if (strstr($_SERVER['REQUEST_URI'], 'test'))
  {
    $home_dir = 'test_Curriculum';
    require_once('logging.php');
    ob_start();
    echo "\n<!--\nSESSION\n";
    var_dump($_SESSION);
    if (isset($_POST))
    {
      echo "POST\n";
      var_dump($_POST);
    }
    echo "\n-->\n";
    $dump_if_testing = ob_get_contents();
    ob_end_clean();
  }

  //  Final $site_home_url
  $site_home_url = "$http_protocol://$http_host/$home_dir";
  //  Global form_name: which form, if any, was submitted.
  $form_name = '';
  if ( isset($_POST[form_name])) $form_name = sanitize($_POST[form_name]);

  //  Handle the logout form if it was submitted.
  //  -----------------------------------------------------------------------------------
  if ($form_name === 'logout')
  {
    //  Clear all session variables
    $keys = array_keys($_SESSION);
    foreach ($keys as $key)
    {
      unset($_SESSION[$key]);
    }
    //  And redirect to site index page
    header("Location: $site_home_url");
    exit;
  }

  //  Set login state
  //  ----------------------------------------------------------------------------------
  $person         = '';
  $pending_person = '';
  if (isset($_SESSION['person']))
  {
    $person = unserialize($_SESSION['person']);
    unset($pending_person);
  }
  else if (isset($_SESSION['pending_person']))
  {
    $pending_person = unserialize($_SESSION['pending_person']);
    unset($person);
  }
  else
  {
    unset($person);
    unset($pending_person);
  }
?>
