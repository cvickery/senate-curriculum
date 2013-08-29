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
    <title>Request RD Change</title>
    <link rel="stylesheet" type="text/css" href="request_rd_change.css" />
    <script type="text/javascript" src="jquery-1.10.2.min.js"></script>
    <script type="text/javascript" src="request_rd_change.js"></script>
  </head>
  <body>
    <h1>Request Requirement Designation Change</h1>
    <form action='submit-request.php' method='post'>
      <fieldset><legend><strong>Instructions</strong></legend>
        <p>
          If you have completed or are registered for a course that has two or more
          general education requirement designations (RD’s), you can use this page
          to request to have one of the alternate designation applied to your academic record
          at Queens College.
        </p>
        <p>
          To use this form, you must provide your QC email address. When you submit
          your request, an email message will be sent to that email address, and you
          must click a link in that email to submit your request to the registrar.
        </p>
        <p>
          <strong>The only email address that will work is your Queens College email
          address. You must respond to the email message sent to that address to complete
          your request.</strong>
        </p>
      </fieldset>
      <fieldset>
        <p>
          <label for='email'>Your Queens College Email Address:</label><br/>
          <input type='text' name='email' id='email'/>@qc.cuny.edu
        </p>
        <label for='course'>Select Your Course:</label><br/>
        <?php
          $query = <<<EOD
select c.discipline, c.course_number, c.course_title, c.suffixes, d.is_primary, p.abbr
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
          $course_strings       = array();
          $course_titles        = array();
          $primaries            = array();
          $others               = array();
          $result = pg_query($curric_db, $query) or
                    die("<h1 class='error'>Course query failed</h1>");
          while ($row = pg_fetch_assoc($result))
          {
            //  Agglomerate courses
            $this_course  = $row['discipline'] . '-' . $row['course_number'];
            $course_titles[$this_course] = $row['course_title'];
            $suffixes     = $row['suffixes'];
            if ($last_course !== $this_course)
            {
              if ($last_course !== '')
              {
                $course_strings[$last_course] = $last_course;
              }
              $last_course                    = $this_course;
              $last_course_str                = $this_course;
              $last_course_primary            = '';
              $last_course_others             = array();

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
              $others[$last_course][] = $row['abbr'];
            }
          }
          $course_strings[$last_course] = $last_course_str;
        ?>

          <?php
            $options = "<option value='none'>Select Course</option>\n";
            $divs = '';
            foreach ($others as $course => $list)
            {
              if (count($list) > 0)
              {
                $options .= "<option value='$course'>{$course_strings[$course]}</option>\n";
                $primary = $primaries[$course];
                $alternates = '';
                foreach ($list as $designation)
                {
                  $alternates .= <<<EOD
  <p>
    <input type='hidden' name='primary' value='$primary'/>
    <input type='radio' name='new-rd' value='$designation'/>
    Use $designation instead of $primary for $course.
  </p>
EOD;
                }
                $divs .= <<<EOD
<div id='{$course}-div' class='course-div' style='display:none;'>
  $alternates
</div>

EOD;
              }
            }
          ?>
        <select id='course' name='course'>
          <?php echo "$options"; ?>
        </select>
        <?php echo $divs; ?>
        <button type='submit' disabled='disabled'>Incomplete</button>
      </fieldset>
    </form>
  </body>
</html>
