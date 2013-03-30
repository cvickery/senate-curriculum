<?php
//  nav_functions.php
/*  Functions to generate navigation bars.
 */

//  Menu Item definitions
//  -------------------------------------------------------------------------------------
/*  Label for directories. You can change the labels on the buttons without breaking
 *  the links. The constant is the directory name, and the value is the label.
 */
  //  site-wide
  //  ------------------------------------------------------
  define('Proposals_label',         'Track Proposals');
  define('Model_Proposals_label',   'Guidelines');
  define('Proposal_Manager_label',  'Manage Proposals');
  define('Syllabi_label',           'Syllabi');
  define('Reviews_label',           'GEAC Reviews');
  define('Review_Editor_label',     'Edit Reviews');
  define('Admin_label',             'Admin');

  //  Admin
  //  ------------------------------------------------------
  define('event_editor_label',      'Event Editor');
  define('review_status_label',     'GEAC Review Status');
  define('proposal_status_label',   'Update Statuses');
  define('need_revision_label',     'Pending Revision');

//  site_nav()
//  -------------------------------------------------------------------------------------
/*  The set of top-level links depends on the user's login state, and whether s/he is a
 *  proposal reviewer with reviews to do or not.
 */
  function site_nav()
  {
    $return_val = "<nav>\n";
    $cwd        = basename(getcwd());
    $is_index   = strstr($_SERVER['PHP_SELF'], 'index.');

    //  Full list of possible links
    $link_info  = array(
        Proposals_label         =>  'Proposals',
        Model_Proposals_label   =>  'Model_Proposals',
        Proposal_Manager_label  =>  'Proposal_Manager',
        Syllabi_label           =>  'Syllabi',
        Reviews_label           =>  'Reviews',
        Review_Editor_label     =>  'Review_Editor',
        Admin_label             =>  'Admin',
        );

    //  Filter out the ones that don't apply to this user
    //  -------------------------------------------------
    if (!(isset($person) && $person))
    {
      //  Person is not logged in
      unset($link_info['Manage Proposals']);
      unset($link_info['Edit Reviews']);
      unset($link_info['Admin']);
    }
    else
    {
      //  Person is logged in:
      //    check for reviews to edit
      if (! $person->has_reviews)
      {
        unset($link_info['Edit Reviews']);
      }
      //    chedk for administrator status
      //
      if (! $person->is_administrator)
      {
        unset($link_info['Admin']);
      }
    }
    foreach ($link_info as $name => $dir)
    {
      $current_page = "";
      if ($dir === $cwd && $is_index) $current_page = " class='current-page'";
      $return_val .= "  <a href='../$dir$current_page'>$name</a>\n";
    }
    return $return_val . "</nav>\n";
  }

//  admin_nav()
//  -------------------------------------------------------------------------------------
/*  The <nav> within the Admin directory
 */
  function admin_nav()
  {
    $this_file  = basename($_SERVER['PHP_SELF']);
    $return_val = "<nav>\n";

    //  Full list of possible links
    $link_info  = array(
        proposal_status_label =>  'update_statuses.php',
        event_editor_label    =>  'event_editor.php',
        need_revision_label   =>  'need_revision.php',
        review_status_label   =>  'review_status.php',
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
