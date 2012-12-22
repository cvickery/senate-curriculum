<?php
  //  Minutes/index.php
  date_default_timezone_set('America/New_York');
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
    <title>Pathways Committee Minutes</title>
    <link rel="stylesheet" href="../css/pathways.css" type="text/css" />
    <link rel="stylesheet" href="../css/minutes.css" type="text/css" />
  </head>
  <body>
    <ul id='nav'>
      <li><a href='../' title='Main page'>Home</a></li>
      <li><a href='http://senate.qc.cuny.edu/pways'
             title="Forum for discussing issues related to the committee's work">
        Questions and Answers</a>
      </li>
      <li><a href="../Documents"
               title="CUNY, QC, and Committee documents">
          Documents</a>
      </li>
    </ul>
    <h1>Pathways Committee Minutes</h1>
    <div>
      <?php
        //  Find all the YYYY-mm-dd.pdf files, sort them in reverse date order, and
        //  group them by year
        $dir = opendir('.');
        $files = array();
        while ( $file = readdir($dir))
        {
          if (strpos($file, '.pdf') > 0)
          {
            $files[] = $file;
          }
        }
        $current_academic_year = '';
        rsort($files);
        foreach ($files as $file)
        {
          $date = new DateTime(substr($file, 0, 10));
          $year = substr($file, 0, 4 );
          $month = substr($file, 5, 2);
          if ( $month < 7 )
          {
            $fall = $year - 1;
            $spring = $year;
          }
          else
          {
            $fall = $year;
            $spring = $year + 1;
          }
          $academic_year = "$fall - $spring";
          if ($academic_year !== $current_academic_year)
          {
            $current_academic_year = $academic_year;
            echo "<h2>$current_academic_year</h2>\n";
          }
          $file_text = $date->format('F j, Y');
          echo "<p><a href='./$file'>$file_text</a></p>\n";
        }
				if (count($files) === 0) echo "<p>None yet</p>\n";
      ?>
    </div>
  </body>
</html>
