<?php
  header("Vary: Accept");
  if ( (array_key_exists("HTTP_ACCEPT", $_SERVER) &&
        stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml+xml"))
       ||
       (array_key_exists("HTTP_USER_AGENT", $_SERVER) &&
        stristr($_SERVER["HTTP_USER_AGENT"], "W3C_Validator"))
     )
  {
    header("Content-type: application/xhtml+xml");
    header("Last-Modified: "
                    .date('r',filemtime($_SERVER['SCRIPT_FILENAME'])));
    print("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n");
  }
  else
  {
    header("Content-type: text/html; charset=utf-8");
  }
 ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
  <head>
    <title>oci test</title>
  </head>
  <body>
    <h1>Test</h1>
    <?php  
    $qs = "SELECT orig_hire_dt, fname, miname, lname, nameprefix, namesuffix, "
    ." dept_descr, descr, job_function, cu_email_addr_c1, cu_email_addr_c2, "
    ." cu_email_addr_c3, cu_email_addr_c4 FROM octsims.erp856 WHERE "
    ." REGEXP_LIKE(cu_email_addr_c1, 'vickery', 'i') OR REGEXP_LIKE(cu_email_addr_c2, "
    ." 'vickery', 'i') OR REGEXP_LIKE(cu_email_addr_c3, 'vickery', 'i') OR "
    ." REGEXP_LIKE(cu_email_addr_c4, 'vickery', 'i')";
    echo "<p>$qs</p>\n";
    $obj = json_decode(exec("(export DYLD_LIBRARY_PATH=/opt/oracle/instantclient/; "
      . "export ORACLE_HOME=\$DYLD_LIBRARY; echo \"$qs\"|/usr/local/bin/oci_query)"));
    echo "<pre>\n";
    var_dump($obj);
    echo "</pre>\n";
    ?>
  </body>
</html>
