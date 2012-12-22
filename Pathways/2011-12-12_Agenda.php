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
    <title>December 12 Agenda</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="css/pathways.css" />
  </head>
  <body>
  <h1>December 12, 2011 Agenda</h1>
  <dl>
    <dt>I. Election of Chair</dt>
    <dt>II. Decisions to be made</dt>
    <dd>
      <p>
        The full college plan needs to address each of the following issues in order to be assured of a
        coherent GenEd curriculum. The plan submitted to the university, however, need only indicate how
        the Required and Flexible Common Core offerings will be structured.
      </p>
      <dl>
        <dt>English Composition</dt>
        <dt>Mathematical and Quantitative Reasoning</dt>
        <dt>Life and Physical Sciences</dt>
        <dt>Five Flexible Core Areas</dt>
        <dt>College Option</dt>
        <dt>Writing Across the Curriculum</dt>
        <dt>Quantitative Reasoning Across the Curriculum</dt>
        <dt>Implementation: course submission and approval; resources needed; transition policies</dt>
      </dl>
    </dd>
    <dt>III. Formation of Working Groups</dt>
    <dd>
      <p>
        Working groups will meet (electronically or otherwise) between now and January 26, at which
        time each group will present its recommendations to the full committee.
      </p>
    </dd>
  </dl>
  <a href='index.php'>Return</a>
  </body>
</html>