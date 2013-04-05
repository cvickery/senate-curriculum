<?php
// include/login.inc

//  Constants used by the login module.
//  -------------------------------------------------------------------------------------

//  person
//  -------------------------------------------------------------------------------------
if (!defined('person')) define('person', 'person');

//  Forms
define('login_which_department',        'login-which-department');
define('login_form',                    'login-form');

//  login-which-departments fields
define('pending_person',                'pending-person');
define('dept_name',                     'dept-name');

//  login-form fields
define('qc_email',                      'qc-email');
define('password',                      'password');
define('new_password',                  'new_password');
define('repeat_new',                    'repeat_new');

//  Error Handling
//  -------------------------------------------------------------------------------------
//  When restricted area redirects to site index
define('login_error_msg',               'login-error-msg'); // $_SESSION index
define('access_denied',                 'Access denied');

//  Within login module
define('bad_email',                     'Unrecognized email address');
define('bad_pass',                      'Unrecognized email/password');
define('no_dept',                       'Unknown email or department affiliation');
