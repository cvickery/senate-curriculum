<?php
//  This is the server side of CSV download functionality, invoked via XMLHttpRequest.
/*  Just put the CSV string into $_SESSION['csv'], and use this as URL of the request
 *  object's open() method.
 */
  session_start();
  $filename = "gened_courses_" . date('Y-m-d_his') . ".csv";
  if (isset($_POST['download']) && isset($_SESSION['csv']))
  {
    $cl = strlen($_SESSION['csv']);
    if ($cl > 0)
    {   
      header("Content-type: application/octet-stream");
      header("Content-Disposition: attachment; filename=$filename");
      header("Content-length: $cl");
      echo $_SESSION['csv'];
      exit;
    }
  }
  header("Location: http://senate.qc.cuny.edu/Curriculum");
  exit;
 ?>
