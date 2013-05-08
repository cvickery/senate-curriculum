<?php
//  /include/admin.inc
//  Make sure the user is logged in as an administrator
//  Redirect to site index if not
  $can_view = false;
  $can_edit = false;
  if (isset($person))
  {
    if      (in_array('admin_change', $person->roles)) $can_edit = true;
    else if (in_array('admin_view',   $person->roles)) $can_view = true;
  }
  if (! ($can_view || $can_edit))
  {
    //  No admin privileges: redirect to Curriculum home page
    $_SESSION[login_error_msg] = 'Access denied';
    header("Location: $site_home_url");
    exit;
  }
?>
