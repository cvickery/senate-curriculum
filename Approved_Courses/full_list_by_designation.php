<?php
//  Curriculum/Approved_Courses/full_list_by_designation.php
  date_default_timezone_set('America/New_York');
  session_start();
  require_once('credentials.inc');

//  course_rows()
//  -----------------------------------------------------------------------------------------------
/*  Echo list of courses that satisfy the specified RD.
 */
  function course_rows($rd)
  {
    global $curric_db;

    $discipline     = '';
    $course_number  = 0;
    $query = <<<EOD
select    a.discipline      as discipline,
          a.course_number   as course_number,
          a.suffixes        as suffixes,
          t.abbr            as designation,
          t.full_name       as designation_string,
          a.course_title    as course_title
from      approved_courses a, course_designation_mappings m, proposal_types t
where     t.abbr          = '$rd'
and       t.id            = m.designation_id
and       a.discipline    = m.discipline
and       a.course_number = m.course_number
order by  t. abbr, a.discipline, a.course_number

EOD;

    $result = pg_query($curric_db, $query)
    or die("<h1 class='error'>Query failed: " . basename(__FILE__) . ' line ' . __LINE__ ."</h1>");
    if (pg_num_rows($result) > 0)
    {
      while ($row = pg_fetch_assoc($result))
      {
        $discipline         = $row['discipline'];
        $course_number      = $row['course_number'];
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

        $course_info      = "<span>$discipline $course_number_str. {$row['course_title']}</span>";

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
        echo "<div>$course_info $other_designations</div>\n";
      }
    }
    else
    {
    echo "<div>None</div>";
    }
  }

  $curric_db    = curric_connect() or die('Unable to access db');
  $result       = pg_query($curric_db, "select * from update_log") or die('Query failed in ' . basename(__FILE__)
                                                                        . ' line ' . __LINE__);
  $table_dates  = array();
  while ($row = pg_fetch_assoc($result))
  {
    $table_dates[$row['table_name']] = new DateTime(substr($row['updated_date'], 0, 10 ));
  }
  foreach ($table_dates as $table => $date)
  {
    $table_dates[$table] = $date->format('F j, Y');
  }


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
      .course-list {
        -moz-column-count: 3;
        -moz-column-gap: 1em;
        -moz-column-rule: 1px solid black;
        -webkit-column-count: 3;
        -webkit-column-gap: 1em;
        -webkit-column-rule: 1px solid black;
      }
    </style>
  </head>
  <body>
    <h1>Approved General Education Courses</h1>
    <div>
      <p>CUNYfirst catalog information last updated <?php echo $table_dates['cf_catalog']; ?></p>
      <p>Approved course list last updated <?php echo $table_dates['approved_courses']; ?></p>
    </div>
    <div class='course-list'>
      <h2>Pathways Courses</h2>
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
      <h2>Perspectives (PLAS) Courses</h2>
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
    </div>
  </body>
</html>
