<?php
require_once('scripts/mail_setup.php');
$recipient_email  = 'Christopher.Vickery@qc.cuny.edu';
$email_sender     = 'An Academic Senate Robot';
$timestamp        = date("l F j, Y H:i");
  //  text-only version
  $text_msg = <<<EOD
This is your text test message, sent: $timestamp

EOD;

  //  HTML version
  $html_msg = <<<EOD

<p>This is your HTML test message, sent: $timestamp</p>

EOD;
  $mail = new Senate_Mail('QC Curriculum<nobody@qc.cuny.edu>', $recipient_email,
    "Jack’s Alive",
     $text_msg, $html_msg);
  $mail->add_recipient('cvickery@gmail.com');
  $mail->send() or die( $mail->getMessage() .
      " <a href='.'>try again</a> or report the problem to $webmaster_email");

  echo "<h1>Test message appears to have been sent. No errors reported.</h1>";
  exit;

?>