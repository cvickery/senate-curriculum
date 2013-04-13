<?php  /* Admin/need_revision.php */

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
    <title>Need Revision</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/need_revision.css" />
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
  <div>
    <h1>Proposals Pending Revision</h1>
    $dump_if_testing

EOD;


  //  List proposals reviewed but the review is not 'Accept'
  $query = <<<EOD

SELECT    p.id                                AS id,
          t.abbr                              AS type,
          p.discipline||' '||p.course_number  AS course,
          p.submitter_name                    AS submitter,
          p.submitter_email                   AS email,
          p.submitted_date                    AS proposal_date,
          r.submitted_date                    AS review_date,
          r.recommendation                    AS recommendation,
          r.reviewer_email                    AS reviewer_email
FROM      proposals p,
          proposal_types t,
          reviews r
WHERE     p.submitted_date IS NOT NULL
AND       p.closed_date IS NULL
AND       p.id = r.proposal_id
AND       p.type_id = t.id
AND       (r.submitted_date IS NULL OR r.recommendation != 'Accept')
ORDER BY  p.id

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
          <th>Recommendation</th>
          <th>Reviewer</th>
          <th>Submitter</th>
          <th>Email</th>
        </tr>

EOD;
    $num_proposals = 0;
    $missing_obsolete = array();
    $this_id = 0;
    $csv =  "Proposal, Type, Course, Submitted, Reviewed, Recommendation, Reviewer, " .
            "Submitter, Email\r\n";
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
      $recommendation     = $row['recommendation'];
      $reviewer           = $row['reviewer_email'];
      preg_match('/\.(.+)@/', $row['reviewer_email'], $matches);
      if (count($matches) == 2)
      {
        $reviewer = ucfirst(strtolower($matches[1]));
      }
      if ( ($proposal_date > $review_date)
            ||
            ($recommendation === 'None')
            ||
         ($recommendation === null)

         )
      {
        $highlight_row = " class='need-review'";
        if (isset($missing_obsolete[$reviewer]))
        {
          $missing_obsolete[$reviewer]++;
        }
        else
        {
          $missing_obsolete[$reviewer] = 1;
        }
      }
      else $highlight_row = '';
      echo <<<EOD
        <tr$highlight_row>
          <td><a href='../Reviews#$id'>$id</a></td>
          <td>{$row['type']}</td>
          <td>{$row['course']}</td>
          <td>$proposal_date_str</td>
          <td>$review_date_str</td>
          <td>$recommendation</td>
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
      $csv .= "\"$recommendation\",";
      $csv .= "\"$reviewer\",";
      $csv .= "\"{$row['submitter']}\",";
      $csv .= "\"{$row['email']}\"\r\n";

    }
    echo "      </table>\n";
    if (count($missing_obsolete) > 0)
    {
      ksort($missing_obsolete);
      echo <<<EOD
      <p><table>
        <tr><th>Reviewer</th><th>Reviews Needed</th></tr>

EOD;
      foreach ($missing_obsolete as $reviewer => $count)
      {
        echo <<<EOD
        <tr><td>$reviewer</td><td>$count</td></tr>

EOD;
      }
      echo "      </table></p>\n";
    }
    echo <<<EOD
      <form action='../scripts/download_csv.php' method='post'>
        <input type='hidden' name='form-name' value='csv' />
        <button type='submit'>Save CSV</button>
       </form>

EOD;
    $_SESSION['csv'] = $csv;
    $_SESSION['csv_name'] = 'need_revision';

?>
    </div>
  </body>
</html>
