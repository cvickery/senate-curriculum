<?php
//  Curriculum/signin.php

set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() .  '/scripts'
    . PATH_SEPARATOR . getcwd() . '/include');
require_once('init_session1.php');
require_once('login.inc');

/*  There are three scenarios to deal with
 *    1.  Another page noted that the user is not logged in, and displayed a link to this
 *    one. In that case $_SESSION[person] is not set and $_SERVER[REFERER_URI] is set. We
 *    capture the referer uri in $_SESSION[saved_referer_uri] and include login.php, which
 *    will display the login form and reload this page when the user submits the form.
 *    2.  If $_SESSION[person] is set, it means the user is now logged in and we redirect
 *    to $_SESSION[saved_referer_uri].
 *    3.  The user bookmarked this page and comes here with $_SESSION[saved_referer_uri]
 *    not set. In that case we supply an explanation and things to click on, including a
 *    signout button.
 */

//  Save referer link, if available and not already saved
if ( !isset($_SESSION[saved_referer_uri]) && isset($_SERVER['HTTP_REFERER']))
{
  $_SESSION[saved_referer_uri] = $_SERVER['HTTP_REFERER'];
}

//  Generate the signin page
//  -------------------------------------------------------------------------------------
  $mime_type = "text/html";
  $html_attributes="lang=\"en\"";
  if ( array_key_exists("HTTP_ACCEPT", $_SERVER) &&
        (stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml") ||
         stristr($_SERVER["HTTP_ACCEPT"], "application/xml") )
       ||
       (array_key_exists("HTTP_USER_AGENT", $_SERVER) &&
        stristr($_SERVER["HTTP_USER_AGENT"], "W3C_Validator"))
     )
  {
    $mime_type = "application/xhtml+xml";
    $html_attributes = "xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\"";
    header("Content-type: $mime_type");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
  }
  else
  {
    header("Content-type: $mime_type; charset=utf-8");
  }
?>
<!DOCTYPE html>
<html <?php echo $html_attributes;?>>
  <head>
    <title>QC Curriculum Sign In</title>
    <link rel="stylesheet" type="text/css" href="css/curriculum.css" />
    <script type='text/javascript' src='js/jquery.min.js'></script>
    <script type='text/javascript' src='js/site_ui.js'></script>
  </head>
  <body>


<?php
  $nav_bar = site_nav();
  echo <<<EOD
    <div id='status-bar'>
      <button id='show-hide-instructions-button'>Hide Instructions</button>
      $nav_bar
    </div>
    <h1>Queens College Curriculum</h1>
    $dump_if_testing
EOD;
  require_once('login1.php');
  if (isset($person))
  {
    $return_option = '';
    if (! empty($_SESSION[saved_referer_uri]))
    {
      $referer_uri = $_SESSION[saved_referer_uri];
      unset($_SESSION[saved_referer_uri]);
      $return_option = <<<EOD
      <li>
        Return to <a href='$referer_uri'>$referer_uri</a>
      </li>

EOD;
    }
    if ( isset($login_error_message) && $login_error_message !== '')
    {
      assert('$login_error_message === password_changed');
      $password_message = "<div class='error'>Password Changed</div>\n";
    }
    else
    {
      $password_message = '';
    }
    echo <<<EOD
    <h2>You are signed in as $person</h2>
    $password_message
    <form action='.' method='post'>
      <input type='hidden' name='form-name' value='logout' />
      <p>
        You may:
      </p>
      <ul>
        $return_option
        <li>Go to one of the links at the top of this page</li>
        <li>Or: <button type='submit'>Sign Out</button></li>
      </ul>
    </form>

EOD;
  }
?>
  </body>
</html>
