<?php
//  Approved_Courses/index.php
//  -------------------------------------------------------------------------------------
/*    Generate a table designed to be displayed in a SharePoint "Page Viewer" web part.
 *    Use query string parameters to configure the generated table:
 *
 *    Option      | Default | Notes
 *    ------------+---------+------------------------------------------------------------
 *    title       |         | Abbreviations of the designations included.
 *                |         |
 *    show        | title   | Show title, or title with details?
 *                |         | Course is always shown. Designations are shown if multiple.
 *                |         | Options (case-insensitive):
 *                |         |   title   - title only
 *                |         |   details - title, hours, credits, and requisites
 *                |         |   other   - other designations besides the one(s) requested
 *                |         |   all     - title, details, and other
 *                |         |
 *    width       | 800     | Width in pixels of the page.
 *                |         | Set this to the width of the target web part.
 *                |         |
 *    designation | MNS     | Comma and/or space-separated list of designations to
 *                |         | include.
 *                |         | Case insensitive.
 *                |         | Groups can be specified as follows:
 *                |         |   RCC   = EC1, EC2, MQR, LPS
 *                |         |   FCC   = CE, IS, SW, USED, WCGI
 *                |         |   CO4   = RCC, FCC, LIT, LANG, SCI, SYN
 *                |         |   COPT  = LIT, LANG, SCI, CO4
 *                |         |   PATH  = RCC, FCC, COPT
 *                |         |   MNS   = LPS, SW, SCI
 *                |         |   AOK   = AP, CV, NS, NS+L, RL, SS  (Area of Knowledge)
 *                |         |   CTXT  = US, ET, WC                (Context of Experience)
 *                |         |   PLAS  = AOK, CTXT, PI
 *
 *  Options may be abbreviated to their unique prefixes. For example, title = t;
 *  designation = d; details = det; default = def; full = f.
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
//  Setup and initialization
//  --------------------------------------------------------------------------------------
date_default_timezone_set('America/New_York');

require_once('../include/titlecase.inc');

require_once('credentials.inc');
$curric_db                    = curric_connect() or die('Unable to access curriculum db');
$cf_catalog_update_date       = 'Unknown';
$approved_courses_update_date = 'Unknown';

$query = 'select * from update_log';
$result = pg_query($curric_db, $query) or die('update_log query failed');
while ($row = pg_fetch_assoc($result))
{
  $this_date = new DateTime($row['updated_date']);
  $date_str = $this_date->format('F j, Y');
  switch ($row['table_name'])
  {
    case 'cf_catalog':
      $cf_catalog_update_date = $date_str;
      break;
    case 'approved_courses':
      $approved_courses_update_date = $date_str;
      break;
    default:
      ; //  ignore other tables that might be there
  }
}

$disipline_names = array();
$result = pg_query($curric_db, 'select * from discp_dept_div') or
            die ('Unable to query discp_dept_div');
while ($row = pg_fetch_assoc($result))
{
  $discipline_names[$row['discipline']] = $row['discipline_full_name'];
}

//  Global arrays for identifying designations
//  -------------------------------------------------------------------------------------
  //  Designation Sets
  $designation_sets = array
    (
      //  Note recursive definitions here
      'EC'    =>  array('EC1', 'EC2'),
      'RCC'   =>  array('EC', 'MQR', 'LPS'),
      'FCC'   =>  array('CE', 'IS', 'SW', 'USED', 'WCGI'),
      'CO4'   =>  array('FCC', 'LIT', 'LANG', 'SCI', 'SYN'),
      'COPT'  =>  array('LIT', 'LANG', 'SCI', 'CO4'),
      'PATH'  =>  array('RCC', 'FCC', 'COPT'),
      'MNS'   =>  array('LPS', 'SW', 'SCI'),
      'AOK'   =>  array('AP', 'CV', 'NS', 'NS+L', 'RL', 'SS'),
      'CTXT'  =>  array('US', 'ET', 'WC'),
      'PLAS'  =>  array('AOK', 'CTXT', 'PI'),
    );
  //  Designation Atoms
  $designation_atoms = array
    (
      'EC1', 'EC2', 'MQR', 'LPS', 'CE', 'IS', 'SW', 'USED', 'WCGI',
      'LIT', 'LANG', 'SCI', 'SYN',
      'AP', 'CV', 'NS', 'NS+L', 'RL', 'SS', 'US',
      'ET', 'WC', 'PI'
    );
    $designation_titles = array
    (
      'EC'    =>  'CUNY Required Core: English Composition',
      'RCC'   =>  'CUNY Required Core',
      'FCC'   =>  'CUNY Flexible Core',
      'CO4'   =>  'QC College Option: fourth group',
      'COPT'  =>  'QC College Option',
      'PATH'  =>  'CUNY Pathways, including QC College Option',
      'MNS'   =>  'Pathways courses offered by MNS Division',
      'AOK'   =>  'QC Perspectives (PLAS) Area of Knowledge',
      'CTXT'  =>  'QC Perspectives (PLAS) Context of Experience',
      'PLAS'  =>  'QC Perspectives',
      'EC-1'   =>  'QC First English Composition',
      'EC-2'   =>  'QC Second English Composition',
      'MQR'   =>  'CUNY Pathways: Mathematics and Quantitative Reasoning',
      'LPS'   =>  'CUNY Pathways: Life and Physical Sciences',
      'CE'    =>  'CUNY Pathways: Creative Expression',
      'IS'    =>  'CUNY Pathways: Individual and Society',
      'SW'    =>  'CUNY Pathways: Scientific World',
      'USED'  =>  'CUNY Pathways: United States Experience in its Diversity',
      'WCGI'  =>  'CUNY Pathways: World Cultures and Global Issues',
      'LIT'   =>  'QC College Option: Literature',
      'LANG'  =>  'QC College Option: Language',
      'SCI'   =>  'QC College Option: Science',
      'SYN'   =>  'QC College Option: Synthesis',
      'AP'    =>  'QC Perspectives: Appreciating and Participating in the Arts',
      'CV'    =>  'QC Perspectives: Culture and Values',
      'NS'    =>  'QC Perspectives: Natural Science',
      'NS+L'  =>  'QC Perspectives: Natural Science with Laboratory',
      'RL'    =>  'QC Perspectives: Reading Literature',
      'SS'    =>  'QC Perspectives: Social Science',
      'US'    =>  'QC Perspectives: United States',
      'ET'    =>  'QC Perspectives: European Traditions',
      'WC'    =>  'QC Perspectives: World Cultures',
      'PI'    =>  'QC Perspectives: Pre-industrial'
    );

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

//  append_designations()
//  -------------------------------------------------------------------------------------
/*  Append all designations implied by a designation name to the global array,
 *  $designations
 */
  function append_designations($desig)
  {
    global $designation_atoms, $designation_sets, $designations;
    if (in_array($desig, $designation_atoms))
    {
      if (! in_array($desig, $designations)) $designations[] = $desig;
      return;
    }
    else
    {
      if (isset($designation_sets[$desig]))
      {
        $this_set = $designation_sets[$desig];
        foreach($this_set as $element) append_designations($element);
        return;
      }
      else die("<h1>append_designations: $desig is not a valid designation</h1>");
    }
  }

