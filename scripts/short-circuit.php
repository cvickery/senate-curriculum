<?php
//  Short circuit: if host is localhost, log me in.
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'localhost')
{
  $person = new Person('christopher.vickery@qc.cuny.edu');
  $person->set_name('Christopher Vickery');
  $person->set_dept('Computer Science');
  $person->finish_login();
  $_SESSION[person] = serialize($person);
}
?>
