<?php
//  download_csv.php
/*  Like download.csv, only it's for curric instead of gened.
 *  And it's, like, way betterer.
 */
session_start();
date_default_timezone_set('America/New_York');

$destination = date('Y-m-d');
$header_str = date('Y-m-d, g:i a');
if (isset($_SERVER['HTTP_REFERER']))
{
  $referer = parse_url($_SERVER['HTTP_REFERER']);
  if ($referer)
  {
    $path_info    = pathinfo($referer['path']);
    $destination .= '_' . $path_info['filename'];
    if (isset($referer['query']))
    {
      $query_part  = $referer['query'];
      $query_parts = split('=', $query_part);
      if (count($query_parts) === 2)
      {
        $header_str .= ', ' . ucfirst($query_parts[0]) . ' = ' . $query_parts[1];
      }
    }
  }
}
$destination .= '.csv';
header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Length: ". strlen($_SESSION['csv']).";");
header("Content-Disposition: attachment; filename=$destination");
header("Content-Type: text/csv; "); 
header("Content-Transfer-Encoding: UTF-8");
echo $header_str . "\r\n";
echo $_SESSION['csv'];

?>