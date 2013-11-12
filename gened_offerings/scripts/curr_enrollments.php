#! /usr/bin/php
<?php
ini_set('memory_limit', -1);  //  Big query
require_once('utils.php');
  /*  This is a cron job that converts information from two 805 tables dumped
   *  from CUNY to QC each night into a local database named
   *  YYYY-MM-DD_enrollments.db. The date stamp is so enrollment patterns can be
   *  tracked if desired, given that OCT overwrites the tables each time they
   *  take a dump. The generated database actually contains data for several
   *  semesters, apparently for approximately the calendar year that includes
   *  the current semester. */
   $enrollment_query = <<<EOD
SELECT a.crse_id,
       a.strm,
       a.session_code,
       a.class_section,
       a.class_nbr,
       a.subject,
       a.catalog_nbr,
       a.class_stat,
       a.enrl_cap,
       a.wait_cap,
       a.enrl_tot,
       a.wait_tot,
       a.class_nbr,
       a.ssr_component,
       a.date_loaded,
       c.ssr_component,
       b.meeting_time_start,
       b.meeting_time_end,
       b.mon, b.tues, b.wed, b.thurs, b.fri, b.sat, b.sun
FROM  octsims.erp805_class_section a,
      octsims.erp805_class_section_dtl b,
      octsims.erp805_course_component c
WHERE a.crse_id         = b.crse_id
and   a.crse_id         = c.crse_id
and   a.strm            = b.strm
and   a.crse_offer_nbr  = b.crse_offer_nbr
and   a.session_code    = b.session_code
and   a.class_section   = b.class_section
and   a.ssr_component   = c.ssr_component
order by a.strm, a.session_code, a.subject, a.catalog_nbr, a.class_section
EOD;
    $enrollment_info = json_decode(exec("(export "    .
    " DYLD_LIBRARY_PATH=/opt/oracle/instantclient/; " .
    " export ORACLE_HOME=\$DYLD_LIBRARY_PATH; echo "  .
    " \"$enrollment_query\"|/usr/local/bin/oci_query)"));
    $msg = "curr_enrollments.php: " . date('Y-m-d h:ia ');
    if (is_array($enrollment_info) && (count($enrollment_info) > 0))
    {
      $msg .= number_format(count($enrollment_info)) . " sections\n";
    }
    else $msg .= "UPDATE FAILED\n";
    echo "$msg\n";
    if (strstr($msg, 'FAIL')) exit();

    //  Now to create the table here
    /*  We assume this script will be run 1x per day. Any more often than
     *  that, and we'll just wipe out and overwrite the previous version.
     */
      $file_name = './db/' . date('Y-m-d') . '_enrollments.db';
      $db = new SQLite3($file_name);
      $db->exec("DROP TABLE IF EXISTS enrollments");
      $db->exec("CREATE TABLE enrollments (" .
        "term           TEXT,   " .
        "session        TEXT,   " .
        "term_code      NUMBER, " .
        "term_name      TEXT,   " .
        "term_abbr      TEXT,   " .
        "course_id      NUMBER, " .
        "discipline     TEXT,   " .
        "course_number  TEXT,   " .
        "class_section  TEXT,   " .
        "class_nbr      NUMBER, " .
        "component      TEXT,   " .
        "status         TEXT,   " .
        "start_time     TEXT,   " .
        "end_time       TEXT,   " .
        "days           TEXT,   " .
        "seats          NUMBER, " .
        "enrollment     NUMBER, " .
        "date_loaded    TEXT    " .
      ")");
    //  Populate the table
    foreach ($enrollment_info as $row)
    {
      //  Convert the STRM and SESSION_CODE columns into a single,
      //  chronologically-correct numerical column in the form YYYYTTT.
      $term = new Term($row->STRM, $row->SESSION_CODE);
      $date_loaded = new DateTime($row->DATE_LOADED);
      $date_loaded = $date_loaded->format('Y-m-d');

      $start_time = str_replace('.', ':', substr($row->MEETING_TIME_START, 0, 5));
      $end_time   = str_replace('.', ':', substr($row->MEETING_TIME_END, 0, 5));
      $days = '';
      if ($row->MON   === 'Y') $days .= 'Mon';
      if ($row->TUES  === 'Y') $days .= ($days === '' ? '' : ', ') . 'Tue';
      if ($row->WED   === 'Y') $days .= ($days === '' ? '' : ', ') . 'Wed';
      if ($row->THURS === 'Y') $days .= ($days === '' ? '' : ', ') . 'Thu';
      if ($row->FRI   === 'Y') $days .= ($days === '' ? '' : ', ') . 'Fri';
      if ($row->SAT   === 'Y') $days .= ($days === '' ? '' : ', ') . 'Sat';
      if ($row->SUN   === 'Y') $days .= ($days === '' ? '' : ', ') . 'Sun';

      //  Insert the row.

      $db->exec("INSERT INTO enrollments VALUES ("  .
      "'{$row->STRM}',                " .
      "'{$row->SESSION_CODE}',        " .
      "$term->code,                   " .
      "'{$term->name}',               " .
      "'{$term->abbr}',               " .
      "{$row->CRSE_ID},               " .
      "'{$row->SUBJECT}',             " .
      "'{$row->CATALOG_NBR}',         " .
      "'{$row->CLASS_SECTION}',       " .
      "'{$row->CLASS_NBR}',           " .
      "'{$row->SSR_COMPONENT}',       " .
      "'{$row->CLASS_STAT}',          " .
      "'$start_time',                 " .
      "'$end_time',                   " .
      "'$days',                       " .
      "{$row->ENRL_CAP},              " .
      "{$row->ENRL_TOT},              " .
      "'$date_loaded'                 " .
      ")");
    }

  ?>
