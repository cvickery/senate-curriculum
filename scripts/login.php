<?php

//  Manage Login (Signin) Process
//  ======================================================================================
/*  To log in, the user has to provide a QC email address, for which we look first in our
 *  local curric.people database table. If that fails, try the octsims.erp856 table, which
 *  may return multiple departmental affiliations. The user has to pick the appropriate
 *  one.
 *
 *  Users who login through the 856 table get added to the curric.people table for use
 *  during future logins.
 *
 */

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

  //  Manage the login process
  //  ==================================================================================
  /*  It's complicated. There are two different forms in this module: login-form
   *  gets a user name or email address, which gets looked up in the curric.people table
   *  and, if that fails, the octsims.erp856 table. The 856 table can return multiple hits
   *  (departments), which get disambibuated by login-which-dept. See also need_password.
   *
   *  Algorithm:
   *  + Process form data. Set SESSION[person] if user/dept have been identified. Set
   *    SESSION[password] if one is entered correctly. Update person's password if new one
   *    is given.
   *  + Display the appropriate form, or none if the user is logged in and either doesn't
   *    need a password or has already provided one.
   *  + User has supplied a preferred_dept_index or an email address that is in the
   *    curric.people table or and email address that matches a single entry in the 856
   *    table: complete the login process.
   *  + User has supplied an email address that matches multiple entries in the 856
   *    table: display a disambiguation form.
   *  + User has displayed an invalid email address or one that is in neither the
   *    curric.people nor the 856 table: set an error message.
   *  + Display either the login-which-department form, the login-form, or nothing
   *    depending on
   *
   *    SESSION variables
   *      session_state:        ss_is_logged_in or ss_not_logged_in
   *      person:               Either unset or a serialized Person object
   *                              carries array of depts and index of preferred one
   *      need_password:        If set
   *                              if true
   *                                invoking page requires a password and none or wrong
   *                                one entered
   *                              else
   *                                password required and correct one has been entered
   *                            Else
   *                              no password needed
   *      login_error_msg:      Error message (if any) to display in login form
   *    POST variables
   *      qc_email
   *      password (maybe)
   *      preferred_dept_index
   *      remember_me           (not implemented)
   *    form_name
   *      login-which-department
   *      login-form
   *
   */

  //  Globals for this module
  $login_error_msg = '';
  //  Process login-which-department form
  //  -----------------------------------------------------------------------------------
  if ($form_name === 'login-which-department')
  {
    /*  User previously submitted an email address that returned multiple hits from the
     *  octsims.erp856 table and has now selected the preferred department.
     */

    //  Sanity checks
    if (! isset($_POST['preferred_dept_index']))
    {
      die("<h1 class='error'>Error: No department index</h1>\n");
    }
    if (! isset($_SESSION[person]))
    {
      die("<h1 class='error'>Error: Missing person</h1>\n");
    }

    //  Complete the login process
    $person = unserialize($_SESSION[person]);
    $num_depts = count($person->dept_names);
    $index = sanitize($_POST['preferred_dept_index']) - 0;
    if ($index >= 0 && $index < $num_depts)
    {
      //  Complete the login process
      $person->set_dept($person->dept_names[$index]);
      add_to_curric($person);
      $person->finish_login();
      $_SESSION[session_state] = ss_is_logged_in;
      $_SESSION[person] = serialize($person);
    }
    else
    {
      die("<h1 class='error'>Error: Invalid department index</h1>\n");
    }
  }

  //  Process login-form form
  //  -----------------------------------------------------------------------------------
  /*  The form accepts the user's qc-email address. It may also contain a section for
   *  password entry and management, which can be processed only once the user's email
   *  address has been determined.
   */
  if ($form_name === 'login-form')
  {
    //  Sanity check
    if (! isset($_POST['qc-email']))
    {
      die("<h1 class='error'>Error: email address not set.</h1></body></html>\n");
    }

    //  Get rid of blanks and invalid characters
    $email = str_replace(' ', '.', trim(sanitize($_POST['qc-email'])));
    //  Ignore empty input, finger slips, and phishing trips
    if (strlen($email) > 2)
    {
      //  Supply default domain if none provided
      if ($email && !strpos($email, '@')) $email .= '@qc.cuny.edu';
      if (!preg_match('/^(\w+[\.\-]?\w+)+@[\w\.]*cuny.edu$/i', $email))
      {
        $login_error_msg = bad_email;
      }
      else
      {
        //  Have potential email address: try looking it up in the curric db
        $login_query = "SELECT * FROM people WHERE lower(qc_email) = lower('$email')";
        $result = pg_query($curric_db, $login_query) or die('Unable to access people:' .
            basename(__FILE__) . ' ' . __LINE__ . ' ' . $login_query);
        if ($result && pg_num_rows($result) === 1)
        {
          $row = pg_fetch_assoc($result);
          $person = new Person($email);
          $person->set_name($row['name']);
          $person->set_dept($row['department']);
          $person->finish_login();
          $_SESSION[person] = serialize($person);
          $_SESSION[session_state] = ss_is_logged_in;
        }
        else
        {
          /* Not in curric.people: try the octsims.erp856 table at OCT
           *    Query returns:
           *      descr is job title
           *      job_function is C for clerical, I for instructional, "etc."
           */
          $login_query =
              "SELECT fname, miname, lname, nameprefix, namesuffix, dept_descr "
            . "FROM octsims.erp856 "
            . "WHERE REGEXP_LIKE(cu_email_addr_c1, '$email', 'i') OR "
            . "      REGEXP_LIKE(cu_email_addr_c2, '$email', 'i') OR "
            . "      REGEXP_LIKE(cu_email_addr_c3, '$email', 'i') OR "
            . "      REGEXP_LIKE(cu_email_addr_c4, '$email', 'i')    ";
          //  Use /usr/local/bin/oci_query to run the query.
          $result = json_decode(exec(
                "(export DYLD_LIBRARY_PATH=/opt/oracle/instantclient/; "
              . "export ORACLE_HOME=\$DYLD_LIBRARY; "
              . "echo \"$login_query\"|/usr/local/bin/oci_query)"));
          if (is_array($result) && count($result) !== 0)
          {
            //  OCT lookup succeeded, now build Person object
            $person = new Person($email);
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
              $person->set_name($name);
              $person->append_dept($row->DEPT_DESCR);
            }
            $num_depts = count($person->dept_names);
            if ($num_depts === 0)
            {
              $login_error_msg = no_dept;
            }
            else if ($num_depts === 1)
            {
              //  Valid person: if single dept, s/he's in
              $person->set_dept($person->dept_names[0]);
              add_to_curric($person);
              $person->finish_login();
              $_SESSION[person] = serialize($person);
              $_SESSION[session_state] = ss_is_logged_in;
            }
            else
            {
              //  Multiple departments
              $_SESSION[person] = serialize($person);
              $_SESSION[session_state] = ss_not_logged_in;
            }
          }
          else
          {
            $login_error_msg = "'{$_POST['qc-email']}': " . bad_email;
          }
        }
      }
    }
    //  Process password part of the form only if it's required and person's email address
    //  has been determined. (Failure here puts the person back in the not logged in state
    //  again.)
    if ($_SESSION[session_state] === ss_is_logged_in &&
        isset($_SESSION[need_password]) && $_SESSION[need_password])
    {
      //  Sanity check to start
      if (strstr($email, '@') === FALSE)
      {
        die("<h1 class='error'>Error: bad qc_email at " . __FILE__ . " line " . __LINE__ .
            "</h1></body></html>\n");
      }
      $password     = sanitize($_POST[password]);
      $new_password = sanitize($_POST[new_password]);
      $repeat_new   = sanitize($_POST[repeat_new]);
      $query = <<<EOD
  SELECT password
    FROM people
   WHERE lower(qc_email) = lower('$email')

EOD;
      $result = pg_query($curric_db, $query) or die("Query failed: " .
          pg_last_error($curric_db) . " at " . __FILE__ . " line " . __LINE__);
      $num = pg_num_rows($result);
      if (1 !== $num)
      {
        die("<h1 class='error'>Error: password lookup for $email failed ($num)</h1.\n" .
            "</body></html>\n");
      }
      $row = pg_fetch_assoc($result);
      if (sha1(PRE_SALT . $password . POST_SALT) === $row['password'])
      {
        //  Correct password ...
        $_SESSION[need_password] = false;
        if ( ($_POST[new_password] !== '') && ($new_password !== $password) )
        {
          //  User wants to change passwords
          if ($new_password === $repeat_new)
          {
            $pwd = sha1(PRE_SALT . $new_password . POST_SALT);
            $query = <<<EOD
   BEGIN;
   UPDATE people
      SET password = '$pwd'
    WHERE lower(qc_email) = lower('$email')

EOD;
            $result = pg_query($curric_db, $query) or die("<h1 class='error'>Error: " .
                " query failed: " . pg_last_error($curric_db) . " at " . __FILE__ .
                " line " . __LINE__);
            $num = pg_affected_rows($result);
            if ($num === 1)
            {
              pg_query($curric_db, "COMMIT");
              $password_change = "Password Changed";
            }
            else
            {
              pg_query($curric_db, "ROLLBACK");
              die("<h1 class='error'>Password change failed: attempted to change $num " .
                  "passwords</h1></body></html>\n");
            }
          }
          else
          {
            //  new and repeat differ: to provide feedback, the password part of the login
            //  form will have to be presented again.
            $login_error_msg = "Unable to change password: New and Repeat differ.";
            $_SESSION[need_password] = true;
          }
        }
      }
      else
      {
        //  wrong password entered
        $login_error_msg = "Wrong password.";
        $_SESSION[session_state] = ss_not_logged_in;
        unset($person);
        unset($_SESSION[person]);
      }
    }
  }

  //  Generate the appropriate form, if any
  //  -----------------------------------------------------------------------------------
  /*  Three possibilities:
   *    1.  session_state is not-logged-in and $person is set
   *          This occurs when the user provides a valid email address that is not in
   *          curric.people and returned multiple hits from octsims.erp856.
   *          Display department choice form.
   *    2.  session_state is not-logged-in and $person is not set:
   *          This occurs when there has been no login attempt, or a failed one.
   *          Display regular login form.
   *    3.  session_state is logged-in:
   *          No form to display ... unless need_password
   */
  if ($_SESSION[session_state] === ss_not_logged_in)
  {
    if (isset($person) && $person)
    {
      //  Login was started by a person with multiple departments: select which one
      echo <<<EOD
    <form id='login-which-department' action='.' method='post'>
      <fieldset><legend>Select Department</legend>
        <input type="hidden" name='form-name' value='login-which-department' />
        <p>{$person->name}: Please select which department to use:</p>

EOD;
      $n = 0;
      $checked = " checked='checked'";
      foreach ($person->dept_names as $dept)
      {
        $dept_name = sanitize($dept);
        echo <<<EOD
        <div>
          <input type='radio' id='dept-$n' value='$n' name="preferred_dept_index" $checked />
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
    else
    {
      //  Nobody logged in or login attempt failed
      $login_error_msg = "<p class='error'>{$login_error_msg}</p>";

      if (!isset($email)) $email = '';
      echo <<<EOD
    <form id='login-form' action='.' method='post'>
      <fieldset><legend>Sign In</legend>
        <input type='hidden' name='form-name' value='login-form' />
        <div class='instructions'>
          <p>
            Much of this site is open to the public with no neeed to sign in. For other
            parts, such as the Proposal Editor, you must provide your Queens College email
            address, which must be on record with CUNYfirst. Contact $webmaster_email if
            you are unable to sign in.
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
        $login_error_msg
        <label for='qc-email'>Your Queens College email address:</label>
        <div>
          <input  id='qc-email'
                  type='text'
                  name='qc-email'
                  tabindex='1'
                  class='triple-wide'
                  value='$email' />
          <fieldset><legend>Enter/Change Password (see instructions)</legend>
          <div class='instructions'>
EOD;
      if (isset($_SESSION[need_password]) && $_SESSION[need_password])
      {
        echo <<<EOD
          <p>
            You need to enter your password to proceed. You may also change it here if you
            want to do so.
          </p>

EOD;
      }
      else
      {
        echo <<<EOD
          <p>
            Most parts of this site do not use passwords, so you can simply leave the
            fields below empty. This is secure because we verify that anyone submitting
            a proposal actually owns the Queens College email address they used for
            signing in.
          </p>
          <p>
            But for members of the curriculum committees and their subcommittees, certain
            additional features become available only if you log in using a password,
            which should be entered below. You can also change your password by entering a
            new one where indicated.
          </p>

EOD;
      }
      echo <<<EOD
            </div>
            <label for='password'>Password:</label>
            <input type='password' name='password' id='password' tabindex='1' />
            <label for='new_password'>New password:<br />(if you want to change it)</label>
            <input type='password' name='new_password' id='new_password' tabindex='3'/>
            <label for='repeat_new'>Repeat new password:</label>
            <input type='password' name='repeat_new' id='repeat_new' />
          </fieldset>

          <button type='submit' tabindex='2'>Sign in</button>
        </div>
      </fieldset>
    </form>

EOD;
    }
  }
  else
  {
    //  Person is logged in, but may not yet have supplied a needed password
    if (isset($_SESSION[need_password]) and $_SESSION[need_password])
    {
      if ($login_error_msg)
      {
        $login_error_msg = "<p class='error'>$login_error_msg</p>\n";
      }
      $person = unserialize($_SESSION[person]);
      echo <<<EOD
      <form id='login-form' method='post' action='.'>
        <input type='hidden' name='form-name' value='login-form' />
        <input type='hidden' name='qc-email' value='{$person->email}' />
        <fieldset><legend>Enter/Change Password</legend>
          $login_error_msg
          <label for='password'>Password:</label>
          <input type='password' name='password' id='password' tabindex='1' />
          <label for='new_password'>New password:<br />(if you want to change it)</label>
          <input type='password' name='new_password' id='new_password' tabindex='3'/>
          <label for='repeat_new'>Repeat new password:</label>
          <input type='password' name='repeat_new' id='repeat_new' />
          <button type='submit' tabindex='2'>Submit</button>
        </fieldset>
      </form>

EOD;
    }
  }

 ?>

