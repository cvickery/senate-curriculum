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

//  Logged in person, for non-login pages
define('person', 'person');
define('pending_person', 'pending-person');

//  Course->toHTML optional arguments
  define('with_approvals',         true);
  define('without_approvals',      false);
  define('with_radio',             true);
  define('without_radio',          false);

//  Form name index
define('form_name',             'form-name');

//  Catalog information (class Proposal)
define('course_title',          'course_title');
define('components',            'components');
define('credits',               'credits');
define('prerequisites',         'prerequisites');
define('catalog_description',   'catalog_description');
define('designation',           'designation');
define('justifications',        'justifications');

//  Proposal ID ranges
define('MAX_PLAS_ID',           '160');

?>
