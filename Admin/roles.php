<?php  /* Admin/manage_roles.php */

set_include_path(get_include_path() . PATH_SEPARATOR . getcwd() . '/../scripts' );
require_once('init_session1.php');
require_once('login1.php');

  $can_view = false;
  $can_edit = false;
  if ($person && in_array('admin_change', $person->roles)) $can_edit = true;
  else if ($person && in_array('admin_view', $person->roles)) $can_view = true;
  if (! ($can_view || $can_edit))
  {
    //  No admin privileges: redirect to Curriculum home page
    $_SESSION['login_error_msg'] = 'Access denied';
    header("Location: $site_home_url");
    exit;
  }

//  Here beginnith the web page
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
    <title>Update Proposal Statuses</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/review_status.css" />
    <script type="application/javascript" src='js/jquery-1.8.3.min.js'></script>
    <script type="application/javascript" src='js/update_statuses.js'></script>
  </head>
  <body>
    <h1>Manage Roles</h1>
<?php
  echo $dump_if_testing;
  
    echo "    <table><tr><th>Email</th><th>View</th><th>Change</th></tr>\n";
    $query = <<<EOD
select * from person_roles

EOD;
    $result = pg_query($curric_db, 'select * from person_roles') or die(
        "    <h1 class='error'>Query failed:" . pg_last_error($curric_db) . ' in ' .
        basename(__FILE__) . ' at line ' . __LINE__ . "</h1></body></html>\n");
    while($row = pg_fetch_assoc($result))
    {
      $email = $row['email'];
      $view_role = $row['view'] === '1';
      $change_role = $row['change'] === '1';
      $view_checked = $view_role ? " checked='checked'" : "";
      $change_checked = $change_role ? " checked='checked'" : "";
      $view_value = $can_edit ? "<input type='checkbox'$view_checked />" :
        ($view_role ? 'x' : '');
      $change_value = $can_edit ? "<input type='checkbox'$change_checked />" :
        ($change_role ? 'x' : '');
      echo "      <tr><td>$email</td><td>$view_value</td><td>$change_value</td></tr>\n";
    }
    echo "    </table>\n";
?>
  </body>
</html>

