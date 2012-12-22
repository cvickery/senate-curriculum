<?php  /* Proposals/index.php */

set_include_path(get_include_path() . PATH_SEPARATOR . '../scripts' );
require_once('init_session.php');
require_once('syllabus_utils.php');
require_once('simple_diff.php');
require_once('tracking_utils.php');

require_once('../Proposal_Editor/include/atoms.inc');

$proposal_id  = '';
$page_title   = 'Curriculum Proposals';
if (isset($_GET['id']))
{
  $proposal_id = sanitize($_GET['id']);
  if ($proposal_id < 161)
  {
    $proposal_id = '';
  }
  else
  {
    $page_title = "Curriculum Proposal #$proposal_id";
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

  //  Initialize display options.
  //  -----------------------------------------------------------------------------------
  $id = '';
  $class_id = '';
  $type_abbr = '';
  $discp_abbr = '';

  if (isset($_GET['id']))
  {
    //  Display by ID
    //  ===============================================================================
    /*  If GET[id] is set, display the proposal if it is valid.
     */
    $id = sanitize($_GET['id']);
    if ($id < 161)
    {
      //  Ignore requests to view old system proposals.
      $id = '';
      echo <<<EOD
<h1 class='error'>
  “{$_GET['id']}” is not a valid proposal ID number.
</h1>

EOD;
    }
    $query = <<<EOD
  SELECT proposals.*,
         cf_academic_organizations.department_name  dept_name,
         proposal_types.full_name                   proposal_type,
         proposal_types.abbr                        proposal_abbr,
         proposal_classes.abbr                      proposal_class
    FROM proposals, cf_academic_organizations, proposal_types, proposal_classes
   WHERE proposals.id = $id
     AND dept_id = cf_academic_organizations.id
     AND type_id = proposal_types.id
     AND proposal_classes.id = (SELECT class_id FROM proposal_types WHERE id = type_id)

EOD;

    $result = pg_query($curric_db, $query)
      or die('Unable to access proposals: ' . basename(__FILE__) . ' ' . __LINE__);
    $n = pg_num_rows($result);
    if ($n !== 1)
    {
      echo <<<EOD
  <h1 class='error'>Error accessing Proposal #$id</h1>
  <p>$n records found</p>
EOD;
    }
    else
    {
      $row                = pg_fetch_assoc($result);
      $opened_date        = $row['opened_date'];
      $submitted_date     = $row['submitted_date'];
      $closed_date        = $row['closed_date'];
      $dept_approval_date = $row['dept_approval_date'];
      $dept_approval_name = $row['dept_approval_name'];
      $discipline         = $row['discipline'];
      $course_number      = $row['course_number'];
      $submitter_name     = $row['submitter_name'];
      $submitter_email    = $row['submitter_email'];
      $dept_name          = $row['dept_name'];
      $proposal_type      = $row['proposal_type'];
      $proposal_abbr      = $row['proposal_abbr'];
      $proposal_class     = $row['proposal_class'];
      $cur_catalog        = unserialize($row['cur_catalog']);
      $new_catalog        = unserialize($row['new_catalog']);
      $justifications     = unserialize($row['justifications']);
      $opened_date_obj    = new DateTime($opened_date);
      $opened_date_str    = $opened_date_obj->format('F j, Y');
      $submitted_status_1 = "created on $opened_date_str";
      $submitted_status_2 = ", but has not yet been submitted";
      if ($submitted_date)
      {
        $submitted_date_obj = new DateTime($submitted_date);
        $submitted_date_str = $submitted_date_obj->format('F j, Y');
        $submitted_status_1 = "submitted on $submitted_date_str";
        $submitted_status_2 = '';
      }
      $course             = new Course($cur_catalog);
      $catalog_info       = $course->toHTML(with_approvals);
      if ($course->course_id === 0)
      {
        $catalog_info = <<<EOD
  <p>
    When this proposal was created, $discipline $course_number was not an available course
    in CUNYfirst. (The latest catalog data was from {$cur_catalog->cf_info_date} at that
    time.)
  </p>
  <p>
    Look in the “Other Proposals” section below for a separate proposal that should
    provide the catalog information for this course.
  </p>

EOD;
      }
      echo <<<EOD
  <h1>Proposal #$id<br/>$discipline $course_number: $proposal_type</h1>

EOD;
      $is_new = ($proposal_abbr === 'NEW-U' || $proposal_abbr === 'NEW-G');
      if (! $is_new)
      {
        echo <<<EOD
  <h2>CUNYfirst Catalog Information</h2>
  <fieldset>
    $catalog_info
  </fieldset>

EOD;
      }
      if ($proposal_class === 'Course')
      {
        $revised = new Course($new_catalog);
        $revised_info = $revised->toHTML();
        echo <<<EOD
  <h2>Proposed Catalog Information</h2>
  <fieldset>
  $revised_info
  </fieldset>

EOD;
        if (! $is_new)
        {
          echo <<<EOD
  <fieldset><legend>Catalog Differences</legend>

EOD;
        echo Course::diffs_to_html($course, $revised);
        echo "  </fieldset>\n";
        }
      }

      //  Display department approval information if required for this type of proposal
      //  ------------------------------------------------------------------------------
      if (in_array($proposal_abbr, $require_dept_approval))
      {
        echo "<h2>Department Approval</h2>\n";
        //  It's possible that the proposal hasn't been submitted yet, so the approval
        //  information might not be available yet.
        if ($dept_approval_date)
        {
          if ($dept_approval_date === 'Pending')
          {
            echo <<<EOD
  <p class='warning detail-info'>
    Department Approval is Pending
  </p>
EOD;
          }
          else
          {
            $approval_date = strtotime($dept_approval_date);
            if ($approval_date)
            {
              echo <<<EOD
  <p class='detail-info'>
    $discipline $course_number was approved by $dept_approval_name on $dept_approval_date.
  </p>

EOD;
            }
          }
        }
        else
        {
          //  No approval information available. Proposal had better not be submitted
          //  yet.
          if ($submitted_date !== null)
          {
            echo <<<EOD
  <p class='error detail-info'>
    Missing approval information for a submitted course.
  </p>

EOD;
          }
          else
          {
            echo "<p class='warning'>Information not available yet.</p>\n";
          }
        }
      }

      //  Current Syllabus
      //  -----------------------------------------------------------------------------
      $syllabus = get_current_syllabus("$discipline $course_number");
      if ($syllabus)
      {
        echo "<h2>Current Syllabus for $discipline $course_number</h2>\n";
        $size_str = humanize_num(filesize($syllabus));
        $date_str = date('F j, Y', filemtime($syllabus));
        echo <<<EOD
      <p>
        <a href='$syllabus'>The current syllabus for $discipline $course_number</a>
        was uploaded on $date_str ($size_str).
      </p>

EOD;
      }
      else
      {
        echo <<<EOD
    <h2 class='error'>
      No Syllabus Available for $discipline $course_number
    </h2>

EOD;
      }

      //  Other Proposals
      //  -------------------------------------------------------------------------------
      /*  Only submitted proposals are eligible for display in this section.
       */
      echo <<<EOD
  <h2>Other Proposals for $discipline $course_number</h2>

EOD;
      $query = <<<EOD
  SELECT proposals.id         other_id,
         proposal_types.abbr  proposal_type,
         opened_date          opened,
         submitted_date       submitted,
         closed_date          closed,
         submitter_name,
         submitter_email,
         agencies.abbr          agency
    FROM proposals, proposal_types, agencies
   WHERE proposals.id != $id
     AND discipline = '$discipline'
     AND course_number = '$course_number'
     AND submitted_date IS NOT NULL
     AND proposal_types.id = type_id
     AND agencies.id = proposals.agency_id
EOD;
      $result = pg_query($curric_db, $query) or die('Error accessing other proposals ' .
          basename(__FILE__) . ' ' . __LINE__);
      if (pg_num_rows($result) > 0)
      {
        echo <<<EOD
      <table class='summary'>
        <tr>
          <th>ID</th>
          <th>Type</th>
          <th>Opened</th>
          <th>Submitted</th>
          <th>Closed</th>
          <th>Agent</th>
          <th>Submitted By</th>
        </tr>
EOD;
        while ($row = pg_fetch_assoc($result))
        {
          $submitted  =  ($row['submitted']) ? $row['submitted'] : 'Not yet';
          $submitted  = substr($submitted, 0, 10);
          $closed     =  ($row['closed']) ? $row['closed'] : 'Not yet';
          $closed     = substr($closed, 0, 10);
          $other_id   = $row['other_id'];
          if ($other_id > 160)
          {
            $other_id = "<a href='.?id=$other_id'>$other_id</a>";
          }
          echo <<<EOD
          <tr>
            <td>$other_id</td>
            <td>{$row['proposal_type']}</td>
            <td>{$row['opened']}</td>
            <td>$submitted</td>
            <td>$closed</td>
            <td>{$row['agency']}</td>
            <td>{$row['submitter_name']} ({$row['submitter_email']})</td>
          </tr>
EOD;
        }
        echo "      </table>\n";
      }
      else
      {
        echo "<p>None.</p>\n";
      }

      //  Proposal Status
      //  -------------------------------------------------------------------------------
      /*  Opened and Closed statuses are determined from the proposal itself; other
       *  status changes require processing events related to the proposal. Each event
       *  gets its own paragraph.
       */
      $events = array();
      $events_query = <<<EOD
  SELECT event_date,
         agencies.full_name as agency_name,
				 agencies.abbr      as agency_abbr,
         actions.full_name  as action,
         annotation
    FROM events, agencies, actions
   WHERE proposal_id = $id
     AND agencies.id = (SELECT id FROM agencies WHERE id = agency_id)
     AND actions.id = (SELECT id FROM actions WHERE id = action_id)
ORDER BY event_date, entered_at;

EOD;

      $events_result = pg_query($curric_db, $events_query) or die(
          "<h1 class='error'>Query failed: " .  pg_last_error($curric_db) .
          "</h1></body></html>");
      $num_events = pg_num_rows($events_result);
      if ($num_events > 0)
      {
        while ($status_row = pg_fetch_assoc($events_result))
        {
          $event_date     = new DateTime($status_row['event_date']);
          $event_date_str = $event_date->format('F j, Y');
          $agency_name    = $status_row['agency_name'];
					$agency_abbr    = $status_row['agency_abbr'];
          $action         = $status_row['action'];
          $annotation     = $status_row['annotation'];
          if ($action === 'Revise')
          {
            $reviewer_info = '';
            if ($agency_abbr === 'GEAC')
            {
              //  GEAC provides comments from individual reviewers; other agencies do not.
              $reviewer_info = <<<EOD
        <br/>
        Comments from individual $agency_abbr reviewers may also be available at the
        <a href='../Reviews#$proposal_id'>Browse Reviews</a> page.

EOD;
            }
            $event_str = <<<EOD
      <p>
        The $agency_name <strong>requested revisions</strong> to the proposal on
        $event_date_str. $reviewer_info
      </p>

EOD;
            $events[] = $event_str;
          }
          else
          {
            $what = ($action === 'Fix') ? 'CUNYfirst catalog data' : 'proposal';
            if (isset($past_tense[$action]))
            {
              $action = $past_tense[$action];
            }
            if ($action === 'Submitted')
            {
              $action = 'Received';
            }
            if ($action === 'Resubmitted')
            {
              $action = 'Received an updated version of';
            }
            $event_str = <<<EOD
      <p>
        The $agency_name <strong>$action</strong> the $what on $event_date_str.
      </p>

EOD;
            $events[] = $event_str;
          }
          if ($annotation !== '')
          {
            $events[] = <<<EOD
      <p class='status-explanation'>
        <strong>$agency_abbr Comments: </strong>
        $annotation
      </p>

EOD;
          }
        }
      }
      else
      {
        $events[] = "<p>No actions have been taken on this proposal yet.</p>\n";
      }

      //  Proposal Status
      //  ===============================================================================
      echo <<<EOD

  <h2>Proposal Status</h2>
  <p>
    The proposal was $submitted_status_1 by $submitter_name
    ($submitter_email)$submitted_status_2.
  </p>

EOD;
      foreach($events as $event)
      {
        echo $event;
      }

      //  Justifications
      //  ===============================================================================
      echo <<<EOD
  <h2>Justifications</h2>
  <p>
    Each Criterion starts with the abbreviation for a group of criteria and/or a summary
    of how many of the needed justifications for the group have been supplied.
  </p>

  <table>
    <col width='25%' />
    <tr>
      <th>Criterion</th>
      <th>Justification</th>
    </tr>

EOD;
      //  Each criteria group has a certain number of justifications needed.
      /*  Justification types are named so the characters up to a '-' are the
       *  group abbreviation.
       *  The text that appears in the Criterion box gets tailored to handle special
       *  cases. The text consists of three parts: the group name, a note, and the text of
       *  the criterion itself.
       *    If the entire group is optional, the group name becomes 'Optional' and the
       *    note is omitted.
       *    If the justification is blank (or equivalent) the note is omitted.
       *    If the justification given is one of a set, the note tells which one of how
       *    many.
       *  The following code is messy beause the note should be enclosed in parens and
       *  followed by a colon, but if it is omitted, the colon goes after the group name.
       */
      $is_course_proposal = $proposal_class === 'Course';
      $current_group      = '';
      $num_needed         = 0;
      $num_given          = 0;

      foreach ($justifications as $type => $justification_text)
      {
        //  Justificaation types are justification group strings with '-#' appended, so
        //  the substring up to the hyphen is the group name.
        $group_name = substr($type, 0, strpos($type, '-'));
        if ($group_name !== $current_group)
        {
          // Initialize display of a new criteria group
          $current_group = $group_name;
          $num_given  = 0;
          if ($is_course_proposal)
          {
            $num_needed = 1;
          }
          else
          {
            $num_needed = $criteria_group_abbr2num_needed[$current_group];
          }
        }
        $criterion_text     = $criteria_text[$type];
        $num_given_str      = '';
        $justification_text = trim($justification_text);
        $group_name_str     = $group_name;
        $note               = '';

        //  How many ways can a box be empty?
        if (  ('' === $justification_text) ||
              ('[none]' === strtolower($justification_text)) ||
              ('none'   === strtolower($justification_text))   ||
              ('n/a'    === strtolower($justification_text)) )
        {
          $justification_text = '';
          $note = " (omitted)";
        }
        else
        {
          $num_given++;
          if ($num_needed > 1)
          {
            if ($num_given > $num_needed)
            {
              $note = " (<span class='warning'>extra justification</span>)";
            }
            else
            {
              $note = " (No. $num_given of $num_needed needed)";
            }
          }
        }
        $justification_text = str_replace("\n", '<br />', $justification_text);
        if ($num_needed == 1)
        {
          $group_name_str = 'Required';
        }
        if ($num_needed == 0)
        {
          $group_name_str = ' Optional';
        }
        echo <<<EOD
      <tr>
        <td>
          <strong>$group_name_str$note:</strong><br />$criterion_text
        </td>
        <td>
          $justification_text
        </td>
      </tr>

EOD;
      }
      echo <<<EOD
  </table>
  <form method='get' action='.'>
    <fieldset><legend>Select a Different Proposal</legend>
      <p>
        If you know the ID number of a different proposal you are interested in, enter it
        here: <input type='text' name='id' class='one-col' />
      </p>
    </fieldset>
  </form>
EOD;
    }
  }
  else if (isset($_GET['class']))
  {
    //  Display by Class
    //  ===============================================================================
    $class_id = sanitize($_GET['class']);
    if ($class_id > 0)
    {
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
  else if (isset($_GET['type']))
  {
    //  Display by Type
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
        If you know the ID number of a proposal you are interested in, enter it here:
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
        <a href='.' class='current-page'>Browse Proposals</a>
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

