<?php
//  get_disciplines.php
//  -----------------------------------------------------------------------------------
/*  Target of an ajax request: returns a json-encoded array of discipline codes and
 *  names.
 */
class Discipline
{
  public $code, $name;
  function __construct($code, $name)
  {
    $this->code = $code;
    $this->name = $name;
  }
}
$disciplines = array();
require_once('credentials.inc');
$db = curric_connect() or die('Failed to access db');
$query = "SELECT discipline as code, discipline_full_name as name FROM discp_dept_div";
$result = pg_query($db, $query) or die('Query failed');
while ($row = pg_fetch_assoc($result))
{
  $disciplines[] = new Discipline($row['code'], $row['name']);
}
echo json_encode($disciplines);
1?>
