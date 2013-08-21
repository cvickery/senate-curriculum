#! /usr/bin/php
<?php

require_once('utils.php');
  /*  This is a cron job that converts information from two 805 tables dumped
   *  from CUNY to QC each night into a local database named
   *  YYYY-MM-DD_enrollments.db. The date stamp is so enrollment patterns can be
   *  tracked if desired, given that OCT overwrites the tables each time they
   *  take a dump. The generated database actually contains data for several
   *  semesters, apparently for approximately the calendar year that includes
   *  the current semester. */
   $enrollment_query = <<<EOD
SELECT a.strm,
       a.session_code,
       a.class_section,
       a.subject,
       a.catalog_nbr,
       a.class_stat,
       a.enrl_cap,
       a.wait_cap,
       a.enrl_tot,
       a.wait_tot,
       b.ssr_component,
       b.date_loaded
FROM  octsims.erp805_class_section a,
      octsims.erp805_course_component b
WHERE a.crse_id = b.crse_id

EOD;
    $enrollment_info = json_decode(exec("(export "    .
    " DYLD_LIBRARY_PATH=/opt/oracle/instantclient/; " .
    " export ORACLE_HOME=\$DYLD_LIBRARY_PATH; echo "  .
    " \"$enrollment_query\"|/usr/local/bin/oci_query)"));
    $msg = date('Y-m-d h:ia ');
    if (is_array($enrollment_info))
    {
      $msg .= number_format(count($enrollment_info)) . " sections\n";
    }
    else $msg .= "UPDATE FAILED\n";
    $log = fopen('curr_update.log', 'a+');
    fputs($log, $msg);

    //  Now to create the table here
    /*  We assume this script will be run 1x per day. Any more often than
     *  that, and we'll just wipe out and overwrite the previous version.
     */
      $file_name = './db/' . date('Y-m-d') . '_enrollments.db';
      $db = new SQLite3($file_name);
      $db->exec("DROP TABLE IF EXISTS offerings");
      $db->exec("CREATE TABLE offerings (" .
        "term           TEXT,   " .
        "session        TEXT,   " .
        "term_code      NUMBER, " .
        "term_name      TEXT,   " .
        "term_abbr      TEXT,   " .
        "discipline     TEXT,   " .
        "course_number  TEXT,   " .
        "class_section  TEXT,   " .
        "component      TEXT,   " .
        "status         TEXT,   " .
        "seats          NUMBER, " .
        "enrollment     NUMBER, " .
        "date_loaded    DATE    " .
      ")");
    //  Populate the table
    foreach ($enrollment_info as $row)
    {
      //  Convert the STRM and SESSION_CODE columns into a single,
      //  chronologically-correct numerical column in the form YYYYTTT.
      $term = new Term($row->STRM, $row->SESSION_CODE);
      //  Insert the row.
      $db->exec("INSERT INTO offerings VALUES ("  .
      "'{$row->STRM}',          " .
      "'{$row->SESSION_CODE}',  " .
      "$term->code,             " .
      "'{$term->name}',         " .
      "'{$term->abbr}',         " .
      "'{$row->SUBJECT}',       " .
      "'{$row->CATALOG_NBR}',   " .
      "'{$row->CLASS_SECTION}', " .
      "'{$row->SSR_COMPONENT}', " .
      "'{$row->CLASS_STAT}',    " .
      "{$row->ENRL_CAP},        " .
      "{$row->ENRL_TOT},        " .
      "{$row->DATE_LOADED}      " .
      ")");
    }

    fclose($log);
  ?>
