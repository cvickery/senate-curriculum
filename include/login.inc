<?php
// include/login.inc

//  Constants used by the login module.
//  -------------------------------------------------------------------------------------

//  Forms
define('login_which_department',        'login-which-department');
define('login_form',                    'login-form');

//  login-which-departments fields
define('dept_name',                     'dept-name');

//  login-form fields
define('qc_email',                      'qc-email');
define('password',                      'password');
define('new_password',                  'new_password');
define('repeat_new',                    'repeat_new');

//  Error Handling
//  -------------------------------------------------------------------------------------
//  When restricted area redirects to site index
define('login_error_msg',               'login-error-msg'); // $_SESSION index
define('access_denied',                 'Access denied');

//  When a page requires user to be logged in
define('login_required',                'Signin required');

//  Error messages within login module
define('blank_email',                   'Missing email address');
define('bad_email',                     'Unrecognized email address');
define('bad_pass',                      'Unrecognized email/password');
define('no_dept',                       'Unknown email or department affiliation');
define('new_repeat_mismatch',           'New and repeated passwords don’t match');
define('password_changed',              'Password changed');

//  $_SESSION keys
//  -------------------------------------------------------------------------------------
define('saved_referer_uri',             'SAVED_REFERER_URI');


//  require_login()
//  -------------------------------------------------------------------------------------
/*  Save current location and redirect to /signin.php if user is not logged in.
 */
  function require_login()
  {
    if ( !isset($person) )
    {
      if ( isset($_SERVER['HTTP_REFERER']) )
      {
        $_SESSION[saved_referer_uri] = $_SERVER['HTTP_REFERER'];
      }
      $_SESSION[login_error_msg] = login_required;
      header("Location: $site_home_uri/signin.php");
      exit;
    }
  }

//  login_status()
//  -------------------------------------------------------------------------------------
/*  Generate and return the status-msg div, initialized with user’s current login status.
 */
  function login_status()
  {
    global $person;
    $returnVal = "<div id='status-msg'>";
    if ( isset($person) )
    {
      $returnVal .= <<<EOD
    <form id='logout-form' action='.' method='post'>
      <input type='hidden' name='form-name' value='logout' />
      <button type='submit'>Sign Out</button>
    </form>

EOD;
      $returnVal .= "<div>$person<div>" . $person->last_login_info() . "</div></div>";
    }
    else
    {
      $returnVal .= "<a id='signin-link' href='./signin.php'>Sign In</a>";
    }
    return $returnVal . "</div>\n";;
  }

