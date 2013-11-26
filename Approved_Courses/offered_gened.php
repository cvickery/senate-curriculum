<?php
//  Curriculum/gened_offerings/advisement_list.php
  date_default_timezone_set('America/New_York');
  session_start();
  require_once('credentials.inc');

//  course_rows()
//  -----------------------------------------------------------------------------------------------
/*  Echo list of courses that satisfy the specified RD and are offered during $term_code.
 */
  function course_rows($rd)
  {
    global $curric_db, $term_code;
    $query = <<<EOD
select    a.discipline,
          a.course_number,
          a.course_title,
          o.suffixes,
          sum(o.sections)   as sections,
          sum(o.seats)      as seats,
          sum(o.enrollment) as enrollment
from      approved_courses a, offered_gened o
where     o.term_code     = $term_code
and       o.designation   = '$rd'
and       a.discipline    = o.discipline
and       a.course_number = o.course_number
group by  a.discipline, a.course_number, a.course_title,
          o.suffixes, o.sections, o.seats, o.enrollment
order by  a.discipline, a.course_number

EOD;

    $result = pg_query($curric_db, $query)
    or die("<h1 class='error'>Query failed: " . basename(__FILE__) . ' line ' . __LINE__ ."</h1>");
    if (pg_num_rows($result) > 0)
    {
      while ($row = pg_fetch_assoc($result))
      {
        $course_number_str = $row['course_number'];
        $suffixes = $row['suffixes'];
        //  - should appear before H should appear before W, but enforce it
        $suffix_str = '';
        if (strpos($suffixes, '-') !== False) $suffix_str .= '-';
        if (strpos($suffixes, 'H') !== False) $suffix_str .= 'H';
        if (strpos($suffixes, 'W') !== False) $suffix_str .= 'W';
        switch ($suffix_str)
        {
          case '-':
            break;
          case 'H':
            $course_number_str .= 'H';
            break;
          case 'W':
            $course_number_str .= 'W';
            break;
          case '-H':
            $course_number_str .= "/{$course_number_str}H";
            break;
          case '-W':
            $course_number_str .= "/{$course_number_str}W";
            break;
          case 'HW':
            $course_number_str .= "H/{$course_number_str}W";
            break;
          case '-HW':
            $course_number_str .= "/{$course_number_str}W/{$course_number_str}H";
            break;
          default:
            die("Bad suffix_str ($suffix_str) at " . basename(__FILE__) . " line " . __LINE__);
        }

        $course_info      = "<td>{$row['discipline']} $course_number_str</td><td>{$row['course_title']}</td>";
        $seats            = $row['seats'];
        $enrollment       = $row['enrollment'];
        $status                                 = " class='open'";
        if ($enrollment > 0.9 * $seats) $status = " class='warn'";
        if ($enrollment >= $seats)      $status = " class='closed'";
        $enrollment_info  = "<td>({$row['sections']}, $seats, $enrollment)</td>";
        echo "<tr$status>$course_info $enrollment_info</tr>\n";
      }
    }
    else
    {
    echo "<tr><td>None</td></tr>";
    }
  }

  $enrollment_date  = 'Unknown';
  $curric_db        = curric_connect() or die('Unable to access db');

  //  Default term_code is latest one available.
  $result = pg_query($curric_db, "select * from enrollment_terms order by term_code")
  or die("<h1 class='error'>Query failed: " . basename(__FILE__) . ' line ' . __LINE__ ."</h1>");
  $term_codes = array();
  while ($row = pg_fetch_assoc($result))
  {
    $term_codes[$row['term_code']] = $row['term_name'];
  }
  end($term_codes);
  $term_code = key($term_codes);
  $term_name = $term_codes[$term_code];

  //  process query string if present
  /*    term-code:  The term/session to report.
   *                  YYYYMMS, where S is 0, 1, or 2
   */
  //  Convert first query string name starting with 't' or 'T' to 'term-code'
  foreach ($_GET as $name => $value)
  {
    //  $value might be junk, but it's not used unless it matches one of the
    //  term_code numeric values in the db.
    if (strtolower($name[0]) === 't')
    {
      $_GET['term-code'] = $value;
      break;
    }
  }

  //  Update $term_code and $term_name if specified.
  if (array_key_exists('term-code', $_GET))
  {
    $term_code_value = $_GET['term-code'];

    if (array_key_exists($term_code_value, $term_codes))
    {
      $term_code = $term_code_value;
      $term_name = $term_codes[$term_code];
    }
    else
    {
      die("<h1 class='error'>'$term_code' is not a valid term-code</h1>" .
          "<h2>term-code format is YYYYMMS</h2>");
    }
  }

  //  Enrollment update date
  $result = pg_query($curric_db,
    "select updated_date " .
    "from update_log " .
    "where table_name = 'enrollments'")
  or die("<h1 class='error'>Query failed: " . basename(__FILE__) . ' line ' . __LINE__ ."</h1>");
  $row = pg_fetch_assoc($result);
  $enrollment_date = new DateTime($row['updated_date']);
  $enrollment_date = $enrollment_date->format('F j, Y');

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
    <title>General Education Course Offerings</title>
    <style type='text/css'>
      .open   {color:green;}
      .warn   {color:#990;}
      .closed {color:red;}
      #other-term-links {
        font-size: 0.7em;
      }
      #other-term-links ul {
        list-style-type: none;
      }
      #other-term-links li {
        display: inline-block;
      }
      #other-term-links a {
        display:block;
        text-decoration: none;
        margin: 0.5em 1em;
        padding:0.2em;
        width:10em;
        background-color: #ccc;
        border: 1px solid black;
        border-radius: 0.25em;
        color:black;
        font-family: sans-serif;
        text-align:center;
      }
      #other-term-links a:hover {
        background-color:black;
        color:#ccc;
      }
      @media print {
        #other-term-links {display:none;}
        .course-list {
          column-count:3;
          font-size: 8pt;
        }
      }
    </style>
  </head>
  <body>
    <h1>General Education Course Offerings</h1>
    <div>
      <p>
        The following General Education courses are scheduled to be offered during the
        <strong><?php echo $term_name; ?></strong> semester.
      </p>
      <p>
        Numbers in parentheses are (number of sections,
        total number of seats, current enrollment) as of <?php echo $enrollment_date; ?>.
      </p>
      <div id='other-term-links'>
        <h2>Other Semesters Available</h2>
        <?php
          echo "<ul id='other-term-links'>\n";
          foreach ($term_codes as $other_term_code => $other_term_name)
          {
            if ($term_code != $other_term_code)
            {
              echo "<li><a href='./offered_gened.php?t=$other_term_code'>$other_term_name</a></li>\n";
            }
          }
            echo "</ul>\n</div>\n";
      ?>
    </div>
    <div class='course-list'>
      <h2>Pathways Courses</h2>
      <h3>Required Core: College Writing 1 (EC-1)</h3>
      <table>
        <?php course_rows('EC-1'); ?>
      </table>
      <h3>Required Core: College Writing 2 (EC-2)</h3>
      <table>
        <?php course_rows('EC-2'); ?>
      </table>
      <h3>Required Core: Mathematics and Quantitative Reasoning (MQR)</h3>
      <table>
        <?php course_rows('MQR'); ?>
      </table>
      <h3>Required Core: Life and Physical Sciences (LPS)</h3>
      <table>
        <?php course_rows('LPS'); ?>
      </table>
      <h3>Flexible Core: World Cultures and Global Issues (WCGI)</h3>
      <table>
        <?php course_rows('WCGI'); ?>
      </table>
      <h3>Flexible Core: U.S. Experience in its Diversity (USED)</h3>
      <table>
        <?php course_rows('USED'); ?>
      </table>
      <h3>Flexible Core: Creative Expression (CE)</h3>
      <table>
        <?php course_rows('CE'); ?>
      </table>
      <h3>Flexible Core: Individual and Society (IS)</h3>
      <table>
        <?php course_rows('IS'); ?>
      </table>
      <h3>Flexible Core: Scientific World (SW)</h3>
      <table>
        <?php course_rows('SW'); ?>
      </table>
      <h3>College Option: Literature (LIT)</h3>
      <table>
        <?php course_rows('LIT'); ?>
      </table>
      <h3>College Option: Language (LANG)</h3>
      <table>
        <?php course_rows('LANG'); ?>
      </table>
      <h3>College Option: Science (SCI)</h3>
      <table>
        <?php course_rows('SCI'); ?>
      </table>
      <h3>College Option: Other</h3>
      Any LPS or Flexible Core course listed above, plus the following Synthesis (SYN) courses.
      <table>
        <?php course_rows('SYN'); ?>
      </table>
      <h2>Perspectives (PLAS) Courses</h2>
      <h3>Appreciating and Participating in the Arts (AP)</h3>
      <table>
        <?php course_rows('AP'); ?>
      </table>
      <h3>Cultures and Values (CV)</h3>
      <table>
        <?php course_rows('CV'); ?>
      </table>
      <h3>Natural Science (NS)</h3>
      <table>
        <?php course_rows('NS'); ?>
      </table>
      <h3>Natural Science with Lab (NS+L)</h3>
      <table>
        <?php course_rows('NS+L'); ?>
      </table>
      <h3>Reading Literature (RL)</h3>
      <table>
        <?php course_rows('RL'); ?>
      </table>
      <h3>Analyzing Social Structures (SS)</h3>
      <table>
        <?php course_rows('SS'); ?>
      </table>
      <h3>United States (US)</h3>
      <table>
        <?php course_rows('US'); ?>
      </table>
      <h3>European Traditions (ET)</h3>
      <table>
        <?php course_rows('ET'); ?>
      </table>
      <h3>World Cultures (WC)</h3>
      <table>
        <?php course_rows('WC'); ?>
      </table>
      <h3>Pre-Industrial Society (PI)</h3>
      <table>
        <?php course_rows('PI'); ?>
      </table>
    </div>
  </body>
</html>
