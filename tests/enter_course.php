<?php
//  /tests/enter_course.php

set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() . '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');

//  Test enter_course widget
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
    <title>Course Information</title>
    <link rel="stylesheet" type="text/css" href="../css/curriculum.css" />
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/enter_course.js"></script>
    <script type="text/javascript" src="../js/scrollIntoView.min.js"></script>
    <style type='text/css'>
      label, input {
        display: block;
        float: left;
        width: 200px;
      margin:0; padding:0.25em;
      }
      label {
        border:2px solid transparent;
      }
      input {
        border:2px inset ;
      }
      #discipline { clear:left; }
      #prompt-list {
        display:none;
        background-color:white;
        list-style-type: none;
        padding:0;
        overflow-y:auto;
        max-height:560px;
      }
      #prompt-list li { padding: 0.25em; margin:0;
      border: 1px solid black;
      }
      form {
        position: relative;
      }
      button {display:block; clear: both; }
      .highlight {
        background-color:#99f;
        color:white;
       }
    </style>
  </head>
  <body>

  <h1>Course Information</h1>
  <form action='enter_course.php' method='post'>
    <input  type='hidden'  
            name='form-name' 
            value='course-info' />
    <fieldset>
      <legend>Enter Course</legend>
      <label for='discipline'>Discipline</label>
      <label for='course_number'>Course Number</label>
      <input  type='text'    
              name='discipline' 
              id='discipline'
              autocomplete='off' />
      <input  type='text'    
              name='course-number' 
              id='course_number'
              autocomplete='off' />
      <button type='submit'>Lookup</button>
    </fieldset>
  </form>

<?php
  //  Show course(s) selected if form was submitted.
  if ( !empty($form_name) and $form_name === 'course-info')
  {
    $discipline     = sanitize($_POST['discipline']);
    $course_number  = sanitize($_POST['course-number']);
    echo "<h2>CUNYfirst information for $discipline $course_number</h2>\n";
  }
?>
  </body>
</html>
