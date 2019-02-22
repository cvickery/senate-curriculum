<?php
//  Close propoposals that have been approved by the CCRC or the BOT,
//  withdrawn (by any agency), or fixed by the registrar
//  *** THIS UTILITY IS NOT IN ACTIVE USE ***
$debug = True;

//  Timestamp
$timestamp = date('Y-m-d h:i a');

set_include_path('/Users/vickery/php/' . PATH_SEPARATOR . get_include_path());

require_once('credentials.inc');
$curric_db = curric_connect();
if (! $curric_db)
{
  echo "<p>$timestamp: Unable to access curriculum db</p>";
  exit(1);
}
pg_query($curric_db, 'BEGIN');
if ($debug) echo "<p>$timestamp  Begin\n</p>";

//  CCRC or BOT: Could not figure out how to do this in straight SQL

//  Step 1: Get list of approval events
$query = <<<EOD
SELECT  e.proposal_id,
        e.event_date,
        e.discipline||' '||e.course_number  as course,
        t.abbr                              as type,
        g.abbr                              as agency
FROM    events e, agencies g, proposal_types t
WHERE   e.action_id = (SELECT id FROM actions WHERE full_name = 'Approve')
AND     e.agency_id = g.id
AND     e.agency_id IN
        (
          SELECT id FROM agencies
          WHERE abbr  = 'CCRC'
          OR abbr     = 'BOT'
          OR abbr     = 'OAA'
        )
AND     t.id = (select type_id from proposals where id = e.proposal_id)
AND     (SELECT closed_date FROM proposals where id = e.proposal_id) IS NULL

EOD;
$result = pg_query($curric_db, $query) or die('Select events failed');
$approvals = array();
$num = 0;
while ($row = pg_fetch_assoc($result))
{
  $event_date = $row['event_date'];
  $course     = $row['course'];
  $type       = $row['type'];
  $agency     = $row['agency'];
  $approvals[$row['proposal_id']] = $event_date;
  echo "<p>$timestamp: Approve $course for $type by $agency on $event_date</p>";
}

//  STEP 2: Close proposals
if (count($approvals) > 0)
{
  $in_list = '';
  $query = <<<EOD
  UPDATE proposals p SET closed_date = CASE

EOD;
  foreach ($approvals as $id => $date)
  {
    if ($in_list !== '') $in_list .= ',';
    $in_list .= $id;
    $query .= <<<EOD
    WHEN p.id = $id THEN '$date'::date

EOD;
  }
  $query .= <<<EOD
    END
  WHERE p.id IN ($in_list)

EOD;
  $result = pg_query($curric_db, $query) or die("Approve query failed\n");
  $num = pg_affected_rows($result);
}

$s = ($num === 1) ? '' : 's';
if ($num > 0) echo "<p>$timestamp: $num Proposal$s approved</p>";

//  Withdraw
//  -------------------------------------------------------------------------------------
/*
 *  2013-12-17: Only close withdrawn proposals if there are no succeeding events for the
 *  proposal. (Then, to reopen a withdrawn proposal, create a 'Reopen' event for it.)
 *  2013-09-23: Reinstate this feature for all but ANTH 100-level courses 'cause there
 *  are LANG proposals that need to be cleared out.
 *  2013-06-19: Withdrawing the feature to close withdrawn proposals. At least until all
 *  previously withdrawn proposals (by Anthropology) have been approved.
 */
$query = <<<EOD
select distinct e.proposal_id,
       e.discipline||' '||e.course_number as course,
       t.abbr as type
from events e, proposal_types t
where action_id = (select id from actions where full_name = 'Withdraw')
and e.proposal_id in (select id from proposals where closed_date is null)
and e.id =  (select max(id) from events where proposal_id = e.proposal_id)
and t.id =  (
              select id from proposal_types where id =
              (
               select type_id from proposals where id = e.proposal_id
              )
            )
EOD;
$result = pg_query($curric_db, $query) or die ("Withdraw query failed at " .
    basename(__FILE__) . ' line ' . __LINE__);
$num = 0;
while ($row = pg_fetch_assoc($result))
{
  $id     = $row['proposal_id'];
  $course = $row['course'];
  $type   = $row['type'];
  $w_query = <<<EOD
update proposals set closed_date = now() where id = $id

EOD;
  $w_result = pg_query($curric_db, $w_query) or die ("Withdrawal query failed at " .
    basename(__FILE__) . ' line ' . __LINE__);
  if ($num_affected = pg_affected_rows($w_result) === 1)
  {
    $num++;
    echo "<p>$timestamp: Close withdrawn proposal #$id of $course for $type</p>\n";
  }
  else
  {
    die("<p>$timestamp: Attempt to close withdrawn proposal #$id of $course for $type failed</p>" .
        "<p>$num_affected proposals changed</p>\n");
  }
}

$s = ($num === 1) ? '' : 's';
if ($num > 0) echo "<p>$timestamp: $num Proposal$s withdrawn</p>\n";

//  Fix
$query = <<< EOD
UPDATE  proposals p
SET     closed_date = (
        SELECT  event_date
        FROM    events e
        WHERE   e.proposal_id = p.id
        AND     e.action_id = (
                SELECT  id
                FROM    actions
                WHERE full_name = 'Fix'))
WHERE   p.closed_date IS NULL
AND     ('Fix' IN (
          SELECT  full_name
          FROM    events e,
                  actions a
          WHERE   e.proposal_id = p.id
          AND     e.action_id = a.id))

EOD;
$result = pg_query($curric_db, $query) or die("<p>Fix query failed</p>\n");
$num = pg_affected_rows($result);

$s = ($num === 1) ? '' : 's';
if ($num > 0) echo "<p>$timestamp: $num  Course$s fixed</p>\n";

pg_query($curric_db, 'COMMIT');
if ($debug) echo "<p>$timestamp Commit</p>\n";

?>
