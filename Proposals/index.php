<?php
// Proposals/index.php
set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() . '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');
require_once('display_proposal.php');
require_once('syllabus_utils.php');
require_once('tracking_utils.php');
require_once('simple_diff.php');

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
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/site_ui.js"></script>
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
    $dump_if_testing

EOD;

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
        $csv = tracking_table($types, 'p.type_id, p.id', true);
        if ($csv === '')
        {
          echo "    <h2>No Proposals Found</h2>\n";
        }
        else
        {
          $_SESSION['csv'] = $csv;
          $_SESSION['csv_name'] = str_replace(' ', '_', strtolower($class_name));
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
      $csv = tracking_table($type_abbr, "$order_by $direction", true);
      if ($csv === '')
      {
        echo "    <h2>No $type_abbr Proposals Found</h2>\n";
      }
      else
      {
        $_SESSION['csv'] = $csv;
        $_SESSION['csv_name'] = strtolower($type_abbr);
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

      $query = "select * from track_by_discipline where discipline = '$discp_abbr'";
      $result = pg_query($curric_db, $query)
        or die('Unable to access proposals: ' . basename(__FILE__) . ' ' . __LINE__);
      $n = pg_num_rows($result);
      if ($n === 0)
      {
        echo "<h2>No $discipline_name Proposals Found</h2>\n";
      }
      else
      {
        $csv = generate_table_headings(array());
        $current_id = 0;
        while ($row = pg_fetch_assoc($result))
        {
          $proposal_id = $row['proposal_id'];
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
            $events     = array();
          }
          $course         = $row['course'];
          $type           = $row['type'];
          $class          = $row['class'];
          $submitted_date = substr($row['submitted_date'], 0, 10);
          $submitter_name = $row['submitter_name'];
          $agency         = $row['agency'];
          $event_date     = $row['event_date'];
          $action         = $row['action'];
          $events[]       = new Event($agency, $event_date, $action);
        }
        //  Last proposal; end of table
        $csv .= generate_table_row( $proposal_id,
                                    $course,
                                    $type,
                                    $class,
                                    $submitted_date,
                                    $submitter_name,
                                    $events);
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

?>
    </div>
  </body>
</html>
