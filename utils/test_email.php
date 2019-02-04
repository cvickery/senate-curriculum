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
fwrite($plain_file, $text_msg);
fclose($plain_file);
$html_name = tempnam('/tmp/', 'html');
$html_file = fopen($html_name, 'w');
fwrite($html_file, $html_msg);
fclose($html_file);
chmod($plain_name, 0644);
chmod($html_name, 0644);

$cmd = "/Users/vickery/bin/mail.py -s 'Jack’s Alive' -p $plain_name -h $html_name " .
       "-f 'An Academic Senate Robot' -d1 $recipient_email cvickery@gmail.com";
system($cmd, $return_value);

if ($return_value === 0)
{
  echo "<h1>Test message appears to have been sent. No errors reported.</h1>";
}
else
{
  echo "<h1>*** test_email failed</h1>";
}

// unlink($plain_name);
// unlink($html_name);
exit;
  // $mail = new Senate_Mail('QC Curriculum<nobody@qc.cuny.edu>', $recipient_email,
  //   "Jack’s Alive",
  //    $text_msg, $html_msg);
  // $mail->add_recipient('cvickery@gmail.com');
  // $mail->send() or die( $mail->getMessage() .
  //     " <a href='.'>try again</a> or report the problem to $webmaster_email");

  // exit;

?>