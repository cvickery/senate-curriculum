<?php
//  /Approved_Courses/full_list.php
/*  Master list of all courses with all their designations.
 */
//set_include_path(get_include_path()
//    . PATH_SEPARATOR . getcwd() . '/../scripts'
//    . PATH_SEPARATOR . getcwd() . '/../include');
//require_once('init_session.php');
date_default_timezone_set('America/New_York');
require_once('credentials.inc');
$curric_db          = curric_connect() or die('Unable to access curriculum db');
$result             = pg_query("select * from update_log where table_name = 'cf_catalog'")
                        or die("Unable to query update_log");
$row                = pg_fetch_assoc($result);
$cf_update_date_raw = new DateTime($row['updated_date']);
$cf_update_date     = $cf_update_date_raw->format('F j, Y');


//  sanitize()
//  ---------------------------------------------------------------------------
/*  Prepare a user-supplied string for inserting/updating a db table.
 *    Force all line endings to Unix-style.
 *    Replace straight quotes, apos, and quot with smart quotes
 *    Convert '<' and '&' to html entities without destroying existing entities
 *    Convert '--' to mdash
 */
  function sanitize($str)
  {
    $returnVal = trim($str);
    //  Convert \r\n to \n, then \r to \n
    $returnVal = str_replace("\r\n", "\n", $returnVal);
    $returnVal = str_replace("\r", "\n", $returnVal);
    //  Convert exisiting html entities to characters
    $returnVal = str_replace('&amp;', '&', $returnVal);
    $returnVal = str_replace('--', '—', $returnVal);
    $returnVal = preg_replace('/(^|\s)"/', '$1“', $returnVal);
    $returnVal = str_replace('"', '”', $returnVal);
    $returnVal = preg_replace("/(^\s)'/", "$1‘", $returnVal);
    $returnVal = str_replace("'", "’", $returnVal);
    $returnVal = htmlspecialchars($returnVal, ENT_NOQUOTES, 'UTF-8');
    return $returnVal;
  }


//  Approved courses
//  -------------------------------------------------------------------------------------
/*    List all approved courses and their designations.
 *    To evolve: list selected categories; limit info displayed; provide editing (add,
 *    delete, change)
 */
  //  Generate the web page
  //  -----------------------------------------------------------------------------------
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
    <title>Approved General Education Courses</title>
    <link rel="stylesheet" type="text/css" href="../css/curriculum.css" />
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/site_ui.js"></script>
    <style type='text/css'>
      body {width: 1200px;}
      table {
width:1170px;
        table-layout: fixed;
        border: 1px solid black;
      }
      th, td {
        padding:8px;
        }
      th:nth-child(1 ) div, td:nth-child(1 ) div { width: 100px; }
      th:nth-child(2 ) div, td:nth-child(2 ) div { width: 200px; }
      th:nth-child(3 ) div, td:nth-child(3 ) div { width:  30px; }
      th:nth-child(4 ) div, td:nth-child(4 ) div { width:  30px; }
      th:nth-child(5 ) div, td:nth-child(5 ) div { width: 120px; }
      th:nth-child(6 ) div, td:nth-child(6 ) div { width: 100px; }
      th:nth-child(7 ) div  {width: 468px; }
      td:nth-child(7 ) div  {width:  80px; }
      td:nth-child(8 ) div  {width:  80px; }
      td:nth-child(9 ) div  {width:  80px; }
      td:nth-child(10 ) div {width:  80px; }
      td:nth-child(11 ) div {width:  80px; }
      thead, tbody {display:block;}
      tbody {height:800px; width:1184px; overflow:auto;}
    </style>
  </head>
  <body>

  <h1>Queens College General Education Courses</h1>
  <p>Based on CUNYfirst catalog data as of <?php echo $cf_update_date; ?>.</p>
  <table>
    <thead>
      <tr>
        <th><div>Course</div></th>
        <th><div>Title</div></th>
        <th><div>Hr</div></th>
        <th><div>Cr</div></th>
        <th><div>Requisites</div></th>
        <th><div>CF Designation</div></th>
        <th colspan='5'><div>QC Designation(s)</div></th>
      </tr>
    </thead>
    <tbody>
<?php
  //  Loop through all the approved_courses, getting proper catalog data and suffix list
  //  for each one from cf_catalog. Then get all the designation mappings and their
  //  reasons.
  $query = <<<EOD
