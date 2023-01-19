<?php
//  tracking_utils.php

//  Class Event
//  -------------------------------------------------------------------------------------
/*  Provides a to_string function so an array of Events can be sorted easily.
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
  //  events can be sorted chronologically. Used by generate_table_row().
  public function __toString()
  {
    return $this->event_date . ' ' . $this->agency . ' ' . $this->action;
  }
}

//  generate_table_headings()
//  -------------------------------------------------------------------------------------
/*  Generate the appropriate table columns and initialize the $csv string
 */
  function generate_table_headings($types)
  {
    global $proposal_type_abbr2type_id,
           $proposal_type_id2agency_id,
           $agency_ids;

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
    $columns = array('Proposal ID', 'Proposal Type', 'Course',
                     'Submitted Date', 'Submitted By',
                     'Subcommittee', 'Committee', 'Senate', 'CCRC', 'OAA');
    $csv = '';
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
      else if ($column === 'Subcommittee')
      {
        $csv .= 'Subcommittee Action,Subcommittee Date,Subcommittee Name';
      }
      else if ($column === 'Committee')
      {
        $csv .= 'Committee Action,Committee Date,Committee Name';
      }
      else if ($column === 'Senate')
      {
        $csv .= 'Senate Action,Senate Date';
      }
      else if ($column === 'CCRC')
      {
        $csv .= 'CCRC Action,CCRC Date';
      }
      else if ($column === 'OAA')
      {
        $csv .= 'OAA Action,OAA Date';
      }
      else
      {
        $csv .= $column;
      }
    }
    echo "</tr>\n";
    $csv .= "\r\n";
    return $csv;
  }

