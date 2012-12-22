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
    <title>QC Writing Documents</title>
    <link rel="icon" href="../../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/pathways.css" />
    <style type='text/css'>
      #links {list-style-type:none;padding:0;}
      #links li { margin: 1em; }
			dd {margin:1em;}
			dt {max-width:75%;}
    </style>
  </head>
  <body>
    <ul id='nav'>
      <li>
        <a href="../Documents">Other Pathways Documents</a>
      </li>
      <li><a href='http://lists.qc.cuny.edu/mailman/listinfo/ec-2'
             title='Mailing List'
             target='_blank'>Mailing List Information</a>
      </li>
      <li>
        <a href="http://lists.qc.cuny.edu/pipermail/ec-2/"
           title='Mailing List Archive'
           target='_blank'>Mailing List Archive</a>
      </li>
    </ul>
    <h1>QC Writing Documents</h1>
    <dl>
      <dt><a href="../Documents/2012-06-18_Logue_to_Stellar.pdf">VC Logue on Contact Hours</a></dt>
      <dd>
        This email says it offers helpful information about “the current hours devoted to the teaching of writing
        at CUNY.”
      </dd>
      <dt><a href='../Documents/2012-06-06_CW2_Guidelines.pdf'>Draft Guidelines for English
        Composition Courses at Queens College</a>
      </dt>
      <dd>
        June 6, 2012 document produced by members of the <a
        href='http://lists.qc.cuny.edu/mailman/listinfo/ec-2'>English Composition 2 mailing list</a> following a
        group meeting held on May 30, 2012. Drafted by Kate Antonova (History), Katherine Profeta
        (Drama, Theatre, &amp; Dance), and Esther Muehlbauer (Biology), with edits suggested by
        others.
        <p><em>
          Please note that these guidelines are being provided in draft form now so that people who want to can
          work on developing “College Writing 2” courses during the Summmer of 2012. We expect these guidelines
          to evolve as we gain experience with this new set of courses. Early adopters should realize that
          work done now might have to be revised before finally being approved by the Senate.
        </em></p>
      </dd>
      <dt>
        The following material is based on an email sent by Kevin Ferguson, Director of Writing at
        Queens, on May 31, 2012.
      </dt>
      <dd>
        <ul id='links'>
          <li>
            <a href="http://english110.qwriting.org/110-resources/110-guidelines/"
               target='_blank'>
              Guidelines for ENG 110
            </a>
          </li>
          <li>
            <a href="http://english110.qwriting.org/110-resources/110-goals/"
               target='_blank'>
              Learning Goals for ENG 110
            </a>
            <p>
              Those two documents were created over a recent year-long process of revising ENG 110. The
              Learning Goals easily cover the Pathways-mandated outcomes, and you’ll see how the separate
              “Guidelines” document contextualizes the broader goal-oriented language.
            </p>
          </li>
          <li>
            <a href="http://writingatqueens.org/writing-intensive-courses/"
               target='_blank'>
              WaQ homepage about Writing Intensive courses
            </a>
          </li>
          <li>
            <a href="http://writingatqueens.org/for-faculty/creating-a-w-course/"
               target='_blank'>
              Specific requirements for creating and proposing a W course
            </a>
          </li>
          <li>
            <a href="http://writingatqueens.org/files/2010/05/GoalsforStudentWriting1.pdf"
               target='_blank'>
              “Goals for Student Writing” document approved by the Senate in May 2007 (pdf)
            </a>
          </li>
          <li>
            <a href="../Documents/2012-03-30_Queens_College_Pathways_implementation_plan.pdf">
              Queens College Pathways Implementation Plan approved by the Senate in March 2012 (pdf)
            </a>
          </li>
          <li>
            <a href="http://bit.ly/JRvKOt"
               target='_blank'>QC Implementation Plan sent to the Chancellor on April 1,
            2012 (pdf)</a>
          </li>
          <li>
            <a href="../Documents/2011-12-01_CommonCoreStructureFinalRec.pdf">
              Pathways “Final Report,” which includes list of required Outcomes for Composition
              courses (pdf)
            </a>
          </li>
        </ul>
      </dd>
    </dl>
  </body>
</html>
