<?php
//  Review_Editor/index.php
set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() . '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');

//  For checking proposals updated after review.
$one_hour = new DateInterval('PT1H');

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
    <title>Proposal Review Editor</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/review_editor.css" />
    <style type="text/css">
      td, th {font-size: 0.9em;}
    </style>
    <script type='application/javascript' src='../js/jquery.min.js'></script>
    <script type='application/javascript' src="../js/site_ui.js"></script>
    <script type='application/javascript' src='../js/review_editor.js'></script>
  </head>
  <body>
<?php
  //  Status Bar and H1 element
  $status_msg = login_status();
  $nav_bar    = site_nav();
  echo <<<EOD
  <div id='status-bar'>
    $instructions_button
    $status_msg
    $nav_bar
  </div>
  <div>
    <h1>Proposal Review Editor</h1>
    $dump_if_testing

EOD;
  if ($form_name === 'update-reviews')
  {
    //  Update db if new data were posted.
    if (isset($_POST))
    {
      $which_proposal_id = $_POST['save-review-id'];
      foreach ($_POST as $name => $value)
      {
        //  Name is 'field-proposal_id'
        preg_match('/^(\S+)-(\d+)$/', $name, $matches);
        if (count($matches) === 3)
        {
          $proposal_id = $matches[2];
          if ($proposal_id === $which_proposal_id)
          {
            $field = $matches[1];
            $value = sanitize(trim($value));
            $query = <<<EOD
  UPDATE reviews
     SET $field = '$value', submitted_date = now()
   WHERE proposal_id = $proposal_id
     AND lower(reviewer_email) = lower('{$person->email}')

EOD;
            $result = pg_query($curric_db, $query) or
              die("<p class='error'>Update failed</p></body></html>\n");
            $n = pg_affected_rows($result);
            if ($n !== 1)
              die("<p class='error'>Updated $n rows</p></body></html>\n");
          }
        }
      }
    }
  }
?>
<!--

Reviews get assigned to email addresses, so visitors to this page will see "no review
assignments" unless they sign in with an email address that has reviews assigned to it.

Reviewers and all GEAC members have to have (verified) passwords so imposters can't see
their private notes.

Reviewers need to be able to edit their reviews. Open question is whether to maintain a
review history. I think so.

Assignments:
  Reviewer (email) | Proposal # | Assigment Date | Submitted Date | Action | Public Comments
  | Private Comments
  * Dates are timestamps that get displayed as dates
  * Action must be  Accept, Revise, Reject
  * Multiple submitted dates means the review was revised

  -->
<?php
  if ($person)
  {
    echo <<<EOD
    <h2>Your Reviews</h2>
    <div>

EOD;
    $proposals = array();
    $query = <<<EOD
  SELECT *
    FROM reviews
   WHERE lower(reviewer_email) = lower('{$person->email}')
     AND proposal_id IN (SELECT id
                           FROM proposals
                          WHERE submitted_date IS NOT NULL
                            AND closed_date IS NULL)

ORDER BY proposal_id
EOD;
    $result = pg_query($curric_db, $query) or
      die("<p class='error'>Query Failed at: " .
        __FILE__ . ' ' . __LINE__ . "</p></div></body></html>\n");
    while ($row = pg_fetch_assoc($result))
    {
      $proposal_id = $row['proposal_id'];
      $proposal_query = <<<EOD
  SELECT proposals.discipline||' '||proposals.course_number AS course,
         proposals.submitted_date,
         proposal_types.abbr
    FROM proposals, proposal_types
   WHERE proposals.id = $proposal_id
     AND proposal_types.id = proposals.type_id

EOD;
      $proposal_result = pg_query($curric_db, $proposal_query) or
      die("<p class='error'>Query Failed at: " .
        __FILE__ . ' ' . __LINE__ . "</p></div></body></html>\n");
      $proposal_row = pg_fetch_assoc($proposal_result);
      $proposal_info = <<<EOD
<br />{$proposal_row['course']}<br />{$proposal_row['abbr']}

EOD;
      $proposal_submitted_date = new DateTime($proposal_row['submitted_date']);
      $assigned_date = substr($row['assigned_date'], 0, 10);
      $review_submitted_date = 'Not yet';
      $need_review = " class='need-review'";
      if ($row['submitted_date'] !== null && $row['recommendation'] !== 'None')
      {
        $review_submitted_date = new DateTime($row['submitted_date']);
        if ($review_submitted_date > $proposal_submitted_date->add($one_hour))
        {
          $need_review = "";
        }
        $review_submitted_date = $review_submitted_date->format('Y-m-d');
      }
      $proposal_submitted_date = $proposal_submitted_date->format('Y-m-d');
      $selected = array(
          'None'  => '',
          'Accept' => '',
          'Revise' => '',
          'Reject' => ''
          );
      $selected[$row['recommendation']] = "selected='selected'";
      $proposal = <<<EOD
      <tr$need_review>
        <td>
          <a href='../Proposals?id=$proposal_id' target='new'>$proposal_id</a>
          $proposal_info
        </td>
        <td>$proposal_submitted_date</td>
        <td>$assigned_date</td>
        <td>$review_submitted_date</td>
        <td>
          <select name='recommendation-$proposal_id'>
            <option value='None'  {$selected['None']}>None</option>
            <option value='Accept' {$selected['Accept']}>Accept</option>
            <option value='Revise' {$selected['Revise']}>Revise</option>
            <option value='Reject' {$selected['Reject']}>Reject</option>
          </select>
        </td>
        <td>
          <textarea name='public_comments-$proposal_id'>{$row['public_comments']}</textarea>
        </td>
        <td>
          <textarea name='private_comments-$proposal_id'>{$row['private_comments']}</textarea>
        </td>
        <td>
          <button name='save-review-id' value='$proposal_id' type='submit'>Save</button>
        </td>
      </tr>
EOD;
      $proposals[] = $proposal;
    }
    if (count($proposals) > 0)
    {
      echo <<<EOD
      <form action='.' method='post'>
        <input type='hidden' name='form-name' value='update-reviews' />
        <table>
          <tr>
            <th>Proposal</th>
            <th>Proposal Submitted</th>
            <th>Review Assigned</th>
            <th>Review Submitted</th>
            <th>Recommend</th>
            <th>Public Comments</th>
            <th>Private Comments</th>
            <th> </th> <!-- Save buttons -->
          </tr>

EOD;
      foreach ($proposals as $proposal)
      {
        echo "$proposal\n";
      }
      echo <<<EOD
        </table>
      </form>
    </div>

EOD;
    }
    else
    {
      echo <<<EOD
      <p>
        You have no proposals to review
      </p>
    </div>

EOD;
    }
  }
?>
    </div>
  </body>
</html>

