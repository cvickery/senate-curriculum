<?php
  $mime_type = "text/html";
  $html_attributes="lang=\"en\"";
  if ( array_key_exists("HTTP_ACCEPT", $_SERVER) &&
        (stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml") ||
         stristr($_SERVER["HTTP_ACCEPT"], "application/xml") )
       ||
       (array_key_exists("HTTP_USER_AGENT", $_SERVER) &&
        stristr($_SERVER["HTTP_USER_AGENT"], "W3C_Validator"))
     )
  {
    $mime_type = "application/xhtml+xml";
    $html_attributes = "xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\"";
    header("Content-type: $mime_type");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
  }
  else
  {
    header("Content-type: $mime_type; charset=utf-8");
  }
?>
<!DOCTYPE html>
<html <?php echo $html_attributes;?>>
  <head>
    <title>January 26 Agenda</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="css/pathways.css" />
  </head>
  <body>
  <h1>January 26, 2012 Agenda</h1>
  <dl>
    <dt>I. Call to order</dt>
    <dt>II. Greetings from the Provost</dt>
    <dt>III. Updates on CUNY policies and procedures concerning Pathways (Summerfield)</dt>
    <dt>IV. Discussion of <a href="Documents/2012-01-26_Draft.pdf">Working Draft</a></dt>
  </dl>
  <a href='index.php'>Return</a>
  </body>
</html>