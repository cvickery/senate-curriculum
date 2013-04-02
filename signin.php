<?php
//  Curriculum/signin.php

set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() .  '/scripts' 
    . PATH_SEPARATOR . getcwd() . '/include');
require_once('init_session1.php');

$referer        = 'https://senate.qc.cuny.edu/Curriculum';
$referer_title  = 'Curriculum Home Page';

//  Generate generic login page
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
    <title>QC Curriculum Login</title>
    <link rel="stylesheet" type="text/css" href="css/curriculum.css" />
  </head>
  <body>
    <h1>Login</h1>
<?php
  echo "    <h2>Or <a href='$referer'>return to $referer_title</a></h2>\n";
  require_once('login1.php');
?>
  </body>
</html>
