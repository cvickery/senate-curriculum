<?php  /* Admin/index.php */

set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() .  '/../scripts' 
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');
require_once('admin.inc');                       // Must be logged in as an administrator
$login_status = login_status();

//  Here beginnith the web page
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
    <title>Event Editor</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/admin.css" />
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/site_ui.js"></script>
  </head>
  <body>
<?php

  //  Generate Status Bar and Page Content
  $nav_bar = site_nav();
  $admin_nav = admin_nav();
  echo <<<EOD
  <!-- Status Bar -->
  <div id='status-bar'>
    $instructions_button
    $login_status
    $nav_bar
    $admin_nav
  </div>
  <h1>Administration</h1>
  $dump_if_testing
    <p>
      Live long and prosper.
    </p>
    <p>
      <em>You may also select one of the links above.</em>
    </p>

EOD;

?>
  </body>
</html>
