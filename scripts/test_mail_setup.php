<?php
require('mail_setup.php');
require('sanitize.php');
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
  $mail = new Senate_Mail('Senate Email Test<Christopher.Vickery@qc.cuny.edu>',
      'Christopher.Vickery@qc.cuny.edu', "'testing' don’t question authority",
      $text_msg, $html_msg);
  $mail->add_recipient('cvickery@gmail.com', 'Dr. Christopher Vickery');
  $mail->add_cc('poffice@qc.cuny.edu');
  $mail->add_bcc('cvickery@qc.cuny.edu', 'Charles Christopher Vickery');

  $status = $mail->send();
  if (! $status)
  {
    echo "<h1>Failed!</h1><p>" . $mail->getMessage() . "</p>";
  }
  else
  {
    echo "<h1>Mail Sent!</h1>\n";
  }
 ?>
