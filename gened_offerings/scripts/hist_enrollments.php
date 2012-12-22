#! /usr/bin/php
<?php
ini_set('memory_limit', -1);
ini_set('auto_detect_line_endings', 1);

require_once('utils.php');
//  Convert most recent CU_SR_CLASS_ENRL_BY_TERMS csv file into an sqlite table.
//  This script has to be run manually, so there is point in having a log file.

  $top_dir =
      '/Volumes/Sensitive/Library/WebServer/Documents/senate.qc.cuny.edu/Curriculum/gened_offerings';
  $dir = opendir("$top_dir/csv");
  $file = '';
  while ($candidate = readdir($dir))
  {
    if ('CU_SR_CLASS' === substr($candidate, 11, 11))
    {
      if (substr($candidate, 0, 10) > substr($file, 0, 10))
      {
        $file = $candidate;
      }
    }
  }
  if ($file === '') die("No candidate csv files found\n");
  echo "Using $file\n";
  //  Unlike the 805 db's, where we maintain a history, there is only one
  //  hist_enrollments db, which is overwritten when a new query result is
  //  downloaded.

  (filemtime("$top_dir/db/hist_enrollments.db") > filemtime("$top_dir/csv/$file")) 
                                  and die ("Database is already up to date.\n");
  $fp = fopen("$top_dir/csv/$file", 'r')         or  die ("Unable to read '$file'\n");
  $db = new SQLite3("$top_dir/db/hist_enrollments.db");
  $db->exec("DROP TABLE IF EXISTS offerings");
  $db->exec("CREATE TABLE offerings (" .
    "term_code      NUMBER, " .
    "term_name      TEXT,   " .
    "term_abbr      TEXT,   " .
    "discipline     TEXT,   " .
    "course_number  TEXT,   " .
    "component      TEXT,   " .
    "status         TEXT,   " .
    "enrollment     NUMBER  " .
    ")");

  //  Populate it
  $num_rows = 0;
  $record_count = fgetcsv($fp); // Row at beginning
  echo "Expecting {$record_count[1]} records\n";
  $headers = fgetcsv($fp);
  while ($line = fgetcsv($fp))
  {
    if (count($line) > 1)
    {
      for ($i = 0; $i < count($line); $i++) $line[$i] = trim($line[$i]);
      $term = new Term($line[1], $line[2]);
      $db->exec("INSERT INTO offerings VALUES (" .
        " {$term->code},  " .
        "'{$term->name}', " .
        "'{$term->abbr}', " .
        "'{$line[4]}',    " .
        "'{$line[5]}',    " .
        "'{$line[7]}',    " .
        "'{$line[8]}',    " .
        "{$line[11]}      " .
        ")");
      $num_rows++;
      if (0 === ($num_rows % 1000)) echo "\r$num_rows records";
    }
  }
  echo "\nProcessed $num_rows records\n";

 ?>

