<?php

//  Delete proposal via Ajax

function sanitize($str)
{
  $returnVal = trim($str);
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

require_once('credentials.inc');
$curric_db = curric_connect() or die('Unable to access curriculum db');

$id        = sanitize($_POST['proposal_id']);

//  Proposals can only be deleted if they haven't been submitted and if there are no
//  reviewers assigned to them. (Belt and suspenders: reviews should not be created until
//  the proposal has been submitted, but that's not enforced.)

$result = pg_query($curric_db, "SELECT * FROM reviews WHERE proposal_id = $id")
    or die('Query failed: ' . pg_last_error($curric_db));
if (0 !== pg_num_rows($result))
{
  echo "Unable to delete a proposal that has been assigned reviewers.\n";
}


$result = pg_query($curric_db, "SELECT proposal_id FROM reviews WHERE proposal_id = $id")
    or die('Query failed: ' . pg_last_error($curric_db));
if (0 !== pg_num_rows($result))
{
  echo "Unable to delete a proposal that has been assigned reviewers.\n";
}



$result    = pg_query($curric_db, "DELETE FROM proposals WHERE id = $id")
    or die('Query failed: ' . pg_last_error($curric_db));
$num = pg_affected_rows($result);
if (0 === $num) echo "fail";
else echo "success";

?>
