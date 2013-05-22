<?php
//  /dev/college_option.php

set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() . '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');

//  Develop College Option calculator
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
    <title>College Option Calculator</title>
    <link rel="stylesheet" type="text/css" href="../css/curriculum.css" />
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/college_option.js"></script>
    <style type='text/css'>
      label, input {
        margin:0; padding:0.25em;
      }
      table td {
        border: 0;
        }
      td {
        padding:0.25em;
        }
      #other-cuny-courses { display:none; }
      #other-cuny-courses input {display:block; width:90%; margin:auto;)
    </style>
  </head>
  <body>

  <h1>College Option Calculator</h1>
  <form action='<?php echo $_SERVER['PHP_SELF'];?>' method='post'>
    <input  type='hidden'  
            name='form-name' 
            value='college-option' />
    <fieldset>
      <legend>Questions</legend>
      <p class='instructions'>
        Answer the following questions and see your College Option requirements at Queens
        College.
      </p>
        <table>
          <tr>
            <td>
              <input type='radio' id='other-cuny-y' name='other-cuny' value='y'/>
              <label for='other-cuny-y'>Yes</label>
            </td>
            <td>
              <input type='radio' id='other-cuny-n' name='other-cuny' value='n'
              checked='checked'/>
              <label for='other-cuny-n'>No</label>
            </td>
            <td>
              Have you received credit for College Option courses from another CUNY
              college?
            </td>
          </tr>
          <tr id='other-cuny-courses'>
            <td colspan='2'>
              <input  name='num-other-cuny-courses' id='num-other-cuny-courses'
                      type='number' min='0' max='4' step='1' />
            </td>
            <td>How many courses?</td>
          </tr>
          <tr>
            <td>
              <input type='radio' id='bachelor-degree-y' name='bachelor-degree' value='y'/>
              <label for='bachelor-deg-y'>Yes</label>
            </td>
            <td>
              <input type='radio' id='bachelor-degree-n' name='bachelor-degree' value='n'
              checked='checked'/>
              <label for='bachelor-degree-n'>No</label>
            </td>
            <td>
              Do you already have a Bachelor’s (BA, BS, or BFA) degree?
            </td>
          </tr>
          <tr>
            <td>
              <input type='radio' id='associate-degree-y' name='associate-degree' value='y'/>
              <label for='associate-deg-y'>Yes</label>
            </td>
            <td>
              <input type='radio' id='associate-degree-n' name='associate-degree' value='n'
              checked='checked'/>
              <label for='associate-degree-n'>No</label>
            </td>
            <td>
              Do you have an Associate’s (AA or AS) degree?
            </td>
          </tr>
            <td>
              <input type='radio' id='31-or-more-y' name='31-or-more' value='y'/>
              <label for='bachelor-deg-y'>Yes</label>
            </td>
            <td>
              <input type='radio' id='31-or-more-n' name='31-or-more' value='n'
              checked='checked'/>
              <label for='31-or-more-n'>No</label>
            </td>
            <td>
              Are you transferring 31 or more credits to Queens from another college?
            </td>
           <tr>
          </tr>
        </table>
    </fieldset>
    <fieldset>
      <legend>Your College Option Requirements</legend>
      <p id='result'>You must take a Language, a Literature, a Science, and an additional
        course.
      </p>
    </fieldset>
  </form>

  </body>
</html>
