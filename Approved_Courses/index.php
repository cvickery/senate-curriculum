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
    <script type="text/javascript" src="../js/course_list.js"></script>
    <style type='text/css'>
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
  <table>
    <tr>
      <th>Course</th><th>W-ness</th><th>Title</th>
      <th>Hours</th><th>Credits</th><th>Prerequisites</th>
      <th colspan='5'>Designation(s)</th>
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
    $query = <<<EOD
select * from cf_catalog
where discipline = '$discipline'
and course_number ~* '^{$course_number}[WH]?$'
EOD;
    $course_result = pg_query($curric_db, $query) or die("<h1 class='error'>Query Failed " .
      basename(__FILE__) . ' line ' . __LINE__ . "</h1></body></html>\n");
    $w_ness = 'Undefined';
    $honors = '';
    $title  = '';
    while ($course_row = pg_fetch_assoc($course_result))
    {
      $cf_course_number = $course_row['course_number'];
      $suffix = $cf_course_number[strlen($cf_course_number) - 1];
      $debug .= "$suffix:";
      switch ($suffix)
      {
        case 'W':
          if ($w_ness === 'Undefined') $w_ness = 'Always-W';
          else $w_ness = 'Sometimes-W';
          break;
        case 'H':
          $honors = '<br/>Honors';
          break;
        default:
          if ($w_ness === 'Undefined') $w_ness = 'Never-W';
          else $w_ness = 'Sometimes-W';
          break;
      }
      if ($title !== $course_row['course_title'])
      {
        $br = $title === '' ? '' : '<br/>';
        $title .= "$br{$course_row['course_title']}";
      }
    }
    echo <<<EOD
  <tr>
    <td>$discipline $course_number</td>
    <td>$w_ness$honors</td>
    <td>$title</td>
  </tr>

EOD;
  }
?>
  </table>
  </body>
</html>
