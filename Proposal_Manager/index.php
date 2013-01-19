<?php  /* Proposal_Editor/index.php */

set_include_path(get_include_path() . PATH_SEPARATOR . '../scripts' );
require_once('init_session.php');
require_once('syllabus_utils.php');

require_once('include/atoms.inc');

//  Global Variables
//  --------------------------------------------------------------------------------------
/*  These variables are declared here to give them global scope. $person is manged in the
 *  login module; the others are set in the select_proposal module, and referenced in the
 *  Proposal and Syllabus modules.
 */
  $person               = null; //  Object of class Person
  $proposal             = null; //  Object of class Proposal
  $cur_catalog          = null; //  Object of class Course
  $new_catalog          = null; //  Object of class Course
  $proposal_course_str  = '';
  $proposal_type        = '';
  $proposal_class       = '';

//  Here beginnith the web page
//  ---------------------------------------------------------------------------------------
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
    <title>Manage Curriculum Proposals</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/proposal_editor.css" />
    <script type="text/javascript" src="../../js/jquery-current.js"></script>
    <script type="text/javascript" src="../js/site_ui.js"></script>
    <script type="text/javascript" src="js/select_proposal.js"></script>
    <script type="text/javascript" src="js/proposal_editor.js"></script>
  </head>
  <body>
