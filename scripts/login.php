<?php

require_once('login.inc');

//  Manage Login (Signin) Process
//  ======================================================================================
/*    This module is included by pages that require login and by the generic signin page.
 *    Pages that do not require login simply do not include this file, and the user's
 *    login state is perserved in SESSION[person].
 *
 *  To log in, the user has to provide a QC email address, for which we look first in our
 *  local curric.people database table. If that fails, try the octsims.erp856 table, which
 *  may return multiple departmental affiliations. The user has to pick the appropriate
 *  one.
 *
 *  Users who login through the 856 table get added to the curric.people table for use
 *  during future logins.
 *
 *  2013-04-01: No way to bypass the password requirement for some pages and not others.
 *  the person session variable is set only if the user is completely logged in, and not
 *  otherwise. Uses pending_person form element for person whose department has not been
 *  resolved yet.
 */

//  Global Variables
//  -----------------------------------------------------------------------------------
//    (The global $person object is evaluated after form processing.)
if (! isset($login_error_message)) $login_error_message = '';
$qc_email = '';

//  add_to_curric()
//  --------------------------------------------------------------------------------------
/*  Function for adding a person who had to be looked up in octsims.erp856 to
 *  curric.people.
 */
  function add_to_curric($person)
  {
    global $curric_db;
    $login_query = <<<EOD
  INSERT INTO people VALUES (
      '{$person->email}',       -- qc_email
      '{$person->name}',        -- name
      '{$person->dept_name}',   -- department
      default,                  -- alt_email
      default,                  -- password
      default,                  -- password_token
      default                   -- last_login
      )
EOD;
    $result = pg_query($curric_db, $login_query) or die("Add user to curric failed: " .
        pg_last_error($curric_db) . ' at ' . basename(__FILE__) . ' ' . __LINE__);
  }

//  update_password()
//  -------------------------------------------------------------------------------------
/*  If new_passwd and repeat_new are the same and the person is in the curric db, update
 *  the user's password.
 *
 *  Note: $new_passwd and $repeat_new must be _raw_ POST data so the user can type spaces
 *  to generate a blank password. They get sanitized here.
 *
 *  Returns a string indicating success or failure.
 */
  function update_password($person, $new_passwd, $repeat_new)
  {
    global $curric_db;
    assert('"Person" === get_class($person)') or die("<h1 class='error'>Non-person at " .
        basename(__FILE__) . ' ' . __LINE__ . "</h1></body></html>\n");

    //  No-op if new and repeat are blank.
    if ($new_passwd === '' && $repeat_new === '') return '';

    //  Get primary key for people table
    $qc_email = $person->email;

    //  New and repeat passwords must match except for leading and trailing spaces
    $new_passwd = sanitize($new_passwd);
    $repeat_new = sanitize($repeat_new);
    if ($new_passwd === $repeat_new)
    {
      //  Use 16-character date/time as salt for CRYPT_SHA512
      $pwd = crypt($new_passwd, '$6$' . date('Y-m-d H:i'));
      $query = <<<EOD
 BEGIN;
 UPDATE people
    SET password = '$pwd'
  WHERE lower(qc_email) = lower('$qc_email')

EOD;
      $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query failed: " .
                pg_last_error($curric_db) . " at " .
                basename(__FILE__) .  ' ' . __LINE__ . "</h1></body></html>\n");
      $num = pg_affected_rows($result);
      if ($num === 1)
      {
        pg_query($curric_db, "COMMIT");
        return password_changed;
      }
      else
      {
        //  Neither zero nor multiple rows should ever be affected.
        pg_query($curric_db, 'ROLLBACK');
        die ("<h1 class='error'>Attempt to change password for $num " .
            "people</h1></body></html>\n");
      }
    }
    return new_repeat_mismatch;
  }

//  login_form()
//  -------------------------------------------------------------------------------------
/*    Display the standard login form. The email address is initialized with the value of
 *    the corresponding global variable.
 */
  function login_form()
  {
    global $login_error_message, $webmaster_email, $qc_email;

    $request_uri = $_SERVER['REQUEST_URI'];
    echo <<<EOD
    <form id='login-form' action='$request_uri' method='post'>
      <fieldset><legend>Sign In</legend>
        <input type='hidden' name='form-name' value='login-form' />
        <div class='instructions'>
          <p>
            Much of this site is open to the public with no neeed to sign in. For other
            parts you must sign in using your Queens College email address. Contact
            $webmaster_email if you are unable to sign in.
          </p>
          <p>
            If your Queens College email address is in the standard format
            (<em>First.Last@qc.cuny.edu</em>), you may simply enter your name.
          </p>
          <p>
            If CUNYfirst has you listed in multiple departments, you will be prompted to
            select the one you want to use. You will only have to do that once.
          </p>
        </div>
        <div class='error'>$login_error_message</div>
        <p>
          <label for='qc-email'>Your Queens College email address:</label>
          <input  id='qc-email'
                  type='text'
                  name='qc-email'
                  tabindex='1'
                  class='triple-wide'
                  value='$qc_email' />
        </p>
        <fieldset><legend>Enter/Change Password</legend>
          <div class='instructions'>
            <p>
              Your password is blank initially, and you may leave it that way if you want
              to.
            </p>
            <p>
              If you want to create a password or change your current one, enter your
              current password, your new password, and repeat it in the boxes below.
            </p>
            <p>
              <strong>Password Rules:</strong> Passwords may be any length and may contain
              any characters, except that spaces at the beginning and end will be removed.
              That means you can revert to a blank password by typing a space in the new and
              repeat boxes.
            </p>
            <p>
              Passwords are highly encrypted before being stored. Still, the usual safe
              practices apply: longer passwords are better than short ones; using
              punctuation symbols, digits, letters with diacritical marks, non-Latin
              letters, etc. are all better than just plain letters.
            </p>
          </div>
          <label for='password'>Password:</label>
          <input type='password' name='password' id='password' tabindex='2' />
          <label for='new_password'>New password:<br />(if you want to change it)</label>
          <input type='password' name='new_password' id='new_password' tabindex='3'/>
          <label for='repeat_new'>Repeat new password:</label>
          <input type='password' name='repeat_new' id='repeat_new' tabindex='4' />
        </fieldset>
        <button type='submit' tabindex='2'>Sign in</button>
    </fieldset>
  </form>

EOD;
  }

