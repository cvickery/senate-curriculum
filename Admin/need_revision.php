<?php  /* Admin/need_revision.php */

set_include_path(get_include_path() . PATH_SEPARATOR . '../scripts' );
require_once('init_session.php');

//  Nicknames for reviewers
//  TODO: Simply extract first name from email address and capitalize.
$reviewers = array(
    'lesle' => 'Leslee',
    'pablo' => 'Pablo',
    'megha' => 'Meghan',
    'chris' => 'Chris',
    'natha' => 'Nathalia',
    'kathe' => 'Kate',
    'eugen' => 'Eugenia',
    'alexa' => 'Alex');


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
    <title>Need Revision</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/need_revision.css" />
  </head>
  <body>
    <h1>Proposals Pending Revision</h1>
<?php
  echo $dump_if_testing;

  //  Handle the logging in/out situation here
  //  TODO: because this is not the index page, users will be taken to that if
  //  this page finds they need to login.
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
      die("<h1 class='error'>Need Revisions: Invalid login state</h1></body></html>");
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


  //  List proposals submitted, but listed as needing revisions.
    $query = <<<EOD

   SELECT proposals.id                                        AS id,
          proposal_types.abbr                                 AS type,
          proposals.discipline||' '||proposals.course_number  AS course,
          proposals.submitter_name                            AS submitter,
          proposals.submitter_email                           AS email,
          proposals.submitted_date                            AS proposal_date,
          reviews.submitted_date                              AS review_date,
          reviews.reviewer_email                              AS reviewer_email
     FROM proposals, proposal_types, reviews
    WHERE proposals.submitted_date IS NOT NULL
      AND proposals.id = reviews.proposal_id
      AND proposals.type_id = proposal_types.id
      AND reviews.recommendation = 'Revise'
 ORDER BY proposals.id

EOD;
    $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query Failed: " .
        pg_last_error($curric_db) . ': ' . basename(__FILE__) . ' ' . __LINE__ .
        '</h1></body></html>');
    echo <<<EOD

      <table>
        <tr>
          <th>id</th>
          <th>Type</th>
          <th>Course</th>
          <th>Submitted</th>
          <th>Reviewed</th>
          <th>Reviewer</th>
          <th>Submitter</th>
          <th>Email</th>
        </tr>

EOD;
    $num_proposals = 0;
    $this_id = 0;
    $csv="Proposal, Type, Course, Submitted, Reviewed, Reviewer, Submitter, Email\r\n";
    while ($row = pg_fetch_assoc($result))
    {
      $id = $row['id'];
      if ($id != $this_id)
      {
        $this_id = $id;
        $num_proposals++;
      }
      $proposal_date      = new DateTime($row['proposal_date']);
      $proposal_date_str  = $proposal_date->format('Y-m-d');
      $review_date        = new DateTime($row['review_date']);
      $review_date_str    = $review_date->format('Y-m-d');
      $reviewer           = $reviewers[substr($row['reviewer_email'], 0, 5)];
      if ($proposal_date > $review_date)
      {
        $backwards = " class='need-review'";
      }
      else $backwards = '';
      echo <<<EOD
        <tr$backwards>
          <td><a href='../Reviews#$id'>$id</a></td>
          <td>{$row['type']}</td>
          <td>{$row['course']}</td>
          <td>$proposal_date_str</td>
          <td>$review_date_str</td>
          <td>$reviewer</td>
          <td>{$row['submitter']}</td>
          <td>{$row['email']}</td>
        </tr>

EOD;
      $csv .= "\"$id\",";
      $csv .= "\"{$row['type']}\",";
      $csv .= "\"{$row['course']}\",";
      $csv .= "\"$proposal_date_str\",";
      $csv .= "\"$review_date_str\",";
      $csv .= "\"$reviewer\",";
      $csv .= "\"{$row['submitter']}\",";
      $csv .= "\"{$row['email']}\"\r\n";

    }
    echo <<<EOD
      </table>
      <form action='../scripts/download_csv.php' method='post'>
        <input type='hidden' name='form-name' value='csv' />
        <button type='submit'>Save CSV</button>
       </form>

EOD;
    $_SESSION['csv'] = $csv;
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
        $num_proposals proposals need revision.
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
        <a href='review_status.php'>Review Status</a>
        <a href='need_revision.php' class='current-page'>Pending Revision</a>
      </nav>
    </div>

EOD;

?>
  </body>
</html>
