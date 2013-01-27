<?php  /* Proposals/index.php */

set_include_path(get_include_path() . PATH_SEPARATOR . '../scripts' );
require_once('init_session.php');
require_once('syllabus_utils.php');
require_once('simple_diff.php');
require_once('tracking_utils.php');
require_once('display_proposal.php');

require_once('../include/atoms.inc');

//  Set up page title
$proposal_id    = '';
$discipline     = '';
$course_number  = '';
$page_title   = 'Curriculum Proposals';

//  Alter page title if the id string was provided.
if (isset($_GET['id']))
{
  //  Decide whether the value is a proposal ID or a discipline-number pair
  $proposal_id = sanitize($_GET['id']);
  if (is_numeric($proposal_id))
  {
    if ($proposal_id > MAX_PLAS_ID)
    {
      $page_title = "Curriculum Proposal #$proposal_id";
    }
    else
    {
      $proposal_id = '';
    }
  }
  else
  {
    //  See if the "id" is a valid course string
    preg_match('/^\s*([a-z]+)[ -]*(\d+[wh]?)\s*$/i', $proposal_id, $matches);
    if (count($matches) === 3)
    {
      //  Syntax okay. No need to check for valid discipline 'cause we will be
      //  doing a query on that, which can return zero matches
      $discipline = strtoupper($matches[1]);
      $course_number = strtoupper($matches[2]);
      $page_title = "Proposals for $discipline-$course_number";
    }
    //  Whether it was a valid course string or not, it isn't an id number.
    $proposal_id = '';
  }
}

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
    <title><?php echo $page_title; ?></title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/proposals.css" />
  </head>
  <body>

