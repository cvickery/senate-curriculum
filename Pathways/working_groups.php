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
    <title>Working Groups</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="css/pathways.css" />
  </head>
  <body>
  <ul id='nav'>
    <li><a href='../' title='Main page'>Home</a></li>
    <li><a href='http://senate.qc.cuny.edu/pways'
           title="Forum for discussing issues related to the committee's work">
      Question / Answer Forum</a>
    </li>
    <li><a href="Documents"
             title="CUNY, QC, and Committee documents">
        Documents</a>
    </li>
    <li><a href='Minutes'
           title="Archive of meeting minutes">Meeting Minutes</a></li>
  </ul>
  <h1>Working Groups</h1>
  <p>
    Not available: Groups have formed on an <em>ad hoc</em> basis.
  </p>
  <p>
    To participate, leave comments on the <a href="http://senate.qc.cuny.edu/pways">Question /
    Answer forum</a> and/or attend committee meetings.
  </p>
  <dl>
  </dl>
  </body>
</html>