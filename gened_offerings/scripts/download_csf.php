<?php
  session_start();
	date_default_timezone_set('America/New_York');
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
  header("Location: http://senate.qc.cuny.edu/gened_offerings");
  exit;
 ?>