/*  There are two different forms in this module: the login-form, generated by the
 *  login_form() function, gets an email address (or person's name if their QC email
 *  address is in "firstname.lastname@qc.cuny.edu format) and password. The email address
 *  gets looked up in the curric.people table and, if that fails, the octsims.erp856
 *  table. The 856 table can return multiple hits (departments), which get disambiguated
 *  by the second form in this module, the which-dept form.
 *
 *  Structure:
 *
 *  + Process form data, if any.
 *    POST variables:
 *      form_name
 *       =login-form
 *          qc_email [ type = text ]
 *          password [ type = password ]
 *       =login-which-department
 *          pending_person [ type = hidden ] serialized Person object
 *          preferred_dept_index [ type = text ] must be numeric (why?)
 *
 *  + If global variable $person is set, do a sanity check to be sure it’s a Person
 *    object and that it matches $_SESSION[person].
 *    Else generate username/password form
 *
 *    SESSION
 *      person:               A serialized Person object when a user is logged in.
 *      login_error_message:      Error message (if any) to display in login form. May
 *                            be displayed by the index page in the case of an attempt
 *                            to access an admin page by someone who is not (yet) logged
 *                            in as an administrator.
 *
 */

  //  Process login-which-department form
  //  -----------------------------------------------------------------------------------
  if ($form_name === login_which_department)
  {
    /*  User previously submitted an email address that returned multiple hits from the
     *  octsims.erp856 table and has now selected the preferred department.
     */

    //  Sanity check
    if (! (isset($_POST[pending_person]) && isset($_POST[dept_name])))
    {
      die("<h1 class='error'>Error: Missing pending_person or " .
          "dept_name</h1></body></html>");
    }

    //  Complete the login process
    $pending_person = unserialize($_POST[pending_person]);
    $dept_name = sanitize($_POST[dept_name]);
    if ($dept_name)
    {
      //  Complete the person object and add to curric db with blank password.
      $pending_person->set_dept();
      add_to_curric($pending_person);
      $_SESSION[person] = serialize($pending_person);
      $person = unserialize($_SESSION[person]);

      // The following were passed as hidden elements by login-form.
      $new_passwd = $_POST[new_password];
      $repeat_new = $_POST[repeat_new];
      $login_error_message =
          update_password($person, $new_passwd, $repeat_new);
    }
    else
    {
      die("<h1 class='error'>Error: Invalid department name</h1></body></html>");
    }
  }

  //  Process login-form form
  //  -----------------------------------------------------------------------------------
  /*    The form has fields for the user’s email, password, and new/repeat passwords.
   */
  if ($form_name === login_form)
  {
    //  Sanity check
    if (! isset($_POST[qc_email]))
    {
      die("<h1 class='error'>Error: email address not set.</h1></body></html>\n");
    }

    //  Extract form data
    $qc_email = str_replace(' ', '.', trim(sanitize($_POST[qc_email])));
    $password = sanitize($_POST[password]);
    $new_passwd = $_POST[new_password];
    $repeat_new = $_POST[repeat_new];

    //  Require 3+ characters for user’s name
    if (strlen($qc_email) > 2)
    {
      //  Supply default email domain if none provided
      if ($qc_email && !strpos($qc_email, '@')) $qc_email .= '@qc.cuny.edu';
      if (!preg_match('/^(\w+[\.\-]?\w+)+@[\w\.]*cuny.edu$/i', $qc_email))
      {
        $login_error_message = bad_email;
      }
      else
      {
        //  Have valid email address: try looking it up in the curric db
        $login_query = "SELECT * FROM people WHERE lower(qc_email) = lower('$qc_email')";
        $result = pg_query($curric_db, $login_query) or die('Unable to access people:' .
            basename(__FILE__) . ' ' . __LINE__ . ' ' . $login_query);
        if ($result && pg_num_rows($result) === 1)
        {
          //  Found user in curric.people: check password
          $row = pg_fetch_assoc($result);
          $post_password = sanitize($_POST[password]);
          if (crypt($post_password, $row[password]) === $row[password])
          {
            $person = new Person($qc_email);
            $person->set_name($row['name']);
            $person->set_dept($row['department']);
            $person->finish_login();
            $_SESSION[person] = serialize($person);
            $login_error_message =
                update_password($person, $new_passwd, $repeat_new);
          }
          else
          {
            $login_error_message = bad_pass;
          }
        }
        else
        {
          /* Not in curric.people: try the octsims.erp856 table at OCT
           *    Query returns:
           *      descr is job title
           *      job_function is C for clerical, I for instructional, "etc."
           */
          $departments_list = array();
          $login_query =
              "SELECT fname, miname, lname, nameprefix, namesuffix, dept_descr "
            . "FROM octsims.erp856 "
            . "WHERE REGEXP_LIKE(cu_email_addr_c1, '$qc_email', 'i') OR "
            . "      REGEXP_LIKE(cu_email_addr_c2, '$qc_email', 'i') OR "
            . "      REGEXP_LIKE(cu_email_addr_c3, '$qc_email', 'i') OR "
            . "      REGEXP_LIKE(cu_email_addr_c4, '$qc_email', 'i')    ";
          //  Use oci_query to run the query.
          $result = json_decode
                    (
                      exec
                      (
                        "(export ORACLE_HOME=/opt/oracle/instantclient/; " .
                        " export TNS_ADMIN=/opt/oracle/instantclient; " .
                        " export DYLD_LIBRARY_PATH=/opt/oracle/instantclient; " .
                        " echo \"$login_query\"|bin/oci_query)"
                      )
                    );
          if (is_array($result) && count($result) !== 0)
          {
            //  OCT lookup succeeded, now build Person object
            $pending_person = new Person($qc_email);
            foreach($result as $row)
            {
              $prefix = trim($row->NAMEPREFIX);
              if ($prefix && substr($prefix, -1) !== '.') $prefix .= '.';
              $fname  = trim($row->FNAME);
              $miname = trim($row->MINAME);
              if ($miname && strlen($miname) === 1) $miname .= '.';
              $lname  = trim($row->LNAME);
              $suffix = trim($row->NAMESUFFIX);
              $name  = $fname . (strlen($miname) ? ' '. $miname . ' ' : ' ');
              $name  .= $lname . (strlen($suffix) ? ' ' . $lname : '');
              $pending_person->set_name($name);
              $departments_list[] = $row->DEPT_DESCR;
            }
            $num_depts = count($departments_list);
            if ($num_depts === 0)
            {
              $login_error_message = no_dept;
              login_form();
            }
            else if ($num_depts === 1)
            {
              //  Valid person: if single dept, s/he's in
              $pending_person->set_dept($departments_list[0]);
              add_to_curric($pending_person);
              $pending_person->finish_login();
              $_SESSION[person] = serialize($pending_person);
              $person = unserialize($_SESSION[person]);
              //  User might set initial passwd using the new/repeat fields
              $login_error_message =
                  update_password($person, $new_passwd, $repeat_new);
            }
            else
            {
              //  Multiple departments: display the login-which-department form
              $serialized_pp = serialize($pending_person);
              $request_uri = $_SERVER['REQUEST_URI'];
              echo <<<EOD
    <form id='login-which-department' action='$request_uri' method='post'>
      <fieldset><legend>Select Department</legend>
        <input type='hidden' name='form-name' value='login-which-department' />
        <input type='hidden' name='pending-person' value='$serialized_pp' />
        <input type='hidden' name='password' value='$password' />
        <input type='hidden' name='new_password' value='$new_passwd' />
        <input type='hidden' name='repeat_new' value='$repeat_new' />
        <p>{$pending_person->name}: Please select which department to use:</p>

EOD;
              //  Radio buttons, first one checked by default, for dept names.
              $n = 0;
              $checked = " checked='checked'";
              foreach ($departments_list as $dept_name)
              {
                $dept_name = sanitize($dept_name);
                echo <<<EOD
        <div>
          <input  type='radio'
                  id='dept-$n'
                  value='$dept_name'
                  name="dept-name" $checked />
          <label for='dept-$n'>{$dept_name}</label>
        </div>

EOD;
                $n++;
                $checked = '';
              }
              echo <<<EOD
        <div>
          <button type='submit'>Continue</button>
        </div>
      </fieldset>
    </form>

EOD;
            }
          }
          else
          {
            //  In neither curric nor 856
            $login_error_message = "'{$_POST[qc_email]}': " . bad_email;
          }
        }   //  if in 856
      }     //  if in curric
    }       //  if non-blank qc_email
    else
    {
      $login_error_message = blank_email;
    }
  }         //  if login-form

  //  Any form submitted has been processed. If not yet logged in, present the form.
  //  ------------------------------------------------------------------------------
  $login_status_msg = <<<EOD


EOD;
  if ( empty($person) && empty($pending_person) )
  {
    login_form();
  }

 ?>