//  generate_table_row()
//  -------------------------------------------------------------------------------------
/*  Emit a table row based on events for a proposal.
 */
  function generate_table_row($current_id,
                              $course,
                              $type,
                              $class,
                              $submitted_date,
                              $submitter_name,
                              $events)
  {
    global $past_tense;
    if (preg_match('/test/', getcwd()))
    {
      echo "<!-- id: $current_id -->\n";
      echo "<!-- course: $course -->\n";
      echo "<!-- type: $type -->\n";
      echo "<!-- class: $class -->\n";
      echo "<!-- submitted: $submitted_date -->\n";
    }
    //  Figure out the column contents based on the events for this proposal.
    //  sort($events);
    $subcommittee = $committee = $senate = $ccrc = $oaa = '';
    foreach ($events as $event)
    {
      if (isset($past_tense[$event->action]))
      {
        $event->action = $past_tense[$event->action];
      }
      
      //  Departments submit proposals, which are received by a (sub-)committee
      //  But the college submits proposals to the CCRC
      //  So in the hopes of being clear, submissions other than those going to
      //  the CCRC are marked as Received
      if (($event->agency !== 'CCRC') &&
          ($event->action === 'Submitted'))
      {
        $event->action = 'Received';
      }

      echo "<!-- $event -->\n";
      switch ($event->agency)
      {
        case 'AQRAC':
        case 'WSC':
        case 'GEAC':
          //$subcommittee = $event->toString();
          $subcommittee = $event->action . ' ' . $event->event_date . ' ' . $event->agency;
          if ($event->action === 'Approved') $ucc = 'Pending';
          break;
        case 'UCC':
        case 'GCC':
        case 'Registrar':
          $committee = $event->action . ' ' . $event->event_date . ' ' . $event->agency;
          if ($event->action === 'Approved') $senate = 'Pending';
          if ($class === 'Course') $subcommittee = 'N/A';
          if ($type === 'FIX')
          {
            $senate = 'N/A';
            $ccrc  = 'N/A';
          }
          break;
        case 'Senate':
          $senate = $event->action . ' ' . $event->event_date;
          if (($event->action === 'Approved') && ($class === 'CUNY'))
          {
            $ccrc = 'Pending';
          }
          else $ccrc = 'N/A';
          break;
        case 'CCRC':
          $ccrc = $event->action . ' ' . $event->event_date;
          break;
        case 'OAA':
          $oaa = $event->action . ' ' . $event->event_date;
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
  <td>$submitted_date</td>
  <td>$submitter_name</td>
  <td>$subcommittee</td>
  <td>$committee</td>
  <td>$senate</td>
  <td>$ccrc</td>
  <td>$oaa</td>
</tr>

EOD;
    $csv = "\"$current_id\",";
    $csv .= "\"$type\",";
    //  Separate the Course into discipline and number columns for CSV
    $discipline_number = explode(' ', $course);
    $csv .= "\"{$discipline_number[0]}\",\"{$discipline_number[1]}\",";
    $csv .= "\"$submitted_date\",";
    $csv .= "\"$submitter_name\",";
    //  Separate subcommittee fields: action-date-agency
    $subcommittee_fields = explode(' ', $subcommittee);
    if (3 === count($subcommittee_fields))
    {
      $csv .= "\"$subcommittee_fields[0]\",";
      $csv .= "\"$subcommittee_fields[1]\",";
      $csv .= "\"$subcommittee_fields[2]\",";
    }
    else
    {
      $csv .= ',,,';
    }
    //  Separate committee fields: action-date-agency
    $committee_fields = explode(' ', $committee);
    if (3 === count($committee_fields))
    {
      $csv .= "\"$committee_fields[0]\",";
      $csv .= "\"$committee_fields[1]\",";
      $csv .= "\"$committee_fields[2]\",";
    }
    else
    {
      $csv .= ',,,';
    }
    //  Separate senate fields: action-date
    $senate_fields = explode(' ', $senate);
    if (2 === count($senate_fields))
    {
      $csv .= "\"$senate_fields[0]\",";
      $csv .= "\"$senate_fields[1]\",";
    }
    else
    {
      $csv .= ',,';
    }
    //  Separate CCRC fields: action-date
    $ccrc_fields = explode(' ', $ccrc);
    if (2 === count($ccrc_fields))
    {
      $csv .= "\"$ccrc_fields[0]\",";
      $csv .= "\"$ccrc_fields[1]\",";
    }
    else
    {
      $csv .= ',,';
    }
    //  Separate OAA fields: action-date
    $oaa_fields = explode(' ', $oaa);
    if (2 === count($oaa_fields))
    {
      $csv .= "\"$oaa_fields[0]\",";
      $csv .= "\"$oaa_fields[1]\",";
    }
    else
    {
      $csv .= ',,';
    }
    return $csv . "\r\n";
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
                          $order_by = 'p.id',
                          $include_closed = false)
  {
    global  $curric_db, $agency_ids,
            $proposal_type_abbr2type_id,
            $proposal_type_id2agency_id;

    //  Generate closed clause
    //  ----------------------
    $closed_clause = 'AND       p.closed_date IS NULL';
    if ($include_closed)
    {
      $closed_clause = '';
    }

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

    //  The Query
    $query = <<<EOD
SELECT      p.id                                    AS proposal_id,
            t.abbr                                  AS type,
            c.abbr                                  AS class,
            p.discipline||' '||p.course_number      AS course,
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
$closed_clause
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
      return '';  //  Empty csv string
    }
    else
    {
      /*  Columns depend on agency(ies) associated with the proposal type(s) requested.
       *
       *  subcom:     submit -> subcom -> UCC -> Sen -> CCCRC (-> OAA if appeal)
       *  UCC:        submit           -> UCC -> Sen -> BOT
       *  GCC:        submit           -> GCC -> Sen -> BOT
       *  Registrar:  submit           (->xCC)       -> Registrar
       *
       *  That is, omit the subcommittee if the agency is UCC, GCC, or Registrar.
       *  Registrar proposals have to be reviewed by the appropriate curriculum
       *  committee before going to the Registrar.
       *  TODO: Curriculum committees may convert FIX proposals to REV-x proposals.
       */
      $csv = generate_table_headings($types);
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
            $csv .= generate_table_row( $current_id,
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
      $csv .= generate_table_row( $current_id,
                                  $course,
                                  $type,
                                  $class,
                                  $submitted_date,
                                  $submitter_name,
                                  $events);
      echo <<<EOD
      </table>

EOD;
    return $csv;
    }
  }

 ?>
