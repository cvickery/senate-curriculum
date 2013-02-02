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
    <title>Proposal Status</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/review_status.css" />
    <script type="application/javascript" src='js/jquery-1.8.3.min.js'></script>
    <script type="application/javascript" src='js/proposal_status.js'></script>
  </head>
  <body>
    <h1>Proposal Status</h1>
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

  //  Proposals that need to be marked 'Approved' by GEAC
  //  ----------------------------------------------------
  //  First process the geac-approval-form, if it was submitted
  if ($form_name === 'geac-approval-form')
  {
    try
    {
      //  Common to all approvals
      $num = 0;
      $event_date     = new DateTime(sanitize($_POST['geac-approval-date']));
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
        if (strstr($key, 'geac-accept'))
        {
          $proposal_id = str_replace('geac-accept-', '', $key);
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
EOD;
            $result = pg_query($curric_db, $query) or die("Update error: " .
              pg_last_error($curric_db) . ' file ' . basename(__FILE__) . ' line ' .
              __LINE__);
            $num++;
        }
      }
      echo <<<EOD
      <h2 class='warning'>$num GEAC approvals entered.</h2>

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
  //  Now display the proposals than need to be marked Approved, if any.
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
    echo <<<EOD
      <h2>Proposals that need to be marked 'Approved' by GEAC</h2>
      <form name='geac-approval-form' action='./proposal_status.php' method='post'>
        <input type='hidden' name='form-name' value='geac-approval-form' />
        <table>
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
            <td><input type='checkbox' name='geac-accept-$id' checked='checked' /></td>
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
          <label for='geac-approval-date'>GEAC Approval Date</label>
          <input  type='text'
                  name='geac-approval-date'
                  value='today'
                  id='geac-approval-date' />
          <button type='submit' id='geac-approval-button'>
          <span id='num-accept'>Approve $num Proposals</span></button>
        </fieldset>
      </form>

EOD;
  }
  else
  {
    echo "<h2>There are no proposals that need to be marked 'Approved' by GEAC</h2>\n";
  }
  //  Proposals that are approved by GEAC, but not by UCC
  //  ----------------------------------------------------
  echo <<<EOD
      <h2>Proposals that are approved by GEAC, but not by UCC</h2>

EOD;

  //  Proposals that are approved by UCC, but not by Senate
  //  ------------------------------------------------------
  echo <<<EOD
      <h2>Proposals that are approved by UCC, but not by Senate</h2>

EOD;

  //  Proposals approved by Senate, but not by CCRC
  //  ----------------------------------------------
  echo <<<EOD
      <h2>Proposals approved by Senate, but not by CCRC</h2>

EOD;

  //  Proposals approved by Senate, but not by BOT
  //  ---------------------------------------------
  echo <<<EOD
      <h2>Proposals approved by Senate, but not by BOT</h2>

EOD;

  //  Proposals to Fix CUNYfirst awaiting Registrar action
  //  -----------------------------------------------------
  echo <<<EOD
      <h2>Proposals to Fix CUNYfirst awaiting Registrar action</h2>

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
      <nav>
        <a href='../Proposals'>Track Proposals</a>
        <a href='../Model_Proposals'>Guidelines</a>
        <a href='../Proposal_Manager'>Manage Proposals</a>
        <a href='../Syllabi'>Syllabi</a>
        <a href='../Reviews'>Reviews</a>
        $review_link
      </nav>
      <nav>
        <a href='.'>Admin</a>
        <a href='event_editor.php'>Event Editor</a>
        <a href='review_status.php' class='current-page'>Review Status</a>
        <a href='need_revision.php'>Pending Revision</a>
      </nav>
    </div>

EOD;
  }

?>
  </body>
</html>

