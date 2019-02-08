<?php
/* Test the Senate_Mail interface to mail.py
 */

require('mail_setup.php');
require('sanitize.php');

  echo "<h1>Testing Senate_Mail interface to <em>mail.py</em></h1>\n";

$html_msg = <<<EOD
<h1>Testing Senate_Mail</h1>
<h2>This is a "swell" message, don't you think?</h2>
<h2>This is a “smart” message, don’t you think?</h2>

EOD;
$text_msg = <<<EOD
  TESTING Senate_Mail
  This is a "swell" message, don't you think?
  This is a “smart” message, don’t you think?

EOD;
  // The testing strings
  $from = 'Senate Email Test <Christopher.Vickery@qc.cuny.edu>';
  $to = 'Christopher.Vickery@qc.cuny.edu';
  $subject = "'testing' don’t question authority";
  $recipient = array('cvickery@gmail.com', 'Dr. Christopher Vickery');
  $cc = 'poffice@qc.cuny.edu';
  $bcc = array('cvickery@qc.cuny.edu', 'Charles Christopher Vickery');
  $reply_to = array('nobody@qc.cuny.edu', 'No Reply');

  //  Construct the message
  $mail = new Senate_Mail($from, $to, $subject, $text_msg, $html_msg);
  $mail->add_recipient($recipient[0], $recipient[1]);
  $mail->add_cc($cc);
  $mail->add_bcc($bcc[0], $bcc[1]);
  $mail->set_reply_to($reply_to[0], $reply_to[1]);

  //  Send it
  $status = $mail->send();

  //  Report
  if (! $status)
  {
    echo "<h1>Failed!</h1>\n<p>available information (if any): “" .
          $mail->getMessage() . "”</p>\n";
  }
  else
  {
    echo "<h1>Mail Sent!</h1>\n";
    echo <<<EOD
  <table>
  <tr><td>From:</td><td>$from</td></tr>
  <tr><td>To:</td><td>$to</td></tr>
  <tr><td>Subject:</td><td>$subject</td></tr>
  <tr><td>To:</td><td>$recipient[0], $recipient[1]</td></tr>
  <tr><td>Cc:</td><td>$cc</td></tr>
  <tr><td>Bcc:</td><td>$bcc[0], $bcc[1]</td></tr>
  <tr><td>Reply To:</td><td>$reply_to[0], $reply_to[1]</td></tr>
  </table>
EOD;
  }
 ?>
