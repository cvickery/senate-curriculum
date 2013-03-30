<?php  /* Admin/proposal_status.php */

set_include_path(get_include_path() . PATH_SEPARATOR . '../scripts' );
require_once('init_session.php');


//  Here beginnith the web page
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
    <title>Update Proposal Statuses</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/review_status.css" />
    <script type="application/javascript" src='js/jquery-1.8.3.min.js'></script>
    <script type="application/javascript" src='js/update_statuses.js'></script>
  </head>
  <body>
    <h1>Update Proposal Statuses</h1>
<?php
  echo $dump_if_testing;

  //  Handle the logging in/out situation here
  $last_login       = '';
  $status_msg       = 'Not signed in';
  $sign_out_button  = '';
  $person           = '';
  $password_change  = '';
  require_once('short-circuit.php');
  if ( ! isset($_SESSION[need_password]) )
  {
    $_SESSION[need_password] = true;
  }
  require_once('login.php');
  if (isset($_SESSION[session_state]) && $_SESSION[session_state] === ss_is_logged_in)
  {
    if (isset($_SESSION[person]))
    {
      $person = unserialize($_SESSION[person]);
    }
    else
    {
      die("<h1 class='error'>Review Status: Invalid login state</h1></body></html>");
    }

    $status_msg = sanitize($person->name) . ' / ' . sanitize($person->dept_name);
    $last_login = 'First login';
    if ($person->last_login_time)
    {
      $last_login   = "Last login at ";
      $last_login  .= $person->last_login_time . ' from ' . $person->last_login_ip;
    }
    $sign_out_button = <<<EOD

    <form id='logout-form' action='.' method='post'>
      <input type='hidden' name='form-name' value='logout' />
      <button type='submit'>Sign Out</button>
    </form>

EOD;

  //  Process Form Data
  //  ===================================================================================
  
  //  Delete events if undo form is submitted
  //  ---------------------------------------
  if ($form_name === 'undo-form')
  {
    $or_list = '';
    $event_ids = unserialize($_SESSION['event_ids']);
    unset($_SESSION['event_ids']);
//    echo "<pre>\n";var_dump($_SESSION);var_dump($event_ids);echo "</pre>\n";
    foreach ($event_ids as $event_id)
    {
      if ($or_list === '') $or_list = "id = $event_id";
      else $or_list .= ' or id = ' . $event_id;
    }
    $result = pg_query($curric_db, "DELETE FROM events WHERE $or_list");
    if ($result !== false)
    {
      $num = pg_affected_rows($result);
      $suffix = ($num === 1) ? '' : 's';
      echo <<<EOD
        <h2 class='warning'>
          Did undo $num event$suffix
        </h2>

EOD;
    }
    else echo "<h2 class='error'>Error: didn’t undo any events</h2>\n";

  }

  //  Clear array of event IDs to be used for "undo" feature.
  $event_ids = array();

  //  Generate GEAC Approval events
  //  -----------------------------
  if ($form_name === 'geac-approved-form')
  {
    try
    {
      //  Common to all approvals
      $num = 0;
      $event_date     = new DateTime(sanitize($_POST['geac-approved-date']));
      $event_date     = $event_date->format('Y-m-d');
      $effective_date = $event_date;
      $agency         = 'GEAC';
      $action         = 'Approve';
      $comment        = '';
      $remote_ip = 'Unknown Host IP';
      if (isset($_SERVER['REMOTE_ADDR']))
      {
        $remote_ip = $_SERVER['REMOTE_ADDR'];
      }

      //  Generate approval event for each proposal selected
      pg_query($curric_db, 'BEGIN') or die("<h1 class='error'>Query Failed: "
          . pg_last_error($curric_db) . " File " . __FILE__ . " " . __LINE__
          . "</h2></body></html>\n");
      foreach ($_POST as $key => $value)
      {
        if (strstr($key, 'geac-approved-id-'))
        {
          $proposal_id = str_replace('geac-approved-id-', '', $key);
          $query = <<<EOD
INSERT INTO events
        (
          id,
          event_date,
          effective_date,
          agency_id,
          action_id,
          proposal_id,
          discipline,
          course_number,
          annotation,
          entered_by,
          entered_from,
          entered_at
        )
VALUES (
        default,                                                        -- id
        '$event_date',                                                  -- event_date
        '$effective_date',                                              -- effective_date
        (SELECT id FROM agencies WHERE abbr = '$agency'),               -- agency_id
        (SELECT id FROM actions WHERE full_name = '$action'),           -- action_id
        $proposal_id,                                                   -- proposal_id
        (SELECT discipline FROM proposals WHERE id = $proposal_id),     -- discipline
        (SELECT course_number FROM proposals WHERE id = $proposal_id),  -- course_number
        '$comment',                                                     -- annotation
        '{$person->email}',                                             -- entered_by
        '$remote_ip',                                                   -- entered_from
        now()                                                           -- entered_at
        )
        returning id
EOD;
            $result = pg_query($curric_db, $query) or die("Update error: " .
              pg_last_error($curric_db) . ' file ' . basename(__FILE__) . ' line ' .
              __LINE__);
            $event_ids[] = pg_fetch_result($result, 0, 'id');
            $num++;
        }
      }
      $suffix = ($num === 1) ? '' : 's';
      echo <<<EOD
      <h2 class='warning'>$num GEAC approval$suffix entered.</h2>

EOD;
      pg_query($curric_db, 'COMMIT') or die("<h1 class='error'>Query Failed: "
          . pg_last_error($curric_db) . " File " . __FILE__ . " " . __LINE__
          . "</h2></body></html>\n");
    }
    catch (Exception $e)
    {
      echo <<<EOD
      <h2 class='error'>
        Invalid GEAC Approval Date. No approvals entered.
      </h2>

EOD;
    }
  }

  //  Generate UCC Approval events
  //  ----------------------------------------------------
  if ($form_name === 'ucc-approved-form')
  {
    try
    {
      //  Common to all approvals
      $num = 0;
      $event_date     = new DateTime(sanitize($_POST['ucc-approved-date']));
      $event_date     = $event_date->format('Y-m-d');
      $effective_date = $event_date;
      $agency         = 'UCC';
      $action         = 'Approve';
      $comment        = '';
      $remote_ip = 'Unknown Host IP';
      if (isset($_SERVER['REMOTE_ADDR']))
      {
        $remote_ip = $_SERVER['REMOTE_ADDR'];
      }

      //  Generate approval event for each proposal selected
      pg_query($curric_db, 'BEGIN') or die("<h1 class='error'>Query Failed: "
          . pg_last_error($curric_db) . " File " . __FILE__ . " " . __LINE__
          . "</h2></body></html>\n");
      foreach ($_POST as $key => $value)
      {
        if (strstr($key, 'ucc-approved-id-'))
        {
          $proposal_id = str_replace('ucc-approved-id-', '', $key);
          $query = <<<EOD
INSERT INTO events
        (
          id,
          event_date,
          effective_date,
          agency_id,
          action_id,
          proposal_id,
          discipline,
          course_number,
          annotation,
          entered_by,
          entered_from,
          entered_at
        )
VALUES (
        default,                                                        -- id
        '$event_date',                                                  -- event_date
        '$effective_date',                                              -- effective_date
        (SELECT id FROM agencies WHERE abbr = '$agency'),               -- agency_id
        (SELECT id FROM actions WHERE full_name = '$action'),           -- action_id
        $proposal_id,                                                   -- proposal_id
        (SELECT discipline FROM proposals WHERE id = $proposal_id),     -- discipline
        (SELECT course_number FROM proposals WHERE id = $proposal_id),  -- course_number
        '$comment',                                                     -- annotation
        '{$person->email}',                                             -- entered_by
        '$remote_ip',                                                   -- entered_from
        now()                                                           -- entered_at
        )
        returning id
EOD;
            $result = pg_query($curric_db, $query) or die("Update error: " .
              pg_last_error($curric_db) . ' file ' . basename(__FILE__) . ' line ' .
              __LINE__);
            $event_ids[] = pg_fetch_result($result, 0, 'id');
            $num++;
        }
      }
      $suffix = $num === 1 ? '' : 's';
      echo <<<EOD
      <h2 class='warning'>$num UCC approval$suffix entered.</h2>

EOD;
      pg_query($curric_db, 'COMMIT') or die("<h1 class='error'>Query Failed: "
          . pg_last_error($curric_db) . " File " . __FILE__ . " " . __LINE__
          . "</h2></body></html>\n");
    }
    catch (Exception $e)
    {
      echo <<<EOD
      <h2 class='error'>
        Invalid UCC Approval Date. No approvals entered.
      </h2>

EOD;
    }
  }

  //  Generate Senate Approval events
  //  ----------------------------------------------------
  if ($form_name === 'senate-approved-form')
  {
    try
    {
      //  Common to all approvals
      $num = 0;
      $event_date     = new DateTime(sanitize($_POST['senate-approved-date']));
      $event_date     = $event_date->format('Y-m-d');
      $effective_date = $event_date;
      $agency         = 'Senate';
      $action         = 'Approve';
      $comment        = '';
      $remote_ip = 'Unknown Host IP';
      if (isset($_SERVER['REMOTE_ADDR']))
      {
        $remote_ip = $_SERVER['REMOTE_ADDR'];
      }

      //  Generate approval event for each proposal selected
      pg_query($curric_db, 'BEGIN') or die("<h1 class='error'>Query Failed: "
          . pg_last_error($curric_db) . " File " . __FILE__ . " " . __LINE__
          . "</h2></body></html>\n");
      foreach ($_POST as $key => $value)
      {
        if (strstr($key, 'senate-approved-id-'))
        {
          $proposal_id = str_replace('senate-approved-id-', '', $key);
          $query = <<<EOD
INSERT INTO events
        (
          id,
          event_date,
          effective_date,
          agency_id,
          action_id,
          proposal_id,
          discipline,
          course_number,
          annotation,
          entered_by,
          entered_from,
          entered_at
        )
VALUES (
        default,                                                        -- id
        '$event_date',                                                  -- event_date
        '$effective_date',                                              -- effective_date
        (SELECT id FROM agencies WHERE abbr = '$agency'),               -- agency_id
        (SELECT id FROM actions WHERE full_name = '$action'),           -- action_id
        $proposal_id,                                                   -- proposal_id
        (SELECT discipline FROM proposals WHERE id = $proposal_id),     -- discipline
        (SELECT course_number FROM proposals WHERE id = $proposal_id),  -- course_number
        '$comment',                                                     -- annotation
        '{$person->email}',                                             -- entered_by
        '$remote_ip',                                                   -- entered_from
        now()                                                           -- entered_at
        )
        returning id
EOD;
            $result = pg_query($curric_db, $query) or die("Update error: " .
              pg_last_error($curric_db) . ' file ' . basename(__FILE__) . ' line ' .
              __LINE__);
            $num++;
            $event_ids[] = pg_fetch_result($result, 0, 'id');
        }
      }
      $suffix = $num === 1 ? '' : 's';
      echo <<<EOD
      <h2 class='warning'>$num Senate approval$suffix entered.</h2>

EOD;
      pg_query($curric_db, 'COMMIT') or die("<h1 class='error'>Query Failed: "
          . pg_last_error($curric_db) . " File " . __FILE__ . " " . __LINE__
          . "</h2></body></html>\n");
    }
    catch (Exception $e)
    {
      echo <<<EOD
      <h2 class='error'>
        Invalid Senate Approval Date. No approvals entered.
      </h2>

EOD;
    }
  }

  //  Generate CCRC submitted events
  //  ----------------------------------------------------
  if ($form_name === 'ccrc-submitted-form')
  {
    try
    {
      //  Common to all approvals
      $num = 0;
      $event_date     = new DateTime(sanitize($_POST['ccrc-submitted-date']));
      $event_date     = $event_date->format('Y-m-d');
      $effective_date = $event_date;
      $agency         = 'CCRC';
      $action         = 'Submit';
      $comment        = '';
      $remote_ip = 'Unknown Host IP';
      if (isset($_SERVER['REMOTE_ADDR']))
      {
        $remote_ip = $_SERVER['REMOTE_ADDR'];
      }

      //  Generate approval event for each proposal selected
      pg_query($curric_db, 'BEGIN') or die("<h1 class='error'>Query Failed: "
          . pg_last_error($curric_db) . " File " . __FILE__ . " " . __LINE__
          . "</h2></body></html>\n");
      foreach ($_POST as $key => $value)
      {
        if (strstr($key, 'ccrc-submitted-id-'))
        {
          $proposal_id = str_replace('ccrc-submitted-id-', '', $key);
          $query = <<<EOD
INSERT INTO events
        (
          id,
          event_date,
          effective_date,
          agency_id,
          action_id,
          proposal_id,
          discipline,
          course_number,
          annotation,
          entered_by,
          entered_from,
          entered_at
        )
VALUES (
        default,                                                        -- id
        '$event_date',                                                  -- event_date
        '$effective_date',                                              -- effective_date
        (SELECT id FROM agencies WHERE abbr = '$agency'),               -- agency_id
        (SELECT id FROM actions WHERE full_name = '$action'),           -- action_id
        $proposal_id,                                                   -- proposal_id
        (SELECT discipline FROM proposals WHERE id = $proposal_id),     -- discipline
        (SELECT course_number FROM proposals WHERE id = $proposal_id),  -- course_number
        '$comment',                                                     -- annotation
        '{$person->email}',                                             -- entered_by
        '$remote_ip',                                                   -- entered_from
        now()                                                           -- entered_at
        )
        returning id
EOD;
            $result = pg_query($curric_db, $query) or die("Update error: " .
              pg_last_error($curric_db) . ' file ' . basename(__FILE__) . ' line ' .
              __LINE__);
            $event_ids[] = pg_fetch_result($result, 0, 'id');
            $num++;
        }
      }
      $suffix = $num === 1 ? '' : 's';
      echo <<<EOD
      <h2 class='warning'>$num CCRC Submitted event$suffix entered.</h2>

EOD;
      pg_query($curric_db, 'COMMIT') or die("<h1 class='error'>Query Failed: "
          . pg_last_error($curric_db) . " File " . __FILE__ . " " . __LINE__
          . "</h2></body></html>\n");
    }
    catch (Exception $e)
    {
      echo <<<EOD
      <h2 class='error'>
        Invalid CCRC Submission Date. No submissions entered.
      </h2>

EOD;
    }
  }

  //  If any events were created, offer to delete them.
  $num_events = count($event_ids);
  if ($num_events > 0)
  {
    $suffix = ($num_events === 1) ? '' : 's';
    $_SESSION['event_ids'] = serialize($event_ids);
    echo <<<EOD
      <form action='./proposal_status.php' method='post'>
        <input type='hidden' name='form-name' value='undo-form' />
        <button type='submit'>Undo $num_events event$suffix</button>
      </form>

EOD;
  }
  else
  {
    unset($_SESSION['event_ids']);
  }

  //  GEAC proposals that need to be marked Approved, if any.
  //  ===================================================================================
  $query = <<<EOD
select *
from double_accepts
order by id;

EOD;
  $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query Failed: "
      . pg_last_error($curric_db) . " " . basename(__FILE__) . " " . __LINE__
      . "</h1></body></html>");
  $num = pg_num_rows($result);
  if ($num > 0)
  {
    $suffix = $num === 1 ? '' : 's';
    echo <<<EOD
      <h2>$num proposal$suffix to be marked 'Approved' by GEAC</h2>
      <form name='geac-approved-form' action='./proposal_status.php' method='post'>
        <input type='hidden' name='form-name' value='geac-approved-form' />
        <table id='geac-approved-table'>
          <tr>
            <th>Accept</th>
            <th>ID</th>
            <th>Course</th>
            <th>Type</th>
            <th>Num Accepts</th>
          </tr>

EOD;
      while ($row = pg_fetch_assoc($result))
      {
        $id = $row['id'];
        echo <<<EOD
          <tr>
            <td><input type='checkbox' name='geac-approved-id-$id' checked='checked' /></td>
            <td><a href='../Proposals?id=$id' target='_blank'>$id</a></td>
            <td>{$row['course']}</td>
            <td>{$row['type']}</td>
            <td>{$row['num_accepts']}</td>
          </tr>

EOD;
      }
          echo <<<EOD
        </table>
        <fieldset><legend>Submit approvals</legend>
          <label for='geac-approved-date'>GEAC Approval Date</label>
          <input  type='text'
                  name='geac-approved-date'
                  value='today'
                  id='geac-approved-date' />
          <button type='submit' id='geac-approved-button'>
            GEAC approved all Proposals
          </button>
        </fieldset>
      </form>

EOD;
  }
  else
  {
    echo "<h2>There are no proposals to be marked 'Approved' by GEAC</h2>\n";
  }

  //  Proposals that are approved by {GEAC, WSC, AQRAC} but not by UCC
  //  ----------------------------------------------------------------
  $query = 'select * from ucc_pending order by subcommittee_name, subcommittee_approved';
  $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query Failed: "
      . pg_last_error($curric_db) . ' File ' . __FILE__ . ' ' . __LINE__
      . "</h1></body></html>");
  $num = pg_num_rows($result);
  $suffix = 's';
  $copula = 'are';
  if ($num == 1)
  {
    $suffix = '';
    $copula = 'is';
  }
  if ($num > 0)
  {
    echo <<<EOD
      <h2>
        $num proposal$suffix that $copula approved by {GEAC, WSC, AQRAC}, but not by UCC
      </h2>
      <form name='ucc-approved-form' action='./proposal_status.php' method='post'>
        <input type='hidden' name='form-name' value='ucc-approved-form' />
        <table id='ucc-approved-table'>
          <tr>
            <th>Select</th>
            <th>ID</th>
            <th>Course</th>
            <th>Type</th>
            <th>Submitted</th>
            <th>Subcommittee Approved</th>
          </tr>
EOD;
    while ($row = pg_fetch_assoc($result))
    {
      $id = $row['proposal_id'];
      echo <<<EOD
          <tr>
            <td><input type='checkbox' name='ucc-approved-id-$id' checked='checked' /></td>
            <td><a href='../Proposals?id=$id'>$id</a></td>
            <td>{$row['course']}</td>
            <td>{$row['type']}</td>
            <td>{$row['submitted']}</td>
            <td>{$row['subcommittee_approved']} ({$row['subcommittee_name']})</td>
          </tr>
EOD;
    }
    echo <<<EOD
        </table>
        <fieldset><legend>Submit UCC approvals</legend>
          <label for='ucc-approved-date'>UCC Approval Date</label>
          <input  type='text'
                  name='ucc-approved-date'
                  value='today'
                  id='ucc-approved-date' />
          <button type='submit' id='ucc-approved-button'>
            The UCC approved all proposals
          </button>
        </fieldset>
      </form>

EOD;
  }
  else
  {
    echo <<<EOD
      <h2>
        All proposals approved by {GEAC, WSC, AQRAQ} have been approved by the UCC
      </h2>

EOD;
  }

  //  Course proposals not yet approved by the UCC
  //  --------------------------------------------
  echo <<<EOD
      <h2>
        Proposals for new or revised courses not yet approved by the UCC
        <span class='warning'>(not implemented yet)</span>
      </h2>

