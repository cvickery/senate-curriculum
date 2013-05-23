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
    <script type="text/javascript" src="../js/site_ui.js"></script>
    <script type="text/javascript" src="../js/college_option.js"></script>
    <style type='text/css'>
      fieldset { margin: 1em 0 }
      label, input {
        margin:0; padding:0.25em;
      }
      table {
        border: 1px solid black;
        border-radius: 0.25em;
        box-shadow: #999 0.25em 0.25em;
      }
      table td {
        border: 0;
        }
      td {
        padding:0.25em;
        }
      #ask-num-prev-co :last-child { padding: 0.25em 0.25em 0.25em 1em; }
      #prev-co-courses input {display:block; width:90%; margin:auto; }
      #result {
        margin:0 1em;
        padding: 0.5em;
        border-radius: 0.25em;
        background-color:white;
        font-style:italic;
      }
      #result + * { line-height:1.5em; }
    </style>
  </head>
  <body>
<?php echo $instructions_button;?>
  <h1>College Option Calculator</h1>
  <form action='<?php echo $_SERVER['PHP_SELF'];?>' method='post'>
    <input  type='hidden'
            name='form-name'
            value='college-option' />
    <fieldset>
      <legend>Questions</legend>
      <p class='instructions'>
        Answer the following questions to see what College Option courses you will need
        to take at Queens College.
      </p>
        <table>
          <tr id='ask-bachelor'>
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
              Do you already have a Bachelor’s degree?
            </td>
          </tr>
          <tr id='ask-began'>
           <td>
              <input type='radio' id='began-2' name='began' value='2' checked='checked' />
              <label for='began-2'>2-year</label>
            </td>
            <td>
              <input type='radio' id='began-4' name='began' value='4' />
              <label for='began-4'>4-year</label>
            </td>
             <td>
              Did you first start taking college level courses in a 2-year program, or
              in a 4-year program?
            </td>
          </tr>
          <tr id='ask-associate'>
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
              Do you have an Associate’s degree?
            </td>
          </tr>
          <tr id='ask-31-or-more'>
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
          </tr>
          <tr id='ask-if-prev-co'>
            <td>
              <input type='radio' id='prev-co-y' name='prev-co' value='y'/>
              <label for='prev-co-y'>Yes</label>
            </td>
            <td>
              <input type='radio' id='prev-co-n' name='prev-co' value='n'
              checked='checked'/>
              <label for='prev-co-n'>No</label>
            </td>
            <td>
              Have you received credit for College Option courses from another CUNY
              senior college?
            </td>
          </tr>
          <tr id='ask-num-prev-co'>
            <td colspan='2'>
              <input  name='num-prev-co' id='num-prev-co'
                      type='number' min='0' max='4' step='1' />
            </td>
            <td>
              How many College Option courses have you completed at another CUNY
              senior college?
            </td>
          </tr>
        </table>
    </fieldset>
    <fieldset>
      <legend>Your College Option Requirements</legend>
      <p>
        Based on the answers above:
      </p>
      <p id='result'>You must take a Language, a Literature, a Science, and an additional
        course. [CO04]
      </p>
      <p>
        <strong>Note: </strong> This analysis is only as accurate as your input!<br/>
        To be sure you answered each question correctly, consult with an advisor
        in the <a href='http://advising.qc.cuny.edu'>Office of Academic Advisement</a>.
      </p>
    </fieldset>
  </form>

  </body>
</html>
