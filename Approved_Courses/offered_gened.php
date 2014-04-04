<?php
//  Curriculum/gened_offerings/advisement_list.php
  date_default_timezone_set('America/New_York');
  session_start();
  require_once('credentials.inc');

//  Which lists(s) to show
$show_path = false;
$show_plas = false;
foreach ($_GET as $name => $value)
{
  if (substr(strtolower($name), 0, 4) === 'path') $show_path = true;
  if (substr(strtolower($name), 0, 4) === 'plas') $show_plas = true;
  if (substr(strtolower($name), 0, 5) === 'persp') $show_plas = true;
}
if (! ($show_path || $show_plas))
{
  error_log("neither set");
  $show_path = $show_plas = true;
}

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
          if ($other_designation != $rd) $other_designations .= "$other_designation, ";
        }
        if ($other_designations !== '')
        {
          $other_designations = '(' . rtrim($other_designations, ', ') . ')';
        }
        echo "<div $status>$course_info $other_designations</div>\n";
      }
    }
    else
    {
    echo "<div>None</div>";
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
    <?php if ($show_path) { ?>
    <h2><?php echo $term_name;?> Scheduled Pathways Courses</h2>
    <p>
      <em>
        Last updated on <strong><?php echo $enrollment_date; ?></strong>.
        Courses in italics had fewer than 10% of their seats open on that date.
      </em>
    </p>
    <div class='course-list'>
      <h3>Required Core: College Writing 1 (EC-1)</h3>
        <?php course_rows('EC-1'); ?>
      <h3>Required Core: College Writing 2 (EC-2)</h3>
        <?php course_rows('EC-2'); ?>
      <h3>Required Core: Mathematics and Quantitative Reasoning (MQR)</h3>
        <?php course_rows('MQR'); ?>
      <h3>Required Core: Life and Physical Sciences (LPS)</h3>
        <?php course_rows('LPS'); ?>
      <h3>Flexible Core: World Cultures and Global Issues (WCGI)</h3>
        <?php course_rows('WCGI'); ?>
      <h3>Flexible Core: U.S. Experience in its Diversity (USED)</h3>
        <?php course_rows('USED'); ?>
      <h3>Flexible Core: Creative Expression (CE)</h3>
        <?php course_rows('CE'); ?>
      <h3>Flexible Core: Individual and Society (IS)</h3>
        <?php course_rows('IS'); ?>
      <h3>Flexible Core: Scientific World (SW)</h3>
        <?php course_rows('SW'); ?>
      <h3>College Option: Literature (LIT)</h3>
        <?php course_rows('LIT'); ?>
      <h3>College Option: Language (LANG)</h3>
        <?php course_rows('LANG'); ?>
      <h3>College Option: Science (SCI)</h3>
        <?php course_rows('SCI'); ?>
      <h3>College Option: Other</h3>
      Any LPS or Flexible Core course listed above, plus the following Synthesis (SYN) courses.
        <?php course_rows('SYN'); ?>
    </div>

    <?php }
      if ($show_plas) { ?>
    <h2><?php echo $term_name;?> Scheduled Perspectives (PLAS) Courses</h2>
    <p>
      <em>
        Last updated on <strong><?php echo $enrollment_date; ?></strong>.
        Courses in italics had fewer than 10% of their seats open on that date.
      </em>
    </p>
    <div class='course-list'>
      <h3>Appreciating and Participating in the Arts (AP)</h3>
        <?php course_rows('AP'); ?>
      <h3>Cultures and Values (CV)</h3>
        <?php course_rows('CV'); ?>
      <h3>Natural Science (NS)</h3>
        <?php course_rows('NS'); ?>
      <h3>Natural Science with Lab (NS+L)</h3>
        <?php course_rows('NS+L'); ?>
      <h3>Reading Literature (RL)</h3>
        <?php course_rows('RL'); ?>
      <h3>Analyzing Social Structures (SS)</h3>
        <?php course_rows('SS'); ?>
      <h3>United States (US)</h3>
        <?php course_rows('US'); ?>
      <h3>European Traditions (ET)</h3>
        <?php course_rows('ET'); ?>
      <h3>World Cultures (WC)</h3>
        <?php course_rows('WC'); ?>
      <h3>Pre-Industrial Society (PI)</h3>
        <?php course_rows('PI'); ?>
      <h3>Abstract or Quantitative Reasoning (AQR)</h3>
        <?php course_rows('AQR'); ?>
    </div>
    <?php } ?>
  </body>
</html>
