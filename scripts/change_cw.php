#! /usr/bin/php
<?php
require_once('credentials.inc');
require_once('../include/atoms.inc');
require_once('classes.php');
require_once('utils.php');

$qc_1   = 'QC-1';
$qc_2   = 'QC-2';
$ec_1   = 'EC-1';
$ec_2   = 'EC-2';
$ec_3   = 'EC-3';
$ec_4   = 'EC-4';
$ec_5   = 'EC-5';
$cw2_1  = 'CW2-1';
$cw2_2  = 'CW2-2';
$cw2_3  = 'CW2-3';
$cw_1   = 'CW-1';
$cw_2   = 'CW-2';
$cw_3   = 'CW-3';
$note_1 = 'NOTE-1';
$note_2 = 'NOTE-2';
$note_3 = 'NOTE-3';
  pg_query($curric_db, 'BEGIN');
  $query = <<<EOD
SELECT id, justifications
  FROM proposals
 WHERE type_id = (SELECT id FROM proposal_types WHERE abbr = 'EC-2')

EOD;

  $result = pg_query($curric_db, $query) or die('Query failed');
  while ($row = pg_fetch_assoc($result))
  {
  $id = $row['id'];
  echo "\nProposal $id:\n";
  $justifications = unserialize($row['justifications']);
  $new_justifications           = new StdClass();
  if (isset($justifications[$qc_1]))
  {
    $new_justifications->$qc_1  = $justifications[$qc_1];
  }
  if (isset($justifications[$qc_2]))
  {
    $new_justifications->$qc_2  = $justifications[$qc_2];
  }
  $new_justifications->$ec_1    = $justifications[$ec_1];
  $new_justifications->$ec_2    = $justifications[$ec_2];
  $new_justifications->$ec_3    = $justifications[$ec_3];
  $new_justifications->$ec_4    = $justifications[$ec_4];
  $new_justifications->$ec_5    = $justifications[$ec_5];

  if (isset($justifications[$cw_1]))
  {
    $new_justifications->$cw2_1   = $justifications[$cw_1];
  }
  else if (isset($justifications[$cw2_1]))
  {
    $new_justifications->$cw2_1   = $justifications[$cw2_1];
  }
  else
  {
    $new_justifications->$cw2_1   = '';
  }

  if (isset($justifications[$cw_2]))
  {
    $new_justifications->$cw2_2   = $justifications[$cw_2];
  }
  else if (isset($justifications[$cw2_2]))
  {
    $new_justifications->$cw2_2   = $justifications[$cw2_2];
  }
  else
  {
    $new_justifications->$cw2_2   = '';
  }

  if (isset($justifications[$cw_3]))
  {
    $new_justifications->$cw2_3   = $justifications[$cw_3];
  }
  else if (isset($justifications[$cw2_3]))
  {
    $new_justifications->$cw2_3   = $justifications[$cw2_3];
  }
  else
  {
    $new_justifications->$cw2_3   = '';
  }

  $new_justifications->$note_1  = $justifications[$note_1];
  $new_justifications->$note_2  = $justifications[$note_2];
  $new_justifications->$note_3  = $justifications[$note_3];
  foreach ($new_justifications as $type => $text)
    echo "$type: '$text'\n";
  $justifications = serialize($new_justifications);
  $update_query = <<<EOD
UPDATE proposals SET justifications = '$justifications' WHERE id = $id

EOD;
  $update_result = pg_query($curric_db, $update_query) or die('Update failed');

  }

  pg_query($curric_db, 'COMMIT');
?>

