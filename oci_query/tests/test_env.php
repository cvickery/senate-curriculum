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
    <title>env test</title>
  </head>
  <body>
    <h1>Environment Test</h1>
    <?php  
    //  Demonstrate setting up environment variables for a C program (test_env).
    //  Note the parens containing the three commands being executed: they
    //  establish a common shell for all three commands so the export commands
    //  will affect the following commands, including the C program at the end.
    $obj = exec("(export DYLD_LIBRARY_PATH=/opt/oracle/instantclient/;export ORACLE_HOME=\$DYLD_LIBRARY_PATH; scripts/test_env)");
    var_dump($obj)
    ?>
  </body>
</html>
