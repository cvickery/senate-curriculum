<?php  /* Model_Proposals/index.php */

set_include_path(get_include_path() . PATH_SEPARATOR . '../scripts' );
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
    <title>Model Curriculum Proposals</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/model_proposals.css" />
    <script type='text/javascript' src="../../js/jquery-current.js"></script>
  </head>
  <body>
<?php
  //  Handle the logging in/out situation here
  $last_login       = '';
  $status_msg       = 'Not signed in';
  $person           = '';
  $sign_out_button  = '';
  require_once('../scripts/short-circuit.php');
  require_once('../scripts/login.php');
  if (isset($_SESSION[session_state]) && $_SESSION[session_state] === ss_is_logged_in)
  {
    if (isset($_SESSION[person]))
    {
      $person = unserialize($_SESSION[person]);
    }
    else
    {
      die("<h1 class='error'>Model Proposals: Invalid login state</h1></body></html>");
    }

    $status_msg = sanitize($person->name) . ' / ' . sanitize($person->dept_name);
    $last_login = 'First login';
    if ($person->last_login_time)
    {
      $last_login   = "Last login at ";
      $last_login  .= $person->last_login_time . ' from ' . $person->last_login_ip;
    }
    $sign_out_button = <<<EOD

    <form id='logout-form' action='.' method='post'>
      <input type='hidden' name='form-name' value='logout' />
      <button type='submit'>Sign Out</button>
    </form>

EOD;
  }
    //  Status/Nav Bars
    //  =================================================================================
    /*  Generated here, after login status is determined, but displayed up top by the
     *  wonders of CSS.
     */
    //  First row link to Review Editor depends on the user having something to review
    $review_link = '';
    if ($person && $person->has_reviews)
    {
      $review_link = "<a href='../Review_Editor'>Edit Reviews</a>";
    }
    echo <<<EOD
    <div id='status-bar'>
      $sign_out_button
      <div id='status-msg' title='$last_login'>
        $status_msg
      </div>
      <!-- Navigation -->
      <nav>
        <a href='../Proposals'>Track Proposals</a>
        <a href='../Model_Proposals' class='current-page'>Guidelines</a>
        <a href='../Proposal_Editor'>Manage Proposals</a>
        <a href='../Syllabi'>Syllabi</a>
        <a href='../Reviews'>Reviews</a>
        $review_link
      </nav>
    </div>

