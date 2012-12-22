<?php  /* Documents/index.php */
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
    <title>Pathways Planning Documents</title>
    <link rel="icon" href="../../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/pathways.css" />
    <style type='text/css'>
      #links {list-style-type:none;padding:0;}
      #links li { margin: 1em; }
      dd {margin:1em;}
      dt {max-width:75%;}
      .warning {color:#c33;}
    </style>
  </head>
  <body>
    <ul id='nav'>
      <li>
        <a href="../Writing">College Writing 2 Documents</a>
      </li>
      <li>
        <a href="../Documents">Other Pathways Documents</a>
      </li>
      <li><a href='http://lists.qc.cuny.edu/mailman/listinfo/pp'
             title='Mailing List'
             target='_blank'>Mailing List</a>
      </li>
      <li>
        <a href="http://lists.qc.cuny.edu/pipermail/pp/"
           title='Mailing List Archive'
           target='_blank'>Mailing List Archive</a>
      </li>
    </ul>
    <h1>Pathways Planning</h1>
    <div>
      <p>
        This site is a way for the QC community to follow the decisions that need to be
        made in order to implement the Pathways plan approved by the Academic Senate on
        March 30, 2012.
      </p>
      <p>
        Anyone can participate in discussing the issues related to implementing Pathways
        by subscribing to the “Mailing List” link at the top of this page, and joining the
        “PP” mailing list.
       </p>
    </div>
    <h2>Planning Documents</h2>
    <dl>
      <dt>
        <a href="../Documents/2012-06-20_College_Presidents_CCCRC.pdf">Goldstein CCCRC
        Deadlines Memo</a>
      </dt>
      <dd>
        June 20, 2012 memo from Chancellor Goldstein, addressed to college presidents and
        deans, explaining the Common Core Course Review Committee deadline chart (see next
        item), and calling for its completion by July 16, 2012.
      </dd>
      <dt><a href="../Documents/2012-06-20_CCCRC_chart.pdf">CCCRC Deadlines</a></dt>
      <dd>
        The Common Core Course Review Committee deadline chart introduced by the
        Chancellor in the memo above.
      </dd>
      <dt>
        <a href="../Documents/2012-06-18_Planning_Meeting_Notes.pdf">Planning Meeting
        Notes</a>
      </dt>
      <dd>
        Ann Morgado’s summary of a June 18, 2012 planning meeting attended by R. Brody, S.
        Henderson, K. Lord, A. Morgado, E. Pratt, S. Schwarz, J. Summerfield, and C.
        Vickery.
      </dd>
      <dt>
        <a href='../Documents/2012-06-21_Seat_Projections.pdf'>Seat Projections (3
        pages)</a>
      </dt>
      <dd>
        June 21, 2012 spreadsheet that summarizes responses to a survey conducted by the
        Provost’s office, requesting departments to list the courses they plan to submit
        for various Pathways requirements. The College projects a need for approximately
        3600 seats per requirement per year. This document is a slightly revised version
        of the draft document that was discussed at the June 18, 2012 planning meeting.
      </dd>
    </dl>
  </body>
</html>

