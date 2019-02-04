<?php
require_once('../scripts/mail_setup.php');
$recipient_email  = 'Christopher.Vickery@qc.cuny.edu';
$email_sender     = 'An Academic Senate Robot';
$timestamp        = date("l F j, Y H:i");

  //  plain text version
  $text_msg = <<<EOD
This is your text test message, sent: $timestamp

EOD;

  //  HTML version
  $html_msg = <<<EOD

<p>This is your HTML test message, sent: $timestamp</p>

EOD;

$plain_name = tempnam('/tmp/', 'plain');
$plain_file = fopen($plain_name, 'w');
$plain_file.fwrite($text_msg);
fclose($plain_file);
$html_name = tempnam('/tmp/', 'html');
$html_file = fopen($html_name, 'w');
$html_file.fwrite($text_msg);
fclose($html_file);

system("/Users/vickery/bin/mail.py -s 'Jack’s Alive' -t $plain_file -h $html_file" .
       "-f 'An Academic Senate Robot' -t $recipient_email -d1 cvickery@gmail.com", $return_value);
if ($return_value === 0)
{
  echo "<h1>Test message appears to have been sent. No errors reported.</h1>";
}
else
{
  echo "<h1>*** test_email failed</h1>";
}

unlink($plain_name);
unlink($html_name);
exit;
  // $mail = new Senate_Mail('QC Curriculum<nobody@qc.cuny.edu>', $recipient_email,
  //   "Jack’s Alive",
  //    $text_msg, $html_msg);
  // $mail->add_recipient('cvickery@gmail.com');
  // $mail->send() or die( $mail->getMessage() .
  //     " <a href='.'>try again</a> or report the problem to $webmaster_email");

  // exit;

?>