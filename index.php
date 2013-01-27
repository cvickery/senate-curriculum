<?php
//  Curriculum/index.php

  //  Force HTTPS connection if not already in place and not coming from localhost
  if ( !isset($_SERVER['HTTPS']) &&
       !(isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'localhost') )
  {
    header("Location: https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}");
    exit;
  }

//  Modules used by all pages ... tailored for being up one directory level.
//  -------------------------------------------------------------------------------------
  date_default_timezone_set('America/New_York');
  session_start();
  require_once('credentials.inc');
  require_once('include/atoms.inc');
  require_once('scripts/assertions.php');
  require_once('scripts/classes.php');
  require_once('scripts/utils.php');
  $form_name = '';
  if ( isset($_POST[form_name])) $form_name = sanitize($_POST[form_name]);

  //  Handle the logout form if it was submitted.
  //  -----------------------------------------------------------------------------------
  $home_dir = 'Curriculum';
  if (strstr($_SERVER['REQUEST_URI'], 'testing'))
  {
    $home_dir = 'testing_Curriculum';
  }

  if ($form_name === 'logout')
  {
    //  Clear all session variables
    $keys = array_keys($_SESSION);
    foreach ($keys as $key)
    {
      unset($_SESSION[$key]);
    }
    //  Set the session_state
    $_SESSION['session_state'] = ss_not_logged_in;
    //  And redirect to site index page
    header("Location: https://{$_SERVER['SERVER_NAME']}/$home_dir");
    exit;
  }

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
  </head>
  <body>
<?php

//  Handle the logging in/out situation here
$instructions_button = '';
$status_msg          = 'Not signed in';
$sign_out_button     = '';
require_once('scripts/login.php');
if (isset($_SESSION[session_state]) && $_SESSION[session_state] === ss_is_logged_in)
{
    if (isset($_SESSION[person]))
    {
      $person = unserialize($_SESSION[person]);
    }
    else
    {
      die("<h1 class='error'>Invalid login state</h1></body></html>\n");
    }
    $status_msg = sanitize($person->name) . ' / ' . sanitize($person->dept_name);
    $sign_out_button = <<<EOD

    <form id='logout-form' action='.' method='post'>
      <input type='hidden' name='form-name' value='logout' />
      <button type='submit'>Sign Out</button>
    </form>

EOD;
}
 ?>
    <h1>Queens College Curriculum</h1>
    <div>
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
        <em>Requires a valid Queens College email address.</em>
      </dd>

      <dt><a href="Reviews">Reviews</a></dt>
      <dd>
        View subcommittee or curriculum committee reviewers’ comments and
        recommendations for proposals.
        <br/>
        <em>Requires a valid Queens College email address.</em>
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
      <dd>QC Curriculum material extracted from CURs. (not current)</dd>

    </dl>

    <!-- Status Bar -->
<?php
    $review_link = '';
    if (isset($person) && $person->has_reviews)
    {
      $review_link = "<a href='./Review_Editor'>Edit Reviews</a>";
    }

    echo <<<EOD
    <div id='status-bar'>
      $instructions_button
      $sign_out_button
      <div id='status-msg'>
        $status_msg
      </div>
      <!-- Navigation Row-->
      <nav>
        <a href='./Proposals'>Track Proposals</a>
        <a href='./Model_Proposals'>Guidelines</a>
        <a href='./Proposal_Manager'>Manage Proposals</a>
        <a href='./Syllabi'>Syllabi</a>
        <a href='./Reviews'>Reviews</a>
        $review_link;
      </nav>
    </div>

EOD;
 ?>
  </body>
</html>
