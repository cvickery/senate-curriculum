<?php  /* Reviews/index.php */

set_include_path(get_include_path() . PATH_SEPARATOR . '../scripts' );
require_once('init_session.php');

//  Here beginneth the web page
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
    <title>Proposal Reviews</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/reviews.css" />
  </head>
  <body>
    <h1>Proposal Reviews</h1>
<?php
  echo $dump_if_testing;

  //  Handle the logging in/out situation here
  $last_login           = '';
  $status_msg           = 'Not signed in';
  $person               = '';
  $sign_out_button      = '';
  $is_geac              = FALSE;
  $public_comments_th   = "<th>Comments</th>\n";
  $private_comments_th  = '';
  require_once('short-circuit.php');
  require_once('login.php');
  if (isset($_SESSION[session_state]) && $_SESSION[session_state] === ss_is_logged_in)
  {
    if (isset($_SESSION[person]))
    {
      $person = unserialize($_SESSION[person]);
      if (isset($person->affiliations['GEAC']))
      {
        $is_geac              = true;
        $public_comments_th   = "<th>Public Comments</th>\n";
        $private_comments_th  = "<th>Private Comments</th>\n";
      }
    }
    else
    {
      die("<h1 class='error'>Browse Proposals: Invalid login state</h1></body></html>");
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
  }
  ?>
<?php
    if ($person)
    {

      echo <<<EOD
    <h2>All Reviews</h2>
    <div>

EOD;
        $proposals = array();
        $query = <<<EOD
  SELECT reviews.*,
         proposals.discipline,
         proposals.course_number,
         proposal_types.abbr      designation
    FROM reviews, proposals, proposal_types
   WHERE proposals.submitted_date IS NOT NULL
     AND reviews.submitted_date IS NOT NULL
     AND reviews.proposal_id  = proposals.id
     AND proposal_types.id    = proposals.type_id
ORDER BY proposal_id
EOD;
        $result = pg_query($curric_db, $query) or
          die("<p class='error'>Query Failed at: " .
            __FILE__ . ' ' . __LINE__ . "</p></div></body></html>\n");
        $this_id = '';
        $alphabet = 'ABCDEFGHIJKLNOPQRSTUVWXYZ';
        while ($row = pg_fetch_assoc($result))
        {
          $proposal_id = $row['proposal_id'];
          $target = '';
          if ($proposal_id !== $this_id)
          {
            $this_id = $proposal_id;
            $reviewer_count = 0;
            $target = " id='$this_id'";
          }
          $reviewer = 'GEAC-' . $alphabet[$reviewer_count++];
          if ($is_geac)
          {
            $reviewer_str = $row['reviewer_email'];
            $initial = strtoupper($reviewer_str[0]);
            $lname =
              substr( $reviewer_str,
                      strpos($reviewer_str, '.') + 1,
                      strpos($reviewer_str, '@') - 1 - strpos($reviewer_str, '.'));
            $lname[0] = strtoupper($lname[0]);
            $reviewer = $initial . '. ' . $lname;
          }
          $recommendation_date = 'Not yet';
          if ($row['submitted_date'] !== null)
          {
            $recommendation_date = substr($row['submitted_date'], 0, 10);
          }
          $course = "{$row['discipline']} {$row['course_number']}";
          $designation = $row['designation'];
          $recommendation = $row['recommendation'];
          $private_comments_td = '';
          if ($is_geac)
          {
            $private_comments_td = "<td>{$row['private_comments']}</td>\n";
          }
          $proposal = <<<EOD
      <tr$target>
        <th><a href='../Proposals?id=$proposal_id'>$proposal_id</a></th>
        <td>$course</td>
        <td>$designation</td>
        <td>$reviewer</td>
        <td>
          $recommendation_date<br />$recommendation
        </td>
        <td>
          {$row['public_comments']}
        </td>
        $private_comments_td
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
            <th>Proposal ID</th>
            <th>Course</th>
            <th>Area</th>
            <th>Reviewer</th>
            <th>Recommend</th>
            $public_comments_th
            $private_comments_th
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
        There are no reviews.
      </p>
    </div>

EOD;
      }
    }

    //  Status/Nav Bars
    //  =================================================================================
    /*  Generated here, after login status is determined, but displayed up top by the
     *  wonders of CSS.
     */
    //  First row link to Review Editor depends on the user having something to review
    $review_link = '';
    if ($person && $person->has_reviews)
    {
      $review_link = "<a href='../Review_Editor'>Edit Reviews</a>";
    }
    echo <<<EOD
    <div id='status-bar'>
      $sign_out_button
      <div id='status-msg' title='$last_login'>
        $status_msg
      </div>
      <!-- Navigation -->
      <nav>
        <a href='../Proposals'>Browse Proposals</a>
        <a href='../Model_Proposals'>Model Proposals</a>
        <a href='../Proposal_Editor'>Proposal Editor</a>
        <a href='../Syllabi'>Browse Syllabi</a>
        <a href='../Reviews'>Browse Reviews</a>
        $review_link
      </nav>
    </div>

EOD;

?>
  </body>
</html>
