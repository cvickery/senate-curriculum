<?php
//  scripts/nav_functions.php
/*  Functions to generate navigation bars.
 */

//  Menu Item definitions
//  -------------------------------------------------------------------------------------
/*  Labels for directories. You can change the labels on the buttons without breaking
 *  the links. The constant is the label name, and the value is the text for the label.
 */
  //  site-wide
  //  ------------------------------------------------------
  define('Proposals_label',           'Track Proposals');
  define('Proposal_Guidelines_label', 'Guidelines');
  define('Proposal_Manager_label',    'Manage Proposals');
  define('Syllabi_label',             'Syllabi');
  define('Reviews_label',             'GEAC Reviews');
  define('Review_Editor_label',       'Edit Reviews');
  define('Admin_label',               'Admin');

  //  Admin
  //  ------------------------------------------------------
  define('event_editor_label',      'Event Editor');
  define('review_status_label',     'Review Status');
  define('proposal_status_label',   'Update Statuses');
  define('need_revision_label',     'Pending Revision');
  define('roles_label',             'Manage Roles');

//  site_nav()
//  -------------------------------------------------------------------------------------
/*  The set of top-level links depends on the user's login state, and whether s/he is a
 *  proposal reviewer with reviews to do or not.
 */
  function site_nav()
  {
    global $site_home_url, $home_dir, $person;

    $return_val = "<nav>\n";

    //  The "current page" is either the index page within a directory or the current
    //  file.
        $script_filename  = $_SERVER['SCRIPT_FILENAME'];
        $script_dirname   = basename(dirname($script_filename));
        $script_filename  = basename($script_filename);

    //  Map labels to directory URLs
    $link_info  = array(
        Proposals_label           =>  "$site_home_url/Proposals",
        Proposal_Guidelines_label =>  "$site_home_url/Proposal_Guidelines",
        Proposal_Manager_label    =>  "$site_home_url/Proposal_Manager",
        Syllabi_label             =>  "$site_home_url/Syllabi",
        Reviews_label             =>  "$site_home_url/Reviews",
        Review_Editor_label       =>  "$site_home_url/Review_Editor",
        Admin_label               =>  "$site_home_url/Admin",
        );

    //  Filter out the ones that don't apply to this user
    //  -------------------------------------------------
    if (!(isset($person) && $person))
    {
      //  Person is not logged in
      unset($link_info[Proposal_Manager_label]);
      unset($link_info[Review_Editor_label]);
      unset($link_info[Reviews_label]);
      unset($link_info[Admin_label]);
    }
    else
    {
      //  Person is logged in:
      //    check for reviews to edit
      if (! $person->has_reviews)
      {
        unset($link_info[Review_Editor_label]);
      }
      //    chedk for administrator status
      //
      if ( !in_array('admin_view', $person->roles) )
      {
        unset($link_info[Admin_label]);
      }
    }
    foreach ($link_info as $name => $url)
    {
      $current_page = "";
      if ( basename($url) === $script_filename || basename($url) === $script_dirname )
      {
        $current_page = " class='current-page'";
      }
      $return_val .= "  <a href='$url'$current_page>$name</a>\n";
    }
    return $return_val . "</nav>\n";
  }

//  admin_nav()
//  -------------------------------------------------------------------------------------
/*  The <nav> within the Admin directory
 */
  function admin_nav()
  {
    global $site_home_url;
    $this_file  = basename($_SERVER['PHP_SELF']);
    $return_val = "<nav>\n";

    //  Full list of possible links
    $link_info  = array(
        proposal_status_label =>  "$site_home_url/Admin/update_statuses.php",
        event_editor_label    =>  "$site_home_url/Admin/event_editor.php",
        need_revision_label   =>  "$site_home_url/Admin/need_revision.php",
        review_status_label   =>  "$site_home_url/Admin/review_status.php",
        roles_label           =>  "$site_home_url/Admin/roles.php",
        );
    foreach($link_info as $label => $file)
    {
      $current_page = "";
      if ($file === $this_file) $current_page = " class='current-page'";
      $return_val .= "<a href='$file'$current_page>$label</a>\n";
    }
    return $return_val . "</nav>\n";
  }

?>