//  or_list()
//  ----------------------------------------------------------------------
/*  Returns a comma-separated list of array elements, with the last item
 *  preceded by "or."
 */
 function or_list($elements)
 {
   $n = count($elements);
   switch ($n)
   {
     case 0:
         return "";
         break;
     case 1:
         return $elements[0];
         break;
     case 2:
         return $elements[0] . " or " . $elements[1];
         break;
     default:
         $str = $elements[0];
         for ($i = 1; $i < $n - 1; $i++)
         {
           $str .= ", " . $elements[$i];
         }
         return $str . ", or " . $elements[$n -1];
   }
 }


  //  Process the query string
  //  ===================================================================================

  //  Default values
  $page_title         = '';
  $page_width         = '800px';
  $show_details       = false;
  $show_other         = false;
  $designations       = array('LPS', 'SW', 'SCI');

  //  Make the option keys case-insensitive and canonical
  /*  s = s*    Show (title or title and details)
   *  d = d*    Designation
   *  t = t*    Page title
   *  w = w*    Page width
   */
  foreach ($_GET as $key => $value)
  {
    unset($_GET[$key]);
    $k = strtolower($key[0]);
    switch ($k)
    {
      case 's':
        $_GET['show']         = $value;
        break;
      case 'd':
        $_GET['designations'] = $value;
        break;
      case 't':
        $_GET['title']        = $value;
        break;
      case 'w':
        $_GET['width']        = $value;
        break;
      default:
        die("<h1>Unrecognized option: $key</h1>");
    }
  }
  //  Process options
  if (isset($_GET['title']))
  {
    $page_title = sanitize($_GET['title']);
  }

  $other_heading = '';
  if (isset($_GET['show']))
  {
    //  Validate and process show option
    //  This is more elaborate than expected because it was originally conceived that way!
    $show_option = sanitize(strtolower($_GET['show']));
    switch (strtolower($show_option[0]))
    {
      case 't':
        $show_details = false;
        $show_other   = false;
        break;
      case 'd':
        $show_details = true;
        $show_other   = false;
        break;
      case 'o':
        $show_details = false;
        $show_other   = true;
        $other_heading = '<th>Other Designation(s)</th>';
        break;
      case 'a':
        $show_details = true;
        $show_other   = true;
        $other_heading = '<th>Other Designation(s)</th>';
        break;
      default:
        die("<h1>'$show_option' is not a valid show option</h1>" .
            "<h2>Must be ‘title’, ‘details’, 'other', or'all'.</h2>");
    }
  }

  if (isset($_GET['width']))
  {
    $w = sanitize($_GET['width']);
    if (is_numeric($w)) $page_width = $w . 'px';
    else
    {
      exit("<h1>‘$w’ is not a valid page width</h1>");
    }
  }

  $desig_array = array(); //  Default if not in _GET
  if (isset($_GET['designations']))
  {
     //  Extract array of lowercase strings from query string
    $desig_str = str_replace(',', ' ', $_GET['designations']);
    $desig_str = preg_replace('/\s+/', ' ', $desig_str);
    $desig_array = explode(' ', strtoupper($desig_str));

    //  Generate the set of designations to be displayed
    $designations = array();
    foreach ($desig_array as $desig) append_designations($desig);
  }

  //  If page_title is not set, generate one based on the designations requested.
  //  ---------------------------------------------------------------------------
  if ($page_title === '')
  {
    $page_title = 'Courses that can satisfy ';
    if (isset($_GET['designations']))
    {
      $page_title .= or_list($desig_array);
    }
    else
    {
      $page_title .= or_list($designations);
    }
  }

  //  Designations column is displayed only if there are multiple designations selected
  if (count($designations) > 1)
  {
    $show_designations = true;
    $designation_heading = '<th>Designation(s)</th>';
  }
  else
  {
    $show_designations = false;
    $designation_heading = '';
  }

  $catalog_heading = ($show_details ?
                        'Catalog Description' : 'Course Title') . ' (with variants).';

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
        width:<?php echo $page_width;?>;
        }
      h1+p {text-align:center;}
    </style>
  </head>
  <body>
