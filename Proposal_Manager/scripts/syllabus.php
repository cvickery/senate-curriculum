<?php
echo "<!-- syllabus.php -->\n";
//  Manage syllabus uploads and access
//  ======================================================================================
/*
 *  Users can upload any file they want and call it a “syllabus” for a “course.”
 *  Ultimately, review panels will see if the uploaded document really is a syllabus for
 *  the course indicated, so the goal here is to provide users with as much flexibility
 *  here as possible, while guiding them into doing the right thing.
 *
 *  The canonical mode of operation is to open a proposal and then use this section to
 *  upload a syllabus for the course that is the subject of the proposal. So, if $proposal
 *  is defined, we use the information found there. But we also provide a text field in
 *  the upload form so the user can override the course name or even to provide one
 *  if no proposal is open.
 */

  //  Determine system size limit for file/post uploads
  //  ------------------------------------------------------------------------------------
  $max_upload_size  = trim(ini_get('upload_max_filesize'));
  $suffix = strtolower($max_upload_size[strlen($max_upload_size) - 1]);
  $max_upload_size = substr($max_upload_size, 0, strlen($max_upload_size) - 1);
  switch ($suffix)
  {
    case 'g': $max_upload_size *= 1024;
    case 'm': $max_upload_size *= 1024;
    case 'k': $max_upload_size *= 1024;
  }
  $max_post_size  = trim(ini_get('upload_max_filesize'));
  $suffix = strtolower($max_post_size[strlen($max_post_size) - 1]);
  $max_post_size = substr($max_post_size, 0, strlen($max_post_size) - 1);
  switch ($suffix)
  {
    case 'g': $max_post_size *= 1024;
    case 'm': $max_post_size *= 1024;
    case 'k': $max_post_size *= 1024;
  }

  $max_size = ($max_upload_size > $max_post_size) ? $max_post_size : $max_upload_size;
  $max_size_str = humanize_num($max_size);  // Used in instructions

  //  Deterime whether there is a course selected for possible syllabus upload yet
  //  ------------------------------------------------------------------------------------
  $course_str           = 'Select course';
  $proposal_course_str  = '';
  $upload_course_str    = '';
  if (isset($proposal) && $proposal !== null)
  {
    $proposal_course_str = $proposal->discipline . ' ' . $proposal->course_number;
  }
  if ($form_name === 'syllabus-upload')
  {
    $upload_course_str = strtoupper(sanitize($_POST[course_str]));
  }
  $warning = '';
  if ($proposal_course_str  !== '' &&
      $upload_course_str    !== '' &&
      $proposal_course_str  !== $upload_course_str)
  {
    $warning = <<<EOD
    <p class='warning'>
      <strong>Note:</strong>
      Upload was for $upload_course_str.
      Current proposal is for $proposal_course_str.
    </p>

EOD;
  }
  if ($proposal_course_str !== '')
  {
    $course_str = $proposal_course_str;
  }
  else if ($upload_course_str !== '')
  {
    $course_str = $upload_course_str;
  }
  echo <<<EOD
    <h2 id='upload-syllabus-section'>Upload Syllabus: $course_str</h2>
    <div>