select * from approved_courses order by discipline, course_number
EOD;
  $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query Failed " .
      basename(__FILE__) . ' line ' . __LINE__ . "</h1></body></html>\n");
  while ($row = pg_fetch_assoc($result))
  {
    $discipline     = $row['discipline'];
    $course_number  = $row['course_number'];
    $suffixes       = $row['suffixes'];
    $titles         = array(sanitize($row['course_title']));
    $hours          = $row['hours'];
    $credits        = $row['credits'];
    $prereqs        = '';
    $cf_designation = '';
    $designations   = array();
    $cf_query = <<<EOD
select * from cf_catalog
where discipline = '$discipline'
and course_number ~* '^{$course_number}[WH]?$'
EOD;
    $cf_result = pg_query($curric_db, $cf_query) or
      die("<h1 class='error'>Query Failed " . basename(__FILE__) .
          " line " . __LINE__ . "</h1></body></html>\n");
    $w_ness       = 'Undefined';
    $is_honors    = '';
    while ($cf_row = pg_fetch_assoc($cf_result))
    {
      $cf_course_number = $cf_row['course_number'];
      $cf_designation   = $cf_row['designation'];
      $suffix = $cf_course_number[strlen($cf_course_number) - 1];
      switch ($suffix)
      {
        case 'W':
          if ($w_ness === 'Undefined') $w_ness = 'Always';
          else $w_ness = 'Sometimes';
          break;
        case 'H':
          $is_honors = 'Honors';
          break;
        default:
          if ($w_ness === 'Undefined') $w_ness = 'Never';
          else $w_ness = 'Sometimes';
          break;
      }

      $titles[] = $cf_row['course_title'];
      if ($hours != $row['hours'])
      {
        $hours .= "<div class='error'>{$row['hours']}</div>";
      }
      if ($credits != $row['credits'])
      {
        $credits .= "<div class='error'>{$row['credits']}</div>";
      }
      $prereqs = $cf_row['prerequisites'];
    }

    $course_numbers = '';
    switch ($w_ness)
    {
      case 'Always':
        $course_numbers = "{$course_number}W";
        break;
      case 'Sometimes':
        $course_numbers = "$course_number/­{$course_number}W";
        break;
      case 'Never':
      case 'Undefined':
        $course_numbers = $course_number;
        break;
      default:
        die("<h1 class='error'>Bad switch at " . basename(__FILE__) . " line " .
          __LINE__ . "</h1></body></html>");
    }
    if ($is_honors)
    {
      if ($w_ness === 'Undefined')
      {
        $course_numbers .= 'H';
      }
      else
      {
        $course_numbers .= "/­{$course_number}H";
      }
    }
    else if ($w_ness === 'Undefined')
    {
      $titles[] = "<span class='error'>Course not active in CUNYfirst</span>";
    }
    $title = $titles[0];
    for ($i = 1; $i < count($titles); $i++)
    {
      if ($titles[$i] !== $titles[0])
      {
        $title .= "<div class='error'>{$titles[$i]}</div>";
      }
    }
    $d_query = <<<EOD
select t.abbr as designation, m.is_primary, m.reason
from course_designation_mappings m, proposal_types t
where m.discipline = '$discipline'
and   m.course_number = $course_number
and   t.id = m.designation_id
EOD;
    $d_result = pg_query($curric_db, $d_query) or
      die("<h1 class='error'>Query Failed " . basename(__FILE__) .
          " line " . __LINE__ . "</h1></body></html>\n");
    $designations[0] = 'No pri. desig.';
    while ($d_row = pg_fetch_assoc($d_result))
    {
      $is_primary = $d_row['is_primary'] === 't' ? '*' : '';
      if ($is_primary)
      {
        $designations[0] = "{$d_row['designation']}$is_primary ({$d_row['reason']})";
      }
      else
      {
        $designations[] = "{$d_row['designation']}$is_primary ({$d_row['reason']})";
      }
    }
    while (count($designations) < 5) $designations[] = '';
    echo <<<EOD
  <tr>
    <td><div>$discipline $course_numbers</div></td>
    <td><div>$title</div></td>
    <td><div>$hours</div></td>
    <td><div>$credits</div></td>
    <td><div>$prereqs</div></td>
    <td><div>$cf_designation</div></td>
    <td><div>$designations[0]</div></td>
    <td><div>$designations[1]</div></td>
    <td><div>$designations[2]</div></td>
    <td><div>$designations[3]</div></td>
    <td><div>$designations[4]</div></td>
  </tr>

EOD;
  }
?>
      </tbody>
    </table>
  </body>
</html>