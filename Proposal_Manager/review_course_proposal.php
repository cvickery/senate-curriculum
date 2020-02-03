<?php
// Proposal_Manager/review_course_proposal.php
set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() . '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');

if ( !isset($person) )
{
  //  Attempt to access this page while not logged in.
  $_SESSION[login_error_msg] = 'Sign-in Required';
  header("Location: $site_home_url");
}

require_once('proposal_manager.inc');
require_once('syllabus_utils.php');
require_once('simple_diff.php');
require_once('mail_setup.php');

/*  Display the course proposal for the submitter to review. Submitting the do-it form at
 *  the bottom of the page confirms that the proposal is ready to send out for
 *  verification.
 */
$cur_catalog        = unserialize($_SESSION[cur_catalog]);
$new_catalog        = unserialize($_SESSION[new_catalog]);
$proposal           = unserialize($_SESSION[proposal]);
$proposal_id        = $proposal->id;
$dept_approval_name = $proposal->dept_approval_name;
$dept_approval_date = $proposal->dept_approval_date;
$submitter_email    = $proposal->submitter_email;
$submitter_name     = $proposal->submitter_name;
$discipline         = $proposal->discipline;
$course_number      = $proposal->course_number;
$guid               = $proposal->guid;
$agency_name        = $agency_names[$proposal->agency_id];
$base_dir           = basename(dirname(getcwd()));
$view_url           = "https://senate.qc.cuny.edu/$base_dir/Proposals?id=$proposal_id";
$submission_url     =
       "https://senate.qc.cuny.edu/$base_dir/Proposal_Manager/submit_proposal.php?token=$guid";

//  To be determined:
$email_sender     = 'Academic Senate';

$introductory_text = <<<EOD
You, or someone else using this Queens College email account on your behalf, created a Curriculum proposal. If this is not the case, you may ignore this message. But if you do want to submit the proposal, you must now click the “Submit” link below. If you do nothing, the proposal will simply disappear after a while.
EOD;

$summary_text = '';
$type_abbr = $proposal_type_id2abbr[$proposal->type_id];
$type_name = $proposal_type_id2name[$proposal->type_id];
switch ($type_abbr)
{
  case 'NEW-U':
    $summary_text = <<<EOD
Proposal #$proposal_id is to submit $discipline $course_number as a new course to the Undergraduate Curriclum Committee.
EOD;
    break;
  case 'NEW-G':
    $summary_text = <<<EOD
Proposal #$proposal_id is to submit $discipline $course_number as a new course to the Graduate Curriclum Committee.
EOD;
    break;
  case 'REV-U':
    $summary_text = <<<EOD
Proposal #$proposal_id is to submit $discipline $course_number to the Undergraduate Curriclum Committee for revision.
EOD;
    break;
  case 'REV-G':
    $summary_text = <<<EOD
Proposal #$proposal_id is to submit $discipline $course_number to the Graduate Curriclum Committee.
EOD;
    break;
  case 'FIX':
    $summary_text = <<<EOD
Proposal #$proposal_id is a request to correct catalog information in CUNYfirst for $discipline $course_number.
EOD;
    break;
  default:
    die("Bad switch ({$proposal_type_id2abbr[$proposal->type_id]}) " .
        basename(__FILE__) . " line " . __LINE__);
}

//  Set up the list of who will be notified when the course is submitted
$copies_to    = array();
$dept_abbr    = $depts_by_id[$proposal->dept_id];
$chair        = $chairs_by_abbr[$dept_abbr];
$chair_email  = $chair->email;
$chair_name   = $chair->name;
$div_abbr     = $divs_by_id[$proposal->div_id];
$dean         = $deans_by_abbr[$div_abbr];
$dean_email   = $dean->email;
$dean_name    = $dean->name;
if ($chair_email && strtolower($chair_email) !== strtolower($submitter_email))
{
  $copies_to[] = "Chair " . $chair_name;
}
if ( $dean_email && ($dean_email !== $chair_email) &&
    (strtolower($dean_email) !== strtolower($submitter_email)) )
{
  $copies_to[] = "Dean " . $dean_name;
}

