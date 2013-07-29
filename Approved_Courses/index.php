<?php
//  /Approved_Courses/index.php

set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() . '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');

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
      body {width: 1024px;}
      table {
        border: 1px solid black;
        border-radius: 0.25em;
        box-shadow: #999 0.25em 0.25em;
      }
      td {
        padding:0.25em;
        }
    </style>
  </head>
  <body>

  <h1>Queens College General Education Courses</h1>
  <p>Based on CUNYfirst catalog data as of <?php echo date('F j, Y', $cf_update_date);
  ?>.</p>
  <table>
    <tr>
      <th>Course</th><th>Title</th>
      <th>Hours</th><th>Credits</th><th>Prerequisites</th>
      <th>CF Designation</th>
      <th>CUNY/QC Core Designations</th>
      <th>PLAS Designations</th>
    </tr>
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
        $course_numbers = "$course_number/{$course_number}W";
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
        $course_numbers .= "/{$course_number}H";
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
    $core_designations = '';
    $plas_designations = '';
    while ($d_row = pg_fetch_assoc($d_result))
    {
      $is_primary = $d_row['is_primary'] === 't' ? '*' : '';
      if ($d_row['reason'] === 'PLAS')
      {
        $plas_designations .= "{$d_row['designation']} ";
      }
      else
      {
        $core_designations .= (($core_designations === '') ? '' : '<br/>') .
          "{$d_row['designation']}{$is_primary} ({$d_row['reason']}) ";
      }
    }
    echo <<<EOD
  <tr>
    <td>$discipline $course_numbers</td>
    <td>$title</td>
    <td>$hours</td>
    <td>$credits</td>
    <td>$prereqs</td>
    <td>$cf_designation</td>
    <td>$core_designations</td>
    <td>$plas_designations</td>
  </tr>

EOD;
  }
?>
  </table>
  </body>
</html>
