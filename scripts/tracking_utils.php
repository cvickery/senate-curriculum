<?php
//  tracking_utils.php

//  Class Event
//  -------------------------------------------------------------------------------------
/*  Provides a to_string function so and array of Events can be sorted easily.
 */
class Event
{
  public $event_date, $agency, $action;
  public function __construct($agency, $event_date, $action)
  {
    $this->event_date = $event_date;
    $this->agency     = $agency;
    $this->action     = $action;
  }

  //  By returning the date, agency, and action as a string in that order, an array of
  //  events can be sorted chronologically. Used by generate_row().
  public function __toString()
  {
    return $this->event_date . ' ' . $this->agency . ' ' . $this->action;
  }
}

//  generate_row()
//  -------------------------------------------------------------------------------------
/*  Emit a table row based on events for a proposal.
 */
  function generate_row($current_id,
                        $course,
                        $type,
                        $class,
                        $submitted_date,
                        $submitter_name,
                        $events)
  {
    global $past_tense;

    //  Figure out the column contents based on the events for this proposal.
    //  sort($events);
    $subcommittee = $committee = $senate = $cccrc = '';
    $discipline_number = explode(' ', $course);
    foreach ($events as $event)
    {
      if (isset($past_tense[$event->action]))
      {
        $event->action = $past_tense[$event->action];
      }
      if ($event->action === 'Submitted') $event->action = 'Received';
      echo "<!-- $event -->\n";
      switch ($event->agency)
      {
        case 'AQRAC':
        case 'WSC':
        case 'GEAC':
          //$subcommittee = $event->toString();
          $subcommittee = $event->agency . ' ' . $event->event_date . ' ' . $event->action;
          if ($event->action === 'Approve') $ucc = 'Pending';
          break;
        case 'UCC':
        case 'GCC':
        case 'Registrar':
          $committee = $event->agency . ' ' . $event->event_date . ' ' . $event->action;
          if ($event->action === 'Approve') $senate = 'Pending';
          if ($class === 'Course') $subcommittee = 'N/A';
          if ($type === 'FIX')
          {
            $senate = 'N/A';
            $cccrc  = 'N/A';
          }
          break;
        case 'Senate':
          $senate = $event->agency . ' ' . $event->event_date . ' ' . $event->action;
          if (($event->action === 'Approve') && ($class === 'CUNY'))
          {
            $cccrc = 'Pending';
          }
          else $cccrc = 'N/A';
          break;
        case 'CCCRC':
          $cccrc = $event->agency . ' ' . $event->event_date . ' ' . $event->action;
          break;
      }
    }

    //  Suppress links to PLAS proposals: the full proposals are in a different db.
    $id_link = "<a href='.?id=$current_id'>$current_id</a>";
    if ($class === 'PLAS')
    {
      $id_link = $current_id;
    }
    echo <<<EOD
<tr>
  <td>$id_link</td>
  <td>$type</td>
  <td>$course</td>
  <td>$submitter_name</td>
  <td>$subcommittee</td>
  <td>$committee</td>
  <td>$senate</td>
  <td>$cccrc</td>
</tr>

EOD;
    $csv = "\"$current_id\",";
    $csv .= "\"$type\",";
    //  Separate the Course into discipline and number columns for CSV
    $csv .= "\"${discipline_number[0]}\",\"{$discipline_number[1]}\",";
    $csv .= "\"$submitted_date\",";
    $csv .= "\"$submitter_name\",";
    $csv .= "\"$subcommittee\",";
    $csv .= "\"$committee\",";
    $csv .= "\"$senate\",";
    $csv .= "\"$cccrc\"\r\n";
    return $csv;
  }

