#! /usr/bin/php
<?php
require_once('credentials.inc');
require_once('../include/atoms.inc');
require_once('classes.php');
require_once('utils.php');

//  no change
$qc_1   = 'QC-1';
$qc_2   = 'QC-2';
$ec_1   = 'EC-1';

//  old
$lang_1 = 'LANG-1';
$lang_2 = 'LANG-2';
$lang_3 = 'LANG-3';
$lang_4 = 'LANG-4';
$lang_5 = 'LANG-5';
$lang_6 = 'LANG-6';
$lang_7 = 'LANG-7';
$lang_8 = 'LANG-8';

//  new
$lang_a_1 = 'LANG_A-1';
$lang_a_2 = 'LANG_A-2';
$lang_a_3 = 'LANG_A-3';
$lang_b_1 = 'LANG_B-1';
$lang_b_2 = 'LANG_B-2';
$lang_b_3 = 'LANG_B-3';
$lang_b_4 = 'LANG_B-4';
$lang_b_5 = 'LANG_B-5';

//  no change
$note_1 = 'NOTE-1';
$note_2 = 'NOTE-2';
$note_3 = 'NOTE-3';

  pg_query($curric_db, 'BEGIN');
  $query = <<<EOD
SELECT id, justifications
  FROM proposals
 WHERE type_id = (SELECT id FROM proposal_types WHERE abbr = 'LANG')

EOD;

  $result = pg_query($curric_db, $query) or die('Query failed');
  while ($row = pg_fetch_assoc($result))
  {
  $id = $row['id'];
  echo "\nProposal $id:\n";
  $justifications = unserialize($row['justifications']);
  $new_justifications           = new StdClass();
  $new_justifications->$qc_1 = $justifications->$qc_1;
  $new_justifications->$qc_2 = $justifications->$qc_2;

  $new_justifications->$lang_a_1 = $justifications->$lang_1;
  $new_justifications->$lang_a_2 = $justifications->$lang_2;
  $new_justifications->$lang_a_3 = $justifications->$lang_3;
  $new_justifications->$lang_b_1 = $justifications->$lang_4;
  $new_justifications->$lang_b_2 = $justifications->$lang_5;
  $new_justifications->$lang_b_3 = $justifications->$lang_6;
  $new_justifications->$lang_b_4 = $justifications->$lang_7;
  $new_justifications->$lang_b_5 = $justifications->$lang_8;

  $new_justifications->$note_1  = $justifications->$note_1;
  $new_justifications->$note_2  = $justifications->$note_2;
  $new_justifications->$note_3  = $justifications->$note_3;
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

