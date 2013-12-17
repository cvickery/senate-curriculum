<?php
//  /Lookup_Course/index.php

set_include_path(get_include_path()
    . PATH_SEPARATOR . getcwd() . '/../scripts'
    . PATH_SEPARATOR . getcwd() . '/../include');
require_once('init_session.php');

//  Get form data, if any.
//  -------------------------------------------------------------------------------------

$discipline_value     = $discipline     = '';
$course_number_value  = $course_number  = '';
  if ( !empty($form_name) and $form_name === 'course-info')
  {
    $discipline     = sanitize($_POST['discipline']);
    $course_number  = sanitize($_POST['course-number']);
    $course_number  = preg_replace('/[a-z\.]/i', '', $course_number);
    $course_number  = preg_replace('/^0*/', '', $course_number);

    $discipline_value     = " value='$discipline'";
    $course_number_value  = " value='$course_number'";
  }

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
      td, th {
        padding: 0.25em;
        text-align: center;
      }
      .error {
        background-color: red;
        color: white;
      }
    </style>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/enter_course.js"></script>
    <script type="text/javascript" src="../js/scrollIntoView.min.js"></script>
  </head>
  <body>

  <h1>Course Information</h1>
  <p>
    <?php
      echo "Catalog Information last updated on $cf_update_date";
    ?>.
  </p>
  <form action='./index.php' method='post'>
    <input  type='hidden'
            name='form-name'
            value='course-info' />
    <fieldset>
      <legend>Select Course</legend>
      <div class='instructions'>
        Select the course discipline from the list, and enter a single course number without
        decimal points or leading zeros, and all variants (W and H), if any, will be shown.
      </div>
      <label for='discipline'>Discipline</label>
      <label for='course_number'>Course Number</label>
      <input  type='text'
              name='discipline'
              id='discipline'
              autocomplete='off'
              <?php echo $discipline_value; ?>/>
      <input  type='text'
              name='course-number'
              id='course_number'
              autocomplete='off'
              <?php echo $course_number_value; ?>/>
      <button type='submit'>Lookup</button>
    </fieldset>
  </form>

<?php
  //  Show course(s) selected if form was submitted.
  if ( $discipline !== '' )
  {
    $query = <<<EOD
select * from cf_catalog
where lower(discipline) = lower('$discipline')
and (course_number ~* '^{$course_number}[WH]?$')

EOD;
    $result = pg_query($curric_db, $query) or die("<h1 class='error'>Curric query failed: "
        . basename(__FILE__) . " line " . __LINE__
        . "</h1></body></html>\n");
    $num = pg_num_rows($result);
    if ($num < 1)
    {
      echo "<h2>No CUNYfirst data found for $discipline $course_number</h2>\n";
    }
    else
    {
      while ($row = pg_fetch_assoc($result))
      {
        $course_id      = $row['course_id'];
        $effective_date = new DateTime($row['effective_date']);
        $effective_date = $effective_date->format('F d, Y');
        $is_active      = ('A' === $row['status']) ? 'yes' : 'no';
        $can_schedule   = ('Y' === $row['schedule']) ? 'yes' : 'no';
        $course         = $row['discipline'] . '—' . $row['course_number'];
        $division       = $row['division'];
        $department     = $row['department'];
        $level          = ('UGRD' === $row['career']) ? 'Undergrad' : 'Grad';
        $component      = ' ' . trim(strtolower($row['component']));
        $uc_component		= strtoupper($component);
        $hours          = $row['hours'];
        $credits        = $row['credits'];
        $title          = trim($row['course_title']);
        if ( empty($title) )
        {
          $title = 'MISSING TITLE';
        }
        if (strtoupper($title) === $title)
        {
          $title = "<span class='error'>[$title]</span>";
        }
        $catalog_description  = trim($row['catalog_description']);
        if ( empty($catalog_description) )
        {
          $catalog_description = "<span class='error'>[None]</span>";
        }
        $designation          = $row['designation'];
        $prerequisites        = trim($row['prerequisites']);
        if (empty($prerequisites))
        {
          $prerequisites = "No prerequisites";
        }
        $designation_table = '';
        $designations = lookup_designations($discipline, $course_number);
        if ($designations !== '')
        {
          $designation_table = <<<EOD
      <p>$course has been approved for the following requirement designations:</p>
      <table>
        <tr><th>Abbr.</th><th>Designation</th><th>Approval Basis</th></tr>
        $designations
      </table>

EOD;
        }
      }
      echo <<<EOD
      <h2>$course: $title. $uc_component</h2>
      <div>
        <table>
          <tr>
            <th>Course ID</th>
            <th>Effective Date</th>
            <th>Is Active</th>
            <th>Can Schedule</th>
            <th>Department</th>
            <th>Division</th>
            <th>Level</th>
            <th>Designation</th>
          </tr>
          <tr>
            <td>$course_id</td>
            <td>$effective_date</td>
            <td>$is_active</td>
            <td>$can_schedule</td>
            <td>$department</td>
            <td>$division</td>
            <td>$level</td>
            <td>$designation</td>
          </tr>
        </table>
        <p>${hours}hr${component}; ${credits}cr; $prerequisites</p>
        <h3>Catalog Description:</h3>
        <p>$catalog_description</p>
        $designation_table
      </div>

EOD;
    }
  }

?>
  </body>
</html>

