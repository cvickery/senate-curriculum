<?php
// Proposal_Manager/index.php
set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() . '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');
require_once('proposal_manager.inc');
require_once('mail_setup.php');

//  Check the email link was the origin
if ( !isset($_GET['token']) )
{
  $_SESSION[login_error_msg] = 'Invalid Access';
  header("Location: $site_home_url");
  exit;
}
$guid = sanitize($_GET['token']);

//  Here beginneth the web page
//  ================================================================================
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
    <title>Submit Proposal</title>
    <link rel="icon" href="../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/proposal_editor.css" />
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/site_ui.js"></script>
    <style type='text/css'>
    </style>
  </head>
  <body>
<?php
  $status_msg = login_status();
  $site_nav = site_nav();
  echo <<<EOD
    <div id='status-bar'>
      $instructions_button
      $status_msg
      $site_nav
    </div>
    <h1>Submit Proposal</h1>
    $dump_if_testing

EOD;

    //  Check information about this proposal
    $query = <<<EOD
   SELECT proposals.*,
          proposal_types.full_name            proposal_type,
          proposal_classes.full_name          proposal_class,
          agencies.full_name                    agency,
          cf_academic_organizations.abbr      dept,
          cf_academic_groups.abbr             div
     FROM proposals, agencies,
          cf_academic_organizations,
          cf_academic_groups,
          proposal_types,
          proposal_classes
    WHERE guid = '$guid'
      AND proposal_types.id            = proposals.type_id
      AND agencies.id                    = proposals.agency_id
      AND cf_academic_organizations.id = proposals.dept_id
      AND cf_academic_groups.id        = proposals.div_id
      AND proposal_classes.id = (
           SELECT class_id FROM proposal_types
            WHERE id = proposals.type_id)