EOD;
?>
    <h1>Guidelines and Model Proposals</h1>
    <h2>Guidelines for CUNY Core and College Option Proposals and Syllabi</h2>
    <div>
      <p>
        The most important thing to keep in mind when preparing proposals is that the
        syllabus and the proposal justifications have to complement each other in
        meaningful ways. They must not contradict each other, and both must be specific
        enough so that the case for approving the course is “obvious.”
      </p>
      <h3>The Syllabus</h3>
      <p>
        The syllabus is the document given to students at the beginning of the
        course. It provides the student with (a) the rationale for the course, sometimes
        stated as “goals” or “objectives,” (b) the
        course requirements (assignments and exams), and (c) information on how
        grades will be determined. Syllabi normally contain additional administrative
        information for the course (“administrivia”) such as how to contact the
        instructor(s), office hours, how to access course materials, etc., but the first
        three elements are the critical parts for present purposes.
      </p>
      <p>
        Although the syllabus is designed for student consumption, it also provides
        proposal reviewers with their only way to understand how the course is actually
        structured and what material is actually covered.
      </p>
      <p>
        Naturally, the syllabus must be consistent with the formal catalog information for
        the course: title, contact hours and credits, prerequisites, and catalog
        description.
      </p>
      <p>
        There is no length requirement for the syllabus, and no required structure for the
        document, provided the three key elements listed above are all clearly presented.
        That said, CUNY guidelines suggest a five-page maximum, but it is not a strictly
        enforced limit. A one page syllabus is almost certainly too skimpy to allow
        meaningful evaluation of the course, and a ten page syllabus is almost certainly
        too detailed for the time students and reviewers alike are willing to devote to
        it.
      </p>
      <p>
        Many syllabi contain a weekly or class-by-class schedule for the course. Such a
        schedule can be a good way to indicate what proportion of the course is devoted to
        various topics, and to help students keep track of where they are in the course as
        the semester progresses. But to support a General Education proposal, it’s
        more important for the syllabus to give a good idea of what assignments the
        students will be submitting rather than just a list of due dates.
      </p>
      <p>
        Although the syllabus submitted in support of a General Education proposal may
        well be an actual one distributed to students, it doesn’t have to be. For new
        courses or courses that vary the assignments across sections or semesters, a
        synthetic or representative syllabus that provides a model for how the course is
        taught is perfectly acceptable. Occasionally, it can be useful to provide two
        different syllabi for the course to show what aspects of the course do and don’t
        vary across offerings. In this case, combine the two syllabi, clearly identified
        and separated from each other, in a single document for uploading. See <a
        href='https://senate.qc.cuny.edu/Curriculum/Syllabi/ENGL-110_2012-11-29.pdf'>the
        syllabus for English 110</a> for an example.
      </p>
      <h3>Justifications</h3>
      <p>
        All Queens College General Education courses must meet two criteria that were
        established when the “Perspectives” (PLAS) curriculum was adopted in 2006:
        (a) “Address how, in the discipline (or disciplines) of the course, data and
        evidence are construed and knowledge is acquired; that is, how questions are asked
        and answered.”
        (b) “Position the discipline(s) in the liberal arts curriculum and the larger
        society.”
      </p>
      <p>
        Note that both these criteria are <em>discipline oriented</em>, and that they
        relate to the first of the three key elements of the syllabus listed above.  As a
        liberal arts college, Queens strives to structure the General Education portion of
        the curriculum as a coherent unity rather than as a set of disparate un-related
        requirements. Use the justifications for these two criteria to explain to the
        Queens College reviewers how the course reinforces this integrative approach to
        general education.
      </p>
      <p>
        Proposals for “CUNY Core” (Pathways) areas are evaluated on the basis of “learning
        outcomes.” In practice, this means that the justifications have to be stated in
        terms of activities that students are actually graded on in the course. A topic
        may be germaine to a particular learning outcome, but "covering,” “discussing,” or
        ”reading about” a topic is not sufficient justification for claiming that the
        learning outcome will actually be met.
      </p>
      <p>
        Rather, the justifications for learning outcomes must cite the graded activities
        listed in the syllabus that make it possible to establish that the outcomes are
        met.
      </p>
      <p>
        Make sure the time allocated to&#x2014;and the grading weight for&#x2014;each
        activity in the syllabus has face validity for supporting the corresponding
        justifications in the proposal.
      </p>
      <p>
        Justifications are normally not long: two to five sentences should be fine. A lot
        of reviewers are reading a lot of proposals, so you want to make your case clearly
        and concisely.  Eshew both puffery and vagueness!
      </p>
      <h3>Advice</h3>
      <p>
        Avoid justifying the discipline or particular course topic in the CUNY
        Core justifications. Concentrate on what students will do in the course instead.
        The only place you would need to say something about the discipline or course
        topic is in the two Queens College “perspectives” critera and, possibly, the
        Queens College “College Option” areas (Language, Literature, Science, and
        Synthesis).
      </p>
      <p>
       Do not include more justifications than are required unless you think they really
       strengthen the case for the course. Go for consistently good justifications rather
       than a larger number of weaker ones.
      </p>
    </div>

    <h2>Model Proposals</h2>
    <div>
      <!-- EC-2 -->
      <section id='EC'>
        <a href='#EC'>English Composition (CUNY Core)</a> [No model available]
        <section>
          <h1>Comments</h1>
          <div>
            <p>
              The Writing Subcommittee guidelines for English Composition (“College
              Writing 2”) courses are avalable as a separate document: <a
              href='docs/2012-09-27_CW2_Guidelines.pdf'>CW2 Guidelines</a>.
            </p>
          </div>
        </section>
      </section>

      <!-- MQR -->
      <section id='MQR'>
        <a href='#MQR'>Mathematical and Quantitative Reasoning (CUNY Core)</a> [No model
        available]
        <section>
          <h1>Comments</h1>
          <div>
            None available
          </div>
        </section>
      </section>

      <!-- LPS -->
      <section id='LPS'>
        <a href='#LPS'>Life and Physical Sciences (CUNY Core)</a> [No model available]
        <section>
          <h1>Comments</h1>
          <div>
            None available
          </div>
        </section>
      </section>

      <!-- WC -->
      <section id='WC'>
        <a href='../Proposals?id=194'>Proposal #194: World Cultures and Global Issues
        (CUNY Core)</a>
        <section>
          <h1>Comments</h1>
          <div>
            <ul>
              <li>As originally submitted, this proposal was very long. It has since been
              revised, but parts of it are still longer than necessary.</li>
              <li>What the committee liked about the proposal is that it clearly indicates
              how the student assignments given in the syllabus address the criteria
              for the WC designation.</li>
            </ul>
          </div>
        </section>
      </section>

      <!-- USED -->
      <section id='USED'>
        <a href='../Proposals?id=171'>Proposal #171: United States Experience in its
        Diversity (CUNY Core)</a>
        <section>
          <h1>Comments</h1>
          <div>
            <ul>
              <li>The committee found the justifications to be complete, compelling, and
              well-related to the sample syllabus.</li>
              <li>A particularly strong feature of this proposal is that it makes it clear
              that the course can be taught in a variety of styles, according to the
              instructor’s preferences, yet always satisfies the criteria for the US
              designation at Queens and across the university.</li>
            </ul>
          </div>
        </section>
      </section>

      <!-- CE -->
      <section id='CE'>
        <a href='../Proposals?id=165'>Proposal #165: Creative Expression (CUNY Core)</a>
        <section>
          <h1>Comments</h1>
          <div>
            <ul>
              <li>
                This proposal illustrates the case where a course needs two proposals to
                achieve designation as a CUNY Core course. In addition to proposal #165,
                there is a related proposal (<a href='../Proposals?id=187'>#187</a>)
                that revises the course catalog information so that the course satisfies a
                property outside the scope of the CE learning outcomes, specifically to
                change its Liberal Arts Designation from Non-Liberal Arts to Regular
                Liberal Arts.
              </li>
              <li>
                Notice that each justification specifies just what students will do in the
                course to achieve the objectives. Each justification either explicitly or
                implicitly refers to the student activities given in the sample syllabus.
              </li>
            </ul>
          </div>
        </section>
      </section>

      <!-- IS -->
      <section id='IS'>
        <a href='#IS'>Individual and Society (CUNY Core)</a> [No model available]
        <section>
          <h1>Comments</h1>
          <div>
            None available
          </div>
        </section>
      </section>

      <!-- SW -->
      <section id='SW'>
        <a href='#'>Scientific World (CUNY Core)</a> [No model available]
        <section>
          <h1>Comments</h1>
          <div>
            None available
          </div>
        </section>
      </section>

      <!-- LANG -->
      <section id='LANG'>
        <a href='#'>Language (College Option)</a> [No model available]
        <section>
          <h1>Comments</h1>
          <div>
            None available
          </div>
        </section>
      </section>

      <!-- LIT -->
      <section id='LIT'>
        <a href='#'>Literature (College Option)</a> [No model available]
        <section>
          <h1>Comments</h1>
          <div>
            None available
          </div>
        </section>
      </section>

      <!-- SCI -->
      <section id='SCI'>
        <a href='#'>Science (College Option)</a> [No model available]
        <section>
          <h1>Comments</h1>
          <div>
            None available
          </div>
        </section>
      </section>
    </div>
  </body>
</html>
