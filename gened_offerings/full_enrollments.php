#! /usr/bin/php
<?php

require_once('scripts/utils.php');
  /*  This is a test program to build an sqlite table that contains all the
   *  information available for courses in the current enrollment 805 stuff
   *  over there at OCT-land. */
   $enrollment_query = 
    "SELECT * " .
    "FROM octsims.erp805_class_section,octsims.erp805_course_component " .
    "WHERE octsims.erp805_course_component.crse_id = octsims.erp805_class_section.crse_id";
    $enrollment_info = json_decode(exec("(export "    .
    " DYLD_LIBRARY_PATH=/opt/oracle/instantclient/; " .
    " export ORACLE_HOME=\$DYLD_LIBRARY_PATH; echo "  .
    " \"$enrollment_query\"|/usr/local/bin/oci_query)"));

    $columns = array();
    // not so good without knowing the columm names and types returned by the
    // query.
    //  Now to create the database and table here
      $file_name = 'full_enrollments.db';
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
        "component      TEXT,   " .
        "status         TEXT,   " .
        "seats          NUMBER, " .
        "enrollment     NUMBER  " .
      ")");
    //  Populate the table
    foreach ($enrollment_info as $row)
    {
      //  Convert the STRM and SESSION_CODE columns into a single,
      //  chronologically-correct numerical column in the form YYYYTTT.
      $term = new Term($row->STRM, $row->SESSION_CODE);
      //  Insert the row.
      $db->exec("INSERT INTO offerings VALUES ("  .
      "'{$row->STRM}', " .
      "'{$row->SESSION_CODE}', " .
      "$term->code, " .
			"'{$term->name}', " .
			"'{$term->abbr}', " .
      "'{$row->SUBJECT}', " .
      "'{$row->CATALOG_NBR}', " .
      "'{$row->SSR_COMPONENT}', " .
      "'{$row->CLASS_STAT}', " .
      "{$row->ENRL_CAP}, " .
      "{$row->ENRL_TOT} " .
      ")");
    }

    fclose($log);
  ?>
