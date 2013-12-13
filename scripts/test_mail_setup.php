<?php
require('mail_setup.php');
require('sanitize.php');
$html_msg = <<<EOD

<h2>This is a "swell" message, don't you think?</h2>
<h2>This is a “smart” message, don’t you think?</h2>

EOD;
$text_msg = <<<EOD

  This is a "swell" message, don't you think?
  This is a “smart” message, don’t you think?

EOD;
  $mail = new Senate_Mail('Christopher Vickery<Christopher.Vickery@qc.cuny.edu>',
      'Christopher.Vickery@qc.cuny.edu', "'testing' don’t question authority",
      sanitize($text_msg), sanitize($html_msg));
  $mail->add_cc('vickery@babbage.cs.qc.cuny.edu');
  $mail->add_bcc('cvickery@gmail.com', 'Charles Christopher Vickery');
  echo "<pre>\n"; var_dump($mail); echo "</pre>\n";
  $status = $mail->send();
  if (! $status)
  {
    echo "<h1>Failed</h1>\n" . $mail->getMessage() . "\n<pre>";
    var_dump($mail);
    echo "</pre>\n";
  }
  else
  {
    echo "<h1>Mail Sent!</h1>\n";
  }
 ?>
