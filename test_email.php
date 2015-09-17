<?php
require_once('scripts/mail_setup.php');
$submitter_email = 'Christopher.Vickery@qc.cuny.edu';
$webmaster_email = 'yourself';
$email_sender     = 'An Academic Senate Robot';

  //  text-only version
  $text_msg = <<<EOD
This is your text test message

EOD;

  //  HTML version
  $html_msg = <<<EOD

<p>This is your HTML test message</p>

EOD;
  $mail = new Senate_Mail('QC Curriculum<nobody@qc.cuny.edu>', $submitter_email,
    "This is a test message",
     $text_msg, $html_msg);
  $mail->send() or die( $mail->getMessage() .
      " <a href='.'>try again</a> or report the problem to $webmaster_email");

  echo "<h1>Test message appears to have been sent. No errors reported.</h1>";
  exit;

?>