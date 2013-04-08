<?php
// Proposal_Manager/sent_verification.php
set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() . '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');
require_once('proposal_manager.inc');

if ( !(isset($person) && isset($_SESSION[proposal])) )
{
  //  Attempt to access this page while not logged in or no proposal to verify
  $_SESSION[login_error_msg] = 'No proposal submitted';
  header("LOcation: $site_home_url");
  exit;
}

//  At this point, the user's session loses the current proposal
unset($_SESSION[proposal]);

//  Here beginneth the web page
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
    <title>Sent Verification Link</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/proposal_editor.css" />
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/site_ui.js"></script>
  </head>
  <body>
<?php
  //  Status Bar and H1 element
  //  --------------------------------------------------------------------------------
  $status_msg = login_status();
  $nav_bar    = site_nav();
  echo <<<EOD
    <div id='status-bar'>
      $instructions_button
      $status_msg
      $nav_bar
    </div>
    <div>
    <h1>Check Your Email</h1>
    $dump_if_testing
    <p>
      A message containing a link for submitting your proposal has been sent to
      <em>$person</em>.
    </p>
    <p>
      The proposal will not be acted upon until you click on the link in that message.
      The link will remain active for a month.
    </p>
EOD;
?>
  </body>
</html>
