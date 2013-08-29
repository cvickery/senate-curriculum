<?php //Request_RD_Change/submit-request.php
require_once('credentials.inc');
$curric_db = curric_connect();
//  sanitize()
//  ---------------------------------------------------------------------------
/*  Prepare a user-supplied string for inserting/updating a db table.
 *    Force all line endings to Unix-style.
 *    Replace straight quotes, apos, and quot with smart quotes
 *    Convert '<' and '&' to html entities without destroying existing entities
 *    Convert '--' to mdash
 */
  function sanitize($str)
  {
    $returnVal = trim($str);
    //  Convert \r\n to \n, then \r to \n
    $returnVal = str_replace("\r\n", "\n", $returnVal);
    $returnVal = str_replace("\r", "\n", $returnVal);
    //  Convert exisiting html entities to characters
    $returnVal = str_replace('&amp;', '&', $returnVal);
    $returnVal = str_replace('--', '—', $returnVal);
    $returnVal = preg_replace('/(^|\s)"/', '$1“', $returnVal);
    $returnVal = str_replace('"', '”', $returnVal);
    $returnVal = preg_replace("/(^\s)'/", "$1‘", $returnVal);
    $returnVal = str_replace("'", "’", $returnVal);
    $returnVal = htmlspecialchars($returnVal, ENT_NOQUOTES, 'UTF-8');
    return $returnVal;
  }

  $email    = isset($_POST['email'])    ? sanitize($_POST['email'])   : '';
  $course   = isset($_POST['course'])   ? sanitize($_POST['course'])  : '';
  $primary  = isset($_POST['primary'])  ? sanitize($_POST['primary']) : '';
  $new_rd   = isset($_POST['new-rd'])   ? sanitize($_POST['new-rd'])  : '';
  if (! ($email && $course && $primary && $new_rd))
  {
    die('<h1>Missing Information</h1>');
  }
  $email = strtolower($email . '@qc.cuny.edu');
  $guid = uniqid($email);

  //  Check if confirmation request has already been sent
  $repeat_note = '';
  $query = <<<EOD
select * from change_rd_requests
where lower(email) = '$email'
and course = '$course'
and new_rd = '$new_rd'

EOD;
  $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query failed</h1>");
  if ( 0 < pg_num_rows($result))
  {
    $row = pg_fetch_assoc($result);
    $guid = $row['guid'];
    $when_submitted = new DateTime($row['when_submitted']);
    $when_submitted = $when_submitted->format('F j, Y');
    $repeat_note = <<<EOD
<p>This is a repeat of an email that was sent to $email on $when_submitted.</p>

EOD;
//  TODO: need to check if registrar has already fized it or not; whether the person
//  has confirmed it yet or not.
  }
  $ack_para = <<<EOD
<p>
  An email has been sent to $email asking you to confirm that you want to
  change the requirement designation for $course from $primary to $new_rd.
</p>
$repeat_note
<p>
  You must click the link in that email message in order to verify that you
  actually own that email address and want to make this change to your academic
  record. Once you click the link, another email message will be sent to the
  Queens College Registrar’s office (<em>adrienne.bricker@hunter.qc.cuny.edu</em>) to
  submit the actual change request.
</p>
<p>
  <strong>
    Your request will not be processed until you respond to that email message.
  </strong>
</p>
EOD;

  $query = <<<EOD
insert into change_rd_requests values(
  '$guid',    --  token
  '$email',   --  email
  '$course',  --  course
  '$primary', --  primary_id
  '$new_rd',  --  new_rd
  now(),      --  submitted
  null,       --  confirmed
  '$host_ip'  --  submited_from
)

EOD;
  $result = pg_query($curric_db, $query)
            or die("<h1 class='error'>Query failed. Line " . __LINE__ . "</h1>");

//  Generate the page
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
    <title>Confirm RD Change</title>
    <link rel="stylesheet" type="text/css" href="request_rd_change.css" />
  </head>
  <body>
    <h1>Confirm Requirement Designation Change Request</h1>
    <?php echo $ack_para; ?>
  </body>
</html>
