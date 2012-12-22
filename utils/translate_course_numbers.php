<?php

require_once('credentials.inc');
  $db = gened_connect() or die("Unable to connect\n");
  //  Construct translations for all courses approved by GEAC.
	$query = "SELECT abbreviation, id, number, cf_number from disciplines, proposal_course_mappings" .
	" WHERE id = discipline_id ORDER BY name, number";
	//echo $query;die;
	$result = pg_query($db, $query);

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
    <title>Translate Course Numbers</title>
    <link rel="shortcut icon" href="../favicon.ico" />
    <script type="text/javascript" src="../../js/jquery-current.js"></script>
    <script type="text/javascript" src="scripts/translate_num.js"></script>
  </head>
  <body>
  <table>
    <th>Discipline</th><th>Quasar</th><th>CUNYfirst</th>
    <?php
		while ($row = pg_fetch_assoc($result))
		{
			$discp  = $row['abbreviation'];
			$qc_num = $row['number'];
			$cf_num = $row['cf_number'];
			echo "<tr><td>$discp</td><td>$qc_num</td><td>";
			echo <<<END_TEXT_INPUT
			<input type='text' name='$discp:$qc_num' value='$cf_num'/>
END_TEXT_INPUT;
			echo "</td><td></td></tr>\n";
		}
		?>
  </table>
  </body>
</html>