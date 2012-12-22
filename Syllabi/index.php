<?php  /* Syllabi/index.php */

set_include_path(get_include_path() . PATH_SEPARATOR . '../scripts' );
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
  </head>
  <body>
    <h1>Current Syllabi</h1>
<?php
  //  Handle the logging in/out situation here
  $last_login       = '';
  $status_msg       = 'Not signed in';
  $person           = '';
  $sign_out_button  = '';
  require_once('../scripts/short-circuit.php');
  require_once('../scripts/login.php');
  if (isset($_SESSION[session_state]) && $_SESSION[session_state] === ss_is_logged_in)
  {
    if (isset($_SESSION[person]))
    {
      $person = unserialize($_SESSION[person]);
    }
    else
    {
      die("<h1 class='error'>Proposal Reviews: Invalid login state</h1></body></html>");
    }

    $status_msg = sanitize($person->name) . ' / ' . sanitize($person->dept_name);
    $last_login = 'First login';
    if ($person->last_login_time)
    {
      $last_login   = "Last login at ";
      $last_login  .= $person->last_login_time . ' from ' . $person->last_login_ip;
    }
    $sign_out_button = <<<EOD

    <form id='logout-form' action='.' method='post'>
      <input type='hidden' name='form-name' value='logout' />
      <button type='submit'>Sign Out</button>
    </form>

EOD;
  }
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
  //  Status/Nav Bars
  //  =================================================================================
  /*  Generated here, after login status is determined, but displayed up top by the
   *  wonders of CSS.
   */
  //  First row link to Review Editor depends on the user having something to review
  $review_link = '';
  if ($person && $person->has_reviews)
  {
    $review_link = "<a href='../Review_Editor'>Edit Reviews</a>";
  }
  echo <<<EOD
  <div id='status-bar'>
    $sign_out_button
    <div id='status-msg' title='$last_login'>
      $status_msg
    </div>
    <!-- Navigation -->
    <nav>
      <a href='../Proposals'>Browse Proposals</a>
      <a href='../Model_Proposals'>Model Proposals</a>
      <a href='../Proposal_Editor'>Proposal Editor</a>
      <a href='.' class='current-page'>Browse Syllabi</a>
      <a href='../Reviews'>Browse Reviews</a>
      $review_link
    </nav>
  </div>

EOD;
?>
  </body>
</html>