EOD;

  //  See if there as a syllabus upload operation to complete
  //  ----------------------------------------------------------------------------------
  if (isset ($_FILES) && isset($_FILES['syllabus_file']))
  {
    $upload_ok = false;
    //  Check file within system size limit, and valid filename extension
    $script_file      = basename(__FILE__); // for diagnostics
    $upload           = $_FILES['syllabus_file'];
    $upload_name      = strtolower($upload['name']);
    $upload_size      = $upload['size'];
    $upload_size_str  = number_format($upload_size);
    if ($max_size > $upload['size'] && $upload['error'])
    {
      echo <<<EOD
    <p class='error'>Upload of $upload_name failed. $upload_size_str exceeds
    the maximum file size allowed ($max_size_str).</p>
EOD;
    }
    else
    {
      // PROCESS THE UPLOADED FILE
      // Supported file types and processing steps:
      // ------------------------------------------
      /*
       *  PDF  No conversion needed.
       *  txt, md, html, odt, docx: Use pandoc.
       */
      $tmp_name = $upload['tmp_name'];

      $extension = '';
      if (preg_match('/\.([a-z]{2,})$/i', $upload_name, $matches))
      {
        $candidate = strtolower($matches[1]);
        foreach ($valid_extensions as $ext)
        {
          if ($candidate === $ext)
          {
            $extension = $candidate;
            break;
          }
        }
      }
      if ($extension !== '')
      {
        // JavaScript has already reported invalid extension via an alert, it has to be valid if
        // execution gets here.

        // var_dump($matches); echo "<hr/>";
        // var_dump($upload_name); echo "<hr/>";
        // var_dump($tmp_name); echo "<hr/>";

        //  Generate the new base name (DISCP-catalog_number-date)
        preg_match('/^\s*([a-z]+)[ -]*(\d+.?)\s*$/i', $course_str, $matches);
        $discipline = strtoupper($matches[1]);
        $course_number = $matches[2];
        $base_name = $discipline . '-' . $course_number . '_' . date('Y-m-d');
        $file_name = "${base_name}.$extension";

        // Move it to the conversion directory.
        $file_to_convert = "../Syllabi/To_Convert/$file_name";
        $result = move_uploaded_file($tmp_name, $file_to_convert);
        if (! $result)
        {
          $location = basename(__FILE__) . '; line ' . __LINE__;
          echo <<<EOD
    <p class='error'>
      Syllabus upload of $upload_name failed. Please report the problem to $webmaster_email. Error
      message is “{$location}.”
    </p>
EOD;
        }
        else
        {
          //  Uploaded file is in To_Convert directory
          $pandoc = "/usr/local/bin/pandoc --pdf-engine /Library/TeX/texbin/pdflatex";
          switch ($extension)
          {
            case 'pdf':
              break;
            case 'docx':
            case 'odt':
            case 'html':
            case 'md':
            case 'txt':
              system("(cd ../Syllabi/To_Convert; $pandoc -i $file_name -o $base_name.pdf;)");
              break;
            default:
              echo <<<EOD
        <p class='error'>
          Syllabus upload failed: “${extension}” is not a recognized file type.
        </p>
EOD;
              break;
          }

          // PDF file should exist, either by direct upload or by conversion processing.
          $converted_file = "../Syllabi/To_Convert/$base_name.pdf";
          if (!file_exists($converted_file))
          {
            echo <<<EOD
      <p class='error'>
        Syllabus conversion from $upload_name to $file_name failed. Please report the problem to
        $webmaster_email.”
      </p>
EOD;
          }
          else
          {
            //  Move the PDF into place and delete the uploaded file if it was not a PDF
            $destination = "../Syllabi/$base_name.pdf";
            rename($converted_file, $destination)
              // Configuration Error
              or die('Rename failed ' . basename(__FILE__) . ' ' . __LINE__);
            unlink("../Syllabi/To_Convert/$base_name.$extension")
              // Configuration Error
              or die('Unlink failed ' . basename(__FILE__) . ' ' . __LINE__);
            //  Looks successful: happify the user and silently create a record in the
            //  syllabus_uploads table.
            echo <<<EOD
    <p>
      <strong>Upload successful. Syllabus is now available at <a href="../Syllabi/{$base_name}.pdf"
      target="_blank">{$base_name}.pdf</a></strong>.
    </p>
EOD;
            $remote_ip = 'Unknown IP';
            if (isset($_SERVER['REMOTE_ADDR']))
            {
              $remote_ip = $_SERVER['REMOTE_ADDR'];
            }
            $doc_type = substr($extension, 1);
            $query = <<<EOD
      INSERT INTO syllabus_uploads
      VALUES (now(),                          -- saved_date
              '$base_name'||' '||'$doc_type', -- file_name
              '{$person->name}',              -- saved_by
              '$remote_ip')                   -- saved_from

EOD;
            $result = pg_query($curric_db, $query) or die("<h1 class='error'>Database error: "
                      . pg_last_error($curric_db) . ' ' . basename(__FILE__) . ' ' . __LINE__
                      . "Please report this problem to $webmaster_email.</h1></body></html>");
          }
        }
      }
      else
      {
        echo <<<EOD
    <p class='error'>
      I don’t know how to convert “${upload_name}” to PDF.
    </p>
EOD;
      }
    }
  }


//  Output Section
//  --------------------------------------------------------------------------------------