//  tracking_table()
//  -------------------------------------------------------------------------------------
/*  Generate a table giving the current status of a set of proposals.
 *
 *  Parameters
 *    types           Array of proposal types to display: if null or an empty array,
 *                    display all types. If a string, just that type is displayed and the
 *                    Type column is disabled.
 *    order_by        String for the order-by clause. Default is 'proposals.id'
 *    include_closed  Boolean: if true, closed proposals are displayed. Default is false.
 *
 *  Return
 *    csv             CSV table, as a string.
 */
  function tracking_table($types = null,
                          $order_by = 'proposals.id',
                          $include_closed = false)
  {
    global  $curric_db, $agency_ids,
            $proposal_type_abbr2type_id,
            $proposal_type_id2agency_id;
    $csv = '';

    //  Generate types clause
    //  ---------------------
    if ( ! $types) $types = array();
    if ( is_string($types)) $types = array($types);
    $types_clause = '';
    if (count($types) > 0)
    {
      $types_clause = <<<EOD

p.type_id IN (SELECT id FROM proposal_types WHERE abbr = '{$types[0]}')

EOD;
      for ($i = 1; $i < count($types); $i++)
      {
        $types_clause .= <<<EOD
OR  p.type_id IN (SELECT id FROM proposal_types WHERE abbr = '{$types[$i]}')

EOD;
      }
    }
    //  Generate closed clause
    //  ----------------------
    if ($include_closed) $close_clause = '';
    else $closed_clause = "        AND closed_date IS NULL\n";

    //  The Query
    $query = <<<EOD
SELECT      p.id                                    AS proposal_id,
            t.abbr                                  AS type,
            c.abbr                                  AS class,
            p.discipline||' '||p.course_number      AS course,
            to_char(p.submitted_date, 'YYYY-MM-DD') AS submitted_date,
            p.submitter_name                        AS submitter_name,
            p.submitter_email                       AS submitter_email,
            g.abbr                                  AS agency,
            a.full_name                             AS action,
            e.event_date                            AS event_date
FROM        proposals p LEFT JOIN events e
                        ON  p.id = e.proposal_id
                        LEFT JOIN actions a
                        ON a.id = e.action_id,
          proposal_types t,
          agencies g,
          proposal_classes c
WHERE     ({$types_clause})
AND       t.id  = p.type_id
AND       c.id = (SELECT class_id FROM proposal_types WHERE id = p.type_id)
AND       p.submitted_date IS NOT NULL
AND       g.id = e.agency_id
ORDER BY  {$order_by}, event_date, entered_at

EOD;
    // echo "<pre>$query</pre>";
    $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query failed: " .
        pg_last_error($curric_db) . ' ' . basename(__FILE__) . ' ' . __LINE__ .
        '</h1></body></html>');
    $n = pg_num_rows($result);
    if ($n === 0)
    {
      return;
    }
    else
    {
      /*  Columns depend on agency(ies) associated with the proposal type(s) requested.
       *
       *  subcom:     submit -> subcom -> UCC -> Sen -> CCCRC
       *  UCC:        submit           -> UCC -> Sen -> BOT
       *  GCC:        submit           -> GCC -> Sen -> BOT
       *  Registrar:  submit           (->xCC)       -> Registrar
       *
       *  That is, omit the subcommittee if the agency is UCC, GCC, or Registrar.
       *  Registrar proposals have to be reviewed by the appropriate curriculum
       *  committee before going to the Registrar. (Curriculum committees may convert FIX
       *  proposals to REV-x proposals.)
       */

      //  Gather list of agencies. All proposals are submitted to one of the following:
      $agencies = array('GEAC'      => false,
                        'WSC'       => false,
                        'AQRAC'     => false,
                        'UCC'       => false,
                        'GCC'       => false,
                        'Registrar' => false);
      foreach ($types as $type_abbr)
      {
        $type_id    = $proposal_type_abbr2type_id[$type_abbr];
        $agency_id  = $proposal_type_id2agency_id[$type_id];
        $agency_abbr = array_search($agency_id, $agency_ids);
        $agencies[$agency_abbr] = true;
      }
      //  Columns to include
      $columns = array('Proposal', 'Type', 'Course', 'Submitted Date', 'Submitted By',
          'Subcommittee', 'Committee', 'Senate', 'CCCRC');

      echo "<table class='summary'><tr>";
      foreach ($columns as $column)
      {
        echo "<th>$column</th>";
        if ($csv !== '') $csv .= ',';
        if ($column === 'Course')
        {
          //  Separate the Course into discipline and number columns for CSV
          $csv .= 'Discipline, Number';
        }
        else
        {
          $csv .= $column;
        }
      }
      echo "</tr>\n";
      $csv .= "\r\n";

      $current_id = 0;
      while ($row = pg_fetch_assoc($result))
      {
        $proposal_id      = $row['proposal_id'];
        if ($proposal_id !== $current_id)
        {
          //  Starting a new proposal
          if ($current_id !== 0)
          {
            //  Display current proposal before starting new one
            $csv .= generate_row( $current_id,
                                  $course,
                                  $type,
                                  $class,
                                  $submitted_date,
                                  $submitter_name,
                                  $events);
          }
          $current_id = $proposal_id;
          $events = array();
        }
        $submitted_date   = $row['submitted_date'];
        $course           = $row['course'];
        $submitter_name   = $row['submitter_name'];
        $submitter_email  = $row['submitter_email'];
        $agency           = $row['agency'];
        $action           = $row['action'];
        $event_date       = $row['event_date'];
        $type             = $row['type'];
        $class            = $row['class'];
        $events[] = new Event($agency, $event_date, $action);
      }
      //  Last proposal
      $csv .= generate_row( $current_id,
                            $course,
                            $type,
                            $class,
                            $submitted_date,
                            $submitter_name,
                            $events);
      echo <<<EOD
      </table>

EOD;
    }
  return $csv;
  }

 ?>