<?php
  //  Handle the logging in/out situation here
  $last_login       = '';
  $status_msg       = 'Not signed in';
  $person           = '';
  $sign_out_button  = '';
  require_once('short-circuit.php');
  require_once('login.php');
  if (isset($_SESSION[session_state]) && $_SESSION[session_state] === ss_is_logged_in)
  {
    if (isset($_SESSION[person]))
    {
      $person = unserialize($_SESSION[person]);
    }
    else
    {
      die("<h1 class='error'>Proposals: Invalid login state</h1></body></html>");
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

  //  Initialize display options.
  //  -----------------------------------------------------------------------------------
  $class_id = '';
  $type_abbr = '';
  $discp_abbr = '';

  //  Display By ID
  //  ===============================================================================
  /*  If GET[id] is set, it could be either an id or a course.
   *    proposal_id not empty:  one proposal
   *    discipline not empty:   all proposals for one course
   *    otherwise:              invalid id or course
   */
  if (isset($_GET['id']))
  {
    if ($proposal_id !== '')
    {
      display_proposal($proposal_id);
    }
    else if ($discipline !== '')
    {
      //  Display table of proposals for this course, or just the propsal if there is only
      //  one.
      $max_plas_id = MAX_PLAS_ID;
      $query = <<<EOD
  SELECT  p.*,
          t.full_name
  FROM    proposals       p,
          proposal_types  t
  WHERE   p.discipline    = '$discipline'
  AND     p.course_number = '$course_number'
  AND     t.id            = p.type_id
  AND     p.id            > $max_plas_id

EOD;
      $result = pg_query($curric_db, $query) or die( 'Unable to access proposals: ' .
          pg_last_error($curric_db) . ' at ' . basename(__FILE__) . ' ' . __LINE__);
      $num_proposals = pg_num_rows($result);
      switch ($num_proposals)
      {
        case 0:
          echo <<<EOD
  <h1 class='error'>No proposals found for $discipline-$course_number</h1>

EOD;
          break;
        case 1:
          $row = pg_fetch_assoc($result);
          display_proposal($row['id']);
          break;
        default:
          //  Multiple proposals for the course: display a selection table.
          $row = pg_fetch_assoc($result);
          $course = unserialize($row['cur_catalog']);
          $course_title = $course->course_title;
          echo <<<EOD
      <h1>$num_proposals Proposals for $discipline-$course_number: $course_title</h1>
      <table class='sumary'>
        <tr>
          <th>Proposal ID</th>
          <th>Proposal Type</th>
          <th>Submitted Date</th>
          <th>Submitted By</th>
        </tr>

EOD;
          do
          {
            $id = $row['id'];
            $submitted_date = new DateTime($row['submitted_date']);
            $submitted_date_str = $submitted_date->format('F j, Y');
            echo <<<EOD
        <tr>
          <td><a href='./?id=$id'>$id</a></td>
          <td>{$row['full_name']}</td>
          <td>$submitted_date_str</td>
          <td>{$row['submitter_name']} ({$row['submitter_email']})</td>
        </tr>

EOD;
          } while ($row = pg_fetch_assoc($result));
          echo "      </table>\n";
          break;
      }
    }
    else
    {
      // Unable to display anything for this "id" string.
      echo <<<EOD
      <h1 class='error'>
        “{$_GET['id']}” is not a valid proposal ID or course.
      </h1>

EOD;
    }
  }

  //  Display By Class
  //  ===============================================================================
  else if (isset($_GET['class']))
  {
    $class_id = sanitize($_GET['class']);
    if (is_numeric($class_id) && $class_id > 0)
    {
      //  Determine which types make up this class
      $query = <<<EOD
  SELECT  *
  FROM    proposal_types
  WHERE   class_id = $class_id

EOD;
      $result = pg_query($curric_db, $query) or die("<h1 class='error'>Unable to "
          . "access proposals: " . pg_last_error($curric_db) . ' ' . basename(__FILE__)
          . ' ' . __LINE__ . '</h1></body></html');
      $num_types = pg_num_rows($result);
      if ($num_types > 0)
      {
        $types = array();
        while ($row = pg_fetch_assoc($result))
        {
          $types[] = $row['abbr'];
        }
        $class_name = $proposal_classes[$class_id]['full_name'];
        $class_abbr = $proposal_classes[$class_id]['abbr'];
        echo "      <h1>$class_name ($class_abbr) Proposals</h1>\n";
        $csv = tracking_table($types, 'p.type_id, p.id');
        if ($csv === '')
        {
          echo "    <h2>No Proposals Found</h2>\n";
        }
        else
        {
          $_SESSION['csv'] = $csv;
          echo <<<EOD
        <form action='../scripts/download_csv.php' method='post'>
          <input type='hidden' name='form-name' value='csv' />
          <button type='submit'>Save CSV</button>
         </form>

EOD;
        }
      }
      else
      {
      echo <<<EOD
    <h1 class='error'>$class_id is not a valid proposal class code.</h1>

EOD;
      }
    }
    else
    {
      echo <<<EOD
      <h1 class='error'>$class_id is not a valid class of proposals.</h1>

EOD;
    }
  }
/*
      $query = <<<EOD
  SELECT proposals.*,
         cf_academic_organizations.department_name  dept_name,
         proposal_types.full_name                   proposal_type,
         proposal_classes.abbr                      class_abbr,
         proposal_classes.full_name                 class_name
    FROM proposals, cf_academic_organizations, proposal_types, proposal_classes
   WHERE proposals.type_id in (SELECT id FROM proposal_types where class_id = $class_id)
     AND dept_id = cf_academic_organizations.id
     AND type_id = proposal_types.id
     AND proposal_classes.id = (SELECT class_id FROM proposal_types WHERE id = type_id)
     AND proposals.submitted_date IS NOT NULL
ORDER BY discipline, lpad(course_number, 3), proposals.id

EOD;

      $result = pg_query($curric_db, $query)
        or die('Unable to access proposals: ' . basename(__FILE__) . ' ' . __LINE__);
      $n = pg_num_rows($result);
      if ($n === 0)
      {
        echo "<h1>No Proposals</h1>\n";
      }
      else
      {
        $class_name = $proposal_classes[$class_id]['full_name'];
        $class_abbr = $proposal_classes[$class_id]['abbr'];
        $date_heading = 'Date Submitted';
        if ($class_abbr === 'PLAS')
        {
          $date_heading = 'Senate Approved';
          $instructions = <<<EOD
  <p>
    These are “housekeeping copies” of the Perspectives proposals previously approved by
    the Senate.  The full records of actions on these proposals are at an <a
    href='../../GEAC/Proposals'>earlier GEAC web site.</a>
  </p>

EOD;
        }
        else $instructions = '';
        echo <<<EOD
      <h1>$class_name Proposals</h1>
      $instructions
      <table class='summary'>
        <tr>
          <th>ID</th>
          <th>Course</th>
          <th>Type</th>
          <th>$date_heading</th>
          <th>Submitted By</th>
        </tr>

EOD;
        while ($row = pg_fetch_assoc($result))
        {
          $proposal_id      = $row['id'];
          $opened_date      = $row['opened_date'];
          $submitted_date   = substr($row['submitted_date'], 0, 10);
          $discipline       = $row['discipline'];
          $course_number    = $row['course_number'];
          $submitter_name   = $row['submitter_name'];
          $submitter_email  = $row['submitter_email'];
          $dept_name        = $row['dept_name'];
          $proposal_type    = $row['proposal_type'];
          $class_abbr       = $row['class_abbr'];
          $class_name       = $row['class_name'];
          $cur_catalog      = unserialize($row['cur_catalog']);
          $new_catalog      = unserialize($row['new_catalog']);
          $justifications   = unserialize($row['justifications']);
          $id_link = "<a href='.?id=$proposal_id'>$proposal_id</a>";
          if (!$submitted_date)
          {
            $submitted_date = 'not submitted yet';
            $id_link        = $proposal_id;
          }
          if ($class_abbr === 'PLAS') $id_link = $proposal_id;
          echo <<<EOD
        <tr>
          <td>$id_link</td>
          <td>$discipline $course_number</td>
          <td>$proposal_type</td>
          <td>$submitted_date</td>
          <td>$submitter_name</td>
        </tr>

EOD;
        }
        echo "    </table>\n";
      }
    }
  }
 */
  else if (isset($_GET['type']))
  {
    //  Display By Type
    //  =================================================================================
    $type_abbr = sanitize($_GET['type']);
    //  Handle missing plusses from URL encoding
    $type_abbr = str_replace(' ', '+', $type_abbr);

    if ( ! isset($proposal_type_abbr2name[$type_abbr]) && ($type_abbr !== 'ALL'))
    {
      echo "<h1 class='error'>$type_abbr: Unrecognized Proposal Type</h1>\n";
    }
    else
    {
      if ($type_abbr === 'ALL')
      {
        $type_abbr = array_keys($proposal_type_abbr2name);
        echo "      <h1>All Proposal Types</h1>\n";
      }
      else
      {
        $type_name = $proposal_type_abbr2name[$type_abbr];
        echo <<<EOD
      <h1>$type_name ($type_abbr) Proposals</h1>

EOD;
      }
      //  Determine sorting parameters
      $byid_selected  = $bycourse_selected
                      = $bytype_selected
                      = $bydate_selected
                      = $byname_selected
                      = $direction_checked
                      = $direction
                      = '';
      if (isset($_GET['orderby']))
      {
        switch ($_GET['orderby'])
        {
          case 'id':
            $order_by = 'p.id';
            $byid_selected = "checked='checked'";
            break;
          case 'submitted':
            $order_by = 'p.submitted_date, p.id';
            $bydate_selected = "checked='checked'";
            break;
          case 'person':
            $order_by = "substring(p.submitter_name from ' [A-Za-záñ]*$'), p.id";
            $byname_selected = "checked='checked'";
            break;
          case 'type':
            $order_by = 'p.type_id, p.id';
            $bytype_selected = "checked='checked'";
            break;
          case 'course':
          default:
            //  Invalid order defaults to 'course'
            $order_by = 'p.discipline||lpad(p.course_number, 3), p.id';
            $bycourse_selected = "checked='checked'";
            break;
        }
      }
      else
      {
        $order_by = 'p.discipline||lpad(p.course_number, 3), p.id';
        $bycourse_selected = "checked='checked'";
      }
      if ( ! is_array($type_abbr))
      {
        $bytype_selected = "disabled='disabled'";
      }
      if (isset($_GET['direction']))
      {
        $direction = 'DESC';
        $direction_checked = " checked='checked'";
      }
        echo <<<EOD
      <form action='.' method='get'>
        <input type='hidden' name='type' value='{$_GET['type']}' />
          <fieldset><legend>Display Order</legend>
            <div>
              <input  type='radio'
                      id='orderby-id'
                      name='orderby'
                      value='id'
                      $byid_selected />
              <label for='orderby-id'>ID</label>
            </div>
            <div>
              <input  type='radio'
                      id='orderby-type'
                      name='orderby'
                      value='type'
                      $bytype_selected />
              <label for='orderby-type'>Type</label>
            </div>
            <div>
              <input  type='radio'
                      id='orderby-course'
                      name='orderby'
                      value='course'
                      $bycourse_selected />
              <label for='orderby-course'>Course</label>
            </div>
            <div>
              <input  type='radio'
                      id='orderby-person'
                      name='orderby'
                      value='person'
                      $byname_selected />
              <label for='orderby-person'>Submitted By</label>
            </div>
            <div>
              <input  type='radio'
                      id='orderby-submitted'
                      name='orderby'
                      value='submitted'
                      $bydate_selected />
              <label for='orderby-submitted'>Date Submitted</label>
            </div>
            <div class='checkboxes'>
              <input type='checkbox' name='direction' id='direction' $direction_checked />
              <label for='direction'>Descending</label>
            </div>
            <div><button type='submit'>Reorder</button></div>
          </fieldset>
      </form>

EOD;
      $csv = tracking_table($type_abbr, "$order_by $direction");
      if ($csv === '')
      {
        echo "    <h2>No $type_abbr Proposals Found</h2>\n";
      }
      else
      {
        $_SESSION['csv'] = $csv;
        echo <<<EOD
      <form action='../scripts/download_csv.php' method='post'>
        <input type='hidden' name='form-name' value='csv' />
        <button type='submit'>Save CSV</button>
       </form>

EOD;
      }
    }
  }
  else if (isset($_GET['discp']))
  {
    //  Display by Discipline
    //  =================================================================================
    $discp_abbr = sanitize($_GET['discp']);
    if ( ! isset($disciplines[$discp_abbr]))
    {
      echo "<h1 class='error'>Unrecognized Discipline Abbreviation</h1>\n";
    }
    else
    {
      $discipline_name = $disciplines[$discp_abbr]['discipline_full_name'];
      echo <<<EOD
  <h1>Proposals for $discipline_name ($discp_abbr) Courses</h1>

EOD;
      $query = <<<EOD
  SELECT proposals.*,
         cf_academic_organizations.department_name  dept_name,
         proposal_types.full_name                   proposal_type,
         proposal_classes.abbr                      class_abbr,
         proposal_classes.full_name                 class_name
    FROM proposals, cf_academic_organizations, proposal_types, proposal_classes
   WHERE proposals.discipline = '$discp_abbr'
     AND dept_id = cf_academic_organizations.id
     AND type_id = proposal_types.id
     AND proposal_classes.id = (SELECT class_id FROM proposal_types WHERE id = type_id)
     AND proposals.submitted_date IS NOT NULL
ORDER BY lpad(proposals.course_number, 3), proposal_classes.id

EOD;
      $result = pg_query($curric_db, $query)
        or die('Unable to access proposals: ' . basename(__FILE__) . ' ' . __LINE__);
      $n = pg_num_rows($result);
      if ($n === 0)
      {
        echo "<h2>No $discipline_name Proposals Found</h2>\n";
      }
      else
      {
       echo <<<EOD
      <table class='summary'>
        <tr>
          <th>ID</th>
          <th>Course</th>
          <th>Type</th>
          <th>Date</th>
          <th>Submitted By</th>
        </tr>

EOD;
        while ($row = pg_fetch_assoc($result))
        {
          $proposal_id      = $row['id'];
          $opened_date      = $row['opened_date'];
          $submitted_date   = substr($row['submitted_date'], 0, 10);
          $discipline       = $row['discipline'];
          $course_number    = $row['course_number'];
          $submitter_name   = $row['submitter_name'];
          $proposal_type    = $row['proposal_type'];
          $class_abbr       = $row['class_abbr'];
          $id_link = "<a href='.?id=$proposal_id'>$proposal_id</a>";
          if (!$submitted_date)
          {
            $submitted_date = 'not submitted yet';
            $id_link        = $proposal_id;
          }
          if ($class_abbr === 'PLAS')
          {
            $id_link = $proposal_id;
            $submitted_date .= ' (Senate Approved)';
          }
          else
          {
            $submitted_date .= ' (Submitted)';
          }
          echo <<<EOD
        <tr>
          <td>$id_link</td>
          <td>$discipline $course_number</td>
          <td>$proposal_type ($class_abbr)</td>
          <td>$submitted_date</td>
          <td>$submitter_name</td>
        </tr>

EOD;
        }
        echo "    </table>\n";
      }
    }
  }

  //  Default: Display Summary
  //  ===================================================================================
  else
  {
    $query = <<<EOD
  SELECT * FROM proposals
   WHERE submitted_date IS NOT NULL
ORDER BY type_id

EOD;
    $result = pg_query($curric_db, $query)
      or die('Unable to access proposals: ' . basename(__FILE__) . ' ' . __LINE__);
    $n = pg_num_rows($result);
    $count_types        = array();
    $count_classes      = array();
    $count_discps       = array();
    $count_plas_discps  = array();
    while ($row = pg_fetch_assoc($result))
    {
      $type_name  = $proposal_type_id2abbr[$row['type_id']];
      $class_id   = $proposal_type_id2class_id[$row['type_id']];
      $class_name = $proposal_classes[$class_id]['full_name'];
      $discp      = $row['discipline'];
      if (!isset($count_types[$type_name]))     $count_types[$type_name] = 1;
      else                                      $count_types[$type_name] += 1;
      if (!isset($count_classes[$class_id]))    $count_classes[$class_id] = 1;
      else                                      $count_classes[$class_id] += 1;
      if (!isset($count_discps[$discp]))        $count_discps[$discp] = 1;
      else                                      $count_discps[$discp] += 1;
      if ($class_name === 'QC Perspectives')
      {
        if (!isset($count_plas_discps[$discp]))   $count_plas_discps[$discp] = 1;
        else                                      $count_plas_discps[$discp] += 1;
      }
    }
    echo <<<EOD
    <h1>Curriculum Proposals</h1>
    <form method='get' action='.'>
      <p>
        If you want to track a particular proposal or course, enter the proposal ID
        or course (<em>e.g., ENGL 110</em>) here:
        <input type='text' name='id' />
      </p>
    </form>
    <h2>Summary</h2>
    <p>There are $n proposals.</p>
    <p class='instructions summary'>
      Click on a Class, Type, or Discipline to get a list of the corresponding
      proposals.
    </p>
EOD;
    echo <<<EOD
    <table class='summary'>
      <col width='70%'/>
      <tr><th>Proposal Class</th><th>Number of Proposals</th></tr>

EOD;
    foreach ($count_classes as $class_id => $num)
    {
      $class_name = $proposal_classes[$class_id]['full_name'];
      echo <<<EOD
      <tr>
        <td>
          <a href='index.php?class=$class_id'>$class_name</a>
        </td>
        <td class='number-cell'>$num</td>
      </tr>

EOD;
    }
    echo <<<EOD
      </table>
      <table class='summary'>
        <col width='70%'/>
        <tr><th>Proposal Type</th><th>Number of Proposals</th></tr>

EOD;
    $sum = 0;
    foreach ($count_types as $type => $num)
    {
      $sum += $num;
      $class_id = $proposal_type_abbr2class_id[$type];
      $class_name = $proposal_classes[$class_id]['full_name'];
      echo <<<EOD
      <tr>
        <td>$class_name: <a href='./?type=$type'>$type</a></td>
        <td class='number-cell'>$num</td>
      </tr>

EOD;
      }
    echo <<<EOD
        <td>Show all: <a href='./?type=ALL'>ALL</a></td>
        <td>$sum</td>
      </table>
      <table class='summary'>
        <col width='70%'/>
        <tr><th>Discipline</th><th>Number of Proposals</th></tr>

EOD;
    ksort($count_discps);
    foreach ($count_discps as $discp => $num)
    {
      $plas_note = '';
      if (isset($count_plas_discps[$discp]))
      {
        $num -= $count_plas_discps[$discp];
        $plas_note = "plus {$count_plas_discps[$discp]} Perspectives";
      }
      echo <<<EOD
      <tr>
        <td><a href='./?discp=$discp'>$discp</a></td>
        <td class='number-cell'>$num $plas_note</td>
      </tr>

EOD;
    }
    echo "    </table>\n";
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
        <a href='.' class='current-page'>Track Proposals</a>
        <a href='../Model_Proposals'>Guidelines</a>
        <a href='../Proposal_Manager'>Manage Proposals</a>
        <a href='../Syllabi'>Syllabi</a>
        <a href='../Reviews'>Reviews</a>
        $review_link
      </nav>
    </div>

EOD;
?>
  </body>
</html>
