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
    $instructions_button
    $status_msg
    $nav_bar
  </div>
  <h1>Queens College Academic Senate<br />Curriculum</h1>
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
    <div class='instructions'>
      <h3>Responsibilities of the Academic Senate</h3>
      <p>
        The Academic Senate of Queens College is responsible, subject to the CUNY Board of
        Trustees, for the formulation of policy relating to the admission and retention of
        students, curriculum, granting of degrees, campus life, and the nomination of
        academic deans.
      </p>
      <h3>Role of this web site</h3>
      <p>
        This site supports the work of the Academic Senate’s various curriculum committees
        and subcomiittees, primarily by providing tools and mechanisms for proposing
        changes to the courses offered at Queens College, and for tracking those proposals
        through the approval process.
      </p>
    </div>
    <h2>What’s Here</h2>
    <dl>

      <dt><a href="Proposals">Track Proposals</a></dt>
      <dd>
        View and track curriculum proposals.
      </dd>

      <dt><a href="Model_Proposals">Proposal Guidelines</a></dt>
      <dd>
        Guidelines for preparing curriculum proposals, with examples.
      </dd>

      <dt><a href="Proposal_Manager">Manage Proposals</a></dt>
      <dd>
        Create, edit, submit, or delete your course and designation proposals.
        <br/>
        <em>Access requires a valid Queens College email address.</em>
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
        <em>Access requires a valid Queens College email address.</em>
      </dd>

      <dt><a href="Pathways">Senate Ad Hoc Committee on Pathways</a></dt>
      <dd>
        Archive of documents and discussion leading to the Queens College Pathways
        Implementation Plan adopted by the Academic Senate on March 29, 2012.
      </dd>

      <dt><a href="gened_offerings">Perspectives Offerings</a></dt>
      <dd>
          Historical and current Perspectives (PLAS) course offerings.
      </dd>

      <dt><a href="cur_reports.php">Chancellor’s University Reports (CURs)</a></dt>
      <dd>QC Curriculum material extracted from CURs. (<em>not current</em>)</dd>

    </dl>
  </body>
</html>
