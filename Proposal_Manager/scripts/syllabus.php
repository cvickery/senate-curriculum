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
      $tmp_name = $upload['tmp_name'];

      $extension = '';
      if (preg_match('/(\.[a-z]{3,5})$/i', $upload_name, $matches))
      {
        $extension = '';
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
        //  Generate the new base name (DISCP-num-date)
        preg_match('/^\s*([a-z]+)[ -]*(\d+.?)\s*$/i', $course_str, $matches);
        $discipline = strtoupper($matches[1]);
        $course_number = $matches[2];
        $base_name = $discipline . '-' . $course_number . '_' . date('Y-m-d');
        $file_name = $base_name . $extension;

        if ($extension === '.pdf')
        {
          //  If PDF, move it to syllabi directory
          $destination = "../Syllabi/$file_name";
          if (file_exists($destination))
          {
            unlink($destination) or die('Unlink failed ' . basename(__FILE__) . ' ' .
                __LINE__);
          }
          $result = move_uploaded_file($tmp_name, $destination);
          if (! $result)
          {
            $location = basename(__FILE__) . ' line ' . __LINE__;
            echo <<<EOD
      <p class='error'>
        Syllabus upload of $upload_name failed. Please report the problem
        to $webmaster_email. Error message is “{$location}.”
      </p>
EOD;
          }
          else
          {
            $upload_ok = true;
          }
        }
        else
        {
          //  Otherwise, move it to the conversion directory.
          $destination = "../Syllabi/To_Convert/$file_name";
          if (file_exists($destination))
          {
            unlink($destination) or die('Unlink failed ' . basename(__FILE__) . ' ' .
                __LINE__);
          }
          $result = move_uploaded_file($tmp_name, $destination);
          if (! $result)
          {
            $location = basename(__FILE__) . ' ' . __LINE__;
            echo <<<EOD
      <p class='error'>
        Syllabus upload of $upload_name failed. Please report the problem
        to $webmaster_email. Error message is “{$location}.”
      </p>
EOD;
          }
          else
          {
            $upload_ok = true;
          }
        }
      }
      else
      {
        echo <<<EOD
    <p class='error'>
      Syllabus upload failed: '$upload_name' does not have a recognized file type.
      <br />Recognized extensions are .pdf, .doc, .docx, .rtf, and .pages.
    </p>
EOD;
      }
    }
    //  Looks successful: silently create a record in the syllabus_uploads table.
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
    $result = pg_query($curric_db, $query) or die("<h1 class='error'>Database error: " .
        pg_last_error($curric_db) . ' ' . basename(__FILE__) . ' ' . __LINE__ .
        "Please report this problem to $webmaster_email.</h1></body></html>");
  }


//  Output Section
//  --------------------------------------------------------------------------------------
    echo <<<EOD
      <p class='warning'>
        If you upload a non-PDF file, it will take a few seconds to convert it,
        and it will look like nothing happened when you click the “Upload Syllabus”
        button. But if you reload this page after waiting a bit, the new file
        should be listed here.
      </p>

EOD;
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
                Syllabi are stored as PDFs, but you may submit them in any of the
                following file formats ...
              </p>
              <ul>
                <li>
                  Microsoft Word (.doc or .docx file extension)
                </li>
                <li>
                  Rich Text Format (.rtf file extension)
                </li>
                <li>
                  Apple Pages (.pages file extension)
                </li>
                <li>
                  Plain text (.txt file extension)
                </li>
              </ul>
              <p>
                ... and we will convert them into PDFs for you automatically. <span
                class='warning'>See note below.</span>
              </p>
              <p>
                Alternatively, if the syllabus is maintained as a web page, there are two
                options, both of which are equally acceptable:
              </p>
              <ol>
                <li>
                  Prepare a file in any one of the above formats with the full URL of the
                  syllabus as its content. Make sure the URL starts with http:// or
                  https://, and upload the file here. The resulting PDF will include a
                  clickable link to the web page.
                  <p>
                    The web page must be publicly-available on the Internet for this
                    option to work. There must be no passwords or secret handshakes
                    required for accessing the page.
                  </p>
                </li>
                <li>
                  Convert the web page to one of the formats listed above, and then upload
                  the resulting file here. There are services on the Web for doing this
                  conversion. Consult with the Center for Teaching and Learning if you
                  need help with this option.
                  <p>
                    Note that the resulting PDF might not look like your web page after
                    conversion, so don’t use this option if that possibility is a problem.
                  </p>
                </li>
              </ol>
              <p>
                There is a size limit of $max_size_str for uploaded files. Files
                larger than that will cause the upload operation to fail.
              </p>
              <p>
                We keep a historic archive of all syllabi submitted for a course, so even
                if you revise a syllabus, older versions will still be accessible.
              </p>
              <p>
                If you want to submit multiple syllabi for the same course, such as a set
                of sample syllabi for a variable topics course, combine them into a single
                file, each starting on a separate page, and submit the combined file.
              </p>
              <p>
                It takes a minute or so for syllabi to be converted to PDF form, so they
                won’t show up right away. But if you upload a PDF, that should show up
                here immediately.
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

