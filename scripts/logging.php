<?php


function log_msg($msg)
{
  error_log('[' . date('Y-m-d H:i:s ') . '] ' . $msg);
}
function log_globals($msg='globals')
{
  ob_start();
  echo "\nSESSION:\n";
  var_dump($_SESSION);
  echo "POST:\n";
  var_dump($_POST);
  log_msg($msg . ob_get_contents());
  ob_end_clean();
}
function log_var($msg, $var)
{
  ob_start();
  var_dump($var);
  log_msg($msg . ob_get_contents());
  ob_end_clean();
}
