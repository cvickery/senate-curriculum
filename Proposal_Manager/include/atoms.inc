<?php

//  Course string regular expression
//  ======================================================================================
/*
 *  The discipline code must be 2-6 letters.
 *
 *  The course number must be 1-4 digits after any leading zeros are dropped, followed by
 *  the letter H (Honors College) or W (Writing Intensive).
 *
 *  Note that this definition of a course number will need to be augmented if additional
 *  letter suffixes are introduced.
 *
 *  Spaces and a hyphen between the discipline and course number are ignored, as are
 *  leading zeros in the course number.
 *
 *  (Aside: the CUNYfirst definition of a course number is /^0*\d{0,4}[\da-z]{0,6}$/i
 *  provided the length of the resulting match is > 0. That is, up to four digits followed
 *  by up to six alphanumerics, with either part optional. The regular expression used
 *  here accepts is a proper subset of that definition.)
 */
  define('course_str_re', '/^\s*([a-z]{2,6})\s*\-?\s*0*([1-9]\d{0,3}[wh]?)\s*$/i');

//  General string atoms
//  ======================================================================================
/*  If you are careful to use the following defines instead of the corresponding strings,
 *  you can get the compiler to tell you if the strings are spelled wrong.  Common theme:
 *  HTML and CSS (classes and ids) use hyphens; PHP everything else uses underscores.
 */

  //  Proposal Form names
  define('logout',                'logout');
  define('select_proposal',       'select-proposal');
  define('create_proposal',       'create-proposal');
  define('select_one_course',     'select-one-course');
  define('edit_course',           'edit-course');
  define('edit_designation',      'edit-designation');
  define('do_it',                 'do-it');
  define('syllabus_upload',       'syllabus-upload');

  //  Select proposal form data
  define('use_proposal',          'use_proposal');
  define('course_str',            'course_str');
  define('proposal_type',         'proposal_type');

  //  Error messages
  define('error_msg',             'error_msg');

  //  $_SESSION variables
  define('proposal',              'proposal');
  define('cur_catalog',           'cur_catalog');
  define('new_catalog',           'new_catalog');
  define('course_array',          'course_array');
  define('course_index',          'course_index');

  //  Course->toHTML optional arguments
  define('with_approvals',         true);
  define('without_approvals',      false);
  define('with_radio',             true);
  define('without_radio',          false);

  //  Prompt value for proposal type selector
  define('select_proposal_type',  'Select the proposal type');

  define('course_proposal_type',  'course-proposal-type');
  define('proposal_type_radio',   'proposal-type-radio');
  define('new_course',            'new-course');
  define('revise_course',         'revise-course');
  define('fix_course',            'fix-course');

?>