<?php
echo $dump_if_testing;
?>
    <h1>Manage Proposals</h1>
    <div class='warning overview'>
      <ul>
        <li>
          <strong>Sign in</strong> using your Queens College email address. No password
          required.
        </li>
        <li>
          Use this page to <strong>create</strong>, <strong>edit</strong>, and
          <strong>submit</strong> proposals. See the detailed instructions for more
          information.
        </li>
        <li>
          <strong>Note:</strong>
          If you want to propose a <em>new</em> course for a requirement, you must create
          a “new course” proposal <em>before</em> you can create the proposal for the
          requirement.
        </li>
        <li>
          <strong>Upload the syllabus</strong> for a course. Once there is a syllabus for
          a course, all proposals for that course reference it automatically.
        </li>
        <li>
          Turn <strong>detailed instructions</strong> on/off using the button in the top
          left corner of the window.
        </li>
        <li>
          <strong>Note:</strong>
          <img src="../images/external.png" alt='' /> means the link or button will open a
          new tab or window.
        </li>
      </ul>
      <p>
        Please report suggestions or problems that you encounter with the site to
        Christopher Vickery.
      </p>
    </div>

    <!-- Instructions -->
    <div class='instructions'>
      <p><strong>Overview</strong></p>
      <p>
        This is the Academic Senate’s site for preparing proposals for the Graduate or
        Undergraduate Curriculum committee and their subcommittees, including
        <span class='acronym' title='Abstract or Quantitative Reasoning Advisory Committee'>
          AQRAC
        </span>,
        <span class='acronym' title='General Education Advisory Committee'>GEAC</span>,
        and
        <span class='acronym' title='Writing Intensive Subcommittee'>WISC</span>.
      </p>
      <p>
        There are two types of proposals:
      </p>
      <ul>
        <li>
          <strong>Course Proposals</strong>
          <ul>
            <li>Add a new course to the curriculum.</li>
            <li>Revise catalog information for an existing course</li>
            <li>Report problems with <em>CUNYfirst</em> course catalog data that need to
            be fixed</li>
          </ul>
        </li>
        <li>
          <strong>Requirement Designation Proposals</strong>
          <p>
            Request a course to be designated as satisfying college or university degree
            requirements, including CUNY Core requirements and QC College Option
            requirements.
          </p>
        </li>
      </ul>
      <p>
        Everything on this web page deals only with proposals for individual courses.
        Consult the GCC or UCC for new programs, changes to majors or minors, or other
        changes affecting groups of courses.
      </p>
      <p>
        In order to create a designation proposal for a course, the course must either
        already exist in the CUNYfirst course catalog or be the subject of a “new” or
        “fix” course proposal. Although the course proposal (if needed) must be created
        before the designation proposal, once both proposals have been created, they can
        be edited and submitted in any order. Just be sure to submit both.
      </p>
      <p>
        If an existing course does not need to be revised in order to qualify for a
        designation, you do not need to submit a course proposal for it, just a
        designation proposal.
      </p>
      <p>
        <strong>Proposal Preparation Guidelines</strong>
      </p>
      <p>
        The <a href='../Model_Proposals' target='new'> Guidelines</a> link at the top of
        the page will take you to some guidelines for preparing proposals, along with some
        annotated samples to look at.
      </p>
      <p>
        <strong>Terminology Notes:</strong>
      </p>
        <ul>
          <li>
            CUNY uses “Common Core” to refer to the 30 credits of General Education
            courses that all CUNY students must complete. We use “CUNY Core” to refer to
            the Queens College courses that are designated as satisfying those requirements.
            <p>
              CUNY Core courses offered at Queens must meet all the criteria established
              by CUNY, plus additional criteria required of all General Education courses
              offered at Queens.
            </p>
          </li>
          <li>
            CUNY uses “College Option” to refer to the 12 credits of General Education
            courses that all CUNY baccalaureate students must complete.  We use “QC
            College Option” to refer to the Queens College courses that are designated as
            satisfying those requirements.
          </li>
          <li>
            <p>
              We use the term “designation” rather than “requirement” for two reasons.
            </p>
            <p>
              One reason is that “requirement” can mean either something a student must do
              in order to graduate, or one of the properties a course must exhibit in
              order to be included in the list of courses that a student must choose from.
              Out of context, “requirement” becomes ambiguous.
            </p>
            <p>
              The second reason is that the University is using the term “Requirement
              Designation” to indicate which General Education requirement(s) a course
              satisfies. CUNYfirst presently uses this term to indicate whether a course
              satisfies State guidelines for liberal arts or not, and this information is
              being augmented with the CUNY Core designation that a course carries.
            </p>
          </li>
          <li>
            CUNY uses “Pathways” to refer to an initiative to make transferring from
            one CUNY college to another less burdensome for students. The term covers the
            CUNY Core, College Option, and common introcuctory-level courses for majors.
            <p>
              Proposals entered here include designation proposals for the CUNY Core and
              College Option parts of Pathways, but nothing that has to do with the
              “Pathways to the Major” initiative.
            </p>
            <p>
              Course proposals entered here include both those related to Pathways, and
              “regular” course proposals to the UCC or GCC that have nothing to do with
              Pathways.
            </p>
          </li>
        </ul>
      <h3 class='sub-instructions'>Save and Submit Proposals</h3>
      <p>
        You will see buttons for saving your work in the proposal editing section.  You
        cannot submit a proposal until it has been saved. But once you have saved a
        proposal, you don’t have to submit it right away. You can come back to it any time
        to make more changes before submitting it. Old proposals that don’t get
        submitted will disappear after a while, but you will be notified before they get
        deleted.
      </p>
      <p>
        There is a <a href='../Proposals' target='new'>separate web page</a> for tracking
        all proposals that have been submitted here. The “Track Proposals” button at the
        top of this page is a link to it.
      </p>
      <h3 class='sub-instructions'>Upload Syllabi</h3>
      <p>
        At least one, current, sample syllabus must be on file for all each course that is
        the subject of a course or designation proposal. There is a section for uploading a
        new or additional syllabus for courses at the bottom of this web page.
      </p>
      <p>
        There is a <a href='../Syllabi' target='new'>separate web page</a> for browsing
        the archive of all syllabi on file.
      </p>
      <fieldset><legend>Manage This Page</legend>
        <h3 class='sub-instructions'>Hide Instructions To Save Screen Space</h3>
        <p>
          Use the “Hide Instructions” button at the very top of the page to hide/show the
          shaded instruction panels, such as this one. The <code>F2</code> key should do
          the same thing, but does not work in all browsers.
        </p>
        <h3 class='sub-instructions'>
          Click Gray Title Bars To Open/Close Sections
        </h3>
        <p>
          You can also use the &lt;Tab&gt; key to get to the section you want, and then
          use the &lt;Enter&gt; key to open or close it. The right arrow key also opens a
          section, and either the left arrow key or the &lt;Esc&gt; key closes it.
        </p>
      </fieldset>
    </div>
    <p id='need-javascript'>
      This site will not work unless you enable JavaScript. If you see this message, it
      means that JavaScript is not enabled.
    </p>

    <?php

    //  Handle the logging in/out situation here
    $last_login = '';
    require_once('../scripts/short-circuit.php');
    if (isset($_SESSION[need_password])) unset($_SESSION[need_password]);
    require_once('../scripts/login.php');
    if (isset($_SESSION[session_state]) && $_SESSION[session_state] === ss_is_logged_in)
    {
      if (! $person)
      {
        if (isset($_SESSION[person]))
        {
          $person = unserialize($_SESSION[person]);
        }
        else
        {
          die("<h1 class='error'>Program Error: Invalid login state</h1></body></html>");
        }
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

      //  Process necessary modules, depending on current editing state.
      //  -------------------------------------------------------------------------------

      //  Always provide the option to select a different proposal or start a new one.
      require_once('scripts/select_proposal.php');

      //  Display catalog information and editor controls only if a proposal has been
      //  selected for editing.
      if (isset($_SESSION[proposal]))
      {
        $proposal = unserialize($_SESSION[proposal]);
        require_once('scripts/proposal_editor.php');
      }
      else
      {
        $proposal = null;
        echo <<<EOD

  <h2 id='editor-section'>Edit Proposal: No proposal open</h2>
  <div> <!-- no content to show/hide --></div>

EOD;

      }

      //  Always include the syllabus upload section.
      require_once('scripts/syllabus.php');
    }
    else
    {
      //  This page requires the user to be signed in.
      $status_msg = 'Not signed in';
      $sign_out_button = '';
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

    //  Second row (editor navigation) exists only if person is logged in. And then its
    //  contents depend on what the user is doing.
    $editor_nav = '';
    if ($person)
    {
      $editor_nav = <<<EOD
    <nav>
      <button class='nav-button' id='select-proposal-section-nav'>Create Proposal</button>

EOD;
      if (isset($_SESSION['proposal']))
      {
        $editor_nav .= <<<EOD
      <button class='nav-button' id='catalog-info-section-nav'>Catalog Info</button>
      <button class='nav-button' id='edit-proposal-section-nav'>Edit Proposal</button>
      <button id='save-changes-nav' disabled='disabled'>Save Changes</button>
      <button id='submit-proposal-nav'>
        Submit Proposal
     </button>

EOD;
      }

      $editor_nav .= <<<EOD
      <button class='nav-button' id='upload-syllabus-section-nav'>Upload Syllabus</button>
    </nav>

EOD;
    }
    echo <<<EOD
    <div id='status-bar'>
      <button id='show-hide-instructions-button'>
        Hide Instructions
      </button>
      $sign_out_button
      <div id='status-msg' title='$last_login'>
        $status_msg
      </div>
      <!-- Navigation -->
      <nav>
        <a href='../Proposals'>Track Proposals</a>
        <a href='../Model_Proposals'>Guidelines</a>
        <a href='.' class='current-page'>Manage Proposals</a>
        <a href='../Syllabi'>Syllabi</a>
        <a href='../Reviews'>Reviews</a>
        $review_link
      </nav>
      $editor_nav
    </div>

EOD;
  ?>
  </body>
</html>
