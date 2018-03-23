<?php
//  Curriculum/gened_offerings/advisement_list.php
  date_default_timezone_set('America/New_York');
  session_start();
  require_once('credentials.inc');

//  Requirement designations and their names
$path_rds = array
(
  'EC-1' =>     'Required Core: College Writing 1',
  'EC-2' =>     'Required Core: College Writing 2',
  'MQR'  =>     'Required Core: Mathematics and Quantitative Reasoning',
  'LPS'  =>     'Required Core: Life and Physical Sciences',
  'WCGI' =>     'Flexible Core: World Cultures and Global Issues',
  'USED' =>     'Flexible Core: U.S. Experience in its Diversity',
  'CE'   =>     'Flexible Core: Creative Expression',
  'IS'   =>     'Flexible Core: Individual and Society',
  'SW'   =>     'Flexible Core: Scientific World',
  'LIT'  =>     'College Option: Literature',
  'LANG' =>     'College Option: Language',
  'SCI'  =>     'College Option: Science',
  'SYN'  =>     'College Option: Other'
);
$plas_rds = array
(
  'AP'   =>     'Appreciating and Participating in the Arts',
  'CV'   =>     'Cultures and Values',
  'NS'   =>     'Natural Science',
  'NS+L' =>     'Natural Science with Lab',
  'RL'   =>     'Reading Literature',
  'SS'   =>     'Analyzing Social Structures',
  'US'   =>     'United States',
  'ET'   =>     'European Traditions',
  'WC'   =>     'World Cultures',
  'PI'   =>     'Pre-Industrial Society',
  'AQR'  =>     'Abstract or Quantitative Reasoning',
);

//  Which lists(s) to show
$show_path  = false;
$show_plas  = false;
$show_w     = false;
foreach ($_GET as $name => $value)
{
  if (substr(strtolower($name), 0, 4) === 'path')   $show_path  = true;
  if (substr(strtolower($name), 0, 4) === 'plas')   $show_plas  = true;
  if (substr(strtolower($name), 0, 5) === 'persp')  $show_plas  = true;
  if (substr(strtolower($name), 0, 1) === 'w')      $show_w     = true;
}
if (! ($show_path || $show_plas || $show_w))
{
  $show_path = $show_plas = $show_w = true;
}

