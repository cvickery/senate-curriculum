<?php

  require_once('../../scripts/credentials.inc');
  $db = gened_connect() or die ('Unable to connect');
  if ($_SERVER['REMOTE_ADDR'] !== '74.101.151.49') die ('Wrong ip');
  $query = urldecode($_GET['query']);
  $result = pg_query($db, $query);
  if (! $result) echo pg_last_error($db);
  else echo 'OK';
 ?>
