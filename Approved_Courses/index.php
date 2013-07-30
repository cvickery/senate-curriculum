<?php
//  /Approved_Courses/index.php

require_once('credentials.inc');
//  db Setup
//  --------------------------------------------------------------------------------------
$cf_update_date = 'unknown';
if (file_exists('../CF_Queries/qccv_cu_catalog.xls'))
{
  $cf_update_date = filemtime('../CF_Queries/qccv_cu_catalog.xls');
}
$curric_db      = curric_connect() or die('Unable to access curriculum db');

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

//  Approved_Courses/index.php
//  -------------------------------------------------------------------------------------
/*    Generate a table to be displayed in a SharePoint web part.
 *    Use query string parameters to configure the generated table:
 *
 *    Parameter Default
 *    title     Queens College General Education Courses
 *    cols      def     Comma and/or space-separated list of columns to display.
 *                        default = course, title, designations
 *                        title
 *                        details = hours, credits, requisites
 *                        full    = course, title, details
 *    width     800     Width in pixels of the page. Set this to the width of the target
 *                      web part.
 *    desig     MNS     Comma and/or space-separated list of designations to include.
 *                      Case insensitive.
 *                      Groups can be specified as follows:
 *                        RCC   = EC1, EC2, MQR, LPS
 *                        FCC   = CE, IS, SW, USED, WCGI
 *                        CO4   = RCC, FCC, LIT, LANG, SCI, SYN
 *                        COPT  = LIT, LANG, SCI, CO4
 *                        PATH  = RCC, FCC, COPT
 *                        MNS   = LPS, SW, SCI
 *                        AOK   = AP, CV, NS, NS+L, RL, SS  (Area of Knowledge)
 *                        CTXT  = US, ET, WC                (Context of Experience)
 *                        PLAS  = AOK, CTXT, PI
 * 
 *  Examples. All the following are equivalent:
 *    http://senate.qc.cuny.edu/Curriculum/Approved_Courses
 *    http://senate.qc.cuny.edu/Curriculum/Approved_Courses?width=800&desig=MNS
 *    http://senate.qc.cuny.edu/Curriculum/Approved_courses?desig=lps,sci,sw
 * 
 *    Notes:  1. You can put spaces in the query string, but they must be "URL-encoded"
 *            as %20:
 * 
 *    http://senate.qc.cuny.edu/Curriculum/Approved_courses?desig=lps,%20sci%20sw
 * 
 *            2. An alternate URL encoding for spaces is +, which means NS+L must be 
 *            URL-encoded as NS%2BL:
 * 
 *    http://senate.qc.cuny.edu/Curriculum/Approved_courses?desig=lps,%20sci%20sw,ns%2bl
 *    (Adds NS+L to the previous examples.)
 * 
 */
 
  //  Process the query string
  //  ------------------------------------------------------------------------------------
  $page_title   = 'Queens College General Education Courses';
  $table_width  = '800px';
  $show_course  = true;
  $show_title   = true;
  $show_details = false;
  $designations = array('lps', 'sw', 'sci');

  if (isset($_GET['title']))
  {
    $page_title = $_GET['title'];
  }  
  if (isset($_GET['cols']))
  {
  }
  if (isset($_GET['width']))
  {
    $width = $_GET['width'] . 'px';
  }
  if (isset($_GET['desig']))
  {
    
  }
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
    <link rel='stylesheet' href='../css/approved_courses.css' type='text/css'/>
    <style type='text/css'>
      body {
        width:<?php echo $width;?>;
        }
    </style>
  </head>
  <body>

  <h1><?php echo $page_title;?></h1>
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
