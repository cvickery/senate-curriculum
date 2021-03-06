<?php  /* Admin/event_editor.php */

set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() .  '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');
require_once('admin.inc');                       // Must be logged in as an administrator
$login_status = login_status();

$disabled = " disabled='disabled'";
if ( $can_edit ) $disabled = '';

//  The actions array
$actions = array();
$result = pg_query($curric_db, 'SELECT * FROM actions ORDER BY display_order')
  or die('Unable to get actions: ' . basename(__FILE__) . ' ' . __LINE__);
while ($row = pg_fetch_assoc($result))
{
  $actions[] = $row['full_name'];
}

//  Map agencies to agency_groups
//  There is one column in the table for each agency group. The table of proposals
//  includes a column for each agency group, showing the latest event for that group.
//  if any.

$query = <<<EOD
select    a.abbr            agency_name,
          a.agency_group_id agency_group_id,
          g.name            agency_group_name
from      agencies      a,
          agency_groups g
where     g.id = a.agency_group_id
order by  a.display_order

EOD;
$result = pg_query($curric_db, $query) or die("<h1 class='error'>Query Failed: "
    . pg_last_error($curric_db) . ' ' . basename(__FILE__) . ' ' . __LINE__
    . "</h1></body></html>");

//  list of agency groups names indexed by id
$agency_groups_by_id      = array();
//  list of agency group names indexed by agency
$agency_groups_by_agency  = array();

while ($row = pg_fetch_assoc($result))
{
  $agency_name        = $row['agency_name'];
  $agency_group_id    = $row['agency_group_id'];
  $agency_group_name  = $row['agency_group_name'];

  $agency_groups_by_id[$agency_group_id] = $agency_group_name;
  $agency_groups_by_agency[$agency_name] = $agency_group_name;
}

//  Process Form Data
//  -------------------------------------------------------------------------------------
/*  Update db if new data were posted.
 *  The agency, action, and action date must all be set for all events
 *  By default, the effective date is the same as the action date, but if
 *  the effective date for an event is a valid date string, that becomes the effective
 *  date for the event; if the effective date is non-blank but not a valid date, that
 *  event is not created.
 */

    //  Validate form data
    $error_msg = '';
    $agencies = array_keys($agency_groups_by_agency);
    $remote_ip = 'Unknown Host IP';
    if (isset($_SERVER['REMOTE_ADDR']))
    {
      $remote_ip = $_SERVER['REMOTE_ADDR'];
    }
    $agency           = '';
    $action           = '';
    $action_date_obj  = NULL;
    $action_date_db   = ''; //  Formatted for db
    $action_date_txt  = ''; //  Formatted for display
    $num_events       = 0;
    if ($form_name === 'update-events')
    {
      try
      {
        $action_date_obj  = new DateTime(sanitize($_POST['action_date']));
        $action_date_db   = $action_date_obj->format('Y-m-d');
        $action_date_txt  = $action_date_obj->format('F j, Y');
      }
      catch (Exception $e)
      {
        $error_msg .= "<div class='error'>Invalid action date. Nothing saved</div>\n";
      }
      $agency = sanitize($_POST['agency']);
      if (! in_array($agency, $agencies))
      {
        $error_msg .= "<div class='error'>Invalid agency. Nothing saved</div>\n";
        $agency = '';
      }
      $action = sanitize($_POST['action']);
      if (! in_array($action, $actions))
      {
        $error_msg .= "<div class='error'>Invalid action. Nothing saved</div>\n";
        $action = '';
      }

      //  Create new event for each select-# form element
      if ($action_date_obj && $agency && $action)
      {
        $num_events = 0;
        foreach ($_POST as $name => $value)
        {
          if (strpos($name, 'select') !== FALSE)
          {
            preg_match('/^select-(\d+)$/', $name, $matches);
            $proposal_id          = $matches[1];
            $comment              = sanitize($_POST['comment-' . $proposal_id]);
            $effective_date_db    = $action_date_db;
            $effective_date_post  = sanitize($_POST['effective-' . $proposal_id]);
            if ($effective_date_post !== '')
            {
              try
              {
                $effective_date_obj = new DateTime($effective_date_post);
                $effective_date_db  = $effective_date_obj->format('Y-m-d');
              }
              catch (Exception $e)
              {
                $error_msg .= <<<EOD
            <div class='error'>
              Invalid effective date for proposal $proposal_id. Event skipped.
            </div>

EOD;
                continue;
              }
            }
            //  Check that this event does not duplicate the most recent event for the proposal
            $query = <<<EOD
select  e.id,
        e.event_date  as event_date,
        g.abbr        as agency,
        a.full_name   as action,
        e.entered_by  as entered_by,
        e.entered_at  as entered_at
FROM    events e, agencies g, actions a
WHERE   e.id = (select max(id) from events where proposal_id = $proposal_id)
AND     g.id = e.agency_id
AND     a.id = e.action_id

EOD;
            $result = pg_query($curric_db, $query) or die("<h1 class='error'>".
                      "Query Failed at " . basename(__FILE__) . " line " . __LINE__ .
                      "</h1></body></html>");
            if (($num_rows = pg_num_rows($result)) !== 1)
            {
              die("<h1 class='error'>Error: $num_rows most-recent " .
                  "events for proposal #$proposal_id)</h1></body></html>");
            }
            //  Duplicate events should not occur: die if they do for now.
            $row = pg_fetch_assoc($result);
            if (($row['agency'] === $agency) && ($row['action'] === $action))
            {
              die("<h1 class='error'>Error: $agency $action event duplicates " .
                  "most-recent event for proposal #$proposal_id</h1></body></html>");
            }

            //  Create the event
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
        '$action_date_db',                                              -- event_date
        '$effective_date_db',                                           -- effective_date
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
            $num_events++;
          }
        }
      }
    }

  $num_events = $num_events ? $num_events : 'No';
  $plural = ($num_events === 1) ? '' : 's';
  $events_message = $error_msg ? $error_msg : "$num_events Event$plural Created";

