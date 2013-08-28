<?php //  Request_RD_Change/index.php
require_once('credentials.inc');
$curric_db = curric_connect();
//  Generate site index page
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
    <title>QC Curriculum</title>
    <link rel="stylesheet" type="text/css" href="css/curriculum.css" />
    <script type="text/javascript" src="js/jquery.min.js"></script>
  </head>
  <body>
    <h1>Request Requirement Designation Change</h1>
    <form>
      <fieldset><legend>Instructions</legend>
        <p>
          If you have completed or are registered for a course that has two or more
          general education requirement designations (RD’s), you can use this page
          to request to have an alternate designation applied to your academic record
          at Queens College.
        </p>
      </fieldset>
      <fieldset>
        <label for='email'>Queens College Email Address</label>
        <input type='text' id='email'/>
        <label for='course'>Select Course</label>
        <?php
          $query = <<<EOD
select c.discipline, c.course_number, c.suffixes, d.is_primary, p.abbr
from approved_courses c, course_designation_mappings d, proposal_types p
where c.discipline = d.discipline
and c.course_number = d.course_number
and d.designation_id = p.id
and d.reason != 'PLAS'
order by c.discipline, c.course_number
EOD;
          $last_course          = '';
          $last_course_str      = '';
          $last_course_primary  = '';
          $last_course_others   = array();
          $strings              = array();
          $primaries            = array();
          $others               = array();
          $result = pg_query($curric_db, $query) or
                    die("<h1 class='error'>Course query failed</h1>");
          while ($row = pg_fetch_assoc($result))
          {
            //  Agglomerate courses
            $this_course  = $row['discipline'] . '-' . $row['course_number'];
            $suffixes     = $row['suffixes'];
            if ($last_course !== $this_course)
            {
              if ($last_course !== '')
              {
                $strings[$last_course]    = $last_course;
                $primaries[$last_course]  = $last_course_primary;
                $others[$last_course]     = $last_course_others;
              }
              $last_course              = $this_course;
              $last_course_str          = $this_course;
              $last_course_primary      = '';
              $last_course_others       = array();

              if (1 === strlen($suffixes))
              {
                $last_course_str .= ($suffixes === '-') ? '' : $suffixes;
              }
              else
              {
                $last_course_str .= ($suffixes[0] === '-') ? '' : $suffixes[0];
                for ($i = 1; $i < strlen($suffixes); $i++)
                {
                  $last_course_str .= "/$last_course" . (($suffixes[$i] === '-') ? '' : $suffixes[$i]);
                }
              }
            }
            if ($row['is_primary'] === 't')
            {
              $primaries[$last_course] = $row['abbr'];
            }
            else
            {
              $others[$last_course] = $row['abbr'];
            }
          }
          $strings[$last_course]    = $last_course_str;
          $primaries[$last_course]  = $last_course_primary;
          $others[$last_course]     = $last_course_others;
echo count($strings) . ' ' . count($primaries) . ' ' . count($others) . "\n";
        ?>

        <select id='course' name='course'>
          <?php
            foreach ($others as $course => $string)
            {
              //echo "<option value='$course'>$string</option>\n";
            }
          ?>
        </select>
      </fieldset>
    </form>
  </body>
</html>
