<?php  /* Proposal/sent_verification.php */

set_include_path(get_include_path() . PATH_SEPARATOR . '../scripts' );
require_once('init_session.php');

require_once('include/atoms.inc');

if ( ! (isset($_SESSION[session_state]) && $_SESSION[session_state] === ss_is_logged_in))
{
  //  Attempt to access this page while not logged in.
  die('Confguration error ' . __LINE__);
}
$person = unserialize($_SESSION['person']);
$email = $person->email;
$pwd = basename(getcwd());

//  At this point, the user's session loses the current proposal
unset($_SESSION[proposal]);

//  Here beginneth the web page
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
    <title>Sent Verification Link</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/proposal_editor.css" />
  </head>
  <body>
    <h1>Check Your Email</h1>
    <?php
  //  Handle the logging in/out situation here
  $last_login       = '';
  $status_msg       = 'Not signed in';
  $person           = '';
  $sign_out_button  = '';
  $review_link      = '';
  require_once('short-circuit.php');
  require_once('login.php');
  if (isset($_SESSION[session_state]) && $_SESSION[session_state] === ss_is_logged_in)
  {
    if (isset($_SESSION[person]))
    {
      $person = unserialize($_SESSION[person]);
      if ($person->has_reviews)
      {
        $review_link = "<a href='../Review_Editor'>Edit Reviews</a>\n";
      }
    }
    else
    {
      die("<h1 class='error'>Review Course Proposal: " .
          "Invalid login state</h1></body></html>");
    }

    $status_msg = sanitize($person->name) . ' / ' .
                  sanitize($person->dept_name);
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

    echo <<<EOD
    <p>
      A message containing a link for submitting your proposal has been sent to <em>$email</em>.
    </p>
    <p>
      The proposal will not be acted upon until you click on the link in that message. The link
      will remain active for a month.
    </p>
EOD;

    //  Status Bar
    echo <<<EOD
    <div id='status-bar'>
      $sign_out_button
      <div id='status-msg' title='$last_login'>
        $status_msg
      </div>
      <!-- Navigation -->
      <nav>
        <a href='../Proposals'>Browse Proposals</a>
        <a href='../Model_Proposals'>Model Proposals</a>
        <a href='.' class='current-page'>Proposal Editor</a>
        <a href='../Syllabi'>Browse Syllabi</a>
        <a href='../Reviews'>Proposal Reviews</a>
        $review_link
      </nav>
    </div>
    <h2><a href='.'>Return to Proposal Editor</a></h2>

EOD;
    ?>
  </body>
</html>