//  Display list of currently-available syllabi for this course, if any
    $syllabi_msg = '';
    if ($course_str !== 'Select course')
    {
      $current_syllabi = get_syllabi($course_str);
      $num_syllabi = count($current_syllabi);
    }
    else $num_syllabi = 0;
    if ($num_syllabi > 0)
    {
      //  List all syllabi for the course that have been uploaded to date.
      $what = 'A syllabus';
      $copula = 'was';
      $plural = '';
      if ($num_syllabi > 1)
      {
        $what = 'Syllabi';
        $copula = 'were';
        $plural = 's';
      }
      $num_syllabi = num2str($num_syllabi);
      echo <<<EOD
    <h3>Available Syllabi</h3>
    <p>
      $what for $course_str $copula saved on the date$plural
      indicated:
    </p>
    <ul>

EOD;
      foreach ($current_syllabi as $date_str => $pathname)
      {
        $saved_date     = substr($date_str, 0, 10) . ' ' . substr($date_str, 14, 5);
				$human_date_obj = new DateTime($saved_date);
				$human_date_str = $human_date_obj->format('F j, Y \a\t g:i ')
				    . substr($date_str, 20, 2);
        $query = <<<EOD
SELECT saved_by
FROM syllabus_uploads
WHERE to_char(saved_date, 'YYYY-MM-DD HH:MI') = '$saved_date'

EOD;
        $result = pg_query($curric_db, $query) or die("<h1 class='error'>System error." .
            "Please report the propblem to $webmaster_email. Error message: " .
            basename(__FILE__) . ' ' . __LINE__ . "</h1></body></html>");
        $byline = '';
        if (pg_num_rows($result) === 1)
        {
          $row = pg_fetch_assoc($result);
          $byline = ' by ' . $row['saved_by'];
        }
        echo "      <li><a href='$pathname'>$human_date_str</a> $byline</li>\n";
      }
      echo "    </ul>\n";
    }
    else
    {
      if ($course_str === 'Select course' || trim($course_str) === '')
      {
        $syllabi_msg = '';
      }
      else
      {
        $syllabi_msg =
            "<p class='error'>There is no syllabus available for $course_str yet.</p>";
      }
    }
    echo <<<EOD
    <form action='.' method='post' enctype='multipart/form-data'>
        <fieldset id='syllabus-upload'><legend>Syllabus</legend>
          $warning
          <input type='hidden' name='form-name' value='syllabus-upload' />
          $syllabi_msg
          <fieldset><legend>Upload a Syllabus</legend>
            <div class='instructions'>
              <p>
                Syllabi are stored as PDFs, but you may submit them in any of the following file
                formats ...
              </p>
              <ul>
                <li>
                  Microsoft Word (.docx or .doc file extension)
                </li>
                <li>
                  Rich Text Format (.rtf file extension)
                </li>
                <li>
                  Open Office / Libre Office (.odt file extension)
                </li>
                <li>
                  Markdown (.md file extension)
                </li>
                <li>
                  Plain text (.txt file extension)
                </li>
              </ul>
              <p>
                ... and we will convert them into PDFs for you automatically.
              </p>
              <p>
                If the syllabus is maintained as a web page, open it in your browser, save it as
                a PDF document on your computer, and upload that. Or, if your syllabus is a
                self-contained .html file (no links to stylesheets or JavaScript), you can upload it
                for us to convert to PDF here, but check the result to be sure it looks okay.
              </p>
              <p>
                There is a size limit of $max_size_str for uploaded files on this computer. Files
                larger than that will cause the upload operation to fail.
              </p>
              <blockquote style="border:1px solid #ccc; border-radius:0.5em; padding:0.5em">
                The CUNY Common Core Review Committee (CCRC) will not accept syllabi larger than
                500 KB, so be sure your sample syllabus does not exceed that limit if you are
                submitting a proposal for one of the Common Core designations (EC-2, MQR, LPS, WCGI,
                USED, CE, IS, or SW).
              </blockquote>
              <p>
                If you want to submit multiple syllabi for the same course, such as a set
                of sample syllabi for a variable topics course, combine them into a single
                file, each starting on a separate page, and submit the combined file.
              </p>
            </div>
            <div>
              <label for='syllabus-file'>Syllabus File:</label>
              <input type='file' id='syllabus-file' name='syllabus_file' />
            </div>
            <div class='course-str-div'>
              <label for='course-str-s'>Course:</label>
              <input  type='text'
                      name='course_str'
                      id='course-str-s'
                      class='course-str'
                      value='$course_str' />
              <span class='course-str-msg warning'> </span>
              <ul class='discipline-suggestion-list'></ul>
            </div>
            <div>
              <button type='submit'
                      id='upload-syllabus'
                      class='centered-button'
                      disabled='disabled'>
                        Upload Syllabus
              </button>
              <span class='unsaved-edits error'>
                Warning: you have made changes to proposal No. $proposal_id, which will be
                lost if you upload a syllabus without saving the proposal first.
              </span>
            </div>
        </fieldset>
      </fieldset>
    </form>
  </div>

EOD;
?>

