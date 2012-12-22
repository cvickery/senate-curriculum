<?php
//  get_disciplines.php
/*  Ajax resource for the list of valid disciplines
 */
require_once('credentials.inc');

//  sanitize()
//  ---------------------------------------------------------------------------
/*  Prepare a user-supplied string for inserting/updating a db table.
 *    Replace straight quotes with smart quotes
 *    Convert '<' and '&' to html entities
 *    Convert '--' to mdash
 */
  function sanitize($str)
  {
    $returnVal = $str;

    $returnVal = str_replace('--', '—', $returnVal);
    $returnVal = preg_replace('/(^|\s)"/', '$1“', $returnVal);
    $returnVal = str_replace('"', '”', $returnVal);
    $returnVal = preg_replace("/(^\s)'/", "$1‘", $returnVal);
    $returnVal = str_replace("'", "’", $returnVal);
    $returnVal = htmlspecialchars($returnVal, ENT_NOQUOTES, 'UTF-8');
    return $returnVal;
  }

//  Build array of disciplines
//  ---------------------------------------------------------------------------
/*  This is a list of currently active disipline codes and their names taken
 *  from the CUNYfirst catalog, to use as prompts for users selecting a course
 *  to work with.
 *
 *  This list is used only by JavaScript for the suggestion list.
 *
 */
$curric_db = curric_connect() or die('Unable to access curriculum database');

$query = <<<EOD
  SELECT discipline,
         discipline_full_name
    FROM discp_dept_div
ORDER By discipline
EOD;
$result = pg_query($curric_db, $query) or die('Unable to get disciplines');
$disciplines = array();
while ($row = pg_fetch_assoc($result))
{
  $discp = sanitize($row['discipline']);
  $disciplines[$discp] = sanitize($row['discipline_full_name']);
}
//  Return JSON representation of the array
echo json_encode($disciplines);

?>
