<?php
//  Model_Proposals/index.php
set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() . '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');

//  Here beginnith the web page
//  -------------------------------------------------------------------------------------
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
    <title>GenEd Proposal Guidelines</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/model_proposals.css" />
    <script type='text/javascript' src="../js/jquery.min.js"></script>
    <script type='text/javascript' src="../js/site_ui.js"></script>
  </head>
  <body>
<?php
  //  Status Bar and H1 element
  $status_msg = login_status();
  $nav_bar    = site_nav();
  echo <<<EOD
  <nav id='status-bar'>
    $status_msg
    $nav_bar
  </nav>
  <h1>Undergraduate General Education Proposal Guidelines</h1>
EOD;
?>
  <section>
    <h2>Three Types of General Education Requirements at Queens College</h2>
    <div>
      <dl>
        <dt>CUNY-wide <em>Common Core</em> (“Pathways”)</dt>
        <dd>
          <p>
            A course may be designated to satisfy no more than one of the following eight Common Core
            areas:
          </p>
          <ul>
            <li>English Composition (EC)</li>
            <li>Mathematics and Quantitative Reasoning (MQR)</li>
            <li>Life and Physical Sciences (LPS)</li>
            <li>World Cultures and Global Issues (WCGI)</li>
            <li>United States Experience in its Diversity (USED)</li>
            <li>Creative Expression (CE)</li>
            <li>Individual and Society (IS)</li>
            <li>Scientific World (SW)</li>
          </ul>
          <p>
            Students must complete two EC courses plus an extra course from among the last five
            designations, for a total of ten courses. Once a student has satisfied one of these
            designation requirements at any CUNY college, that requirement remains satisfied no matter
            where within CUNY the student subsequently transfers.
          </p>
          <p>
            For a course to be designated as a Common Core course, a proposal must be approved both
            at Queens (by the Academic Senate) and by a CUNY-wide committee called the Common Core
            Review Committee (CCRC). Once the CCRC approves a proposal, the course goes to the
            Chancellor for final approval, and then gets entered into <em>CUNYfirst</em>.
          </p>
          <p>
            Most of the material below is intended to help faculty prepare proposals that the CCRC
            will approve.
          </p>
        </dd>
        <dt>College Option</dt>
        <dd>
          <p>
            The College Option requirements are part of the overall Pathways structure, but unlike
            the Common Core, no CUNY-wide review takes place: once the Academic Senate approves a
            course for the College Option, it is forwarded directly to the Chancellor.
          </p>
          <p>
            Unlike Common Core designations, a course may have multiple College Option attributes
            in addition to or instead of a single Common Core designation. But students can use a
            single course to satisfy no more than one Common Core or College Option requirement.
          </p>
          <p>
            Also unlike Common Core requirements, once a student matriculates at Queens College, all
            remaining College Option requirements must be completed in residence at Queens.
          </p>
          <p>
            The College Option structure is arcane. See <a href="../College_Option/"
            target="_blank"> the college option calculator</a> to see how to determine which college
            option courses a student must complete here.
          </p>
        </dd>
        <dt>Writing Intensive Courses</dt>
        <dd>
          <p>
            Students must complete two writing intensive courses at Queens College. These courses can
            and should overlap with the courses taken as part of the student’s major or other
            General Education requirements.
          </p>
        </dd>
      </dl>
    </div>
  </section>
  <section>
    <h2>Proposal Preparation</h2>
    <div>
      <p>
        Once you Sign In to this website (using the button at the top right of this page, for
        example), a menu will open up at the top of the page that includes a “Manage Proposals”
        button. From that page, you will be able to create, edit, or delete proposals, and to upload
        sample course syllabi to support your proposals.
      </p>
      <blockquote style="border:1px solid #999; border-radius:0.5em; padding:0.5em;">
        In order to propose a course for a General Education requirement, the course must already
        exist in <em>CUNYfirst</em> <strong>or</strong> you must first start a separate proposal
        here to create the course. (A future version of this site should eliminate the need for
        separate proposals.)
      </blockquote>
      <p>
        There are two closely-linked parts to a proposal, a list of Student Learning Outcomes (SLOs)
        and the sample syllabus for the course being proposed. When justifying a SLO, tell how
        successful completion of the course will demonstrate that a student has achieved that
        learning outcome. The focus has to be on what the student learns, not what the course
        covers. The justification should reference particular graded activities given in the course
        syllabus. Be concrete, but not long-winded, in these justifications.
      </p>
      <p>
        Unlike an actual syllabus provided to students at the beginning of a course, the sample
        syllabus submitted to the CCRC should be written with the CCRC in mind. They like to see the
        SLOs listed explicitly, and it is important to make clear what assignments/activities will
        be required, and how much each will count towards the course grade. Including the schedule
        of topics to be covered and descriptions of the graded assignments are important. Policies
        related to course management, when and where the class meets, how to contact the instructor,
        etc. are not important, but do not have to be removed either.
      </p>
      <p>
        The critical point is that the syllabus and the SLO justifications must clearly support
        each other.
      </p>
    </div>
  </section>

  <section>
    <h2>More Information</h2>
    <p>
      The Writing Subcommittee guidelines for English Composition (“College Writing 2”) courses
      are avalable as a separate document: <a href='docs/2012-09-27_CW2_Guidelines.pdf'>CW2
      Guidelines</a>.
    </p>
    <p>
      All previously-submitted QC proposals are available for review in the <a href="../Proposals">
      Curriculum Proposals</a> section of this website. If a proposal was returned by the CCRC,
      their comments are shown in the section showing the steps in the review process for the
      proposal.
    </p>
  </section>

  </body>
</html>
