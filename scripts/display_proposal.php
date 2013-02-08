<?php
// scripts/display_proposal.php

//  display_proposal()
//  -------------------------------------------------------------------------------------
/*  Display all the information available about one proposal.
 */
  function display_proposal($proposal_id)
  {
    global $curric_db, $criteria_text, $criteria_group_abbr2num_needed,
    $require_dept_approval, $past_tense;
    $query = <<<EOD
  SELECT proposals.*,
         cf_academic_organizations.department_name  dept_name,
         proposal_types.full_name                   proposal_type,
         proposal_types.abbr                        proposal_abbr,
         proposal_classes.abbr                      proposal_class
    FROM proposals, cf_academic_organizations, proposal_types, proposal_classes
   WHERE proposals.id = $proposal_id
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
  <h1 class='error'>Error accessing Proposal #$proposal_id</h1>
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
  <h1>Proposal #$proposal_id<br/>$discipline $course_number: $proposal_type</h1>

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
      $syllabi = get_syllabi("$discipline $course_number");
      $num_syllabi = count($syllabi);
      switch ($num_syllabi)
      {
        case 0:
          echo <<<EOD
      <h2 class='error'>No Syllabi Available for $discipline $course_number</h2>

EOD;
          break;
        case 1:
          $keys = array_keys($syllabi);
          $syllabus = $syllabi[$keys[0]];
          $size_str = humanize_num(filesize($syllabus));
          $date_str = date('F j, Y', filemtime($syllabus));
          $date_saved = date('Y-m-d H-i', filemtime($syllabus));
          $by_line  = '';
          $query = <<<EOD
select  saved_by
from    syllabus_uploads
where   to_char(saved_date, 'YY-MM-DD HH-MI') = '$date_saved'

EOD;
          $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query failed: "
              . basename(__FILE__) . ' ' . __LINE__ . '</h1></body></html>');
          if (pg_num_rows($result) === 1)
          {
            $by_line = "by {$row['saved_by']}";
          }
          echo <<<EOD
      <h2>Current Syllabus for $discipline $course_number</h2>
      <p>
        <a href='$syllabus'>The current syllabus for $discipline $course_number</a>
        ($size_str) was uploaded $by_line on $date_str.
      </p>

EOD;
          break;
        default:
          echo <<<EOD
      <h2>
        $num_syllabi syllabi for $discipline $course_number, most current one first.
      </h2>

EOD;
          foreach ($syllabi as $syllabus)
          {
            $size_str = humanize_num(filesize($syllabus));
            $date_str = date('F j, Y', filemtime($syllabus));
            $date_saved = date('Y-m-d H-i', filemtime($syllabus));
            $by_line  = '';
            $query = <<<EOD
select  saved_by
from    syllabus_uploads
where   to_char(saved_date, 'YY-MM-DD HH-MI') = '$date_saved'

EOD;
            $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query failed: "
              . basename(__FILE__) . ' ' . __LINE__ . '</h1></body></html>');
            if (pg_num_rows($result) === 1)
            {
              $by_line = "by {$row['saved_by']}";
            }
            echo <<<EOD
    <p>
      <a href='$syllabus'>$discipline $course_number</a> ($size_str) uploaded $by_line on
      $date_str.
    </p>

EOD;
          }
          break;
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
   WHERE proposals.id != $proposal_id
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
         effective_date,
         agencies.full_name as agency_name,
         agencies.abbr      as agency_abbr,
         actions.full_name  as action,
         annotation
    FROM events, agencies, actions
   WHERE proposal_id = $proposal_id
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
          $event_date         = new DateTime($status_row['event_date']);
          $effective_date     = new DateTime($status_row['effective_date']);
          $event_date_str     = $event_date->format('F j, Y');
          $effective_date_str = $effective_date->format('F j, Y');
          $agency_name        = $status_row['agency_name'];
          $agency_abbr        = $status_row['agency_abbr'];
          $action             = $status_row['action'];
          $annotation         = $status_row['annotation'];
          if ($action === 'Revise')
          {
            $reviewer_info = '';
            if ($agency_abbr === 'GEAC')
            {
              //  GEAC provides comments from individual reviewers; other agencies do not.
              $reviewer_info = <<<EOD
        <br/>
        Comments from individual $agency_abbr reviewers may also be available at the
        <a href='../Reviews#$proposal_id'>Reviews</a> page.

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
            $what = 'proposal';
            $when = 'on';
            if ($action === 'Fix')
            {
              //  Fixes require special treatment in order to present the correct sequence
              //  of events. The effective_date entered into CUNYfirst is what gets
              //  displayed. But the event_date is taken as the date the fix was entered
              //  into this system so that the most recent event for the proposal will be
              //  the fix event.
              $what = 'CUNYfirst catalog data,';
              $when = "effective $effective_date_str, on";
            }
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
        The $agency_name <strong>$action</strong> the $what $when $event_date_str.
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
        If you want to track a different course or proposal, enter the course (<em>e.g.,
        ENGL 100</em>) or proposal ID here: <input type='text' name='id'
        class='one-col' />
      </p>
    </fieldset>
  </form>
EOD;
    }
  }
?>
