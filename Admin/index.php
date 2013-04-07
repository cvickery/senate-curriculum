<?php  /* Admin/index.php */

set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() .  '/../scripts' 
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session1.php');
require_once('admin.inc');  // Must be logged in as an administrator

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
    <title>Event Editor</title>
    <link rel="icon" href="../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/admin.css" />
  </head>
  <body>
<?php
    $last_login = 'First login';
    if ($person->last_login_time)
    {
      $last_login   = "Last login at ";
      $last_login  .= $person->last_login_time . ' from ' . $person->last_login_ip;
    }

    $status_text = sanitize($person->name) . ' / ' . sanitize($person->dept_name);
    $status_action = <<<EOD

    <form id='logout-form' action='.' method='post'>
      <input type='hidden' name='form-name' value='logout' />
      <button type='submit'>Sign Out</button>
    </form>
    <div>$last_login</div>

EOD;

    $nav_bar = site_nav();
    $admin_nav = admin_nav();
    echo <<<EOD
    <!-- Status Bar -->
    <div id='status-bar'>
      <div id='status-msg'>
        $status_text
        $status_action
      </div>
      $nav_bar
      $admin_nav
    </div>
    <h1>Administration</h1>
    $dump_if_testing
    <p>
      Live long and prosper.
    </p>
    <p>
      <em>You may also select one of the links above.</em>
    </p>

EOD;

?>
  </body>
</html>