<?php
  echo <<<EOD
    <h1>$page_title</h1>
    <p>Hover over abbreviations for translations.</p>
    <p><em>
      Approval data last updated $approved_courses_update_date.
      <br/>
      CUNYfirst catalog data last updated $cf_catalog_update_date.
    </em></p>
    <table>
      <tr>
        <th>Course</th>
        <th>$catalog_heading</th>
        $designation_heading $other_heading
      </tr>
EOD;

  //  Loop through all the approved_courses, getting proper catalog data and suffix list
  //  for each one from cf_catalog. Then get all the designation mappings and their
  //  reasons.  Display only those courses that have at least one of the requested
  //  designations in its mapping set.
  $query = <<<EOD
select * from approved_courses order by discipline, course_number
EOD;
  $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query Failed " .
      basename(__FILE__) . ' line ' . __LINE__ . "</h1></body></html>\n");
  while ($row = pg_fetch_assoc($result))
  {
    $discipline       = $row['discipline'];
    $discipline_name  = $discipline_names[$discipline];
    $course_number    = $row['course_number'];
    $suffixes         = $row['suffixes'];
    $titles           = array
                        (
                          str_replace('Ii', 'II',
                            titleCase(
                              sanitize(
                                strtolower(($row['course_title'])))))
                        );
    $hours            = $row['hours'];
    $credits          = $row['credits'];
    $prereqs          = '';
    $cf_designation   = '';
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
      //  Hack the title as best we can
      $this_title = str_replace('Ii', 'II',
          titleCase(
            sanitize(
              strtolower($cf_row['course_title']))));
      if ('.' === substr($this_title, -1)) $this_title = substr($this_title, 0, -1);
      if (! in_array($this_title, $titles))
      {
        $titles[] = $this_title;
      }
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
      $titles[] = <<<EOD
      <span class='error'>
        <strong>Note: </strong>Course not active in CUNYfirst</span>
EOD;
    }
    $title = "$titles[0].";
    for ($i = 1; $i < count($titles); $i++)
    {
      if ($titles[$i] !== $titles[0])
      {
        $title .= "<div>{$titles[$i]}.</div>";
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
    //  Build lists of requested and other designations. If requested list
    //  ends up empty, skip the course.
    $requested_designations = '';
    $other_designations = '';
    while ($d_row = pg_fetch_assoc($d_result))
    {
      $this_designation = $d_row['designation'];
      $this_title = $designation_titles[$this_designation];
      if (in_array($this_designation, $designations))
      {
        $requested_designations .= "<span title='$this_title'>$this_designation</span> ";
      }
      else
      {
        $other_designations .= "<span title='$this_title'>$this_designation</span> ";
      }
    }
    if ($requested_designations !== '')
    {
      $course_info = $title;
      if ($show_details)
      {
        $course_info .= " {$hours}hr; {$credits}cr; $prereqs";
      }
      $others = '';
      if ($show_other)
      {
        $others = "<td>$other_designations</td>";
      }
      if ($show_designations)
      {
        $requested_designations = "<td>$requested_designations</td>";
      }
      else
      {
        $requested_designations = '';
      }
      echo <<<EOD
  <tr>
    <td><span title='$discipline_name'>$discipline</span> $course_numbers</td>
    <td>$course_info</td>
    $requested_designations
    $others
  </tr>

EOD;
    }
  }
?>
    </table>
  </body>
</html>