EOD;

    $result = pg_query($curric_db, $query) or die('Unable to query database');
    $num_proposals = pg_num_rows($result);
    if (1 !== pg_num_rows($result))
    {
      echo <<<EOD
      <h1 class='error'>Nothing to Submit</h1>
      <p>
        The proposal you attempted to submit does not exist. Either you deleted it before
        clicking the verification link emailed to you, or there is a problem with the
        system.
      </p>
      <p>
        In the latter case, please let $webmaster_email know about the problem.
      </p>

EOD;
    }
    else
    {
      //  Extract info about the propsoal being (re-)submitted
      $proposal_row       = pg_fetch_assoc($result);
      $proposal_id        = $proposal_row['id'];
      $justifications     = $proposal_row['justifications'];
      $dept_approval_date = $proposal_row['dept_approval_date'];
      $dept_approval_name = $proposal_row['dept_approval_name'];
      $submitter_email    = $proposal_row['submitter_email'];
      $submitter_name     = $proposal_row['submitter_name'];
      $new_catalog        = $proposal_row['new_catalog'];
      $submitted_date     = trim($proposal_row['submitted_date']);
      $discipline         = $proposal_row['discipline'];
      $course_number      = $proposal_row['course_number'];
      $agency              = $proposal_row['agency'];
      $proposal_type      = $proposal_row['proposal_type'];
      $proposal_class     = $proposal_row['proposal_class'];
      $dept_abbr          = $proposal_row['dept'];

      $proposal_link      = "https://senate.qc.cuny.edu/Curriculum/Proposals/?id=$proposal_id";

      //  Generate list of emails to notify
      $notify_list        = array();
      $chair_name         = $chairs_by_abbr[$dept_abbr]->name;
      $chair_email        = $chairs_by_abbr[$dept_abbr]->email;
      $div_abbr           = $proposal_row['div'];
      $dean_name          = $deans_by_abbr[$div_abbr]->name;
      $dean_email         = $deans_by_abbr[$div_abbr]->email;
      if (trim($chair_email) !== '')
      {
        $notify_list[] = "Chair $chair_name ($chair_email)";
      }
      if ($dean_email !== '')
      {
        $notify_list[] = "Dean $dean_name ($dean_email)";
      }
      $notify_text = '';
      switch (count($notify_list))
      {
        case 0:
          break;
        case 1:
          $notify_text = $notify_list[0];
          break;
        case 2:
          $notify_text = $notify_list[0] . " and " . $notify_list[1];
          break;
        default:
          die('Bad switch: ' . basename(__FILE__) . ', line ' . __LINE__);
      }

      //  Check previous submissions
      //  -------------------------------------------------------------------------------
      /*  Look for, and display, deltas from previous submission.
       *  Actual submission depends on there either being no previous version or some
       *  differences. (You can't submit the same thing twice in a row.)
       */
      $ok_to_submit = true;
      $query = <<<EOD
   BEGIN;
  SELECT * FROM proposal_histories
   WHERE guid = '$guid'
     AND submitted_date = (SELECT max(submitted_date) FROM proposal_histories
                            WHERE guid='$guid')

EOD;
      $result = pg_query($curric_db, $query) or die("History query failed: " .
            $curric_db->last_error());
      $num = pg_num_rows($result);
      if ($num > 1)
      {
        die("<h1 class='error'>Error: History query returned " .
            "$num “most-recent” submissions</h1></body></html>\n");
      }
      if ($num < 1)
      {
        //  No previous submissions
        $transaction_type = 'submitted';
        $action_name      = 'Submit';
        echo <<<EOD
    <h1>
      Submitting Proposal #<a href='../Proposals/?id=$proposal_id'>$proposal_id</a>
      <br/>$proposal_type for $discipline $course_number
    </h1>

EOD;

      }
      else
      {
        //  There is a previously submitted version
        $transaction_type = 'resubmitted';
        $action_name      = 'Resubmit';
        echo <<<EOD
    <h1>
      Resubmitting Proposal #<a href='../Proposals/?id=$proposal_id'>$proposal_id</a>
      <br/>$proposal_type for $discipline $course_number
    </h1>

EOD;
        //  Check for deltas
        /*  Things that might have changed:
         *    justifications
         *    dept approval
         *    submitter (not quite sure how, yet...)
         *    new_catalog
         */
        //  Get previous version and check what's changed
        $ok_to_submit = false;
        $history_row = pg_fetch_assoc($result);
        $last_submit_date = new DateTime($history_row['submitted_date']);
        $last_submit_str  = $last_submit_date->format('F j, Y \a\t g:i a');
        if ($history_row['justifications'] !== $justifications)
        {
          echo "<h2>Justifications changed</h2>\n";
          $hist_justs = unserialize($history_row['justifications']);
          $prop_justs = unserialize($justifications);
          //  Go through all justifications in the proposal and show diffs with old (which
          //  might not exist).
          foreach ($prop_justs as $type => $text)
          {
            $type_text = $criteria_text[$type];
            if ( isset($hist_justs->$type) &&
                 $hist_justs->$type === $text)
            {
              //  not changed
              echo "<!-- $type not changed -->\n";
              continue;
            }
            $from = "No $type";
            if (isset($hist_justs->$type))
            {
              $from = $hist_justs->$type;
            }
            echo <<<EOD
    <h3>$type_text</h3>
    <div>
      <p><strong>From:</strong> <del>$from</del></p>
      <p><strong>To:</strong> <ins>$text</ins></p>
    </div>

EOD;
          }
          $ok_to_submit = true;
        }

        if ( ($history_row['dept_approval_date'] !== $dept_approval_date) ||
             ($history_row['dept_approval_name'] !== $dept_approval_name) )
        {
          $ok_to_submit = true;
          echo <<<EOD
    <h2>Dept Approval</h2>
    <p>
      <strong>From:</strong>
            <del>{$history_row['dept_approval_name']} on
                 {$history_row['dept_approval_date']}}</del>
    </p>
    <p>
      <strong>To:</strong> <ins>$dept_approval_name on $dept_approval_date</ins>
    </p>

EOD;
        }

        if ( ($history_row['submitter_email'] !== $submitter_email) ||
             ($history_row['submitter_name'] !== $submitter_name) )
        {
          echo "<h2>Submitting Person</h2>\n";
          $ok_to_submit = true;
          echo <<<EOD
    <p>
      <strong>From:</strong>
            <del>{$history_row['$submitter_name']}
                ({$history_row['$submitter_email']})</del>
    </p>
    <p>
      <strong>To:</strong> <ins>$submitter_name {$submitter_email}</ins>
    </p>

EOD;
        }

        if ($history_row['new_catalog'] !== $new_catalog)
        {
          echo "<h2>Catalog Data</h2>\n<p>See proposal for details.</p>\n";
          $ok_to_submit = true;
        }
      }
    }

    //  There is a proposal: submit it or not
    //  ---------------------------------------------------------------------------------
    if ($ok_to_submit)
    {
      //  Submit the proposal
      $remote_ip = 'Unkown';
      if (isset($_SERVER['REMOTE_ADDR']))
      {
        $remote_ip = $_SERVER['REMOTE_ADDR'];
      }
      $query = <<<EOD
  -- Create History Record
  INSERT INTO proposal_histories VALUES (
             {$proposal_row['id']},
            '{$proposal_row['guid']}',
             {$proposal_row['type_id']},
            '{$proposal_row['dept_approval_date']}',
            '{$proposal_row['dept_approval_name']}',
            '{$proposal_row['opened_date']}',
            '{$proposal_row['saved_date']}',
             now(),
            '{$proposal_row['submitter_name']}',
            '{$proposal_row['submitter_email']}',
             {$proposal_row['dept_id']},
             {$proposal_row['div_id']},
            '{$proposal_row['cur_catalog']}',
            '{$proposal_row['new_catalog']}',
            '{$proposal_row['justifications']}');

  -- Update Proposal
    UPDATE proposals
       SET submitted_date = now()
     WHERE guid = '$guid';

  -- Create Event
    INSERT INTO events
    VALUES
    (
                default,                             -- id
                to_char(now(), 'YYYY-MM-DD')::date,  -- event_date
                (SELECT agency_id
                 FROM   proposals
                 WHERE  id = $proposal_id),           -- agency_id
                (SELECT id
                 FROM   actions
                 WHERE  full_name = '$action_name'),  -- action_id
                $proposal_id,                         -- proposal_id
                '$discipline',                        -- discipline
                '$course_number',                     -- course_number
                default,                              -- annotation
                'Internal',                           -- entered_by
                '$remote_ip',                         -- entered_from
                now()                                 --  entered_at
    );
EOD;
      //  echo "<pre>$query</pre>\n";
      $result = pg_query($curric_db, $query)
          or die("<h2 class='error'>Submission failed: " . pg_last_error($curric_db) .
              "</h2><p>Please report this error to $webmaster_email</p></body></html>");
      $n = pg_affected_rows($result);
      if (1 === $n)
      {
        //  Successful submission. Exclaim excitement to the user!
        pg_query($curric_db, "COMMIT");

        //  And notify interested parties
        $mail_text = <<<EOD

  $submitter_name:

  Your $proposal_type proposal, ID #$proposal_id, for $discipline $course_number
  has been $transaction_type to the $agency. You can track the progress of the proposal at
  $proposal_link.

  Thank you,
  Academic Senate

EOD;
        $mail_html = <<<EOD

  <p>$submitter_name:</p>

  <p>
    Your <a href='$proposal_link'>$proposal_type proposal for $discipline $course_number
    (ID #$proposal_id)</a> has been $transaction_type to the $agency. You can track the
    progress of the proposal at $proposal_link.
  </p>
  <p>
    Thank you,
  </p>
  <p>
    Academic Senate
  </p>

EOD;
        $mail = new Senate_Mail("Academic Senate<Senate@qc.cuny.edu>",
          $submitter_email,
          "$discipline $course_number Proposal #$proposal_id $transaction_type",
          $mail_text, $mail_html);
        $mail->add_cc('Alicia Alvero<Alicia.Alvero@qc.cuny.edu>')
        $mail->add_cc('Academic Senate<Senate@qc.cuny.edu>')
        $mail->add_bcc('cvickery@qc.cuny.edu');
        $mail->send() or die( $mail->getMessage() .
            " Please report the problem to $webmaster_email");

        echo <<<EOD
      <h2>Success!</h2>
      <p>
        Your proposal for $discipline $course_number has been $transaction_type and will be
        forwarded to the $agency.
      </p>
      <p>
        If you have any questions about this proposal, you may contact Associate Provost Alicia
        Alvero or the Senate Administrative Coordinator, Brenda Salas, who are copied on this email.
      </p>
EOD;
/*

        if ($notify_text)
        {
          echo "<p>A copy will also be forwarded to $notify_text.</p>";
          echo <<<EOD
    <p class='warning'>
      Actually, that’s not quite true. The proposal has indeed been $transaction_type, for
      approval, and
      <a href='../Proposals/?id=$proposal_id'><em>anyone</em> can look at it</a>. Also, a final
      confirmation has been sent to you for your records, and a blind copy has been sent to
      Christopher Vickery (cvickery@qc.cuny.edu) for verification.
      <em>But no email notifications will be sent to your chair or dean at this time.</em>
    </p>

EOD;
        }
*/
      }
      else
      {
        //  Report propblem if zero or multiple proposals saved
        pg_query($curric_db, "ROLLBACK");
        $date_str = date('r');
        echo <<<EOD
    <h1>Submission Failed</h1>
    <p>Please report the problem to $webmaster_email, with the time of the error
    ($date_str) and the error message, “Attempt to submit $n proposals.”
    </p>

EOD;
      }
    }
    else
    {
      echo <<<EOD
  <h2 class='warning'>No Changes To Submit</h2>
  <p>
    The proposal has not changed since it was last submitted on $last_submit_str, so
    there is nothing new to submit.
  </p>

EOD;

    }
    echo <<<EOD
    <h2><a href='https://senate.qc.cuny.edu/$site_home_url/Proposal_Manager/'>
      Manage Proposals</a>
    </h2>

EOD;
  ?>
  </body>
</html>

