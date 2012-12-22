#! /usr/bin/php
<?php
  require_once('credentials.inc');
  require_once('../include/atoms.inc');
  require_once('classes.php');
  require_once('utils.php');
  $curric_db = curric_connect() or die("Unable to connect\n");
  $query = <<<EOD
   SELECT proposals.id,
          proposal_types.abbr,
          opened_date, submitted_date, saved_date, cur_catalog, new_catalog
     FROM proposals, proposal_types
    WHERE proposals.id > 160
      AND proposals.type_id = proposal_types.id
 ORDER BY saved_date DESC

EOD;
  $result = pg_query($curric_db, $query) or die("Query failed\n");
  while ($row = pg_fetch_assoc($result))
  {
    $cur_info = unserialize($row['cur_catalog']);
    $new_info = unserialize($row['new_catalog']);
    echo <<<EOD
Proposal #{$row['id']} {$row['abbr']}
  Opened:     {$row['opened_date']}
  Submitted:  {$row['submitted_date']}
  Saved:      {$row['saved_date']}
  Cur Info:   {$cur_info->cf_info_date}
  New Info:   {$new_info->cf_info_date}


EOD;
  }
  ?>
