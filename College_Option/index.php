<?php
//  /dev/college_option.php

set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() . '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');

//  College Option Calculator
//  -------------------------------------------------------------------------------------
/*    Display a statement of either: what college option courses a student needs to take
 *    or what student group, if any, applies to a student's situation.
 *
 *    If the GET query string includes 'explain' as a key, display a technical explanation
 *    and the student group code that applies, if any.
 *
 *    Always display the set of courses the student must take.
 *
 *    TODO: provide links to the course lists for the courses the student must take.
 *
 *    Requires JavaScript to operate.
 */

//  Tailor the instructions to the type of statement that will be generated, and attach
//  the student-group-report class to the form so JavaScript will know what to display.
 $form_class = '';
 $instructions = <<<EOD
Answer the following questions to see what College Option courses you will need to take at
Queens College.  You may also <a href='{$_SERVER['PHP_SELF']}?explain'>display technical
explanations</a> with the results.
EOD;
  if (isset($_GET['explain']))
  {
    $form_class = " class='explain'";
    $instructions = <<<EOD
Answer the following questions to see what College Option courses you will need to take at
Queens College, with a technical explanation. You may also display the results <a
href='{$_SERVER['PHP_SELF']}'>without the technical explanations</a>.
EOD;
  }

//  Initial values for inputs
//  -------------------------------------------------------------------------------------
/*  The following code gets executed, but has no effect because there is currently no way
 *  to actually submit the form because JavaScript prevents it.
 *  By changing the links in the instructions to submit buttons, removing the JavaScript
 *  code that prevents submissions, and the JavaScript code that initializes the settings,
 *  this code would let the user change the 'explain' option without losing their current
 *  set of answers.
 */
  $bachelor_y_checked = '';
  $bachelor_n_checked = "checked='checked'";
  if ( isset($_GET['bachelor-degree']) )
  {
    $bachelor_y_checked = "checked-'checked'";
    $bachelor_n_checked = '';
  }

  $associate_y_checked = '';
  $associate_n_checked = "checked='checked'";
  if ( isset($_GET['associate-degree']) )
  {
    $associate_y_checked = "checked='checked'";
    $associate_n_checked = '';
  }

  $began_2_checked = "checked='checked'";
  $began_4_checked = '';
  if ( isset($_GET['began']) )
  {
    $began_2_checked = '';
    $began_4_checked = "checked='checked'";
  }

  $over_30_y_checked = '';
  $over_30_n_checked = "checked='checked'";
  if ( isset($_GET['over-30']) )
  {
    $over_30_y_checked = "checked='checked'";
    $over_30_n_checked = '';
  }

  $prev_co_y_checked = '';
  $prev_co_n_checked = "checked='checked'";
  if ( isset($_GET['over-30']) )
  {
    $prev_co_y_checked = "checked='checked'";
    $prev_co_n_checked = '';
  }

  $num_prev_co = '';
  if ( isset($_GET['num-prev-co']) &&
      preg_match('/^[0-9]+$/', trim($_GET['num-prev-co'])) )
  {
    $num_prev_co = trim($_GET['num-prev-co']);
  }

  //  Generate the web page
  //  -----------------------------------------------------------------------------------
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
      h1 + p { text-align:center; font-weight:bold; }
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
      #num-prev-co {
        display:inline-block;
        width:50px;
        float:right;
        text-align:right;
        margin:0;padding:0;
      }
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

  <?php echo $instructions_button; ?>
  <h1>College Option Calculator</h1>
  <form action=<?php echo "'{$_SERVER['PHP_SELF']}'$form_class";?> method='post'>
    <input  type='hidden'
            name='form-name'
            value='college-option' />
    <fieldset>
      <legend>Questions</legend>
<?php
  echo <<<EOD
      <p class='instructions'>$instructions</p>
        <table>
          <tr id='ask-bachelor'>
            <td>
              <input  type='radio'
                      id='bachelor-degree-y'
                      name='bachelor-degree'
                      value='y'
                      $bachelor_y_checked />
              <label for='bachelor-deg-y'>Yes</label>
            </td>
            <td>
              <input  type='radio'
                      id='bachelor-degree-n'
                      name='bachelor-degree'
                      value='n'
                      $bachelor_n_checked/>
              <label for='bachelor-degree-n'>No</label>
            </td>
            <td>
              Do you have a Bachelor’s degree?
            </td>
          </tr>
          <tr id='ask-associate'>
            <td>
              <input  type='radio'
                      id='associate-degree-y'
                      name='associate-degree'
                      value='y'
                      $associate_y_checked />
              <label for='associate-deg-y'>Yes</label>
            </td>
            <td>
              <input  type='radio'
                      id='associate-degree-n'
                      name='associate-degree'
                      value='n'
                      $associate_n_checked />
              <label for='associate-degree-n'>No</label>
            </td>
            <td>
              Do you have an Associate’s degree?
            </td>
          </tr>
          <tr id='ask-began'>
           <td>
              <input  type='radio'
                      id='began-2'
                      name='began'
                      value='2'
                      $began_2_checked />
              <label for='began-2'>2-year</label>
            </td>
            <td>
              <input  type='radio'
                      id='began-4'
                      name='began'
                      value='4'
                      $began_4_checked />
              <label for='began-4'>4-year</label>
            </td>
             <td>
              Did you first start taking college level courses in a 2-year program, or
              in a 4-year program?
            </td>
          </tr>
          <tr id='ask-over-30'>
            <td>
              <input  type='radio'
                      id='over-30-y'
                      name='over-30'
                      value='y' />
              <label for='over-30-y'>Yes</label>
            </td>
            <td>
              <input  type='radio'
                      id='over-30-n'
                      name='over-30'
                      value='n' />
              <label for='over-30-n'>No</label>
            </td>
            <td>
              Are you transferring more than 30 credits to Queens from another college?
            </td>
          </tr>
          <tr id='ask-if-prev-co'>
            <td>
              <input  type='radio'
                      id='prev-co-y'
                      name='prev-co'
                      value='y' />
              <label for='prev-co-y'>Yes</label>
            </td>
            <td>
              <input  type='radio'
                      id='prev-co-n'
                      name='prev-co'
                      value='n' />
              <label for='prev-co-n'>No</label>
            </td>
            <td>
              Have you received credit for College Option courses from another CUNY
              senior college?
            </td>
          </tr>
          <tr id='ask-num-prev-co'>
            <td colspan='2'>
              <input  name='num-prev-co'
                      id='num-prev-co'
                      type='number' min='0' step='1'
                      $num_prev_co />
            </td>
            <td>
              How many College Option credits have you completed at another CUNY
              senior college?
            </td>
          </tr>
        </table>
EOD;
  ?>
    </fieldset>
    <fieldset>
      <legend>Your College Option Requirements</legend>
      <p>
        Based on the questions above:
      </p>
      <div id='result'>
        <p id='need-javascript' class='error'>
          You need to enable JavaScript to use this web page.
        </p>
      </div>
      <p>
        <strong>Note: </strong><em>The result is only as accurate as your input!<br/> To
        be sure you answered each question correctly, consult with an advisor in the <a
        href='http://advising.qc.cuny.edu'>Office of Academic Advisement</a>.</em>
      </p>
    </fieldset>
  </form>

  </body>
</html>