//  course_rows()
//  -----------------------------------------------------------------------------------------------
/*  Echo list of courses that satisfy the specified RD and are offered during $term_code.
 */
  function course_rows($rd, $msg = NULL)
  {
    global $curric_db, $term_code, $plas_rds, $path_rds;
    $is_plas = false;
    $is_path = false;
    if (array_key_exists($rd, $plas_rds))
    {
      $is_plas = true;
      echo "      <h3>{$plas_rds[$rd]} ($rd)</h3>\n";
    }
    else if (array_key_exists($rd, $path_rds))
    {
      $is_path = true;
      echo "      <h3>{$path_rds[$rd]} ($rd)</h3>\n";
    }
    else
    {
      die("<h1 class='error'>Error: unrecognized RD ($rd) at " . basename(__FILE__) . " line " .
          __LINE__ . "</h1>\n");
    }
    if ($msg) echo "      <p>$msg</p>\n";
    $query = <<<EOD
select    a.discipline,
          a.course_number,
          o.suffixes,
          o.component,
          a.course_title,
          sum(o.sections)   as sections,
          sum(o.seats)      as seats,
          sum(o.enrollment) as enrollment
from      approved_courses a, offered_gened o
where     o.term_code     = $term_code
and       o.designation   = '$rd'
and       a.discipline    = o.discipline
and       a.course_number = o.course_number
group by  a.discipline, a.course_number, a.course_title,
          o.suffixes, o.sections, o.seats, o.enrollment, o.component
order by  a.discipline, a.course_number, o.component desc

EOD;
    $result = pg_query($curric_db, $query)
    or die("<h1 class='error'>Query failed: " . basename(__FILE__) . ' line ' . __LINE__ .
           pg_last_error($curric_db) .
           "</h1>");
    if (pg_num_rows($result) > 0)
    {
      while ($row = pg_fetch_assoc($result))
      {
        $discipline         = $row['discipline'];
        $course_number      = $row['course_number'];
        if ($row['component'] === 'LAB')
        {
          //  Do not display lab sections: it makes students think that's
          //  all they have to take.
          continue;
        }
        $if_lab             = ($row['component'] === 'LAB') ? ' (Lab)' : '';
        $course_number_str  = $course_number;
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

        $course_info      = "<span>$discipline $course_number_str$if_lab. {$row['course_title']}</span>";
        $seats            = $row['seats'];
        $enrollment       = $row['enrollment'];
        $status                                 = " class='open'";
        if ($enrollment > 0.9 * $seats) $status = " class='warn'";
        if ($enrollment >= $seats)      $status = " class='closed'";
        $enrollment_info  = "<span>({$row['sections']}, $seats, $enrollment)</span>";

        //  List other designations satisfied, if any
        $other_query = <<<EOD
  select abbr as designation
  from proposal_types
  where id in ( select  designation_id
                from    course_designation_mappings
                where   discipline    = '$discipline'
                and     course_number = $course_number )

EOD;
        $other_result = pg_query($curric_db, $other_query)
        or die("<h1 class='error'>Query failed: " . basename(__FILE__) . ' line ' . __LINE__ ."</h1>");
        $other_designations = "";
        while ($other_row = pg_fetch_assoc($other_result))
        {
          $other_designation = $other_row['designation'];
          if ($other_designation != $rd)
          {
            if ( ($is_plas && array_key_exists($other_designation, $plas_rds)) ||
                 (!$is_plas && array_key_exists($other_designation, $path_rds))
               )
            $other_designations .= "$other_designation, ";
          }
        }
        if ($other_designations !== '')
        {
          $other_designations = '(' . rtrim($other_designations, ', ') . ')';
        }
        echo "      <div $status>$course_info $other_designations</div>\n";
      }
    }
    else
    {
    echo "      <div>None</div>";
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
    <link rel='stylesheet' type='text/css' href="css/offered_gened.css" />
  </head>
  <body>
    <h1>General Education Course Offerings</h1>
    <div>
      <div id='semester-info'>
        <h2>
          The following General Education courses are scheduled to be offered during the
          <em><?php echo $term_name; ?></em> semester.
        </h2>
        <h2>Other Semesters Available:</h2>
        <?php
          echo "<ul id='other-term-links'>\n";
          foreach ($term_codes as $other_term_code => $other_term_name)
          {
            if ($term_code != $other_term_code)
            {
              echo "<li><a href='./offered_gened.php?t=$other_term_code'>$other_term_name</a></li>\n";
            }
          }
            echo "</ul>\n";
        ?>
      </div>
    </div>
    <?php
    if ($show_path)
    {
      echo <<<EOD
    <h2>$term_name Scheduled Pathways Courses</h2>
    <div class='preamble'>
      <p>
        Students who entered Queens College in the Fall 2013 semester or later follow the Pathways curriculum.
      </p>
      <p>
        The following courses satisfy Pathways requirements <em>and</em> are scheduled to be offered
        during the $term_name semester. The list is accurate as of $enrollment_date, but may change as
        additional courses are scheduled or if scheduled courses are canceled during the enrollment
        period.
      </p>
    <p>
      <em>
        Courses in italics had fewer than 10% open seats as of $enrollment_date.
      </em>
    </p>
    <p class='print-only'>
      The current version of this list is available online at http://bit.ly/R20mGz .
    </p>
    <p>
      Each course may be used to satisfy just one requirement, but some courses appear in multiple areas.
      In those cases, you may choose to have the course satisfy any <em>one</em> of those areas. See an advisor in
      the Advising Center (Kiely 217) for more information on this option. Courses that are listed in multiple
      areas show the abbreviations of their other areas in parentheses following the course title.
    </p>
    <p>
      Some courses have co-requisites, which are listed in the course catalog. In those cases,
      <em>both</em> the course shown here <em>and</em> its co-requisite(s) must be completed to
      satisfy the listed requirement.
    </p>
    </div>
    <div class='course-list'>

EOD;
      course_rows('EC-1');
      course_rows('EC-2');
      course_rows('MQR');
      course_rows('LPS');
      course_rows('WCGI');
      course_rows('USED');
      course_rows('CE');
      course_rows('IS');
      course_rows('SW');
      course_rows('LIT');
      course_rows('LANG');
      course_rows('SCI');
      course_rows('SYN', 'Any LPS or Flexible Core course listed above, plus the following Synthesis courses.');

      echo "    </div>\n";
    }

    if ($show_plas)
    {
      echo <<<EOD
    <h2>$term_name Scheduled Perspectives (PLAS) Courses</h2>
    <div class='preamble'>
      <p>
        Students who entered Queens College after the Fall 2009 semester but before the Fall 2013 semester follow
        the Perspectives in the Liberal Arts and Sciences (PLAS) curriculum.
      </p>
      <p>
        The following courses satisfy Perspectives (PLAS) requirements <em>and</em> are scheduled to be offered
        during the $term_name semester. The list is accurate as of $enrollment_date, but may change as
        additional courses are scheduled or if scheduled courses are canceled during the enrollment period.
      </p>
    <p>
      <em>
        Courses in italics had fewer than 10% open seats as of $enrollment_date.
      </em>
    </p>
    <p class='print-only'>
      The current version of this list is available online at http://bit.ly/R20mGz .
    </p>
    <p>
      Each course may be used to satisfy just one area requirement (AP, CV, NS, NS+L, RL, or SS), but some courses
      may also satisfy a context of experience requirement (US, ET, or WC), and/or an extended requirement (PI or
      AQR). Courses that can satisfy multiple requirements show the abbreviations of their other requirements in
      parentheses following the course title.
    </p>
    <p>
      Some courses have co-requisites, which are listed in the course catalog. In those cases,
      <em>both</em> the course shown here <em>and</em> its co-requisite(s) must be completed to
      satisfy the listed requirement.
    </p>
    </div>
    <div class='course-list'>

EOD;
      course_rows('AP');
      course_rows('CV');
      course_rows('NS');
      course_rows('NS+L');
      course_rows('RL');
      course_rows('SS');
      course_rows('US');
      course_rows('ET');
      course_rows('WC');
      course_rows('PI');
      course_rows('AQR');
      echo "    </div>\n";
    }

    if ($show_w)
    {
      $query = <<<EOD
  select * from w_enrollments
  where term_code = '$term_code'
EOD;
      $result = pg_query($curric_db, $query) or die("<h1 class='error'>W Lookup Failed at " . basename(__FILE__) .
                                                    " line " . __LINE__ . "</h1>\n");
      $suffix = 's are';
      $num_rows = pg_num_rows($result);
      if (1 === $num_rows) $suffix = ' is';

      echo <<<EOD
      <h2>$term_name Scheduled Writing-Intensive (W) Courses</h2>
      <div class='preamble'>
        <p>
          Students who entered Queens College after the Fall 2009 semester but before the Fall 2013 semester follow
          the Perspectives in the Liberal Arts and Sciences (PLAS) curriculum, and must complete three Writing-Intensive
          (W) courses in order to graduate. Perspectives students may elect to take a Pathways EC-2 (College Writing 2)
          course as one of their three W courses.
        </p>
        <p>
          Students who entered Queens College in the Fall 2013 semester or later follow the Pathways curriculum,
          and must complete two W courses in order to graduate.
        </p>
        <p>
          To avoid the need to take extra courses, students should plan their course of study at Queens in a way that
          maximizes the overlap between W courses and courses taken to complete the requirements for their Major or
          other General Education requirements. Students who transfer from institutions that do not indicate
          writing-intensive courses on the transcript should make sure they receive credit for having completed the
          proper number of W courses. Contact the Advising Center (Kiely 217) if there are problems.
        </p>
        <p>
          The following $num_rows writing-intensive course$suffix scheduled to be offered
          during the $term_name semester. The list is accurate as of $enrollment_date, but may change as
          additional courses are scheduled or if scheduled courses are canceled during the enrollment period.
        </p>
        <p>
          <em>
            Courses in italics had fewer than 10% open seats as of $enrollment_date.
          </em>
          (Abbreviations in parentheses give Perspectives and Pathways requirements also satisfied by each
                    course.)
        </p>
        <p class='print-only'>
          The current version of this list is available online at http://bit.ly/R20mGz .
        </p>
      </div>

EOD;
      if (0 === $num_rows)
      {
        echo "<h3>No Writing-intensive Courses are scheduled to be offered during $term_name</h3>\n";
      }
      else
      {
        echo "      <div class='course-list'>\n";
        while ($row = pg_fetch_assoc($result))
        {
          $discipline         = $row['discipline'];
          $course_number      = $row['course_number'];
          $course_info        = "<span>$discipline ${course_number}W. {$row['course_title']}</span>";
          $seats              = $row['seats'];
          $enrollment         = $row['enrollment'];
          $status                                 = " class='open'";
          if ($enrollment > 0.9 * $seats) $status = " class='warn'";
          if ($enrollment >= $seats)      $status = " class='closed'";
          $enrollment_info  = "<span>({$row['sections']}, $seats, $enrollment)</span>";

          //  List other designations satisfied, if any
          $other_query = <<<EOD
  select abbr as designation
  from proposal_types
  where id in ( select  designation_id
                from    course_designation_mappings
                where   discipline    = '$discipline'
                and     course_number = $course_number )

EOD;
          $other_result = pg_query($curric_db, $other_query)
          or die("<h1 class='error'>Query failed: " . basename(__FILE__) . ' line ' . __LINE__ ."</h1>");
          $other_designations = "";
          while ($other_row = pg_fetch_assoc($other_result))
          {
            $other_designations .= $other_row['designation'] . ', ';
          }
          if ($other_designations !== '')
          {
            $other_designations = '(' . rtrim($other_designations, ', ') . ')';
          }
          echo "      <div $status>$course_info $other_designations</div>\n";
        }
        echo "</div>\n";
      }
    }
    ?>
  </body>
</html>