//  Generate the web page
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
    <title>Event Editor</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/event_editor.css" />
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
      $events_message
      $login_status
      $nav_bar
      $admin_nav
    </div>
    <h1>Event Editor</h1>
    $dump_if_testing

EOD;

    //  Generate the form
    //  =================================================================================
  echo <<<EOD
    <form action='{$_SERVER['REQUEST_URI']}' method='post'>
      <input type='hidden' name='form-name' value='update-events' />
        <div id='action-table'>
          <table>
            <tr>
              <th><label for='agency'>Agency</label></th>
              <th><label for='action'>Action</label></th>
              <th><label for='action_date'>Action Date</label></th>
            </tr>
            <tr>
              <td>
                <select name='agency' id='agency'>

EOD;
  foreach ($agency_ids as $abbr => $agency_id)
  {
    $selected = '';
    if (isset($_POST['agency']) && $_POST['agency'] === $abbr)
    {
      $selected = " selected='selected'";
    }
    echo "<option value='$abbr'$selected>$abbr</option>\n";
  }
  echo <<<EOD
                </select>
              </td>
              <td>
                <select name='action' id='action'>

EOD;
  foreach ($actions as $action)
  {
    $selected = '';
    if (isset($_POST['action']) && $_POST['action'] === $action)
    {
      $selected = " selected='selected'";
    }
    echo "<option value='$action'$selected>$action</option>\n";
  }
  echo <<<EOD
                </select>
              </td>
              <td>
                <input  type='text' name='action_date' id='action_date'
                        value='$action_date_txt' />
              </td>
            </tr>
          </table>
          <button type='submit'$disabled>Submit</button>
        </div>
        <!-- Proposal Table -->
        <table id='proposal-table'>
          <thead>
            <tr>
              <th><div>Sel</div></th>
              <th><div>ID</div></th>
              <th><div>Course</div></th>
              <th><div>Type</div></th>
EOD;
  foreach ($agency_groups_by_id as $group_id => $agency_group_name)
  {
    echo "            <th><div>$agency_group_name</div></th>";
  }
  echo <<<EOD
              <th><div>Comment</div></th>
              <th><div>Effective Date</div></th>
            </tr>
          </thead>
          <tbody>

EOD;
      //  Get all proposals that have not been closed yet, and display
      //  them, along with controls for updating selected ones.
  $query = <<<EOD
   SELECT proposals.id            proposal_id,
          proposals.discipline    discipline,
          proposals.course_number course_number,
          proposal_types.abbr     proposal_type
     FROM proposals, proposal_types
    WHERE proposals.submitted_date IS NOT NULL
      AND proposals.closed_date IS NULL
      AND proposals.type_id = proposal_types.id
      AND proposals.type_id IN
          (SELECT id
             FROM proposal_types
            WHERE class_id IN
            (SELECT id
              FROM proposal_classes
             WHERE abbr = 'CUNY'
                OR abbr = 'CO'
                OR abbr = 'Course')
            )
 ORDER BY proposals.id

EOD;
  $result = pg_query($curric_db, $query) or die("Query failed: " .
      pg_last_error($curric_db) . ' file ' .
      basename(__FILE__) . ' line ' . __LINE__);

  //  Get all the events for the proposal
  while ($row = pg_fetch_assoc($result))
  {
    $event_query = <<<EOD
  SELECT events.event_date AS event_date,
         agencies.abbr     AS agency,
         actions.full_name AS action
    FROM events, actions, agencies
   WHERE proposal_id = {$row['proposal_id']}
     AND agencies.id = agency_id
     AND actions.id = action_id
ORDER BY event_date

EOD;
  $event_result = pg_query($curric_db, $event_query) or die("Query failed: " .
    pg_last_error($curric_db) . ' file ' . basename(__FILE__) . ' line ' . __LINE__);
  $actions = array();
  foreach ($agency_groups_by_id as $agency_id => $agency_group_name)
  {
    $actions[$agency_group_name] = '';
  }
  while ($event = pg_fetch_assoc($event_result))
  {
    $action = $event['agency'] . ' ' . $event['action'] . '<br />'
            . $event['event_date'];
    $actions[$agency_groups_by_agency[$event['agency']]] = $action;
  }
  $proposal_id = $row['proposal_id'];
  echo <<<EOD
        <tr>
          <td>
            <div>
              <input type='checkbox' name='select-{$row['proposal_id']}'$disabled />
            </div>
          </td>
          <td><div><a href='../Proposals?id=$proposal_id'>$proposal_id</a></div></td>
          <td><div>{$row['discipline']} {$row['course_number']}</div></td>
          <td><div>{$row['proposal_type']}</div></td>

EOD;
  foreach ($agency_groups_by_id as $agency_id => $agency_group_name)
  {
    echo "      <td><div>{$actions[$agency_group_name]}</div></td>\n";
  }
  echo <<<EOD
          <td>
            <div><input type='text' name='comment-{$row['proposal_id']}' /></div>
          </td>
          <td>
            <div><input type='text' name='effective-{$row['proposal_id']}' /></div>
          </td>
        </tr>

EOD;
    }
  echo <<<EOD
        </tbody>
      </table>
    </form>

EOD;

?>
  </body>
</html>