EOD;

  //  Proposals that are approved by UCC, but not by Senate
  //  ------------------------------------------------------
  $query = 'select * from senate_pending order by type, ucc_approved';
  $result = pg_query($curric_db, $query) or die("<h1 class = 'error'>Query Failed: "
      . pg_last_error($curric_db) . ' File ' . __FILE__ . ' ' . __LINE__
      . "</h1></body></html>\n");
  $num = pg_num_rows($result);
  $suffix = 's';
  $copula = 'are';
  if ($num == 1)
  {
    $suffix = '';
    $copula = 'is';
  }
  if ($num > 0)
  {
    echo <<<EOD
      <h2>$num proposal$suffix that $copula approved by UCC, but not by the Senate</h2>
      <form name='senate-approved-form' action='./proposal_status.php' method='post'>
        <input type='hidden' name='form-name' value='senate-approved-form' />
        <table id='senate-approved-table'>
          <tr>
            <th>Select</th>
            <th>ID</th>
            <th>Course</th>
            <th>Type</th>
            <th>Submitted</th>
            <th>UCC Approved</th>
          </tr>

EOD;
    while ($row = pg_fetch_assoc($result))
    {
      $id = $row['proposal_id'];
      echo <<<EOD
          <tr>
            <td>
              <input type='checkbox' name='senate-approved-id-$id' checked='checked' />
            </td>
            <td><a href='../Proposals?id=$id?'>$id</a></td>
            <td>{$row['course']}</td>
            <td>{$row['type']}</td>
            <td>{$row['submitted']}</td>
            <td>{$row['ucc_approved']}</td>
          </tr>

EOD;
    }
     echo <<<EOD
        </table>
        <fieldset><legend>Submit Senate approvals</legend>
          <label for='senate-approved-date'>Senate Approval Date</label>
          <input  type='text'
                  name='senate-approved-date'
                  value='today'
                  id='senate-approved-date' />
          <button type='submit' id='senate-approved-button'>
            The Senate approved all proposals
          </button>
          <button type='button' id='clear-all-senate-approved-button'>Clear All</button>
          <button type='button' id='select-all-senate-approved-button' disabled='disabled'>
            Select All
          </button>
        </fieldset>
      </form>

EOD;
  }
  else
  {
  echo <<<EOD
      <h2>
        All proposals approved by the UCC have been approved by the Senate
      </h2>

EOD;
  }

  //  Proposals approved by Senate, but not yet submitted to the CCRC
  //  ---------------------------------------------------------------
  $query = 'select * from ccrc_pending_submit order by type, senate_approved';
  $result = pg_query($curric_db, $query) or die("<h1 class = 'error'>Query Failed: "
      . pg_last_error($curric_db) . ' File ' . __FILE__ . ' ' . __LINE__
      . "</h1></body></html>\n");
  $num = pg_num_rows($result);
  $suffix = 's';
  $copula = 'are';
  if ($num == 1)
  {
    $suffix = '';
    $copula = 'is';
  }
  if ($num > 0)
  {
    echo <<<EOD
      <h2>
        $num proposal$suffix that $copula approved by the Senate, but not yet submitted
        to the CCRC.
      </h2>

      <form name='ccrc-submitted-form' action='./proposal_status.php' method='post'>
        <input type='hidden' name='form-name' value='ccrc-submitted-form' />
        <table id='ccrc-submitted-table'>
          <tr>
            <th>Select</th>
            <th>ID</th>
            <th>Course</th>
            <th>Type</th>
            <th>Submitted</th>
            <th>Senate Approved</th>
          </tr>

EOD;
    while ($row = pg_fetch_assoc($result))
    {
      $id = $row['proposal_id'];
      echo <<<EOD
          <tr>
            <td><input type='checkbox' name='ccrc-submitted-id-$id' checked='checked' /></td>
            <td><a href='../Proposals?id=$id'>$id</a></td>
            <td>{$row['course']}</td>
            <td>{$row['type']}</td>
            <td>{$row['submitted']}</td>
            <td>{$row['senate_approved']}</td>
          </tr>

EOD;
    }
     echo <<<EOD
        </table>
        <fieldset><legend>Submitted to CCRC</legend>
          <label for='ccrc-submitted-date'>Date submitted to CCRC</label>
          <input  type='text'
                  name='ccrc-submitted-date'
                  value='today'
                  id='ccrc-submitted-date' />
          <button type='submit' id='ccrc-submitted-button'>
            Submitted all proposals to the CCRC
          </button>
          <button type='button' id='clear-all-ccrc-submitted-button'>Clear All</button>
          <button type='button' id='select-all-ccrc-submitted-button' disabled='disabled'>
            Select All
          </button>
        </fieldset>
      </form>

EOD;
  }
  else
  {
  echo <<<EOD
      <h2>All proposals approved by the Senate have been submitted to the CCRC</h2>

EOD;
  }

  //  Proposals approved by Senate, but not by BOT
  //  ---------------------------------------------
  echo <<<EOD
      <h2>
        Proposals approved by Senate, but not by BOT
        <span class='warning'>(not implemented yet)</span>
      </h2>

EOD;

  //  Proposals to Fix CUNYfirst awaiting Registrar action
  //  -----------------------------------------------------
  echo <<<EOD
      <h2>Proposals to Fix CUNYfirst awaiting Registrar action
        <span class='warning'>(not implemented yet)</span>
      </h2>

EOD;

  //  Status/Nav Bars
  //  =================================================================================
  /*  Generated here, after login status is determined, but displayed up top by the
   *  wonders of CSS.
   */
  //  First row link to Review Editor depends on the user having something to review
  $review_link = '';
  if (isset($person) && $person && $person->has_reviews)
  {
    $review_link = "<a href='../Review_Editor'>Edit Reviews</a>";
  }
  $site_nav  = site_nav();
  $admin_nav = admin_nav();
  echo <<<EOD
    <div id='status-bar'>
      <div class='warning' id='password-msg'>
        $password_change
      </div>
      $sign_out_button
      <div id='status-msg' title='$last_login'>
        $status_msg
      </div>
      <!-- Navigation -->
			$site_nav
			$admin_nav
    </div>

EOD;
  }

?>
  </body>
</html>

