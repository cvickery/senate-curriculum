<?php
// Proposal_Manager/review_designation_proposal.php
set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() . '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');

require_once('mail_setup.php');


function sendme($submitter_email)
{
  //  Send the email: both text-only and MIME (HTML) formats
  //  -------------------------------------------------------------------------

  //  text-only version
  $text_msg = <<<EOD

Dear Christopher,

This is to inform you that you have successfully sent yourself an email.

EOD;

  //  HTML version
  $html_msg = <<<EOD

  <h1>Dear Christopher,</h1>
  <h2>Your have experienced success</h2>

EOD;
  $mail = new Senate_Mail('QC Curriculum<do-not-reply@qc.cuny.edu>', $submitter_email,
    "And a Happy Easter Day to you!",
     $text_msg, $html_msg);
  $mail->send() or die( "<h1>Email Failed" . $mail->getMessage() . "</h1>");
  echo "<h1>Hot diggity dog, $submitter_email</h1>";
}

//  Here beginneth the web page
//  =====================================================================================
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
    <title>Email Test</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel='stylesheet' type='text/css' href='../css/proposal_editor.css' />
  </head>
  <body>
<?php
  sendme('Christopher.Vickery@qc.cuny.edu');
  sendme('cvickery@gmail.com');
?>
  </body>
</html>

