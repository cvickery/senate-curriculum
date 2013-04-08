<?php
// Syllabi/index.php
set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() . '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');
require_once('syllabus_utils.php');

//  Here beginnith the web page
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
    <title>Syllabi</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/syllabi.css" />
    <script type='text/javascript' src="../js/jquery.min.js"></script>
    <script type='text/javascript' src="../js/site_ui.js"></script>
  </head>
  <body>
<?php
  //  Status Bar and H1 element
  $status_msg = login_status();
  $nav_bar    = site_nav();
  echo <<<EOD
  <div id='status-bar'>
    $instructions_button
    $status_msg
    $nav_bar
  </div>
  <div>
    <h1>Course Syllabi</h1>
    <h2>
      All syllabi copyright © Queens College of CUNY all rights reserved unless otherwise
      noted.
    </h2>
    $dump_if_testing

EOD;

  $dir = opendir('.');
  $syllabi = array();
  //  Collect list of syllabi, indexed by course
  while ($file = readdir($dir))
  {
    if (preg_match('/^([A-Z]+)-(\d+[A-Z]?)_(\d{4}-\d{2}-\d{2})\.pdf$/', $file, $matches))
    {
      $discipline = $matches[1];
      $course_number = $matches[2];
      $syllabi["$discipline $course_number"] = $file;
    }
  }

  ksort($syllabi);
  //  Now display links with discipline headings
  $current_discipline = '';
  foreach ($syllabi as $course => $file)
  {
    preg_match('/^([A-Z]+)-(\d+[A-Z]?)_(\d{4}-\d{2}-\d{2})\.pdf$/', $file, $matches);
    $discipline = $matches[1];
    $course_number = $matches[2];
    $size_str = humanize_num(filesize($file));
    preg_match('/(\d{4})-(\d{2})-(\d{2})/', $matches[3], $date_matches);
    $date_str = matches2datestr($date_matches);
    if ($discipline !== $current_discipline)
    {
      $current_discipline = $discipline;
      echo "<h2>$current_discipline</h2>\n";
    }
    echo <<<EOD
    <p>
      <a href='$file'>$discipline $course_number</a>
      ($size_str) $date_str
    </p>
EOD;
  }
?>
    </div>
  </body>
</html>
