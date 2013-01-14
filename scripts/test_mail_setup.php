#! /usr/bin/php

<?php
require('mail_setup.php');
  $mail = new Senate_Mail('Testing Email<do-not-reply@qc.cuny.edu>', 
      'Christopher.Vickery@qc.cuny.edu', 'testing',
      "This is a nice message.");
  $mail->add_cc('vickery@babbage.cs.qc.cuny.edu');
  $status = $mail->send();
  if (! $status) echo $mail->getMessage() . "\n";
  else var_dump($mail);
 ?>
