<?php
// include/login.inc

//  Constants used by the login module.
//  -------------------------------------------------------------------------------------

//  Forms
define('login_which_department',        'login-which-department');
define('login_form',                    'login-form');

//  login-which-departments fields
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

//  Error messages within login module
define('blank_email',                   'Missing email address');
define('bad_email',                     'Unrecognized email address');
define('bad_pass',                      'Unrecognized email/password');
define('no_dept',                       'Unknown email or department affiliation');
define('new_repeat_mismatch',           'New and repeated passwords don’t match');
define('password_changed',              'Password changed');

//  $_SESSION keys
//  -------------------------------------------------------------------------------------
define('saved_referer_uri',             'SAVED_REFERER_URI');
