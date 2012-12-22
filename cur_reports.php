<?php
  date_default_timezone_set('America/New_York');
  header("Vary: Accept");
  if (  array_key_exists("HTTP_ACCEPT", $_SERVER) &&
        stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml") ||
        stristr($_SERVER["HTTP_USER_AGENT"], "W3C_Validator")
      )
  {
    header("Content-type: application/xhtml+xml");
    echo "<?xml version='1.0' encoding='utf-8'?>\n";
    $html_attributes = ' xmlns="http://www.w3.org/1999/xhtml" xml:lang="en"';
  }
  else
  {
    header("Content-type: text/html; charset=utf-8");
    $html_attributes = '';
  }
 ?>
<!DOCTYPE html>
<html<?php echo $html_attributes;?>>
  <head>
    <title>BOT Course Approvals</title>
    <link rel="shortcut icon" href="../favicon.ico" />
    <link rel="stylesheet"
          type="text/css"
          media="all"
          href="css/cur_reports.css"
    />
    <script type="text/javascript" src="../js/jquery-1.6.4.min.js"></script>
  </head>
  <body>
  <h1>BOT Course Approvals</h1>
  <?php
    $keys = array();
    $dir = opendir('cur_reports/json.out');
    while ($file = readdir($dir))
    {
      if ( ! preg_match('/^\d{4}-\d{2}/', "$file") ) continue;
      $date_txt = substr($file, 0,7);
      $date_obj = new DateTime($date_txt);
      $date_str = $date_obj->format('M d, Y');
      $json_txt = file_get_contents("cur_reports/json.out/$file");
      $courses = json_decode($json_txt);
      if (is_array($courses))
      {
/*
 * prereq         68
 * id             180
 * disp           180
 * approval_date  180
 * credits        230
 * hours          230
 * justification  321
 * course_num     393
 * title          393
 * text           405
 */
        $num_courses = count($courses);
        $suffix = ($num_courses === 1) ? '' : 's';
        $n = 0;
        echo "<h2>$num_courses Course$suffix approved on $date_str:</h2>\n";
        foreach ($courses as $course)
        {
          $n++;
          $course->text = str_replace('&nbsp;', ' ', $course->text);
          foreach ($course as $key => $value)
          {
            if (isset($course->$key))
            {
              $course->$key = str_replace('&nbsp;', ' ', $value);
            }
          }
          echo "<div><h3>COURSE $n Original Text:</h3>\n<p class='text'>{$course->text}</p>\n";
          echo '<h3>';
          echo (isset($course->id) ?
              $course->id : "<span class='missing'>NO ID</span>") . '. ';
          echo (isset($course->disp) ?
              $course->disp : "<span class='missing'>NODISCP</span>") . ' ';
          echo (isset($course->course_num) ?
              $course->course_num : "<span class='missing'>###</span>") . '. ';
          echo (isset($course->title) ?
              $course->title : "<span class='missing'>NO TITLE</span>") . '.';
          echo "</h3><h3>\n";
          echo (isset($course->hours) ?
              $course->hours : "<span class='missing'>??</span>") . 'hr; ';
          echo (isset($course->credits) ?
              $course->credits : "<span class='missing'>??</span>") . 'cr. ';
          if (isset($course->prereq)) echo 'Prereq: ' . $course->prereq;
          echo "</h3><p class='text'>\n";
          echo (isset($course->description) 
            ? $course->description 
            : "<span class='missing'>No Course Description.</span>") . "</p>\n";          
          echo "<h3>Justification</h3>\n<p class='text'>\n";
          echo (isset($course->justification) 
            ? $course->justification
            : "<span class='missing'>No Justification.</span>") . "</p>\n";
          echo "<h3>Senate Approval: ";
          echo (isset($course->approval_date) 
            ? $course->approval_date
            : "<span class='missing'>No Approval Date.</span>") . "</h3>\n";
          echo "</div>\n";
        }
      }
      else
      {
        echo "<h2>No Queens College Information for $date_str</h2>\n";
      }
    }
  ?>
  </body>
</html>