$copies_text = '';
if ($type_abbr === 'FIX')
{
  $copies_text = <<<EOD
When you submit the request, the Registrar will verify that the changes are correct, and will then update the information in CUNYfirst.

EOD;
}
else
{
  $num_CCs = count($copies_to);
  if ($num_CCs > 0)
  {
    $txt = ($num_CCs === 1) ? 'a copy' : 'copies';
    $copies_text =
      'When you submit the proposal, ' .
      and_list($copies_to)                                .
      " will also receive $txt of it.";
  }
}

if ($form_name === do_it)
{
  //  Update the opened date. The old and new catalog info should already have
  //  been saved.
  $cur_catalog    = serialize($cur_catalog);
  $new_catalog    = serialize($new_catalog);
  $justifications = serialize($proposal->justifications);
  $query = <<<EOD
UPDATE proposals
   SET opened_date    = now(),
       cur_catalog    = '$cur_catalog',
       new_catalog    = '$new_catalog',
       justifications = '$justifications'
 WHERE guid           = '{$proposal->guid}'
EOD;

  $result = pg_query($curric_db, $query) or die('Failed to update course proposal: '
      . basename(__FILE__) . ' ' . __LINE__);

  //  Send the email: both text-only and MIME (HTML) formats
  //  -------------------------------------------------------------------------
  $base_dir = dirname(getcwd());  //  To accomodate separate testing directory

  //  text-only version
  $text_msg = <<<EOD

$submitter_name:

$introductory_text

$summary_text

To submit the proposal now, use this link: $submission_url

You can view or print the proposal at this link: $view_url

$copies_text

Thank you!
$email_sender

EOD;

  //  HTML version
  $html_msg = <<<EOD

  <p>$submitter_name:</p>
  <p>
    $introductory_text
  </p>
  <p>
    $summary_text
  </p>
  <ul>
  <h2><a href='$submission_url'>Submit the Proposal</a></h2>
  <p>You can also <a href='$view_url'>view or print the proposal</a>.</p>
  <p>
    $copies_text
  </p>
  <p>
    Thank you!
    <br />$email_sender
  </p>

EOD;
  $mail = new Senate_Mail('QC Curriculum<nobody@qc.cuny.edu>', $submitter_email,
    "Verifying your course proposal for $discipline $course_number",
     $text_msg, $html_msg);
  $mail->send() or die( $mail->getMessage() .
      " <a href='.'>try again</a> or report the problem to $webmaster_email");

  //  redirect to the congratulations page
  $goto = str_replace(basename($_SERVER['SCRIPT_FILENAME']),
                      'sent_verification.php',
                      $_SERVER['REQUEST_URI']);
  header("Location: $goto");
  exit;
}

//  Here beginneth the web page
//  =====================================================================================
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
    <title>Review Course Changes</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel='stylesheet' type='text/css' href='../css/proposal_editor.css' />
    <script type="application/javascript" src="../js/jquery.min.js"></script>
    <script type="application/javascript" src="../js/site_ui.js"></script>
    <style type="text/css">
     ins {text-decoration:underline;}
     del {text-decoration:line-through;}
    </style>
  </head>
  <body>
<?php
  $status_msg = login_status();
  $nav_bar    = site_nav();

  //  Navigation row for this page
  if (isset($_SESSION['proposal']))
  {
    $editor_nav = <<<EOD
    <nav>
      <a class='nav-button' href='index.php'>Return to Editor</a>
    </nav>
EOD;
  }
  else
  {
    die("<h1 class='error'>Error: access to submit proposal page with no proposal " .
      "active at " . basename(__FILE__) . ' line ' . __LINE__ . "</h1></body></html>");
  }

  echo <<<EOD
    <div id='status-bar'>
      $instructions_button
      $status_msg
      $nav_bar
      $editor_nav
    </div>
    <div>
    <h1>
      Final Review Before Submission of Proposal #$proposal_id:<br />
      $type_name for $discipline $course_number<br />
      <span class='error'>
        You still need to click the Submit Proposal button at the bottom of this page
      </span>
    </h1>
    $dump_if_testing
    <div class='instructions'>
      Check this summary of your proposal, and if everything seems to be in order,
      click the “Submit Proposal” button at the bottom of the page. You will then
      need to respond to an email message we will send to $person to complete the
      submission process.
    </div>

