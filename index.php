<?php
//  /index.php
set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() . '/scripts'
    . PATH_SEPARATOR . getcwd() . '/include');
require_once('init_session.php');

//  Generate site index page
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
    <title>QC Curriculum</title>
    <link rel="stylesheet" type="text/css" href="css/curriculum.css" />
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/site_ui.js"></script>
  </head>
  <body>
<?php
  //  Status Bar and H1 element
  $status_msg = login_status();
  $nav_bar    = site_nav();
  echo <<<EOD
  <div id='status-bar'>
    $status_msg
    $nav_bar
  </div>
  <h1>Queens College Academic Senate<br />Curriculum Matters</h1>
  $dump_if_testing

EOD;

  //  May have been redirected here by attempt of non-administrator to access Admin
  //  section.
  if (isset($_SESSION[login_error_msg]))
  {
    echo "    <h2 class='error'>{$_SESSION[login_error_msg]}</h2>\n";
    unset($_SESSION[login_error_msg]);
  }
?>
    <div>
      <p>
        The Academic Senate of Queens College “is responsible, subject to the CUNY Board of
        Trustees, for the formulation of policy relating to the admission and retention of
        students, curriculum, granting of degrees, campus life, and the nomination of
        academic deans.”
      </p>
      <p>
        This site supports the work of the Academic Senate’s various curriculum committees
        and subcomittees. It provides information about courses currently offered at Queens College,
        mechanisms for proposing new courses or changing existing courses, and a way to track those
        proposals through the approval process.
      </p>
    </div>

    <h2>Information</h2>
    <dl>

      <dt><a href="Approved_Courses">Approved General Education Courses</a></dt>
      <dd>
        List of QC courses approved for Pathways requirements.
      </dd>
      <dt><a href="Approved_Courses/offered_gened.php">Scheduled General Education Courses</a>
      </dt>
      <dd>
        Select from past as well as present terms. Indicates “nearly full” enrollment indicators.
        Updated daily.
      </dd>

      <dt><a href="Lookup_Course">Course Information</a>
      </dt>
      <dd>
        <p>
          Includes catalog information from CUNYfirst; general education requirements each course
          satisfies (if any); basis for general education approvals; course attributes; and
          enrollment histories. Updated daily.
        </p>
        <p>
          <em>Note:</em> You can look up multiple courses at a time by using wildcards (
          <strong><code>*</code>, </strong><strong><code>+</code></strong>, and
          <strong><code>?</code></strong>) in the course number field.
        </p>
      </dd>
      <dt>
        <a href="College_Option">College Option Calculator</a>
      </dt>
      <dd>Answer questions about a student’s history to determine how many College Option credits
        the student must complete at QC.</dd>

      <h2>Proposal Processes</h2>
      <dt><a href="Proposals">Track Proposals</a></dt>
      <dd>
        View and track curriculum proposals.
      </dd>

      <dt><a href="Proposal Guidelines">General Education Proposal Guidelines</a></dt>
      <dd>
        Guidelines for preparing proposals for CUNY Common Core and QC College Option designations.
      </dd>

      <dt><a href="Proposal_Manager">Manage Proposals</a></dt>
      <dd>
        Create, edit, submit, or delete your course and designation proposals.
        <br/>
        <em>Access requires you to sign in with a valid Queens College email address.</em>
      </dd>

      <dt><a href='Syllabi'>Syllabi</a></dt>
      <dd>
        Review course syllabi stored on this site.
        <br/>
        <em>All syllabi are Copyright © Queens College of CUNY unless otherwise
        indicated.</em>
      </dd>

      <dt><a href="Reviews">GEAC Reviews</a></dt>
      <dd>
        View individual comments from General Education Advisory Committee (GEAC)
        recommendations.
        <br/>
        <em>Access requires you to sign in with a valid Queens College email address.</em>
      </dd>
    </dl>
  </body>
</html>
