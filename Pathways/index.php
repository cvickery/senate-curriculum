<?php  /* Pathways/index.php */
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
    <title>QC Academic Senate Ad Hoc Committee on Pathways</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="css/pathways.css" />
  </head>
  <body>
    <ul id='nav'>
      <li><a href='http://senate.qc.cuny.edu/pways'
             title="Forum for discussing issues related to the committee's work">
        Question / Answer Forum</a>
      </li>
      <li><a href="Documents"
             title="CUNY, QC, and Committee documents">
        Documents</a>
       </li>
      <li><a href="Writing"
             title="Writing-related documents">
        Writing-Related Documents</a>
       </li>
      <li><a href="Planning"
             title="Planning-related documents">
        Implementation Planning</a>
       </li>
     </ul>
    <h1>QC Academic Senate Ad Hoc Committee on Pathways</h1>
    <p>
      The Senate formed this <em>ad hoc</em> committee to coordinate preparation of the Queens
      College implementation plan for the CUNY Pathways project.
    </p>
    <p>
      There will be a Special Limited Meeting of the Academic Senate on March 29, 2012 to consider
      this committee’s recommendations, which will impact the College’s Pathways
      Implementation Plan, which, in turn, is to be submitted to the Chancellor by April 1, 2012.
    </p>
    <p>
      This site is a repository for information related to the committee’s work.
    </p>
    <h2>Schedule</h2>
    <dl>
      <dt>Monday, December 12, 2011 <a href="2011-12-12_Agenda.php">Agenda</a></dt>
      <dd>
        <p>3:00 - 4:30 pm; President’s Conference Room, Rosenthal Library</p>
        <p>
          Election of chair; Outline of descisions to be made; Formation of working groups.
        </p>
      </dd>
      <dt>Thursday, January 26, 2012 <a href="2012-01-26_Agenda.php">Agenda</a></dt>
      <dd>
        <p>10:00 am - noon; President’s Conference Room, Rosenthal Library</p>
        <p>Working brunch to establish the structure of the QC GenEd curriculum.</p>
      </dd>
      <dt>Monday, February 6, 2012</dt>
      <dd>
        <p>3:00 pm; President’s Conference Room #1</p>
        <p>Open meeting to discuss draft report to submit to the UCC</p>
      </dd>
      <dt>Wednesday, February 22, 2012</dt>
      <dd>
        <p>3:00 pm; President’s Conference Room #1</p>
        <p>Respond to UCC comments on our draft report of February 9.</p>
        <p>
          This will be an open meeting, but only members of the subcommittee will be
          allowed to vote on motions.
        </p>
        <h4 style='margin: 0 1em;'>Agenda:</h4>
        <ul style='margin-top: 0.1em;'>
          <li>
            Review of how this committee’s work coordinates with the UCC, the Senate, the Senate’s
            Governance Committee, and the College’s Administrative Implementation Plan.
          </li>
          <li>
            Adoption of the <a href="Documents/2012-02-22_Draft.pdf">current working draft</a> of
            this committee’s report.
          </li>
          <li>Schedule for additions or changes to the committee’s report.</li>
          <li>Next meeting.</li>
        </ul>
      </dd>
      <dt>Friday, March 9, 2012</dt>
      <dd>
        <p>10:00 am to noon; President’s Conference Room #1</p>
        <h4 style='margin: 0 1em;'>Agenda:</h4>
        <ul style='margin-top: 0.1em;'>
          <li>
            Review the <a href="Documents/2012-03-08_UCC_Summary.pdf">curriculum structure as
            approved by the UCC on March 8</a>.
          </li>
          <li>
            Discuss Flexible Core at Queens.
          </li>
          <li>
            Next steps . . .
          </li>
        </ul>
      </dd>
    </dl>
    <h2>Participate</h2>
    <p>
      Faculty and students are invited to take part in the committee’s work.
      Whether you can attend meetings and particpate in one of the working
      groups, or simply would like to be put on the committee’s mailing list, <a
      href='mailto:Christopher.Vickery@qc.cuny.edu?Subject=Pathways%20Committee'>send
      email to the committee</a>. Be sure to indicate your level of interest.
    </p>
  </body>
</html>

