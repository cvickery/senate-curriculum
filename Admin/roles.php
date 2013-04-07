<?php  /* Admin/manage_roles.php */

set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() .  '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');
require_once('admin.inc');                       // Must be logged in as an administrator
$login_status = login_status();

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
    <script type="application/javascript" src='../js/jquery.min.js'></script>
    <script type="application/javascript" src='../js/site_ui.js'></script>
  </head>
  <body>
<?php
  //  Generate Status Bar and Page Content
  $nav_bar = site_nav();
  $admin_nav = admin_nav();
  echo <<<EOD
  <!-- Status Bar -->
  <div id='status-bar'>
    $instructions_button
    $login_status
    $nav_bar
    $admin_nav
  </div>
  <h1>Manage Roles</h1>
  $dump_if_testing
  <p>
    This is where you can view/change who can view or change people’s administrative
    access to the system.
  </p>
  <p>
    If you see checkboxes, that means you can make changes in the Admin section of the
    site. At this point, you can click on the checkboxes and the checkmarks will come and
    go. But nothing else will happen.
  </p>
  <p>
    If you see x’s, that means you have view-only access to the Admin section of the
    site. Scratching your left ear and clicking on the x’s will have the same effect on
    this page.
  </p>
  <p>
    Other than that, this page is good to go.
  </p>

EOD;

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

