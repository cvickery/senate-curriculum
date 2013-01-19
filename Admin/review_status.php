<?php  /* Admin/need_revision.php */

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
    <title>Review Status</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/review_status.css" />
  </head>
  <body>
    <h1>Review Status</h1>
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


  //  Display missing/backdated reviews
    $query = <<<EOD
   SELECT * FROM review_status

EOD;
    $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query Failed: " .
        pg_last_error($curric_db) . ': ' . basename(__FILE__) . ' ' . __LINE__ .
        '</h1></body></html>');
    if (($num = pg_num_rows($result)) < 1)
    {
      echo "<h2>All reviews are available and up to date</h2>\n";
    }
    else
    {
      $csv =  '"Proposal","Type","Course","Proposal Date","Assigned",' .
              '"Reviewed", "Recommended", "Reviewer"';
      echo <<<EOD
      <h2>$num Missing or Oboslete Reviews</h2>
      <table>
        <tr>
          <th>Proposal</th>
          <th>Type</th>
          <th>Course</th>
          <th>Proposal Date</th>
          <th>Assigned</th>
          <th>Reviewed</th>
          <th>Recommendation</th>
          <th>Reviewer</th>
        </tr>

EOD;
      while ($row = pg_fetch_assoc($result))
      {
        $id       = $row['proposal'];
        $proposed = $row['proposal_date'];
        $assigned = $row['assigned_date'];
        $reviewed = $row['reviewed_date'];
        echo <<<EOD
        <tr>
          <td><a href='../Reviews#$id'>$id</a></td>
          <td>{$row['type']}</td>
          <td>{$row['course']}</td>
          <td>$proposed</td>
          <td>$assigned</td>
          <td>$reviewed</td>
          <td>{$row['recommendation']}</td>
          <td>{$row['reviewer']}</td>
        </tr>

EOD;

      }
      echo "      </table>\n";
    }

    //  Proposals lacking reviews
    //  -------------------------------------------------------------------------------
    $query = <<<EOD
SELECT    p.id                                    AS proposal_id,
          p.discipline||' '||p.course_number      AS course,
          t.abbr                                  AS type,
          to_char(p.submitted_date, 'YYYY-MM-DD') AS submitted_date,
					p.submitter_email
FROM      proposals p, proposal_types t
WHERE     p.id > 160
AND       p.submitted_date IS NOT NULL
AND       t.id = p.type_id
AND       p.agency_id = (SELECT id FROM agencies WHERE abbr = 'GEAC')
AND       p.id NOT IN (SELECT proposal_id FROM reviews)
ORDER BY  p.id;

EOD;
    $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query Failed: " .
        basename(__FILE__) . ' ' . __LINE__) . "</h2></body></html>\n";
    if (($num = pg_num_rows($result)) < 1)
    {
      echo "<h2>All GEAC proposals have been assigned to reviewers</h2>\n";
    }
    else
    {
      echo <<<EOD
      <h2>$num GEAC proposals not yet assigned to reviewers</h2>
      <table>
        <tr>
          <th>Proposal</th>
          <th>Course</th>
          <th>Type</th>
          <th>Submitted</th>
					<th>Submitter</th>
        </tr>

EOD;
      while ($row = pg_fetch_assoc($result))
      {
				$proposal_id = $row['proposal_id'];
        echo <<<EOD
        <tr>
          <td><a href="../Proposals?id=$proposal_id">$proposal_id</a></td>
          <td>{$row['course']}</td>
          <td>{$row['type']}</td>
          <td>{$row['submitted_date']}</td>
					<td>{$row['submitter_email']}</td>
        </tr>

EOD;
      }
      echo "      </table>\n";
    }
  }

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
        <a href='../Proposal_Editor'>Manage Proposals</a>
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

?>
  </body>
</html>