EOD;

  $syllabus_pathnames = get_syllabi("$discipline $course_number");
  $syllabus_info = <<<EOD
  <h2 class='error'>
    Warning: No syllabi for $discipline $course_number have been uploaded yet.
  </h2>

EOD;
  $num_syllabi = count($syllabus_pathnames);
  if ($num_syllabi > 0)
  {
    $suffix = ($num_syllabi > 1) ? 'i' : 'us';
    $syllabus_info = <<<EOD
  <h2>Syllab$suffix for $discipline $course_number</h2>
  <ul>
EOD;
    rsort($syllabus_pathnames);
    foreach($syllabus_pathnames as $pathname)
    {
      preg_match('/(\d{4})-(\d{2})-(\d{2})/', $pathname, $matches);
      $date_str       = matches2datestr($matches);
      $file_size      = humanize_num(filesize($pathname));
      $syllabus_info .= "    <li><a href='$pathname'>$date_str ($file_size)</a></li>\n";
    }
    $syllabus_info .= "  </ul>\n";
  }

  echo $syllabus_info;
  if ($new_catalog->course_id !== 0)
  {
    echo "<h2>Current Catalog Information</h2>\n"  . $cur_catalog->toHTML();
    echo "<h2>Proposed Catalog Information</h2>\n" . $new_catalog->toHTML();
    echo "<h2>Summary of Changes</h2>\n" .
          Course::diffs_to_html($cur_catalog, $new_catalog);
  }
  else
  {
    echo "<h2>Proposed Catalog Information (new course)</h2>\n" . $new_catalog->toHTML();
  }
  $justification = $proposal->justifications->$type_abbr;

  //  Block proposals that are missing the justification or, for NEW/REV proposals,
  //  missing the department approval name or date.
  if ($justification === '')
  {
    echo "<h1 class='error'>Cannot Submit: No Justification</h1>\n";
  }
  else if ( in_array($type_abbr, $require_dept_approval) &&
           ($dept_approval_name == '') )
  {
    echo "<h1 class='error'>Missing Approval Department Name</h1>\n";
  }
  else if ( in_array($type_abbr, $require_dept_approval) &&
           ($dept_approval_date == '') )
  {
    echo "<h1 class='error'>Missing or Invalid Department Approval Date</h1>\n";
  }
  else if ( in_array($type_abbr, $require_dept_approval) &&
            ( $dept_approval_date == 'Enter approval date') )
  {
    echo "<h1 class='error'>Invalid or missing Department Approval Date</h1>\n";
  }
  else
  {
    //  OK to submit the proposal: display the remaining information about the proposal
    //  and provide the submission form.
    if (in_array($type_abbr, $require_dept_approval))
    {
      $approval_date_str = "<span class='warning'>pending</span>";
      if (strtolower($dept_approval_date) !== 'pending')
      {
        $approval_date_str = "on $dept_approval_date";
      }
      echo <<<EOD
  <h2>Department Approval by $dept_approval_name $approval_date_str</h2>

EOD;
    }
    echo  "<h2>Proposal Justification</h2>\n" .
          "<p class='designation-paragraph'>$justification</p>\n";

    //  Form for submitting the proposal
    //  ----------------------------------------------------------------------------------
    echo <<<EOD
<h2>Submit Proposal</h2>
<form method='post' action='{$_SERVER['SCRIPT_NAME']}'>
<fieldset>
  <input type='hidden' name='form-name' value='do-it' />
  <p>
    <strong>Summary:</strong> $summary_text
  </p>
  <p>
    Review the information above. If you need to change anything, <a href='.'>return to
    the proposal editing page</a>.
  </p>
  <p>
    There are still two more steps you must take in order to submit this proposal:
  </p>
  <ol>
    <li>
      Verify that you are who you say you are by clicking the “Submit Proposal”
      button below, which will send a message to you at $submitter_email.
    </li>
    <li>
      <strong>The email message will include a link that you must click</strong>
      to <em>finally-finally</em> submit the proposal and notify the $agency_name.
      $copies_text
    </li>
  </ol>
    <div>
      <input type='hidden' name='agent-id' value='{$proposal->agency_id}' />
      <button type='submit' class='centered-button call-to-action'>
        Submit Proposal
      </button>
    </div>
  </fieldset>
</form>
EOD;
  }
?>
  </div>
  </body>
</html>
