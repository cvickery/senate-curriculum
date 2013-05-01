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
    <title>Enter Course Test</title>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/enter_course.js"></script>
    <script type="text/javascript" src="../js/scrollIntoView.min.js"></script>
     <link rel="stylesheet" type="text/css" href="../css/curriculum.css" />
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
        position: absolute;
        top:0;
        right: 0;
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
      .highlight {
        background-color:#99f;
        color:white;
       }
    </style>
  </head>
  <body>

  <h1>Enter Course Test</h1>
<?php
  //  Show course(s) selected if form was submitted.
  if ( !empty($form_name) and $form_name === 'enter_course_test')
  {
    $course = sanitize($_POST['course']);
    echo "<h2>'$course'</h2>\n";
  }
?>
    <form action='enter_course.php' method='post'>
      <input  type='hidden'  
              name='form-name' 
              value='enter_course_test' />
      <fieldset>
        <label for='discipline'>Discipline</label>
        <label for='course_number'>Number</label>
        <input  type='text'    
                name='discipline' 
                id='discipline'
                autocomplete='off' />
        <input  type='text'    
                name='course-number' 
                id='course_number'
                autocomplete='off' />
      </fieldset>
    </form>
  </body>
</html>
