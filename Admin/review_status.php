<?php  /* Admin/review_status.php */

set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() .  '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');
require_once('admin.inc');                       // Must be logged in as an administrator
$login_status = login_status();

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
    <script type="application/javascript" src='../js/jquery.min.js'></script>
    <script type="application/javascript" src='../js/site_ui.js'></script>
  </head>
  <body>
<?php
  //  Generate Status Bar and Page Content
  $nav_bar = site_nav();
  $admin_nav = admin_nav();
  echo <<<EOD
  <!-- Status Bar -->
  <div id='status-bar'>
    $instructions_button
    $login_status
    $nav_bar
    $admin_nav
  </div>
  <h1>Review Status</h1>
  $dump_if_testing

EOD;


  //  Display missing/backdated reviews
  $query = <<<EOD
   SELECT * FROM review_status

EOD;
  $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query Failed: " .
      pg_last_error($curric_db) . ': ' . basename(__FILE__) . ' ' . __LINE__ .
      '</h1></body></html>');
  $reviewer_counts = array();
  if (($num = pg_num_rows($result)) < 1)
  {
    echo "<h2>All reviews are available and up to date</h2>\n";
  }
  else
  {
    echo <<<EOD
      <h2>$num Missing or Oboslete Reviews</h2>
      <table>
        <tr>
          <th>Proposal</th>
          <th>Type</th>
          <th>Course</th>
          <th>Proposal Date</th>
          <th>Assigned Date</th>
          <th>Review Date</th>
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
      $reviewer = $row['reviewer'];
      echo <<<EOD
        <tr>
          <td><a href='../Reviews#$id'>$id</a></td>
          <td>{$row['type']}</td>
          <td>{$row['course']}</td>
          <td>$proposed</td>
          <td>$assigned</td>
          <td>$reviewed</td>
          <td>{$row['recommendation']}</td>
          <td>$reviewer</td>
        </tr>

EOD;
      if (isset($reviewer_counts[$reviewer]))
      {
        $reviewer_counts[$reviewer]++;
      }
      else
      {
        $reviewer_counts[$reviewer] = 1;
      }

    }
    echo "      </table>\n";
  }
  if (count($reviewer_counts) > 0)
  {
    ksort($reviewer_counts);
    //  Display number of missing obsolete reviews for each reviewer
    echo <<<EOD
      <table>
        <tr>
          <th>Reviewer</th>
          <th>Reviews Needed</th>
        </tr>

EOD;
    foreach ($reviewer_counts as $reviewer => $count)
    {
      echo "        <tr><td>$reviewer</td><td>$count</td></tr>\n";
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
AND       p.closed_date IS NULL
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

?>
  </body>
</html>

