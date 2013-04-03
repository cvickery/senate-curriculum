<?php
//  Curriculum/signin.php

set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() .  '/scripts' 
    . PATH_SEPARATOR . getcwd() . '/include');
require_once('init_session1.php');

$referer        = $site_home_url;
$referer_title  = $site_home_title;

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
  </head>
  <body>
    <h1>Sign In</h1>
<?php
  if (isset($person))
  {
    echo <<<EOD
    <form id='logout-form' action='.' method='post'>
      <input type='hidden' name='form-name' value='logout' />
      <p>You are already logged in as $person. You may either:</p>
      <button type='submit'>Sign Out</button>
      <p>or <a href='$referer'> return to $referer_title</a></p>
    </form>

EOD;
  }
  else
  {
    require_once('login1.php');
  }
?>
  </body>
</html>
