<?php
//  Curriculum/signin.php

set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() .  '/scripts'
    . PATH_SEPARATOR . getcwd() . '/include');
require_once('init_session1.php');

//  Generic signin page
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
    <title>QC Curriculum Sign In</title>
    <link rel="stylesheet" type="text/css" href="css/curriculum.css" />
    <script type='text/javascript' src='js/jquery.min.js'></script>
    <script type='text/javascript' src='js/site_ui.js'></script>
  </head>
  <body>


<?php
  $nav_bar = site_nav();
  echo <<<EOD
    <div id='status-bar'>
      <button id='show-hide-instructions-button'>Hide Instructions</button>
      $nav_bar
    </div>
    <h1>Queens College Curriculum</h1>

EOD;
  if (isset($person))
  {
    echo <<<EOD
    <h2>Sign in</h2>
    <form action='.' method='post'>
      <input type='hidden' name='form-name' value='logout' />
      <p>
        You are already logged in as $person.
      </p>
      <p>
        You may go to one of the links at the top of this page or <button
        type='submit'>Sign Out</button>
      </p>

EOD;
    if (! empty($referer_url))
    {
      //  The following should never appear because nobody should generate a link to this
      //  page if the person is already signed in. They should get here only from a saved
      //  URL.
      echo <<<EOD
      <p>
        The back button will return you to <a href='$referer_url'>$referer_url</a>
      </p>

EOD;
    }
    echo "      </form>\n";

  }
  else
  {
    require_once('login1.php');
  }
?>
  </body>
</html>
