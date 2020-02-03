<?php
// Proposal_Manager/review_designation_proposal.php
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
 *  the bottom of the page confirms that the proposal is ready to submit.
 */
$proposal         = unserialize($_SESSION[proposal]);
$submitter_email  = $proposal->submitter_email;
$submitter_name   = $proposal->submitter_name;
$discipline       = $proposal->discipline;
$course_number    = $proposal->course_number;
$proposal_id      = $proposal->id;
$guid             = $proposal->guid;
$agency_name      = $agency_names[$proposal->agency_id];
$base_dir         = basename(dirname(getcwd()));
$view_url         = "https://senate.qc.cuny.edu/$base_dir/Proposals?id=$proposal_id";
$submit_url =
  "https://senate.qc.cuny.edu/$base_dir/Proposal_Manager/submit_proposal.php?token=$guid";

//  To be determined:
$email_sender     = 'Academic Senate';
$designation = $proposal_type_id2name[$proposal->type_id];

$introductory_text = <<<EOD
You, or someone else using this Queens College email account on your behalf, created a curriculum proposal, ID #$proposal_id, for $discipline $course_number to be approved for the $designation designation. If this is not the case, you may ignore this message. But if you do want to submit it, you must now click the “Submit” link below. If you do nothing, the proposal will simply disappear after a while.
EOD;

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
$num_CCs = count($copies_to);
if ($num_CCs > 0)
{
  $txt = ($num_CCs === 1) ? 'a copy' : 'copies';
  $copies_text =
    'When you submit the proposal, ' .
    and_list($copies_to)                                .
    " will also receive $txt of it.";
}

if ($form_name === do_it)
{
  //  Update the opened date. The old and new catalog info and justifications should
  //  already have been saved.
  $cur_catalog    = $_SESSION[cur_catalog];
  $new_catalog    = $_SESSION[new_catalog];
  $justifications = serialize($proposal->justifications);
  $query = <<<EOD
UPDATE proposals
   SET opened_date    = now(),
       cur_catalog    = '$cur_catalog',
       new_catalog    = '$new_catalog',
       justifications = '$justifications'
 WHERE guid           = '{$proposal->guid}'
EOD;

  $result = pg_query($curric_db, $query) or die('Failed to update proposal: '
      . basename(__FILE__) . ' ' . __LINE__);

  //  Send the email: both text-only and MIME (HTML) formats
  //  -------------------------------------------------------------------------

  //  text-only version
  $text_msg = <<<EOD

$submitter_name:

$introductory_text

To submit the proposal now, use this link: $submit_url

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
  <h3><a href='$submit_url'>Submit the Proposal</a></h3>
  <p>You can also <a href='$view_url'>view or print the proposal</a>.</p>
  <p>
    $copies_text
  </p>
  <p>
    Thank you!
    <br />$email_sender
  </p>

EOD;
  $mail = new Senate_Mail('QC Curriculum<do-not-reply@qc.cuny.edu>', $submitter_email,
    "Verifying your course proposal for $discipline $course_number",
     $text_msg, $html_msg);
  $mail->send() or die( $mail->getMessage() .
      " <a href='.'>try again</a> or report the problem to $webmaster_email");

  //  redirect to the congratulations page
  $goto = str_replace(basename($_SERVER['SCRIPT_FILENAME']),
                      'sent_verification.php', $_SERVER['REQUEST_URI']);
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
    <title>Review Designation Proposal</title>
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
      $discipline $course_number for $designation Designation<br />
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

  //  Get current catalog information
  $cur_catalog = unserialize($_SESSION['cur_catalog']);
  $catalog_info = <<<EOD
    <h2 class='error'>Catalog Description for $discipline $course_number</h2>
    <p class='error'>
      $discipline $course_number is not in the CUNYfirst course catalog.  Be sure to
      submit a separate proposal either to create it or to fix the course catalog.
    </p>

EOD;
  if ($cur_catalog->course_id !== 0)
  {
    $catalog_info = <<<EOD
    <h2>Catalog Description for $discipline $course_number</h2>
    {$cur_catalog->toHTML()}

EOD;
  }

  //  Get current syllabus list
  $syllabus_pathnames = get_syllabi("$discipline $course_number");
  $syllabus_info = <<<EOD
  <h2 class='error'>
    Warning: No syllabus for $discipline $course_number has been uploaded yet.
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
      $file_size      = number_format(filesize($pathname));
      $syllabus_info .= "    <li><a href='$pathname'>$date_str ($file_size bytes)</a></li>\n";
    }
    $syllabus_info .= "  </ul>\n";
  }
  echo <<<EOD
    $catalog_info
    $syllabus_info
    <h2>Justifications for Designation of $discipline $course_number as $designation</h2>
    <p>
      This is an abbreviated summary of the justifications you are submitting.
    </p>
    <table class='selection-table'>
      <tr>
        <th>Criterion</th><th>Justification</th>
      </tr>

EOD;

  //  Make sure the proper number of justifications are given for each
  //  criteria group!
  $num_justs  = 0;
  foreach ($proposal->justifications as $criterion => $text)
  {
    $text_str = trim($text);
    if ($text_str === '')
    {
      $text_str = '[None]';
    }
    else
    {
      if (substr($criterion, 0, 4) !== 'NOTE') $num_justs++;
    }
    if (strlen($text_str) > 70)
    {
      $text_str = mb_substr($text_str, 0, 66, 'utf-8') . '...';
    }
    echo "<tr><td>$criterion</td><td>$text_str</td></tr>\n";
  }
  echo "</table>\n";
  if ($num_justs < $proposal->num_justifications_needed)
  {
    $how_many = 'none';
    if ($num_justs > 0) $how_many = "only $num_justs";
    echo <<<EOD
    <h1 class='error'>Error: Unable To Submit</h1>
    <p>
      $designation proposals require $proposal->num_justifications_needed justifications,
      but you have provided $how_many.
    </p>

EOD;
  }
  else
  {
    //  Check if too many (warning only)
    if ($num_justs > $proposal->num_justifications_needed)
    {
      echo <<<EOD
    <h3 class='warning'>Warning: Extra Justifications</h3>
    <p>
      You have provided $num_justs justifications, but $designation proposals require only
      {$proposal->num_justifications_needed}. Review committees might ignore extra
      justifications, so be sure the first {$proposal->num_justifications_needed} are
      sufficient to make your case for the course. Superflous justifications will be
      submitted to the review committee, but there is no assurance they will be used.
    </p>

EOD;
    }

    //  Form for verifying the proposal
    //  ---------------------------------------------------------------------------------
    echo <<<EOD
<form method='post' action='{$_SERVER['SCRIPT_NAME']}'>
<fieldset><legend>Submit Proposal</legend>
  <input type='hidden' name='form-name' value='do-it' />
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

