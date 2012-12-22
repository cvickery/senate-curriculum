<?php
//  .../Curriculum/scripts/init_session.php

/*  Common setup for all pages:
 *    set_include_path(get_include_path() . PATH_SEPARATOR . [path_to_this_dir] );
 *    require this
 *    ...
 */
  //  Force HTTPS connection if not already in place and not coming from localhost
  if ( !isset($_SERVER['HTTPS']) &&
       !(isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'localhost') )
  {
    header("Location: https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}");
    exit;
  }

//  Modules used by all pages
//  -------------------------------------------------------------------------------------
  date_default_timezone_set('America/New_York');
  session_start();
  require_once('credentials.inc');
  require_once('../include/atoms.inc');
	require_once('assertions.php');
  require_once('classes.php');
  require_once('utils.php');

//  When in a testing directory
//  -------------------------------------------------------------------------------------
/*  If the current directory includes the string 'testing', enable logging, and dump
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
  $home_dir = 'Curriculum';
  $dump_if_testing = '';
  if (strstr($_SERVER['REQUEST_URI'], 'testing'))
  {
    $home_dir = 'testing_Curriculum';
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

  //  Be sure session_state has a value
  if ( ! isset($_SESSION[session_state])) $_SESSION[session_state] = ss_not_logged_in;

  //  Which form, if any, was submitted.
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
    //  Set the session_state
    $_SESSION['session_state'] = ss_not_logged_in;
    //  And redirect to site index page
    header("Location: https://{$_SERVER['SERVER_NAME']}/$home_dir");
    exit;
  }

?>
